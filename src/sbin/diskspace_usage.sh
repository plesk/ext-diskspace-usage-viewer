#!/bin/bash

if [[ -n $1 ]]; then
    cd "$1"
else
    cd /
fi

du -d 1 | sed 's#.\/##g' | sort -n -r
