<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Helper;

class IndexController extends pm_Controller_Action
{
    private $client;
    private $currentPath = '/';
    private $basePath = '/';

    protected $_accessLevel = ['admin', 'reseller', 'client'];

    private function getActionUrl($action, array $params = [])
    {
        $url = pm_Context::getActionUrl('index', $action);

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    public function init()
    {
        parent::init();

        $this->client = pm_Session::getClient();

        if ($this->_getParam('dom_id')) {
            $domainId = $this->_getParam('dom_id');

            if (!$this->client->hasAccessToDomain($domainId)) {
                throw new pm_Exception('Access denied');
            }

            $this->basePath = pm_Domain::getByDomainId($domainId)->getHomePath();

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

        $this->view->pageTitle = $this->lmsg('pageTitle', ['path' => $this->getCurrentPathBreadcrumb()]);
        $this->view->chartData = $chartData;
        $this->view->list = $this->getUsageList($this->currentPath, $usage);
        $this->view->path = $this->currentPath;
        $this->view->runningTask = $runningTask;
        $this->view->isEmptyDir = empty($usage);
    }

    public function indexDataAction()
    {
        if ($this->_getParam('path')) {
            $this->setCurrentPath($this->_getParam('path'));
        }

        $usage = Helper::getDiskspaceUsage($this->currentPath);
        $list = $this->getUsageList($this->currentPath, $usage);

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

        if ($path == '') {
            $path = '/';
        }

        if (!$this->client->isAdmin()) {
            if (substr($path, 0, strlen($this->basePath)) !== $this->basePath) {
                $path = $this->basePath;
            }
        }

        $this->currentPath = $path;
    }

    private function getFullPath($folderName)
    {
        if (substr($this->currentPath, -1) == '/') {
            return $this->currentPath . $folderName;
        } else {
            return $this->currentPath . '/' . $folderName;
        }
    }

    private function getCurrentPathBreadcrumb()
    {
        $path = trim($this->currentPath, '/');

        if ($path == '') {
            return '<a href="' . $this->getActionUrl('index', ['path' => '/']) . '">/</a>';
        }

        $names = explode('/', $path);
        $breadcrumbs = ['<a href="' . $this->getActionUrl('index', ['path' => '/']) . '">/</a>'];
        $currentPath = '';

        foreach ($names as $name) {
            $currentPath .= '/' . $name;
            $breadcrumbs[] = '<a href="' . $this->getActionUrl('index', ['path' => $currentPath]) . '">' . htmlspecialchars($name) . '</a> /';
        }

        return '<b>' . implode(' ', $breadcrumbs) . '</b>';
    }

    private function getUsageList($currentPath, array $usage)
    {
        $data = [];

        foreach ($usage as $item) {
            $displayPath = $item['displayName'];
            $fullPath = $this->getFullPath($item['name']);

            if ($item['isDir']) {
                $displayPath = '<a href="' . $this->getActionUrl('index', ['path' => $fullPath]) . '">' . htmlspecialchars($item['displayName']) . '</a>';
            }

            $data[] = [
                'id' => $fullPath,
                'path' => $displayPath,
                'size' => '<span class="hidden">' . str_pad($item['size'], 10, '0', STR_PAD_LEFT) . '</span>' . Helper::formatSize($item['size']),
            ];
        }

        $options = [
            'defaultSortField' => 'size',
            'defaultSortDirection' => pm_View_List_Simple::SORT_DIR_DOWN,
        ];

        $list = new pm_View_List_Simple($this->view, $this->_request, $options);

        $list->setColumns([
            pm_View_List_Simple::COLUMN_SELECTION,
            'path' => [
                'title' => pm_Locale::lmsg('columnPath'),
                'noEscape' => true,
                'sortable' => true,
                'searchable' => true,
            ],
            'size' => [
                'title' => pm_Locale::lmsg('columnSize'),
                'noEscape' => true,
                'sortable' => true,
                'cls' => 'number t-r',
            ],
        ]);

        $list->setData($data);

        if (!empty($data)) {
            $listTools = [
                [
                    'title' => pm_Locale::lmsg('buttonRefresh'),
                    'class' => 'sb-refresh',
                    'link' => 'javascript:extDiskspaceUsageViewerRefresh(' . json_encode($currentPath) . ')',
                ],
                [
                    'title' => pm_Locale::lmsg('buttonDelete'),
                    'execGroupOperation' => [
                        'skipConfirmation' => false,
                        'subtype' => 'delete',
                        'locale' => ['confirmOnGroupOperation' => pm_Locale::lmsg('confirmDelete')],
                        'url' => $this->getActionUrl('delete-selected'),
                    ],
                    'class' => 'sb-delete-selected',
                ],
            ];

            $list->setTools($listTools);
        }

        $list->setDataUrl($this->getActionUrl('index-data', ['path' => $currentPath]));

        return $list;
    }

    public function deleteSelectedAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Permission denied');
        }

        $paths = (array) $this->_getParam('ids');
        $fileManager = $this->client->isAdmin() ? new pm_ServerFileManager : new pm_FileManager(pm_Session::getCurrentDomain()->getId());

        foreach ($paths as $path) {
            $path = Helper::cleanPath($path);

            if ($fileManager->isDir($path)) {
                $fileManager->removeDirectory($path);
            } else {
                $fileManager->removeFile($path);
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

        $url = $this->getActionUrl('index', ['path' => $parentPath]);

        $this->redirect($url);
    }
}
