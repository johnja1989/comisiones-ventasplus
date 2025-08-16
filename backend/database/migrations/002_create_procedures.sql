-- =====================================================
-- Migration: 002_create_procedures.sql
-- Procedimientos Almacenados y Vistas
-- Sistema de Comisiones VentasPlus S.A.
-- =====================================================

USE comisiones_db;

-- Eliminar procedimientos si existen
DROP PROCEDURE IF EXISTS sp_calcular_comisiones;
DROP PROCEDURE IF EXISTS sp_actualizar_dashboard_metrics;
DROP PROCEDURE IF EXISTS sp_get_top_vendedores;
DROP VIEW IF EXISTS v_resumen_ventas_mensual;
DROP VIEW IF EXISTS v_top_vendedores_comision;
DROP VIEW IF EXISTS v_reporte_comisiones;

DELIMITER //

-- =====================================================
-- PROCEDIMIENTO: Calcular comisiones por período
-- =====================================================
CREATE PROCEDURE sp_calcular_comisiones(
    IN p_periodo VARCHAR(7)
)
BEGIN
    DECLARE v_parametro_id INT;
    DECLARE v_porcentaje_base DECIMAL(5,2);
    DECLARE v_porcentaje_bono DECIMAL(5,2);
    DECLARE v_limite_bono DECIMAL(12,2);
    DECLARE v_porcentaje_penalizacion DECIMAL(5,2);
    DECLARE v_limite_devoluciones DECIMAL(5,2);
    DECLARE v_count INT;
    
    -- Obtener parámetros activos
    SELECT id, porcentaje_base, porcentaje_bono, limite_bono, 
           porcentaje_penalizacion, limite_devoluciones
    INTO v_parametro_id, v_porcentaje_base, v_porcentaje_bono, 
         v_limite_bono, v_porcentaje_penalizacion, v_limite_devoluciones
    FROM parametros_comision
    WHERE estado = 'activo'
    AND (fecha_fin IS NULL OR fecha_fin >= LAST_DAY(CONCAT(p_periodo, '-01')))
    ORDER BY fecha_inicio DESC
    LIMIT 1;
    
    -- Verificar si se encontraron parámetros
    IF v_parametro_id IS NULL THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'No hay parámetros de comisión activos para el período';
    END IF;
    
    -- Crear tabla temporal con resumen de ventas
    DROP TEMPORARY TABLE IF EXISTS tmp_resumen_ventas;
    CREATE TEMPORARY TABLE tmp_resumen_ventas AS
    SELECT 
        v.vendedor_id,
        SUM(CASE WHEN v.tipo_operacion = 'venta' THEN v.valor_total ELSE 0 END) as total_ventas,
        SUM(CASE WHEN v.tipo_operacion = 'devolucion' THEN ABS(v.valor_total) ELSE 0 END) as total_devoluciones
    FROM ventas v
    WHERE DATE_FORMAT(v.fecha_venta, '%Y-%m') = p_periodo
    GROUP BY v.vendedor_id;
    
    -- Insertar o actualizar comisiones
    INSERT INTO comisiones (
        vendedor_id, parametro_id, periodo, total_ventas, 
        total_devoluciones, comision_base, bono_adicional, penalizacion,
        aplica_bono, aplica_penalizacion, estado, observaciones
    )
    SELECT 
        t.vendedor_id,
        v_parametro_id,
        p_periodo,
        t.total_ventas,
        t.total_devoluciones,
        -- Comisión base
        (t.total_ventas - t.total_devoluciones) * (v_porcentaje_base / 100),
        -- Bono adicional
        CASE 
            WHEN (t.total_ventas - t.total_devoluciones) > v_limite_bono 
            THEN (t.total_ventas - t.total_devoluciones) * (v_porcentaje_bono / 100)
            ELSE 0
        END,
        -- Penalización
        CASE 
            WHEN t.total_ventas > 0 AND (t.total_devoluciones / t.total_ventas * 100) > v_limite_devoluciones
            THEN (t.total_ventas - t.total_devoluciones) * (v_porcentaje_penalizacion / 100)
            ELSE 0
        END,
        -- Aplica bono
        CASE 
            WHEN (t.total_ventas - t.total_devoluciones) > v_limite_bono THEN TRUE
            ELSE FALSE
        END,
        -- Aplica penalización
        CASE 
            WHEN t.total_ventas > 0 AND (t.total_devoluciones / t.total_ventas * 100) > v_limite_devoluciones THEN TRUE
            ELSE FALSE
        END,
        'calculado',
        -- Observaciones
        CONCAT_WS('. ',
            CASE WHEN (t.total_ventas - t.total_devoluciones) > v_limite_bono 
                 THEN 'Bono aplicado por superar límite de ventas' END,
            CASE WHEN t.total_ventas > 0 AND (t.total_devoluciones / t.total_ventas * 100) > v_limite_devoluciones 
                 THEN 'Penalización aplicada por exceder límite de devoluciones' END,
            CASE WHEN (t.total_ventas - t.total_devoluciones) <= v_limite_bono 
                  AND (t.total_ventas = 0 OR (t.total_devoluciones / t.total_ventas * 100) <= v_limite_devoluciones)
                 THEN 'Comisión estándar aplicada' END
        )
    FROM tmp_resumen_ventas t
    ON DUPLICATE KEY UPDATE
        parametro_id = VALUES(parametro_id),
        total_ventas = VALUES(total_ventas),
        total_devoluciones = VALUES(total_devoluciones),
        comision_base = VALUES(comision_base),
        bono_adicional = VALUES(bono_adicional),
        penalizacion = VALUES(penalizacion),
        aplica_bono = VALUES(aplica_bono),
        aplica_penalizacion = VALUES(aplica_penalizacion),
        estado = VALUES(estado),
        observaciones = VALUES(observaciones),
        fecha_calculo = CURRENT_TIMESTAMP;
    
    -- Obtener cantidad de comisiones calculadas
    SELECT ROW_COUNT() INTO v_count;
    
    -- Actualizar métricas del dashboard
    CALL sp_actualizar_dashboard_metrics(p_periodo);
    
    -- Limpiar tabla temporal
    DROP TEMPORARY TABLE IF EXISTS tmp_resumen_ventas;
    
    -- Retornar resultado
    SELECT v_count as comisiones_calculadas, p_periodo as periodo;
    
