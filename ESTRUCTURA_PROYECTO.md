# 📁 Estructura Completa del Proyecto - Sistema de Comisiones VentasPlus

## Árbol de Directorios y Archivos

omisiones-ventasplus/
│
├── 📄 README.md                          # Documentación principal del proyecto
├── 📄 INSTRUCCIONES_ENTREGA.md          # Guía de entrega y checklist
├── 📄 ESTRUCTURA_PROYECTO.md            # Este archivo
├── 🔧 setup.sh                          # Script de instalación automática
├── 🔧 start-backend.sh                  # Script para iniciar backend
├── 🔧 start-frontend.sh                 # Script para iniciar frontend
├── 🔧 start-all.sh                      # Script para iniciar todo el sistema
│
├── 📂 backend/                          # BACKEND PHP
│   ├── 📂 app/                          # Código de la aplicación
│   │   ├── 📂 Controllers/              # Controladores MVC
│   │   │   ├── ComisionController.php   # Controlador principal de comisiones
│   │   │   ├── VendedorController.php   # Controlador de vendedores
│   │   │   ├── VentaController.php      # Controlador de ventas
│   │   │   └── DashboardController.php  # Controlador del dashboard
│   │   │
│   │   ├── 📂 Models/                   # Modelos de datos
│   │   │   ├── Comision.php            # Modelo de comisiones
│   │   │   ├── Vendedor.php            # Modelo de vendedores
│   │   │   ├── Venta.php               # Modelo de ventas
│   │   │   ├── Producto.php            # Modelo de productos
│   │   │   └── ParametroComision.php   # Modelo de parámetros
│   │   │
│   │   ├── 📂 Services/                 # Servicios de negocio
│   │   │   ├── ComisionService.php     # Lógica de cálculo de comisiones
│   │   │   ├── ImportService.php       # Servicio de importación
│   │   │   ├── ReporteService.php      # Generación de reportes
│   │   │   └── DashboardService.php    # Servicio del dashboard
│   │   │
│   │   ├── 📂 Config/                   # Configuración
│   │   │   ├── database.php            # Configuración de base de datos
│   │   │   ├── app.php                 # Configuración de aplicación
│   │   │   └── routes.php              # Definición de rutas
│   │   │
│   │   ├── 📂 Helpers/                  # Helpers y utilidades
│   │   │   ├── Response.php            # Helper para respuestas HTTP
│   │   │   ├── Validator.php           # Validador de datos
│   │   │   └── Utils.php               # Utilidades generales
│   │   │
│   │   └── 📂 Middleware/               # Middleware
│   │       ├── AuthMiddleware.php      # Autenticación
│   │       └── CorsMiddleware.php      # CORS
│   │
│   ├── 📂 database/                     # Base de datos
│   │   ├── 📂 migrations/               # Migraciones SQL
│   │   │   ├── 001_create_initial_schema.sql
│   │   │   └── 002_create_procedures.sql
│   │   │
│   │   └── 📂 seeds/                    # Datos iniciales
│   │       └── initial_data.sql
│   │
│   ├── 📂 public/                       # Directorio público
│   │   ├── index.php                    # Punto de entrada de la aplicación
│   │   └── .htaccess                    # Configuración Apache
│   │
│   ├── 📂 storage/                      # Almacenamiento
│   │   ├── 📂 logs/                     # Logs de aplicación
│   │   ├── 📂 cache/                    # Caché
│   │   └── 📂 uploads/                  # Archivos subidos
│   │
│   ├── 📂 tests/                        # Tests unitarios
│   │   ├── Unit/
│   │   └── Feature/
│   │
│   ├── 📄 composer.json                 # Dependencias PHP
│   ├── 📄 composer.lock                 # Lock de dependencias
│   ├── 📄 .env.example                  # Ejemplo de configuración
│   └── 📄 .env                          # Configuración local (no versionado)
│
├── 📂 frontend/                         # FRONTEND
│   ├── 📂 src/                          # Código fuente
│   │   ├── 📂 app/                      # Aplicación Angular
│   │   │   ├── 📂 components/           # Componentes
│   │   │   ├── 📂 services/             # Servicios
│   │   │   └── 📂 models/               # Modelos TypeScript
│   │   │
│   │   ├── 📂 assets/                   # Assets estáticos
│   │   │   ├── 📂 css/                  # Estilos
│   │   │   ├── 📂 js/                   # JavaScript
│   │   │   └── 📂 images/               # Imágenes
│   │   │
│   │   └── 📂 environments/             # Configuración de entornos
│   │       ├── environment.ts
│   │       └── environment.prod.ts
│   │
│   ├── 📂 dist/                         # Build de producción
│   │
│   ├── 📄 dashboard.html                # Dashboard standalone
│   ├── 📄 package.json                  # Dependencias Node.js
│   ├── 📄 package-lock.json            # Lock de dependencias
│   ├── 📄 angular.json                  # Configuración Angular
│   └── 📄 tsconfig.json                 # Configuración TypeScript
│
├── 📂 etl/                              # ETL y procesamiento
│   ├── 📂 scripts/                      # Scripts de procesamiento
│   │   ├── import_csv.php              # Importador principal de CSV
│   │   ├── calculate_commissions.php   # Calculador de comisiones
│   │   └── export_reports.php          # Exportador de reportes
│   │
│   └── 📂 data/                         # Datos de ejemplo
│       ├── ventas_junio_julio.csv      # CSV de ventas
│       └── ventas_con_devoluciones.csv # CSV con devoluciones
│
├── 📂 docker/                           # Configuración Docker
│   ├── 📄 docker-compose.yml           # Orquestación de servicios
│   ├── 📄 Dockerfile                    # Imagen de la aplicación
│   ├── 📂 nginx/                        # Configuración Nginx
│   │   └── default.conf
│   └── 📂 php/                          # Configuración PHP
│       └── local.ini
│
├── 📂 docs/                             # Documentación adicional
│   ├── 📄 arquitectura.md              # Arquitectura del sistema
│   ├── 📄 manual_usuario.md            # Manual de usuario
│   ├── 📄 api.md                       # Documentación API
│   ├── 📄 desarrollo.md                # Guía de desarrollo
│   └── 📄 deployment.md                # Guía de despliegue
│
└── 📂 logs/                             # Logs del sistema
├── backend.log                     # Log del backend
├── frontend.log                    # Log del frontend
└── import.log                      # Log de importaciones

