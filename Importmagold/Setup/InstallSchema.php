<?php

namespace LM\Importmagold\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $table = $setup->getConnection()->newTable(
            $setup->getTable('clienti_importati')
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
            'email',
            Table::TYPE_TEXT,
            255,
            ['default' => ''],
            'Email'
        )->addColumn(
            'aggiornata',
            Table::TYPE_INTEGER,
            null,
            ['default' => '0'],
            'Updated'
        )->setComment(
            'Clienti Importati Table'
        )->setOption(
            'charset',
            'utf8mb3'
        )->setOption(
            'collate',
            'utf8mb3_general_ci'
        );

        $setup->getConnection()->createTable($table);

        $setup->endSetup();
    }
}

