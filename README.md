# Sobre o Projeto ARQUIGRAFIA

Online desde 2011, o [ARQUIGRAFIA](https://www.arquigrafia.org.br/home) é hoje um ambiente colaborativo temático com cerca de 14 mil imagens de arquiteturas e espaços urbanos, disponibilizadas para livre acesso, com direitos autorais protegidos por licenças [Creative Commons](https://creativecommons.org/share-your-work/cclicenses/).

# Instalação

Site oficial do Laravel para referência https://laravel.com/docs/12.x

## Requisitos
- PHP 8.4 (https://www.php.net/downloads)
- MySQL 8 (https://dev.mysql.com/downloads/installer/)
- Composer ^2.0 (https://getcomposer.org/download/)

## Na pasta raiz do projeto

Após clonar o projeto, criar um banco de dados local para ser usado pela aplicação.

Copiar o arquivo de **enviroment** de exemplo (de .env.example para .env)
e completar as configurações de acesso ao banco de dados.

    cp .env.example .env

Instalar dependências do Composer.

    composer install

Gerar chave de criptografia da aplicação.

    php artisan key:generate

Rodar as Migrations do banco de dados (não esquecer de criar o banco e salvar os acessos no .env). A opção --seed rodará todos os Seeders presentes no arquivo [DatabaseSeeder.php](database/seeders/DatabaseSeeder.php). Caso não deseje que isso aconteça, essa opção pode ser omitida.

    php artisan migrate:fresh [--seed]

Gerar chaves necessárias para a realização de login utilizando o [Passport](https://laravel.com/docs/12.x/passport), e criar cliente de senha. As informações desse cliente devem ser utilizadas no frontend para realizar o login via API.

    php artisan passport:keys
    php artisan passport:client --password

Gerar link público para as imagens de upload.

    php artisan storage:link

Para rodar um servidor local.

    php artisan serve

O servidor pode ser acessado em: http://localhost:8000

**Lista de comandos**

    git clone https://github.com/ARQUIGRAFIA4-0/arquigrafia4-backend.git
    cd arquigrafia4-backend
    cp .env.example .env
    composer install
    php artisan key:generate
    php artisan migrate:fresh [--seed]
    php artisan passport:keys
    php artisan passport:client --password
    php artisan storage:link
    php artisan serve

## Tiles IIIF

As imagens são servidas como pirâmides de tiles no padrão [IIIF Image API 3](https://iiif.io/api/image/3.0/), permitindo _deep zoom_ no visualizador do frontend.

### Como funciona

1. No upload (`POST /api/images`), a imagem original é gravada em `images/iiif/{uuid}/full/max/0/default.jpg` e um job `TileImage` é enfileirado.
2. O job [`TileImage`](app/Jobs/TileImage.php) roda o `dzsave` do libvips (`layout: iiif3`) para gerar a pirâmide de tiles e, ao concluir, grava `vrac_images.processed_at`.
3. A fila usa `QUEUE_CONNECTION=database`, então os jobs só são processados enquanto houver um _worker_ rodando.

`processed_at IS NOT NULL` é a fonte de verdade para "os tiles existem". A URL pública embutida nos manifestos/`info.json` vem de `config/iiif.php` (env `IIIF_BASE_URL`).

### Worker (processamento da fila)

Em produção o worker roda como um serviço **systemd**, sempre ativo e reiniciado automaticamente:

    sudo systemctl status arquigrafia-worker     # ver estado
    sudo systemctl restart arquigrafia-worker    # reiniciar
    sudo journalctl -u arquigrafia-worker -f      # acompanhar logs

O arquivo de unidade fica em `/etc/systemd/system/arquigrafia-worker.service` no servidor.

> **Importante:** o worker **precisa** rodar como o usuário `www-data` (o mesmo do servidor web). Rodar `queue:work` como outro usuário causa erros de `Permission denied` ao gravar os tiles em `images/iiif`.

Em desenvolvimento, basta rodar o worker manualmente:

    php artisan queue:work

### Verificação e auto-recuperação

O comando abaixo mostra a saúde do tiling (total, com tiles, sem tiles, órfãs sem original, jobs na fila/falhos):

    php artisan images:tile-status              # relatório
    php artisan images:tile-status --dispatch   # reenfileira as imagens sem tiles (idempotente)

Esse comando roda automaticamente **a cada hora** (agendado em [`routes/console.php`](routes/console.php) e disparado pelo cron `schedule:run`), então qualquer imagem que perca os tiles é reenfileirada sozinha. O `TileImage` é idempotente: pula imagens já processadas e pula (registrando no log, sem falhar) imagens cujo original não existe.

### Reprocessamento manual

    php artisan images:process-migrated               # todas sem tiles (idempotente)
    php artisan images:process-migrated --id=<uuid>   # uma imagem
    php artisan images:process-migrated --id=<uuid> --force   # força reprocessar
    php artisan queue:retry all                        # retenta jobs falhos

> **Observação:** cerca de 307 imagens legadas têm registro e diretório mas **não têm o arquivo original** (nunca migraram do servidor antigo). Elas permanecem sem `processed_at` e aparecem como "órfãs" no `images:tile-status` — isso é esperado, não é um bug.

## Licença
O projeto ARQUIGRAFIA é um software livre licenciado segundo diretrizes da [GNU GPL](https://www.gnu.org/licenses/).

## Financiamentos
O presente trabalho foi realizado com apoio da **Fundação de Amparo à Pesquisa do Estado de São Paulo (FAPESP)**, Brasil. Processo nº 20/05134-9.
