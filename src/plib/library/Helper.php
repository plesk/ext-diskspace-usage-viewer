<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class Helper
{
    private static $systemFiles = [
        // From safe-rm
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

        // Plesk-related
        '/opt/plesk',
        '/opt/psa',
        '/var/www/vhosts/*/httpdocs',
        '/pleskswap',
        '/var/lib/psa/*',
    ];

    public static function cleanPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $segments = explode('/', $path);

        $segments = array_filter($segments, function ($segment) {
            if (in_array($segment, ['', '.', '..'])) {
                return false;
            }

            return true;
        });

        return '/' . implode('/', $segments);
    }

    public static function size(string $path): int
    {
        $args = [$path];

        if (\pm_Session::getClient()->isClient()) {
            $args[] = self::activeDomain()->getSysUserLogin();
            $args[] = dirname($path);
        }

        $size = 0;

        try {
            $result = \pm_ApiCli::callSbin('size.sh', $args, \pm_ApiCli::RESULT_EXCEPTION);
            $output = trim($result['stdout']);
            $pos = strpos($output, "\t");

            if ($pos !== false) {
                $size = (int) substr($output, 0, $pos);
            }
        } catch (\pm_Exception $e) {
            // Exception intentionally silenced
        }

        return $size;
    }

    public static function delete(string $path): void
    {
        if (self::isSystemFile($path)) {
            throw new \pm_Exception('Cannot delete system file: ' . $path);
        }

        $fileManager = self::createFileManager();

        if ($fileManager->isDir($path)) {
            $fileManager->removeDirectory($path);
        } else {
            $fileManager->removeFile($path);
        }
    }

    public static function activeDomain(): \pm_Domain
    {
        $domains = \pm_Session::getCurrentDomains();

        reset($domains);

        $key = key($domains);

        return $domains[$key];
    }

    private static function isSystemFile(string $path): bool
    {
        foreach (self::$systemFiles as $systemFile) {
            if (fnmatch($systemFile, $path)) {
                return true;
            }
        }

        return false;
    }

    private static function createFileManager(): \pm_FileManager
    {
        if (\pm_Session::getClient()->isAdmin()) {
            return new \pm_ServerFileManager;
        }

        return new \pm_FileManager(self::activeDomain()->getId());
    }
}
