FROM centos:7 as php-builder

RUN yum install -y git gcc gcc-c++ libxml2-devel pkgconfig openssl-devel bzip2-devel curl-devel libpng-devel libjpeg-devel libXpm-devel freetype-devel gmp-devel libmcrypt-devel mariadb-devel aspell-devel recode-devel autoconf bison re2c libicu-devel libxslt-devel libxslt sqlite-devel oniguruma-devel make
RUN cd / && curl -O -L https://github.com/php/php-src/archive/refs/tags/php-7.4.26.tar.gz && tar -xvf php-7.4.26.tar.gz
RUN cd /php-src-php-7.4.26 && \
    ./buildconf --force && \
    ./configure --prefix=/etc/php --with-config-file-path=/etc/php/etc --with-config-file-scan-dir=/etc/php/etc/conf.d --enable-bcmath --with-bz2 --with-curl --enable-filter --enable-fpm --enable-intl --enable-mbstring --enable-mysqlnd --with-mysql-sock=/var/lib/mysql/mysql.sock --with-mysqli=mysqlnd --with-pdo-mysql=mysqlnd --with-pdo-sqlite --disable-phpdbg --disable-phpdbg-webhelper --enable-opcache --with-openssl --enable-simplexml --with-sqlite3 --enable-xmlreader --enable-xmlwriter --enable-soap --with-xsl && \
    make && make install

FROM steamcmd/steamcmd:centos-7 as builder
FROM centos:7

# Define configuration parameters
ENV TIMEZONE="America/New_York" \
    TELNET_PORT="8081" \
    INSTALL_DIR=/data/7DTD
#ARG TELNET_PW
#ENV TELNET_PW=$TELNET_PW

VOLUME ["/data"]

RUN yum install -y sqlite-devel

# Copy Supervisor Config Creator
COPY files/gen_sup.sh /
COPY files/epel.repo /etc/yum.repos.d/
#COPY files/remi-safe.repo /etc/yum.repos.d/

# Copy ServerMod Manager Files into Image
RUN yum -y install git && \
    yum clean all && rm -rf /tmp/* && rm -rf /var/tmp/* && \
    cd / && git clone https://github.com/XelaNull/docker-7dtd.git && \
    ln -s /docker-7dtd/7dtd-servermod/files/7dtd-daemon.sh && \
    ln -s /docker-7dtd/7dtd-servermod/files/7dtd-sendcmd.php && \
    ln -s /docker-7dtd/7dtd-servermod/files/7dtd-sendcmd.sh && \
    ln -s /docker-7dtd/7dtd-servermod/files/7dtd-upgrade.sh && \
    ln -s /docker-7dtd/7dtd-servermod/files/servermod-cntrl.php && \
    ln -s /docker-7dtd/7dtd-servermod/files/start_7dtd.sh && \
    ln -s /docker-7dtd/7dtd-servermod/files/stop_7dtd.sh

# Copy Steam files from builder
COPY --from=builder /usr/lib/games/steam/steamcmd.sh /usr/lib/games/steam/
COPY --from=builder /usr/lib/games/steam/steamcmd /usr/lib/games/steam/
COPY --from=builder /usr/bin/steamcmd /usr/bin/steamcmd

# Set up Steam working directories
RUN mkdir -p ~/.steam/appcache ~/.steam/config ~/.steam/logs ~/.steam/SteamApps/common ~/.steam/steamcmd/linux32 && \
    ln -s ~/.steam ~/.steam/root && \
    ln -s ~/.steam ~/.steam/steam && \
    cp -p /usr/lib/games/steam/steamcmd.sh ~/.steam/steamcmd/ && \
    cp -p /usr/lib/games/steam/steamcmd ~/.steam/steamcmd/linux32/ && \
    chmod a+x ~/.steam/steamcmd/steamcmd.sh && \
    chmod a+x ~/.steam/steamcmd/linux32/steamcmd

# Install base YUM packages required
#RUN yum -y install nginx php81-php-fpm php81-php-cli php81-php-xml && \
#    ln -s /usr/bin/php81 /usr/bin/php && \
#    yum clean all && rm -rf /tmp/* && rm -rf /var/tmp/*
RUN yum -y install glibc.i686 libstdc++.i686 supervisor telnet expect net-tools sysvinit-tools && \
    yum clean all && rm -rf /tmp/* && rm -rf /var/tmp/*

# Install Tools to Extract Mods
#RUN yum -y install svn
RUN yum -y install unzip p7zip p7zip-plugins curl wget && \
    yum clean all && rm -rf /tmp/* && rm -rf /var/tmp/* && \
    wget --no-check-certificate https://www.rarlab.com/rar/rarlinux-x64-5.5.0.tar.gz && \
    tar -zxf rarlinux-*.tar.gz && cp rar/rar rar/unrar /usr/local/bin/ && \
    rm -rf rar* rarlinux-x64-5.5.0.tar.gz

# Deploy the Nginx & FPM Config files
COPY nginx-config/nginx.conf /etc/nginx/nginx.conf
#COPY nginx-config/fpm-pool.conf /etc/opt/remi/php81/php-fpm.d/www.conf
#COPY nginx-config/php.ini /etc/opt/remi/php81/php.d/custom.ini

# Configure Supervisor
RUN printf '[supervisord]\nnodaemon=true\nuser=root\nlogfile=/var/log/supervisord\n' > /etc/supervisord.conf
#RUN /gen_sup.sh php8-fpm "/opt/remi/php81/root/usr/sbin/php-fpm -F" >> /etc/supervisord.conf && \
RUN /gen_sup.sh nginx "nginx -g 'daemon off;'" >> /etc/supervisord.conf && \
    /gen_sup.sh severmod-cntrl "/servermod-cntrl.php $INSTALL_DIR" >> /etc/supervisord.conf && \
    /gen_sup.sh 7dtd-daemon "/7dtd-daemon.sh" >> /etc/supervisord.conf

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

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:80/fpm-ping
