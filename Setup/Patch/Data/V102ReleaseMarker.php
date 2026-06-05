<?php

declare(strict_types=1);

namespace ETechFlow\ProductWarning\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * No-op release marker for v1.0.2.
 *
 * v1.0.2 fixes two packaging bugs and removes the default-data seed.
 *
 * 1. Layout + UI component filenames were stuck on the pre-rename
 *    "keystation" prefix. Magento builds admin layout handles from
 *    `{route_id}_{controller}_{action}` and resolves UI components by
 *    filename — both lookups failed silently, leaving the admin grid
 *    page blank (200 OK, page chrome rendered, no content). Renamed:
 *
 *      view/adminhtml/layout/
 *        keystation_warning_warning_index.xml     → etechflow_*
 *        keystation_warning_warning_edit.xml      → etechflow_*
 *        keystation_warning_warning_newaction.xml → etechflow_*
 *      view/adminhtml/ui_component/
 *        keystation_warning_listing.xml → etechflow_warning_listing.xml
 *        keystation_warning_form.xml    → etechflow_warning_form.xml
 *
 *    All file *contents* were already correct — only the names were
 *    stale, so the fix is a pure rename + version bump. No code or
 *    XML body changes.
 *
 * 2. Removed `Setup/Patch/Data/SeedDefaultWarnings.php`. New installs
 *    now land on an empty warnings grid. The old patch hard-coded 3
 *    automotive-locksmith-flavoured demo rows that were noise for
 *    every other type of merchant. Existing installs that already
 *    seeded keep their rows (patch already recorded in patch_list);
 *    delete them from the admin grid if no longer wanted.
 *
 *    `V101ReleaseMarker::getDependencies()` previously returned
 *    `[SeedDefaultWarnings::class]` — removed so new installs do not
 *    fatal on the missing dependency.
 *
 * No schema change. Marker patch only.
 */
class V102ReleaseMarker implements DataPatchInterface
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
        return [V101ReleaseMarker::class];
    }
}
