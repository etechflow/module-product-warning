<?php

declare(strict_types=1);

namespace ETechFlow\ProductWarning\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * No-op release marker for v1.0.1.
 *
 * v1.0.1 fixes a fatal class-load bug in Block/Frontend/Notice.php where
 * the WarningResolver property was declared `private` despite Magento's
 * Template parent class declaring its own `$resolver` as `protected`.
 * PHP requires equal-or-weaker visibility on inherited property
 * overrides, so the v1.0.0 declaration caused setup:di:compile to fatal
 * (caught on first local Docker install — php -l doesn't detect this
 * class of bug because it's a class-load-time check).
 *
 * Renamed the property to $warningResolver to side-step the collision
 * entirely (cleaner than relaxing visibility to protected, which would
 * shadow the parent's actual layout resolver).
 *
 * No schema change. Marker patch only.
 */
class V101ReleaseMarker implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        return $this;
    }

    public function getAliases(): array
    {
        return [];
    }

    public static function getDependencies(): array
    {
        return [SeedDefaultWarnings::class];
    }
}
