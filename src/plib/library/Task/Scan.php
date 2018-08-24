<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer\Task;

use PleskExt\DiskspaceUsageViewer\Helper;

class Scan extends \pm_LongTask_Task
{
    public function run()
    {
        $path = $this->getParam('path');

        if ($this->getParam('isAdmin')) {
            $result = \pm_ApiCli::callSbin('diskspace_usage.sh', [$path]);
        } else {
            $result = \pm_ApiCli::callSbin('diskspace_usage.sh', [$path, $this->getParam('username')]);
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

        file_put_contents(Helper::getCacheFile($path), json_encode($list));
    }

    public function getSteps()
    {
        return [
            [
                'title' => \pm_Locale::lmsg('scanTaskRunning', ['path' => $this->getParam('path')]),
                'icon' => \pm_Context::getBaseUrl() . 'img/sync.png',
            ]
        ];
    }

    public function statusMessage()
    {
        switch ($this->getStatus()) {
            case static::STATUS_RUNNING:
                return \pm_Locale::lmsg('scanTaskRunning', ['path' => $this->getParam('path')]);
            case static::STATUS_DONE:
                return \pm_Locale::lmsg('scanTaskDone', ['path' => $this->getParam('path')]);
        }

        return '';
    }

    public function onError(\Exception $e)
    {
        $this->deleteTaskIdFile();
    }

    public function onDone()
    {
        $this->deleteTaskIdFile();
    }

    private function deleteTaskIdFile()
    {
        $path = $this->getParam('path');

        unlink(Helper::getTaskIdFile($path));
    }
}
