<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Controller\Adminhtml\Warning;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * GET etechflow_warning/warning/categoriesTree
 * Returns the full category tree (id, name, parent_id, level) — used by the
 * admin picker for client-side cascading filter without per-step AJAX.
 */
class CategoriesTree extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_ProductWarning::warnings';

    private JsonFactory $jsonFactory;
    private CollectionFactory $collectionFactory;

    public function __construct(Context $context, JsonFactory $jsonFactory, CollectionFactory $collectionFactory)
    {
        parent::__construct($context);
        $this->jsonFactory       = $jsonFactory;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect('name')
            ->addFieldToFilter('level', ['gt' => 1])
            ->setOrder('path', 'ASC');

        $items = [];
        foreach ($collection as $cat) {
            $items[] = [
                'id'        => (int)$cat->getId(),
                'name'      => (string)$cat->getName(),
                'parent_id' => (int)$cat->getParentId(),
                'level'     => (int)$cat->getLevel(),
            ];
        }
        return $result->setData(['items' => $items]);
    }
}