## 📋 Descripción de Archivos Clave

### Backend (PHP)

| Archivo | Descripción |
|---------|-------------|
| `backend/app/Controllers/ComisionController.php` | Controlador principal que maneja todas las operaciones de comisiones |
| `backend/app/Models/Comision.php` | Modelo de datos para comisiones con métodos CRUD |
| `backend/app/Services/ComisionService.php` | Lógica de negocio para cálculo de comisiones |
| `backend/app/Config/database.php` | Configuración y conexión a base de datos |
| `backend/public/index.php` | Punto de entrada de la API REST |
| `backend/database/migrations/001_create_initial_schema.sql` | Esquema completo de base de datos |
| `backend/database/migrations/002_create_procedures.sql` | Procedimientos almacenados |

### Frontend

| Archivo | Descripción |
|---------|-------------|
| `frontend/dashboard.html` | Dashboard completo con visualizaciones |
| `frontend/package.json` | Dependencias y scripts de Node.js |
| `frontend/src/app/` | Código fuente de la aplicación Angular |

### ETL

| Archivo | Descripción |
|---------|-------------|
| `etl/scripts/import_csv.php` | Script principal de importación de CSV |
| `etl/data/*.csv` | Archivos CSV de ejemplo |

### Configuración

| Archivo | Descripción |
|---------|-------------|
| `.env.example` | Plantilla de variables de entorno |
| `docker-compose.yml` | Configuración de Docker |
| `setup.sh` | Script de instalación automática |

## 🔌 Endpoints API Disponibles

### Comisiones
- `GET /api/comisiones` - Listar todas las comisiones
- `GET /api/comisiones/{id}` - Detalle de comisión
- `POST /api/comisiones/calcular` - Calcular comisiones
- `PUT /api/comisiones/{id}/aprobar` - Aprobar comisión
- `PUT /api/comisiones/{id}/pagar` - Marcar como pagada
- `GET /api/comisiones/reporte` - Reporte consolidado
- `GET /api/comisiones/exportar` - Exportar a CSV/Excel
- `GET /api/comisiones/dashboard` - Datos del dashboard
- `GET /api/comisiones/top-vendedores` - Top 5 vendedores
- `GET /api/comisiones/tendencia` - Tendencia histórica

### Vendedores
- `GET /api/vendedores` - Listar vendedores
- `GET /api/vendedores/{id}` - Detalle de vendedor
- `POST /api/vendedores` - Crear vendedor
- `PUT /api/vendedores/{id}` - Actualizar vendedor
- `DELETE /api/vendedores/{id}` - Eliminar vendedor
- `GET /api/vendedores/{id}/ventas` - Ventas del vendedor
- `GET /api/vendedores/{id}/comisiones` - Comisiones del vendedor
- `GET /api/vendedores/ranking` - Ranking de vendedores

