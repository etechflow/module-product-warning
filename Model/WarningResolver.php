<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;

/**
 * Returns the list of active warnings that apply to a given product.
 * Applies if:
 *   - warning is directly linked to the product (etechflow_warning_product), OR
 *   - warning is linked to any category the product belongs to (etechflow_warning_category)
 * Active warnings only, sorted by sort_order ASC, then name ASC. Deduplicated by warning_id.
 */
class WarningResolver
{
    private ResourceConnection $resource;
    private array $cache = [];

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return array<int, array{warning_id:int,name:string,message:string,color:string,sort_order:int}>
     */
    public function getForProduct(Product $product): array
    {
        $pid = (int)$product->getId();
        if (!$pid) return [];

        if (isset($this->cache[$pid])) return $this->cache[$pid];

        $conn = $this->resource->getConnection();
        $w     = $this->resource->getTableName('etechflow_warning');
        $wp    = $this->resource->getTableName('etechflow_warning_product');
        $wc    = $this->resource->getTableName('etechflow_warning_category');
        $ccp   = $this->resource->getTableName('catalog_category_product_index');

        /* Direct product link OR any category the product is in */
        $sql = "
            SELECT DISTINCT w.warning_id, w.name, w.message, w.color, w.sort_order
            FROM {$w} w
            WHERE w.is_active = 1
              AND (
                  w.warning_id IN (SELECT warning_id FROM {$wp} WHERE product_id = :pid)
                  OR w.warning_id IN (
                      SELECT wc.warning_id
                      FROM {$wc} wc
                      INNER JOIN {$ccp} ccp ON ccp.category_id = wc.category_id
                      WHERE ccp.product_id = :pid2
                  )
              )
            ORDER BY w.sort_order ASC, w.name ASC
        ";
        $rows = $conn->fetchAll($sql, ['pid' => $pid, 'pid2' => $pid]);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'warning_id' => (int)$r['warning_id'],
                'name'       => (string)$r['name'],
                'message'    => (string)$r['message'],
                'color'      => (string)$r['color'],
                'sort_order' => (int)$r['sort_order'],
            ];
        }
        return $this->cache[$pid] = $out;
    }

    /**
     * Used by the admin "where shown" view — returns deduplicated product IDs.
     */
    public function getAssignedProductIds(int $warningId): array
    {
        $conn = $this->resource->getConnection();
        $wp   = $this->resource->getTableName('etechflow_warning_product');
        $wc   = $this->resource->getTableName('etechflow_warning_category');
        $ccp  = $this->resource->getTableName('catalog_category_product_index');

        $direct  = $conn->fetchCol("SELECT product_id FROM {$wp} WHERE warning_id = ?", [$warningId]);
        $viaCat  = $conn->fetchCol("
            SELECT DISTINCT ccp.product_id
            FROM {$wc} wc
            INNER JOIN {$ccp} ccp ON ccp.category_id = wc.category_id
            WHERE wc.warning_id = ?
        ", [$warningId]);
        return array_values(array_unique(array_map('intval', array_merge($direct, $viaCat))));
    }
}
