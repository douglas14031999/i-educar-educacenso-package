#!/usr/bin/env bash
set -e

# ==============================================================================
# Script de Instalação e Atualização Inteligente - i-Educar Educacenso
# Repositório: douglas14031999/i-educar-educacenso-package (Branch 2.12)
# ==============================================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

echo -e "${BLUE}======================================================================${NC}"
echo -e "${BLUE}    Gerenciador do Pacote Educacenso para o i-Educar (Douglas)        ${NC}"
echo -e "${BLUE}======================================================================${NC}"

# 1. Localizar diretório raiz do i-Educar
IEDUCAR_DIR="/var/www/ieducar"
if [ ! -d "$IEDUCAR_DIR" ] && [ -f "./artisan" ]; then
    IEDUCAR_DIR="$(pwd)"
elif [ ! -d "$IEDUCAR_DIR" ] && [ -d "/var/www/html" ] && [ -f "/var/www/html/artisan" ]; then
    IEDUCAR_DIR="/var/www/html"
fi

if [ ! -d "$IEDUCAR_DIR" ] || [ ! -f "$IEDUCAR_DIR/artisan" ]; then
    echo -e "${RED}[ERRO] Instalação do i-Educar não encontrada em $IEDUCAR_DIR!${NC}"
    echo -e "Certifique-se de executar o comando no servidor onde o i-Educar está instalado."
    exit 1
fi

echo -e "${CYAN}Diretório do i-Educar:${NC} $IEDUCAR_DIR"
cd "$IEDUCAR_DIR"

PACKAGE_DIR="${IEDUCAR_DIR}/packages/portabilis/i-educar-educacenso-package"
REPO_URL="https://github.com/douglas14031999/i-educar-educacenso-package.git"
BRANCH="2.12"
TIMESTAMP=$(date +%Y%m%d%H%M%S)

# 2. Verificar se o pacote já está instalado e sua procedência
if [ -d "$PACKAGE_DIR" ]; then
    echo -e "\n${YELLOW}[1/5] Pacote detectado em: $PACKAGE_DIR${NC}"
    
    IS_DOUGLAS_REPO=false
    if [ -d "$PACKAGE_DIR/.git" ]; then
        REMOTE_URL=$(git -C "$PACKAGE_DIR" config --get remote.origin.url || true)
        echo -e "      Origem remota detectada: ${CYAN}${REMOTE_URL}${NC}"
        
        if [[ "$REMOTE_URL" == *"douglas14031999"* ]]; then
            IS_DOUGLAS_REPO=true
        fi
    fi

    if [ "$IS_DOUGLAS_REPO" = true ]; then
        echo -e "${GREEN}==> Repositório do Douglas confirmado! Atualizando para a versão mais recente...${NC}"
        git -C "$PACKAGE_DIR" fetch origin "$BRANCH"
        git -C "$PACKAGE_DIR" checkout "$BRANCH"
        git -C "$PACKAGE_DIR" reset --hard "origin/$BRANCH"
        git -C "$PACKAGE_DIR" clean -fd
        echo -e "${GREEN}      Repositório atualizado com sucesso!${NC}"
    else
        echo -e "${YELLOW}==> Repositório da Portabilis ou versão antiga detectada!${NC}"
        echo -e "${YELLOW}      Substituindo pelo repositório customizado do Douglas...${NC}"
        
        BACKUP_DIR="${IEDUCAR_DIR}/packages/portabilis/i-educar-educacenso-package.bak.${TIMESTAMP}"
        echo -e "      Criando backup de segurança em: ${BACKUP_DIR}"
        cp -r "$PACKAGE_DIR" "$BACKUP_DIR"
        rm -rf "$PACKAGE_DIR"

        echo -e "${BLUE}      Clonando repositório do Douglas (${REPO_URL} - branch ${BRANCH})...${NC}"
        mkdir -p "$(dirname "$PACKAGE_DIR")"
        git clone -b "$BRANCH" "$REPO_URL" "$PACKAGE_DIR"
        echo -e "${GREEN}      Repositório do Douglas instalado com sucesso!${NC}"
    fi
else
    echo -e "\n${YELLOW}[1/5] Pacote não encontrado. Realizando nova instalação do repositório do Douglas...${NC}"
    mkdir -p "$(dirname "$PACKAGE_DIR")"
    git clone -b "$BRANCH" "$REPO_URL" "$PACKAGE_DIR"
    echo -e "${GREEN}      Repositório clonado com sucesso em: $PACKAGE_DIR${NC}"
fi

# 3. Ajustar permissões para o usuário web (www-data)
echo -e "\n${BLUE}[2/5] Ajustando permissões de arquivos...${NC}"
chown -R www-data:www-data "$PACKAGE_DIR"
chmod -R 775 "$PACKAGE_DIR"

# 4. Atualizar autoload do Composer
echo -e "\n${BLUE}[3/5] Atualizando o Autoload do Composer...${NC}"
export COMPOSER_ALLOW_SUPERUSER=1
composer dump-autoload --optimize

# 5. Executar migrations e garantir menu ativo
echo -e "\n${BLUE}[4/5] Executando migrations e registrando menu do Educacenso...${NC}"
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

# 6. Limpar caches da aplicação
echo -e "\n${BLUE}[5/5] Limpando caches do sistema...${NC}"
php artisan optimize:clear || true
php artisan config:clear || true
php artisan cache:clear || true
php artisan view:clear || true

echo -e "\n${GREEN}======================================================================${NC}"
echo -e "${GREEN}  Pacote Educacenso (Douglas) configurado e ativo com sucesso!        ${NC}"
echo -e "${GREEN}======================================================================${NC}"
echo -e "${CYAN}Commit instalado:${NC} $(git -C "$PACKAGE_DIR" log -1 --oneline || true)"
echo -e "${CYAN}Branch ativa:${NC} $(git -C "$PACKAGE_DIR" branch --show-current || true)\n"
