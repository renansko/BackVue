# Desafio I NOV - Backend
Esse projeto utilizei API's utilizando Laravel 9.x
Utilizo RabbitMQ para processar jobs e events para enviar as noticias aos usuarios. 

No envio de e-mail na primeira interação ele vai adicionar as 15 noticias no banco
Porem não seria interessante mandar para todos os usuarios as 15 noticias então
ele vai mandar com a ultima.
Ficaria a opção de mandar as outras noticias antigas como opção

Depois da primeira interação ele manda os e-mails normalmente. 

Se o JOB foi diario, ele envia muitos e-mails para o usuario (ao meu ver não seria interessante 
receber 5 e-mail de noticias do dia passado de uma vez)
# Exemplo de uso


Assista ao vídeo: [Loom Video](https://www.loom.com/share/bac016542ad44f3fa51ec88b49f95016)



## Requisitos do sistema
- PHP 8.1+
- Composer
- PostgreSQL
- RabbitMQ
- Docker (Opcional)
- PHP Configuration

## PHP configuration

**Para que o RabbitMQ funcione no projeto habilite a extenção 'sockets' no php.ini**

### Habilitar no Windows (XAMPP/WAMP):

Abra seu arquivo php.ini
Descomente o  extension=sockets
Reinicie o server

### Habilitar Linux (Ubuntu/Debian):

```SH
sudo apt-get install php8.1-sockets
sudo systemctl restart php8.1-fpm  # If using FPM
sudo systemctl restart apache2     # If using Apache
```

## Docker Setup Windows
1. Instale  [docker](https://www.docker.com/products/docker-desktop)
2. Rode o comando
```sh
docker-compose up -d
```

## Docker Linux
```sh
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

sudo curl -L "https://github.com/docker/compose/releases/download/v2.18.1/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

sudo systemctl enable docker
sudo systemctl start docker


sudo usermod -aG docker $USER
```

2. Rode Docker compose 
```sh
docker-compose up -d
```

# Inicializar Projeto

### Instale dependecias
```sh
composer install
```

### Configuração do .env
```sh
cp .env.example .env
php artisan key:generate
```


### .env For Database And RabitMQ
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=desafio_inov
DB_USERNAME=postgres
DB_PASSWORD=your_password

QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=localhost
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
```

### Database Setup
```sh
php artisan migrate
php artisan db:seed  # Opcional
```

# Rodar projeto

```sh
php artisan serve
```

```sh
php artisan queue:work rabbitmq
```

# Testes:

Rodar job que busca as noticias:

```sh
php artisan tinker
> App\Jobs\getNewsJob::dispatch();

Ou

php artisan job:dispatch "App\Jobs\getNewsJob"
```


Rodar Event para emitir e-mail:
```sh
php artisan tinker

$news = App\Models\News::first() ?? App\Models\News::create([
    'title' => 'Test News Article',
    'description' => 'This is a test news article',
    'link' => 'https://example.com/test-article',
    'pubDate' => now(),
    'news_hash' => md5('test-article'),
]);

App\Events\NewsProcessedEvent::dispatch($news->id);
```

# Troubleshooting
- Se você encontrar problemas de conexão com o RabbitMQ, verifique a interface de gerenciamento do RabbitMQ em http://localhost:15672 (credenciais padrão: guest/guest).

- Para problemas de conexão com o banco de dados, certifique-se de que o PostgreSQL está em execução e que as credenciais no seu arquivo .env estão corretas.

- Verifique os logs do Laravel em laravel.log para mensagens de erro detalhadas.