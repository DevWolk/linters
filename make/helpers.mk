fix-syntax-completely: ## Fix code style by all code style instruments
	-@make syntax-fix
	-@make rector
	-@make php-cs-fixer
	-@make phpstan
	-@make composer-validate
	-@make composer-audit
	-@make test-unit

test-unit: ## Run unit tests
	vendor/bin/phpunit -d memory_limit=512M --order-by=random --colors=always

syntax-fix: ## Fix code style with php code sniffer tool
	./bin/linters generate phpcs
	vendor/bin/phpcbf -p --standard=phpcs.xml
syntax: ##  Check code style with php code sniffer tool
	./bin/linters run phpcs

rector: ## Fix code style with Rector tool
	./bin/linters run rector
rector-dry-run: ## Check code style with Rector tool
	./bin/linters generate rector
	vendor/bin/rector process --config=rector.php --clear-cache --dry-run

php-cs-fixer: ## Fix code style with PHP-CS-Fixer tool
	./bin/linters run php-cs-fixer
php-cs-fixer-check: ## Check code style with PHP-CS-Fixer tool
	./bin/linters generate php-cs-fixer
	vendor/bin/php-cs-fixer fix --dry-run --config=.php-cs-fixer.php --diff -vv --allow-risky=yes --using-cache=no

phpstan: ## Check code style with PHPStan tool
	./bin/linters run phpstan
phpstan-baseline: ## Check code style with PHPStan tool and generate baseline
	./bin/linters generate phpstan
	vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=512M --generate-baseline --allow-empty-baseline -vv

composer-validate: ## Perform  composer.json and composer.lock validity analysis.
	composer validate --no-check-all --no-check-publish --no-check-version
composer-audit: ## Outputs a list of reported security vulnerabilities for the list of packages versions currently installed.
	composer audit
composer-unused: ## Find unused composer dependencies.
	./bin/linters run composer-unused
composer-normalize: ## Normalize composer.json and composer.lock files.
	./bin/linters run composer-normalize
