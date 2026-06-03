# Usage Guide — Etechflow_ProductWarning

How to create warnings and assign them to products / categories.

---

## Admin location

**Catalog → Inventory Warning**

(ACL resource: `Etechflow_ProductWarning::warnings` — manage via System → Permissions → User Roles.)

---

## Creating a warning

1. Click **Add New Warning** (top right).
2. Fill in:

| Field | Required | Notes |
|---|---|---|
| **Name** | Yes | Internal admin-only label. Used in the listing grid. |
| **Message** | Yes | Customer-facing text shown on the PDP. Plain text — no HTML rendered. |
| **Color** | Yes | Hex color picker. The banner is tinted from this color (background, border, text). Defaults to `#C41818` (red). |
| **Active** | Yes | If `No`, the warning is hidden everywhere even if assigned. |
| **Sort Order** | No | When a product matches multiple warnings, lower values show first. |
| **Assigned Categories** | No | Multi-tree picker. The warning will show on every product belonging to any selected category. |
| **Assigned Products** | No | Multi-search picker (debounced SKU/name search). The warning shows on these specific products. |

3. Click **Save**. The warning is now live on matching PDPs.

---

## Assigning a warning to products

Two ways:

### A) From the warning edit page (top-down)

Edit the warning → **Assigned Products** tab → use the search box to find products by name or SKU → tick them. Save.

This is best when you have one warning that applies to a small fixed set of products.

### B) From category assignment (bulk)

Edit the warning → **Assigned Categories** tab → pick one or more categories from the tree. Save.

Every product in any selected category will now show the warning. This is best for stores like *"all backorder items should show a lead-time warning"* — one category, one warning, no per-product setup.

Categories and Products can be combined freely on the same warning. The resolver returns a warning if **any** match.

---

## What the customer sees

For each matching warning, the PDP renders this above the price block:

```
┌────────────────────────────────────────────────────────────┐
│ ⓘ  IMPORTANT: <your customer-facing message text here>   │
└────────────────────────────────────────────────────────────┘
```

- Background and border are tinted from the warning's color.
- Multiple matching warnings stack vertically with a 0.6rem gap.
- Sort order controls stacking order (lower first).
- Inactive warnings render nothing.

---

## Customizing where the banner appears

By default the banner injects into the `product.info.main` container, immediately before `product.info.price`. To move it elsewhere — e.g., above the title, or below add-to-cart — override the module's layout file in your **own theme**:

`app/design/frontend/<Vendor>/<theme>/Etechflow_ProductWarning/layout/catalog_product_view.xml`:

```xml
<?xml version="1.0"?>
<page xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:framework:View/Layout/etc/page_configuration.xsd">
    <body>
        <move element="etechflow.product.warning.notice"
              destination="product.info.main"
              before="product.info.main.title"/>   <!-- moves it above the H1 -->
    </body>
</page>
```

(See `CONFIGURATION.md` for more layout examples.)

---

## Resolving warnings in custom code

If you want to render the warnings somewhere outside the PDP (cart summary, order confirmation email, listing page hover), inject the resolver:

```php
use Etechflow\ProductWarning\Model\WarningResolver;
use Magento\Catalog\Api\ProductRepositoryInterface;

class MyBlock extends \Magento\Framework\View\Element\Template
{
    public function __construct(
        Context $context,
        WarningResolver $resolver,
        ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->resolver = $resolver;
        $this->productRepository = $productRepository;
    }

    public function getWarningsForSku(string $sku): array
    {
        $product = $this->productRepository->get($sku);
        return $this->resolver->getForProduct($product);
        // Returns:
        // [
        //   ['message' => 'Ships in 7 days',    'color' => '#C41818', 'sort_order' => 0],
        //   ['message' => 'Cutting unavailable', 'color' => '#0535F5', 'sort_order' => 10],
        // ]
    }
}
```

The same `WarningResolver` is the only data-access surface — use it everywhere so the resolution logic (active flag, category match, product match) stays consistent.

---

## Examples of practical warnings

- *"This key requires programming at a locksmith — chip not pre-coded."* (assigned to chip-key category)
- *"Ships within 7–10 business days — not stocked locally."* (assigned to backorder products)
- *"Cutting service not available for this model."* (assigned to specific SKUs)
- *"Only sold in pairs of 2."* (assigned to a manually-curated list of products)
- *"Limited stock — last 5 units."* (set up a cron-driven assignment if you want dynamic stock-level warnings)

The module doesn't auto-pick warnings based on stock or attributes — that's intentional. If you need attribute-driven warnings, write a small custom module that:
1. Listens for `catalog_product_save_after`
2. Reads any attribute (e.g., `stock_qty`, `shipping_class`)
3. Calls into this module's `WarningResolver`'s repository to attach/detach warnings programmatically

That keeps this module's API simple while letting integrators add automation.
