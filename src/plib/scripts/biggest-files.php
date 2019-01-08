<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Helper;

try {
    Helper::updateBiggestFiles();
} catch (\Exception $e) {
    \pm_Log::err($e->__toString());

    exit(1);
}
