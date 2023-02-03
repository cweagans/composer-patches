<?php

namespace cweagans\Composer\Tests\Unit;

use Codeception\Test\Unit;
use Composer\Composer;
use Composer\Config;
use Composer\IO\NullIO;
use cweagans\Composer\Downloader\ComposerDownloader;
use cweagans\Composer\Downloader\Exception\HashMismatchException;
use cweagans\Composer\Patch;

class ComposerDownloaderTest extends Unit
{
    public function setUp(): void
    {
        parent::setUp();

        // Needed so we get full coverage in the ComposerDownloader class.
        $patches_dir = sys_get_temp_dir() . '/composer-patches/';
        if (is_dir($patches_dir)) {
            foreach (glob($patches_dir . '*.patch') as $patch) {
                unlink($patch);
            }
            rmdir($patches_dir);
        }
    }

    /**
     * Test the composer downloader.
     */
    public function testDownloader()
    {
        $composer = new Composer();
        $composer->setConfig(new Config());
        $io = new NullIO();

        $downloader = new ComposerDownloader($composer, $io);

        $patch = new Patch();
        $patch->package = "placeholder";
        $patch->description = "test patch";
        $patch->url = "https://patch-diff.githubusercontent.com/raw/cweagans/composer-patches-testrepo/pull/1.patch";

        $downloader->download($patch);

        $sha = '0ec56d93aed447775aa70e55b5530f401cb3a59facd8ce20301c1d007461f1bf';
        $this->assertNotEmpty($patch->localPath);
        $this->assertEquals($sha, $patch->sha256);

        $path = $patch->localPath;

        // Downloading the same patch again shouldn't throw an exception or
        // anything, but it won't re-download. Should be the same path.
        $downloader->download($patch);
        $this->assertEquals($path, $patch->localPath);
        $this->assertEquals($sha, $patch->sha256);

        // If the patch sha256 is set to something that doesn't match the file,
        // an exception should be thrown. (clearing localPath to force re-download
        // and re-check of sha256.
        $patch->localPath = '';
        $patch->sha256 = 'an incorrect hash';
        $this->expectException(HashMismatchException::class);
        $downloader->download($patch);
    }

    /**
     * Test local file "download".
     */
    public function testLocalFile()
    {
        $composer = new Composer();
        $composer->setConfig(new Config());
        $io = new NullIO();

        $downloader = new ComposerDownloader($composer, $io);

        $patch = new Patch();
        $patch->package = "placeholder";
        $patch->description = "test patch";
        $patch->url = codecept_data_dir('localfile.patch');

        $downloader->download($patch);

        $sha = '0ec56d93aed447775aa70e55b5530f401cb3a59facd8ce20301c1d007461f1bf';
        $this->assertNotEmpty($patch->localPath);
        $this->assertEquals($sha, $patch->sha256);
    }
}
