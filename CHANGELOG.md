# Changelog — ETechFlow Product Warning

All notable changes to this module. Adheres to [Semantic Versioning](https://semver.org/).

---

## [1.1.0] — 2026-06-05 — Stripe portal licensing + admin gate + storefront gating

### Added

- **Stripe portal subscription licensing.** Adds the SP-XXXX subscription-key
  flow — same pattern shipped on `ETechFlow_BackorderEtaDisplay` v1.3.0,
  `ETechFlow_NextDayEligibility` v1.8.0, `ETechFlow_ShippingTableRates` v1.2.0,
  and `ETechFlow_Faq` v1.1.0. Three plan tiers (Starter $9/mo, Professional
  $19/mo, Enterprise $49/mo) with in-admin Stripe Checkout, automatic key
  activation, portal-validated server-IP enforcement, IP-block auto-restore,
  and 48-hour offline grace when the portal is unreachable. HMAC per-module +
  bundle keys (LICENSING_PROTOCOL.md) continue to work for offline / bundle
  activation.

- **`Model/LicenseValidator.php`** — upgraded constructor from 2-arg to
  5-arg (`ScopeConfigInterface`, `StoreManagerInterface`, `CacheInterface`,
  `Curl`, `WriterInterface`). Adds tri-state `validateViaPortal(): ?bool`
  per the enforcement contract. Preserves `MODULE_ID = 'product-warning'`
  and the existing `SECRET_FRAGMENTS` byte-for-byte so v1.0.x HMAC keys
  remain valid.

- **License gate page** under **Catalog → Inventory Warning → License & Plans**
  with dark plan-cards UI and a Stripe Checkout button. Visiting any
  warning-admin URL without a valid licence redirects here.

- **Module Status banner** (5-state) at the top of Stores → Configuration →
  ETECHFLOW → Product Warning. Tells the merchant exactly why the module is
  locked (or that it's active).

- **Admin gating plugin** (`Plugin/Adminhtml/LicenseGatePlugin.php`) — every
  admin Warning CRUD controller + every assignment-picker controller (9 in
  total) redirects to the license gate when not licensed.

- **Storefront gating** — `Block/Frontend/Notice.php` now checks
  `LicenseValidator::isValid()` before calling the resolver. When unlicensed,
  `getWarnings()` returns an empty array and the PDP renders no banner. The
  block stays in the layout but produces zero output — the storefront
  degrades silently rather than crashing or showing a "module locked" message
  to customers.

- **`<payment>` config group** for Stripe `sk_test` / `sk_live` / publishable
  key / currency (Encrypted backend model on the secret key).

- **`<license>` config group** expanded — adds `issued_key` (Encrypted audit
  field), `issued_at` (timestamp), `ip_blocked` (auto-managed flag),
  `portal_url`, and `bundle_license_key` (Encrypted, mapped to the shared
  `etechflow_bundle/license/license_key` path).

### Changed

- **Menu restructured.** `Catalog → Inventory Warning` is now a parent node
  with two children: **Manage Warnings** (the existing grid) and **License &
  Plans** (the new gate). Existing bookmarks to `/etechflow_warning/warning/index`
  continue to work unchanged.

- **Module Status backend block** added (`Block/Adminhtml/System/Config/ModuleStatus.php`)
  to render the always-expanded 5-state banner on the Stores → Config page.

### Migration

```
composer update etechflow/module-product-warning
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

After upgrade, on a production host the module is **locked by default**.
Go to **Catalog → Inventory Warning → License & Plans** and either paste an
existing SP-XXXX / HMAC / bundle key, or click "Select Plan & Pay" to buy
a subscription via Stripe.

Dev hosts (localhost, `*.test`, `*.local`, `staging.*`, `*.ngrok-free.dev`,
etc.) auto-bypass licensing. Production hosts that aren't auto-detected can
opt out with **Production Environment = No**.

### Notes

- `License Portal URL` defaults to
  `https://license-service.etechflow.com/license/validate`
  (the eTechFlow portal). For production, change this when eTechFlow
  publishes the final portal URL.
- Portal IP-revoke + suspend lock the module within ~60 seconds. Re-activating
  in the portal restores the module within the same window via the
  `issued_key` auto-restore.

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
