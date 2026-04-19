#!/bin/bash
# =============================================================================
# setup.sh — Setup inicial de app-gym en el VPS
# Repo: app-gym-docker
# Requiere haber corrido setup.sh del repo nginx-proxy primero
# Correr como root: bash setup.sh
# =============================================================================

set -e

APP_USER="deploy"
APP_GYM_DIR="/srv/app-gym"
BACKUP_DIR="/srv/backups/app-gym"

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
ok()   { echo -e "${GREEN}✅ $1${NC}"; }
warn() { echo -e "${YELLOW}⚠️  $1${NC}"; }
err()  { echo -e "${RED}❌ $1${NC}"; exit 1; }

[ "$EUID" -ne 0 ] && err "Este script debe correrse como root"

# Verificar que el setup del proxy ya se corrió
if ! docker network inspect nginx-proxy_net &>/dev/null; then
  err "La red 'nginx-proxy_net' no existe. Corré primero el deploy.sh del repo nginx-proxy."
fi

echo ""
echo "============================================="
echo "  Setup — app-gym"
echo "============================================="
echo ""

# ── 1. Directorios ────────────────────────────────────────────────────────────
echo "📁 Creando directorios..."
mkdir -p $APP_GYM_DIR/app
mkdir -p $BACKUP_DIR
chown -R $APP_USER:$APP_USER $APP_GYM_DIR
chown -R $APP_USER:$APP_USER $BACKUP_DIR
ok "Directorios creados: $APP_GYM_DIR y $BACKUP_DIR"

# ── 2. Volumen MySQL ──────────────────────────────────────────────────────────
echo ""
echo "💾 Creando volumen Docker para MySQL..."
if docker volume inspect app-gym_mysql_data &>/dev/null; then
  warn "El volumen ya existe, saltando..."
else
  docker volume create app-gym_mysql_data
  ok "Volumen 'app-gym_mysql_data' creado"
fi

# ── 3. Crear .env interactivo ─────────────────────────────────────────────────
echo ""
echo "⚙️  Configuración del archivo .env"
echo "─────────────────────────────────────────────"

if [ -f "$APP_GYM_DIR/.env" ]; then
  warn ".env ya existe en $APP_GYM_DIR/.env — saltando (editalo manualmente si cambió)"
else
  echo "Completá los valores (Enter para usar el default entre corchetes):"
  echo ""

  read -p "  MySQL root password:       " MYSQL_ROOT_PASSWORD
  read -p "  MySQL usuario de la app:   [app-gym_user] " MYSQL_USER
  MYSQL_USER=${MYSQL_USER:-app-gym_user}
  read -p "  MySQL password de la app:  " MYSQL_PASSWORD
  read -p "  MySQL nombre de la BD:     [app-gym] " MYSQL_DATABASE
  MYSQL_DATABASE=${MYSQL_DATABASE:-app-gym}

  cat > $APP_GYM_DIR/.env <<EOF
# Base de datos — MYSQLHOST debe ser "db" siempre
DB_HOST=db
DB_PORT=3306
DB_EXTERNAL_PORT=3406
DB_ROOT_PASS=$MYSQL_ROOT_PASSWORD
DB_NAME=$MYSQL_DATABASE
DB_USER=$MYSQL_USER
DB_PASS=$MYSQL_PASSWORD
DB_CHARSET=utf8mb4
VIRTUAL_HOST_DB=appgymdb.coninf.com.ar

# PROXY
VIRTUAL_HOST=appgym.coninf.com.ar
VIRTUAL_PORT=80
LETSENCRYPT_HOST=appgym.coninf.com.ar
LETSENCRYPT_EMAIL=admin@appgym.coninf.com.ar
EOF

  chmod 600 $APP_GYM_DIR/.env
  chown $APP_USER:$APP_USER $APP_GYM_DIR/.env
  ok ".env creado en $APP_GYM_DIR/.env"
fi

# ── 4. Backup automático ──────────────────────────────────────────────────────
echo ""
echo "💾 Configurando backup automático de MySQL..."

cat > /usr/local/bin/backup-app-gym.sh <<'BACKUP'
#!/bin/bash
BACKUP_DIR="/srv/backups/app-gym"
FECHA=$(date +%Y-%m-%d_%H-%M)
ARCHIVO="$BACKUP_DIR/db_$FECHA.sql.gz"

source /srv/app-gym/.env

docker exec app-gym-db \
  mysqldump -u root -p"$DB_ROOT_PASS" "$DB_NAME" \
  | gzip > "$ARCHIVO"

# Retención 7 días
find $BACKUP_DIR -name "*.sql.gz" -mtime +7 -delete

echo "[$FECHA] Backup guardado: $ARCHIVO"
BACKUP

chmod +x /usr/local/bin/backup-app-gym.sh

CRON_JOB="0 3 * * * /usr/local/bin/backup-app-gym.sh >> /var/log/backup-app-gym.log 2>&1"
( crontab -l 2>/dev/null | grep -v "backup-app-gym"; echo "$CRON_JOB" ) | crontab -
ok "Backup automático: todos los días a las 3am, retención 7 días"

# ── 5. Resumen ────────────────────────────────────────────────────────────────
VPS_IP=$(curl -s ifconfig.me 2>/dev/null || echo 'IP_DEL_VPS')
echo ""
echo "============================================="
ok "Setup de app-gym completado"
echo "============================================="
echo ""
echo "Próximo paso — desde tu máquina local (repo app-gym-docker):"
echo "  ./deploy.sh $VPS_IP"
echo ""