<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces;

use Exception;
interface AcfGroupInterface extends GroupInfoInterface
{
    /**
     * @param array<int|string,mixed> $rows
     *
     * @return array<int|string,mixed>
     */
    public static function convertRepeaterFieldValues(string $repeaterFieldName, array $rows, bool $isFromAcfFormat = \true) : array;
    /**
     * @param array<string,mixed> $fields
     *
     * @return array<int|string,mixed>
     */
    public static function convertCloneField(string $cloneFieldName, array $fields, bool $isFromAcfFormat = \true) : array;
    /**
     * @param false|string|int $source
     * @param array<string,mixed>|null $externalData Can be output of the 'getFieldValues()' method
     *
     * @throws Exception
     */
    public function load($source = \false, string $clonePrefix = '', ?array $externalData = null, string $fromStorage = '') : bool;
    public function loadFromPostContent(int $postId) : bool;
    /**
     * @param int|string $sourceObjectId postId or 'options'
     * @param array<string,mixed> $record
     */
    public function loadFromTable($sourceObjectId, array $record = [], string $tablePrefix = '') : bool;
    public function save(bool $isForce = \false, string $toStorage = '') : bool;
    /**
     * @param array<string,mixed> $postFields Can be used to update other post fields (in the same query)
     *
     * @return bool
     */
    public function saveToPostContent(array $postFields = []) : bool;
    public function saveToTable(bool $isForce = \false) : bool;
    public function deleteInTable() : bool;
    public function isExternalSource() : bool;
    public function isLoaded() : bool;
    public function isHasChanges() : bool;
    /**
     * @return string[]
     */
    public function getRepeaterFieldNames() : array;
    /**
     * @return string[]
     */
    public function getCloneFieldNames() : array;
    public function refreshFieldValuesCache() : void;
    public function getJson(bool $isSkipDefaults = \false) : string;
    /**
     * @return array<string,mixed>
     */
    public function getFieldValues(string $clonePrefix = '', bool $isSkipDefaults = \false) : array;
    /**
     * Batch set of field values by acf name, without updating in cache and DB.
     * Used e.g. in the TableStorage, when we get data from the form.
     *
     * @param array<string,mixed> $fieldValues
     */
    public function setFieldValues(array $fieldValues) : void;
    /**
     * @return int|string|false
     */
    public function getSource();
    /**
     * @param false|string|int $source
     *
     * @return void
     */
    public function setSource($source) : void;
    /**
     * @return array<string,mixed>|null
     */
    public function getExternalData() : ?array;
    public function getClonePrefix() : string;
    public function setClonePrefix(string $clonePrefix) : void;
    public function setTablePrefix(string $tablePrefix) : void;
    public function getTablePrefix() : string;
    public function getTableRecordId() : ?int;
    public function getDeepClone() : AcfGroupInterface;
}
