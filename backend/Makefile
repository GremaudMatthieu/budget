# Docker commands
up:
	docker compose up --build -d

up-no-build:
	docker compose up

down:
	docker compose down

shell:
	docker exec -it my_symfony_app /bin/sh

# Symfony and Composer commands
composer-install:
	docker exec -it my_symfony_app composer install

cache-clear:
	docker exec -it my_symfony_app bin/console cache:clear

# Database commands
database-create:
	docker exec -it my_symfony_app bin/console doctrine:database:create

database-drop:
	docker exec -it my_symfony_app bin/console doctrine:database:drop --force

migration-diff:
	docker exec -it my_symfony_app bin/console doctrine:migrations:diff

migration-apply:
	docker exec -it my_symfony_app bin/console doctrine:migrations:migrate

jwt-generate-key:
	docker exec -it my_symfony_app bin/console lexik:jwt:generate-keypair

# Testing and code quality commands
phpunit:
	docker exec -it my_symfony_app bin/phpunit tests/

phpunit-coverage:
	docker exec -it my_symfony_app ./vendor/bin/phpunit --coverage-html coverage

phpstan:
	docker exec -it my_symfony_app ./vendor/bin/phpstan analyse --level=max --generate-baseline --memory-limit=1G -vv

rector:
	docker exec -it my_symfony_app ./vendor/bin/rector process

cs-fixer:
	docker exec -it my_symfony_app ./vendor/bin/php-cs-fixer fix

# docker exec -it my_symfony_app /bin/sh -c 'export PHP_CS_FIXER_IGNORE_ENV=1 && ./vendor/bin/php-cs-fixer fix'
