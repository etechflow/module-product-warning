# Changelog — Etechflow_ProductWarning

## [1.0.0] — 2026-05-20

First public release as a standalone, theme-agnostic Magento 2 module.

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
