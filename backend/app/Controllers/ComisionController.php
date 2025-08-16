<?php
/**
 * Controlador de Comisiones
 * Sistema de Comisiones VentasPlus S.A.
 */

namespace App\Controllers;

use App\Services\ComisionService;
use App\Models\Comision;
use App\Helpers\Response;
use App\Helpers\Validator;

class ComisionController {
    private $comisionService;
    private $comisionModel;
    
    public function __construct() {
        $this->comisionService = new ComisionService();
        $this->comisionModel = new Comision();
    }
    
    /**
     * GET /api/comisiones
     * Listar todas las comisiones con filtros
     */
    public function index($request, $response) {
        try {
            $filtros = [
                'periodo' => $request->getQueryParam('periodo', date('Y-m')),
                'vendedor_id' => $request->getQueryParam('vendedor_id'),
                'estado' => $request->getQueryParam('estado')
            ];
            
            $comisiones = $this->comisionModel->getAll($filtros);
            
            return Response::json($response, [
                'success' => true,
                'data' => $comisiones,
                'total' => count($comisiones),
                'filtros' => $filtros
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * GET /api/comisiones/{id}
     * Obtener detalle de una comisión
     */
    public function show($request, $response, $args) {
        try {
            $id = $args['id'];
            
            $comision = $this->comisionModel->getById($id);
            
            if (!$comision) {
                return Response::notFound($response, 'Comisión no encontrada');
            }
            
            return Response::json($response, [
                'success' => true,
                'data' => $comision
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * POST /api/comisiones/calcular
     * Calcular comisiones para un período
     */
    public function calcular($request, $response) {
        try {
            $data = $request->getParsedBody();
            $periodo = $data['periodo'] ?? date('Y-m');
            
            // Validar período
            $this->comisionService->validarPeriodo($periodo);
            
            // Calcular comisiones
            $resultado = $this->comisionService->calcularComisionesPeriodo($periodo);
            
            return Response::json($response, [
                'success' => true,
                'message' => 'Comisiones calculadas exitosamente',
                'resultado' => $resultado,
                'periodo' => $periodo
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * PUT /api/comisiones/{id}/aprobar
     * Aprobar una comisión
     */
    public function aprobar($request, $response, $args) {
        try {
            $id = $args['id'];
            
            $resultado = $this->comisionService->aprobarComisiones([$id]);
            
            if ($resultado == 0) {
                return Response::error($response, 'No se pudo aprobar la comisión');
            }
            
            return Response::json($response, [
                'success' => true,
                'message' => 'Comisión aprobada exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * PUT /api/comisiones/aprobar-lote
     * Aprobar múltiples comisiones
     */
    public function aprobarLote($request, $response) {
        try {
            $data = $request->getParsedBody();
            
            if (!isset($data['ids']) || !is_array($data['ids'])) {
                return Response::badRequest($response, 'Debe proporcionar un array de IDs');
            }
            
            $aprobadas = $this->comisionService->aprobarComisiones($data['ids']);
            
            return Response::json($response, [
                'success' => true,
                'message' => "$aprobadas comisiones aprobadas exitosamente",
                'aprobadas' => $aprobadas
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * PUT /api/comisiones/{id}/pagar
     * Marcar comisión como pagada
     */
    public function pagar($request, $response, $args) {
        try {
            $id = $args['id'];
            $data = $request->getParsedBody();
            
            $observaciones = $data['observaciones'] ?? null;
            
            $resultado = $this->comisionService->marcarComoPagadas([$id], $observaciones);
            
            if ($resultado == 0) {
                return Response::error($response, 'No se pudo marcar la comisión como pagada');
            }
            
            return Response::json($response, [
                'success' => true,
                'message' => 'Comisión marcada como pagada'
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * GET /api/comisiones/reporte
     * Obtener reporte de comisiones
     */
    public function reporte($request, $response) {
        try {
            $periodo = $request->getQueryParam('periodo', date('Y-m'));
            
            $comisiones = $this->comisionModel->getAll(['periodo' => $periodo]);
            $resumen = $this->comisionModel->getResumenPeriodo($periodo);
            
            return Response::json($response, [
                'success' => true,
                'periodo' => $periodo,
                'resumen' => $resumen,
                'detalle' => $comisiones
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * GET /api/comisiones/exportar
     * Exportar comisiones a CSV/Excel
     */
    public function exportar($request, $response) {
        try {
            $periodo = $request->getQueryParam('periodo', date('Y-m'));
            $formato = $request->getQueryParam('formato', 'csv');
            
            $datos = $this->comisionModel->exportar($periodo, $formato);
            
            if ($formato == 'csv') {
                return $this->exportarCSV($response, $datos, $periodo);
            } else {
                return $this->exportarExcel($response, $datos, $periodo);
            }
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * GET /api/comisiones/dashboard
     * Obtener datos para el dashboard
     */
    public function dashboard($request, $response) {
        try {
            $periodo = $request->getQueryParam('periodo', date('Y-m'));
            
            $estadisticas = $this->comisionService->obtenerEstadisticas($periodo);
            
            return Response::json($response, [
                'success' => true,
                'periodo' => $periodo,
                'data' => $estadisticas
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * GET /api/comisiones/top-vendedores
     * Obtener top vendedores por comisión
     */
    public function topVendedores($request, $response) {
        try {
            $periodo = $request->getQueryParam('periodo', date('Y-m'));
            $limit = $request->getQueryParam('limit', 5);
            
            $topVendedores = $this->comisionModel->getTopVendedores($periodo, $limit);
            
            return Response::json($response, [
                'success' => true,
                'periodo' => $periodo,
                'data' => $topVendedores
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * GET /api/comisiones/tendencia
     * Obtener tendencia de comisiones
     */
    public function tendencia($request, $response) {
        try {
            $meses = $request->getQueryParam('meses', 6);
            
            $tendencia = $this->comisionModel->getTendencia($meses);
            
            return Response::json($response, [
                'success' => true,
                'meses' => $meses,
                'data' => $tendencia
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * POST /api/comisiones/recalcular
     * Recalcular comisiones
     */
    public function recalcular($request, $response) {
        try {
            $data = $request->getParsedBody();
            
            $periodo = $data['periodo'] ?? null;
            $vendedorId = $data['vendedor_id'] ?? null;
            
            if (!$periodo) {
                return Response::badRequest($response, 'Debe especificar un período');
            }
            
            $resultado = $this->comisionService->recalcularComisiones($periodo, $vendedorId);
            
            return Response::json($response, [
                'success' => true,
                'message' => 'Comisiones recalculadas exitosamente',
                'resultado' => $resultado
            ]);
            
        } catch (\Exception $e) {
            return Response::error($response, $e->getMessage());
        }
    }
    
    /**
     * Exportar a CSV
     */
    private function exportarCSV($response, $datos, $periodo) {
        $filename = "comisiones_{$periodo}.csv";
        
        $response = $response->withHeader('Content-Type', 'text/csv')
                            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
        
        $body = $response->getBody();
        
        // Escribir BOM para UTF-8
        $body->write("\xEF\xBB\xBF");
        
        // Escribir encabezados
        if (!empty($datos)) {
            $headers = array_keys($datos[0]);
            $body->write(implode(',', $headers) . "\n");
            
            // Escribir datos
            foreach ($datos as $fila) {
                $valores = array_map(function($valor) {
                    return '"' . str_replace('"', '""', $valor) . '"';
                }, array_values($fila));
                $body->write(implode(',', $valores) . "\n");
            }
        }
        
        return $response;
    }
    
    /**
     * Exportar a Excel (simplificado - CSV con extensión .xls)
     */
    private function exportarExcel($response, $datos, $periodo) {
        // Por simplicidad, exportamos como CSV con extensión .xls
        $filename = "comisiones_{$periodo}.xls";
        
        $response = $response->withHeader('Content-Type', 'application/vnd.ms-excel')
                            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
        
        $body = $response->getBody();
        
        // Escribir como tabla HTML que Excel puede interpretar
        $html = '<table border="1">';
        
        // Encabezados
        if (!empty($datos)) {
            $html .= '<tr>';
            foreach (array_keys($datos[0]) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';
            
            // Datos
            foreach ($datos as $fila) {
                $html .= '<tr>';
                foreach ($fila as $valor) {
                    $html .= '<td>' . htmlspecialchars($valor) . '</td>';
                }
                $html .= '</tr>';
            }
        }
        
        $html .= '</table>';
        $body->write($html);
        
        return $response;
    }
}
?>