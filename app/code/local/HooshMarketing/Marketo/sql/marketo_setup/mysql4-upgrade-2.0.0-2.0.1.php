<?php
/** @var $installer  Mage_Core_Model_Resource_Setup */
$installer = $this;
/** @var Varien_Db_Adapter_Pdo_Mysql $adapter */
$adapter = $installer->getConnection();
//Initialize talbe names chunks
$entityTablesPrefix     = array("marketo_lead_entity", "marketo_opportunity_entity");
$tableTypes             = array(
    "datetime" => Varien_Db_Ddl_Table::TYPE_DATETIME,
    "int"      => Varien_Db_Ddl_Table::TYPE_INTEGER,
    "varchar"  => Varien_Db_Ddl_Table::TYPE_VARCHAR
);

$_defaults = array(
    "datetime" => "CURRENT_TIMESTAMP",
    "int"      => 0,
);
// INIT TABLES
$_marketoLeadEntityTable = $installer->getTable("marketo_lead_entity");
$_opportunityEntityTable = $installer->getTable("marketo_opportunity_entity");


$installer->startSetup();
//Creating entities tables
//Marketo Lead
$adapter->dropTable($_marketoLeadEntityTable); //DROP TABLE IF EXISTS
$marketoLeadEntityTable = $adapter
    ->newTable($_marketoLeadEntityTable)
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Entity ID')
    ->addColumn('entity_type_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Entity Type ID')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), "Store Id")
    ->addForeignKey(
        $installer->getFkName(
            'hoosh_marketo/lead',
            'store_id',
            'core/store',
            'store_id'
        ),'store_id', $installer->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Creation Time')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Update Time')
    ->addIndex($installer->getIdxName('catalog/product', array('entity_type_id')),
        array('entity_type_id'));

$adapter->createTable($marketoLeadEntityTable); //Create marketo_lead_entity table

$adapter->dropTable($_opportunityEntityTable);
// Marketo Opportunity
$opportunityEntityTable = $adapter
    ->newTable($_opportunityEntityTable)
    ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
    ), 'Entity ID')
    ->addColumn('entity_type_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Entity Type ID')
    ->addColumn('parent_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), 'Link To Lead Id')
    ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Creation Time')
    ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0',
    ), "Store Id")
    ->addForeignKey(
        $installer->getFkName(
            'hoosh_marketo/opportunity',
            'store_id',
            'core/store',
            'store_id'
        ),'store_id', $installer->getTable('core/store'), 'store_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
    ), 'Update Time')
    ->addIndex($installer->getIdxName('catalog/product', array('entity_type_id')),
        array('entity_type_id'))
    ->addForeignKey(
        $installer->getFkName(
            'hoosh_marketo/opportunity',
            'parent_id',
            'hoosh_marketo/lead',
            'entity_id'
        ),'parent_id', $installer->getTable('hoosh_marketo/lead'), 'entity_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE); //Add chain between lead and opportunity

$adapter->createTable($opportunityEntityTable); //create marketo_opportunity_entity

/** Adding prefixes table like int, datetime, etc */
foreach ($entityTablesPrefix as $prefix) {
    foreach ($tableTypes as $_key => $_type) {
        $_tableName = $installer->getTable($prefix . "_" . $_key); //Getting table Name
        $_default = (isset($_defaults[$_key])) ? $_defaults[$_key] : null;

        $adapter->dropTable($_tableName);

        $_table = $adapter
            ->newTable($_tableName)
            ->addColumn('value_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'unsigned'  => true,
                'unique'    => true,
                'identity'  => true,
                'primary'   => true
            ), "Valud Id")
            ->addColumn('entity_type_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
                'unsigned'  => true,
                'nullable'  => false,
                'default'   => '0'
            ), "Entity Type Id")
            ->addColumn('attribute_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
                'unsigned'  => true,
                'nullable'  => false,
                'default'   => '0'
            ), "Attribute Id")
            ->addColumn("store_id", Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
                'unsigned'  => true,
                'nullable'  => false,
                'default'   => '0'
            ), "Store Id")
            ->addColumn("entity_id", Varien_Db_Ddl_Table::TYPE_INTEGER, null, array(
                'unsigned'  => true,
                'nullable'  => false,
                'default'   => '0'
            ))
            ->addColumn("value", $_type, null, array(
                'nullable'  => false,
                'defaut'    => $_default
            ))
            ->addForeignKey(
                "FK_" . strtoupper($_tableName),
                "entity_id",
                $installer->getTable($prefix),
                "entity_id",
                Varien_Db_Ddl_Table::ACTION_CASCADE,
                Varien_Db_Ddl_Table::ACTION_CASCADE
            )
            ->addIndex( //unique key
                "UNQ_" . strtoupper($_tableName) . "_ENTITY_ATTRIBUTE_STORE", //
                array('entity_id', 'attribute_id', 'store_id', 'entity_type_id'),
                array('type' => Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE)
            );

        $adapter->createTable($_table);
    }
}

$installer->endSetup();
