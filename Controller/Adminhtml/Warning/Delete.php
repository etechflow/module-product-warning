<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Controller\Adminhtml\Warning;

use ETechFlow\ProductWarning\Model\WarningFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_ProductWarning::warnings';

    private WarningFactory $warningFactory;

    public function __construct(Context $context, WarningFactory $warningFactory)
    {
        parent::__construct($context);
        $this->warningFactory = $warningFactory;
    }

    public function execute()
    {
        $redirect = $this->resultRedirectFactory->create();
        $id = (int)$this->getRequest()->getParam('warning_id');
        if ($id) {
            try {
                $model = $this->warningFactory->create();
                $model->load($id);
                if ($model->getId()) {
                    $model->delete();
                    $this->messageManager->addSuccessMessage(__('Warning deleted.'));
                }
            } catch (\Throwable $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        return $redirect->setPath('*/*/');
    }
}