END //

-- =====================================================
-- PROCEDIMIENTO: Actualizar métricas del dashboard
-- =====================================================
CREATE PROCEDURE sp_actualizar_dashboard_metrics(
    IN p_periodo VARCHAR(7)
)
BEGIN
    DECLARE v_total_vendedores INT;
    DECLARE v_vendedores_con_bono INT;
    
    -- Obtener totales
    SELECT 
        COUNT(*),
        SUM(CASE WHEN aplica_bono THEN 1 ELSE 0 END)
    INTO v_total_vendedores, v_vendedores_con_bono
    FROM comisiones
    WHERE periodo = p_periodo;
    
    -- Insertar o actualizar métricas
    INSERT INTO dashboard_metrics (
        periodo,
        total_ventas,
        total_comisiones,
        numero_vendedores,
        vendedores_con_bono,
        porcentaje_con_bono,
        promedio_devoluciones
    )
    SELECT 
        p_periodo,
        COALESCE(SUM(ventas_netas), 0),
        COALESCE(SUM(comision_final), 0),
        v_total_vendedores,
        v_vendedores_con_bono,
        CASE WHEN v_total_vendedores > 0 
             THEN (v_vendedores_con_bono / v_total_vendedores) * 100 
             ELSE 0 END,
        COALESCE(AVG(porcentaje_devoluciones), 0)
    FROM comisiones
    WHERE periodo = p_periodo
    ON DUPLICATE KEY UPDATE
        total_ventas = VALUES(total_ventas),
        total_comisiones = VALUES(total_comisiones),
        numero_vendedores = VALUES(numero_vendedores),
        vendedores_con_bono = VALUES(vendedores_con_bono),
        porcentaje_con_bono = VALUES(porcentaje_con_bono),
        promedio_devoluciones = VALUES(promedio_devoluciones),
        fecha_actualizacion = CURRENT_TIMESTAMP;
        
END //

-- =====================================================
-- PROCEDIMIENTO: Obtener top vendedores
-- =====================================================
CREATE PROCEDURE sp_get_top_vendedores(
    IN p_periodo VARCHAR(7),
    IN p_limit INT
)
BEGIN
    SELECT 
        v.id,
        v.codigo,
        v.nombre as vendedor,
        c.ventas_netas,
        c.comision_final,
        c.aplica_bono,
        c.aplica_penalizacion,
        RANK() OVER (ORDER BY c.comision_final DESC) as ranking
    FROM comisiones c
    INNER JOIN vendedores v ON c.vendedor_id = v.id
    WHERE c.periodo = p_periodo
    AND c.estado IN ('calculado', 'aprobado', 'pagado')
    ORDER BY c.comision_final DESC
    LIMIT p_limit;
