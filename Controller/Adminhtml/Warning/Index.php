<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Controller\Adminhtml\Warning;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ETechFlow_ProductWarning::warnings';

    private PageFactory $resultPageFactory;

    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $page = $this->resultPageFactory->create();
        $page->setActiveMenu('ETechFlow_ProductWarning::warnings');
        $page->getConfig()->getTitle()->prepend(__('Product Warnings'));
        return $page;
    }
}
