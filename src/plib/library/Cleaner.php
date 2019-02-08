<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class Cleaner
{
    public const DEFAULT_DAYS_TO_KEEP_BACKUPS = 90;

    public static function cleanCache(): void
    {
        \pm_ApiCli::callSbin('repair_diskspace', ['--repair'], \pm_ApiCli::RESULT_EXCEPTION);
    }

    public static function cleanBackups(int $daysToKeepBackups): void
    {
        $args = [
            '--get-dump-list',
            '--type',
            'server',
        ];

        $result = \pm_ApiCli::callSbin('pmm-ras', $args, \pm_ApiCli::RESULT_EXCEPTION);
        $sXml = simplexml_load_string($result['stdout']);
        $toDate = new \DateTime("-{$daysToKeepBackups} days");

        foreach ($sXml->dump as $dump) {
            $incrementBase = (string) $dump['increment-base'];

            if ($incrementBase !== '') {
                continue;
            }

            $timestamp = (string) $dump['creation-date'];
            $basename = (string) $dump['name'];
            $backupDate = \DateTime::createFromFormat('ymdHi', $timestamp);

            if ($backupDate->getTimestamp() > $toDate->getTimestamp()) {
                continue;
            }

            $args = [
                '--delete-dump',
                '--dump-specification',
                $basename,
            ];

            \pm_ApiCli::callSbin('pmm-ras', $args, \pm_ApiCli::RESULT_EXCEPTION);
        }
    }
}
