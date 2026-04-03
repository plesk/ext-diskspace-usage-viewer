<?php
// Copyright 1999-2026. WebPros International GmbH. All rights reserved.

use PleskExt\DiskspaceUsageViewer\Cleaner;
use PleskExt\DiskspaceUsageViewer\Controller;
use PleskExt\DiskspaceUsageViewer\Files;
use PleskExt\DiskspaceUsageViewer\Helper;
use PleskExt\DiskspaceUsageViewer\Task\UpdateFiles as UpdateFilesTask;
use PleskExt\DiskspaceUsageViewer\PermissionChecker;

class IndexController extends Controller
{
    public function indexAction()
    {
        $domainId = (int) $this->getParam('site_id');
        $client = pm_Session::getClient();
        if ($domainId > 0) {
            $url = pm_Context::getBaseUrl() . '#' . $this->dir($client);
            $this->redirect($url);
        } elseif (!$client->isAdmin()) {
            $domain = Helper::activeDomain();
            $permissionChecker = new PermissionChecker();
            if (!$permissionChecker->canManageFiles($client, $domain)) {
                throw new pm_Exception('Permission denied');
            }
        }

        $openFiles = (bool) $this->getParam('openFiles', 0);

        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl() . 'css/chart.css');
        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl() . 'css/loading.css');

        $this->view->moduleId = pm_Context::getModuleId();
        $this->view->baseUrl = pm_Context::getBaseUrl();
        $this->view->locales = pm_Locale::getSection('home');
        $this->view->transOthers = pm_Locale::lmsg('others');
        $this->view->isAdmin = pm_Session::getClient()->isAdmin();
        $this->view->defaultDaysToKeepBackups = Cleaner::defaultDaysToKeepBackups();
        $this->view->openFiles = $openFiles;
    }

    public function usageAction()
    {
        $client = pm_Session::getClient();
        $dir = $this->dir($client);


        if ($client->isAdmin()) {
            $fileManager = new pm_ServerFileManager();
        } else {
            $fileManager = new pm_FileManager(Helper::requireActiveDomain()->getId());
        }

        $items = [];

        foreach ($fileManager->scanDir($dir, true) as $basename) {
            $basename = urldecode($basename);
            $path = rtrim($dir, '/') . '/' . $basename;

            try {
                $isDir = $fileManager->isDir($path);
                $mtime = @filemtime($path);

                if ($mtime === false) {
                    $mtime = 0;
                }
            } catch (PleskUtilException $e) {
                continue;
            }

            $items[] = [
                'name' => $basename,
                'isDir' => $isDir,
                'path' => $path,
                'mtime' => $mtime,
                'size' => 0,
                'sizeLoading' => true,
            ];
        }

        $this->ajax($items);
    }

    public function sizeAction()
    {
        $this->requireProtectedActionAccess();
        $path = Helper::cleanPath($this->getParam('path', ''));

        $this->ajax([
            'size' => Helper::size($path),
        ]);
    }

    public function batchSizeAction()
    {
        $this->requireProtectedActionAccess();
        $json = $this->getParam('json');
        $data = json_decode($json, true);

        foreach ($data as $key => $value) {
            $data[$key]['path'] = Helper::cleanPath($value['path'] ?? '');
            $data[$key]['size'] = Helper::size($data[$key]['path']);
        }

        $this->ajax($data);
    }

    public function filesAction()
    {
        $this->requireAdmin();

        $this->ajax(array_values(Files::all()));
    }

    public function updateFilesAction()
    {
        $this->requirePost();
        $client = pm_Session::getClient();
        $dir = $this->dir($client);
        $task = new UpdateFilesTask();
        $url = pm_Context::getBaseUrl() . '?openFiles=1#' . $dir;

        $task->setParam('redirect', $url);

        (new pm_LongTask_Manager())->start($task);

        $this->ajax([]);
    }

    public function deleteByPathAction()
    {
        $this->requireProtectedActionAccess();
        $this->requirePost();

        $json = $this->getParam('json');
        $paths = json_decode($json, true);
        $errors = [];

        foreach ($paths as $path) {
            try {
                $path = Helper::cleanPath((string) $path);
                Helper::delete($path);
            } catch (Exception $e) {
                pm_Log::err($e);

                $errors[] = pm_Locale::lmsg('home.message.deleteFailed', ['path' => $path]);
            }
        }

        $this->ajax($errors);
    }

    public function deleteByIdAction()
    {
        $this->requireAdmin();
        $this->requirePost();

        $json = $this->getParam('json');
        $ids = json_decode($json, true);
        $files = Files::all();
        $errors = [];

        foreach ($ids as $id) {
            try {
                if (!isset($files[$id])) {
                    continue;
                }

                $path = $files[$id]['path'];

                Helper::delete($path);
                Files::delete($id);
            } catch (Exception $e) {
                pm_Log::err($e);

                $errors[] = pm_Locale::lmsg('home.message.deleteFailed', ['path' => $path]);
            }
        }

        $this->ajax($errors);
    }

    public function cleanupAction()
    {
        $this->requireAdmin();
        $this->requirePost();

        $cleanupCache = (bool) $this->getParam('cleanupCache');
        $cleanupBackup = (bool) $this->getParam('cleanupBackup');
        $cleanupBackupDays = (int) $this->getParam('cleanupBackupDays');

        if ($cleanupBackupDays <= 0) {
            $cleanupBackupDays = Cleaner::defaultDaysToKeepBackups();
        }

        if ($cleanupCache) {
            Cleaner::cleanCache();
        }

        if ($cleanupBackup) {
            Cleaner::cleanBackups($cleanupBackupDays);
        }

        $this->ajax([]);
    }

    private function dir(\pm_Client $client): string
    {
        $domainId = (int) $this->getParam('site_id');

        if ($domainId > 0) {
            $pmDomain = pm_Domain::getByDomainId($domainId);
            $this->assertCanManageFiles($client, $pmDomain);

            return $pmDomain->getDocumentRoot();
        }

        $dir = Helper::cleanPath($this->getParam('dir', ''));

        if ($client->isAdmin()) {
            return $dir;
        }

        $domain = Helper::requireActiveDomain();
        $this->assertCanManageFiles($client, $domain);

        $baseDir = $domain->getHomePath();

        if (substr($dir, 0, strlen($baseDir)) !== $baseDir) {
            return $baseDir;
        }

        $fileManager = new pm_FileManager($domain->getId());

        if (!$fileManager->isDir($dir)) {
            return $baseDir;
        }

        return $dir;
    }

    /**
     * @throws pm_Exception
     */
    private function requireProtectedActionAccess(): void
    {
        $client = pm_Session::getClient();

        if ($client->isAdmin()) {
            return;
        }

        $domainId = (int) $this->getParam('site_id');
        $domain = $domainId > 0 ? pm_Domain::getByDomainId($domainId) : Helper::activeDomain();
        $this->assertCanManageFiles($client, $domain);
    }

    private function assertCanManageFiles(\pm_Client $client, ?\pm_Domain $domain): void
    {
        $permissionChecker = new PermissionChecker();

        if (!$permissionChecker->canManageFiles($client, $domain)) {
            throw new pm_Exception('Permission denied');
        }
    }
}
