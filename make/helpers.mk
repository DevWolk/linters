fix-syntax-completely: ## Fix code style by all code style instruments
	-@make syntax-fix
	-@make rector
	-@make php-cs-fixer
	-@make phpstan
	-@make composer-validate
	-@make composer-audit
	-@make composer-normalize
	#-@make composer-unused
	-@make test-unit

test-unit: ## Run unit tests
	vendor/bin/phpunit -d memory_limit=512M --order-by=random --colors=always

syntax: ## Check code style with PHPCS
	./bin/linters run phpcs

syntax-fix: ## Fix code style with PHPCBF
	./bin/linters run phpcbf

rector: ## Fix code style with Rector
	./bin/linters run rector
rector-dry-run: ## Check code style with Rector (dry-run)
	./bin/linters run rector -- --dry-run

php-cs-fixer: ## Fix code style with PHP-CS-Fixer
	./bin/linters run php-cs-fixer -- --allow-unsupported-php-version=yes
php-cs-fixer-check: ## Check code style with PHP-CS-Fixer (dry-run)
	./bin/linters run php-cs-fixer -- --allow-unsupported-php-version=yes --dry-run --diff

phpstan: ## Static analysis with PHPStan
	./bin/linters run phpstan
phpstan-baseline: ## Generate PHPStan baseline
	./bin/linters run phpstan -- --generate-baseline --allow-empty-baseline

composer-validate: ## Validate composer.json
	composer validate --no-check-all --no-check-publish --no-check-version
composer-audit: ## Check security vulnerabilities
	composer audit
composer-unused: ## Find unused composer dependencies
	./bin/linters run composer-unused
composer-normalize: ## Normalize composer.json
	./bin/linters run composer-normalize
