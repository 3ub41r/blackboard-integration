#!/usr/bin/env bash

UPSTREAM=${1:-'@{u}'}
LOCAL=$(git rev-parse @)
REMOTE=$(git rev-parse "$UPSTREAM")
BASE=$(git merge-base @ "$UPSTREAM")

if [ $LOCAL = $BASE ]; then
    echo "Pulling update..."
    git pull origin master
    docker build -t blackboard .
fi