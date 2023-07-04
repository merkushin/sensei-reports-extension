all: prefix archive 

.PHONY: prefix
prefix:
	composer install --no-dev
	vendor/bin/php-scoper add-prefix
	cd build && composer dump-autoload

.PHONY: archive
archive:
	mv build sensei-reports-extension
	zip -r sensei-reports-extension.zip sensei-reports-extension
	rm -rf sensei-reports-extension
