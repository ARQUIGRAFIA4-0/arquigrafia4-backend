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

## Licença
O projeto ARQUIGRAFIA é um software livre licenciado segundo diretrizes da [GNU GPL](https://www.gnu.org/licenses/).

## Financiamentos
O presente trabalho foi realizado com apoio da **Fundação de Amparo à Pesquisa do Estado de São Paulo (FAPESP)**, Brasil. Processo nº 20/05134-9.
