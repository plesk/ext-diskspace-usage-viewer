<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Db;

try {
    $file = Db::path();

    if (!is_file($file)) {
        touch($file);
        chmod($file, 0600);

        Db::adapter()->query('CREATE TABLE `cache` (`id` INTEGER PRIMARY KEY AUTOINCREMENT, `hash` TINYTEXT NOT NULL, `size` INTEGER NOT NULL, `expires` INTEGER NOT NULL)');
        Db::adapter()->query('CREATE UNIQUE INDEX `idx_hash` ON `cache` (`hash`)');
    }
} catch (\Exception $e) {
    \pm_Log::err($e->__toString());

    exit(1);
}
