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

        $fileManager = $this->getParam('isAdmin') ? new \pm_ServerFileManager : new \pm_FileManager($this->getParam('domainId'));
        $lines = explode("\n", trim($result['stdout']));
        $list = [];

        foreach ($lines as $line) {
            $segments = preg_split('/\s+/', $line);

            if (count($segments) < 2) {
                continue;
            }

            $kiloBytes = (int)array_shift($segments);
            $baseName = implode(' ', $segments);

            if ($baseName === '.') {
                continue;
            }

            $baseName = substr($baseName, 2);
            $fullPath = $path . DIRECTORY_SEPARATOR . $baseName;

            if (method_exists($fileManager, 'isDir')) {
                $isDir = $fileManager->isDir($fullPath);
            } else {
                $isDir = $fileManager->fileExists($fullPath . '/');
            }

            $list[] = [
                'size' => $kiloBytes,
                'name' => $baseName,
                'isDir' => $isDir,
                'displayName' => $isDir ? $baseName . '/' : $baseName,
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
