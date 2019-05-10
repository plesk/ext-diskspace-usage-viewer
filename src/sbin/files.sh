#!/usr/bin/env bash

find / -path /proc -prune -o -path /sys -prune -o -type f -size +512k -printf '%s\t%p\n' | sort -n | tail -n 50
