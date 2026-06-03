<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Model;

use ETechFlow\ProductWarning\Api\Data\WarningInterface;
use Magento\Framework\Model\AbstractModel;

class Warning extends AbstractModel implements WarningInterface
{
    protected $_eventPrefix = 'etechflow_warning';
    protected $_eventObject = 'warning';

    protected function _construct()
    {
        $this->_init(\ETechFlow\ProductWarning\Model\ResourceModel\Warning::class);
    }

    public function getWarningId() { return $this->getData(self::WARNING_ID); }
    public function setWarningId($id) { return $this->setData(self::WARNING_ID, $id); }
    public function getName(): ?string { return $this->getData(self::NAME); }
    public function setName(string $name) { return $this->setData(self::NAME, $name); }
    public function getMessage(): ?string { return $this->getData(self::MESSAGE); }
    public function setMessage(string $message) { return $this->setData(self::MESSAGE, $message); }
    public function getColor(): ?string { return $this->getData(self::COLOR); }
    public function setColor(string $color) { return $this->setData(self::COLOR, $color); }
    public function getIsActive(): bool { return (bool)$this->getData(self::IS_ACTIVE); }
    public function setIsActive(bool $isActive) { return $this->setData(self::IS_ACTIVE, $isActive ? 1 : 0); }
    public function getSortOrder(): int { return (int)$this->getData(self::SORT_ORDER); }
    public function setSortOrder(int $sortOrder) { return $this->setData(self::SORT_ORDER, $sortOrder); }

    public function getCategoryIds(): array
    {
        $ids = $this->getData('category_ids');
        if ($ids === null && $this->getId()) {
            $ids = $this->getResource()->lookupCategoryIds((int)$this->getId());
            $this->setData('category_ids', $ids);
        }
        return is_array($ids) ? array_map('intval', $ids) : [];
    }
    public function setCategoryIds(array $ids) { return $this->setData('category_ids', array_values(array_unique(array_map('intval', $ids)))); }

    public function getProductIds(): array
    {
        $ids = $this->getData('product_ids');
        if ($ids === null && $this->getId()) {
            $ids = $this->getResource()->lookupProductIds((int)$this->getId());
            $this->setData('product_ids', $ids);
        }
        return is_array($ids) ? array_map('intval', $ids) : [];
    }
    public function setProductIds(array $ids) { return $this->setData('product_ids', array_values(array_unique(array_map('intval', $ids)))); }
}
