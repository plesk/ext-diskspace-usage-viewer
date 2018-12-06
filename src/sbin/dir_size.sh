#!/bin/bash -e

if [ ! -z "$2" ]
then
    plesk sbin filemng "$2" exec "$1" "$0" "$1"
    exit $?
fi

du -bls "$1"
