<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class Installer
{
    public static function upgrade(string $fromVersion): void
    {
        self::initDb();
        self::migrate($fromVersion);
        self::addUpdateFilesTask();
    }

    private static function migrate(string $fromVersion): void
    {
        $schemaDir = \pm_Context::getPlibDir() . 'resources/schemas';
        $allVersions = [];

        foreach (glob($schemaDir . '/*.sql') as $basename) {
            $allVersions[] = pathinfo($basename, PATHINFO_FILENAME);
        }

        if (empty($allVersions)) {
            return;
        }

        usort($allVersions, 'version_compare');

        $versionFile = \pm_Context::getVarDir() . 'db.version';
        $schemaVersion = null;
        $fileManager = new \pm_ServerFileManager();

        if (!is_file($versionFile)) {
            $fileManager->touch($versionFile);
            $fileManager->chmod($versionFile, '0600');
        } else {
            $schemaVersion = trim($fileManager->fileGetContents($versionFile));
        }

        foreach ($allVersions as $curVersion) {
            if (version_compare($curVersion, $fromVersion) == -1) {
                continue;
            }

            if ($schemaVersion !== null) {
                if (version_compare($curVersion, $schemaVersion) !== 1) {
                    continue;
                }
            }

            $schemaFile = $schemaDir . '/' . $curVersion . '.sql';
            $contents = str_replace(["\r\n", "\r", "\n"], ' ', $fileManager->fileGetContents($schemaFile));
            $queries = explode(';', $contents);

            foreach ($queries as $sql) {
                $sql = trim($sql);

                if ($sql === '') {
                    continue;
                }

                Db::adapter()->query($sql);
            }

            $fileManager->filePutContents($versionFile, $curVersion);
        }
    }

    private static function initDb(): void
    {
        $file = Db::path();

        if (!file_exists($file)) {
            touch($file);
            chmod($file, 0600);
        }
    }

    private static function addUpdateFilesTask(): void
    {
        $scheduler = \pm_Scheduler::getInstance();
        $command = 'files.php';

        foreach ($scheduler->listTasks() as $task) {
            if ($task->getCmd() === $command) {
                return;
            }
        }

        $task = new \pm_Scheduler_Task();

        $task->setCmd($command);
        $task->setSchedule(\pm_Scheduler::$EVERY_HOUR);

        $scheduler->putTask($task);
    }
}
