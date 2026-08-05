fix-syntax-completely: ## Fix code style by all code style instruments
	$(MAKE) syntax-fix
	$(MAKE) rector
	$(MAKE) php-cs-fixer
	$(MAKE) syntax
	$(MAKE) rector-dry-run
	$(MAKE) php-cs-fixer-check
	$(MAKE) phpmd
	$(MAKE) phpstan
	$(MAKE) composer-validate
	$(MAKE) composer-audit
	$(MAKE) composer-normalize
	$(MAKE) composer-unused
	$(MAKE) test-unit

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
	./bin/linters run php-cs-fixer
php-cs-fixer-check: ## Check code style with PHP-CS-Fixer (dry-run)
	./bin/linters run php-cs-fixer -- --dry-run --diff

phpstan: ## Static analysis with PHPStan
	./bin/linters run phpstan
phpstan-baseline: ## Generate PHPStan baseline
	./bin/linters run phpstan -- --generate-baseline --allow-empty-baseline

phpmd: ## Detect code quality issues with PHPMD
	./bin/linters run phpmd

composer-validate: ## Validate composer.json
	composer validate --strict
composer-audit: ## Check security vulnerabilities
	composer audit
composer-unused: ## Find unused composer dependencies
	./bin/linters run composer-unused
composer-normalize: ## Normalize composer.json
	./bin/linters run composer-normalize
