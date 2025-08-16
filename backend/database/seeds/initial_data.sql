-- =====================================================
-- Seed Data: initial_data.sql
-- Sistema de Comisiones VentasPlus S.A.
-- =====================================================

USE comisiones_db;

-- =====================================================
-- Insertar parámetros de comisión
-- =====================================================
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

-- =====================================================
-- Insertar vendedores
-- =====================================================
INSERT INTO vendedores (codigo, nombre, email, telefono, fecha_ingreso, estado) VALUES
('VEN001', 'Juan Pérez', 'juan.perez@ventasplus.com', '3001234567', '2023-01-15', 'activo'),
('VEN002', 'María Gómez', 'maria.gomez@ventasplus.com', '3002345678', '2023-02-01', 'activo'),
('VEN003', 'Carlos Rodríguez', 'carlos.rodriguez@ventasplus.com', '3003456789', '2023-03-10', 'activo'),
('VEN004', 'Ana Martínez', 'ana.martinez@ventasplus.com', '3004567890', '2023-04-05', 'activo'),
('VEN005', 'Luis Fernández', 'luis.fernandez@ventasplus.com', '3005678901', '2023-05-20', 'activo'),
('VEN006', 'Laura Torres', 'laura.torres@ventasplus.com', '3006789012', '2023-06-15', 'activo'),
('VEN007', 'Pedro Ramírez', 'pedro.ramirez@ventasplus.com', '3007890123', '2023-07-01', 'activo'),
('VEN008', 'Sofía López', 'sofia.lopez@ventasplus.com', '3008901234', '2023-08-10', 'activo'),
('VEN009', 'Andrés Herrera', 'andres.herrera@ventasplus.com', '3009012345', '2023-09-05', 'activo'),
('VEN010', 'Camila Morales', 'camila.morales@ventasplus.com', '3000123456', '2023-10-20', 'activo');

-- =====================================================
-- Insertar productos
-- =====================================================
INSERT INTO productos (referencia, nombre, categoria, precio_unitario, estado) VALUES
('TB10-2024', 'Tablet Air 10', 'Tablets', 1500000, 'activo'),
('LP15-2024', 'Laptop Pro 15', 'Laptops', 3500000, 'activo'),
('SP12-2024', 'Smartphone 12', 'Smartphones', 2500000, 'activo'),
('MW01-2024', 'Smartwatch', 'Wearables', 800000, 'activo'),
('MS-2024', 'Mouse Inalámbrico', 'Accesorios', 150000, 'activo'),
('KB-2024', 'Teclado Bluetooth', 'Accesorios', 300000, 'activo'),
('HD-2024', 'Audífonos HD', 'Audio', 500000, 'activo'),
('CAM-HD', 'Cámara Web HD', 'Accesorios', 200000, 'activo'),
('MON27-2024', 'Monitor 27"', 'Monitores', 1200000, 'activo'),
('TKM-RED', 'Teclado Mecánico', 'Accesorios', 350000, 'activo'),
('SSD1TB-2024', 'SSD 1TB', 'Almacenamiento', 450000, 'activo'),
('RAM16-2024', 'Memoria RAM 16GB', 'Componentes', 350000, 'activo'),
('ROUTER-AC', 'Router WiFi AC', 'Networking', 280000, 'activo'),
('PRINTER-MF', 'Impresora Multifunción', 'Impresoras', 650000, 'activo'),
('UPS-1000', 'UPS 1000VA', 'Energía', 420000, 'activo');

-- =====================================================
-- Insertar ventas de ejemplo (Junio 2025)
-- =====================================================
-- Ventas para Laura Torres (superará los 50M para obtener bono)
INSERT INTO ventas (vendedor_id, producto_id, fecha_venta, cantidad, valor_unitario, valor_total, impuesto, tipo_operacion) VALUES
(6, 2, '2025-06-05', 10, 3500000, 35000000, 6650000, 'venta'),
(6, 1, '2025-06-10', 15, 1500000, 22500000, 4275000, 'venta'),
(6, 3, '2025-06-15', 8, 2500000, 20000000, 3800000, 'venta'),
(6, 9, '2025-06-20', 12, 1200000, 14400000, 2736000, 'venta'),
(6, 4, '2025-06-25', 15, 800000, 12000000, 2280000, 'venta');

