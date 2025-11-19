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
	vendor/bin/phpcbf -p
syntax: ##  Check code style with php code sniffer tool
	vendor/bin/phpcs -p --error-severity=1 --warning-severity=8

rector: ## Fix code style with Rector tool
	vendor/bin/rector process --clear-cache
rector-dry-run: ## Check code style with Rector tool
	vendor/bin/rector process --clear-cache --dry-run

php-cs-fixer: ## Fix code style with PHP-CS-Fixer tool
	vendor/bin/php-cs-fixer fix --config=.php_cs.dist.php --allow-risky=yes --using-cache=no
php-cs-fixer-check: ## Check code style with PHP-CS-Fixer tool
	vendor/bin/php-cs-fixer fix --dry-run --config=.php_cs.dist.php --diff -vv --allow-risky=yes --using-cache=no

phpstan: ## Check code style with PHPStan tool
	vendor/bin/phpstan analyse --memory-limit=512M
phpstan-baseline: ## Check code style with PHPStan tool and generate baseline
	vendor/bin/phpstan analyse --memory-limit=512M --generate-baseline --allow-empty-baseline -vv

composer-validate: ## Perform  composer.json and composer.lock validity analysis.
	composer validate --no-check-all --no-check-publish
composer-audit: ## Outputs a list of reported security vulnerabilities for the list of packages versions currently installed.
	composer audit
