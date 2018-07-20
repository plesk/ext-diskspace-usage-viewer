#!/bin/bash

rm ext-diskspace-usage-viewer.zip
zip -r ext-diskspace-usage-viewer.zip *
scp -r plib/* root@jlsoft.de:/usr/local/psa/admin/plib/modules/diskspace-usage-viewer/
scp -r htdocs/* root@jlsoft.de:/usr/local/psa/admin/htdocs/modules/diskspace-usage-viewer/
scp -r sbin/* root@jlsoft.de:/usr/local/psa/admin/sbin/modules/diskspace-usage-viewer/
