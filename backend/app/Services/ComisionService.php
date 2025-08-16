<?php
/**
 * Servicio de Comisiones
 * Sistema de Comisiones VentasPlus S.A.
 */

namespace App\Services;

use App\Models\Comision;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Models\ParametroComision;
use App\Config\Database;

class ComisionService {
    private $comisionModel;
    private $vendedorModel;
    private $ventaModel;
    private $parametroModel;
    private $db;
    
    public function __construct() {
        $this->comisionModel = new Comision();
        $this->vendedorModel = new Vendedor();
        $this->ventaModel = new Venta();
        $this->parametroModel = new ParametroComision();
        $this->db = Database::getInstance();
    }
    
    /**
     * Calcular comisiones para un período
     */
    public function calcularComisionesPeriodo($periodo) {
        try {
            $this->db->beginTransaction();
            
            // Obtener parámetros activos
            $parametros = $this->parametroModel->getParametrosActivos($periodo);
            if (!$parametros) {
                throw new \Exception("No hay parámetros de comisión configurados para el período");
            }
            
            // Obtener vendedores activos
            $vendedores = $this->vendedorModel->getAll('activo');
            
            $resultados = [
                'procesados' => 0,
                'exitosos' => 0,
                'errores' => [],
                'total_comisiones' => 0
            ];
            
            foreach ($vendedores as $vendedor) {
                try {
                    // Calcular comisión individual
                    $comision = $this->calcularComisionVendedor(
                        $vendedor['id'],
                        $periodo,
                        $parametros
                    );
                    
                    if ($comision) {
                        $resultados['exitosos']++;
                        $resultados['total_comisiones'] += $comision['comision_final'];
                    }
                    
                } catch (\Exception $e) {
                    $resultados['errores'][] = "Error vendedor {$vendedor['nombre']}: " . $e->getMessage();
                }
                
                $resultados['procesados']++;
            }
            
            // Actualizar métricas del dashboard
            $this->actualizarMetricasDashboard($periodo);
            
            $this->db->commit();
            
            return $resultados;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Calcular comisión individual de un vendedor
     */
    public function calcularComisionVendedor($vendedorId, $periodo, $parametros) {
        // Obtener resumen de ventas del vendedor
        $resumenVentas = $this->ventaModel->getResumenPorVendedorPeriodo($vendedorId, $periodo);
        
        // Si no hay ventas, no hay comisión
        if (!$resumenVentas || $resumenVentas['total_ventas'] == 0) {
            return null;
        }
        
        // Calcular valores base
        $totalVentas = $resumenVentas['total_ventas'];
        $totalDevoluciones = $resumenVentas['total_devoluciones'];
        $ventasNetas = $totalVentas - $totalDevoluciones;
        $porcentajeDevoluciones = ($totalVentas > 0) ? ($totalDevoluciones / $totalVentas) * 100 : 0;
        
        // Calcular comisión base
        $comisionBase = $ventasNetas * ($parametros['porcentaje_base'] / 100);
        
        // Calcular bono (si aplica)
        $bonoAdicional = 0;
        $aplicaBono = false;
        if ($ventasNetas > $parametros['limite_bono']) {
            $bonoAdicional = $ventasNetas * ($parametros['porcentaje_bono'] / 100);
            $aplicaBono = true;
        }
        
        // Calcular penalización (si aplica)
        $penalizacion = 0;
        $aplicaPenalizacion = false;
        if ($porcentajeDevoluciones > $parametros['limite_devoluciones']) {
            $penalizacion = $ventasNetas * ($parametros['porcentaje_penalizacion'] / 100);
            $aplicaPenalizacion = true;
        }
        
        // Comisión final
        $comisionFinal = $comisionBase + $bonoAdicional - $penalizacion;
        
        // Preparar datos para guardar
        $datosComision = [
            'vendedor_id' => $vendedorId,
            'parametro_id' => $parametros['id'],
            'periodo' => $periodo,
            'total_ventas' => $totalVentas,
            'total_devoluciones' => $totalDevoluciones,
            'comision_base' => $comisionBase,
            'bono_adicional' => $bonoAdicional,
            'penalizacion' => $penalizacion,
            'aplica_bono' => $aplicaBono,
            'aplica_penalizacion' => $aplicaPenalizacion,
            'estado' => 'calculado',
            'observaciones' => $this->generarObservaciones($aplicaBono, $aplicaPenalizacion)
        ];
        
        // Verificar si ya existe comisión para este período
        $comisionExistente = $this->comisionModel->getByVendedorPeriodo($vendedorId, $periodo);
        
        if ($comisionExistente) {
            // Actualizar comisión existente
            $this->actualizarComision($comisionExistente['id'], $datosComision);
        } else {
            // Crear nueva comisión
            $this->comisionModel->create($datosComision);
        }
        
        $datosComision['comision_final'] = $comisionFinal;
        return $datosComision;
    }
    
    /**
     * Actualizar comisión existente
     */
    private function actualizarComision($id, $datos) {
        $sql = "UPDATE comisiones SET 
                total_ventas = :total_ventas,
                total_devoluciones = :total_devoluciones,
                comision_base = :comision_base,
                bono_adicional = :bono_adicional,
                penalizacion = :penalizacion,
                aplica_bono = :aplica_bono,
                aplica_penalizacion = :aplica_penalizacion,
                estado = :estado,
                observaciones = :observaciones,
                fecha_calculo = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $datos['id'] = $id;
        $this->db->query($sql, $datos);
    }
    
    /**
     * Generar observaciones automáticas
     */
    private function generarObservaciones($aplicaBono, $aplicaPenalizacion) {
        $observaciones = [];
        
        if ($aplicaBono) {
            $observaciones[] = "Bono aplicado por superar límite de ventas";
        }
        
        if ($aplicaPenalizacion) {
            $observaciones[] = "Penalización aplicada por exceder límite de devoluciones";
        }
        
        if (!$aplicaBono && !$aplicaPenalizacion) {
            $observaciones[] = "Comisión estándar aplicada";
        }
        
        return implode(". ", $observaciones);
    }
    
    /**
     * Actualizar métricas del dashboard
     */
    private function actualizarMetricasDashboard($periodo) {
        $resumen = $this->comisionModel->getResumenPeriodo($periodo);
        
        $sql = "INSERT INTO dashboard_metrics 
                (periodo, total_ventas, total_comisiones, numero_vendedores,
                 vendedores_con_bono, porcentaje_con_bono, promedio_devoluciones)
                VALUES 
                (:periodo, :total_ventas, :total_comisiones, :numero_vendedores,
                 :vendedores_con_bono, :porcentaje_con_bono, :promedio_devoluciones)
                ON DUPLICATE KEY UPDATE
                total_ventas = VALUES(total_ventas),
                total_comisiones = VALUES(total_comisiones),
                numero_vendedores = VALUES(numero_vendedores),
                vendedores_con_bono = VALUES(vendedores_con_bono),
                porcentaje_con_bono = VALUES(porcentaje_con_bono),
                promedio_devoluciones = VALUES(promedio_devoluciones),
                fecha_actualizacion = CURRENT_TIMESTAMP";
        
        $porcentajeConBono = ($resumen['total_vendedores'] > 0) 
            ? ($resumen['vendedores_con_bono'] / $resumen['total_vendedores']) * 100 
            : 0;
        
        $this->db->query($sql, [
            ':periodo' => $periodo,
            ':total_ventas' => $resumen['ventas_netas'],
            ':total_comisiones' => $resumen['total_comisiones'],
            ':numero_vendedores' => $resumen['total_vendedores'],
            ':vendedores_con_bono' => $resumen['vendedores_con_bono'],
            ':porcentaje_con_bono' => $porcentajeConBono,
            ':promedio_devoluciones' => $resumen['promedio_devoluciones']
        ]);
    }
    
    /**
     * Aprobar comisiones
     */
    public function aprobarComisiones($ids) {
        $aprobadas = 0;
        
        foreach ($ids as $id) {
            if ($this->comisionModel->updateEstado($id, 'aprobado')) {
                $aprobadas++;
            }
        }
        
        return $aprobadas;
    }
    
    /**
     * Marcar comisiones como pagadas
     */
    public function marcarComoPagadas($ids, $observaciones = null) {
        $pagadas = 0;
        
        foreach ($ids as $id) {
            if ($this->comisionModel->updateEstado($id, 'pagado', $observaciones)) {
                $pagadas++;
            }
        }
        
        return $pagadas;
    }
    
    /**
     * Obtener estadísticas de comisiones
     */
    public function obtenerEstadisticas($periodo = null) {
        if (!$periodo) {
            $periodo = date('Y-m');
        }
        
        $estadisticas = [
            'resumen_periodo' => $this->comisionModel->getResumenPeriodo($periodo),
            'top_vendedores' => $this->comisionModel->getTopVendedores($periodo, 5),
            'distribucion' => $this->comisionModel->getDistribucion($periodo),
            'tendencia' => $this->comisionModel->getTendencia(6)
        ];
        
        return $estadisticas;
    }
    
    /**
     * Validar período para cálculo
     */
    public function validarPeriodo($periodo) {
        // Formato válido YYYY-MM
        if (!preg_match('/^\d{4}-\d{2}$/', $periodo)) {
            throw new \Exception("Formato de período inválido. Use YYYY-MM");
        }
        
        // No permitir períodos futuros
        if ($periodo > date('Y-m')) {
            throw new \Exception("No se pueden calcular comisiones para períodos futuros");
        }
        
        return true;
    }
    
    /**
     * Recalcular comisiones
     */
    public function recalcularComisiones($periodo, $vendedorId = null) {
        $this->validarPeriodo($periodo);
        
        if ($vendedorId) {
            // Recalcular solo para un vendedor
            $parametros = $this->parametroModel->getParametrosActivos($periodo);
            return $this->calcularComisionVendedor($vendedorId, $periodo, $parametros);
        } else {
            // Recalcular todo el período
            return $this->calcularComisionesPeriodo($periodo);
        }
    }
}
?>
                