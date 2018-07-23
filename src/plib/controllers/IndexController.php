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

        $this->view->headLink()->appendStylesheet(\pm_Context::getBaseUrl() . 'styles.css');

        $this->setPath('/');
    }

    public function indexAction()
    {
        if ($this->_getParam('path')) {
            $this->setPath($this->_getParam('path'));
        }

        $this->view->list = $this->getFolderList($this->currentPath);
    }

    public function indexDataAction()
    {
        if ($this->_getParam('path')) {
            $this->setPath($this->_getParam('path'));
        }

        $list = $this->getFolderList($this->currentPath);

        $this->_helper->json($list->fetchData());
    }

    private function setPath($path)
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

        $this->view->pageTitle = $this->lmsg('pageTitle', ['path' => $this->getCurrentPathBreadcrump()]);
    }

    private function getFullPath($folderName)
    {
        if (substr($this->currentPath, -1) == '/') {
            return $this->currentPath . $folderName;
        } else {
            return $this->currentPath . '/' . $folderName;
        }
    }

    private function getCurrentPathBreadcrump()
    {
        $folders = explode('/', $this->currentPath);
        $path = '/';
        $breadCrumb = '/';

        foreach ($folders as $folder) {
            if (!empty($folder)) {
                $path .= $folder . '/';
                $breadCrumb .= '<a href="' . \pm_Context::getBaseUrl() . '?path=' . $path .'">' . $folder . '</a>/';
            }
        }

        return $breadCrumb;
    }

    private function getFolderList($path)
    {
        $data = [];

        // add first row to navigate to parent folder
        if ($path != '/') {
            $data[] = [
                'size' => '<span class="hidden">9999999999</span>',
                'folder' => '<a href="' . \pm_Context::getBaseUrl() . '?path=' . Helper::getParentPath($this->currentPath) .'">..</a>',
            ];
        }

        $folders = Helper::getDiskspaceUsage($path);

        foreach ($folders as $folder) {
            $data[] = [
                'size' => '<span class="hidden">' . str_pad($folder[0], 10, '0', STR_PAD_LEFT) . '</span>' . Helper::formatSize($folder[0]),
                'folder' => '<a href="' . \pm_Context::getBaseUrl() . '?path=' . $this->getFullPath($folder[1]) .'">' . $folder[1] . '</a>',
            ];
        }

        $options = array (
            'defaultSortField' => 'size',
            'defaultSortDirection' => \pm_View_List_Simple::SORT_DIR_DOWN,
        );

        $list = new \pm_View_List_Simple($this->view, $this->_request, $options);

        $list->setColumns([
            'size' => [
                'title' => \pm_Locale::lmsg('columnSize'),
                'noEscape' => true,
                'sortable' => true,
            ],
            'folder' => [
                'title' => \pm_Locale::lmsg('columnFolder'),
                'noEscape' => true,
                'sortable' => true,
                'searchable' => true,
            ],
        ]);

        $list->setData($data);
        $list->setDataUrl(['action' => 'index-data']);

        return $list;
    }
}
