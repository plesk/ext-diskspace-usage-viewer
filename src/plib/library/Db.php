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

    public static function saveFiles(array $files): void
    {
        self::adapter()->exec('DELETE FROM `files`');

        $stmt = self::adapter()->prepare('INSERT INTO `files` (`path`, `size`) VALUES (:path, :size)');

        foreach ($files as $file) {
            $stmt->execute([
                'path' => $file['path'],
                'size' => $file['size'],
            ]);
        }
    }

    public static function getFiles(): array
    {
        $sql = <<<SQL
SELECT *
FROM `files`
ORDER BY `size` DESC
SQL;

        $files = [];

        foreach (Db::adapter()->fetchAssoc($sql) as $row) {
            $files[$row['id']] = $row;
        }

        return $files;
    }

    public static function deleteFileById(int $id): void
    {
        $stmt = self::adapter()->prepare('DELETE FROM `files` WHERE `id` = :id');

        $stmt->execute([
            'id' => $id,
        ]);
    }
}