-- Ventas para Sofía López
INSERT INTO ventas (vendedor_id, producto_id, fecha_venta, cantidad, valor_unitario, valor_total, impuesto, tipo_operacion) VALUES
(8, 1, '2025-06-03', 12, 1500000, 18000000, 3420000, 'venta'),
(8, 2, '2025-06-08', 5, 3500000, 17500000, 3325000, 'venta'),
(8, 3, '2025-06-12', 6, 2500000, 15000000, 2850000, 'venta'),
(8, 9, '2025-06-18', 10, 1200000, 12000000, 2280000, 'venta'),
(8, 7, '2025-06-22', 20, 500000, 10000000, 1900000, 'venta');

-- Ventas para Carlos Rodríguez
INSERT INTO ventas (vendedor_id, producto_id, fecha_venta, cantidad, valor_unitario, valor_total, impuesto, tipo_operacion) VALUES
(3, 2, '2025-06-02', 4, 3500000, 14000000, 2660000, 'venta'),
(3, 1, '2025-06-07', 8, 1500000, 12000000, 2280000, 'venta'),
(3, 3, '2025-06-14', 5, 2500000, 12500000, 2375000, 'venta'),
(3, 4, '2025-06-19', 12, 800000, 9600000, 1824000, 'venta'),
(3, 9, '2025-06-24', 7, 1200000, 8400000, 1596000, 'venta');

-- Ventas para Camila Morales
INSERT INTO ventas (vendedor_id, producto_id, fecha_venta, cantidad, valor_unitario, valor_total, impuesto, tipo_operacion) VALUES
(10, 1, '2025-06-04', 10, 1500000, 15000000, 2850000, 'venta'),
(10, 3, '2025-06-09', 5, 2500000, 12500000, 2375000, 'venta'),
(10, 2, '2025-06-13', 3, 3500000, 10500000, 1995000, 'venta'),
(10, 9, '2025-06-17', 8, 1200000, 9600000, 1824000, 'venta'),
(10, 4, '2025-06-23', 10, 800000, 8000000, 1520000, 'venta');

-- Ventas para Juan Pérez
INSERT INTO ventas (vendedor_id, producto_id, fecha_venta, cantidad, valor_unitario, valor_total, impuesto, tipo_operacion) VALUES
(1, 3, '2025-06-06', 4, 2500000, 10000000, 1900000, 'venta'),
(1, 1, '2025-06-11', 6, 1500000, 9000000, 1710000, 'venta'),
(1, 2, '2025-06-16', 2, 3500000, 7000000, 1330000, 'venta'),
(1, 9, '2025-06-21', 5, 1200000, 6000000, 1140000, 'venta'),
(1, 7, '2025-06-26', 10, 500000, 5000000, 950000, 'venta');

-- =====================================================
-- Insertar ventas de ejemplo (Julio 2025)
-- =====================================================
-- Ventas para Laura Torres
INSERT INTO ventas (vendedor_id, producto_id, fecha_venta, cantidad, valor_unitario, valor_total, impuesto, tipo_operacion) VALUES
(6, 2, '2025-07-03', 12, 3500000, 42000000, 7980000, 'venta'),
(6, 1, '2025-07-08', 18, 1500000, 27000000, 5130000, 'venta'),
(6, 3, '2025-07-15', 10, 2500000, 25000000, 4750000, 'venta'),
(6, 9, '2025-07-20', 15, 1200000, 18000000, 3420000, 'venta');

-- Ventas para Ana Martínez (con devolución para aplicar penalización)
INSERT INTO ventas (vendedor_id, producto_id, fecha_venta, cantidad, valor_unitario, valor_total, impuesto, tipo_operacion) VALUES
(4, 1, '2025-07-02', 8, 1500000, 12000000, 2280000, 'venta'),
(4, 2, '2025-07-07', 3, 3500000, 10500000, 1995000, 'venta'),
(4, 3, '2025-07-12', 4, 2500000, 10000000, 1900000, 'venta'),
(4, 8, '2025-07-18', 10, 200000, 2000000, 380000, 'venta');

