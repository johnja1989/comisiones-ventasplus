-- =====================================================
-- Script de creación de Base de Datos
-- Sistema de Comisiones VentasPlus S.A.
-- Fecha: Diciembre 2024
-- =====================================================

-- Eliminar base de datos si existe
DROP DATABASE IF EXISTS comisiones_db;

-- Crear base de datos
CREATE DATABASE comisiones_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_spanish_ci;

USE comisiones_db;

-- =====================================================
-- TABLA: vendedores
-- =====================================================
CREATE TABLE vendedores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20),
    fecha_ingreso DATE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vendedor_estado (estado),
    INDEX idx_vendedor_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- TABLA: productos
-- =====================================================
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    referencia VARCHAR(50) UNIQUE NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    categoria VARCHAR(100),
    precio_unitario DECIMAL(12,2) NOT NULL,
    estado ENUM('activo', 'descontinuado') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_producto_referencia (referencia),
    INDEX idx_producto_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- TABLA: ventas
-- =====================================================
CREATE TABLE ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendedor_id INT NOT NULL,
    producto_id INT NOT NULL,
    fecha_venta DATE NOT NULL,
    cantidad INT NOT NULL,
    valor_unitario DECIMAL(12,2) NOT NULL,
    valor_total DECIMAL(12,2) NOT NULL,
    impuesto DECIMAL(12,2) DEFAULT 0,
    valor_neto DECIMAL(12,2) GENERATED ALWAYS AS (valor_total + impuesto) STORED,
    tipo_operacion ENUM('venta', 'devolucion') DEFAULT 'venta',
    motivo_devolucion TEXT,
    archivo_origen VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendedor_id) REFERENCES vendedores(id),
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    INDEX idx_venta_fecha (fecha_venta),
    INDEX idx_venta_vendedor (vendedor_id),
    INDEX idx_venta_tipo (tipo_operacion),
    INDEX idx_venta_vendedor_fecha (vendedor_id, fecha_venta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- TABLA: parametros_comision
-- =====================================================
CREATE TABLE parametros_comision (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT,
    porcentaje_base DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    porcentaje_bono DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    limite_bono DECIMAL(12,2) NOT NULL DEFAULT 50000000,
    porcentaje_penalizacion DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    limite_devoluciones DECIMAL(5,2) NOT NULL DEFAULT 5.00,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE,
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_parametro_fecha (fecha_inicio, fecha_fin),
    INDEX idx_parametro_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- TABLA: comisiones
-- =====================================================
CREATE TABLE comisiones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendedor_id INT NOT NULL,
    parametro_id INT NOT NULL,
    periodo VARCHAR(7) NOT NULL COMMENT 'Formato: YYYY-MM',
    total_ventas DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_devoluciones DECIMAL(12,2) NOT NULL DEFAULT 0,
    ventas_netas DECIMAL(12,2) GENERATED ALWAYS AS (total_ventas - total_devoluciones) STORED,
    porcentaje_devoluciones DECIMAL(5,2) GENERATED ALWAYS AS 
        (CASE WHEN total_ventas > 0 THEN (total_devoluciones / total_ventas) * 100 ELSE 0 END) STORED,
    comision_base DECIMAL(12,2) NOT NULL DEFAULT 0,
    bono_adicional DECIMAL(12,2) DEFAULT 0,
    penalizacion DECIMAL(12,2) DEFAULT 0,
    comision_final DECIMAL(12,2) GENERATED ALWAYS AS 
        (comision_base + IFNULL(bono_adicional, 0) - IFNULL(penalizacion, 0)) STORED,
    aplica_bono BOOLEAN DEFAULT FALSE,
    aplica_penalizacion BOOLEAN DEFAULT FALSE,
    estado ENUM('calculado', 'aprobado', 'pagado', 'cancelado') DEFAULT 'calculado',
    fecha_calculo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_aprobacion TIMESTAMP NULL,
    fecha_pago TIMESTAMP NULL,
    observaciones TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendedor_id) REFERENCES vendedores(id),
    FOREIGN KEY (parametro_id) REFERENCES parametros_comision(id),
    UNIQUE KEY uk_comision_periodo (vendedor_id, periodo),
    INDEX idx_comision_periodo (periodo),
    INDEX idx_comision_estado (estado),
    INDEX idx_comision_vendedor (vendedor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- TABLA: log_importaciones
-- =====================================================
CREATE TABLE log_importaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    archivo VARCHAR(255) NOT NULL,
    tipo ENUM('ventas', 'devoluciones', 'mixto') NOT NULL,
    registros_procesados INT DEFAULT 0,
    registros_exitosos INT DEFAULT 0,
    registros_fallidos INT DEFAULT 0,
    fecha_importacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario VARCHAR(100),
    errores TEXT,
    estado ENUM('procesando', 'completado', 'error') DEFAULT 'procesando',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_fecha (fecha_importacion),
    INDEX idx_log_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================
-- TABLA: dashboard_metrics
-- =====================================================
CREATE TABLE dashboard_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    periodo VARCHAR(7) NOT NULL,
    total_ventas DECIMAL(15,2) DEFAULT 0,
    total_comisiones DECIMAL(15,2) DEFAULT 0,
    numero_vendedores INT DEFAULT 0,
    vendedores_con_bono INT DEFAULT 0,
    porcentaje_con_bono DECIMAL(5,2) DEFAULT 0,
    promedio_devoluciones DECIMAL(5,2) DEFAULT 0,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_metric_periodo (periodo),
    INDEX idx_metric_periodo (periodo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

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
    COUNT(CASE WHEN v.tipo_operacion = 'devolucion' THEN 1 END) as cantidad_devoluciones
FROM ventas v
INNER JOIN vendedores vd ON v.vendedor_id = vd.id
GROUP BY v.vendedor_id, periodo;

-- Vista: Top vendedores por comisión
CREATE VIEW v_top_vendedores_comision AS
SELECT 
    c.periodo,
    v.nombre as vendedor,
    c.ventas_netas,
    c.comision_final,
    c.aplica_bono,
    c.aplica_penalizacion,
    RANK() OVER (PARTITION BY c.periodo ORDER BY c.comision_final DESC) as ranking
FROM comisiones c
INNER JOIN vendedores v ON c.vendedor_id = v.id
WHERE c.estado IN ('calculado', 'aprobado', 'pagado');

-- Vista: Detalle de comisiones para reporte
CREATE VIEW v_reporte_comisiones AS
SELECT 
    c.periodo,
    v.codigo as codigo_vendedor,
    v.nombre as nombre_vendedor,
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
    c.fecha_calculo
FROM comisiones c
INNER JOIN vendedores v ON c.vendedor_id = v.id;

-- =====================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================

DELIMITER //

-- Procedimiento: Calcular comisiones por periodo
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
    
    -- Insertar o actualizar comisiones
    INSERT INTO comisiones (
        vendedor_id, parametro_id, periodo, total_ventas, 
        total_devoluciones, comision_base, bono_adicional, penalizacion,
        aplica_bono, aplica_penalizacion, estado
    )
    SELECT 
        v.vendedor_id,
        v_parametro_id,
        p_periodo,
        v.total_ventas,
        v.total_devoluciones,
        -- Comisión base
        (v.total_ventas - v.total_devoluciones) * (v_porcentaje_base / 100),
        -- Bono adicional
        CASE 
            WHEN (v.total_ventas - v.total_devoluciones) > v_limite_bono 
            THEN (v.total_ventas - v.total_devoluciones) * (v_porcentaje_bono / 100)
            ELSE 0
        END,
        -- Penalización
        CASE 
            WHEN v.total_ventas > 0 AND (v.total_devoluciones / v.total_ventas * 100) > v_limite_devoluciones
            THEN (v.total_ventas - v.total_devoluciones) * (v_porcentaje_penalizacion / 100)
            ELSE 0
        END,
        -- Aplica bono
        CASE 
            WHEN (v.total_ventas - v.total_devoluciones) > v_limite_bono THEN TRUE
            ELSE FALSE
        END,
        -- Aplica penalización
        CASE 
            WHEN v.total_ventas > 0 AND (v.total_devoluciones / v.total_ventas * 100) > v_limite_devoluciones THEN TRUE
            ELSE FALSE
        END,
        'calculado'
    FROM v_resumen_ventas_mensual v
    WHERE v.periodo = p_periodo
    ON DUPLICATE KEY UPDATE
        total_ventas = VALUES(total_ventas),
        total_devoluciones = VALUES(total_devoluciones),
        comision_base = VALUES(comision_base),
        bono_adicional = VALUES(bono_adicional),
        penalizacion = VALUES(penalizacion),
        aplica_bono = VALUES(aplica_bono),
        aplica_penalizacion = VALUES(aplica_penalizacion),
        fecha_calculo = CURRENT_TIMESTAMP;
        
    -- Actualizar métricas del dashboard
    CALL sp_actualizar_dashboard_metrics(p_periodo);
    
END //

-- Procedimiento: Actualizar métricas del dashboard
CREATE PROCEDURE sp_actualizar_dashboard_metrics(
    IN p_periodo VARCHAR(7)
)
BEGIN
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
        SUM(ventas_netas),
        SUM(comision_final),
        COUNT(*),
        SUM(CASE WHEN aplica_bono THEN 1 ELSE 0 END),
        (SUM(CASE WHEN aplica_bono THEN 1 ELSE 0 END) / COUNT(*)) * 100,
        AVG(porcentaje_devoluciones)
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

-- Procedimiento: Obtener top vendedores
CREATE PROCEDURE sp_get_top_vendedores(
    IN p_periodo VARCHAR(7),
    IN p_limit INT
)
BEGIN
    SELECT 
        v.nombre as vendedor,
        c.ventas_netas,
        c.comision_final,
        c.aplica_bono,
        c.aplica_penalizacion
    FROM comisiones c
    INNER JOIN vendedores v ON c.vendedor_id = v.id
    WHERE c.periodo = p_periodo
    ORDER BY c.comision_final DESC
    LIMIT p_limit;
END //

DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER //

-- Trigger: Actualizar comisiones después de insertar venta
CREATE TRIGGER trg_after_insert_venta
AFTER INSERT ON ventas
FOR EACH ROW
BEGIN
    DECLARE v_periodo VARCHAR(7);
    SET v_periodo = DATE_FORMAT(NEW.fecha_venta, '%Y-%m');
    
    -- Marcar periodo para recálculo
    UPDATE comisiones 
    SET estado = 'calculado'
    WHERE vendedor_id = NEW.vendedor_id 
    AND periodo = v_periodo;
END //

DELIMITER ;

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar parámetros de comisión predeterminados
INSERT INTO parametros_comision (
    nombre, descripcion, porcentaje_base, porcentaje_bono, 
    limite_bono, porcentaje_penalizacion, limite_devoluciones, 
    fecha_inicio, estado
) VALUES (
    'Plan Estándar 2025',
    'Plan de comisiones estándar para el año 2025',
    5.00, 2.00, 50000000, 1.00, 5.00,
    '2025-01-01', 'activo'
);

-- Insertar vendedores
INSERT INTO vendedores (codigo, nombre, email) VALUES
('VEN001', 'Juan Pérez', 'juan.perez@ventasplus.com'),
('VEN002', 'María Gómez', 'maria.gomez@ventasplus.com'),
('VEN003', 'Carlos Rodríguez', 'carlos.rodriguez@ventasplus.com'),
('VEN004', 'Ana Martínez', 'ana.martinez@ventasplus.com'),
('VEN005', 'Luis Fernández', 'luis.fernandez@ventasplus.com'),
('VEN006', 'Laura Torres', 'laura.torres@ventasplus.com'),
('VEN007', 'Pedro Ramírez', 'pedro.ramirez@ventasplus.com'),
('VEN008', 'Sofía López', 'sofia.lopez@ventasplus.com'),
('VEN009', 'Andrés Herrera', 'andres.herrera@ventasplus.com'),
('VEN010', 'Camila Morales', 'camila.morales@ventasplus.com');

-- Insertar productos ejemplo
INSERT INTO productos (referencia, nombre, categoria, precio_unitario) VALUES
('TB10-2024', 'Tablet Air 10', 'Tablets', 1500000),
('LP15-2024', 'Laptop Pro 15', 'Laptops', 3500000),
('SP12-2024', 'Smartphone 12', 'Smartphones', 2500000),
('MW01-2024', 'Smartwatch', 'Wearables', 800000),
('MS-2024', 'Mouse Inalámbrico', 'Accesorios', 150000),
('KB-2024', 'Teclado Bluetooth', 'Accesorios', 300000),
('HD-2024', 'Audífonos HD', 'Audio', 500000),
('CAM-HD', 'Cámara Web HD', 'Accesorios', 200000),
('MON27-2024', 'Monitor 27"', 'Monitores', 1200000),
('TKM-RED', 'Teclado Mecánico', 'Accesorios', 350000);

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================

CREATE INDEX idx_ventas_periodo ON ventas(fecha_venta, vendedor_id);
CREATE INDEX idx_comisiones_busqueda ON comisiones(periodo, estado, comision_final);

-- =====================================================
-- USUARIOS Y PERMISOS
-- =====================================================

-- Crear usuario para la aplicación
CREATE USER IF NOT EXISTS 'app_comisiones'@'localhost' IDENTIFIED BY 'C0m1s10n3s2024;

-- Otorgar permisos
GRANT SELECT, INSERT, UPDATE, DELETE ON comisiones_db.* TO 'app_comisiones'@'localhost';
GRANT EXECUTE ON comisiones_db.* TO 'app_comisiones'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================