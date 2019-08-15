<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Installer;

try {
    $installType = $_SERVER['argv'][1] ?? 'install';
    $fromVersion = ($installType === 'install') ? '0.0.0' : $_SERVER['argv'][2];

    Installer::upgrade($fromVersion);
} catch (Exception $e) {
    pm_Log::err($e);

    exit(1);
}
