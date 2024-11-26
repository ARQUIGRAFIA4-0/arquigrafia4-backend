# Instalação

Site oficial do Laravel para referência https://laravel.com/docs/8.x/installation

## Requisitos
- PHP 8.0 (https://www.php.net/downloads)
- MySQL 8 (https://dev.mysql.com/downloads/installer/)
- NPM (https://docs.npmjs.com/downloading-and-installing-node-js-and-npm)
- Composer (https://getcomposer.org/download/)

## Na pasta raiz do projeto

Após clonar, mudar para o branch de desenvolvimento

    git checkout develop

Criar um banco de dados local para ser usardo pela aplicação

Copiar o arquivo de enviroment de exemplo (de .env.example para .env)
e completar as configurações de acesso ao banco de dados

    cp .env.example .env

Instalar dependências do Composer

    composer install

Gerar chave da aplicação

    php artisan key:generate

Rodar as migrations do banco de dados (não esquecer de criar o banco e salvar os acessos no .env)

    php artisan migrate:fresh --seed

Gerar link público para as imagens de upload

    php artisan storage:link

Instalar dependências do NPM (necessário apenas para desenvolvimento)

    npm install

Para rodar um servidor local

    php artisan serve

Você pode acessor o servidor em: http://localhost:8000

**Lista de comandos**

    git clone git@bitbucket.org:sa365/rc-portalvet.git -b develop
    cd rc-portalvet
    cp .env.example .env
    composer install
    php artisan key:generate
    php artisan migrate:fresh --seed
    php artisan storage:link
    npm install
    php artisan serve
    
## Desenvolvimento Front-End
O servidor do Laravel acessa tudo pela pasta /public presente na raiz do projeto, mas o desenvolvimento do front é feito na pasta /resources, também presente na raiz. Para gerar os arquivos que o servidor vai utilizar, é necessário usar o Mix (https://laravel.com/docs/8.x/mix). O projeto já está configurado com o básico do Mix, e pronto para usar com os comandos:

Compilar simples que apenas cria os arquivos necessários e é mais rápido de executar:

    npm run dev

Compilar simples que atualiza os arquivos toda vez que um deles é salvo:

    npm run watch

Compilar minimizado e mais demorado:

    npm run prod

Sempre antes de atualizar os arquivos no git é necessário rodar o comando para minimizar, para otimizar os acessos no servidor.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
