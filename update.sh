#!/usr/bin/env bash

cd /home/administrator/blackboard-integration && git pull origin master && docker build -t blackboard .