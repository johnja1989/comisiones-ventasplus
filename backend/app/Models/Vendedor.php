<?php
/**
 * Modelo Vendedor
 * Sistema de Comisiones VentasPlus S.A.
 */

namespace App\Models;

use App\Config\Database;

class Vendedor {
    private $db;
    private $table = 'vendedores';
    
    // Propiedades
    public $id;
    public $codigo;
    public $nombre;
    public $email;
    public $telefono;
    public $fecha_ingreso;
    public $estado;
    public $created_at;
    public $updated_at;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener todos los vendedores
     */
    public function getAll($estado = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if ($estado !== null) {
            $sql .= " WHERE estado = :estado";
            $params[':estado'] = $estado;
        }
        
        $sql .= " ORDER BY nombre ASC";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener vendedor por ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->query($sql, [':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener vendedor por nombre
     */
    public function getByNombre($nombre) {
        $sql = "SELECT * FROM {$this->table} WHERE nombre = :nombre";
        $stmt = $this->db->query($sql, [':nombre' => $nombre]);
        return $stmt->fetch();
    }
    
    /**
     * Obtener vendedor por código
     */
    public function getByCodigo($codigo) {
        $sql = "SELECT * FROM {$this->table} WHERE codigo = :codigo";
        $stmt = $this->db->query($sql, [':codigo' => $codigo]);
        return $stmt->fetch();
    }
    
    /**
     * Crear nuevo vendedor
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} 
                (codigo, nombre, email, telefono, fecha_ingreso, estado) 
                VALUES 
                (:codigo, :nombre, :email, :telefono, :fecha_ingreso, :estado)";
        
        $params = [
            ':codigo' => $data['codigo'],
            ':nombre' => $data['nombre'],
            ':email' => $data['email'] ?? null,
            ':telefono' => $data['telefono'] ?? null,
            ':fecha_ingreso' => $data['fecha_ingreso'] ?? date('Y-m-d'),
            ':estado' => $data['estado'] ?? 'activo'
        ];
        
        $this->db->query($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Actualizar vendedor
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        foreach ($data as $key => $value) {
            if (in_array($key, ['nombre', 'email', 'telefono', 'estado'])) {
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->query($sql, $params);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Eliminar vendedor (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE {$this->table} SET estado = 'inactivo' WHERE id = :id";
        $stmt = $this->db->query($sql, [':id' => $id]);
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Obtener ventas del vendedor
     */
    public function getVentas($vendedorId, $periodo = null) {
        $sql = "SELECT 
                    v.*,
                    p.nombre as producto_nombre,
                    p.referencia as producto_referencia
                FROM ventas v
                INNER JOIN productos p ON v.producto_id = p.id
                WHERE v.vendedor_id = :vendedor_id";
        
        $params = [':vendedor_id' => $vendedorId];
        
        if ($periodo) {
            $sql .= " AND DATE_FORMAT(v.fecha_venta, '%Y-%m') = :periodo";
            $params[':periodo'] = $periodo;
        }
        
        $sql .= " ORDER BY v.fecha_venta DESC";
        
        $stmt = $this->db->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener comisiones del vendedor
     */
    public function getComisiones($vendedorId, $limit = null) {
        $sql = "SELECT 
                    c.*,
                    p.nombre as plan_nombre,
                    p.porcentaje_base,
                    p.porcentaje_bono,
                    p.limite_bono
                FROM comisiones c
                INNER JOIN parametros_comision p ON c.parametro_id = p.id
                WHERE c.vendedor_id = :vendedor_id
                ORDER BY c.periodo DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $this->db->query($sql, [':vendedor_id' => $vendedorId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Obtener resumen de ventas por período
     */
    public function getResumenVentas($vendedorId, $periodo) {
        $sql = "SELECT 
                    COUNT(CASE WHEN tipo_operacion = 'venta' THEN 1 END) as total_ventas,
                    COUNT(CASE WHEN tipo_operacion = 'devolucion' THEN 1 END) as total_devoluciones,
                    SUM(CASE WHEN tipo_operacion = 'venta' THEN valor_total ELSE 0 END) as monto_ventas,
                    SUM(CASE WHEN tipo_operacion = 'devolucion' THEN ABS(valor_total) ELSE 0 END) as monto_devoluciones,
                    SUM(CASE WHEN tipo_operacion = 'venta' THEN valor_total ELSE -ABS(valor_total) END) as ventas_netas
                FROM ventas
                WHERE vendedor_id = :vendedor_id
                AND DATE_FORMAT(fecha_venta, '%Y-%m') = :periodo";
        
        $stmt = $this->db->query($sql, [
            ':vendedor_id' => $vendedorId,
            ':periodo' => $periodo
        ]);
        
        return $stmt->fetch();
    }
    
    /**
     * Obtener ranking de vendedores
     */
    public function getRanking($periodo, $limit = 10) {
        $sql = "SELECT 
                    v.id,
                    v.codigo,
                    v.nombre,
                    COALESCE(c.ventas_netas, 0) as ventas_netas,
                    COALESCE(c.comision_final, 0) as comision_final,
                    COALESCE(c.aplica_bono, 0) as tiene_bono,
                    RANK() OVER (ORDER BY c.comision_final DESC) as ranking
                FROM {$this->table} v
                LEFT JOIN comisiones c ON v.id = c.vendedor_id AND c.periodo = :periodo
                WHERE v.estado = 'activo'
                ORDER BY comision_final DESC
                LIMIT :limit";
        
        $conn = $this->db->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':periodo', $periodo);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Buscar vendedores
     */
    public function search($query) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (nombre LIKE :query 
                OR codigo LIKE :query 
                OR email LIKE :query)
                AND estado = 'activo'
                ORDER BY nombre ASC";
        
        $stmt = $this->db->query($sql, [':query' => "%$query%"]);
        return $stmt->fetchAll();
    }
}
?>