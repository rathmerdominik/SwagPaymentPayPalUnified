.DEFAULT_GOAL := help

filter := "default"
dirname := $(notdir $(CURDIR))
envprefix := $(shell echo "$(dirname)" | tr A-Z a-z)
envname := $(envprefix)test

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
.PHONY: help

init: composer-install install-hooks install-plugin install-test-environment ## install the plugin with pre commit hook, requirements and the test environment

init-legacy: composer-install install-hooks ## install the plugin in SW 5.2.27 (latest 5.2) with pre commit hook, requirements and the test environment. Requires SwagCookieConsentManager with branch 5.2.11-5.2.27
	php ./../../../bin/console sw:database:setup --steps=drop,create,import,importDemodata --env=$(envname)
	php ./../../../bin/console sw:cache:clear --env=$(envname)
	php ./../../../bin/console sw:database:setup --steps=setupShop --shop-url=http://localhost --env=$(envname)
	php ./../../../bin/console sw:snippets:to:db --include-plugins --env=$(envname)
	php ./../../../bin/console sw:theme:initialize --env=$(envname)
	php ./../../../bin/console sw:firstrunwizard:disable --env=$(envname)
	php ./../../../bin/console sw:admin:create --name="Demo" --email="demo@demo.de" --username="demo" --password="demo" --locale=de_DE -n --env=$(envname)
	touch ./../../../recovery/install/data/install.lock
	php ./../../../bin/console sw:plugin:refresh --env=$(envname)
	php ./../../../bin/console sw:plugin:install SwagCookieConsentManager --activate --env=$(envname)
	php ./../../../bin/console sw:cache:clear --env=$(envname)
	php ./../../../bin/console sw:plugin:install SwagPaymentPayPalUnified --activate --env=$(envname)
	php ./../../../bin/console sw:cache:clear --env=$(envname)

composer-install: ## Install composer requirements
	@echo "Install composer requirements"
	composer install

install-hooks: ## Install pre commit hooks
	@echo "Install pre commit hooks"
	.githooks/install_hooks.sh

install-plugin: .refresh-plugin-list ## Install and activate the plugin
	@echo "Install the plugin"
	./../../../bin/console sw:plugin:install $(dirname) --activate -c

install-test-environment: ## Installs the plugin test environment
	@echo "Install the test environment"
	./psh local:init

run-tests: ## Execute the php unit tests... (You can use the filter parameter "make run-tests filter=yourFilterPhrase")
ifeq ($(filter), "default")
	SHOPWARE_ENV=$(envname) ./../../../vendor/phpunit/phpunit/phpunit --verbose
else
	SHOPWARE_ENV=$(envname) ./../../../vendor/phpunit/phpunit/phpunit --verbose --filter $(filter)
endif

make run-javascript-tests:
	./../../../themes/Frontend/Responsive/node_modules/.bin/jest -c Tests/Javascript/jest.config.js

CS_FIXER_RUN=
fix-cs: ## Run the code style fixer
	./../../../vendor/bin/php-cs-fixer fix -v $(CS_FIXER_RUN)

fix-cs-dry: CS_FIXER_RUN= --dry-run
fix-cs-dry: fix-cs  ## Run the code style fixer in dry mode

check-js-code: check-eslint-frontend check-eslint-backend check-eslint-e2e ## Run esLint

ESLINT_FIX=
check-eslint-frontend:
	./../../../themes/node_modules/eslint/bin/eslint.js --ignore-path .eslintignore -c ./../../../themes/Frontend/.eslintrc.js --global "jQuery,StateManager, PAYPAL, paypal, location" Resources/views/frontend $(ESLINT_FIX)
	./../../../themes/node_modules/eslint/bin/eslint.js --ignore-path .eslintignore -c ./../../../themes/Frontend/.eslintrc.js --global "jQuery" Tests/Javascript $(ESLINT_FIX)

fix-eslint-frontend: ESLINT_FIX= --fix
fix-eslint-frontend: check-eslint-frontend

check-eslint-backend:
	./../../../themes/node_modules/eslint/bin/eslint.js --ignore-path .eslintignore -c ./../../../themes/Backend/.eslintrc.js Resources/views/backend $(ESLINT_FIX)

fix-eslint-backend: ESLINT_FIX= --fix
fix-eslint-backend: check-eslint-backend

fix-eslint-e2e: ESLINT_FIX= --fix
fix-eslint-e2e: check-eslint-e2e

check-eslint-e2e:
	./../../../themes/node_modules/eslint/bin/eslint.js -c ./Tests/E2E/.eslintrc.js ./Tests/E2E/helper/*.mjs ./Tests/E2E/setup/*.mjs ./Tests/E2E/test/*.mjs $(ESLINT_FIX)

phpstan: ## Run PHPStan
	./../../../vendor/bin/phpstan analyse .

phpstan-generate-baseline: ## Run PHPStan and generate a baseline file
	./../../../vendor/bin/phpstan analyse . --generate-baseline

.refresh-plugin-list:
	@echo "Refresh the plugin list"
	./../../../bin/console sw:plugin:refresh
