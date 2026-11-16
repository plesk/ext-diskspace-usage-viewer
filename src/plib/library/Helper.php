<?php
// Copyright 1999-2026. WebPros International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class Helper
{
    private const POWER = 1024;

    // Each directory and all of its contents are protected.
    private const PROTECTED_TREES = [
        '/bin',
        '/boot',
        '/dev',
        '/etc',
        '/initrd',
        '/lib',
        '/lib32',
        '/lib64',
        '/proc',
        '/root',
        '/sbin',
        '/sys',
        '/usr',
        '/opt/plesk',
        '/opt/psa',
        '/var/lib/psa',
    ];

    // Only the path itself is protected; its contents can be deleted.
    private const PROTECTED_PATHS = [
        '/',
        '/home',
        '/var',
        '/var/www/vhosts/*/httpdocs',
        '/pleskswap',
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

    // The name is used as it is: the file manager gives raw names, and "+" or "%" can be part of a name.
    public static function childPath(string $dir, string $name): string
    {
        return rtrim($dir, '/') . '/' . $name;
    }

    public static function size(string $path): int
    {
        $args = [$path];

        if (\pm_Session::getClient()->isClient()) {
            $domain = self::requireActiveDomain();
            $args[] = $domain->getSysUserLogin();
            $args[] = $domain->getHomePath();
        }

        $kiloBytes = 0;

        try {
            $result = \pm_ApiCli::callSbin('size.sh', $args, \pm_ApiCli::RESULT_EXCEPTION);
            $output = trim($result['stdout']);
            $pos = strpos($output, "\t");

            if ($pos !== false) {
                $kiloBytes = (int) substr($output, 0, $pos);
            }
        } catch (\pm_Exception $e) {
            // Exception intentionally silenced
        }

        $bytes = $kiloBytes * self::POWER;

        return $bytes;
    }

    public static function delete(string $path): void
    {
        // The check and the delete must get the same path.
        $path = self::cleanPath($path);

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

    public static function activeDomain(): ?\pm_Domain
    {
        $domains = \pm_Session::getCurrentDomains();

        reset($domains);

        $key = key($domains);

        return $domains[$key] ?? null;
    }

    public static function requireActiveDomain(): \pm_Domain
    {
        $domain = self::activeDomain();

        if (!$domain) {
            throw new \pm_Exception('Permission denied');
        }

        return $domain;
    }

    private static function isSystemFile(string $path): bool
    {
        foreach (self::pathVariants($path) as $variant) {
            if (self::isProtected($variant)) {
                return true;
            }
        }

        return false;
    }

    // The given path, and the same path with symbolic links in its parent directories resolved.
    // The last element is not resolved: the file manager removes a link, not its target.
    private static function pathVariants(string $path): array
    {
        $path = self::cleanPath($path);
        $variants = [$path];

        $resolvedParent = realpath(dirname($path));
        if ($resolvedParent !== false) {
            $variants[] = self::cleanPath($resolvedParent . '/' . basename($path));
        }

        return array_unique($variants);
    }

    private static function isProtected(string $path): bool
    {
        foreach (self::PROTECTED_TREES as $tree) {
            if ($path === $tree || strpos($path, $tree . '/') === 0) {
                return true;
            }
        }

        foreach (self::PROTECTED_PATHS as $pattern) {
            if (fnmatch($pattern, $path)) {
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

        return new \pm_FileManager(self::requireActiveDomain()->getId());
    }
}
