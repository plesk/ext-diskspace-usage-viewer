<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Cleaner;
use PleskExt\DiskspaceUsageViewer\Db;
use PleskExt\DiskspaceUsageViewer\Helper;

class NewController extends \pm_Controller_Action
{
    private $client;
    private $fileManager;

    protected $_accessLevel = ['admin', 'reseller', 'client'];

    public function init()
    {
        parent::init();

        $this->client = pm_Session::getClient();

        if ($this->_getParam('site_id')) {
            $siteId = $this->_getParam('site_id');

            if (!$this->client->hasAccessToDomain($siteId)) {
                throw new pm_Exception('Access denied');
            }

            $this->fileManager = new pm_FileManager($siteId);

            return;
        }

        if ($this->client->isAdmin()) {
            $this->fileManager = new pm_ServerFileManager;

            return;
        }

        $this->fileManager = new pm_FileManager(pm_Session::getCurrentDomain()->getId());
    }

    public function indexAction()
    {
        $path = $this->getParam('path', '/');
        $items = $this->getItems($path);

        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl() . 'css/styles.css');
        $this->view->items = $items;
        $this->view->path = $path;
        $this->view->breadcrumbsPath = $this->createBreadcrumbsPath();
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
        }
        catch (\pm_Exception $e) {
            \pm_Log::err($e->getMessage());
        }

        Db::saveCache($path, $size);

        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        echo $size;
    }

    public function cleanupAction()
    {
        if (!\pm_Session::getClient()->isAdmin()) {
            $response = [
                'success' => false,
                'message' => \pm_Locale::lmsg('home.actionButtonCleanUpErrorNoAdmin'),
            ];

            $this->_helper->json($response);
        }

        $settingsRequest = json_decode($this->getRequest()->getParam('settings'), true);

        if (empty($settingsRequest['cleanUpSelectionCache']) && empty($settingsRequest['cleanUpSelectionBackup'])) {
            $response = [
                'success' => false,
                'message' => \pm_Locale::lmsg('home.actionButtonCleanUpNoSelection'),
            ];

            $this->_helper->json($response);
        }

        if (isset($settingsRequest['cleanUpSelectionCache']) && $settingsRequest['cleanUpSelectionCache'] === true) {
            Cleaner::cleanCache();
        }

        if (isset($settingsRequest['cleanUpSelectionBackup']) && $settingsRequest['cleanUpSelectionBackup'] === true) {
            $cleanUpBackupDays = 90;

            if (isset($settingsRequest['cleanUpBackupDays']) && !empty(intval($settingsRequest['cleanUpBackupDays']))) {
                $cleanUpBackupDays = intval($settingsRequest['cleanUpBackupDays']);
            }

            Cleaner::cleanBackups($cleanUpBackupDays);
        }

        $response = [
            'success' => true,
            'message' => \pm_Locale::lmsg('home.actionButtonCleanUpSuccess'),
        ];

        $this->_helper->json($response);
    }

    public function getItemsAction()
    {
        $path = $this->getParam('path', '/');
        $items = $this->getItems($path);

        $this->_helper->json($items);
    }

    public function getBreadcrumbsPathAction()
    {
        $breadcrumbsPath = $this->createBreadcrumbsPath();

        $this->_helper->json($breadcrumbsPath);
    }

    public function deleteAction()
    {
        if (!$this->_request->isPost()) {
            throw new pm_Exception('Permission denied');
        }

        $response = [
            'success' => true,
            'message' => \pm_Locale::lmsg('home.actionButtonDeleteSuccess'),
        ];

        $rawBody = json_decode($this->getRequest()->getRawBody(), true);

        if (empty($rawBody['paths'])) {
            $response = [
                'success' => false,
                'message' => \pm_Locale::lmsg('home.actionButtonDeleteErrorRequest'),
            ];

            $this->_helper->json($response);
        }

        $paths = $rawBody['paths'];
        $messagesError = [];

        foreach ($paths as $path) {
            $path = Helper::cleanPath($path);

            if (Helper::isSystemFile($path)) {
                $messagesError[] = pm_Locale::lmsg('messageCannotDeleteSystemFile', ['path' => $path]);

                continue;
            }

            try {
                if (Helper::isDir($path, $this->fileManager)) {
                    $this->fileManager->removeDirectory($path);
                } else {
                    $this->fileManager->removeFile($path);
                }
            }
            catch (\PleskUtilException $e) {
                $messagesError[] = pm_Locale::lmsg('messageDeleteInsufficientPermissions', ['path' => $path]);
            }
        }

        if (!empty($messagesError)) {
            $response = [
                'success' => false,
                'message' => implode("\n", $messagesError),
            ];
        }

        $this->_helper->json($response);
    }

    public function getBiggestFilesAction()
    {
        $result = [
            'success' => true,
            'data' => array_values(Db::getFiles()),
        ];

        $this->_helper->json($result);
    }

    public function deleteBiggestFileAction()
    {
        if (!$this->getRequest()->isPost()) {
            throw new \pm_Exception('Permission denied');
        }

        $id = (int) $this->getParam('id');

        $result = [
            'success' => true,
            'message' => '',
        ];

        try {
            $files = Db::getFiles();

            if (isset($files[$id])) {
                $path = $files[$id]['path'];

                if (Helper::isSystemFile($path)) {
                    $result['success'] = false;
                    $result['message'] = \pm_Locale::lmsg('messageCannotDeleteSystemFile', ['path' => $path]);
                } else {
                    try {
                        if (\pm_Session::getClient()->isAdmin()) {
                            $fileManager = new \pm_ServerFileManager;
                        } else {
                            $fileManager = new \pm_FileManager(\pm_Session::getCurrentDomain()->getId());
                        }

                        $fileManager->removeFile($path);
                        Db::deleteFileById($id);
                    }
                    catch (\PleskUtilException $e) {
                        $result['success'] = false;
                        $result['message'] = \pm_Locale::lmsg('messageDeleteInsufficientPermissions', ['path' => $path]);
                    }
                }
            }
        }
        catch (\Exception $e) {
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }

        $this->_helper->json($result);
    }

    public function updateBiggestFilesAction()
    {
        Helper::updateBiggestFiles();

        $this->_helper->json(true);
    }

    private function cleanPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));
        $segments = explode('/', $path);

        $segments = array_filter($segments, function($segment) {
            if (in_array($segment, ['', '.', '..'])) {
                return false;
            }

            return true;
        });

        return '/' . implode('/', $segments);
    }

    private function getItems(string $path): array
    {
        $client = \pm_Session::getClient();
        $curPath = $this->cleanPath($path);

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
            }
            catch (\PleskUtilException $e) {
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
                'id'          => $id,
                'name'        => $basename,
                'displayName' => $isDir ? ($basename . '/') : $basename,
                'isDir'       => $isDir,
                'size'        => $size,
                'path'        => $path,
            ];
        }

        $nameArray = [];

        foreach ($items as $key => $row) {
            $nameArray[$key] = $row['name'];
        }

        array_multisort($nameArray, SORT_ASC, SORT_NATURAL, $items);

        return $items;
    }

    private function createBreadcrumbsPath()
    {
        $breadcrumbsPath = [];
        $path = $this->cleanPath($this->getParam('path', '/'));

        if ($path === '/') {
            return $breadcrumbsPath;
        }

        $pathArray = explode('/', $path);
        $pathElementList = '';

        foreach ($pathArray as $pathElement) {
            if (empty($pathElement)) {
                $breadcrumbsPath[] = ['name' => '/', 'path' => '/'];

                continue;
            }

            $pathElementList .= '/' . $pathElement;
            $breadcrumbsPath[] = ['name' => $pathElement, 'path' => $pathElementList];
        }

        return $breadcrumbsPath;
    }
}
