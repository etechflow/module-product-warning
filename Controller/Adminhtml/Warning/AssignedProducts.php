<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Controller\Adminhtml\Warning;

use ETechFlow\ProductWarning\Model\WarningResolver;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * GET etechflow_warning/warning/assignedProducts?warning_id=<id>
 * Returns the products that currently inherit this warning (direct + via category).
 * Used by the admin form's "Where is this warning shown?" tab.
 */
class AssignedProducts extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_ProductWarning::warnings';

    private JsonFactory $jsonFactory;
    private WarningResolver $resolver;
    private ProductRepositoryInterface $productRepository;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        WarningResolver $resolver,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->jsonFactory       = $jsonFactory;
        $this->resolver          = $resolver;
        $this->productRepository = $productRepository;
    }

    public function execute()
    {
        $warningId = (int)$this->getRequest()->getParam('warning_id');
        $result = $this->jsonFactory->create();

        if ($warningId <= 0) return $result->setData(['count' => 0, 'items' => []]);

        $ids = $this->resolver->getAssignedProductIds($warningId);

        /* Load names for first 50 (paginate later if needed) */
        $items = [];
        foreach (array_slice($ids, 0, 50) as $pid) {
            try {
                $p = $this->productRepository->getById((int)$pid);
                $items[] = ['id' => (int)$pid, 'name' => (string)$p->getName(), 'sku' => (string)$p->getSku()];
            } catch (\Throwable $e) {}
        }
        return $result->setData(['count' => count($ids), 'items' => $items]);
    }
}
