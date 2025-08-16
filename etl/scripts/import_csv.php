<?php
/**
 * Script ETL de Importación de CSV
 * Sistema de Comisiones VentasPlus S.A.
 */

require_once __DIR__ . '/../../backend/vendor/autoload.php';
require_once __DIR__ . '/../../backend/app/Config/Database.php';

use App\Config\Database;

class CSVImporter {
    private $db;
    private $logId;
    private $vendedoresCache = [];
    private $productosCache = [];
    private $stats = [
        'procesados' => 0,
        'exitosos' => 0,
        'fallidos' => 0,
        'errores' => []
    ];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadCaches();
    }
    
    /**
     * Cargar caché de vendedores y productos
     */
    private function loadCaches() {
        try {
            // Cargar vendedores
            $stmt = $this->db->query("SELECT id, nombre FROM vendedores");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->vendedoresCache[strtolower(trim($row['nombre']))] = $row['id'];
            }
            
            // Cargar productos
            $stmt = $this->db->query("SELECT id, referencia FROM productos");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->productosCache[strtolower(trim($row['referencia']))] = $row['id'];
            }
            
            echo "✓ Caché cargado: " . count($this->vendedoresCache) . " vendedores, " . count($this->productosCache) . " productos\n";
            
        } catch (Exception $e) {
            die("Error cargando caché: " . $e->getMessage() . "\n");
        }
    }
    
    /**
     * Importar archivo CSV
     */
    public function importar($filepath, $tipo = 'mixto') {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "INICIANDO IMPORTACIÓN\n";
        echo str_repeat('=', 60) . "\n";
        echo "Archivo: " . basename($filepath) . "\n";
        echo "Tipo: $tipo\n";
        echo "Fecha: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat('-', 60) . "\n\n";
        
        // Validar archivo
        if (!file_exists($filepath)) {
            $this->registrarError("Archivo no encontrado: $filepath");
            return false;
        }
        
        // Registrar inicio en log
        $this->registrarInicioImportacion($filepath, $tipo);
        
        // Procesar archivo
        $resultado = $this->procesarArchivo($filepath);
        
        // Finalizar importación
        $this->finalizarImportacion();
        
        // Mostrar resumen
        $this->mostrarResumen();
        
        return $resultado;
    }
    
    /**
     * Procesar archivo CSV
     */
    private function procesarArchivo($filepath) {
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            $this->registrarError("No se pudo abrir el archivo");
            return false;
        }
        
        // Detectar delimitador
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = $this->detectarDelimitador($firstLine);
        
        // Leer encabezados
        $headers = fgetcsv($handle, 0, $delimiter);
        if (!$headers) {
            $this->registrarError("No se pudieron leer los encabezados");
            fclose($handle);
            return false;
        }
        
        // Limpiar encabezados
        $headers = array_map('trim', $headers);
        
        echo "Columnas detectadas: " . implode(', ', $headers) . "\n";
        echo "Procesando registros...\n\n";
        
        // Iniciar transacción
        $this->db->beginTransaction();
        
        try {
            $lineNumber = 1;
            
            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNumber++;
                
                // Saltar líneas vacías
                if (empty(array_filter($data))) {
                    continue;
                }
                
                $this->stats['procesados']++;
                
                // Crear array asociativo
                $row = array_combine($headers, $data);
                
                // Procesar fila
                if ($this->procesarFila($row, $lineNumber)) {
                    $this->stats['exitosos']++;
                    
                    // Mostrar progreso
                    if ($this->stats['exitosos'] % 10 == 0) {
                        echo "  ✓ Procesados: {$this->stats['exitosos']} registros\n";
                    }
                } else {
                    $this->stats['fallidos']++;
                }
            }
            
            // Confirmar transacción
            $this->db->commit();
            echo "\n✓ Importación completada exitosamente\n";
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $this->registrarError("Error crítico: " . $e->getMessage());
            echo "\n✗ Error durante la importación\n";
            return false;
        }
        
        fclose($handle);
        return true;
    }
    
    /**
     * Detectar delimitador del CSV
     */
    private function detectarDelimitador($line) {
        $delimiters = [',', ';', '\t', '|'];
        $counts = [];
        
        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($line, $delimiter);
        }
        
        return array_search(max($counts), $counts);
    }
    
    /**
     * Procesar una fila del CSV
     */
    private function procesarFila($row, $lineNumber) {
        try {
            // Obtener o crear vendedor
            $vendedorNombre = trim($row['Vendedor'] ?? '');
            if (empty($vendedorNombre)) {
                throw new Exception("Vendedor vacío");
            }
            
            $vendedorId = $this->obtenerOCrearVendedor($vendedorNombre);
            
            // Obtener o crear producto
            $productoRef = trim($row['Referencia'] ?? '');
            $productoNombre = trim($row['Producto'] ?? '');
            
            if (empty($productoRef)) {
                throw new Exception("Referencia de producto vacía");
            }
            
            $productoId = $this->obtenerOCrearProducto($productoRef, $productoNombre, $row);
            
            // Determinar tipo de operación
            $tipoOperacion = 'venta';
            $motivoDevolucion = null;
            
            if (isset($row['TipoOperacion'])) {
                $tipo = strtolower(trim($row['TipoOperacion']));
                if (strpos($tipo, 'devol') !== false) {
                    $tipoOperacion = 'devolucion';
                    $motivoDevolucion = $row['Motivo'] ?? 'Sin especificar';
                }
            }
            
            // Parsear valores numéricos
            $cantidad = abs(intval($row['Cantidad'] ?? 1));
            $valorUnitario = abs(floatval(str_replace(',', '.', $row['ValorUnitario'] ?? 0)));
            $valorVendido = floatval(str_replace(',', '.', $row['ValorVendido'] ?? 0));
            $impuesto = floatval(str_replace(',', '.', $row['Impuesto'] ?? 0));
            
            // Para devoluciones, asegurar valores negativos
            if ($tipoOperacion == 'devolucion') {
                $valorVendido = -abs($valorVendido);
                $impuesto = -abs($impuesto);
            }
            
            // Formatear fecha
            $fechaVenta = $this->formatearFecha($row['FechaVenta'] ?? date('Y-m-d'));
            
            // Insertar en base de datos
            $sql = "INSERT INTO ventas 
                    (vendedor_id, producto_id, fecha_venta, cantidad, valor_unitario, 
                     valor_total, impuesto, tipo_operacion, motivo_devolucion, archivo_origen)
                    VALUES 
                    (:vendedor_id, :producto_id, :fecha_venta, :cantidad, :valor_unitario,
                     :valor_total, :impuesto, :tipo_operacion, :motivo_devolucion, :archivo_origen)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':vendedor_id' => $vendedorId,
                ':producto_id' => $productoId,
                ':fecha_venta' => $fechaVenta,
                ':cantidad' => $cantidad,
                ':valor_unitario' => $valorUnitario,
                ':valor_total' => $valorVendido,
                ':impuesto' => $impuesto,
                ':tipo_operacion' => $tipoOperacion,
                ':motivo_devolucion' => $motivoDevolucion,
                ':archivo_origen' => basename($GLOBALS['archivo_importacion'])
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $this->registrarError("Línea $lineNumber: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener o crear vendedor
     */
    private function obtenerOCrearVendedor($nombre) {
        $nombreKey = strtolower(trim($nombre));
        
        // Buscar en caché
        if (isset($this->vendedoresCache[$nombreKey])) {
            return $this->vendedoresCache[$nombreKey];
        }
        
        // Crear nuevo vendedor
        $codigo = 'VEN' . str_pad(count($this->vendedoresCache) + 11, 3, '0', STR_PAD_LEFT);
        $email = strtolower(str_replace(' ', '.', $nombre)) . '@ventasplus.com';
        
        $sql = "INSERT INTO vendedores (codigo, nombre, email, fecha_ingreso) 
                VALUES (:codigo, :nombre, :email, CURDATE())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':codigo' => $codigo,
            ':nombre' => $nombre,
            ':email' => $email
        ]);
        
        $id = $this->db->lastInsertId();
        $this->vendedoresCache[$nombreKey] = $id;
        
        echo "  → Nuevo vendedor creado: $nombre (ID: $id)\n";
        
        return $id;
    }
    
    /**
     * Obtener o crear producto
     */
    private function obtenerOCrearProducto($referencia, $nombre, $row) {
        $refKey = strtolower(trim($referencia));
        
        // Buscar en caché
        if (isset($this->productosCache[$refKey])) {
            return $this->productosCache[$refKey];
        }
        
        // Crear nuevo producto
        $precio = abs(floatval(str_replace(',', '.', $row['ValorUnitario'] ?? 0)));
        
        $sql = "INSERT INTO productos (referencia, nombre, precio_unitario, categoria) 
                VALUES (:referencia, :nombre, :precio, 'General')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':referencia' => $referencia,
            ':nombre' => $nombre ?: 'Producto ' . $referencia,
            ':precio' => $precio
        ]);
        
        $id = $this->db->lastInsertId();
        $this->productosCache[$refKey] = $id;
        
        echo "  → Nuevo producto creado: $nombre (REF: $referencia)\n";
        
        return $id;
    }
    
    /**
     * Formatear fecha
     */
    private function formatearFecha($fecha) {
        // Intentar diferentes formatos
        $formatos = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
            'm/d/Y'
        ];
        
        foreach ($formatos as $formato) {
            $date = DateTime::createFromFormat($formato, $fecha);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        // Si no se puede parsear, usar fecha actual
        return date('Y-m-d');
    }
    
    /**
     * Registrar inicio de importación
     */
    private function registrarInicioImportacion($archivo, $tipo) {
        $GLOBALS['archivo_importacion'] = $archivo;
        
        $sql = "INSERT INTO log_importaciones (archivo, tipo, estado, usuario) 
                VALUES (:archivo, :tipo, 'procesando', :usuario)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':archivo' => basename($archivo),
            ':tipo' => $tipo,
            ':usuario' => 'CLI'
        ]);
        
        $this->logId = $this->db->lastInsertId();
    }
    
    /**
     * Finalizar importación
     */
    private function finalizarImportacion() {
        $estado = $this->stats['fallidos'] > 0 ? 'error' : 'completado';
        $errores = implode("\n", array_slice($this->stats['errores'], 0, 100));
        
        $sql = "UPDATE log_importaciones 
                SET registros_procesados = :procesados,
                    registros_exitosos = :exitosos,
                    registros_fallidos = :fallidos,
                    errores = :errores,
                    estado = :estado
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':procesados' => $this->stats['procesados'],
            ':exitosos' => $this->stats['exitosos'],
            ':fallidos' => $this->stats['fallidos'],
            ':errores' => $errores,
            ':estado' => $estado,
            ':id' => $this->logId
        ]);
    }
    
    /**
     * Registrar error
     */
    private function registrarError($mensaje) {
        $this->stats['errores'][] = $mensaje;
        echo "  ✗ Error: $mensaje\n";
    }
    
    /**
     * Mostrar resumen
     */
    private function mostrarResumen() {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "RESUMEN DE IMPORTACIÓN\n";
        echo str_repeat('=', 60) . "\n";
        echo "Registros procesados: {$this->stats['procesados']}\n";
        echo "Registros exitosos:   {$this->stats['exitosos']}\n";
        echo "Registros fallidos:   {$this->stats['fallidos']}\n";
        
        if ($this->stats['fallidos'] > 0) {
            $porcentajeExito = round(($this->stats['exitosos'] / $this->stats['procesados']) * 100, 2);
            echo "Porcentaje de éxito:  {$porcentajeExito}%\n";
        }
        
        if (count($this->stats['errores']) > 0) {
            echo "\nPrimeros errores encontrados:\n";
            foreach (array_slice($this->stats['errores'], 0, 5) as $error) {
                echo "  - $error\n";
            }
            
            if (count($this->stats['errores']) > 5) {
                $adicionales = count($this->stats['errores']) - 5;
                echo "  ... y $adicionales errores más\n";
            }
        }
        
        echo str_repeat('=', 60) . "\n\n";
    }
}

