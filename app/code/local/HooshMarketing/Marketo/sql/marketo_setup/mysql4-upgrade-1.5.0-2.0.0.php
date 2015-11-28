<?php
/** @var $installer  Mage_Eav_Model_Entity_Setup */
$installer = $this;
/** @var Varien_Db_Adapter_Pdo_Mysql $adapter */
$adapter = $installer->getConnection();

$eavEntityTypeTable     = $installer->getTable('eav_entity_type');
//Initialize names of entity tables
$_marketoLeadEntityTable = $installer->getTable("marketo_lead_entity");
$_opportunityEntityTable = $installer->getTable("marketo_opportunity_entity");

$installer->startSetup();
//Creating additional eav table
/*** ADDITIONAL ATTRIBUTE TABLE ***/
//Creating additional table
$_additionalTableName = $installer->getTable("marketo_eav_attribute");
//Handle removing old data
$adapter->dropTable($_additionalTableName);


$_additionalTable     = $adapter
    ->newTable($_additionalTableName)
    ->addColumn('attribute_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        "nullable" => false,
        "primary"  => true,
        "identity" => true,
        "unsigned" => true
    ), "MARKETO Attribute")
    ->addColumn("is_enabled", Varien_Db_Ddl_Table::TYPE_SMALLINT, 1, array(
        "nullable" => false,
        "default"  => 0
    ), "Is Attribute Enabled")
    ->addColumn("frontend_renderer", Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(), "Frontend Renderer")
    ->addColumn("api_type", Varien_Db_Ddl_Table::TYPE_SMALLINT, 1, array(
        "nullable" => false,
        "default"  => 0
    ), "Api Type")
    ->addForeignKey(
        $installer->getFkName(
            'hoosh_marketo/eav_attribute_additional_table',
            'attribute_id',
            'eav/attribute',
            'attribute_id'
        ),'attribute_id', $installer->getTable('eav/attribute'), 'attribute_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->addColumn("is_filterable", Varien_Db_Ddl_Table::TYPE_SMALLINT, 1, array(
        "nullable" => false,
        "default"  => 0
    ), "Is Attribute Filterable?")
    ->addColumn("is_searchable", Varien_Db_Ddl_Table::TYPE_SMALLINT, 1, array(
        "nullable" => false,
        "default"  => 0
    ), "Is Attribute Searchable?")
    ->addColumn("rest_api_name", Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(), "Rest Api Name")
    ->addColumn("soap_api_name", Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(), "Soap Api Name")
    ->addColumn("friendly_name", Varien_Db_Ddl_Table::TYPE_VARCHAR, null, array(), "Label");

$adapter->createTable($_additionalTable);


try {
    $adapter->beginTransaction();
    /*** REMOVING OLD DATA ***/
    $conditions = array(
        $adapter->quoteInto("entity_type_code = ?", HooshMarketing_Marketo_Model_Lead::ENTITY),
        $adapter->quoteInto("entity_type_code = ?", HooshMarketing_Marketo_Model_Opportunity::ENTITY)
    );
    $conditions = implode(" OR ", $conditions);
    // Getting old entity Type Ids
    $_oldEntityTypes = $adapter
        ->select()
        ->from($eavEntityTypeTable, array("entity_type_id"))
        ->where($conditions);

    $entityTypes = $adapter->fetchAssoc($_oldEntityTypes);
    if(!empty($entityTypes) && is_array($entityTypes)) {
        //SOFT REMOVE OF ALL ATTRIBUTES
        $attributesCollection = Mage::getSingleton("eav/entity_attribute")
            ->getCollection()
            ->addFieldToFilter("entity_type_id", $entityTypes);
        /**
         * @var $attribute Mage_Eav_Model_Entity_Attribute
         */
        foreach($attributesCollection as $attribute) {
            $attribute->delete();
        }
    }

    /*** INSERTING NEW DATA ***/
    if(empty($entityTypeIds)) { //IF THERE IS NO Entity Type IDS
        //Batch Insert Rows to `entity_type`
        $adapter->insertMultiple(
            $eavEntityTypeTable,
            array(
                array(
                    "entity_type_code" => HooshMarketing_Marketo_Model_Lead::ENTITY,
                    "entity_model"     => 'hoosh_marketo/lead',
                    "attribute_model"  => 'hoosh_marketo/eav_attribute',
                    "entity_table"     => 'hoosh_marketo/lead',
                    "additional_attribute_table" => 'hoosh_marketo/eav_attribute_additional_table',
                    "entity_attribute_collection" => 'hoosh_marketo/lead_attribute_collection'
                ),
                array(
                    "entity_type_code" => HooshMarketing_Marketo_Model_Opportunity::ENTITY,
                    "entity_model"     => 'hoosh_marketo/opportunity',
                    "attribute_model"  => 'hoosh_marketo/eav_attribute',
                    "entity_table"     => 'hoosh_marketo/opportunity',
                    "additional_attribute_table" => 'hoosh_marketo/eav_attribute_additional_table',
                    "entity_attribute_collection" => 'hoosh_marketo/opportunity_attribute_collection'
                )
            )
        );
    }

    $adapter->commit();
} catch(Exception $e) {
    $adapter->rollback();
    Mage::logException($e);
    throw $e; // Throw exception to prevent sql ins
}

$installer->endSetup();
