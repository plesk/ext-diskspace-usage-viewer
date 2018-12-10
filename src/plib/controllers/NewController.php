<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Db;
use PleskExt\DiskspaceUsageViewer\Cleaner;

class NewController extends \pm_Controller_Action
{
    public function indexAction()
    {
        $client = \pm_Session::getClient();
        $curPath = $this->cleanPath($this->getParam('path', '/'));

        if ($client->isAdmin()) {
            $basePath = '/';
            $fileManager = new \pm_ServerFileManager();
        } else {
            $domain = \pm_Session::getCurrentDomain();
            $basePath = $domain->getHomePath();
            $fileManager = new \pm_FileManager($domain->getId());
        }

        if (!$client->isAdmin()) {
            if (substr($curPath, 0, strlen($basePath)) !== $basePath) {
                $curPath = $basePath;
            }
        }

        if (!$fileManager->isDir($curPath)) {
            $curPath = $basePath;
        }

        $items = [];
        $id = 0;

        foreach ($fileManager->scanDir($curPath, true) as $basename) {
            $basename = urldecode($basename);
            $path = rtrim($curPath, '/') . '/' . $basename;

            try {
                $isDir = $fileManager->isDir($path);
            } catch (\PleskUtilException $e) {
                continue;
            }

            if (!$isDir) {
                $size = Db::loadCache($path);

                if ($size === 0) {
                    $size = (int) $fileManager->fileSize($path);

                    Db::saveCache($path, $size);
                }
            } else {
                $size = Db::loadCache($path);
            }

            $id++;

            $items[] = [
                'id' => $id,
                'name' => $basename,
                'displayName' => $isDir ? ($basename . '/') : $basename,
                'isDir' => $isDir,
                'size' => $size,
                'path' => $path,
            ];
        }

        $this->view->headScript()->appendFile('https://www.gstatic.com/charts/loader.js');

        $this->view->items = $items;
    }

    public function getDirSizeAction()
    {
        $path = $this->getParam('path');
        $args = [$path];

        if (!\pm_Session::getClient()->isAdmin()) {
            $args[] = \pm_Session::getCurrentDomain()->getSysUserLogin();
        }

        $size = 0;

        try {
            $result = \pm_ApiCli::callSbin('dir_size.sh', $args, \pm_ApiCli::RESULT_EXCEPTION);
            $output = trim($result['stdout']);
            $pos = strpos($output, "\t");

            if ($pos !== false) {
                $size = (int) substr($output, 0, $pos);
            }
        } catch (\pm_Exception $e) {
            \pm_Log::err($e->getMessage());
        }

        Db::saveCache($path, $size);

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        echo $size;
    }

    public function cleanupAction()
    {
        if (\pm_Session::getClient()->isAdmin()) {
            Cleaner::cleanCache();
            Cleaner::cleanBackups(30);
        }

        $this->redirect('new/index');
    }

    private function cleanPath(string $path): string
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
}