END //

DELIMITER ;

-- =====================================================
-- VISTAS
-- =====================================================

-- Vista: Resumen de ventas por vendedor y mes
CREATE VIEW v_resumen_ventas_mensual AS
SELECT 
    v.vendedor_id,
    vd.nombre as vendedor_nombre,
    DATE_FORMAT(v.fecha_venta, '%Y-%m') as periodo,
    SUM(CASE WHEN v.tipo_operacion = 'venta' THEN v.valor_total ELSE 0 END) as total_ventas,
    SUM(CASE WHEN v.tipo_operacion = 'devolucion' THEN ABS(v.valor_total) ELSE 0 END) as total_devoluciones,
    SUM(CASE WHEN v.tipo_operacion = 'venta' THEN v.valor_total ELSE -ABS(v.valor_total) END) as ventas_netas,
    COUNT(CASE WHEN v.tipo_operacion = 'venta' THEN 1 END) as cantidad_ventas,
    COUNT(CASE WHEN v.tipo_operacion = 'devolucion' THEN 1 END) as cantidad_devoluciones,
    ROUND(
        CASE 
            WHEN SUM(CASE WHEN v.tipo_operacion = 'venta' THEN v.valor_total ELSE 0 END) > 0
            THEN (SUM(CASE WHEN v.tipo_operacion = 'devolucion' THEN ABS(v.valor_total) ELSE 0 END) / 
                  SUM(CASE WHEN v.tipo_operacion = 'venta' THEN v.valor_total ELSE 0 END)) * 100
            ELSE 0
        END, 2
    ) as porcentaje_devoluciones
FROM ventas v
INNER JOIN vendedores vd ON v.vendedor_id = vd.id
GROUP BY v.vendedor_id, periodo;

-- Vista: Top vendedores por comisión
CREATE VIEW v_top_vendedores_comision AS
SELECT 
    c.periodo,
    v.codigo,
    v.nombre as vendedor,
    c.ventas_netas,
    c.comision_base,
    c.bono_adicional,
    c.penalizacion,
    c.comision_final,
    c.aplica_bono,
    c.aplica_penalizacion,
    c.estado,
    RANK() OVER (PARTITION BY c.periodo ORDER BY c.comision_final DESC) as ranking
FROM comisiones c
INNER JOIN vendedores v ON c.vendedor_id = v.id
WHERE c.estado IN ('calculado', 'aprobado', 'pagado');

-- Vista: Detalle de comisiones para reporte
CREATE VIEW v_reporte_comisiones AS
SELECT 
    c.id,
    c.periodo,
    v.codigo as codigo_vendedor,
    v.nombre as nombre_vendedor,
    v.email as email_vendedor,
    c.total_ventas,
    c.total_devoluciones,
    c.ventas_netas,
    c.porcentaje_devoluciones,
    c.comision_base,
    c.bono_adicional,
    c.penalizacion,
    c.comision_final,
    CASE 
        WHEN c.aplica_bono THEN 'Sí'
        ELSE 'No'
    END as tiene_bono,
    CASE 
        WHEN c.aplica_penalizacion THEN 'Sí'
        ELSE 'No'
    END as tiene_penalizacion,
    c.estado,
    c.fecha_calculo,
    c.fecha_aprobacion,
    c.fecha_pago,
    c.observaciones,
    p.nombre as plan_comision,
    p.porcentaje_base,
    p.porcentaje_bono,
    p.limite_bono,
    p.porcentaje_penalizacion,
    p.limite_devoluciones
FROM comisiones c
INNER JOIN vendedores v ON c.vendedor_id = v.id
INNER JOIN parametros_comision p ON c.parametro_id = p.id;

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER //

-- Trigger: Actualizar estado de vendedor si tiene comisiones pendientes
CREATE TRIGGER trg_after_comision_update
AFTER UPDATE ON comisiones
FOR EACH ROW
BEGIN
    -- Si la comisión se marca como pagada, registrar en log
    IF NEW.estado = 'pagado' AND OLD.estado != 'pagado' THEN
        INSERT INTO log_importaciones (archivo, tipo, registros_procesados, estado, usuario, fecha_importacion)
        VALUES (
            CONCAT('Pago_Comision_', NEW.id),
            'mixto',
            1,
            'completado',
            USER(),
            NOW()
        );
    END IF;
END //

DELIMITER ;

-- =====================================================
-- Mensaje de confirmación
-- =====================================================
SELECT 'Procedimientos almacenados y vistas creados exitosamente' as Mensaje;