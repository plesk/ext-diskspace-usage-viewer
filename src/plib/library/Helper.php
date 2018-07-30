<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class Helper
{
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

    public static function getDiskspaceUsage($path)
    {
        $client = \pm_Session::getClient();

        if ($client->isAdmin()) {
            $result = \pm_ApiCli::callSbin('diskspace_usage.sh', [$path]);
        } else {
            $username = \pm_Session::getCurrentDomain()->getSysUserLogin();
            $result = \pm_ApiCli::callSbin('diskspace_usage.sh', [$path, $username]);
        }

        $lines = explode("\n", trim($result['stdout']));
        $list = [];

        foreach ($lines as $line) {
            $arr = explode(' ', $line);
            $size = (int) $arr[0];
            $name = trim($arr[1]);
            $type = (int) $arr[2];

            if ($name == '.') {
                continue;
            }

            $isDir = ($type === 0) ? true : false;

            $list[] = [
                'size' => $size,
                'name' => $name,
                'isDir' => $isDir,
                'displayName' => $isDir ? $name . '/' : $name,
            ];
        }

        return $list;
    }
}
