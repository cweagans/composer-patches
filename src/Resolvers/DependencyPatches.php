<?php

/**
 * @file
 * Contains \cweagans\Composer\Resolvers\DependencyPatches.
 */

namespace cweagans\Composer\Resolvers;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\OperationInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\Installer\PackageEvent;
use Composer\Package\PackageInterface;
use cweagans\Composer\Patch;
use cweagans\Composer\PatchCollection;
use cweagans\Composer\Plugin\Patches;

class DependencyPatches extends ResolverBase
{
    /**
     * {@inheritDoc}
     */
    public function resolve(PatchCollection $collection, PackageEvent $event)
    {
        $this->io->write('  - <info>Gathering patches from dependencies.</info>');

        $operations = $event->getOperations();
        foreach ($operations as $operation) {
            if ($operation->getJobType() === 'install' || $operation->getJobType() === 'update') {
                // @TODO handle exception.
                $package = $this->getPackageFromOperation($operation);
                /** @var PackageInterface $extra */
                $extra = $package->getExtra();
                if (isset($extra['patches'])) {
                    $patches = $this->findPatchesInJson($extra['patches']);
                    foreach ($patches as $package => $patch_list) {
                        foreach ($patch_list as $patch) {
                            $collection->addPatch($patch);
                        }
                    }
                }
            }
        }
    }

    /**
     * Get a Package object from an OperationInterface object.
     *
     * @param OperationInterface $operation
     * @return PackageInterface
     * @throws \Exception
     *
     * @todo Will this method ever get something other than an InstallOperation or UpdateOperation?
     */
    protected function getPackageFromOperation(OperationInterface $operation)
    {
        if ($operation instanceof InstallOperation) {
            $package = $operation->getPackage();
        } elseif ($operation instanceof UpdateOperation) {
            $package = $operation->getTargetPackage();
        } else {
            throw new \Exception('Unknown operation: ' . get_class($operation));
        }

        return $package;
    }
}