// =====================================================
// EJECUCIÓN PRINCIPAL
// =====================================================

if (php_sapi_name() === 'cli') {
    echo "\n";
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║     SISTEMA DE COMISIONES - IMPORTADOR CSV              ║\n";
    echo "║     VentasPlus S.A. © 2024                              ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n";
    
    // Verificar argumentos
    if ($argc < 2) {
        echo "\nUso: php import_csv.php <archivo.csv> [tipo]\n";
        echo "Tipos disponibles: ventas, devoluciones, mixto (default: mixto)\n\n";
        echo "Ejemplo:\n";
        echo "  php import_csv.php ventas_junio.csv\n";
        echo "  php import_csv.php devoluciones.csv devoluciones\n\n";
        exit(1);
    }
    
    $archivo = $argv[1];
    $tipo = $argv[2] ?? 'mixto';
    
    // Crear importador y ejecutar
    try {
        $importer = new CSVImporter();
        $resultado = $importer->importar($archivo, $tipo);
        
        if ($resultado) {
            echo "✓ Proceso completado exitosamente\n\n";
            
            // Preguntar si desea calcular comisiones
            echo "¿Desea calcular las comisiones ahora? (s/n): ";
            $respuesta = trim(fgets(STDIN));
            
            if (strtolower($respuesta) == 's') {
                echo "\nIngrese el período (YYYY-MM): ";
                $periodo = trim(fgets(STDIN));
                
                if (preg_match('/^\d{4}-\d{2}$/', $periodo)) {
                    echo "\nCalculando comisiones para $periodo...\n";
                    
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("CALL sp_calcular_comisiones(:periodo)");
                    $stmt->execute([':periodo' => $periodo]);
                    
                    echo "✓ Comisiones calculadas exitosamente\n";
                } else {
                    echo "Formato de período inválido\n";
                }
            }
        } else {
            echo "✗ Proceso completado con errores\n\n";
            exit(1);
        }
        
    } catch (Exception $e) {
        echo "\n✗ Error fatal: " . $e->getMessage() . "\n\n";
        exit(1);
    }
}

?>