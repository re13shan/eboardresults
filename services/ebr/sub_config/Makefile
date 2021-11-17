all: sub_config.php

.PHONY: sub_config.php

sub_config.php:
	cp -a /vh/g/nixtec/vhosts/eboardresults.com/www/app/stud/*.json ./
	php -f gen_sub_config.php > $@

