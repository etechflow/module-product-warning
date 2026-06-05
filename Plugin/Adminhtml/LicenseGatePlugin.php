<?php

declare(strict_types=1);

namespace ETechFlow\ProductWarning\Plugin\Adminhtml;

use ETechFlow\ProductWarning\Model\LicenseValidator;
use Magento\Backend\App\Action;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * License-gate plugin for every admin Item/Category/Pending controller.
 *
 * Registered in etc/adminhtml/di.xml against each concrete Product Warning admin
 * controller class. When licence is invalid, short-circuits the
 * controller dispatch and redirects to /admin/etechflow_warning/license/gate.
 *
 * License/* controllers (Gate, Checkout, Activated) are NOT in the plugin's
 * pointcut, so the merchant can always reach the gate to enter/buy a key
 * and the Activated page after Stripe payment. The Stores -> Configuration
 * page is also reachable (Magento's own system_config controller, not ours).
 */
class LicenseGatePlugin
{
    public function __construct(
        private readonly LicenseValidator $licenseValidator,
        private readonly ResultFactory $resultFactory
    ) {
    }

    /**
     * @param Action $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return mixed
     */
    public function aroundDispatch(Action $subject, callable $proceed, RequestInterface $request)
    {
        if (!$this->licenseValidator->isValid()) {
            $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $redirect->setPath('etechflow_warning/license/gate');
            return $redirect;
        }
        return $proceed($request);
    }
}
