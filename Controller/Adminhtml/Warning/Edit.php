<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Controller\Adminhtml\Warning;

use ETechFlow\ProductWarning\Model\WarningFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_ProductWarning::warnings';

    private PageFactory $resultPageFactory;
    private WarningFactory $warningFactory;
    private Registry $registry;

    public function __construct(Context $context, PageFactory $resultPageFactory, WarningFactory $warningFactory, Registry $registry)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->warningFactory    = $warningFactory;
        $this->registry          = $registry;
    }

    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('warning_id');
        $model = $this->warningFactory->create();
        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This warning no longer exists.'));
                return $this->resultRedirectFactory->create()->setPath('*/*/');
            }
        }
        $this->registry->register('etechflow_warning', $model);

        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('ETechFlow_ProductWarning::warnings');
        $page->getConfig()->getTitle()->prepend($id ? __('Edit Warning') : __('New Warning'));
        return $page;
    }
}
