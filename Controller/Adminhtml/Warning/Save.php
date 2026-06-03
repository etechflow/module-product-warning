<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Controller\Adminhtml\Warning;

use ETechFlow\ProductWarning\Model\WarningFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;

class Save extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_ProductWarning::warnings';

    private WarningFactory $warningFactory;
    private ResourceConnection $resource;

    public function __construct(Context $context, WarningFactory $warningFactory, ResourceConnection $resource)
    {
        parent::__construct($context);
        $this->warningFactory = $warningFactory;
        $this->resource       = $resource;
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            return $redirect->setPath('*/*/');
        }
        try {
            $id = (int)($data['warning_id'] ?? 0);
            $model = $this->warningFactory->create();
            if ($id) {
                $model->load($id);
                if (!$model->getId()) {
                    throw new \RuntimeException(__('This warning no longer exists.')->__toString());
                }
            }

            $name    = trim((string)($data['name'] ?? ''));
            $message = trim((string)($data['message'] ?? ''));
            $color   = trim((string)($data['color'] ?? '#C41818'));

            if ($name === '')    throw new \RuntimeException(__('Name is required.')->__toString());
            if ($message === '') throw new \RuntimeException(__('Message is required.')->__toString());
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) $color = '#C41818';

            $model->setName($name);
            $model->setMessage($message);
            $model->setColor($color);
            $model->setIsActive((bool)($data['is_active'] ?? 1));
            $model->setSortOrder((int)($data['sort_order'] ?? 0));

            /* Multi-select fields arrive as array of IDs (or comma string) */
            $catIds  = $this->normalizeIds($data['category_ids'] ?? []);
            $prodIds = $this->normalizeIds($data['product_ids']  ?? []);
            /* Narrowing: if admin picks both parent + child, drop the parent. Only the deepest
               selection in each branch is kept, so the warning applies only to that sub-tree. */
            $catIds  = $this->narrowToDeepest($catIds);
            $model->setCategoryIds($catIds);
            $model->setProductIds($prodIds);

            $model->save();

            $this->messageManager->addSuccessMessage(__('Warning saved.'));
            if ($this->getRequest()->getParam('back') === 'edit') {
                return $redirect->setPath('*/*/edit', ['warning_id' => $model->getId()]);
            }
            return $redirect->setPath('*/*/');
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $redirect->setPath('*/*/edit', ['warning_id' => $id ?? null]);
        }
    }

    private function normalizeIds($input): array
    {
        if (is_string($input)) {
            $input = $input === '' ? [] : array_map('trim', explode(',', $input));
        }
        if (!is_array($input)) return [];
        return array_values(array_filter(array_map('intval', $input)));
    }

    /**
     * Drop any category whose descendant is also in the selection.
     * Example: admin ticks "Car Keys" (id=10, path=1/2/10) AND "BMW Remote Keys" (id=15, path=1/2/10/15)
     * → result keeps only 15. The warning applies to BMW Remote Keys' sub-tree only,
     *   not the broader Car Keys branch.
     */
    private function narrowToDeepest(array $catIds): array
    {
        if (count($catIds) < 2) return $catIds;

        $conn = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_category_entity');
        $select = $conn->select()
            ->from($table, ['entity_id', 'path'])
            ->where('entity_id IN (?)', $catIds);
        $rows = $conn->fetchPairs($select);

        $kept = [];
        foreach ($catIds as $id) {
            if (!isset($rows[$id])) continue;
            $myPath = (string)$rows[$id];
            $isAncestorOfAnother = false;
            foreach ($catIds as $otherId) {
                if ($otherId === $id || !isset($rows[$otherId])) continue;
                if (str_starts_with((string)$rows[$otherId], $myPath . '/')) {
                    $isAncestorOfAnother = true;
                    break;
                }
            }
            if (!$isAncestorOfAnother) {
                $kept[] = $id;
            }
        }
        return $kept;
    }
}