-- Devolución para Ana Martínez (para generar penalización)
INSERT INTO ventas (vendedor_id, producto_id, fecha_venta, cantidad, valor_unitario, valor_total, impuesto, tipo_operacion, motivo_devolucion) VALUES
(4, 8, '2025-07-25', 2, 200000, -400000, -76000, 'devolucion', 'Producto defectuoso'),
(4, 1, '2025-07-26', 1, 1500000, -1500000, -285000, 'devolucion', 'Cliente insatisfecho');

-- Más ventas para otros vendedores en Julio
INSERT INTO ventas (vendedor_id, producto_id, fecha_venta, cantidad, valor_unitario, valor_total, impuesto, tipo_operacion) VALUES
(8, 1, '2025-07-05', 14, 1500000, 21000000, 3990000, 'venta'),
(8, 2, '2025-07-10', 6, 3500000, 21000000, 3990000, 'venta'),
(8, 3, '2025-07-16', 8, 2500000, 20000000, 3800000, 'venta'),
(3, 2, '2025-07-04', 5, 3500000, 17500000, 3325000, 'venta'),
(3, 1, '2025-07-09', 10, 1500000, 15000000, 2850000, 'venta'),
(3, 3, '2025-07-14', 6, 2500000, 15000000, 2850000, 'venta'),
(10, 1, '2025-07-06', 12, 1500000, 18000000, 3420000, 'venta'),
(10, 3, '2025-07-11', 7, 2500000, 17500000, 3325000, 'venta'),
(10, 2, '2025-07-17', 4, 3500000, 14000000, 2660000, 'venta'),
(1, 3, '2025-07-07', 5, 2500000, 12500000, 2375000, 'venta'),
(1, 1, '2025-07-13', 8, 1500000, 12000000, 2280000, 'venta'),
(1, 2, '2025-07-19', 3, 3500000, 10500000, 1995000, 'venta'),
(2, 1, '2025-07-08', 7, 1500000, 10500000, 1995000, 'venta'),
(2, 3, '2025-07-15', 4, 2500000, 10000000, 1900000, 'venta'),
(2, 4, '2025-07-22', 10, 800000, 8000000, 1520000, 'venta'),
(5, 2, '2025-07-09', 2, 3500000, 7000000, 1330000, 'venta'),
(5, 1, '2025-07-16', 5, 1500000, 7500000, 1425000, 'venta'),
(5, 3, '2025-07-23', 3, 2500000, 7500000, 1425000, 'venta'),
(7, 1, '2025-07-10', 6, 1500000, 9000000, 1710000, 'venta'),
(7, 3, '2025-07-17', 3, 2500000, 7500000, 1425000, 'venta'),
(7, 4, '2025-07-24', 8, 800000, 6400000, 1216000, 'venta'),
(9, 2, '2025-07-11', 2, 3500000, 7000000, 1330000, 'venta'),
(9, 1, '2025-07-18', 4, 1500000, 6000000, 1140000, 'venta'),
(9, 3, '2025-07-25', 2, 2500000, 5000000, 950000, 'venta');

-- =====================================================
-- Calcular comisiones iniciales (llamar procedimientos)
-- =====================================================
CALL sp_calcular_comisiones('2025-06');
CALL sp_calcular_comisiones('2025-07');

-- =====================================================
-- Verificación de datos insertados
-- =====================================================
SELECT 'Resumen de datos insertados:' as '';
SELECT CONCAT('Vendedores: ', COUNT(*)) as Total FROM vendedores;
SELECT CONCAT('Productos: ', COUNT(*)) as Total FROM productos;
SELECT CONCAT('Ventas Junio: ', COUNT(*)) as Total FROM ventas WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = '2025-06';
SELECT CONCAT('Ventas Julio: ', COUNT(*)) as Total FROM ventas WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = '2025-07';
SELECT CONCAT('Comisiones calculadas: ', COUNT(*)) as Total FROM comisiones;