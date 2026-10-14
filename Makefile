PLUGIN    := reports-graph-for-sensei-lms
BUILD_DIR := build/$(PLUGIN)
ZIP       := $(PLUGIN).zip
WP        ?= $(if $(wildcard vendor/bin/wp),vendor/bin/wp,wp)

POT_ARGS := --slug=$(PLUGIN) --domain=$(PLUGIN) --include=$(PLUGIN).php,uninstall.php,src,assets/src \
	--exclude=build,vendor,node_modules,tests,assets/build

# Files and directories that ship in the plugin zip, besides assets/build.
DIST_FILES := $(PLUGIN).php uninstall.php readme.txt src LICENSE

.PHONY: all install build-js test test-js lint lint-php lint-js phpstan check version-check \
	i18n i18n-check dist smoke plugin-check playground clean

all: dist

install:
	composer install
	npm ci

build-js:
	npm run build

test:
	vendor/bin/phpunit

test-js:
	npm test

lint: lint-php lint-js

# WordPress Coding Standards and PHP compatibility, see phpcs.xml.dist.
lint-php:
	vendor/bin/phpcs

lint-js:
	npm run lint

phpstan:
	composer phpstan

# Everything CI checks on the sources. Run before every commit.
check: lint phpstan test test-js i18n-check version-check

# The version must match in the plugin header, readme.txt (Stable tag), Plugin::VERSION and
# package.json. With TAG=v1.2.3 (or 1.2.3) it must also match the release tag.
version-check:
	@version=$$(sed -n 's/^ \* Version: *//p' $(PLUGIN).php); \
	for other in \
		"readme.txt:$$(sed -n 's/^Stable tag: *//p' readme.txt)" \
		"src/Plugin.php:$$(sed -n "s/.*const VERSION = '\(.*\)';/\1/p" src/Plugin.php)" \
		"package.json:$$(php -r 'echo json_decode(file_get_contents("package.json"))->version;')"; do \
		if [ "$${other#*:}" != "$$version" ]; then echo "$${other%%:*} has version $${other#*:}, $(PLUGIN).php has $$version"; exit 1; fi; \
	done; \
	if [ -n "$(TAG)" ] && [ "$(patsubst v%,%,$(TAG))" != "$$version" ]; then echo "Tag $(TAG) does not match version $$version"; exit 1; fi; \
	echo "Version $$version"

# Regenerates languages/$(PLUGIN).pot from PHP and JavaScript sources. Run after changing
# translatable strings. WordPress.org plugins are translated on translate.wordpress.org.
i18n:
	$(WP) i18n make-pot . languages/$(PLUGIN).pot $(POT_ARGS)

# Fails when languages/$(PLUGIN).pot is out of date with the sources (ignores the dates).
i18n-check:
	@mkdir -p build
	@$(WP) i18n make-pot . build/$(PLUGIN).pot $(POT_ARGS) --quiet
	@grep -v -e '^"POT-Creation-Date:' -e '^# Copyright' languages/$(PLUGIN).pot > build/expected.pot
	@grep -v -e '^"POT-Creation-Date:' -e '^# Copyright' build/$(PLUGIN).pot > build/actual.pot
	@diff -u build/expected.pot build/actual.pot || (echo "languages/$(PLUGIN).pot is out of date, run 'make i18n'." && exit 1)

# Builds the release zip in build/, so the working copy is never modified: copies the plugin
# files and built assets, installs runtime dependencies, keeps only the WPAL services the
# plugin uses, prefixes them with wp-scoper into vendor-prefixed/ (so two plugins bundling
# different WPAL versions don't collide) and drops vendor/ and the Composer files.
dist: clean install build-js
	mkdir -p $(BUILD_DIR)/assets
	cp -R $(DIST_FILES) composer.json composer.lock $(BUILD_DIR)/
	cp -R assets/build $(BUILD_DIR)/assets/
	composer install --working-dir=$(BUILD_DIR) --no-dev --no-plugins --no-scripts --no-autoloader --no-interaction --quiet
	vendor/bin/wpal-prune --scan=$(BUILD_DIR)/src --path=$(BUILD_DIR)/vendor/merkushin/wpal
	vendor/bin/wp-scoper $(BUILD_DIR)
	rm -rf $(BUILD_DIR)/vendor $(BUILD_DIR)/composer.json $(BUILD_DIR)/composer.lock
	cd build && zip -rq ../$(ZIP) $(PLUGIN)

# Loads the release build with stand-ins for WordPress and runs its code paths, so a WPAL
# service removed by wpal-prune or a scoping problem fails here, not on a user's site.
smoke:
	php tests/smoke/dist.php $(BUILD_DIR)

# Runs the official Plugin Check (the checks WordPress.org runs on submissions) on the build
# in a local WordPress with Sensei LMS, which the plugin requires. Needs Docker; CI runs the
# same check without it.
plugin-check:
	npx --yes @wordpress/env start
	npx --yes @wordpress/env run cli wp plugin activate $(PLUGIN)
	npx --yes @wordpress/env run cli wp plugin check $(PLUGIN)

# Starts WordPress in the browser with Sensei LMS and this checkout as an active plugin
# (WordPress Playground, no Docker needed). Run `npm start` alongside to rebuild assets on change.
playground:
	npx --yes @wp-playground/cli@latest server --auto-mount --login --blueprint=blueprint.dev.json

clean:
	rm -rf build assets/build $(ZIP)
