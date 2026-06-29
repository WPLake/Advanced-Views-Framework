<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces;

use Exception;
interface GroupInfoInterface extends FieldsInfoInterface
{
    public static function isLocalGroup() : bool;
    public static function getAcfGroupName(bool $isWithoutPrefix = \false) : string;
    public static function getTableName(bool $isWithoutPrefix = \false) : string;
    public static function getAcfFieldName(string $fieldName) : string;
    public static function getTableColumnName(string $fieldName) : string;
    /**
     * https://www.advancedcustomfields.com/resources/register-fields-via-php/
     * @return array<string|int,mixed>
     */
    public static function getGroupInfo() : array;
    public static function getTableDefinition(string $relatedObjectTableName, string $relatedObjectTableColumn, string $prefix = '') : string;
}
