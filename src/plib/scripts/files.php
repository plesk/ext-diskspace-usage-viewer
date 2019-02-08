<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Files;

try {
    Files::update();
} catch (\Exception $e) {
    \pm_Log::err($e);

    exit(1);
}
