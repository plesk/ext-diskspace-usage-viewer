<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

class Modules_DiskspaceUsageViewer_ConfigDefaults extends pm_Hook_ConfigDefaults
{
    public function getDefaults()
    {
        return [
            'defaultDaysToKeepBackups' => 90,
        ];
    }
}
