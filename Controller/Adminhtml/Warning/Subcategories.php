<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Controller\Adminhtml\Warning;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * GET etechflow_warning/warning/subcategories?parent=<id>
 * Returns immediate children of the given category as [{id, name}, …].
 */
class Subcategories extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_ProductWarning::warnings';

    private JsonFactory $jsonFactory;
    private CategoryCollectionFactory $categoryCollectionFactory;

    public function __construct(Context $context, JsonFactory $jsonFactory, CategoryCollectionFactory $categoryCollectionFactory)
    {
        parent::__construct($context);
        $this->jsonFactory               = $jsonFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    public function execute()
    {
        $parentId = (int)$this->getRequest()->getParam('parent');
        $result = $this->jsonFactory->create();

        if ($parentId <= 0) {
            return $result->setData(['items' => []]);
        }

        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('name')
            ->addFieldToFilter('parent_id', $parentId)
            ->addFieldToFilter('is_active', 1)
            ->setOrder('position', 'ASC');

        $items = [];
        foreach ($collection as $cat) {
            $items[] = [
                'id'        => (int)$cat->getId(),
                'name'      => (string)$cat->getName(),
                'has_children' => (int)$cat->getChildrenCount() > 0,
            ];
        }
        return $result->setData(['items' => $items]);
    }
}
