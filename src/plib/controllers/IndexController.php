<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Helper;

class IndexController extends pm_Controller_Action
{
    private $client;
    private $currentPath = '/';
    private $basePath = '/';

    protected $_accessLevel = ['admin', 'reseller', 'client'];

    public function init()
    {
        parent::init();

        $this->client = pm_Session::getClient();

        if ($this->client->isAdmin()) {
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

        $this->view->pageTitle = $this->lmsg('pageTitle', ['path' => $this->getCurrentPathBreadcrumb()]);
        $this->view->chartData = $chartData;
        $this->view->list = $this->getUsageList($this->currentPath, $usage);
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
            return '<a href="' . $this->_helper->url('index', 'index', null, ['path' => $currentPath]) . '">/</a>';
        }

        $names = explode('/', $path);
        $currentPath = '/';
        $breadcrumbs = ['<a href="' . $this->_helper->url('index', 'index', null, ['path' => $currentPath]) . '">/</a>'];

        foreach ($names as $name) {
            $currentPath .= $name . '/';
            $breadcrumbs[] = '<a href="' . $this->_helper->url('index', 'index', null, ['path' => $currentPath]) . '">' . htmlspecialchars($name) . '/</a>';
        }

        return implode(' ', $breadcrumbs);
    }

    private function getUsageList($currentPath, array $usage)
    {
        $data = [];

        foreach ($usage as $item) {
            $displayPath = $item['displayName'];
            $fullPath = $this->getFullPath($item['name']);

            if ($item['isDir']) {
                $displayPath = '<a href="' . $this->_helper->url('index', 'index', null, ['path' => $fullPath]) . '">' . htmlspecialchars($item['displayName']) . '</a>';
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
                    'title' => pm_Locale::lmsg('buttonDelete'),
                    'execGroupOperation' => [
                        'skipConfirmation' => false,
                        'subtype' => 'delete',
                        'locale' => ['confirmOnGroupOperation' => pm_Locale::lmsg('confirmDelete')],
                        'url' => $this->_helper->url('delete-selected'),
                    ],
                    'class' => 'sb-delete-selected',
                ],
            ];

            $list->setTools($listTools);
        }

        $list->setDataUrl($this->_helper->url('index-data', 'index', null, ['path' => $currentPath]));

        return $list;
    }

    public function deleteSelectedAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Permission denied');
        }

        $paths = (array) $this->_getParam('ids');
        $serverFileManager = new pm_ServerFileManager;

        foreach ($paths as $path) {
            $path = Helper::cleanPath($path);

            if ($serverFileManager->isDir($path)) {
                $serverFileManager->removeDirectory($path);
            } else {
                $serverFileManager->removeFile($path);
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

        $url = $this->_helper->url('index', 'index', null, ['path' => $parentPath]);

        $this->redirect($url);
    }
}
