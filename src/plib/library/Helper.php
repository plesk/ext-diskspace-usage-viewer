<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

use PleskExt\DiskspaceUsageViewer\Task\Scan;

class Helper
{
    const CACHE_LIFETIME = 300;

    public static function formatSize($kb)
    {
        if ($kb > 1048576) {
            return round($kb / 1048576, 1) . '&nbsp;GB';
        } else if ($kb > 1024) {
            return round($kb / 1024, 1) . '&nbsp;MB';
        } else {
            return round($kb, 1) . '&nbsp;KB';
        }
    }

    public static function cleanPath($path)
    {
        $path = str_replace('\\', '/', $path);
        $path = str_replace('../', '', $path);

        return $path;
    }

    public static function getCacheFile($path)
    {
        $cacheDir = \pm_Context::getVarDir() . 'cache';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0700, true);
        }

        return $cacheDir . DIRECTORY_SEPARATOR . sha1($path) . '.json';
    }

    public static function getDiskspaceUsage($path)
    {
        $cacheFile = self::getCacheFile($path);

        if (!is_file($cacheFile)) {
            return [];
        }

        return json_decode(file_get_contents($cacheFile), true);
    }

    public static function needUpdateCache($path)
    {
        $cacheFile = self::getCacheFile($path);

        if (!is_file($cacheFile)) {
            return true;
        }

        $lastModified = filemtime($cacheFile);
        $maxLifetime = time() - self::CACHE_LIFETIME;

        if ((time() - $lastModified) >= self::CACHE_LIFETIME) {
            return true;
        }

        return false;
    }

    public static function startTask($path)
    {
        $taskManager = new \pm_LongTask_Manager;
        $task = new Scan;

        $task->setParam('path', $path);
        $task->setParam('redirect', \pm_Context::getActionUrl('index', 'index?path=' . rawurlencode($path)));

        $taskManager->start($task);

        return $task;
    }
}
