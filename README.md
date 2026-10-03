# i-Educar Educacenso

Módulo desacoplado do Educacenso para o [i-Educar](https://github.com/portabilis/i-educar), com suporte aos Censos 2024, 2025 e 2026 e correções de integridade de dados (vínculo de servidores, turnos e alocações).

---

## ⚡ Instalação e Atualização Rápida (Recomendado)

Você pode instalar ou atualizar o pacote no seu servidor (VPS / Linux) com um único comando:

```bash
curl -fsSL https://raw.githubusercontent.com/douglas14031999/i-educar-educacenso-package/2.12/install.sh | bash
```

### 🤖 O que este script faz automaticamente:
1. **Detecção Inteligente:**
   * Se o pacote **não estiver instalado**: clona e configura o repositório automaticamente em `packages/portabilis/i-educar-educacenso-package`.
   * Se já for o **seu repositório (`douglas14031999`)**: realiza o `fetch`, `checkout 2.12` e `reset --hard` para a versão mais recente com atualização instantânea.
   * Se for o repositório da **Portabilis** ou versão legada: cria um backup de segurança (`.bak`), remove a versão antiga e instala o novo repositório limpo.
2. **Permissões de Arquivos:** Ajusta donos e permissões para `www-data:www-data` e `775`.
3. **Autoload do Composer:** Executa `composer dump-autoload --optimize` com descoberta automática do pacote no i-Educar.
4. **Banco e Menus:** Executa `php artisan migrate --force` e registra/ativa o menu **Importação educacenso** no sistema.
5. **Limpeza de Caches:** Limpa todos os caches da aplicação (`optimize:clear`, `config:clear`, `cache:clear`, `view:clear`).

---

## 🛠️ Instalação Manual

> Para usuários Docker, executar os comandos `# (Docker)` ao invés da linha seguinte.

1. Clone este repositório a partir da raiz do i-Educar:

```bash
git clone -b 2.12 https://github.com/douglas14031999/i-educar-educacenso-package.git packages/portabilis/i-educar-educacenso-package
```

2. Instale o pacote:

```bash
# (Docker) docker-compose exec php composer plug-and-play
composer plug-and-play
```
*(ou se não utilizar plug-and-play: `composer dump-autoload -o`)*

3. Execute as migrations:

```bash
# (Docker) docker-compose exec php artisan migrate
php artisan migrate
```

Antes de executar as migrations certifique-se que sua variável de ambiente `LEGACY_SEED_DATA` está definida como `true` no arquivo `.env`.

4. Limpeza de caches:

```bash
# (Docker) docker-compose exec php artisan cache:clear
php artisan cache:clear
php artisan config:clear
```

---

## Fluxo de trabalho

Todo commit, push e criação de branch de melhorias deverão ocorrer dentro da pasta
`packages/portabilis/i-educar-educacenso-package`, dessa forma você estará manipulando o 
repositório do Educacenso e não o repositório principal do i-Educar.

---

## Execução de testes

Os comandos abaixo devem ser executados a partir da raiz do i-Educar:

```bash
composer plug-and-play:add orchestra/testbench ^10
composer plug-and-play:add pestphp/pest ^4
composer plug-and-play:update
```

### Executar os testes:

```bash
vendor/bin/pest -c packages/portabilis/i-educar-educacenso-package/phpunit.package.xml --test-directory=packages/portabilis/i-educar-educacenso-package/tests
``` 

---

Powered by [Portábilis](https://portabilis.com.br/) & Comunidade i-Educar.
