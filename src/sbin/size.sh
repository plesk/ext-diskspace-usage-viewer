#!/bin/bash -e

if [ ! -z "$3" ]
then
    plesk sbin filemng "$2" exec "$3" "$0" "$1"
    exit $?
fi

du -ls "$1"
