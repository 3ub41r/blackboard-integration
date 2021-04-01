FROM php:7.4-cli

RUN apt-get update -yqq \
    && apt-get install -y --no-install-recommends build-essential openssl gnupg2 unixodbc-dev zip unzip \
    && sed -i 's,^\(MinProtocol[ ]*=\).*,\1'TLSv1.0',g' /etc/ssl/openssl.cnf \
    && sed -i 's,^\(CipherString[ ]*=\).*,\1'DEFAULT@SECLEVEL=1',g' /etc/ssl/openssl.cnf

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install SQL Server drivers
RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add -
RUN curl https://packages.microsoft.com/config/ubuntu/18.04/prod.list > /etc/apt/sources.list.d/mssql-release.list

RUN apt update
RUN ACCEPT_EULA=Y apt install msodbcsql17 -y
RUN pecl install sqlsrv && pecl install pdo_sqlsrv && docker-php-ext-enable sqlsrv pdo_sqlsrv

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
# Make executable
RUN chmod 0644 /usr/src/myapp/src/run.sh
RUN cd src && composer install

CMD ["bash", "/usr/src/myapp/src/run.sh"]