### Ventas
- `GET /api/ventas` - Listar ventas
- `GET /api/ventas/{id}` - Detalle de venta
- `POST /api/ventas` - Registrar venta
- `POST /api/ventas/importar` - Importar CSV
- `GET /api/ventas/resumen` - Resumen de ventas

### Dashboard
- `GET /api/dashboard/kpis` - KPIs principales
- `GET /api/dashboard/metricas` - Métricas generales
- `GET /api/dashboard/resumen-periodo` - Resumen por período
- `GET /api/dashboard/comparativa` - Comparativa de períodos

## 🗄️ Estructura de Base de Datos

### Tablas Principales
1. **vendedores** - Información de vendedores
2. **productos** - Catálogo de productos
3. **ventas** - Registro de ventas y devoluciones
4. **parametros_comision** - Configuración de comisiones
5. **comisiones** - Comisiones calculadas
6. **dashboard_metrics** - Métricas precalculadas
7. **log_importaciones** - Registro de importaciones

### Vistas
- `v_resumen_ventas_mensual` - Resumen mensual de ventas
- `v_top_vendedores_comision` - Ranking de vendedores
- `v_reporte_comisiones` - Vista para reportes

### Procedimientos Almacenados
- `sp_calcular_comisiones` - Cálculo automático de comisiones
- `sp_actualizar_dashboard_metrics` - Actualización de métricas
- `sp_get_top_vendedores` - Obtener top vendedores

## 🚀 Comandos de Inicio Rápido

```bash
# Instalación completa
./setup.sh

# Iniciar backend
cd backend && php -S localhost:8000 -t public

# Iniciar frontend
cd frontend && npm start

# Importar CSV
php etl/scripts/import_csv.php archivo.csv

# Calcular comisiones
mysql -u root -p comisiones_db -e "CALL sp_calcular_comisiones('2025-07');"

# Iniciar con Docker
docker-compose up -d

# Ver logs
tail -f logs/backend.log

📝 Variables de Entorno Requeridas

# Base de Datos
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=comisiones_db
DB_USERNAME=root
DB_PASSWORD=

# Aplicación
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Parámetros de Comisiones
COMISION_BASE=5
BONO_ADICIONAL=2
LIMITE_BONO=50000000
PENALIZACION=1
LIMITE_DEVOLUCIONES=5

🔗 URLs de Acceso

Servicio = API Backend
URL = http://localhost:8000/api
Puerto = 8000

Servicio = Dashboard HTML
URL = file:///frontend/dashboard.html
Puerto = -

Servicio = Frontend Angular
URL = http://localhost:4200
Puerto = 4200

Servicio = phpMyAdmin
URL = http://localhost:8081
Puerto = 8081

📊 Flujo de Datos

CSV Files → Import Script → MySQL Database → PHP Backend → API REST → Frontend Dashboard
                                ↓
                        Stored Procedures
                                ↓
                        Calculated Commissions
                                ↓
                        Dashboard Metrics

✅ Checklist de Verificación

 Base de datos creada y configurada
 Tablas y procedimientos instalados
 Datos de prueba cargados
 Backend PHP funcionando
 API respondiendo correctamente
 Dashboard visualizando datos
 Importación CSV probada
 Cálculo de comisiones verificado
 Exportación de reportes funcionando
 Documentación completa

🎯 Características Implementadas
✅ Integración de Datos

Importación de múltiples archivos CSV
Validación y limpieza de datos
Manejo de errores robusto

✅ Cálculo de Comisiones

Reglas parametrizables
Bonos por cumplimiento
Penalizaciones por devoluciones
Cálculo automático mensual

✅ Dashboard Interactivo

KPIs en tiempo real
Gráficos con Chart.js
Tablas con DataTables
Diseño responsive

✅ Reportes y Exportación

Exportación a CSV/Excel
Reportes consolidados
Filtros avanzados

✅ API REST Completa

CRUD completo
Endpoints documentados
Respuestas JSON estándar

✅ Base de Datos Optimizada

Índices estratégicos
Procedimientos almacenados
Vistas precalculadas
Triggers automáticos

📚 Recursos Adicionales

Documentación de API
Manual de Usuario
Guía de Desarrollo
Arquitectura del Sistema


Sistema de Comisiones VentasPlus S.A.
Versión 1.0.0 | Diciembre 2024