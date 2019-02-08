<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class Files
{
    public static function update(): void
    {
        try {
            $files = self::scan();
            $stmt = Db::adapter()->prepare('INSERT INTO `files` (`path`, `size`) VALUES (:path, :size)');

            Db::adapter()->exec('DELETE FROM `files`');

            foreach ($files as $file) {
                $stmt->execute([
                    'path' => $file['path'],
                    'size' => $file['size'],
                ]);
            }
        } catch (\pm_Exception $e) {
            \pm_Log::err($e);
        }
    }

    public static function all(): array
    {
        $sql = <<<SQL
SELECT *
FROM `files`
ORDER BY `size` DESC
SQL;

        $files = [];

        foreach (Db::adapter()->fetchAssoc($sql) as $row) {
            $id = (int) $row['id'];

            $files[$id] = [
                'id' => $id,
                'name' => basename($row['path']),
                'path' => $row['path'],
                'size' => (int) $row['size'],
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

            $pos = strpos($line, "\t");

            if ($pos === false) {
                continue;
            }

            $files[] = [
                'size' => (int) substr($line, 0, $pos),
                'path' => substr($line, $pos + 1),
            ];
        }

        return $files;
    }
}
