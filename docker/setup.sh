#!/bin/bash
# =============================================================
# setup.sh — ConInf AppGym
# Crea el volumen de la BD e inicializa el ambiente Docker
# Ejecutar desde la raíz del proyecto: bash docker/setup.sh
# =============================================================

set -e

COMPOSE_FILE="docker/docker-compose.yml"
VOLUME_NAME="gymapp_db_data"

echo ""
echo "╔══════════════════════════════════════╗"
echo "║   ConInf AppGym — Setup Docker       ║"
echo "╚══════════════════════════════════════╝"
echo ""

# 1. Verificar que Docker esté corriendo
echo "▶ Verificando Docker..."
if ! docker info > /dev/null 2>&1; then
  echo "✗ Docker no está corriendo. Inicialo e intentá de nuevo."
  exit 1
fi
echo "✓ Docker OK"

# 2. Crear volumen si no existe
echo ""
echo "▶ Verificando volumen de base de datos..."
if docker volume inspect "$VOLUME_NAME" > /dev/null 2>&1; then
  echo "✓ Volumen '$VOLUME_NAME' ya existe"
else
  docker volume create "$VOLUME_NAME"
  echo "✓ Volumen '$VOLUME_NAME' creado"
fi

# 3. Verificar archivo de configuración
echo ""
echo "▶ Verificando config/database.php..."
if [ ! -f "config/database.php" ]; then
  if [ -f "config/database.example.php" ]; then
    cp config/database.example.php config/database.php
    echo "✓ config/database.php creado desde el ejemplo"
  else
    echo "✗ No se encontró config/database.example.php"
    exit 1
  fi
else
  echo "✓ config/database.php ya existe"
fi

# 4. Build y levantar contenedores
echo ""
echo "▶ Construyendo imágenes y levantando contenedores..."
docker compose -f "$COMPOSE_FILE" up -d --build

# 5. Esperar a que MySQL esté listo
echo ""
echo "▶ Esperando a que la base de datos esté lista..."
RETRIES=15
COUNT=0
until docker exec app-gym-db mysqladmin ping -h localhost --silent 2>/dev/null; do
  COUNT=$((COUNT + 1))
  if [ "$COUNT" -ge "$RETRIES" ]; then
    echo "✗ La base de datos no respondió a tiempo."
    exit 1
  fi
  echo "  ... esperando ($COUNT/$RETRIES)"
  sleep 3
done
echo "✓ Base de datos lista"

# 6. Resumen final
echo ""
echo "╔══════════════════════════════════════╗"
echo "║   ✓ Ambiente listo                   ║"
echo "╠══════════════════════════════════════╣"
echo "║   App   →  http://localhost:8080     ║"
echo "║   MySQL →  localhost:3306            ║"
echo "║   BD    →  app_gym                   ║"
echo "║   User  →  appgym / appgym123        ║"
echo "╚══════════════════════════════════════╝"
echo ""
