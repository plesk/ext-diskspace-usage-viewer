#!/bin/bash -e

if [ ! -z "$2" ]
then
    plesk sbin filemng "$2" exec "$1" "$0" "$1"
    exit $?
fi

cd "$1"

du -a -d 1 | sed 's#.\/##g' | sort -nr | awk '{size=$1; name=$2; $1=""; is_dir="test -d "$0; is_dir |& getline; print size,name,close(is_dir)}'
