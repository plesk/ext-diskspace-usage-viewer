#!/usr/bin/env bash

find / -maxdepth 7 -path /proc -prune -o -path /sys -prune -o -type f -size +50M -printf '%s\t%Tc\t%p\n' | sort -n | tail -n 50
