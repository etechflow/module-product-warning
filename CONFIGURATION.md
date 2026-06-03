# Configuration — Etechflow_ProductWarning

This module is intentionally lean — it has no Stores → Configuration tree of its own. Everything that's configurable is configured per-warning in the admin grid (see `USAGE.md`).

The only customisations stores commonly want are layout-level — and those are done via theme override layout XML.

---

## Moving the banner to a different position

The banner is injected into `product.info.main`, immediately before `product.info.price`. To move it:

Create this file in your theme: `app/design/frontend/<Vendor>/<theme>/Etechflow_ProductWarning/layout/catalog_product_view.xml`

### Above the product title

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <move element="etechflow.product.warning.notice"
              destination="product.info.main"
              before="product.info.main.title"/>
    </body>
</page>
```

### Below the add-to-cart button

```xml
<body>
    <move element="etechflow.product.warning.notice"
          destination="product.info.main"
          after="product.info.addtocart"/>
</body>
```

### Inside a custom block from another module

```xml
<body>
    <move element="etechflow.product.warning.notice"
          destination="your.custom.container"/>
</body>
```

### Remove the banner from the PDP entirely (e.g., you want to render it elsewhere yourself)

```xml
<body>
    <referenceBlock name="etechflow.product.warning.notice" remove="true"/>
</body>
```

---

## Overriding the template

If you want to change the markup (e.g., add an icon, change the layout, integrate with your theme's design system), copy the template into your theme:

```
cp <magento-root>/app/code/Etechflow/ProductWarning/view/frontend/templates/notice.phtml \
   <magento-root>/app/design/frontend/<Vendor>/<theme>/Etechflow_ProductWarning/templates/notice.phtml
```

Edit the copy. Magento's theme fallback will use yours instead of the module's. The block class stays the same — it just renders against your template.

---

## Calling the resolver from another module / template

```php
use Etechflow\ProductWarning\Model\WarningResolver;
use Magento\Catalog\Model\Product;

$warnings = $resolver->getForProduct($product);
// Returns:
// [
//   ['warning_id' => 1, 'name' => '7-day lead', 'message' => 'Ships in 7 days', 'color' => '#C41818', 'sort_order' => 0],
//   …
// ]
```

Pass the same `WarningResolver` to your own block via constructor DI — see the example in `USAGE.md`.

---

## Caching considerations

The PDP page is full-page-cached by default in production mode. Warnings only re-render when the FPC entry for that product page is invalidated. Triggers:

- Editing the product (any field) → invalidates that product's FPC entry
- Editing the warning (changing message / color / active flag) → does NOT auto-invalidate FPC. **Recommended**: after creating or editing a warning, run `bin/magento cache:flush full_page` (or wait for the next product save / FPC sweep)

For stores with strict cache-coherence requirements, write a small observer in your own module that listens for `etechflow_warning_save_after` (this module fires the standard `<resource_singular>_save_after` event) and calls `\Magento\PageCache\Model\Cache\Type::invalidate()`.

---

## Disabling the auto-injection while keeping the module enabled

If you want the data and admin UI but not the auto-injected banner (e.g., you'll render warnings yourself in a custom template):

In your theme, add `app/design/frontend/<Vendor>/<theme>/Etechflow_ProductWarning/layout/catalog_product_view.xml`:

```xml
<body>
    <referenceBlock name="etechflow.product.warning.notice" remove="true"/>
</body>
```

Then call `$resolver->getForProduct($product)` from wherever you want to render the warnings.
