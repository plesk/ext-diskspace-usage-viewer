<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class Installer
{
    private const INITIAL_VERSION = '0.0.0';

    public static function install(): void
    {
        $file = Db::path();

        touch($file);
        chmod($file, 0600);

        self::upgrade(self::INITIAL_VERSION);
        self::addUpdateFilesTask();
    }

    public static function upgrade(string $fromVersion): void
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
        $schemaVersion = self::INITIAL_VERSION;
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

            if (version_compare($curVersion, $schemaVersion) !== 1) {
                continue;
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

    private static function addUpdateFilesTask(): void
    {
        $scheduler = \pm_Scheduler::getInstance();
        $task = new \pm_Scheduler_Task();

        $task->setCmd('files.php');
        $task->setSchedule(\pm_Scheduler::$EVERY_HOUR);

        $scheduler->putTask($task);
    }
}
