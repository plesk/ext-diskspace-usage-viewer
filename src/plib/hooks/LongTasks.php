<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Task\Scan;

class Modules_DiskspaceUsageViewer_LongTasks extends pm_Hook_LongTasks
{
    public function getLongTasks()
    {
        return [new Scan];
    }
}
