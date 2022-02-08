.PHONY: *

## —— Help ————————————————————————————————————
help: ## Show help
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' Makefile | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Tests ———————————————————————————————————
tests: ## Run tests
	rm -rf $(shell php -r "echo sys_get_temp_dir();")/com.github.easycorp.easyadmin/tests/var/test/cache/*
	php vendor/bin/simple-phpunit -v

## —— Linters —————————————————————————————————
linter-code-syntax: ## Lint PHP code (in dry-run mode, does not edit files)
	docker run --rm -it -w=/app -v $(shell pwd):/app oskarstark/php-cs-fixer-ga:latest --diff -vvv --dry-run --using-cache=no
linter-docs: ## Lint docs
	docker run --rm -it -e DOCS_DIR='/docs' -v $(shell pwd)/doc:/docs oskarstark/doctor-rst:latest --short
