FROM centos:centos7 as phpbuilder

# 850MB before build consolidation
# 666MB copying everything
# 673MB copied directly in

COPY files/epel.repo /etc/yum.repos.d/

# Install packages needed to manually compile PHP-FPM with XML, SOAP, and Sqlite3 support
RUN yum install -y gcc gcc-c++ libxml2-devel pkgconfig openssl-devel bzip2-devel curl-devel libpng-devel libjpeg-devel \
    libXpm-devel freetype-devel gmp-devel libmcrypt-devel aspell-devel recode-devel autoconf bison re2c \
    libicu-devel libxslt-devel libxslt sqlite-devel oniguruma-devel make && \
    yum clean all && rm -rf /tmp/* && rm -rf /var/tmp/* && rm -rf /var/log/*
RUN cd / && curl -O -L https://github.com/php/php-src/archive/refs/tags/php-7.4.26.tar.gz && tar -xvf php-7.4.26.tar.gz
RUN cd /php-src-php-7.4.26 && \
    ./buildconf --force && \
    ./configure --prefix=/etc/php --with-config-file-path=/etc/php/etc --with-config-file-scan-dir=/etc/php/etc/conf.d \
    --enable-bcmath --with-bz2 --with-curl --enable-filter --enable-fpm --enable-intl --enable-mbstring --with-pdo-sqlite \
    --disable-phpdbg --disable-phpdbg-webhelper --enable-opcache --with-openssl --enable-simplexml --with-sqlite3 \
    --enable-xmlreader --enable-xmlwriter --enable-soap --with-xsl && \
    make && make install

# Install SMM & Steam depdendencies
RUN yum -y install nginx glibc.i686 libstdc++.i686 supervisor telnet expect net-tools sysvinit-tools \
    unzip p7zip p7zip-plugins curl wget git && \
    yum clean all && rm -rf /tmp/* && rm -rf /var/tmp/* && rm -rf /var/log/*
RUN wget --no-check-certificate https://www.rarlab.com/rar/rarlinux-x64-5.5.0.tar.gz && \
    tar -zxf rarlinux-*.tar.gz && cp rar/rar rar/unrar /usr/local/bin/

RUN cd / && git clone https://github.com/XelaNull/docker-7dtd-v2.git

# Remove compilation
RUN yum remove -y gcc gcc-c++ autoconf && \
    yum clean all && rm -rf /tmp/* && rm -rf /var/tmp/*

###########################################
###########################################
# Now we can copy the stuff we need from the above image

FROM steamcmd/steamcmd:centos-7 as steambuilder
FROM centos:7

# Define configuration parameters
ENV TIMEZONE="America/New_York" \
    TELNET_PORT="8081" \
    INSTALL_DIR=/data/7DTD
#ARG TELNET_PW
#ENV TELNET_PW=$TELNET_PW

VOLUME ["/data"]

# Copy Supervisor Config Creator
COPY files/gen_sup.sh /

# Copy ServerMod Manager Files into Image
#RUN ln -s /docker-7dtd/7dtd-servermod/files/7dtd-daemon.sh && \
#    ln -s /docker-7dtd/7dtd-servermod/files/7dtd-sendcmd.php && \
#    ln -s /docker-7dtd/7dtd-servermod/files/7dtd-sendcmd.sh && \
#    ln -s /docker-7dtd/7dtd-servermod/files/7dtd-upgrade.sh && \
#    ln -s /docker-7dtd/7dtd-servermod/files/servermod-cntrl.php && \
#    ln -s /docker-7dtd/7dtd-servermod/files/start_7dtd.sh && \
#    ln -s /docker-7dtd/7dtd-servermod/files/stop_7dtd.sh

# Copy Steam files from builder
COPY --from=steambuilder /usr/lib/games/steam/steamcmd.sh /usr/lib/games/steam/
COPY --from=steambuilder /usr/lib/games/steam/steamcmd /usr/lib/games/steam/
COPY --from=steambuilder /usr/bin/steamcmd /usr/bin/steamcmd

COPY --from=phpbuilder /docker-7dtd-v2 /docker-7dtd-v2

#COPY --from=phpbuilder /etc/php /etc/php
COPY --from=phpbuilder /usr/share/mime/application/x-php.xml /usr/share/mime/application/
RUN echo 'pathmunge /etc/php/bin' > /etc/profile.d/php.sh
RUN mkdir -p /etc/php/etc/conf.d && \
    echo 'zend_extension=opcache.so' >> /etc/php/etc/conf.d/modules.ini && \
    ln -s /etc/php/bin/php /usr/bin/php

COPY --from=phpbuilder /php-src-php-7.4.26/php.ini-development /etc/php/lib/php.ini
COPY nginx-config/fpm-pool.conf /etc/php/etc/php-fpm.d/www.conf
#COPY --from=phpbuilder /php-src-php-7.4.26/sapi/fpm/www.conf /etc/php/etc/php-fpm.d/www.conf
COPY --from=phpbuilder /php-src-php-7.4.26/sapi/fpm/php-fpm.conf /etc/php/etc/php-fpm.conf
RUN ln -s /etc/php/sbin/php-fpm /usr/sbin/php-fpm && mkdir /run/php-fpm


COPY --from=phpbuilder /usr/bin /usr/bin
#COPY --from=phpbuilder /usr/etc /usr/etc
#COPY --from=phpbuilder /usr/games /usr/games
COPY --from=phpbuilder /usr/include /usr/include
COPY --from=phpbuilder /usr/lib /usr/lib
COPY --from=phpbuilder /usr/lib64 /usr/lib64
#COPY --from=phpbuilder /usr/libexec /usr/libexec
#COPY --from=phpbuilder /usr/local /usr/local
COPY --from=phpbuilder /usr/sbin /usr/sbin
#COPY --from=phpbuilder /usr/share /usr/share
#COPY --from=phpbuilder /usr/src /usr/src

#COPY --from=phpbuilder /usr /usr

COPY --from=phpbuilder /etc /etc

# Set up Steam working directories
RUN mkdir -p ~/.steam/appcache ~/.steam/config ~/.steam/logs ~/.steam/SteamApps/common ~/.steam/steamcmd/linux32 && \
    ln -s ~/.steam ~/.steam/root && \
    ln -s ~/.steam ~/.steam/steam && \
    cp -p /usr/lib/games/steam/steamcmd.sh ~/.steam/steamcmd/ && \
    cp -p /usr/lib/games/steam/steamcmd ~/.steam/steamcmd/linux32/ && \
    chmod a+x ~/.steam/steamcmd/steamcmd.sh && \
    chmod a+x ~/.steam/steamcmd/linux32/steamcmd

# Deploy the Nginx & FPM Config files
COPY nginx-config/nginx.conf /etc/nginx/nginx.conf
COPY nginx-config/php.ini /etc/php.d/custom.ini

# Configure Supervisor
RUN printf '[supervisord]\nnodaemon=true\nuser=root\nlogfile=/var/log/supervisord\n' > /etc/supervisord.conf
RUN chmod a+x /gen_sup.sh && \
    /gen_sup.sh php-fpm "/etc/php/sbin/php-fpm -F" >> /etc/supervisord.conf && \
    /gen_sup.sh nginx "nginx -g 'daemon off;'" >> /etc/supervisord.conf && \
    /gen_sup.sh smm-daemon "/docker-7dtd/7dtd-servermod/smm-daemon.php $INSTALL_DIR" >> /etc/supervisord.conf && \

# ServerMod Manager
EXPOSE 80/tcp
EXPOSE 8080/tcp
# 7DTD Telnet Port
EXPOSE 8081/tcp
EXPOSE 8082/tcp
# 7DTD Gameports
EXPOSE 26900/tcp
EXPOSE 26900/udp
EXPOSE 26901/udp
EXPOSE 26902/udp

WORKDIR ["/data"]

# Set to start the supervisor daemon on bootup
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
#CMD ["/bin/bash"]

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:80/fpm-ping
