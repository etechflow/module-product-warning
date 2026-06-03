<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Controller\Adminhtml\Warning;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * GET etechflow_warning/warning/products?category=<id>&q=<query>
 * Returns up to 100 products optionally filtered by category and search term.
 * Used by the cascading picker for product selection within a chosen subcategory.
 */
class Products extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_ProductWarning::warnings';

    private JsonFactory $jsonFactory;
    private ProductCollectionFactory $productCollectionFactory;

    public function __construct(Context $context, JsonFactory $jsonFactory, ProductCollectionFactory $productCollectionFactory)
    {
        parent::__construct($context);
        $this->jsonFactory             = $jsonFactory;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    public function execute()
    {
        $catParam = (string)$this->getRequest()->getParam('category', '');
        $idsParam = (string)$this->getRequest()->getParam('ids', '');
        $query    = trim((string)$this->getRequest()->getParam('q', ''));
        $result   = $this->jsonFactory->create();

        $collection = $this->productCollectionFactory->create()
            ->addAttributeToSelect(['name', 'sku'])
            ->setPageSize(200);

        /* Lookup mode: ids=CSV — used by picker to resolve names for pre-selected products on edit */
        if ($idsParam !== '') {
            $ids = array_values(array_filter(array_map('intval', explode(',', $idsParam))));
            if (!$ids) return $result->setData(['items' => []]);
            $collection->addFieldToFilter('entity_id', ['in' => $ids])->setPageSize(count($ids));
        } else {
            /* Browse mode: filter by selected category IDs (CSV) */
            if ($catParam !== '') {
                $catIds = array_values(array_filter(array_map('intval', explode(',', $catParam))));
                if ($catIds) {
                    $collection->addCategoriesFilter(['in' => $catIds]);
                }
            }
            if ($query !== '') {
                $collection->addFieldToFilter([
                    ['attribute' => 'name', 'like' => '%' . $query . '%'],
                    ['attribute' => 'sku',  'like' => '%' . $query . '%'],
                ]);
            }
        }

        $items = [];
        foreach ($collection as $p) {
            $items[] = [
                'id'   => (int)$p->getId(),
                'name' => (string)$p->getName(),
                'sku'  => (string)$p->getSku(),
            ];
        }
        return $result->setData(['items' => $items]);
    }
}
