#!/bin/bash
# =============================================================================
# deploy.sh — Deploy de app-gym al VPS
# Repo: app-gym-docker
# Uso: ./deploy.sh [IP_DEL_VPS]
# Requiere haber corrido setup.sh de nginx-proxy y de este repo primero
# =============================================================================

set -e

DOMINIO="coninf.com.ar"
VPS_IP="${1}"
VPS_USER="deploy"
APP_GYM_DIR="/srv/app-gym"
APP_DIR=".."       # ruta local al código PHP (repo separado)

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
ok()   { echo -e "${GREEN}✅ $1${NC}"; }
warn() { echo -e "${YELLOW}⚠️  $1${NC}"; }
err()  { echo -e "${RED}❌ $1${NC}"; exit 1; }

[ -z "$VPS_IP" ]    && err "Falta la IP. Uso: ./deploy.sh [IP_DEL_VPS]"
[ ! -d "$APP_DIR" ] && err "No se encuentra el código PHP en '$APP_DIR'"

echo ""
echo "============================================="
echo "  Deploy app-gym → $VPS_IP"
echo "============================================="
echo ""

# ── 1. Copiar archivos de configuración ───────────────────────────────────────
echo "📤 Copiando archivos de configuración..."
scp docker-compose.prod.yml  "$VPS_USER@$VPS_IP:$APP_GYM_DIR/docker-compose.yml"
scp prod.dockerfile           "$VPS_USER@$VPS_IP:$APP_GYM_DIR/prod.dockerfile"
scp nginx.prod.conf           "$VPS_USER@$VPS_IP:$APP_GYM_DIR/nginx.prod.conf"
scp php-config.prod.ini       "$VPS_USER@$VPS_IP:$APP_GYM_DIR/php-config.prod.ini"
ok "Archivos de configuración copiados"

# ── 2. Copiar .env solo si no existe ─────────────────────────────────────────
echo "⚙️  Verificando .env en el VPS..."
ENV_EXISTS=$(ssh "$VPS_USER@$VPS_IP" "[ -f $APP_GYM_DIR/.env ] && echo 'yes' || echo 'no'")
if [ "$ENV_EXISTS" = "yes" ]; then
  warn ".env ya existe en el VPS — no se sobreescribe"
else
  [ ! -f ".env" ] && err "No existe .env local. Copiá .env.example y completalo."
  scp .env "$VPS_USER@$VPS_IP:$APP_GYM_DIR/.env"
  ssh "$VPS_USER@$VPS_IP" "chmod 600 $APP_GYM_DIR/.env"
  ok ".env copiado"
fi

# ── 3. Sincronizar código PHP ─────────────────────────────────────────────────
echo "📤 Sincronizando código PHP..."
#rsync -az --delete \
#  --exclude='.git' \
#  --exclude='node_modules' \
#  --exclude='.env' \
#  --exclude='storage/logs/*' \
#  "$APP_DIR/" "$VPS_USER@$VPS_IP:$APP_GYM_DIR/app/"
ok "Código sincronizado"

# ── 4. Levantar contenedores ──────────────────────────────────────────────────
echo "🐳 Levantando contenedores..."
ssh "$VPS_USER@$VPS_IP" "
  cd $APP_GYM_DIR
  docker compose up -d --build --remove-orphans
"
ok "Contenedores levantados"

# ── 5. Esperar certificado SSL ────────────────────────────────────────────────
echo "🔒 Esperando certificado SSL (acme-companion)..."
echo "   (puede tardar hasta 60 segundos la primera vez)"
ssh "$VPS_USER@$VPS_IP" "
  for i in \$(seq 1 12); do
    if docker exec proxy_acme test -f /etc/nginx/certs/$DOMINIO.crt 2>/dev/null; then
      echo '   ✅ Certificado listo'
      break
    fi
    echo \"   Intento \$i/12 — esperando 5s...\"
    sleep 5
  done
"

# ── 6. Resumen ────────────────────────────────────────────────────────────────
echo ""
echo "============================================="
ok "Deploy completado"
echo "============================================="
echo ""
echo "  🌐 https://$DOMINIO"
echo ""
echo "Comandos útiles (ssh $VPS_USER@$VPS_IP):"
echo "  Logs app:        docker compose -f $APP_GYM_DIR/docker-compose.yml logs -f"
echo "  Logs proxy:      docker logs proxy_nginx -f"
echo "  Logs SSL:        docker logs proxy_acme -f"
echo "  Estado:          docker ps"
echo "  Backup manual:   /usr/local/bin/backup-app-gym.sh"
echo ""
