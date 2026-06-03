<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class ColorSwatch extends Column
{
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) return $dataSource;
        $name = $this->getName();
        foreach ($dataSource['data']['items'] as &$item) {
            $hex = (string)($item['color'] ?? '#C41818');
            $hex = preg_match('/^#[0-9A-Fa-f]{6}$/', $hex) ? $hex : '#C41818';
            $item[$name] = '<span style="display:inline-flex;align-items:center;gap:.45rem">'
                . '<span style="display:inline-block;width:16px;height:16px;border-radius:4px;border:1px solid rgba(0,0,0,0.1);background:'
                . htmlspecialchars($hex, ENT_QUOTES, 'UTF-8') . '"></span>'
                . '<code style="font-size:11px;color:#6B7280">' . htmlspecialchars($hex, ENT_QUOTES, 'UTF-8') . '</code>'
                . '</span>';
        }
        return $dataSource;
    }
}
