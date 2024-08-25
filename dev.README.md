Migrate:
```shell
docker-compose -f .\dev.docker-compose.yaml exec php_app php artisan migrate
```

[External] For API:
```shell
# Step 1:
docker-compose -f .\dev.docker-compose.yaml exec php_app php artisan install:api

# Step 2 (Create API.php):
nano ./routes/api.php # ctrl + s, ctrl + c, y

#Step 3 (Add fictaion in boot)
```
```php
->withRouting(
    api: __DIR__.'/../routes/api.php',
    apiPrefix: '',
)
```
```shell
# Use for create Controller
docker-compose -f .\dev.docker-compose.yaml exec php_app php artisan make:controller TestController --api

```

[External] Caching:
```shell
composer require predis/predis --ignore-platform-req=ext-fileinfo
```
```env
REDIS_PORT=6379
...
CACHE_DRIVER=redis
CACHE_STORE=redis
REDIS_URL=redis_app
REDIS_HOST=redis_app
REDIS_PASSWORD=null
REDIS_CLIENT=predis
REDIS_DB=0
REDIS_CACHE_DB=1
```
```php
// database.php

❌ 'default' => [
        'url' => env('REDIS_URL')

✔  'default' => [
        'url' => env('REDIS_URL').env('REDIS_PORT', '6379').'?database='.env('REDIS_DB', '0'),
```