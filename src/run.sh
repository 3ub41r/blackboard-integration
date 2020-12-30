#!/usr/bin/env bash

rm -rf /usr/src/myapp/data

php main.php

./sis_snpshtFF_auto.sh
./sis_snpshtFF_manual.sh
