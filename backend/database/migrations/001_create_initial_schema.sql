-- =====================================================
-- Migration: 001_create_initial_schema.sql
-- Sistema de Comisiones VentasPlus S.A.
-- Fecha: Diciembre 2024
-- =====================================================

-- Usar base de datos
USE comisiones_db;

-- =====================================================
-- TABLA: vendedores
-- =====================================================
CREATE TABLE IF NOT EXISTS vendedores (
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
CREATE TABLE IF NOT EXISTS productos (
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
CREATE TABLE IF NOT EXISTS ventas (
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
CREATE TABLE IF NOT EXISTS parametros_comision (
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
CREATE TABLE IF NOT EXISTS comisiones (
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
CREATE TABLE IF NOT EXISTS log_importaciones (
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
CREATE TABLE IF NOT EXISTS dashboard_metrics (
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