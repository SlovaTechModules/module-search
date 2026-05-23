<?php
declare(strict_types=1);

namespace Slova\Discovery\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class SetEdgeNgramAnalyzer implements DataPatchInterface
{
    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup
    ) {
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $table = $this->moduleDataSetup->getTable('catalog_eav_attribute');

        $attributeCodes = ['author_names', 'collection_names'];

        $attributeIds = $connection->fetchCol(
            $connection->select()
                ->from($this->moduleDataSetup->getTable('eav_attribute'), ['attribute_id'])
                ->where('attribute_code IN (?)', $attributeCodes)
                ->where('entity_type_id = ?', 4)
        );

        if (!empty($attributeIds)) {
            $connection->update(
                $table,
                ['default_analyzer' => 'standard_edge_ngram'],
                ['attribute_id IN (?)' => $attributeIds]
            );
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
