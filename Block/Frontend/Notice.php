<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Block\Frontend;

use ETechFlow\ProductWarning\Model\WarningResolver;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Renders any active warnings for the current PDP product as scoped,
 * theme-agnostic notice banners.
 *
 * The block is auto-injected into the product.info.main container by this
 * module's catalog_product_view.xml layout file — no theme override needed.
 */
class Notice extends Template
{
    private Registry $registry;
    // v1.0.1: renamed from $resolver to avoid colliding with the protected
    // $resolver property declared in Magento\Framework\View\Element\Template
    // (PHP requires equal-or-weaker visibility on override; private was
    // stronger than protected → class-load fatal). php -l doesn't catch
    // this — only di:compile or runtime instantiation does.
    private WarningResolver $warningResolver;

    public function __construct(
        Context $context,
        Registry $registry,
        WarningResolver $warningResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->warningResolver = $warningResolver;
    }

    public function getProduct(): ?Product
    {
        $product = $this->registry->registry('current_product');
        return $product instanceof Product ? $product : null;
    }

    /**
     * @return array<int, array{message:string,color:string,position:string,priority:int}>
     */
    public function getWarnings(): array
    {
        $product = $this->getProduct();
        if (!$product) {
            return [];
        }
        try {
            return $this->warningResolver->getForProduct($product);
        } catch (\Throwable $e) {
            // Never break the PDP because of a warning lookup
            return [];
        }
    }

    /**
     * Normalises a configured hex color to a 6-digit value. Falls back to red
     * if the saved value is malformed.
     */
    public function safeColor(string $color): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#C41818';
    }
}
