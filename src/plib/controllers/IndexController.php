<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Helper;

class IndexController extends pm_Controller_Action
{
    private $client;
    private $fileManager;
    private $currentPath = '/';
    private $basePath = '/';

    protected $_accessLevel = ['admin', 'reseller', 'client'];

    public function init()
    {
        parent::init();

        $this->client = pm_Session::getClient();
        $this->fileManager = $this->client->isAdmin() ? new pm_ServerFileManager : new pm_FileManager(pm_Session::getCurrentDomain()->getId());

        if ($this->_getParam('site_id')) {
            $siteId = $this->_getParam('site_id');

            if (!$this->client->hasAccessToDomain($siteId)) {
                throw new pm_Exception('Access denied');
            }

            $this->basePath = pm_Domain::getByDomainId($siteId)->getDocumentRoot();

            $this->setCurrentPath($this->basePath);
        } elseif ($this->client->isAdmin()) {
            $this->setCurrentPath('/');
        } else {
            $this->basePath = pm_Session::getCurrentDomain()->getHomePath();

            $this->setCurrentPath($this->basePath);
        }

        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl() . 'css/styles.css');

        $this->view->headScript()->appendFile('https://www.gstatic.com/charts/loader.js');
    }

    public function indexAction()
    {
        if ($this->_getParam('path')) {
            $this->setCurrentPath($this->_getParam('path'));
        }

        $usage = Helper::getDiskspaceUsage($this->currentPath);
        $chartData = [];

        foreach (array_slice($usage, 0, 10) as $item) {
            $chartData[] = [$item['displayName'], $item['size'], $item['displayName']];
        }

        $runningTask = Helper::getRunningTask($this->currentPath);

        if (!$runningTask && Helper::needUpdateCache($this->currentPath)) {
            $runningTask = Helper::startTask($this->currentPath);
        }

        $dirSize = 0;

        foreach ($usage as $item) {
            $dirSize += $item['size'];
        }

        $this->view->pageTitle = $this->lmsg('pageTitle', ['path' => $this->getCurrentPathBreadcrumb()]);
        $this->view->chartData = $chartData;
        $this->view->list = $this->getUsageList($usage);
        $this->view->path = $this->currentPath;
        $this->view->runningTask = $runningTask;
        $this->view->isEmptyDir = empty($usage);
        $this->view->dirSize = $dirSize;
    }

    public function indexDataAction()
    {
        if ($this->_getParam('path')) {
            $this->setCurrentPath($this->_getParam('path'));
        }

        $usage = Helper::getDiskspaceUsage($this->currentPath);
        $list = $this->getUsageList($usage);

        $this->_helper->json($list->fetchData());
    }

    public function refreshAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Permission denied');
        }

        $task = Helper::startTask($this->_getParam('path'));

        $this->_helper->json($task);
    }

    private function setCurrentPath($path)
    {
        $path = trim(Helper::cleanPath($path));

        if (!$this->client->isAdmin()) {
            if (substr($path, 0, strlen($this->basePath)) !== $this->basePath) {
                $path = $this->basePath;
            }
        }

        if (!Helper::isDir($path, $this->fileManager)) {
            $path = $this->basePath;
        }

        $this->currentPath = $path;
    }

    private function getCurrentPathBreadcrumb()
    {
        $path = trim($this->currentPath, '/');

        if ($path == '') {
            return '<a href="' . Helper::getActionUrl('index', ['path' => '/']) . '">/</a>';
        }

        $names = explode('/', $path);
        $breadcrumbs = ['<a href="' . Helper::getActionUrl('index', ['path' => '/']) . '">/</a>'];
        $currentPath = '';

        foreach ($names as $name) {
            $currentPath .= '/' . $name;
            $breadcrumbs[] = '<a href="' . Helper::getActionUrl('index', ['path' => $currentPath]) . '">' . htmlspecialchars($name) . '</a> /';
        }

        return '<b>' . implode(' ', $breadcrumbs) . '</b>';
    }

    private function getUsageList(array $usage)
    {
        return new \PleskExt\DiskspaceUsageViewer\UsageList($this->view, $this->_request, $this->currentPath, $usage);
    }

    private function isRootPath($path)
    {
        $path = trim($path, '/');

        if (strpos($path, '/') === false)
        {
            return true;
        }

        return false;
    }

    public function deleteSelectedAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Permission denied');
        }

        $paths = (array) $this->_getParam('ids');
        foreach ($paths as $path) {
            $path = Helper::cleanPath($path);

            if ($this->isRootPath($path)) {
                throw new pm_Exception(pm_Locale::lmsg('messageCannotDeleteSystemFile', ['path' => $path]));
            }

            try {
                if (Helper::isDir($path, $this->fileManager)) {
                    $this->fileManager->removeDirectory($path);
                } else {
                    $this->fileManager->removeFile($path);
                }
            } catch (PleskUtilException $e) {
                throw new pm_Exception(pm_Locale::lmsg('messageDeleteInsufficientPermissions', ['path' => $path]));
            }
        }

        $parentPath = '/';

        if (!empty($paths)) {
            $path = trim(Helper::cleanPath($paths[0]), '/');

            if ($path != '') {
                $segments = explode('/', $path);

                array_pop($segments);

                if (count($segments) > 0) {
                    $parentPath = '/' . implode('/', $segments);
                }
            }
        }

        unlink(Helper::getCacheFile($parentPath));

        $url = Helper::getActionUrl('index', ['path' => $parentPath]);

        $this->redirect($url);
    }
}
