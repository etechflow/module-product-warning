# Etechflow_ProductWarning — Product Warning Banners for Magento 2

Per-product and per-category warning banners on the storefront PDP. Admin can create coloured notices ("This item ships from a remote warehouse", "Cutting service unavailable for this key", "Backorder — 7 day lead time", etc.), assign them to specific products or whole categories, and the storefront auto-renders them above the price.

- **Version:** 1.0.0
- **Package:** `etechflow/module-product-warning`
- **Magento:** 2.4.4 – 2.4.8 (newer versions should work; report incompatibilities)
- **PHP:** 8.1, 8.2, 8.3
- **License:** proprietary (`LICENSE.txt`) — Commercial licenses available at <https://etechflow.com>
- **Vendor:** ETechFlow — https://etechflow.com

---

## What you get

- **Admin grid + edit form** under `Catalog → Inventory Warning`
  - Warning name (admin-only label)
  - Warning message (customer-facing text)
  - Color (hex picker, used for the banner tint + left border)
  - Active toggle, sort order
  - Assigned categories (multi-tree picker — assigns to every product in those categories)
  - Assigned products (search + multi-select picker)
- **Storefront PDP rendering** (auto-injected via this module's own layout XML — no theme override required)
  - Stacks multiple warnings if a product matches several
  - Tints background + border using the warning's color
  - Scoped CSS namespace (`.epw-*`) — won't collide with theme classes
- **Resolver model** (`ETechFlow\ProductWarning\Model\WarningResolver::getForProduct($product)`) for stores that want to render warnings in a custom location (e.g., listing pages, cart, emails)
- **Theme-agnostic by design** — works on Hyvä, Luma, Mage-OS adminhtml, and any custom theme

---

## Theme compatibility

| Theme | Status |
|---|---|
| **Magento 2 default (Luma / Blank)** | ✅ Works — vanilla CSS, no Knockout dependency |
| **Hyvä Theme** | ✅ Works — no Alpine / Tailwind required |
| **Custom themes** | ✅ Works — uses standard `product.info.main` container which exists in every theme |
| **Mage-OS forks** | ✅ Works |
| **Adobe Commerce** | ✅ Works |
| **Headless / PWA Studio** | ⚠️ Storefront template bypassed; use the `WarningResolver` PHP API or expose a REST endpoint to surface the warnings in your headless front end |

See `COMPATIBILITY.md` for the design choices that make this portable.

---

## Quick start

```bash
# 1. Extract into your Magento root
unzip etechflow-module-product-warning-1.0.0.zip -d <magento-root>/

# 2. Enable + migrate
bin/magento module:enable Etechflow_ProductWarning
bin/magento setup:upgrade
bin/magento setup:di:compile      # production-mode only
bin/magento cache:flush

# 3. Visit the admin
open https://your-store.example.com/admin/etechflow_warning/warning/index
# (or: Admin sidebar → Catalog → Inventory Warning)
```

A fresh install starts with an empty warnings grid. Click **Add New Warning**, set name + message + colour, assign it to a product or category, and reload the PDP — the banner renders above the price.

---

## Documentation index

| File | Purpose |
|---|---|
| `README.md` | Overview, features, compatibility (this file) |
| `INSTALL.md` | Manual + Composer install + verification + troubleshooting |
| `USAGE.md` | Admin walk-through — creating warnings, assigning to products / categories |
| `CONFIGURATION.md` | Module-level customization options + how to move the banner block elsewhere |
| `COMPATIBILITY.md` | Theme + Magento + PHP matrix and the choices that keep it portable |
| `CHANGELOG.md` | Version history |
| `UNINSTALL.md` | Clean removal (disable / drop tables / remove media) |
| `LICENSE.txt` | proprietary license text |

---

## Support

- Email: support@etechflow.com
- Include: Magento version, PHP version, active theme, steps to reproduce, screenshot of the PDP with browser inspector open.

---

## License

proprietary — free for commercial and non-commercial use. See `LICENSE.txt`.
