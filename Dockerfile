FROM php:7.4-cli

RUN apt-get update -yqq \
    && apt-get install -y --no-install-recommends build-essential openssl gnupg2 unixodbc-dev \
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

COPY . /usr/src/myapp
WORKDIR /usr/src/myapp/src
# CMD [ "php", "main.php" ]
CMD [ "./run.sh" ]