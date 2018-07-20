#!/bin/bash

if [[ -n $1 ]]; then
    cd "$1"
else
    cd /
fi

if [[ -n $2 ]]; then
    du -d 1 | sed 's#.\/##g' | sort -n -r | head -n $2
else
    du -d 1 | sed 's#.\/##g' | sort -n -r
fi
