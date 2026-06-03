<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Seeds 3 default warnings and pre-assigns them to matching catalog categories
 * by walking the category tree and matching the existing regex patterns
 * (remote / repair case / blade).
 */
class SeedDefaultWarnings implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply(): self
    {
        $setup = $this->moduleDataSetup;
        $setup->startSetup();
        $conn = $setup->getConnection();

        $warningT  = $setup->getTable('etechflow_warning');
        $catLinkT  = $setup->getTable('etechflow_warning_category');
        $catEntity = $setup->getTable('catalog_category_entity');
        $catNameT  = $setup->getTable('catalog_category_entity_varchar');

        $defaults = [
            'remote' => [
                'name'    => 'Unprogrammed Remote',
                'message' => 'This product is provided unprogrammed and will not start the vehicle until it has been programmed using professional programming tools.',
                'color'   => '#C41818',
                'regex'   => '/(remote\s*key|car\s*key\s*remote|flip\s*remote|smart\s*remote)/i',
            ],
            'case' => [
                'name'    => 'Repair Case',
                'message' => 'This product does not include a PCB circuit board or transponder; you will need to transfer these parts from your existing key. This is a replacement case designed to repair worn or damaged remote cases.',
                'color'   => '#EA580C',
                'regex'   => '/repair[^|]*case|case[^|]*repair/i',
            ],
            'blade' => [
                'name'    => 'Un-cut Blade',
                'message' => 'This product is supplied as a blank un-cut, this will require cutting before it will operate your vehicle.',
                'color'   => '#0535F5',
                'regex'   => '/(key\s*blade|emergency.*blade)/i',
            ],
        ];

        /* category_id => name lookup, store_id=0 */
        $nameAttrId = (int)$conn->fetchOne("
            SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 3
        ");
        $allCats = $conn->fetchAll("
            SELECT c.entity_id, v.value AS name
            FROM {$catEntity} c
            JOIN {$catNameT} v ON v.entity_id = c.entity_id AND v.attribute_id = {$nameAttrId} AND v.store_id = 0
        ");

        $order = 10;
        foreach ($defaults as $key => $def) {
            /* Skip if a warning with the same name already exists */
            $existingId = (int)$conn->fetchOne("SELECT warning_id FROM {$warningT} WHERE name = ?", [$def['name']]);
            if ($existingId) continue;

            $conn->insert($warningT, [
                'name'       => $def['name'],
                'message'    => $def['message'],
                'color'      => $def['color'],
                'is_active'  => 1,
                'sort_order' => $order,
            ]);
            $warningId = (int)$conn->lastInsertId();
            $order += 10;

            /* Assign matching categories */
            $rows = [];
            foreach ($allCats as $cat) {
                if (preg_match($def['regex'], (string)$cat['name'])) {
                    $rows[] = ['warning_id' => $warningId, 'category_id' => (int)$cat['entity_id']];
                }
            }
            if ($rows) {
                $conn->insertMultiple($catLinkT, $rows);
            }
        }

        $setup->endSetup();
        return $this;
    }

    public static function getDependencies(): array { return []; }
    public function getAliases(): array { return []; }
}
