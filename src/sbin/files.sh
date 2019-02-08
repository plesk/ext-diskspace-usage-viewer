#!/bin/bash

find / -type f -print0 | xargs -0 du | sort -n | tail -50 | cut -f2 | xargs -I{} du -bs {}
