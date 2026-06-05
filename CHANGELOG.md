# Changelog — ETechFlow Product Warning

All notable changes to this module. Adheres to [Semantic Versioning](https://semver.org/).

---

## [1.0.2] — 2026-06-05 — Fix blank admin grid + remove default-data seed

### Fixed

- **Admin grid + edit form rendered as blank pages.** Five XML files were
  still named with the pre-rename `keystation_*` prefix while the
  module's route id had been changed to `etechflow_warning`. Magento
  builds admin layout handles from `{route_id}_{controller}_{action}`
  and loads UI components by filename — both lookups silently failed
  to match, so `/admin/etechflow_warning/warning/index` returned 200
  with no errors but rendered no grid, no `Add New Warning` button,
  and no form on the edit page.

  Renamed (pure file renames — file *contents* were already correct):

  ```
  view/adminhtml/layout/
    keystation_warning_warning_index.xml     → etechflow_warning_warning_index.xml
    keystation_warning_warning_edit.xml      → etechflow_warning_warning_edit.xml
    keystation_warning_warning_newaction.xml → etechflow_warning_warning_newaction.xml
  view/adminhtml/ui_component/
    keystation_warning_listing.xml → etechflow_warning_listing.xml
    keystation_warning_form.xml    → etechflow_warning_form.xml
  ```

  **Bug class to watch for**: Magento does not error on a missing
  layout handle — it just renders the page chrome without the
  declared content. Trivial to ship to production undetected. Catch
  it with: `bin/magento dev:layout:export-handles | grep <route_id>`
  after every module rename.

### Removed

- **`Setup/Patch/Data/SeedDefaultWarnings.php`**. New installs now land
  on an empty warnings grid. The old patch hard-coded 3
  automotive-locksmith-flavoured demo rows (Unprogrammed Remote /
  Repair Case / Un-cut Blade) that were noise for every other type of
  merchant.

  **Existing v1.0.0 / v1.0.1 installs**: rows already in
  `etechflow_warning` are not touched on upgrade — the patch is
  already recorded in `patch_list`, so setup:upgrade does nothing.
  Delete the seeded rows from the admin grid if no longer wanted; they
  will not come back on future upgrades.

- **`V101ReleaseMarker::getDependencies()`** previously returned
  `[SeedDefaultWarnings::class]`. Removed so a new install of v1.0.2
  doesn't fatal on the missing class.

### Added

- **`Setup/Patch/Data/V102ReleaseMarker.php`** — always-a-patch
  discipline marker. Depends on `V101ReleaseMarker` so patches run
  in correct version order. No schema change.

### Migration

```bash
composer update etechflow/module-product-warning
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

No schema migration, no data migration. **Strongly recommend upgrade
for any v1.0.0 / v1.0.1 install** — the blank-grid bug makes the
admin UI unusable.

---

## [1.0.1] — 2026-06-03 — Critical: Fix class-load fatal in PDP Notice block

### Fixed

- **`Block/Frontend/Notice.php` class-load fatal**: the `$resolver`
  property was declared `private`, but Magento's `\Magento\Framework\
  View\Element\Template` parent class declares its own `$resolver` as
  `protected`. PHP requires equal-or-weaker visibility on inherited
  property overrides, so v1.0.0 caused `setup:di:compile` and PDP
  render to fatal with:

  ```
  Fatal error: Access level to ETechFlow\ProductWarning\Block\Frontend\
  Notice::$resolver must be protected (as in class Magento\Framework\
  View\Element\Template) or weaker
  ```

  Caught on first local Docker install — **`php -l` does NOT detect
  this class of bug** because it's a class-load-time check, not a
  syntax check. Same bug class flagged in the eTechFlow memory after
  similar issues in earlier modules.

  **Fix**: renamed the property `$resolver` → `$warningResolver`
  (along with the constructor parameter + every `$this->resolver`
  reference). Side-steps the collision entirely without shadowing the
  parent's actual layout resolver.

- **Same class of bug to watch for elsewhere**: any Magento block
  that extends `Template` or `AbstractBlock` and declares a `private`
  property that shadows an inherited `protected` one. Most-common
  offenders: `$resolver`, `$_resolver`, `$escaper`, `$_escaper`,
  `$jsLayout`, `$_template`. `php -l` won't catch them. `setup:di:
  compile` will, on every install.

### Added

- **`Setup/Patch/Data/V101ReleaseMarker.php`** — always-a-patch
  discipline marker. Depends on `SeedDefaultWarnings` so patches run
  in correct version order.

### Migration

```bash
composer require etechflow/module-product-warning:^1.0.1
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

Pre-flight check:
```sql
SELECT module, schema_version, data_version FROM setup_module
WHERE module='ETechFlow_ProductWarning';
```
Both should read `1.0.1`. If `data_version` is stale, re-run
`setup:upgrade` — do NOT flush cache yet.

Anyone who installed v1.0.0 hit the class-load fatal on the first
PDP render or `setup:di:compile`. v1.0.1 fixes it. **Strongly
recommend upgrade for any v1.0.0 installs.**

---


### Added

- **Admin module** under `Catalog → Inventory Warning`
  - Grid + edit form (Magento Ui Components)
  - Hex color picker, active toggle, sort order
  - Multi-tree category assignment picker
  - Multi-select product assignment picker (searchable by name / SKU)
  - ACL resource `Etechflow_ProductWarning::warnings`
- **Storefront PDP rendering** — auto-injected via this module's own
  `view/frontend/layout/catalog_product_view.xml`. No theme override required.
- **WarningResolver** PHP API for stores that want to render warnings
  elsewhere (cart, emails, listing pages, etc.)
- **DB schema** (`etc/db_schema.xml`) — 3 tables:
  - `etechflow_warning` — master record (id, name, message, color, active, sort_order, timestamps)
  - `etechflow_warning_category` — category assignment pivot
  - `etechflow_warning_product` — product assignment pivot
- **Demo data installer** patch — seeds 3 example warnings on first install
- **Scoped frontend CSS** — `.epw-*` namespace, inline `<style>` block
- **Zero JavaScript on frontend** — pure server-rendered HTML
- **Documentation bundle**
  - README, INSTALL, USAGE, COMPATIBILITY, CONFIGURATION, UNINSTALL, CHANGELOG, LICENSE

### Compatibility

- Magento 2.4.4 – 2.4.8
- PHP 8.1, 8.2, 8.3
- Hyvä Theme + Luma + custom themes
- Adobe Commerce + Magento Open Source + Mage-OS

### Migration

If you previously had the original `Keystation_ProductWarning` module on a
store, follow the data-copy SQL in `INSTALL.md → Upgrading from
Keystation_ProductWarning` to bring the data across.

---

[1.0.0]: https://github.com/etechflow/module-product-warning/releases/tag/v1.0.0
