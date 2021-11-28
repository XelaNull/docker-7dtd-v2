#!bin/bash

yum install -y git gcc gcc-c++ libxml2-devel pkgconfig openssl-devel bzip2-devel curl-devel libpng-devel libjpeg-devel libXpm-devel freetype-devel gmp-devel libmcrypt-devel mariadb-devel aspell-devel recode-devel autoconf bison re2c libicu-devel libxslt-devel libxslt sqlite-devel oniguruma-devel make

curl -O -L https://github.com/php/php-src/archive/refs/tags/php-7.4.26.tar.gz
tar -xvf php-7.4.26.tar.gz
cd php-src-php-7.4.26
./buildconf --force
./configure --prefix=/etc/php --with-config-file-path=/etc/php/etc --with-config-file-scan-dir=/etc/php/etc/conf.d --enable-bcmath --with-bz2 --with-curl --enable-filter --enable-fpm --enable-intl --enable-mbstring --enable-mysqlnd --with-mysql-sock=/var/lib/mysql/mysql.sock --with-mysqli=mysqlnd --with-pdo-mysql=mysqlnd --with-pdo-sqlite --disable-phpdbg --disable-phpdbg-webhelper --enable-opcache --with-openssl --enable-simplexml --with-sqlite3 --enable-xmlreader --enable-xmlwriter --enable-soap --with-xsl
