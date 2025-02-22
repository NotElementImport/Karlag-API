docker=docker compose -f ./dev.docker-compose.yaml

init:
	$(docker) up -d --build;\
	$(docker) exec php_app composer install;\
	$(docker) exec php_app php artisan migrate

~artisan:
	$(docker) exec php_app php artisan $(q)
