<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

use PleskExt\DiskspaceUsageViewer\Task\Scan;

class Helper
{
    const CACHE_LIFETIME = 3600;

    private static $systemFiles = [
        '/',
        '/bin',
        '/boot',
        '/dev',
        '/etc',
        '/home',
        '/initrd',
        '/lib',
        '/lib32',
        '/lib64',
        '/proc',
        '/root',
        '/sbin',
        '/sys',
        '/usr',
        '/usr/bin',
        '/usr/include',
        '/usr/lib',
        '/usr/local',
        '/usr/local/bin',
        '/usr/local/include',
        '/usr/local/sbin',
        '/usr/local/share',
        '/usr/sbin',
        '/usr/share',
        '/usr/src',
        '/var',

        '/opt/plesk',
        '/opt/psa',
        '/var/www/vhosts/*/httpdocs',
    ];

    public static function formatSize($kb)
    {
        if ($kb > 1048576) {
            return round($kb / 1048576, 1) . ' GB';
        } else if ($kb > 1024) {
            return round($kb / 1024, 1) . ' MB';
        } else {
            return round($kb, 1) . ' KB';
        }
    }

    public static function cleanPath($path)
    {
        $path = str_replace('\\', '/', $path);
        $segments = explode('/', $path);

        $segments = array_filter($segments, function ($segment) {
            if (in_array($segment, ['', '.', '..'])) {
                return false;
            }

            return true;
        });

        return '/' . implode('/', $segments);
    }

    public static function getCacheFile($path)
    {
        $cacheDir = \pm_Context::getVarDir() . 'cache';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0700, true);
        }

        return $cacheDir . DIRECTORY_SEPARATOR . sha1($path) . '.json';
    }

    public static function getTaskIdFile($path)
    {
        return self::getCacheFile($path) . '.task';
    }

    public static function getDiskspaceUsage($path)
    {
        $cacheFile = self::getCacheFile($path);

        if (!is_file($cacheFile)) {
            return [];
        }

        return (array) json_decode(file_get_contents($cacheFile), true);
    }

    public static function needUpdateCache($path)
    {
        if (is_file(self::getTaskIdFile($path))) {
            return false;
        }

        $cacheFile = self::getCacheFile($path);

        if (!is_file($cacheFile)) {
            return true;
        }

        $lastModified = filemtime($cacheFile);

        if ((time() - $lastModified) >= self::CACHE_LIFETIME) {
            return true;
        }

        return false;
    }

    public static function startTask($path)
    {
        $taskIdFile = self::getTaskIdFile($path);

        if (is_file($taskIdFile)) {
            return null;
        }

        $taskManager = new \pm_LongTask_Manager;
        $task = new Scan;
        $isAdmin = \pm_Session::getClient()->isAdmin();

        $task->setParam('isAdmin', $isAdmin);

        if (!$isAdmin) {
            $task->setParam('username', \pm_Session::getCurrentDomain()->getSysUserLogin());
            $task->setParam('domainId', \pm_Session::getCurrentDomain()->getId());
        }

        $task->setParam('path', $path);
        $task->setParam('redirect', self::getActionUrl('index', ['path' => $path]));

        $taskManager->start($task);

        file_put_contents($taskIdFile, $task->getInstanceId());

        return $task;
    }

    public static function getRunningTask($path)
    {
        $taskIdFile = self::getTaskIdFile($path);

        if (!is_file($taskIdFile)) {
            return null;
        }

        $instanceId = file_get_contents($taskIdFile);
        $taskManager = new \pm_LongTask_Manager;
        $tasks = $taskManager->getTasks(['task\scan']);

        foreach ($tasks as $task) {
            if ($task->getInstanceId() == $instanceId) {
                return $task;
            }
        }
    }

    public static function getActionUrl($action, array $params = [])
    {
        $url = \pm_Context::getActionUrl('index', $action);

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isDir($path, \pm_FileManager $fileManager)
    {
        if (method_exists($fileManager, 'isDir')) {
            return $fileManager->isDir($path);
        } else {
            return $fileManager->fileExists($path . '/');
        }
    }

    /**
     * @param string $path
     * @param int $maxLen
     * @return string
     */
    public static function truncatePath($path, $maxLen)
    {
        if (mb_strlen($path) < $maxLen) {
            return $path;
        }

        return '...' . mb_substr($path, -$maxLen);
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function isSystemFile($path)
    {
        foreach (self::$systemFiles as $systemFile) {
            if (fnmatch($systemFile, $path)) {
                return true;
            }
        }

        return false;
    }
}
