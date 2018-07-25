<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Helper;

class IndexController extends \pm_Controller_Action
{
    private $currentPath = '/';

    protected $_accessLevel = 'admin';

    public function init()
    {
        parent::init();

        $this->view->headLink()->appendStylesheet(\pm_Context::getBaseUrl() . 'css/styles.css');

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
            $chartData[] = [$item['displayName'], $item['size'], $item['name'] . ' ' . Helper::formatSize($item['size'])];
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
        if (empty($path)) {
            throw new \Exception('Path must not be null');
        }

        // add security handling to ensure only paths can be viewed with current priviledges
        // add security handling to prevent manipulating path string
        if (\pm_Session::getClient()->isAdmin()) {
            $this->currentPath = $path;
        } else {
            // TODO: get path of webspace of current user and make sure this is always the base directory
            $domains = \pm_Domain::getDomainsByClient(\pm_Session::getClient(), true);

            if (sizeof($domains) > 0) {
                var_dump('getVhostSystemPath=' . $domains[0]->getVhostSystemPath());
                var_dump('getDocumentRoot=' . $domains[0]->getDocumentRoot());
                var_dump('getHomePath=' . $domains[0]->getHomePath());
                $this->currentPath = $domains[0]->getVhostSystemPath();
            } else {
                throw new \Exception('No webspace found for this user!');
            }
        }
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
        $segments = explode('/', $this->currentPath);
        $path = '/';
        $breadCrumb = '/';

        foreach ($segments as $segment) {
            if (!empty($segment)) {
                $path .= $segment . '/';
                $breadCrumb .= '<a href="' . \pm_Context::getBaseUrl() . '?path=' . $path .'">' . $segment . '</a>/';
            }
        }

        return $breadCrumb;
    }

    private function getUsageList($currentPath, array $usage)
    {
        $data = [];

        // add first row to navigate to parent folder
        if ($currentPath != '/') {
            $data[] = [
                'size' => '<span class="hidden">9999999999</span>',
                'path' => '<a href="' . \pm_Context::getBaseUrl() . '?path=' . Helper::getParentPath($currentPath) . '">..</a>',
            ];
        }

        foreach ($usage as $item) {
            $displayPath = $item['displayName'];

            if ($item['isDir']) {
                $displayPath = '<a href="' . \pm_Context::getBaseUrl() . '?path=' . $this->getFullPath($item['name']) . '">' . $item['displayName'] . '</a>';
            }

            $data[] = [
                'size' => '<span class="hidden">' . str_pad($item['size'], 10, '0', STR_PAD_LEFT) . '</span>' . Helper::formatSize($item['size']),
                'path' => $displayPath,
            ];
        }

        $options = [
            'defaultSortField' => 'size',
            'defaultSortDirection' => \pm_View_List_Simple::SORT_DIR_DOWN,
        ];

        $list = new \pm_View_List_Simple($this->view, $this->_request, $options);

        $list->setColumns([
            'size' => [
                'title' => \pm_Locale::lmsg('columnSize'),
                'noEscape' => true,
                'sortable' => true,
            ],
            'path' => [
                'title' => \pm_Locale::lmsg('columnPath'),
                'noEscape' => true,
                'sortable' => true,
                'searchable' => true,
            ],
        ]);

        $list->setData($data);
        $list->setDataUrl($this->_helper->url('index-data', 'index', null, ['path' => $currentPath]));

        return $list;
    }
}
