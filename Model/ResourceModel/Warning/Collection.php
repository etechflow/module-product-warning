<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Model\ResourceModel\Warning;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'warning_id';
    protected $_eventPrefix = 'etechflow_warning_collection';
    protected $_eventObject = 'warning_collection';

    protected function _construct()
    {
        $this->_init(
            \ETechFlow\ProductWarning\Model\Warning::class,
            \ETechFlow\ProductWarning\Model\ResourceModel\Warning::class
        );
    }
}
