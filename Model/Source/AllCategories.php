<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Model\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class AllCategories implements OptionSourceInterface
{
    private CollectionFactory $collectionFactory;
    private ?array $cache = null;

    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray(): array
    {
        if ($this->cache !== null) return $this->cache;

        $collection = $this->collectionFactory->create()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('is_active')
            ->addFieldToFilter('level', ['gt' => 1])
            ->setOrder('path', 'ASC');

        $options = [];
        foreach ($collection as $cat) {
            $level  = (int)$cat->getLevel();
            $indent = str_repeat('— ', max(0, $level - 2));
            $options[] = [
                'value' => (int)$cat->getId(),
                'label' => $indent . (string)$cat->getName(),
            ];
        }
        $this->cache = $options;
        return $options;
    }
}
