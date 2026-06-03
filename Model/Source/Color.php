<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Color implements OptionSourceInterface
{
    public const PRESETS = [
        '#C41818' => 'Red',
        '#EA580C' => 'Orange',
        '#D97706' => 'Amber',
        '#CA8A04' => 'Yellow',
        '#16A34A' => 'Green',
        '#0535F5' => 'Blue',
        '#7E22CE' => 'Purple',
        '#6B7280' => 'Grey',
    ];

    public function toOptionArray(): array
    {
        $out = [];
        foreach (self::PRESETS as $hex => $label) {
            $out[] = ['value' => $hex, 'label' => __($label) . ' (' . $hex . ')'];
        }
        return $out;
    }
}
