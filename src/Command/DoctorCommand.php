<?php

declare(strict_types=1);

namespace cweagans\Composer\Command;

use Composer\DependencyResolver\Operation\UninstallOperation;
use cweagans\Composer\Capability\Patcher\PatcherProvider;
use cweagans\Composer\Patcher\PatcherInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DoctorCommand extends PatchesCommandBase
{
    protected function configure(): void
    {
        $this->setName('patches-doctor');
        $this->setDescription('Run a series of checks to ensure that Composer Patches has a usable environment.');
        $this->setAliases(['pd']);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plugin = $this->getPatchesPluginInstance();
        if (is_null($plugin)) {
            return 1;
        }
        $plugin->loadLockedPatches();

        $composer = $this->requireComposer();
        $io = $this->getIO();
        $plugin_manager = $composer->getPluginManager();
        $capabilities = $plugin_manager->getPluginCapabilities(
            PatcherProvider::class,
            ['composer' => $composer, 'io' => $this->getIO()]
        );

        $suggestions = [];

        $io->write("");
        $io->write("<info>System information</info>");
        $io->write("================================================================================");
        $io->write(
            str_pad("Composer version: ", 72) . "<info>" . str_pad(
                $this->getApplication()->getVersion(),
                8,
                " ",
                STR_PAD_LEFT
            ) . "</info>"
        );

        $system_issues = false;

        if (!str_starts_with($this->getApplication()->getVersion(), "2")) {
            $system_issues = true;
        }

        $io->write(str_pad("PHP version: ", 72) . "<info>" . str_pad(PHP_VERSION, 8, " ", STR_PAD_LEFT) . "</info>");
        if (PHP_VERSION_ID < 80000) {
            $system_issues = true;
        }

        if ($system_issues) {
            $suggestions[] = [
                "message" => "Upgrade Composer and/or PHP to a more modern/supported version",
                "link" => "https://docs.cweagans.net/composer-patches/troubleshooting/guide#upgrade-system-software"
            ];
        }

        $io->write("");
        $io->write("<info>Available patchers</info>");
        $io->write("================================================================================");
        $has_usable_patcher = false;
        foreach ($capabilities as $capability) {
            /** @var PatcherProvider $capability */
            $newPatchers = $capability->getPatchers();
            foreach ($newPatchers as $i => $patcher) {
                if (!$patcher instanceof PatcherInterface) {
                    throw new \UnexpectedValueException(
                        'Plugin capability ' . get_class($capability) . ' returned an invalid value.'
                    );
                }

                $usable = $patcher->canUse();
                $has_usable_patcher = $has_usable_patcher || $usable;
                $io->write(
                    str_pad(get_class($patcher) . " usable: ", 77) .
                    ($usable ? "<info>yes</info>" : " no")
                );
            }
        }

        $io->write(
            str_pad(
                "Has usable patchers: ",
                77
            ) . ($has_usable_patcher ? "<info>yes</info>" : " <error>no</error>")
        );

        if (!$has_usable_patcher) {
            $suggestions[] = [
                "message" => "Install software dependencies for applying patches",
                "link" => "https://docs.cweagans.net/composer-patches/troubleshooting/guide#install-patching-software"
            ];
        }


        $io->write("");
        $io->write("<info>Common configuration issues</info>");
        $io->write("================================================================================");
        $preferred_install_issues = false;
        $pi = $composer->getConfig()->get('preferred-install');
        $io->write(
            str_pad("preferred-install is set:", 77) . (is_null($pi) ? " <warning>no</warning>" : "<info>yes</info>")
        );

        if (is_null($pi)) {
            $preferred_install_issues = true;
        }

        if (is_string($pi)) {
            $io->write(
                str_pad("preferred-install set to 'source' for all/some packages:", 77) .
                ($pi === "source" ? "<info>yes</info>" : " <warning>no</warning>")
            );
        }

        if (is_string($pi) && $pi !== "source") {
            $preferred_install_issues = true;
        }

        if (is_array($pi)) {
            $patched_packages = $plugin->getPatchCollection()->getPatchedPackages();
            foreach ($patched_packages as $package) {
                if (in_array($package, array_values($pi))) {
                    $io->write(
                        str_pad("preferred-install set to 'source' for $package:", 77) . "<info>yes</info>"
                    );
                    continue;
                }

                foreach ($pi as $pattern => $value) {
                    $pattern = strtr($pattern, ['*' => '.*', '/' => '\/']);
                    if (preg_match("/$pattern/", $package)) {
                        $io->write(
                            str_pad("preferred-install set to 'source' for $package:", 77) .
                            ($value === "source" ? "<info>yes</info>" : " <warning>no</warning>")
                        );

                        if ($value !== "source") {
                            $preferred_install_issues = true;
                        }

                        break 2;
                    }
                }

                $preferred_install_issues = true;
            }
        }

        if ($preferred_install_issues) {
            $suggestions[] = [
                "message" => "Setting 'preferred-install' to 'source' either globally or for each patched dependency " .
                    "is highly recommended for consistent results",
                "link" =>
                    "https://docs.cweagans.net/composer-patches/troubleshooting/guide#set-preferred-install-to-source"
            ];
        }

        $has_http_urls = false;
        foreach ($plugin->getPatchCollection()->getPatchedPackages() as $package) {
            foreach ($plugin->getPatchCollection()->getPatchesForPackage($package) as $patch) {
                if (str_starts_with($patch->url, 'http://')) {
                    $has_http_urls = true;
                    break 2;
                }
            }
        }

        $io->write(
            str_pad("has plain http patch URLs:", 77) . ($has_http_urls ? "<warning>yes</warning>" : " <info>no</info>")
        );
        if ($has_http_urls) {
            $sh = $composer->getConfig()->get('secure-http');
            $io->write(
                str_pad('secure-http disabled:', 77) . ($sh ? " <error>no</error>" : "<info>yes</info>")
            );

            if ($sh) {
                $suggestions[] = [
                    "message" => "Patches must either be downloaded securely or 'secure-http' must be disabled",
                    "link" =>
                        "https://docs.cweagans.net/composer-patches/troubleshooting/guide#download-patches-securely"
                ];
            }
        }

        if (!empty($suggestions)) {
            $io->write("");
            $io->write("<info>Suggestions</info>");
            $io->write("================================================================================");
            foreach ($suggestions as $suggestion) {
                $io->write(" - " . $suggestion['message']);
                $io->write("   More information: " . $suggestion['link']);
            }
        }


        return 0;
    }
}
