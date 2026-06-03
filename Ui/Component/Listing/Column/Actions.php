<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    private UrlInterface $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) return $dataSource;
        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['warning_id'])) continue;
            $id = (int)$item['warning_id'];
            $item[$name] = [
                'edit' => [
                    'href'  => $this->urlBuilder->getUrl('etechflow_warning/warning/edit', ['warning_id' => $id]),
                    'label' => __('Edit'),
                ],
                'delete' => [
                    'href'  => $this->urlBuilder->getUrl('etechflow_warning/warning/delete', ['warning_id' => $id]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title'   => __('Delete this warning?'),
                        'message' => __('This will remove the warning from all assigned products and categories. Continue?'),
                    ],
                ],
            ];
        }
        return $dataSource;
    }
}
