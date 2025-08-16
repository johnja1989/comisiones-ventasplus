<?php
/**
 * Modelo Comision
 * Sistema de Comisiones VentasPlus S.A.
 */

namespace App\Models;

use App\Config\Database;

class Comision {
    private $db;
    private $table = 'comisiones';
    
    // Propiedades
    public $id;
    public $vendedor_id;
    public $parametro_id;
    public $periodo;
    public $total_ventas;
    public $total_devoluciones;
    public $ventas_netas;
    public $porcentaje_devoluciones;
    public $comision_base;
    public $bono_adicional;
    public $penalizacion;
    public $comision_final;
    public $aplica_bono;
    public $aplica_penalizacion;
    public $estado;
    public $fecha_calculo;
    public $fecha_aprobacion;
    public $fecha_pago;
    public $observaciones;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todas las comisiones con filtros
     */
    public function getAll($filtros = []) {
        $sql = "SELECT 
                    c.*,
                    v.codigo as vendedor_codigo,
                    v.nombre as vendedor_nombre,
                    v.email as vendedor_email,
                    p.nombre as plan_nombre
                FROM {$this->table} c
                INNER JOIN vendedores v ON c.vendedor_id = v.id
                INNER JOIN parametros_comision p ON c.parametro_id = p.id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filtros['periodo'])) {
            $sql .= " AND c.periodo = :periodo";
            $params[':periodo'] = $filtros['periodo'];
        }
        
        if (isset($filtros['vendedor_id'])) {
            $sql .= " AND c.vendedor_id = :vendedor_id";
            $params[':vendedor_id'] = $filtros['vendedor_id'];
        }
        
        if (isset($filtros['estado'])) {
            $sql .= " AND c.estado = :estado";
            $params[':estado'] = $filtros['estado'];
        }
        
        $sql .= " ORDER BY c.periodo DESC, c.comision_final DESC";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener comisión por ID
     */
    public function getById($id) {
        $sql = "SELECT 
                    c.*,
                    v.codigo as vendedor_codigo,
                    v.nombre as vendedor_nombre,
                    v.email as vendedor_email,
                    p.nombre as plan_nombre,
                    p.porcentaje_base,
                    p.porcentaje_bono,
                    p.limite_bono,
                    p.porcentaje_penalizacion,
                    p.limite_devoluciones
                FROM {$this->table} c
                INNER JOIN vendedores v ON c.vendedor_id = v.id
                INNER JOIN parametros_comision p ON c.parametro_id = p.id
                WHERE c.id = :id";
        
        $stmt = $this->db->query($sql, [':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener comisión por vendedor y período
     */
    public function getByVendedorPeriodo($vendedorId, $periodo) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE vendedor_id = :vendedor_id 
                AND periodo = :periodo";
        
        $stmt = $this->db->query($sql, [
            ':vendedor_id' => $vendedorId,
            ':periodo' => $periodo
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * Calcular comisiones para un período
     */
    public function calcularPeriodo($periodo) {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("CALL sp_calcular_comisiones(:periodo)");
            $stmt->execute([':periodo' => $periodo]);
            
            // Obtener resumen del cálculo
            $sql = "SELECT 
                        COUNT(*) as total_calculadas,
                        SUM(comision_final) as total_comisiones,
                        SUM(CASE WHEN aplica_bono THEN 1 ELSE 0 END) as con_bono,
                        SUM(CASE WHEN aplica_penalizacion THEN 1 ELSE 0 END) as con_penalizacion
                    FROM {$this->table}
                    WHERE periodo = :periodo";
            
            $stmt = $this->db->query($sql, [':periodo' => $periodo]);
            return $stmt->fetch();
            
        } catch (\Exception $e) {
            throw new \Exception("Error al calcular comisiones: " . $e->getMessage());
        }
    }
    
    /**
     * Crear nueva comisión
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (vendedor_id, parametro_id, periodo, total_ventas, total_devoluciones,
                 comision_base, bono_adicional, penalizacion, aplica_bono, 
                 aplica_penalizacion, estado, observaciones)
                VALUES 
                (:vendedor_id, :parametro_id, :periodo, :total_ventas, :total_devoluciones,
                 :comision_base, :bono_adicional, :penalizacion, :aplica_bono,
                 :aplica_penalizacion, :estado, :observaciones)";
        
        $params = [
            ':vendedor_id' => $data['vendedor_id'],
            ':parametro_id' => $data['parametro_id'],
            ':periodo' => $data['periodo'],
            ':total_ventas' => $data['total_ventas'],
            ':total_devoluciones' => $data['total_devoluciones'],
            ':comision_base' => $data['comision_base'],
            ':bono_adicional' => $data['bono_adicional'] ?? 0,
            ':penalizacion' => $data['penalizacion'] ?? 0,
            ':aplica_bono' => $data['aplica_bono'] ?? false,
            ':aplica_penalizacion' => $data['aplica_penalizacion'] ?? false,
            ':estado' => $data['estado'] ?? 'calculado',
            ':observaciones' => $data['observaciones'] ?? null
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar estado de comisión
     */
    public function updateEstado($id, $estado, $observaciones = null) {
        $sql = "UPDATE {$this->table} 
                SET estado = :estado,
                    observaciones = :observaciones";
        
        $params = [
            ':id' => $id,
            ':estado' => $estado,
            ':observaciones' => $observaciones
        ];
        
        // Agregar fecha según el estado
        if ($estado == 'aprobado') {
            $sql .= ", fecha_aprobacion = CURRENT_TIMESTAMP";
        } elseif ($estado == 'pagado') {
            $sql .= ", fecha_pago = CURRENT_TIMESTAMP";
        }
        
        $sql .= " WHERE id = :id";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Aprobar comisiones en lote
     */
    public function aprobarLote($periodo) {
        $sql = "UPDATE {$this->table} 
                SET estado = 'aprobado',
                    fecha_aprobacion = CURRENT_TIMESTAMP
                WHERE periodo = :periodo 
                AND estado = 'calculado'";
        
        $stmt = $this->db->query($sql, [':periodo' => $periodo]);
        return $stmt->rowCount();
    }
    
    /**
     * Obtener resumen por período
     */
    public function getResumenPeriodo($periodo) {
        $sql = "SELECT 
                    COUNT(DISTINCT vendedor_id) as total_vendedores,
                    SUM(total_ventas) as total_ventas,
                    SUM(total_devoluciones) as total_devoluciones,
                    SUM(ventas_netas) as ventas_netas,
                    AVG(porcentaje_devoluciones) as promedio_devoluciones,
                    SUM(comision_base) as total_comision_base,
                    SUM(bono_adicional) as total_bonos,
                    SUM(penalizacion) as total_penalizaciones,
                    SUM(comision_final) as total_comisiones,
                    SUM(CASE WHEN aplica_bono THEN 1 ELSE 0 END) as vendedores_con_bono,
                    SUM(CASE WHEN aplica_penalizacion THEN 1 ELSE 0 END) as vendedores_con_penalizacion,
                    SUM(CASE WHEN estado = 'calculado' THEN 1 ELSE 0 END) as pendientes_aprobacion,
                    SUM(CASE WHEN estado = 'aprobado' THEN 1 ELSE 0 END) as aprobadas,
                    SUM(CASE WHEN estado = 'pagado' THEN 1 ELSE 0 END) as pagadas
                FROM {$this->table}
                WHERE periodo = :periodo";
        
        $stmt = $this->db->query($sql, [':periodo' => $periodo]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener top vendedores por comisión
     */
    public function getTopVendedores($periodo, $limit = 5) {
        $sql = "SELECT 
                    v.id,
                    v.codigo,
                    v.nombre,
                    c.ventas_netas,
                    c.comision_final,
                    c.aplica_bono,
                    c.aplica_penalizacion,
                    RANK() OVER (ORDER BY c.comision_final DESC) as ranking
                FROM {$this->table} c
                INNER JOIN vendedores v ON c.vendedor_id = v.id
                WHERE c.periodo = :periodo
                ORDER BY c.comision_final DESC
                LIMIT :limit";
        
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':periodo', $periodo);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener distribución de comisiones
     */
    public function getDistribucion($periodo) {
        $sql = "SELECT 
                    CASE 
                        WHEN comision_final < 1000000 THEN '< $1M'
                        WHEN comision_final < 2000000 THEN '$1M - $2M'
                        WHEN comision_final < 3000000 THEN '$2M - $3M'
                        WHEN comision_final < 5000000 THEN '$3M - $5M'
                        ELSE '> $5M'
                    END as rango,
                    COUNT(*) as cantidad,
                    SUM(comision_final) as total
                FROM {$this->table}
                WHERE periodo = :periodo
                GROUP BY rango
                ORDER BY MIN(comision_final)";
        
        $stmt = $this->db->query($sql, [':periodo' => $periodo]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener tendencia de comisiones
     */
    public function getTendencia($meses = 6) {
        $sql = "SELECT 
                    periodo,
                    COUNT(DISTINCT vendedor_id) as vendedores,
                    SUM(total_ventas) as total_ventas,
                    SUM(ventas_netas) as ventas_netas,
                    SUM(comision_final) as total_comisiones,
                    AVG(porcentaje_devoluciones) as promedio_devoluciones
                FROM {$this->table}
                WHERE periodo >= DATE_FORMAT(DATE_SUB(CURRENT_DATE, INTERVAL :meses MONTH), '%Y-%m')
                GROUP BY periodo
                ORDER BY periodo";
        
        $stmt = $this->db->query($sql, [':meses' => $meses]);
        return $stmt->fetchAll();
    }
    
    /**
     * Exportar comisiones
     */
    public function exportar($periodo, $formato = 'csv') {
        $sql = "SELECT 
                    v.codigo as 'Código Vendedor',
                    v.nombre as 'Nombre',
                    c.total_ventas as 'Total Ventas',
                    c.total_devoluciones as 'Total Devoluciones',
                    c.ventas_netas as 'Ventas Netas',
                    c.porcentaje_devoluciones as '% Devoluciones',
                    c.comision_base as 'Comisión Base',
                    c.bono_adicional as 'Bono',
                    c.penalizacion as 'Penalización',
                    c.comision_final as 'Comisión Final',
                    CASE WHEN c.aplica_bono THEN 'Sí' ELSE 'No' END as 'Tiene Bono',
                    CASE WHEN c.aplica_penalizacion THEN 'Sí' ELSE 'No' END as 'Tiene Penalización',
                    c.estado as 'Estado',
                    c.fecha_calculo as 'Fecha Cálculo'
                FROM {$this->table} c
                INNER JOIN vendedores v ON c.vendedor_id = v.id
                WHERE c.periodo = :periodo
                ORDER BY c.comision_final DESC";
        
        $stmt = $this->db->query($sql, [':periodo' => $periodo]);
        return $stmt->fetchAll();
    }
}
?>