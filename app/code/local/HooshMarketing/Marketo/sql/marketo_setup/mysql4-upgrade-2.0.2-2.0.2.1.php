<?php
/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
/** @var Varien_Db_Adapter_Pdo_Mysql $adapter */
$adapter   = $installer->getConnection();
/** @var string $categoryAttributeTable - handle attributes */
$categoryAttributeTable = $installer->getTable("catalog_category_entity_varchar");
//GET ALL ROOT CATEGORIES FROM STORES

$entityTypeId = Mage::getSingleton("eav/entity_type")->load("catalog_category", "entity_type_code")->getId();
/** @var Varien_Data_Collection $attributes */
$attributes   = Mage::getSingleton("eav/entity_attribute")
    ->getCollection()
    ->addFieldToFilter("attribute_code", "name")
    ->addFieldToFilter("entity_type_id", $entityTypeId)
    ->setPageSize(1); //should be only 1
$attributeId = $attributes->getFirstItem()->getId();
//Try to Drop old SPLIT function and create function with namespace
//1 and 2 category Ids -> is root ids that should be skipped
$sql = "
DROP FUNCTION IF EXISTS `SPLIT`;
DROP FUNCTION IF EXISTS `MARKETOSPLIT`;
CREATE FUNCTION `MARKETOSPLIT`(path VARCHAR(255), rootIds VARCHAR(255))
RETURNS VARCHAR(255)
BEGIN
        DECLARE result VARCHAR(255);
        DECLARE lastString VARCHAR(255);
        DECLARE i INT(5);
        SET i = 0;
        SET result = '';
        SET lastString = path;
        ## FOR PARENT CATEGORIES
        WHILE LOCATE('/', lastString, 1) <> 0 DO
            SET i = SUBSTR(lastString, 1, LOCATE('/', lastString, 1) -1);
            SET @result = (SELECT value FROM $categoryAttributeTable WHERE attribute_id = $attributeId
                AND entity_id = i LIMIT 1);
            SET lastString = SUBSTRING(lastString FROM LOCATE('/', lastString, 1) + 1 FOR LENGTH(lastString));
            IF  (rootIds NOT REGEXP CONCAT('(^.*,', i, '$)|(^', i, ',.*$)|(,', i, ',)'))
            THEN
                SET result = CONCAT_WS('/',result, @result);
            END IF;
        END WHILE;
        ## FOR CURRENT CATEGORY
        SET @result = (SELECT value FROM $categoryAttributeTable WHERE attribute_id = $attributeId AND entity_id = lastString LIMIT 1);
        IF  (rootIds NOT REGEXP CONCAT('(^.*,', lastString, '$)|(^', lastString, ',.*$)|(,', lastString, ',)'))
        THEN
            SET result = CONCAT_WS('/',result, @result);
        END IF;
        RETURN result;
END;";

try {
    $adapter->raw_query($sql);
} catch(Exception $e) {
    Mage::logException($e);
}

