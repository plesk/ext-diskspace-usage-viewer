<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

$messages = [
    'title' => 'Diskspace Usage Viewer',
    'buttonHook' => [
        'title' => 'Diskspace Usage',
        'description' => 'Find folders with most used diskspace and easily free up some space',
    ],
    'others' => '[others]',
    'home' => [
        'tab' => [
            'usage' => [
                'title' => 'Diskspace Usage',
                'button' => [
                    'cleanup' => 'Clean-up',
                    'delete' => 'Delete selected',
                ],
                'deleteDialog' => [
                    'title' => 'Delete selected items?',
                    'description' => 'Are you really sure that you want to deleted the selected items? This action cannot be undone!',
                    'button' => 'Delete',
                ],
                'cleanupDialog' => [
                    'title' => 'Clean-up web space',
                    'description' => 'This action removes all cache / temp files and system backups older than the specified amount of days. User backups are not touched by this process. Please be patient, this can take a while!',
                    'button' => 'Clean-up',
                    'cache' => 'Cache / temp files',
                    'backups' => 'System backups',
                    'backupDays' => 'Backup files older than (in days)',
                ],
                'col' => [
                    'name' => 'Name',
                    'type' => 'Type',
                    'size' => 'Size',
                ],
                'type' => [
                    'dir' => 'Directory',
                    'file' => 'File',
                ],
            ],
            'files' => [
                'title' => 'Largest Files',
                'col' => [
                    'name' => 'Name',
                    'path' => 'Path',
                    'size' => 'Size',
                    'mtime' => 'Last modified',
                ],
                'button' => [
                    'refresh' => 'Refresh',
                    'delete' => 'Delete selected',
                ],
            ],
        ],
        'task' => [
            'running' => 'Updating list of largest files...',
            'done' => 'Largest file list updated',
        ],
        'message' => [
            'requestFailed' => 'Operation failed',
            'deleteFailed' => 'Unable to delete: %%path%%',
            'cleanupFinished' => 'Clean-up finished',
        ],
    ],
];
