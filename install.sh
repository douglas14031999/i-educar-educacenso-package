#!/usr/bin/env bash
set -e

# ==============================================================================
# Script de Atualização do Pacote i-Educar Educacenso
# Repositório: douglas14031999/i-educar-educacenso-package (Branch 2.12)
# Suporte aos Censos 2024, 2025 e 2026 com correções de integridade
# ==============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}======================================================${NC}"
echo -e "${BLUE}  Instalador do Pacote Educacenso para o i-Educar     ${NC}"
echo -e "${BLUE}======================================================${NC}"

IEDUCAR_DIR="/var/www/ieducar"
PACKAGE_DIR="${IEDUCAR_DIR}/packages/portabilis/i-educar-educacenso-package"
REPO_URL="https://github.com/douglas14031999/i-educar-educacenso-package.git"
BRANCH="2.12"
BACKUP_DIR="${IEDUCAR_DIR}/packages/portabilis/i-educar-educacenso-package.bak.$(date +%Y%m%d%H%M%S)"

# 1. Verificar se o diretório do i-Educar existe
if [ ! -d "$IEDUCAR_DIR" ]; then
    echo -e "${RED}[ERRO] Diretório $IEDUCAR_DIR não encontrado! Verifique o caminho da instalação.${NC}"
    exit 1
fi

cd "$IEDUCAR_DIR"

# 2. Fazer backup da versão instalada atualmente
if [ -d "$PACKAGE_DIR" ]; then
    echo -e "${YELLOW}[1/5] Realizando backup da versão atual para:${NC}"
    echo -e "      ${BACKUP_DIR}"
    cp -r "$PACKAGE_DIR" "$BACKUP_DIR"
    rm -rf "$PACKAGE_DIR"
else
    echo -e "${YELLOW}[1/5] Diretório do pacote não existia previamente. Prosseguindo com nova instalação.${NC}"
fi

# 3. Clonar a nova versão do GitHub
echo -e "${BLUE}[2/5] Clonando o repositório ${REPO_URL} (branch: ${BRANCH})...${NC}"
mkdir -p "$(dirname "$PACKAGE_DIR")"
git clone -b "$BRANCH" "$REPO_URL" "$PACKAGE_DIR"

# 4. Ajustar permissões para o usuário web (www-data)
echo -e "${BLUE}[3/5] Ajustando permissões do diretório...${NC}"
chown -R www-data:www-data "$PACKAGE_DIR"
chmod -R 775 "$PACKAGE_DIR"

# 5. Atualizar autoload do Composer
echo -e "${BLUE}[4/5] Atualizando o Autoload do Composer...${NC}"
export COMPOSER_ALLOW_SUPERUSER=1
composer dump-autoload --optimize

# 6. Executar migrations e registrar item de menu
echo -e "${BLUE}[5/6] Executando migrations e ativando item de menu...${NC}"
if [ -f "artisan" ]; then
    php artisan migrate --force || true
    php artisan tinker --execute="
        \$educacensoMenu = \App\Menu::where('title', 'Educacenso')->first();
        \$menuImportacao = \App\Menu::where('title', 'Importações')->first();
        if (\$menuImportacao) {
            \App\Menu::updateOrCreate(
                ['process' => 9998849],
                [
                    'parent_id' => \$menuImportacao->getKey(),
                    'title' => 'Importação educacenso',
                    'description' => 'Importação educacenso',
                    'link' => '/educacenso/import-registrations/create',
                    'order' => 0,
                    'type' => 1,
                    'parent_old' => 9998848,
                    'old' => 9998849,
                    'active' => true,
                ]
            );
        }
    " || true
fi

# 7. Limpar caches do Laravel / i-Educar
echo -e "${BLUE}[6/6] Limpando caches da aplicação...${NC}"
if [ -f "artisan" ]; then
    php artisan optimize:clear || true
    php artisan config:clear || true
    php artisan cache:clear || true
    php artisan view:clear || true
fi

echo -e "\n${GREEN}======================================================${NC}"
echo -e "${GREEN}  Pacote Educacenso atualizado com sucesso!           ${NC}"
echo -e "${GREEN}======================================================${NC}"
echo -e "${BLUE}Verificação da versão instalada:${NC}"
composer show portabilis/i-educar-educacenso-package || true
echo -e "\n${YELLOW}Backup preservado em:${NC} $BACKUP_DIR\n"
