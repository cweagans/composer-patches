<?php

namespace cweagans\Composer\Downloader;

use Composer\Util\Filesystem;
use Composer\Util\HttpDownloader;
use Composer\Package\PackageInterface;
use cweagans\Composer\Resolver\Dependencies;
use cweagans\Composer\Downloader\Exception\HashMismatchException;
use cweagans\Composer\Patch;

class ComposerDownloader extends DownloaderBase
{
    /**
     * @inheritDoc
     */
    public function download(Patch $patch): void
    {
        static $downloader;
        if (is_null($downloader)) {
            $downloader = new HttpDownloader($this->io, $this->composer->getConfig());
        }

        // Don't need to re-download a patch if it has already been downloaded.
        if (isset($patch->localPath) && !empty($patch->localPath)) {
            return;
        }

        $patches_dir = sys_get_temp_dir() . '/composer-patches/';
        $filename = uniqid($patches_dir) . ".patch";
        if (!is_dir($patches_dir)) {
            mkdir($patches_dir);
        }

        // Patch inside the package.
        if (isset($patch->extra[Dependencies::RELATIVE_PATCH_PROVIDER])) {
            $provider = $patch->extra[Dependencies::RELATIVE_PATCH_PROVIDER];
            if ($package = $this->getPackage($provider)) {
                $dir = sys_get_temp_dir() . \DIRECTORY_SEPARATOR . uniqid($provider);
                try {
                    $dm = $this->composer->getDownloadManager();
                    $dm->download($package, $dir);
                    $dm->install($package, $dir);
                    $newUrl = realpath($dir . \DIRECTORY_SEPARATOR . $patch->url);
                    if (file_exists($newUrl) && str_starts_with($newUrl, realpath($dir))) {
                        copy($newUrl, $filename);
                    }
                } catch (\Exception $e) {
                    // @TODO anything to do here?
                } finally {
                    (new FileSystem())->removeDirectory($dir);
                }
            }
        } else {
            // Patch stored remotely.
            $downloader->copy($patch->url, $filename);
        }
        if (!file_exists($filename)) {
            // @TODO be more vocal about failure?
            return;
        }

        $patch->localPath = $filename;

        $hash = hash_file('sha256', $filename);

        if (!isset($patch->sha256)) {
            $patch->sha256 = $hash;
        } elseif ($hash !== $patch->sha256) {
            throw new HashMismatchException($patch->url, $hash, $patch->sha256);
        }
    }


    private function getPackage(string $packageName): ?PackageInterface
    {
        foreach ($this->composer->getRepositoryManager()->getRepositories() as $repository) {
            try {
                if ($packages = $repository->findPackages($packageName)) {
                    return reset($packages);
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        return null;
    }
}
