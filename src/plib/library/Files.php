<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class Files
{
    public static function update(): void
    {
        try {
            $files = self::scan();
            $stmt = Db::adapter()->prepare('INSERT INTO `files` (`path`, `size`, `mtime`) VALUES (:path, :size, :mtime)');

            Db::adapter()->exec('DELETE FROM `files`');

            foreach ($files as $file) {
                $stmt->execute([
                    'path' => $file['path'],
                    'size' => $file['size'],
                    'mtime' => $file['mtime'],
                ]);
            }
        } catch (\pm_Exception $e) {
            \pm_Log::err($e);
        }
    }

    public static function all(): array
    {
        $sql = 'SELECT * FROM `files` ORDER BY `size` DESC';
        $files = [];

        foreach (Db::adapter()->fetchAssoc($sql) as $row) {
            $id = (int) $row['id'];

            $files[$id] = [
                'id' => $id,
                'name' => basename($row['path']),
                'path' => $row['path'],
                'size' => (int) $row['size'],
                'mtime' => (int) $row['mtime'],
            ];
        }

        return $files;
    }

    public static function delete(int $id): void
    {
        $stmt = Db::adapter()->prepare('DELETE FROM `files` WHERE `id` = :id');

        $stmt->execute([
            'id' => $id,
        ]);
    }

    private static function scan(): array
    {
        $result = \pm_ApiCli::callSbin('files.sh', [], \pm_ApiCli::RESULT_EXCEPTION);
        $lines = explode("\n", $result['stdout']);
        $files = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $cols = explode("\t", $line);

            if (count($cols) !== 3) {
                continue;
            }

            $files[] = [
                'size' => (int) $cols[0],
                'mtime' => strtotime($cols[1]),
                'path' => $cols[2],
            ];
        }

        return $files;
    }
}
