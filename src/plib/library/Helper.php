<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class Helper
{
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

    public static function getParentPath($path)
    {
        if ($path != '/') {
            return pathinfo($path, PATHINFO_DIRNAME);
        }
    }

    public static function getDiskspaceUsage($path)
    {
        $list = [];
        $result = \pm_ApiCli::callSbin('diskspace_usage.sh', [$path]);

        foreach(preg_split("/((\r?\n)|(\r\n?))/", $result['stdout']) as $line) {
            $matches = [];

            preg_match('/([0-9]+)([^0-9]+)/', $line, $matches);

            $folderSize = intval($matches[1]);
            $folderName = trim($matches[2]);

            if (!empty($folderName) && ($folderName != '.')) {
                $list[] = [$folderSize, $folderName];
            }
        }

        return $list;
    }
}
