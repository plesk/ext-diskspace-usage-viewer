<?php
// Copyright 1999-2026. WebPros International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class DirectoryListing
{
    // The names from scanDir() are used as they are: they are not URL-encoded since Plesk 18.0.67.
    public static function items(\pm_FileManager $fileManager, string $dir): array
    {
        $items = [];

        foreach ($fileManager->scanDir($dir, true) as $basename) {
            $path = Helper::childPath($dir, $basename);

            try {
                $isDir = $fileManager->isDir($path);
                $mtime = @filemtime($path);

                if ($mtime === false) {
                    $mtime = 0;
                }
            } catch (\PleskUtilException $e) {
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

        return $items;
    }
}
