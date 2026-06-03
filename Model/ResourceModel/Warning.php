<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Warning extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('etechflow_warning', 'warning_id');
    }

    public function lookupCategoryIds(int $warningId): array
    {
        $conn = $this->getConnection();
        $table = $this->getTable('etechflow_warning_category');
        return array_map('intval', $conn->fetchCol("SELECT category_id FROM $table WHERE warning_id = ?", [$warningId]));
    }

    public function lookupProductIds(int $warningId): array
    {
        $conn = $this->getConnection();
        $table = $this->getTable('etechflow_warning_product');
        return array_map('intval', $conn->fetchCol("SELECT product_id FROM $table WHERE warning_id = ?", [$warningId]));
    }

    protected function _afterSave(AbstractModel $object)
    {
        $this->saveCategoryLinks((int)$object->getId(), $object->getData('category_ids') ?: []);
        $this->saveProductLinks((int)$object->getId(), $object->getData('product_ids') ?: []);
        return parent::_afterSave($object);
    }

    private function saveCategoryLinks(int $warningId, array $newIds): void
    {
        $conn = $this->getConnection();
        $table = $this->getTable('etechflow_warning_category');
        $existing = array_map('intval', $conn->fetchCol("SELECT category_id FROM $table WHERE warning_id = ?", [$warningId]));
        $newIds = array_map('intval', $newIds);

        $toAdd    = array_diff($newIds, $existing);
        $toRemove = array_diff($existing, $newIds);

        if ($toRemove) {
            $conn->delete($table, ['warning_id = ?' => $warningId, 'category_id IN (?)' => $toRemove]);
        }
        if ($toAdd) {
            $rows = array_map(fn($id) => ['warning_id' => $warningId, 'category_id' => $id], $toAdd);
            $conn->insertMultiple($table, $rows);
        }
    }

    private function saveProductLinks(int $warningId, array $newIds): void
    {
        $conn = $this->getConnection();
        $table = $this->getTable('etechflow_warning_product');
        $existing = array_map('intval', $conn->fetchCol("SELECT product_id FROM $table WHERE warning_id = ?", [$warningId]));
        $newIds = array_map('intval', $newIds);

        $toAdd    = array_diff($newIds, $existing);
        $toRemove = array_diff($existing, $newIds);

        if ($toRemove) {
            $conn->delete($table, ['warning_id = ?' => $warningId, 'product_id IN (?)' => $toRemove]);
        }
        if ($toAdd) {
            $rows = array_map(fn($id) => ['warning_id' => $warningId, 'product_id' => $id], $toAdd);
            $conn->insertMultiple($table, $rows);
        }
    }
}
