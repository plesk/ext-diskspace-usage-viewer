<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer\Task;

use PleskExt\DiskspaceUsageViewer\Files;

class UpdateFiles extends \pm_LongTask_Task
{
    public $poolSize = 1;

    public function run()
    {
        Files::update();
    }

    public function statusMessage()
    {
        switch ($this->getStatus()) {
            case self::STATUS_RUNNING:
                return \pm_Locale::lmsg('home.task.running');
            case self::STATUS_DONE:
                return \pm_Locale::lmsg('home.task.done');
        }

        return '';
    }
}
