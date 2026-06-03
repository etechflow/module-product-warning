/**
 * Cascading 4-level picker for product warnings.
 *   Level 1 — Category      (level=2 categories in Magento tree)
 *   Level 2 — Subcategory   (level=3 children of selected Level 1)
 *   Level 3 — Sub-subcategory (level>=4 descendants of selected Level 2)
 *   Level 4 — Products      (products belonging to deepest selected category set)
 *
 * Selecting at any level is enough — if admin only picks Level 1, the warning
 * applies to that category (which automatically covers every product in its sub-
 * categories via the category-product index).
 *
 * Hidden form fields synced on every change:
 *   category_ids — CSV of every selected category ID (any level)
 *   product_ids  — CSV of selected product IDs
 */
define([
    'uiComponent',
    'uiRegistry',
    'jquery',
    'underscore',
    'ko',
    'mage/translate'
], function (Component, registry, $, _, ko, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Etechflow_ProductWarning/picker',
            urls: { categoriesTree: '', products: '' },
            tracks: {
                allCategories:    true,
                /* selected IDs at each level */
                cat1Selected:     true,
                cat2Selected:     true,
                cat3Selected:     true,
                productSelected:  true,
                /* search inputs */
                cat1Search:       true,
                cat2Search:       true,
                cat3Search:       true,
                productSearch:    true,
                /* per-dropdown open state */
                cat1Open:         true,
                cat2Open:         true,
                cat3Open:         true,
                productOpen:      true,
                /* product options + load state */
                productOptions:   true,
                productInCategory: true,
                loadingCats:      true,
                loadingProducts:  true
            }
        },

        initialize: function () {
            this._super();
            this.allCategories   = [];
            this.cat1Selected    = [];
            this.cat2Selected    = [];
            this.cat3Selected    = [];
            this.productSelected = [];
            this.cat1Search      = '';
            this.cat2Search      = '';
            this.cat3Search      = '';
            this.productSearch   = '';
            this.cat1Open        = false;
            this.cat2Open        = false;
            this.cat3Open        = false;
            this.productOpen     = false;
            this.productOptions  = [];
            this.productInCategory = [];
            this.loadingCats     = false;
            this.loadingProducts = false;
            this._productSelectedNames = {};
            this._productSelectedSkus  = {};

            var self = this;
            this.loadingCats = true;
            $.getJSON(this.urls.categoriesTree)
                .done(function (res) {
                    self.allCategories = (res && res.items) || [];
                    self.loadingCats = false;
                    setTimeout(function () { self.loadInitialState(); }, 50);
                })
                .fail(function () { self.loadingCats = false; });

            /* Close dropdowns when clicking outside */
            $(document).on('click.kspicker', function (e) {
                if (!$(e.target).closest('.ks-picker').length) {
                    self.cat1Open = false;
                    self.cat2Open = false;
                    self.cat3Open = false;
                    self.productOpen = false;
                }
            });

            return this;
        },

        /** Populate selections from saved form data (edit mode) */
        loadInitialState: function () {
            var src = registry.get('etechflow_warning_form.etechflow_warning_form_data_source');
            if (!src) { setTimeout(this.loadInitialState.bind(this), 100); return; }
            var raw = src.get('data') || src.data || {};
            var data = raw;
            if (raw && typeof raw === 'object' && !raw.warning_id) {
                var keys = Object.keys(raw);
                if (keys.length) { data = raw[keys[0]] || {}; }
            }

            /* If we expect data (warning_id in URL) but data is empty, retry once */
            var hasWarningId = (window.location.search.indexOf('warning_id=') >= 0)
                || (window.location.pathname.indexOf('/warning_id/') >= 0);
            if (hasWarningId && (!data || (!data.warning_id && !data.category_ids && !data.product_ids))) {
                this._retryCount = (this._retryCount || 0) + 1;
                if (this._retryCount < 30) {
                    setTimeout(this.loadInitialState.bind(this), 100);
                    return;
                }
            }

            var catIds  = this._toIntArray(data.category_ids);
            var prodIds = this._toIntArray(data.product_ids);

            /* Distribute saved category IDs into the 3 levels by looking up tree depth */
            var byId = {};
            this.allCategories.forEach(function (c) { byId[c.id] = c; });
            var c1 = [], c2 = [], c3 = [];
            catIds.forEach(function (id) {
                var c = byId[id];
                if (!c) return;
                if (c.level === 2) c1.push(id);
                else if (c.level === 3) c2.push(id);
                else if (c.level >= 4) c3.push(id);
            });

            /* === Auto-infer missing parents so the cascade dropdowns can show
               the saved chips. If a saved cat is L3 but its L2 parent isn't
               saved, add the L2 parent to c1. Same for L4 → L3. */
            var dedup = function (arr) {
                return arr.filter(function (id, i, a) { return a.indexOf(id) === i; });
            };
            /* For each L4 cat, walk up to L3 (parent) and L2 (grandparent) */
            c3.forEach(function (id) {
                var node = byId[id];
                while (node && node.parent_id) {
                    var parent = byId[node.parent_id];
                    if (!parent) break;
                    if (parent.level === 3 && c2.indexOf(parent.id) < 0) c2.push(parent.id);
                    else if (parent.level === 2 && c1.indexOf(parent.id) < 0) c1.push(parent.id);
                    node = parent;
                }
            });
            /* For each L3 cat, walk up to L2 (parent) */
            c2.forEach(function (id) {
                var node = byId[id];
                if (!node) return;
                var parent = byId[node.parent_id];
                if (parent && parent.level === 2 && c1.indexOf(parent.id) < 0) c1.push(parent.id);
            });

            this.cat1Selected = dedup(c1);
            this.cat2Selected = dedup(c2);
            this.cat3Selected = dedup(c3);

            this.productSelected = prodIds;
            /* Load product info if there are pre-selected products so chips show names */
            if (prodIds.length) {
                var self = this;
                $.getJSON(this.urls.products, { ids: prodIds.join(',') })
                    .done(function (res) {
                        ((res && res.items) || []).forEach(function (p) {
                            self._productSelectedNames[p.id] = p.name;
                            self._productSelectedSkus[p.id]  = p.sku;
                        });
                        self.productSelected = self.productSelected.slice();  // trigger
                    });
            }

            /* === FIX === Populate the Level 4 product list from the saved categories
               so the admin sees the same products that show up when creating a new
               warning. Without this call, productOptions stays empty in edit mode. */
            if (c1.length || c2.length || c3.length) {
                this._loadProducts();
            }

            this._syncHiddenFields();
        },

        /* ---------- Options getters (filter by parent + search) ---------- */

        getCat1Options: function () {
            var q = (this.cat1Search || '').toLowerCase();
            return this.allCategories.filter(function (c) {
                return c.level === 2 && (!q || c.name.toLowerCase().indexOf(q) >= 0);
            });
        },

        getCat2Options: function () {
            var sel = this.cat1Selected;
            if (!sel.length) return [];
            var q = (this.cat2Search || '').toLowerCase();
            return this.allCategories.filter(function (c) {
                return c.level === 3 && sel.indexOf(c.parent_id) >= 0
                    && (!q || c.name.toLowerCase().indexOf(q) >= 0);
            });
        },

        getCat3Options: function () {
            var sel = this.cat2Selected;
            if (!sel.length) return [];
            var q = (this.cat3Search || '').toLowerCase();
            return this.allCategories.filter(function (c) {
                return c.level >= 4 && sel.indexOf(c.parent_id) >= 0
                    && (!q || c.name.toLowerCase().indexOf(q) >= 0);
            });
        },

        /* ---------- Names lookup ---------- */

        getCatNameById: function (id) {
            var c = _.findWhere(this.allCategories, { id: parseInt(id, 10) });
            return c ? c.name : '#' + id;
        },

        getProductNameById: function (id) {
            return this._productSelectedNames[id] || ('Product #' + id);
        },

        getProductSkuById: function (id) {
            return this._productSelectedSkus[id] || '';
        },

        /* ---------- Toggle handlers ---------- */

        toggleCat1: function (cat) {
            var idx = this.cat1Selected.indexOf(cat.id);
            if (idx >= 0) {
                this.cat1Selected = this.cat1Selected.filter(function (i) { return i !== cat.id; });
                /* deselect children too */
                this._pruneCat2();
            } else {
                this.cat1Selected = this.cat1Selected.concat([cat.id]);
            }
            this._syncHiddenFields();
        },

        toggleCat2: function (cat) {
            var idx = this.cat2Selected.indexOf(cat.id);
            if (idx >= 0) {
                this.cat2Selected = this.cat2Selected.filter(function (i) { return i !== cat.id; });
                this._pruneCat3();
            } else {
                this.cat2Selected = this.cat2Selected.concat([cat.id]);
            }
            this._loadProducts();
            this._syncHiddenFields();
        },

        toggleCat3: function (cat) {
            var idx = this.cat3Selected.indexOf(cat.id);
            if (idx >= 0) {
                this.cat3Selected = this.cat3Selected.filter(function (i) { return i !== cat.id; });
            } else {
                this.cat3Selected = this.cat3Selected.concat([cat.id]);
            }
            this._loadProducts();
            this._syncHiddenFields();
        },

        toggleProduct: function (p) {
            var idx = this.productSelected.indexOf(p.id);
            if (idx >= 0) {
                this.productSelected = this.productSelected.filter(function (i) { return i !== p.id; });
            } else {
                this.productSelected = this.productSelected.concat([p.id]);
                this._productSelectedNames[p.id] = p.name;
                this._productSelectedSkus[p.id]  = p.sku;
            }
            this._syncHiddenFields();
        },

        removeCat: function (id, level) {
            id = parseInt(id, 10);
            if (level === 2) {
                this.cat1Selected = this.cat1Selected.filter(function (i) { return i !== id; });
                this._pruneCat2();
            } else if (level === 3) {
                this.cat2Selected = this.cat2Selected.filter(function (i) { return i !== id; });
                this._pruneCat3();
            } else {
                this.cat3Selected = this.cat3Selected.filter(function (i) { return i !== id; });
            }
            this._loadProducts();
            this._syncHiddenFields();
        },

        removeProduct: function (id) {
            id = parseInt(id, 10);
            this.productSelected = this.productSelected.filter(function (i) { return i !== id; });
            this._syncHiddenFields();
        },

        /* ---------- Dropdown open/close ---------- */

        openDropdown: function (key, ev) {
            if (ev) ev.stopPropagation();
            this.cat1Open    = (key === 'cat1');
            this.cat2Open    = (key === 'cat2');
            this.cat3Open    = (key === 'cat3');
            this.productOpen = (key === 'product');
        },

        /* ---------- Product AJAX ---------- */

        _loadProducts: function () {
            /* Use ALL selected categories across the 3 levels — backend resolves
               products via the category-product index covering descendants too,
               so combining all levels gives a complete product set. */
            var cats = [].concat(this.cat1Selected, this.cat2Selected, this.cat3Selected);
            cats = cats.filter(function (id, idx, arr) { return arr.indexOf(id) === idx; });
            if (!cats.length) { this.productOptions = []; return; }
            var self = this;
            this.loadingProducts = true;
            if (this._prodT) clearTimeout(this._prodT);
            this._prodT = setTimeout(function () {
                $.getJSON(self.urls.products, { category: cats.join(','), q: self.productSearch })
                    .done(function (res) {
                        self.productOptions = (res && res.items) || [];
                        /* Auto-include all category-derived products visually (checked).
                           This shows admin which products the warning currently covers. */
                        self.productInCategory = self.productOptions.map(function (p) { return p.id; });
                    })
                    .always(function () { self.loadingProducts = false; });
            }, 200);
        },

        onProductSearchChange: function () { this._loadProducts(); },

        /* ---------- Pruning when parents deselected ---------- */

        _pruneCat2: function () {
            var p = this.cat1Selected;
            var byId = {};
            this.allCategories.forEach(function (c) { byId[c.id] = c; });
            this.cat2Selected = this.cat2Selected.filter(function (id) {
                var c = byId[id]; return c && p.indexOf(c.parent_id) >= 0;
            });
            this._pruneCat3();
        },

        _pruneCat3: function () {
            var p = this.cat2Selected;
            var byId = {};
            this.allCategories.forEach(function (c) { byId[c.id] = c; });
            this.cat3Selected = this.cat3Selected.filter(function (id) {
                var c = byId[id]; return c && p.indexOf(c.parent_id) >= 0;
            });
        },

        /* ---------- Sync hidden form fields ---------- */

        _syncHiddenFields: function () {
            var allCats = [].concat(this.cat1Selected, this.cat2Selected, this.cat3Selected);
            var catField  = registry.get('etechflow_warning_form.etechflow_warning_form.assignment.category_ids');
            var prodField = registry.get('etechflow_warning_form.etechflow_warning_form.assignment.product_ids');
            if (catField)  catField.value(allCats.join(','));
            if (prodField) prodField.value(this.productSelected.join(','));
        },

        _toIntArray: function (val) {
            if (Array.isArray(val)) return val.map(function (n) { return parseInt(n, 10); }).filter(Boolean);
            if (typeof val === 'string' && val !== '') {
                return val.split(',').map(function (s) { return parseInt(s.trim(), 10); }).filter(Boolean);
            }
            return [];
        }
    });
});
