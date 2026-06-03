# Compatibility — Etechflow_ProductWarning

How and why this module works across themes.

---

## Magento + PHP matrix

| Magento | PHP 8.1 | PHP 8.2 | PHP 8.3 |
|---|---|---|---|
| 2.4.4 | ✅ | ✅ | ❌ (Magento limit) |
| 2.4.5 | ✅ | ✅ | ❌ (Magento limit) |
| 2.4.6 | ✅ | ✅ | ✅ |
| 2.4.7 | ✅ | ✅ | ✅ |
| 2.4.8 | ✅ | ✅ | ✅ |
| Mage-OS | ✅ | ✅ | ✅ |
| Adobe Commerce | ✅ | ✅ | ✅ |

---

## Theme matrix

| Theme | Status | Notes |
|---|---|---|
| **Magento Luma / Blank** | ✅ | No Knockout, RequireJS, or jQuery on our side. |
| **Hyvä Theme** (any child theme) | ✅ | No Alpine / Tailwind required. |
| **Custom themes (Luma parent)** | ✅ | Inherits via Magento's theme fallback chain. |
| **Custom themes (Hyvä parent)** | ✅ | Inherits via Hyvä's theme fallback chain. |
| **Mage-OS adminhtml** | ✅ | Standard Ui Components — work the same on Mage-OS. |
| **Breeze / Frontools / Snowdog Alpaca** | ✅ | Scoped CSS + vanilla DOM means no framework collision. |
| **Headless / PWA Studio** | ⚠️ | Storefront template bypassed in headless mode. Use the `WarningResolver` PHP API to feed warnings into your custom storefront. |

---

## Design choices that keep it portable

### 1. CSS namespaced + scoped inline

The notice template renders an inline `<style>` block with `.epw-*` classes:

```html
<style>
  .epw-stack  { … }
  .epw-notice { … }
</style>
```

- Prefix `.epw-` (Etechflow Product Warning) prevents collision with theme classes.
- Inline `<style>` means we don't depend on the host theme's CSS pipeline (Hyvä's Tailwind build, Luma's LESS compile).
- Per-warning color tokens are written inline on the element (`style="background:…"`) — no CSS custom properties needed.

### 2. Vanilla DOM only (and actually no JS at all)

The frontend template has **zero JavaScript**. The notice is pure server-rendered HTML, no interactivity required, no framework needed. Best possible compatibility.

### 3. Standard Magento layout XML

```xml
<referenceContainer name="product.info.main">
    <block class="Etechflow\ProductWarning\Block\Frontend\Notice"
           name="etechflow.product.warning.notice"
           template="Etechflow_ProductWarning::notice.phtml"
           before="product.info.price"/>
</referenceContainer>
```

- `product.info.main` exists on every theme that renders Magento's standard PDP — both Luma and Hyvä define it.
- `before="product.info.price"` is a standard Magento layout instruction.
- No Hyvä-specific handles (`hyva_default.xml`), no Luma-only blocks.

### 4. No module dependencies beyond Magento core

`etc/module.xml`:
```xml
<sequence>
    <module name="Magento_Backend"/>
    <module name="Magento_Catalog"/>
    <module name="Magento_Ui"/>
</sequence>
```

`composer.json` requires only `magento/framework`, `module-backend`, `module-catalog`, `module-ui`. No commercial deps.

### 5. Admin UI is Ui Components — same on every Magento install

Admin grids and forms use stock `Magento_Ui` XML components, which work identically on Luma admin, Hyvä admin (if any), Mage-OS adminhtml, and Adobe Commerce.

The `picker.js` file in `view/adminhtml/web/js/` uses RequireJS / Knockout — that's expected and **only loads in the admin context**, where RequireJS + Knockout are standard regardless of frontend theme.

### 6. No PageBuilder dependency

The "message" field is a plain textarea — no WYSIWYG / PageBuilder coupling. Works the same regardless of which content-editor stack the store uses.

---

## Theme upgrade testing checklist

When you change themes (e.g., Luma → Hyvä, or one Hyvä child theme → another), verify:

- [ ] PDP renders without console errors
- [ ] Warning banner appears above the price on a product assigned to a warning
- [ ] Multiple warnings stack vertically with the correct sort order
- [ ] Color tint matches the configured hex
- [ ] No CSS conflicts (theme's `.notice` / `.alert` classes don't restyle ours — we use `.epw-*`)
- [ ] Admin grid + edit form still work
- [ ] Category picker tree expands correctly

---

## Known non-issues

- **No Tailwind classes in template** — by design. Adding `bg-red-50 border-red-200` would break the module on Luma.
- **No Alpine attributes** — by design. We don't need interactivity; banners are static.
- **No `x-cloak` or other Hyvä helpers** — by design. Static server-rendered HTML.
- **CSS sometimes looks "less polished" than the host theme's notice classes** — that's the price of independence. Override `.epw-notice` in your theme's CSS if you want to restyle.
