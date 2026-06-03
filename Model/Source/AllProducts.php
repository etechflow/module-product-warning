<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Model\Source;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class AllProducts implements OptionSourceInterface
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
            ->addAttributeToSelect(['name', 'sku'])
            ->addAttributeToFilter('status', 1)
            ->setOrder('name', 'ASC');

        $options = [];
        foreach ($collection as $p) {
            $options[] = [
                'value' => (int)$p->getId(),
                'label' => (string)$p->getName() . ' (' . (string)$p->getSku() . ')',
            ];
        }
        $this->cache = $options;
        return $options;
    }
}
