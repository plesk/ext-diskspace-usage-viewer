<?php
// Copyright 1999-2026. WebPros International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer\Tests;

use PHPUnit\Framework\TestCase;
use PleskExt\DiskspaceUsageViewer\DirectoryListing;

require_once __DIR__ . '/stubs/PleskUtilException.php';

class DirectoryListingTest extends TestCase
{
    public function testItemsKeepNamesFromScanDirUnchanged(): void
    {
        $names = ['lost+found', 'a+b.txt', 'c%20d.txt', 'e f.txt', 'g%2Bh'];
        $directories = ['/srv/lost+found', '/srv/g%2Bh'];

        $fileManager = $this->createMock(\pm_FileManager::class);
        $fileManager->method('scanDir')->with('/srv', true)->willReturn($names);
        $fileManager->expects($this->exactly(count($names)))
            ->method('isDir')
            ->willReturnCallback(function (string $path) use ($directories): bool {
                return in_array($path, $directories, true);
            });

        $items = DirectoryListing::items($fileManager, '/srv');

        $this->assertSame($names, array_column($items, 'name'));
        $this->assertSame(
            ['/srv/lost+found', '/srv/a+b.txt', '/srv/c%20d.txt', '/srv/e f.txt', '/srv/g%2Bh'],
            array_column($items, 'path')
        );
        $this->assertSame([true, false, false, false, true], array_column($items, 'isDir'));
    }

    /**
     * @dataProvider directoryProvider
     */
    public function testItemsJoinTheDirectoryAndTheName(string $dir, string $name, string $expectedPath): void
    {
        $fileManager = $this->createMock(\pm_FileManager::class);
        $fileManager->method('scanDir')->willReturn([$name]);
        $fileManager->method('isDir')->willReturn(true);

        $items = DirectoryListing::items($fileManager, $dir);

        $this->assertSame([$expectedPath], array_column($items, 'path'));
    }

    public function directoryProvider(): array
    {
        return [
            'root directory' => ['/', 'lost+found', '/lost+found'],
            'trailing slash' => ['/srv/', 'a+b', '/srv/a+b'],
            'no trailing slash' => ['/srv', 'a+b', '/srv/a+b'],
        ];
    }

    public function testItemsSkipNamesThatTheFileManagerCannotCheck(): void
    {
        $fileManager = $this->createMock(\pm_FileManager::class);
        $fileManager->method('scanDir')->willReturn(['ok', 'broken']);
        $fileManager->method('isDir')->willReturnCallback(function (string $path): bool {
            if ($path === '/srv/broken') {
                throw new \PleskUtilException('Cannot check the path');
            }

            return false;
        });

        $items = DirectoryListing::items($fileManager, '/srv');

        $this->assertSame(['ok'], array_column($items, 'name'));
    }
}
