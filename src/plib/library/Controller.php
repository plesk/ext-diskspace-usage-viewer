<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

abstract class Controller extends \pm_Controller_Action
{
    public function init()
    {
        parent::init();

        $this->view->pageTitle = \pm_Locale::lmsg('title');
    }

    protected function requirePost(): void
    {
        if (!$this->getRequest()->isPost()) {
            throw new \pm_Exception('POST method required');
        }
    }

    protected function requireAdmin(): void
    {
        if (!\pm_Session::getClient()->isAdmin()) {
            throw new \pm_Exception('Permission denied');
        }
    }

    protected function ajax(array $data): void
    {
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $this->getResponse()->setBody(json_encode($data));
    }
}
