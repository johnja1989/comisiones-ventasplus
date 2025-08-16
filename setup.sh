#!/bin/bash

# =====================================================
# Script de Instalación - Sistema de Comisiones
# VentasPlus S.A. © 2024
# =====================================================

echo "╔══════════════════════════════════════════════════════════╗"
echo "║     INSTALACIÓN DEL SISTEMA DE COMISIONES               ║"
echo "║     VentasPlus S.A.                                     ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para verificar comandos
check_command() {
    if ! command -v $1 &> /dev/null; then
        echo -e "${RED}✗ $1 no está instalado${NC}"
        return 1
    else
        echo -e "${GREEN}✓ $1 está instalado${NC}"
        return 0
    fi
}

# Función para verificar éxito
check_success() {
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ $1${NC}"
    else
        echo -e "${RED}✗ Error: $1${NC}"
        exit 1
    fi
}

# =====================================================
# 1. Verificar requisitos
# =====================================================
echo ""
echo "1. Verificando requisitos del sistema..."
echo "----------------------------------------"

check_command "php" || exit 1
check_command "mysql" || exit 1
check_command "composer" || exit 1
check_command "node" || exit 1
check_command "npm" || exit 1
check_command "git" || exit 1

# Verificar versión de PHP
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
if (( $(echo "$PHP_VERSION >= 8.1" | bc -l) )); then
    echo -e "${GREEN}✓ PHP versión $PHP_VERSION${NC}"
else
    echo -e "${RED}✗ Se requiere PHP 8.1 o superior${NC}"
    exit 1
fi

# =====================================================
# 2. Configurar base de datos
# =====================================================
echo ""
echo "2. Configurando base de datos..."
echo "--------------------------------"

read -p "Usuario MySQL (default: root): " DB_USER
DB_USER=${DB_USER:-root}

read -sp "Contraseña MySQL: " DB_PASS
echo ""

# Crear base de datos
echo "Creando base de datos..."
mysql -u $DB_USER -p$DB_PASS -e "DROP DATABASE IF EXISTS comisiones_db; CREATE DATABASE comisiones_db CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;" 2>/dev/null
check_success "Base de datos creada"

# Ejecutar migraciones
echo "Ejecutando migraciones..."
mysql -u $DB_USER -p$DB_PASS comisiones_db < backend/database/migrations/001_create_initial_schema.sql 2>/dev/null
check_success "Esquema creado"

# Ejecutar procedimientos almacenados
echo "Creando procedimientos almacenados..."
mysql -u $DB_USER -p$DB_PASS comisiones_db < backend/database/migrations/002_create_procedures.sql 2>/dev/null
check_success "Procedimientos creados