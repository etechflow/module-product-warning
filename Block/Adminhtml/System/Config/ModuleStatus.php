<?php

declare(strict_types=1);

namespace ETechFlow\ProductWarning\Block\Adminhtml\System\Config;

use ETechFlow\ProductWarning\Model\LicenseValidator;
use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Helper\Js;

/**
 * Status banner at the top of the Product Warning admin config section.
 *
 * Five-state pattern (no "module enabled" branch — FAQ doesn't have a
 * single master kill-switch; storefront rendering is gated only by the
 * licence). Surfaces immediately to a first-time installer whether the
 * module is doing anything or sitting locked.
 *
 * States:
 *   1. Dev host bypass active           (info)
 *   2. Production Environment = No      (info)
 *   3. Licence key missing              (warning)
 *   4. Licence key invalid for host     (warning)
 *   5. Module is active                 (success)
 */
class ModuleStatus extends Fieldset
{
    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        private readonly LicenseValidator $licenseValidator,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    public function render(AbstractElement $element)
    {
        $element->addClass('etechflow-module-status');

        $html  = $this->_getHeaderHtml($element);
        $html .= '<tr id="' . $element->getHtmlId() . '_status_row"><td colspan="4">';
        $html .= $this->renderStatusBanner();
        $html .= '</td></tr>';
        $html .= $this->_getFooterHtml($element);

        return $html;
    }

    /**
     * Force the Module Status group to render expanded on every page load,
     * regardless of the merchant's previously-saved collapse preference.
     * The banner is the WHOLE POINT of this group — useless if hidden behind
     * a click. Returning true here signals "this fieldset starts expanded".
     */
    protected function _isCollapseState($element)
    {
        return true;
    }

    private function renderStatusBanner(): string
    {
        $host = $this->licenseValidator->getCurrentHost();
        $isDevHost = $this->licenseValidator->isDevHost($host);
        $isProduction = $this->licenseValidator->isProductionEnvironment();
        $licenceValid = $this->licenseValidator->isValid();
        $hasKey = trim($this->licenseValidator->getConfiguredKey()) !== ''
            || trim($this->licenseValidator->getConfiguredBundleKey()) !== '';

        if ($isDevHost) {
            return $this->banner(
                'info',
                '&#8505;&#65039; Dev host bypass active',
                'The detected host <code>' . $this->escapeHtml($host) . '</code> matches a development pattern '
                . '(<code>*.test</code>, <code>*.local</code>, <code>staging.*</code>, <code>*.ngrok-free.dev</code>, etc.). '
                . 'The module runs at full features without a licence key here. Pay only when going live on a production domain.'
            );
        }

        if (!$isProduction) {
            return $this->banner(
                'info',
                '&#8505;&#65039; Production Environment = No',
                'The Production Environment toggle is off, so the module runs at full features without checking the licence. '
                . 'Use this on non-standard dev/staging domains the auto-detector misses. Switch to Yes before going live.'
            );
        }

        if (!$licenceValid) {
            if (!$hasKey) {
                return $this->banner(
                    'warning',
                    '&#9888;&#65039; Licence key missing',
                    'You\'re on production host <code>' . $this->escapeHtml($host) . '</code> but no licence key has been entered. '
                    . 'The module is silently disabled — storefront FAQ pages return 404, admin pages redirect to the licence gate. '
                    . 'Paste your key in the <strong>License Key</strong> field below, or visit '
                    . '<a href="' . $this->getUrl('etechflow_warning/license/gate') . '" style="color:inherit;text-decoration:underline;">License &amp; Plans</a> '
                    . 'to purchase one. If this is actually a dev/staging install, set <strong>Production Environment = No</strong>.'
                );
            }

            return $this->banner(
                'warning',
                '&#9888;&#65039; Licence key invalid for this host',
                'A licence key has been entered, but the portal rejected it for host '
                . '<code>' . $this->escapeHtml($host) . '</code>. The module is silently disabled. '
                . 'Common causes: server IP removed from the portal subscription, wrong key, site moved domains '
                . '(email support for a new key), key suspended, or stray whitespace in the field.'
            );
        }

        return $this->banner(
            'success',
            '&#9989; Module is active',
            'Licence valid for <code>' . $this->escapeHtml($host) . '</code>. Your FAQ listing, detail pages, '
            . 'REST API, and visitor-submission form are all enabled.'
        );
    }

    private function banner(string $kind, string $heading, string $body): string
    {
        $palette = match ($kind) {
            'success' => ['bg' => '#e7f5ec', 'border' => '#2e7d32', 'fg' => '#1b5e20'],
            'warning' => ['bg' => '#fff4e5', 'border' => '#ef6c00', 'fg' => '#bf360c'],
            'info'    => ['bg' => '#e3f2fd', 'border' => '#1976d2', 'fg' => '#0d47a1'],
            default   => ['bg' => '#f5f5f5', 'border' => '#9e9e9e', 'fg' => '#424242'],
        };

        return sprintf(
            '<div style="background:%s;border-left:4px solid %s;color:%s;padding:14px 18px;margin:0 0 6px;border-radius:4px;font-size:13px;line-height:1.5;">'
            . '<strong style="font-size:14px;display:block;margin-bottom:4px;">%s</strong>%s'
            . '</div>',
            $palette['bg'],
            $palette['border'],
            $palette['fg'],
            $heading,
            $body
        );
    }
}
