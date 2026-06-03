# Installation Guide — Etechflow_ProductWarning

Two install paths: **manual ZIP** (easiest) or **Composer** (recommended for teams).

---

## Prerequisites

- Magento 2.4.4 or newer (tested through 2.4.8)
- PHP 8.1, 8.2, or 8.3
- SSH access to your Magento root, or your hosting provider's deploy mechanism

---

## Option A — Manual ZIP install

```bash
# 1. Maintenance mode (optional, safer for production)
bin/magento maintenance:enable

# 2. Drop the module into place
unzip etechflow-module-product-warning-1.0.0.zip -d <magento-root>/
#    Layout: <magento-root>/app/code/Etechflow/ProductWarning/

# 3. Enable + migrate
bin/magento module:enable Etechflow_ProductWarning
bin/magento setup:upgrade

# 4. Production stores only
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f

# 5. Clear caches
bin/magento cache:flush

# 6. Leave maintenance mode
bin/magento maintenance:disable
```

---

## Option B — Composer install

```bash
# Once published to your private composer repository:
composer require etechflow/module-product-warning:^1.0
bin/magento module:enable Etechflow_ProductWarning
bin/magento setup:upgrade
bin/magento setup:di:compile        # production-mode only
bin/magento cache:flush
```

---

## Verify the install

```bash
# 1. Module enabled?
bin/magento module:status Etechflow_ProductWarning
# Expected: "Module is enabled"

# 2. DB tables created?
mysql -e "SHOW TABLES LIKE 'etechflow_warning%'"
# Expected:
#   etechflow_warning
#   etechflow_warning_category
#   etechflow_warning_product

# 3. Admin menu present?
# Log into admin → Catalog sidebar → "Inventory Warning" entry should exist

# 4. Storefront block injected?
curl -s https://your-store.example.com/<any-product-page> | grep "epw-stack"
# If a product has a matching warning, the banner div is in the HTML
```

Open any product detail page that's assigned to one of the seeded demo warnings — the banner appears above the price.

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| `Module 'Etechflow_ProductWarning' has been already disabled` | Re-enable: `bin/magento module:enable Etechflow_ProductWarning` |
| `Cannot redeclare class …` after upgrade | Stale generated code. `rm -rf generated/code generated/metadata && bin/magento setup:di:compile` |
| Admin menu missing | Re-log into admin. Then `bin/magento cache:flush config` |
| Banner not showing on PDP | Check three things: (1) warning is **active**, (2) at least one matching category OR product is assigned, (3) you've flushed `full_page` cache (`bin/magento cache:flush full_page`) |
| Banner shows but color is wrong | Hex picker only stores 6-digit values. Edit the warning, re-pick the color, save. |
| Demo data didn't seed | The patch only runs on first install. To force re-seed: `DELETE FROM setup_module WHERE module='Etechflow_ProductWarning';` then `bin/magento setup:upgrade` |

If you hit something not in this table, send `var/log/exception.log` (last 50 lines) and `var/log/system.log` to support@etechflow.com.

---

## Upgrading from `Keystation_ProductWarning`

If your store previously had the `Keystation_ProductWarning` module (this module is the renamed / portable evolution of it):

1. Take a backup.
2. Export the old data:
   ```sql
   CREATE TABLE etechflow_warning AS SELECT * FROM keystation_warning;
   CREATE TABLE etechflow_warning_category AS SELECT * FROM keystation_warning_category;
   CREATE TABLE etechflow_warning_product AS SELECT * FROM keystation_warning_product;
   ```
3. Disable + remove the old module:
   ```bash
   bin/magento module:disable Keystation_ProductWarning
   rm -rf app/code/Keystation/ProductWarning
   ```
4. Install this module (see Option A above).
5. After `setup:upgrade` creates the new tables, the data you copied in step 2 is already there.
6. Remove any theme template overrides that called `Keystation\ProductWarning\Model\WarningResolver` — this module renders its own banner now.
