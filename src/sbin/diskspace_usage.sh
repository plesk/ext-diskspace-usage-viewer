#!/bin/bash

if [[ -n $1 ]]; then
    cd "$1"
else
    cd /
fi

du -a -d 1 | sed 's#.\/##g' | sort -nr | awk '{size=$1; name=$2; $1=""; cmd="file -b "$0; cmd |& getline type; print size,name,type; close(cmd)}'
