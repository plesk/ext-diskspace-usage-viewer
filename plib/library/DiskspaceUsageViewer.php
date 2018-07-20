<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

class Modules_DiskspaceUsageViewer_DiskspaceUsageViewer
{
    public function __construct()
    {
    }

    public static function formatSize($kb)
    {
        if ($kb > 1048576) {
            return round($kb / 1048576, 1).' GB';
        } else if ($kb > 1024) {
            return round($kb / 1024, 1).' MB';
        } else {
            return round($kb, 1).' KB';
        }
    }

    public static function getParentPath($path)
    {
        if ($path != '/') {
            $path_parts = pathinfo($path);
            return $path_parts['dirname'];
        }
    }

    public function getDiskspaceUsage($path)
    {
        $list = array();
        $result = pm_ApiCli::callSbin('diskspace_usage.sh', [$path]);
        foreach(preg_split("/((\r?\n)|(\r\n?))/", $result['stdout']) as $line) {
            $matches = array();
            preg_match('/([0-9]+)([^0-9]+)/', $line, $matches);
            $folderSize = intval($matches[1]);
            $folderName = trim($matches[2]);

            if (!empty($folderName) && ($folderName != '.')) {
                $list[] = array($folderSize, $folderName);
            }
        }

        return $list;
    }
}
