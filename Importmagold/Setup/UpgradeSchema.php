<?php

namespace LM\Importmagold\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if ( version_compare($context->getVersion(), '1.0.2', '<') ) {

            $table = $setup->getConnection()->newTable(
                $setup->getTable('prodotti_aggiornati')
            )->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )->addColumn(
                'entity_id_old',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Old Entity ID'
            )->addColumn(
                'entity_id_new',
                Table::TYPE_INTEGER,
                null,
                ['default' => '0'],
                'New Entity ID'
            )->addColumn(
                'sku',
                Table::TYPE_TEXT,
                255,
                ['default' => ''],
                'SKU'
            )->addColumn(
                'aggiornata',
                Table::TYPE_INTEGER,
                null,
                ['default' => '0'],
                'Updated'
            )->addColumn(
                'description',
                Table::TYPE_TEXT,
                '64k',
                ['nullable' => true],
                'Description'
            )->addColumn(
                'short_description',
                Table::TYPE_TEXT,
                '64k',
                ['nullable' => true],
                'Short Description'
            )->setComment(
                'Prodotti Aggiornati Table'
            )->setOption(
                'charset',
                'utf8mb3'
            )->setOption(
                'collate',
                'utf8mb3_general_ci'
            );

            $setup->getConnection()->createTable($table);

        }

        $setup->endSetup();
    }
}

