<?php
// Copyright 1999-2026. WebPros International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer\Tests;

use PHPUnit\Framework\TestCase;
use PleskExt\DiskspaceUsageViewer\Helper;

class HelperTest extends TestCase
{
    // Holds symbolic links to protected directories; removed after each test.
    private string $linkDir;

    protected function setUp(): void
    {
        $this->linkDir = sys_get_temp_dir() . '/dsuv-test-' . bin2hex(random_bytes(6));
        mkdir($this->linkDir);
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->linkDir) as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                unlink($this->linkDir . '/' . $entry);
            }
        }
        rmdir($this->linkDir);
    }

    private static function isSystemFile(string $path): bool
    {
        $method = new \ReflectionMethod(Helper::class, 'isSystemFile');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        return $method->invoke(null, $path);
    }

    /**
     * @dataProvider cleanPathProvider
     */
    public function testCleanPath(string $path, string $expected): void
    {
        $this->assertSame($expected, Helper::cleanPath($path));
    }

    public function cleanPathProvider(): array
    {
        return [
            'already clean' => ['/etc/passwd', '/etc/passwd'],
            'trailing slash' => ['/etc/', '/etc'],
            'double slashes' => ['//etc//passwd', '/etc/passwd'],
            'dot and parent segments are dropped' => ['/etc/./../etc/passwd', '/etc/etc/passwd'],
            'backslashes' => ['\\etc\\passwd', '/etc/passwd'],
            'surrounding spaces' => ['  /etc/passwd  ', '/etc/passwd'],
            'relative path' => ['etc/passwd', '/etc/passwd'],
            'empty path' => ['', '/'],
        ];
    }

    /**
     * @dataProvider childPathProvider
     */
    public function testChildPath(string $dir, string $name, string $expected): void
    {
        $this->assertSame($expected, Helper::childPath($dir, $name));
    }

    public function childPathProvider(): array
    {
        return [
            'plus sign in root' => ['/', 'lost+found', '/lost+found'],
            'plus sign in file name' => ['/var/www/vhosts/example.com/httpdocs', 'a+b.txt', '/var/www/vhosts/example.com/httpdocs/a+b.txt'],
            'percent sequence in file name' => ['/var/www', 'c%20d.txt', '/var/www/c%20d.txt'],
            'space in file name' => ['/var/www', 'e f.txt', '/var/www/e f.txt'],
            'trailing slash in directory' => ['/var/www/', 'a+b', '/var/www/a+b'],
        ];
    }

    /**
     * @dataProvider protectedPathProvider
     */
    public function testProtectedPathIsSystemFile(string $path): void
    {
        $this->assertTrue(self::isSystemFile($path));
    }

    public function protectedPathProvider(): array
    {
        return [
            'root' => ['/'],
            'tree root' => ['/etc'],
            'file in tree' => ['/etc/passwd'],
            'nested file in tree' => ['/etc/ssh/sshd_config'],
            'usr subdirectory' => ['/usr/local/bin'],
            'plesk tree root' => ['/opt/psa'],
            'file in plesk tree' => ['/opt/psa/admin/conf/panel.ini'],
            'plesk tree through usr' => ['/usr/local/psa/var'],
            'plesk data tree' => ['/var/lib/psa/dumps'],
            'plesk packages tree' => ['/opt/plesk/php'],
            'root home content' => ['/root/.ssh/authorized_keys'],
            'home root' => ['/home'],
            'var root' => ['/var'],
            'document root' => ['/var/www/vhosts/example.com/httpdocs'],
            'swap file' => ['/pleskswap'],
            // The directories below do not exist, so only cleanPath() can match them.
            'trailing slash' => ['/etc/no-such-dir/file/'],
            'double slashes' => ['//etc//no-such-dir//file'],
            'dot segments' => ['/etc/./no-such-dir/./file'],
            'parent segments' => ['/etc/no-such-dir/../file'],
            'backslashes' => ['\\etc\\no-such-dir\\file'],
        ];
    }

    /**
     * @dataProvider deletablePathProvider
     */
    public function testDeletablePathIsNotSystemFile(string $path): void
    {
        $this->assertFalse(self::isSystemFile($path));
    }

    public function deletablePathProvider(): array
    {
        return [
            'file in home' => ['/home/user/backup.tar.gz'],
            'file in document root' => ['/var/www/vhosts/example.com/httpdocs/big.zip'],
            'directory in document root' => ['/var/www/vhosts/example.com/httpdocs/cache'],
            'log file' => ['/var/log/syslog.1'],
            'sibling of plesk tree' => ['/opt/psa-backup'],
            'prefix of tree name' => ['/etcetera'],
            'prefix of usr' => ['/usr2/file'],
        ];
    }

    public function testPathThroughLinkToProtectedDirectoryIsSystemFile(): void
    {
        symlink('/usr/share', $this->linkDir . '/share');

        // The last element does not exist, so only the parent resolution can find the link.
        $this->assertTrue(self::isSystemFile($this->linkDir . '/share/no-such-file'));
    }

    public function testLinkToProtectedFileIsNotSystemFile(): void
    {
        symlink('/usr/bin/env', $this->linkDir . '/env');

        $this->assertFalse(self::isSystemFile($this->linkDir . '/env'));
    }

    public function testLinkToProtectedDirectoryIsNotSystemFile(): void
    {
        symlink('/usr/share', $this->linkDir . '/share');

        $this->assertFalse(self::isSystemFile($this->linkDir . '/share'));
    }

    public function testFileOutsideProtectedDirectoriesIsNotSystemFile(): void
    {
        touch($this->linkDir . '/plain.txt');

        $this->assertFalse(self::isSystemFile($this->linkDir . '/plain.txt'));
    }
}
