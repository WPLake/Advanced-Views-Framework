<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups;

use Exception;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\AcfGroupInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\CreatorInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\DbQueryManagerInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\FieldInfoInterface;
abstract class AcfGroup extends GroupInfo implements AcfGroupInterface
{
    const STORAGE_TYPE_META = 'meta';
    const STORAGE_TYPE_TABLE = 'table';
    const STORAGE = self::STORAGE_TYPE_META;
    /**
     * @var int|string|false
     */
    private $source;
    private ?int $tableRecordId;
    private string $clonePrefix;
    private string $tablePrefix;
    /**
     * @var array<string,FieldInfoInterface>
     */
    private array $fieldsInfo;
    /**
     * @var array<string,mixed>
     */
    private array $originalFieldValues;
    private bool $isLoaded;
    private CreatorInterface $creator;
    /**
     * @var array<string,mixed>|null
     */
    private ?array $externalData;
    /**
     * @throws Exception
     */
    public function __construct(CreatorInterface $creator)
    {
        $this->creator = $creator;
        $this->source = \false;
        $this->tableRecordId = null;
        $this->clonePrefix = '';
        $this->tablePrefix = '';
        $this->originalFieldValues = [];
        $this->isLoaded = \false;
        $this->externalData = null;
        $this->fieldsInfo = static::getFieldsInfo();
        $this->setDefaultValuesForFields();
    }
    /**
     * 'getFieldValues()' method is designed to work with update_field() (for repeaters)
     * if you want to use (replace) values from 'acf/pre_load_value' or 'acf/pre_update_value' hooks you have to use this method
     * 'pre_update_value' = before saving, to get the AcfGroup (current class) format, which means after you can pass the array into the 'load()' method
     * 'pre_load_value' = before loading, mark '$isFromAcfFormat=false', it'll convert the AcfGroup (current class) format to ACF, and ACF (UI and others) will understand it
     *
     * @param array<int|string,mixed> $rows
     *
     * @return array<int|string,mixed>
     */
    public static function convertRepeaterFieldValues(string $repeaterFieldName, array $rows, bool $isFromAcfFormat = \true, bool $isSkipIndexUpdate = \false) : array
    {
        $newValue = [];
        $prefix = $repeaterFieldName . '_item_';
        foreach ($rows as $index => $row) {
            $newRow = [];
            // can be plain field instead of array (in case the function called from the 'convertCloneField()' method)
            if (!\is_array($row)) {
                $newValue[$index] = $row;
                continue;
            }
            foreach ($row as $itemFieldName => $itemFieldValue) {
                $newItemFieldName = $isFromAcfFormat ? \substr($itemFieldName, \strlen($prefix)) : $prefix . $itemFieldName;
                $fieldNameToFixArray = $isFromAcfFormat ? $newItemFieldName : $itemFieldName;
                $newRow[$newItemFieldName] = \is_array($itemFieldValue) ? static::convertRepeaterFieldValues($fieldNameToFixArray, $itemFieldValue, $isFromAcfFormat) : $itemFieldValue;
            }
            if (!$isSkipIndexUpdate) {
                $newIndex = $isFromAcfFormat ? \str_replace('row-', '', (string) $index) : 'row-' . $index;
            } else {
                $newIndex = $index;
            }
            $newValue[$newIndex] = $newRow;
        }
        return $newValue;
    }
    /**
     * 'getFieldValues()' method is designed to work with update_field() (for repeaters)
     * if you want to use (replace) values from 'acf/pre_load_value' or 'acf/pre_update_value' hooks you have to use this method
     *
     * @param array<string,mixed> $fields
     *
     * @return array<int|string,mixed>
     */
    public static function convertCloneField(string $cloneFieldName, array $fields, bool $isFromAcfFormat = \true) : array
    {
        if (!$isFromAcfFormat) {
            return static::convertRepeaterFieldValues($cloneFieldName, $fields, $isFromAcfFormat);
        }
        $newFields = [];
        $prefix = $cloneFieldName . '_';
        foreach ($fields as $cloneSubFieldName => $cloneFieldValue) {
            $newCloneFieldName = \substr($cloneSubFieldName, \strlen($prefix));
            // can be string field
            if (!\is_array($cloneFieldValue)) {
                $newFields[$newCloneFieldName] = $cloneFieldValue;
                continue;
            }
            $newFields[$cloneSubFieldName] = static::convertRepeaterFieldValues($newCloneFieldName, $cloneFieldValue, $isFromAcfFormat, \true);
        }
        return $newFields;
    }
    protected function getCreator() : CreatorInterface
    {
        return $this->creator;
    }
    /**
     * @return mixed
     * @throws Exception
     */
    protected function getDefaultValue(FieldInfoInterface $fieldInfo)
    {
        $fieldValue = null;
        $fieldName = $fieldInfo->getName();
        switch ($fieldInfo->getType()) {
            case 'bool':
                $fieldValue = \false;
                break;
            case 'int':
            case 'float':
                $fieldValue = 0;
                break;
            case 'string':
                $fieldValue = '';
                break;
            case 'array':
                $fieldValue = [];
                break;
            default:
                $itemClass = $fieldInfo->getType();
                $itemClassImplementations = \class_implements($itemClass);
                if (\false !== $itemClassImplementations && \in_array(AcfGroupInterface::class, $itemClassImplementations, \true)) {
                    // @phpstan-ignore-next-line
                    $fieldValue = $this->creator->create($itemClass);
                    $fieldValue->setClonePrefix($this->getAcfFieldNameWithClonePrefix($fieldName) . '_');
                }
                break;
        }
        return $fieldValue;
    }
    /**
     * @param mixed $fieldValue
     *
     * @throws Exception
     */
    protected function isDefaultValue(FieldInfoInterface $fieldInfo, $fieldValue) : bool
    {
        if (isset($fieldInfo->getArguments()['default_value'])) {
            return $fieldInfo->getArguments()['default_value'] === $fieldValue;
        }
        return $fieldValue === $this->getDefaultValue($fieldInfo);
    }
    /**
     * @throws Exception
     */
    protected function setDefaultValueForField(FieldInfoInterface $fieldInfo) : void
    {
        $fieldName = $fieldInfo->getName();
        // @phpstan-ignore-next-line
        $this->{$fieldName} = $this->getDefaultValue($fieldInfo);
        // null, because we don't know what is in DB
        $this->originalFieldValues[$fieldName] = null;
    }
    /**
     * @throws Exception
     */
    protected function setDefaultValuesForFields() : void
    {
        foreach ($this->fieldsInfo as $fieldInfo) {
            $this->setDefaultValueForField($fieldInfo);
        }
    }
    /**
     * @return mixed
     */
    protected function getAcfFieldValue(FieldInfoInterface $fieldInfo)
    {
        $acfFieldName = $this->getAcfFieldNameWithClonePrefix($fieldInfo->getName());
        if (\true === $this->isExternalSource()) {
            if (\true === isset($this->externalData[$acfFieldName])) {
                return $this->externalData[$acfFieldName];
            }
            return $fieldInfo->getArguments()['default_value'] ?? null;
        }
        return \true === \function_exists('get_field') ? \get_field($acfFieldName, $this->source) : null;
    }
    /**
     * @param mixed $value
     */
    protected function setAcfFieldValue(string $acfFieldName, $value) : void
    {
        if (!\function_exists('update_field')) {
            return;
        }
        \update_field($acfFieldName, $value, $this->source);
    }
    protected function getAcfFieldNameWithClonePrefix(string $fieldName) : string
    {
        return $this->clonePrefix . static::getAcfFieldName($fieldName);
    }
    /**
     * @return AcfGroupInterface[]
     * @throws Exception
     */
    protected function loadRepeaterField(FieldInfoInterface $fieldInfo) : array
    {
        $itemClass = $fieldInfo->getArguments()['item'] ?? '';
        if ('' === $itemClass) {
            throw new Exception('Array field must have the "item" php-doc attribute, class :' . \get_class($this));
        }
        // to make sure the class is right
        // (so if the class is wrong exception will be always, not only when there are data in a field)
        // @phpstan-ignore-next-line
        $this->creator->create($itemClass);
        $acfFieldName = $this->getAcfFieldNameWithClonePrefix($fieldInfo->getName());
        $acfFieldValue = $this->getAcfFieldValue($fieldInfo);
        // don't use (array)$this->getAcfFieldValue() because it'll create not empty array in a 'false' case
        $items = \is_array($acfFieldValue) ? $acfFieldValue : [];
        $fieldValue = [];
        //  foreach instead of for, as identifier can have 'string' type instead of 'int' (unique id)
        $i = 0;
        foreach ($items as $row) {
            // @phpstan-ignore-next-line
            $item = $this->creator->create($itemClass);
            if (\true === $this->isExternalSource()) {
                // with ->isExternalSource() clone prefix is not needed, as fields are without a prefix in the sub array
                $item->load($this->source, '', $row);
            } else {
                $itemPrefix = $acfFieldName . '_' . $i . '_';
                $item->load($this->source, $itemPrefix);
            }
            $fieldValue[] = $item;
            $i++;
        }
        return $fieldValue;
    }
    /**
     * @throws Exception
     */
    protected function loadCloneField(FieldInfoInterface $fieldInfo) : AcfGroupInterface
    {
        $acfFieldName = $this->getAcfFieldNameWithClonePrefix($fieldInfo->getName());
        // @phpstan-ignore-next-line
        $fieldValue = $this->creator->create($fieldInfo->getType());
        // even in case ->isExternalSource() send the whole $externalData,
        // as clone fields are merged into the same group, not like repeater (array in array), but like ordinary fields
        $fieldValue->load($this->source, $acfFieldName . '_', $this->externalData);
        return $fieldValue;
    }
    /**
     * @throws Exception
     */
    protected function loadField(FieldInfoInterface $fieldInfo, bool $isSkipOriginalCacheSet = \false) : void
    {
        $fieldName = $fieldInfo->getName();
        switch ($fieldInfo->getType()) {
            case 'bool':
                $fieldValue = (bool) $this->getAcfFieldValue($fieldInfo);
                break;
            case 'int':
                $fieldValue = $this->getAcfFieldValue($fieldInfo);
                $fieldValue = \is_numeric($fieldValue) ? (int) $fieldValue : 0;
                break;
            case 'float':
                $fieldValue = $this->getAcfFieldValue($fieldInfo);
                $fieldValue = \is_numeric($fieldValue) ? (float) $fieldValue : 0.0;
                break;
            case 'string':
                $fieldValue = $this->getAcfFieldValue($fieldInfo);
                $fieldValue = \is_string($fieldValue) || \is_numeric($fieldValue) ? (string) $fieldValue : '';
                break;
            case 'array':
                if ($this->isRepeaterField($fieldName)) {
                    $fieldValue = $this->loadRepeaterField($fieldInfo);
                } else {
                    $fieldValue = $this->getAcfFieldValue($fieldInfo);
                    // don't use (array)get_field() it gives wrong results for false and null
                    $fieldValue = \is_array($fieldValue) ? $fieldValue : [];
                }
                break;
            default:
                $fieldValue = $this->loadCloneField($fieldInfo);
                break;
        }
        // @phpstan-ignore-next-line
        $this->{$fieldName} = $fieldValue;
        if (\false === $isSkipOriginalCacheSet) {
            // will be used for comparison in the save method to avoid unnecessary db requests
            $this->originalFieldValues[$fieldName] = $fieldValue;
        }
    }
    protected function isRepeaterField(string $fieldName) : bool
    {
        if (\false === \key_exists($fieldName, $this->fieldsInfo)) {
            return \false;
        }
        return $this->fieldsInfo[$fieldName]->isRepeater();
    }
    protected function isTabField(string $fieldName) : bool
    {
        if (\false === \key_exists($fieldName, $this->fieldsInfo)) {
            return \false;
        }
        $aType = $this->fieldsInfo[$fieldName]->getArguments()['a-type'] ?? '';
        return 'tab' === $aType;
    }
    /**
     * @param string $acfFieldName
     * @param AcfGroupInterface[] $newFieldValue
     * @param AcfGroupInterface[] $originalFieldValue
     * @param bool $isForce
     *
     * @return bool
     */
    protected function saveRepeater(string $acfFieldName, array &$newFieldValue, ?array $originalFieldValue, bool $isForce) : bool
    {
        $countOfItems = \count($newFieldValue);
        $countOfOriginalItems = null !== $originalFieldValue ? \count($originalFieldValue) : 0;
        $isRepeaterHasChanges = $countOfItems !== $countOfOriginalItems || \true === $isForce;
        for ($i = 0; $i < $countOfItems && \false === $isRepeaterHasChanges; $i++) {
            $cloneObject = $newFieldValue[$i];
            if (\false === $cloneObject->isHasChanges()) {
                continue;
            }
            $isRepeaterHasChanges = \true;
        }
        if (\false === $isRepeaterHasChanges) {
            return \false;
        }
        $dataArray = [];
        for ($i = 0; $i < $countOfItems; $i++) {
            // set up the new right prefix ($newFieldValue argument accepted by a link, so it'll be updated)
            $newFieldValue[$i]->setClonePrefix($acfFieldName . '_' . $i . '_');
            // the values will be saved once for all items below,
            // so item->isHasChanges() should give 'false' down the line
            $newFieldValue[$i]->refreshFieldValuesCache();
            $dataArray[] = $newFieldValue[$i]->getFieldValues();
        }
        $this->setAcfFieldValue($acfFieldName, $dataArray);
        return \true;
    }
    protected function getDb() : DbQueryManagerInterface
    {
        return $this->creator->getDbQueryManager();
    }
    /**
     * @param false|string|int $source
     * @param array<string,mixed>|null $externalData Can be output of the 'getFieldValues()' method
     *
     * @throws Exception
     */
    public function load($source = \false, string $clonePrefix = '', ?array $externalData = null, string $fromStorage = '') : bool
    {
        $fromStorage = '' === $fromStorage ? static::STORAGE : $fromStorage;
        // skip storage if external data is set.
        if (null === $externalData && self::STORAGE_TYPE_TABLE === $fromStorage) {
            $source = \false === $source ? 0 : $source;
            return $this->loadFromTable($source);
        }
        $this->source = $source;
        $this->clonePrefix = $clonePrefix;
        $this->externalData = $externalData;
        foreach ($this->fieldsInfo as $fieldInfo) {
            $this->loadField($fieldInfo);
        }
        $this->isLoaded = \true;
        return \true;
    }
    /**
     * @throws Exception
     */
    public function loadFromPostContent(int $postId) : bool
    {
        $tablePosts = $this->getDb()->tablePosts();
        // don't use 'get_post($id)->post_content' to avoid the kses issue https://core.trac.wordpress.org/ticket/38715
        $post = $this->getDb()->getRow("SELECT * FROM {$tablePosts} WHERE ID = %d LIMIT 1", [$postId]);
        $content = $post['post_content'] ?? '';
        $content = \true === \is_string($content) ? $content : '';
        $jsonContent = \json_decode($content, \true);
        $jsonContent = !\is_array($jsonContent) ? [] : $jsonContent;
        return $this->load($postId, '', $jsonContent);
    }
    /**
     * @param int|string $sourceObjectId postId or 'options'
     * @param array<string,mixed> $record
     */
    public function loadFromTable($sourceObjectId, array $record = [], string $tablePrefix = '') : bool
    {
        if ('options' !== $sourceObjectId) {
            $sourceObjectId = \true === \is_numeric($sourceObjectId) ? (int) $sourceObjectId : 0;
        } else {
            // set the right post id for the options page.
            $sourceObjectId = $this->getDb()->getOptionsPostId();
        }
        $this->source = $sourceObjectId;
        $this->tablePrefix = $tablePrefix;
        $isTableNameWithoutPrefix = '' !== $this->tablePrefix;
        $tableName = $this->tablePrefix . static::getTableName($isTableNameWithoutPrefix);
        $tableRecord = [] === $record ? $this->getDb()->getRow("SELECT * FROM {$tableName} WHERE object_id = %d LIMIT 1", [$this->source]) : $record;
        if (null === $tableRecord) {
            return \false;
        }
        $tableRecordId = $tableRecord['id'] ?? 0;
        $this->tableRecordId = \true === \is_numeric($tableRecordId) ? (int) $tableRecordId : 0;
        $values = [];
        $repeaterFields = [];
        foreach ($this->fieldsInfo as $fieldInfo) {
            $fieldName = $fieldInfo->getName();
            if (\true === $fieldInfo->isRepeater()) {
                $repeaterFields[] = $fieldInfo;
                continue;
            }
            $columnName = static::getTableColumnName($fieldName);
            if (\false === \key_exists($columnName, $tableRecord)) {
                continue;
            }
            $tableValue = $tableRecord[$columnName];
            $fieldType = $fieldInfo->getArguments()['a-type'] ?? '';
            if ('date_picker' === $fieldType && '0000-00-00' === $tableValue) {
                $tableValue = '';
            }
            $values[static::getAcfFieldName($fieldName)] = $tableValue;
        }
        $res = $this->load($this->source, '', $values);
        foreach ($repeaterFields as $repeaterFieldInfo) {
            $repeaterFieldName = $repeaterFieldInfo->getName();
            $itemClass = $repeaterFieldInfo->getArguments()['item'] ?? '';
            $getItemTableName = [$itemClass, 'getTableName'];
            if (\false === \is_callable($getItemTableName)) {
                throw new Exception('Array field must have the "item" php-doc attribute with callable value, class :' . static::class);
            }
            $itemsTable = $tableName . static::TABLE_REPEATER_PREFIX . \call_user_func($getItemTableName, \true);
            $items = $this->getDb()->getResults("SELECT * FROM {$itemsTable} WHERE object_id = %d", [$this->tableRecordId]);
            $items = \true === \is_array($items) ? $items : [];
            /**
             * @var array<string,mixed> $itemData
             */
            foreach ($items as $itemData) {
                // @phpstan-ignore-next-line
                $item = $this->creator->create($itemClass);
                $item->loadFromTable($this->tableRecordId, $itemData, $tableName . static::TABLE_REPEATER_PREFIX);
                // @phpstan-ignore-next-line
                $this->{$repeaterFieldName}[] = $item;
            }
        }
        // update the cache for repeater items, as we wrote them directly
        if ([] !== $repeaterFields) {
            $this->refreshFieldValuesCache();
        }
        return $res;
    }
    public function save(bool $isForce = \false, string $toStorage = '') : bool
    {
        $toStorage = '' === $toStorage ? static::STORAGE : $toStorage;
        if ($toStorage === self::STORAGE_TYPE_TABLE) {
            return $this->saveToTable($isForce);
        }
        $isHasChangedFields = \false;
        foreach ($this->originalFieldValues as $fieldName => $originalFieldValue) {
            if (\true === $this->isTabField($fieldName)) {
                continue;
            }
            // @phpstan-ignore-next-line
            $newFieldValue = $this->{$fieldName};
            $acfFieldName = $this->getAcfFieldNameWithClonePrefix($fieldName);
            if (\true === $this->isRepeaterField($fieldName)) {
                $originalFieldValue = \is_array($originalFieldValue) ? $originalFieldValue : null;
                if (\true === $this->saveRepeater($acfFieldName, $newFieldValue, $originalFieldValue, $isForce)) {
                    // update, because e.g. indexes could be changed
                    // @phpstan-ignore-next-line
                    $this->{$fieldName} = $newFieldValue;
                    $this->originalFieldValues[$fieldName] = $newFieldValue;
                    $isHasChangedFields = \true;
                }
                continue;
            }
            if ($newFieldValue instanceof AcfGroupInterface) {
                if (\true === $newFieldValue->save($isForce)) {
                    $this->originalFieldValues[$fieldName] = $newFieldValue;
                    $isHasChangedFields = \true;
                }
                continue;
            }
            if (\false === $isForce && $originalFieldValue === $newFieldValue) {
                continue;
            }
            $isHasChangedFields = \true;
            $this->originalFieldValues[$fieldName] = $newFieldValue;
            $this->setAcfFieldValue($acfFieldName, $newFieldValue);
        }
        return $isHasChangedFields;
    }
    /**
     * @param array<string,mixed> $postFields Can be used to update other post fields (in the same query)
     *
     * @throws Exception
     */
    public function saveToPostContent(array $postFields = [], bool $isSkipDefaults = \false) : bool
    {
        $json = $this->getJson($isSkipDefaults);
        $postFields = \array_merge($postFields, ['post_content' => $json]);
        // don't use 'wp_update_post' to avoid the kses issue https://core.trac.wordpress.org/ticket/38715
        $tablePosts = $this->getDb()->tablePosts();
        $this->getDb()->update($tablePosts, $postFields, ['ID' => $this->getSource()]);
        return \true;
    }
    public function saveToTable(bool $isForce = \false) : bool
    {
        // set the right post id for the options page.
        if ('options' === $this->source) {
            $this->source = $this->getDb()->getOptionsPostId();
        }
        $isTableNameWithoutPrefix = '' !== $this->tablePrefix;
        $tableName = $this->tablePrefix . static::getTableName($isTableNameWithoutPrefix);
        if (null === $this->tableRecordId) {
            // if it's not a repeater, then make a DB query to find id in custom table by source.
            if ('' === $this->tablePrefix) {
                $tableRecordId = $this->getDb()->getVar("SELECT id FROM {$tableName} WHERE object_id = %d LIMIT 1", [$this->source]);
                $this->tableRecordId = null !== $tableRecordId ? (int) $tableRecordId : 0;
            } else {
                $this->tableRecordId = 0;
            }
        }
        $isWithChanges = \false;
        $columnsToUpdate = [];
        $allColumns = [];
        $repeaterItemsToUpdate = [];
        $repeaterItemsToRemove = [];
        foreach ($this->originalFieldValues as $fieldName => $originalFieldValue) {
            // ignore tabs.
            if (\true === $this->isTabField($fieldName)) {
                continue;
            }
            // @phpstan-ignore-next-line
            $newFieldValue = $this->{$fieldName};
            if (\true === $this->isRepeaterField($fieldName)) {
                // it's null when nothing is read from DB yet.
                $originalFieldValue = \false === \is_array($originalFieldValue) ? [] : $originalFieldValue;
                $countOfNewItems = \count($newFieldValue);
                for ($i = 0; $i < $countOfNewItems; $i++) {
                    /**
                     * @var AcfGroupInterface $newRepeaterItem
                     */
                    $newRepeaterItem = $newFieldValue[$i];
                    // skip if item is present in the DB and has no changes
                    if (\false === \in_array($newRepeaterItem->getTableRecordId(), [0, null], \true) && \false === $newRepeaterItem->isHasChanges()) {
                        continue;
                    }
                    $repeaterItemsToUpdate[] = $newRepeaterItem;
                    $isWithChanges = \true;
                }
                if (\count($originalFieldValue) > $countOfNewItems) {
                    // repeaterItemsToRemove
                    for ($i = $countOfNewItems; $i < \count($originalFieldValue); $i++) {
                        $repeaterItemsToRemove[] = $originalFieldValue[$i];
                        $isWithChanges = \true;
                    }
                }
                continue;
            }
            $tableColumnName = static::getTableColumnName($fieldName);
            $allColumns[$tableColumnName] = $newFieldValue;
            if ($originalFieldValue === $newFieldValue) {
                continue;
            }
            $isWithChanges = \true;
            $this->originalFieldValues[$fieldName] = $newFieldValue;
            $columnsToUpdate[$tableColumnName] = $newFieldValue;
        }
        if (\false === $isWithChanges && \false === $isForce && 0 !== $this->tableRecordId) {
            return \false;
        }
        if (0 === $this->tableRecordId) {
            // insert all columns, not just marked as changed.
            $insertedCount = $this->getDb()->insert($tableName, \array_merge($allColumns, ['object_id' => $this->source]));
            // do not use 'select' to get inserted id, as it won't work for repeater items (which have multiple items).
            $this->tableRecordId = 1 === $insertedCount ? $this->getDb()->getLastInsertedId() : 0;
        } else {
            // changes may touch some repeater item only.
            if ([] !== $columnsToUpdate || \true === $isForce) {
                $this->getDb()->update($tableName, $columnsToUpdate, ['id' => $this->tableRecordId]);
            }
        }
        // repeater fields
        if ([] !== $repeaterItemsToUpdate && 0 !== $this->tableRecordId) {
            /**
             * @var AcfGroupInterface $repeaterItemToUpdate
             */
            foreach ($repeaterItemsToUpdate as $repeaterItemToUpdate) {
                // make sure new items has the data in place (e.g. if they were loaded via setFieldValues)
                $repeaterItemToUpdate->setTablePrefix($tableName . static::TABLE_REPEATER_PREFIX);
                $repeaterItemToUpdate->setSource($this->tableRecordId);
                $repeaterItemToUpdate->saveToTable($isForce);
            }
        }
        /**
         * @var AcfGroupInterface $repeaterItemToRemove
         */
        foreach ($repeaterItemsToRemove as $repeaterItemToRemove) {
            $repeaterItemToRemove->deleteInTable();
        }
        return \true;
    }
    public function deleteInTable() : bool
    {
        if (\true === \in_array($this->tableRecordId, [0, null], \true)) {
            return \false;
        }
        $isTableNameWithoutPrefix = '' !== $this->tablePrefix;
        $tableName = $this->tablePrefix . static::getTableName($isTableNameWithoutPrefix);
        $this->getDb()->delete($tableName, ['id' => $this->tableRecordId]);
        return \true;
    }
    public function isExternalSource() : bool
    {
        return \is_array($this->externalData);
    }
    public function isLoaded() : bool
    {
        return $this->isLoaded;
    }
    public function isHasChanges() : bool
    {
        foreach ($this->originalFieldValues as $fieldName => $originalFieldValue) {
            // @phpstan-ignore-next-line
            $newFieldValue = $this->{$fieldName};
            if ($this->isRepeaterField($fieldName)) {
                /**
                 * @var AcfGroupInterface $cloneObject
                 */
                foreach ($newFieldValue as $cloneObject) {
                    if ($cloneObject->isHasChanges()) {
                        return \true;
                    }
                }
            }
            if ($newFieldValue instanceof AcfGroupInterface && $newFieldValue->isHasChanges()) {
                return \true;
            }
            if ($originalFieldValue !== $newFieldValue) {
                return \true;
            }
        }
        return \false;
    }
    /**
     * can be used to recognize fields which need to apply 'convertRepeaterFieldValues()' method,
     * as not every array field is a repeater (can be plain field with return-type = array)
     * @return string[]
     */
    public function getRepeaterFieldNames() : array
    {
        $repeaterFieldNames = [];
        foreach ($this->fieldsInfo as $fieldInfo) {
            if (\false === $fieldInfo->isRepeater()) {
                continue;
            }
            $repeaterFieldNames[] = static::getAcfFieldName($fieldInfo->getName());
        }
        return $repeaterFieldNames;
    }
    /**
     * can be used to recognize fields which need to apply 'convertCloneField()' method
     * @return string[]
     */
    public function getCloneFieldNames() : array
    {
        $cloneFieldNames = [];
        foreach ($this->fieldsInfo as $fieldInfo) {
            $fieldName = $fieldInfo->getName();
            // @phpstan-ignore-next-line
            $fieldValue = $this->{$fieldName};
            if (\false === $fieldValue instanceof AcfGroupInterface) {
                continue;
            }
            $cloneFieldNames[] = static::getAcfFieldName($fieldName);
        }
        return $cloneFieldNames;
    }
    public function refreshFieldValuesCache() : void
    {
        foreach ($this->originalFieldValues as $fieldName => $originalFieldValue) {
            // @phpstan-ignore-next-line
            $this->originalFieldValues[$fieldName] = $this->{$fieldName};
        }
    }
    public function getJson(bool $isSkipDefaults = \false) : string
    {
        // don't escape slashes and line terminators
        $json = \json_encode($this->getFieldValues('', $isSkipDefaults), \JSON_HEX_APOS | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_LINE_TERMINATORS);
        return \false !== $json ? $json : '';
    }
    public function setTablePrefix(string $tablePrefix) : void
    {
        $this->tablePrefix = $tablePrefix;
    }
    public function getTablePrefix() : string
    {
        return $this->tablePrefix;
    }
    public function getTableRecordId() : ?int
    {
        return $this->tableRecordId;
    }
    /**
     * Designed to work with update_field() (for repeaters)
     * @return array<string,mixed>
     * @throws Exception
     */
    public function getFieldValues(string $clonePrefix = '', bool $isSkipDefaults = \false) : array
    {
        $fieldValues = [];
        foreach ($this->fieldsInfo as $fieldInfo) {
            $fieldName = $fieldInfo->getName();
            $acfFieldName = static::getAcfFieldName($fieldName);
            // @phpstan-ignore-next-line
            $fieldValue = $this->{$fieldName};
            // ordinary field
            if (!$fieldValue instanceof AcfGroupInterface && !$this->isRepeaterField($fieldName)) {
                if ($isSkipDefaults && $this->isDefaultValue($fieldInfo, $fieldValue)) {
                    continue;
                }
                $fieldValues[$clonePrefix . $acfFieldName] = $fieldValue;
                continue;
            }
            // clone
            if ($fieldValue instanceof AcfGroupInterface) {
                $fieldValue = $fieldValue->getFieldValues($acfFieldName . '_', $isSkipDefaults);
                // merge with fields, because it's a clone, and his fields are added to this group
                // (not like an array with sub fields as it within a repeater)
                $fieldValues = \array_merge($fieldValues, $fieldValue);
                continue;
            }
            // repeater of clones
            $value = [];
            /**
             * @var AcfGroupInterface $item
             */
            foreach ($fieldValue as $item) {
                $itemFieldValues = $item->getFieldValues('', $isSkipDefaults);
                if ($isSkipDefaults && [] === $itemFieldValues) {
                    continue;
                }
                $value[] = $itemFieldValues;
            }
            if ($isSkipDefaults && [] === $value) {
                continue;
            }
            // todo it works for isExternalSource() case,
            // if it doesn't work for direct ACF fields add 'if' and don't use $clonePrefix for direct ACF (like was before)
            $fieldValues[$clonePrefix . $acfFieldName] = $value;
        }
        return $fieldValues;
    }
    /**
     * Batch set of field values by acf name, without updating in cache and DB.
     * Used e.g. in the TableStorage, when we get data from the form.
     *
     * @param array<string,mixed> $fieldValues
     */
    public function setFieldValues(array $fieldValues) : void
    {
        $this->externalData = $fieldValues;
        foreach ($this->fieldsInfo as $fieldInfo) {
            $fieldName = $fieldInfo->getName();
            $acfFieldName = static::getAcfFieldName($fieldName);
            if (\false === \key_exists($acfFieldName, $fieldValues)) {
                continue;
            }
            if (\false === $this->isRepeaterField($fieldName)) {
                $this->loadField($fieldInfo, \true);
                continue;
            }
            /**
             * @var AcfGroupInterface[] $currentItems
             * @phpstan-ignore-next-line
             */
            $currentItems = $this->{$fieldName};
            $newItems = \true === \is_array($fieldValues[$acfFieldName]) ? $fieldValues[$acfFieldName] : [];
            // keys for new items can be string '6600512415a01', which is wrong for us.
            $newItems = \array_values($newItems);
            $countOfItems = \count($currentItems);
            $countOfNewItems = \count($newItems);
            // add new items
            if (\count($currentItems) < $countOfNewItems) {
                for ($i = $countOfItems; $i < $countOfNewItems; $i++) {
                    // @phpstan-ignore-next-line
                    $item = $this->creator->create($fieldInfo->getArguments()['item'] ?? '');
                    $item->load($this->source, '', $newItems[$i]);
                    $currentItems[] = $item;
                }
            } elseif (\count($currentItems) > $countOfNewItems) {
                $currentItems = \array_slice($currentItems, 0, $countOfNewItems);
            }
            // update existing items
            for ($i = 0; $i < $countOfNewItems && $i < $countOfItems; $i++) {
                $currentItems[$i]->setFieldValues($newItems[$i]);
            }
            // @phpstan-ignore-next-line
            $this->{$fieldName} = $currentItems;
        }
    }
    /**
     * @return int|string|false
     */
    public function getSource()
    {
        return $this->source;
    }
    /**
     * @return array<string,mixed>|null
     */
    public function getExternalData() : ?array
    {
        return $this->externalData;
    }
    public function getClonePrefix() : string
    {
        return $this->clonePrefix;
    }
    public function setClonePrefix(string $clonePrefix) : void
    {
        $this->clonePrefix = $clonePrefix;
    }
    /**
     * get deeps clone unlike the std 'clone'
     * @return static
     */
    public function getDeepClone() : AcfGroupInterface
    {
        $clone = clone $this;
        foreach ($this->fieldsInfo as $fieldInfo) {
            $fieldName = $fieldInfo->getName();
            // @phpstan-ignore-next-line
            if (!$clone->{$fieldName} instanceof AcfGroupInterface) {
                continue;
            }
            // @phpstan-ignore-next-line
            $clone->{$fieldName} = $clone->{$fieldName}->getDeepClone();
        }
        return $clone;
    }
    /**
     * @param false|string|int $source
     *
     * @return void
     */
    public function setSource($source) : void
    {
        $this->source = $source;
    }
    // not present in the interface, as for tests only
    public function setTableRecordId(int $tableRecordId) : void
    {
        $this->tableRecordId = $tableRecordId;
    }
}
