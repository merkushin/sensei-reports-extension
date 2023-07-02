all: prefix archive 

.PHONY: prefix
prefix:
	composer install
	vendor/bin/php-scoper add-prefix

.PHONY: archive
archive:
	mv build sensei-reports-extension
	zip -r sensei-reports-extension.zip sensei-reports-extension
	rm -rf sensei-reports-extension
