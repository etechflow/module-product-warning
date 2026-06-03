# Uninstall — Etechflow_ProductWarning

> ⚠️ Back up your database before running the removal SQL. Warnings cannot be recovered without a backup.

---

## Option A — Disable only (preserve data)

```bash
bin/magento module:disable Etechflow_ProductWarning
bin/magento cache:flush
```

The admin menu disappears, PDP banner stops rendering, but `etechflow_warning*` tables stay intact. Re-enable any time.

---

## Option B — Full removal

### 1. Disable

```bash
bin/magento module:disable Etechflow_ProductWarning
bin/magento cache:flush
```

### 2. Drop tables

```sql
DROP TABLE IF EXISTS etechflow_warning_product;
DROP TABLE IF EXISTS etechflow_warning_category;
DROP TABLE IF EXISTS etechflow_warning;
DELETE FROM setup_module WHERE module = 'Etechflow_ProductWarning';
```

### 3. Remove the code

ZIP install:
```bash
rm -rf <magento-root>/app/code/Etechflow/ProductWarning
rmdir <magento-root>/app/code/Etechflow 2>/dev/null    # only if empty
```

Composer install:
```bash
composer remove etechflow/module-product-warning
```

### 4. Rebuild Magento

```bash
bin/magento setup:upgrade
bin/magento setup:di:compile      # production-mode only
bin/magento cache:flush
```

### 5. Verify

```bash
bin/magento module:status Etechflow_ProductWarning
# Expected: not installed / not registered

mysql -e "SHOW TABLES LIKE 'etechflow_warning%'"
# Expected: empty result
```
