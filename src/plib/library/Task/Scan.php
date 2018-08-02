<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer\Task;

use PleskExt\DiskspaceUsageViewer\Helper;

class Scan extends \pm_LongTask_Task
{
    public function run()
    {
        $client = \pm_Session::getClient();
        $path = $this->getParam('path');

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

        $cacheFile = Helper::getCacheFile($path);

        file_put_contents($cacheFile, json_encode($list));
    }

    public function getSteps()
    {
        return [
            [
                'title' => \pm_Locale::lmsg('scanTaskRunning', ['path' => $this->getParam('path')]),
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

    public function onDone()
    {
        $this->markTaskFinished();
    }

    public function onError(\Exception $e)
    {
        $this->markTaskFinished();
    }

    private function markTaskFinished()
    {
        $setting = 'task_running_' . sha1($this->getParam('path'));

        \pm_Settings::set($setting, 'false');
    }
}
