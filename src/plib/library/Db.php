<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class Db
{
    public static function path(): string
    {
        return \pm_Context::getVarDir() . \pm_Context::getModuleId() . '.sqlite3';
    }

    public static function adapter(): \Zend_Db_Adapter_Pdo_Sqlite
    {
        static $adapter = null;

        if ($adapter === null) {
            $adapter = new \Zend_Db_Adapter_Pdo_Sqlite([
                'dbname' => static::path(),
            ]);

            $adapter->getConnection()->exec('PRAGMA foreign_keys = ON');
        }

        return $adapter;
    }

    public static function saveCache(string $path, int $size): void
    {
        $stmt = self::adapter()->prepare('REPLACE INTO `cache` (`hash`, `size`, `expires`) VALUES (:hash, :size, :expires)');

        $stmt->execute([
            'hash' => sha1($path),
            'size' => $size,
            'expires' => time() + 3600,
        ]);
    }

    public static function loadCache(string $path): int
    {
        $stmt = self::adapter()->prepare('SELECT `size` FROM `cache` WHERE `hash` = :hash AND `expires` >= :expires');

        $stmt->execute([
            'hash' => sha1($path),
            'expires' => time(),
        ]);

        $row = $stmt->fetch();

        if ($row === false) {
            return 0;
        }

        return (int) $row['size'];
    }
}
