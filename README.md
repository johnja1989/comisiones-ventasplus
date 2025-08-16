# Sistema de Cálculo de Comisiones - VentasPlus S.A.

## 📋 Descripción del Proyecto

Sistema integral para el cálculo automatizado de comisiones de la fuerza de ventas de VentasPlus S.A. La solución integra múltiples fuentes de datos, parametriza reglas de comisiones y genera reportes e indicadores clave para el área comercial.

## 🚀 Características Principales

- **Integración de Datos**: Procesamiento de archivos CSV con ventas y devoluciones
- **Cálculo Parametrizado**: Sistema flexible de reglas de comisiones
- **Dashboard Interactivo**: Visualización de KPIs y métricas clave
- **API REST**: Endpoints para consulta de datos y cálculos
- **Reportes Detallados**: Exportación en múltiples formatos

## 🛠️ Stack Tecnológico

### Backend
- **PHP 8.1** con patrón MVC
- **MySQL 8.0** para persistencia de datos
- **Composer** para gestión de dependencias

### Frontend
- **Angular 15** para SPA
- **Bootstrap 5** para diseño responsivo
- **Chart.js** para visualizaciones
- **DataTables** para reportes tabulares

### ETL y Procesamiento
- **Scripts PHP** personalizados para ETL
- **Cron Jobs** para procesamiento automatizado

### Herramientas
- **Git** para versionamiento
- **Docker** para containerización
- **PHPUnit** para testing

## 📁 Estructura del Proyecto

```
comisiones-ventasplus/
├── backend/
│   ├── app/
│   │   ├── Controllers/
│   │   ├── Models/
│   │   ├── Services/
│   │   └── Config/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeds/
│   ├── public/
│   └── tests/
├── frontend/
│   ├── src/
│   │   ├── app/
│   │   ├── assets/
│   │   └── environments/
│   └── dist/
├── etl/
│   ├── scripts/
│   └── data/
├── docker/
├── docs/
└── README.md
```

## ⚙️ Instalación

### Requisitos Previos
- PHP 8.1+
- MySQL 8.0+
- Node.js 18+
- Composer
- Git

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/usuario/comisiones-ventasplus.git
cd comisiones-ventasplus
```

2. **Configurar Base de Datos**
```bash
mysql -u root -p < database/schema.sql
mysql -u root -p comisiones_db < database/seeds/initial_data.sql
```

3. **Instalar dependencias del Backend**
```bash
cd backend
composer install
cp .env.example .env
# Editar .env con las credenciales de BD
```

4. **Instalar dependencias del Frontend**
```bash
cd ../frontend
npm install
```

5. **Ejecutar migraciones**
```bash
cd ../backend
php artisan migrate
```

6. **Importar datos iniciales**
```bash
php etl/import_csv.php
```

### Ejecución del Proyecto

**Backend:**
```bash
cd backend
php -S localhost:8000 -t public
```

**Frontend:**
```bash
cd frontend
ng serve
```

Acceder a: http://localhost:4200

## 🔧 Configuración

### Variables de Entorno (.env)
```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=comisiones_db
DB_USERNAME=root
DB_PASSWORD=password

APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Configuración de comisiones
COMISION_BASE=5
BONO_ADICIONAL=2
LIMITE_BONO=50000000
PENALIZACION=1
LIMITE_DEVOLUCIONES=5
```

## 📊 Reglas de Negocio

### Cálculo de Comisiones

1. **Comisión Base**: 5% del valor total de ventas
2. **Bono Adicional**: +2% si ventas mensuales > $50,000,000 COP
3. **Penalización**: -1% si índice de devoluciones > 5%

### Fórmula de Cálculo
```
Ventas Netas = Total Ventas - Total Devoluciones
Índice Devoluciones = (Total Devoluciones / Total Ventas) * 100

Si Ventas Netas > 50,000,000:
    Comisión = Ventas Netas * 0.07
Si Índice Devoluciones > 5%:
    Comisión = Ventas Netas * 0.04
Sino:
    Comisión = Ventas Netas * 0.05
```

## 🔌 API Endpoints

### Vendedores
- `GET /api/vendedores` - Lista todos los vendedores
- `GET /api/vendedores/{id}` - Detalle de vendedor
- `GET /api/vendedores/{id}/comisiones` - Comisiones por vendedor

### Ventas
- `GET /api/ventas` - Lista todas las ventas
- `POST /api/ventas/importar` - Importar CSV de ventas
- `GET /api/ventas/resumen` - Resumen de ventas

### Comisiones
- `GET /api/comisiones` - Lista todas las comisiones
- `POST /api/comisiones/calcular` - Calcular comisiones del mes
- `GET /api/comisiones/reporte` - Reporte consolidado

### Dashboard
- `GET /api/dashboard/kpis` - KPIs principales
- `GET /api/dashboard/top-vendedores` - Top 5 vendedores
- `GET /api/dashboard/tendencias` - Tendencias mensuales

## 📈 KPIs y Métricas

### Indicadores Principales
- Total Ventas Mensuales
- Total Comisiones a Pagar
- Porcentaje de Vendedores con Bono
- Índice Promedio de Devoluciones
- Top 5 Vendedores por Comisión

### Reportes Disponibles
1. **Reporte de Comisiones**: Detalle por vendedor
2. **Análisis de Ventas**: Tendencias y comparativas
3. **Reporte de Devoluciones**: Análisis de causas
4. **Dashboard Ejecutivo**: Vista consolidada

## 🧪 Testing

### Ejecutar Tests
```bash
cd backend
./vendor/bin/phpunit
```

### Coverage
```bash
./vendor/bin/phpunit --coverage-html coverage
```

## 📝 Documentación Adicional

- [Arquitectura del Sistema](docs/arquitectura.md)
- [Manual de Usuario](docs/manual_usuario.md)
- [API Documentation](docs/api.md)
- [Guía de Desarrollo](docs/desarrollo.md)

## 🚢 Despliegue

### Con Docker
```bash
docker-compose up -d
```

### Producción Manual
Ver [Guía de Despliegue](docs/deployment.md)

## 🤝 Contribución

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/AmazingFeature`)
3. Commit cambios (`git commit -m 'Add AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir Pull Request

## 📄 Licencia

Propiedad de VentasPlus S.A. - Todos los derechos reservados.

## 👥 Equipo de Desarrollo

- **Desarrollador Principal**: [Tu Nombre]
- **Fecha de Desarrollo**: Diciembre 2024

## 📞 Soporte

Para soporte técnico, contactar:
- Email: soporte@ventasplus.com
- Documentación: [Wiki del Proyecto](https://github.com/usuario/comisiones-ventasplus/wiki)

---

**Versión**: 1.0.0  
**Última Actualización**: Diciembre 2024