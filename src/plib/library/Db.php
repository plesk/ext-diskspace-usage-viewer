<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

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
}
