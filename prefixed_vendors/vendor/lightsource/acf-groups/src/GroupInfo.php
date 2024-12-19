<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups;

use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\AcfGroupInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\CreatorInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\FieldInfoInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\GroupInfoInterface;
use Exception;
class GroupInfo extends FieldsInfo implements GroupInfoInterface
{
    /**
     * Should be overridden to set up location rules
     * content sample :
     * [
     *      'post_type == x',
     *      'page_template == x',
     * ],
     * [
     *      'block == x',
     * ],
     * One sub-array = one rules group. All parts within will be combined with the 'AND' rule.
     * Rule groups between self will be combined with the 'OR' rule.
     * Every string contains from 3 parts, that combined by a space : 'ParamName Operator Value'
     */
    const LOCATION_RULES = [];
    /**
     * can be overridden to false, if a group has a DB representation,
     * in this case the 'acf_add_local_field_group' function won't be called for the group
     */
    const IS_LOCAL_GROUP = \true;
    /**
     * can be overridden if a group has a name, that doesn't follow the naming agreement
     */
    const CUSTOM_GROUP_NAME = '';
    /**
     * Can be overridden.
     * Must begin with the 'group_' https://www.advancedcustomfields.com/resources/register-fields-via-php/
     */
    const GROUP_NAME_PREFIX = 'group_local_';
    /**
     * Can be overridden.
     * TODO Must begin with the 'field_' https://www.advancedcustomfields.com/resources/register-fields-via-php/
     * TODO But it's impossible atm, likely due to the ACF's bug, because the 'field_' prefix causes issues, while any other, like 'some_' works well
     */
    const FIELD_NAME_PREFIX = '';
    // multilingual support
    const TEXT_DOMAIN = '';
    const TABLE_REPEATER_PREFIX = '__';
    const TABLE_FOREIGN_COLUMN = 'object_id';
    protected static function convertCamelCaseToDashes(string $subject) : string
    {
        $endParts = [];
        $subjectParts = \preg_split('/(?=[A-Z])/', $subject, -1, \PREG_SPLIT_NO_EMPTY);
        $subjectParts = \false === $subjectParts ? [] : $subjectParts;
        foreach ($subjectParts as $subjectPart) {
            $endParts[] = \strtolower($subjectPart);
        }
        return \implode('-', $endParts);
    }
    protected static function addSpacesBetweenCamelCase(string $subject) : string
    {
        $subject = \preg_split('/(?=[A-Z])/', $subject, -1, \PREG_SPLIT_NO_EMPTY);
        return \false !== $subject ? \implode(' ', $subject) : '';
    }
    protected static function getGroupName() : string
    {
        $customGroupName = static::getCustomGroupName();
        if ('' !== $customGroupName) {
            return $customGroupName;
        }
        $className = \explode('\\', static::class);
        return $className[\count($className) - 1];
    }
    protected static function getCustomGroupName() : string
    {
        return static::CUSTOM_GROUP_NAME;
    }
    /**
     * @param array<int,string[]> $locationRules
     *
     * @return array<int,array<int,array<string,string>>>
     */
    protected static function getAcfLocationRules(array $locationRules) : array
    {
        $acfLocationRules = [];
        foreach ($locationRules as $rulesGroup) {
            $acfRulesGroup = [];
            foreach ($rulesGroup as $rule) {
                $ruleParts = \explode(' ', $rule);
                // just skip wrong string
                if (3 !== \count($ruleParts)) {
                    continue;
                }
                $acfRulesGroup[] = ['param' => $ruleParts[0], 'operator' => $ruleParts[1], 'value' => $ruleParts[2]];
            }
            $acfLocationRules[] = $acfRulesGroup;
        }
        return $acfLocationRules;
    }
    /**
     * @return array<int,string[]>
     */
    protected static function getLocationRules() : array
    {
        return static::LOCATION_RULES;
    }
    protected static function getGroupNamePrefix() : string
    {
        return static::GROUP_NAME_PREFIX;
    }
    protected static function getMultilingualLabel(string $label) : string
    {
        return static::TEXT_DOMAIN ? __($label, static::TEXT_DOMAIN) : $label;
    }
    protected static function getGroupLabel() : string
    {
        $groupLabel = static::addSpacesBetweenCamelCase(static::getGroupName());
        return static::getMultilingualLabel($groupLabel);
    }
    protected static function getAcfFieldLabel(string $fieldName) : string
    {
        $label = \ucfirst($fieldName);
        $label = static::addSpacesBetweenCamelCase($label);
        return \str_replace(['-', '_'], ' ', $label);
    }
    /**
     * @param array<string,mixed> $field
     * @param string $targetGroupName
     *
     * @return array<string,mixed>
     */
    protected static function getRepeaterField(array $field, string $targetGroupName) : array
    {
        // e.g. 'Awards' to 'Award', 'Boxes' to 'Box'
        $label = $field['label'];
        $label = !\is_string($label) ? '' : $label;
        $repeaterItemLabel = \rtrim($label, 'es');
        $repeaterItemLabel = $label === $repeaterItemLabel ? \rtrim($label, 's') : $repeaterItemLabel;
        return ['type' => 'repeater', 'layout' => 'row', 'sub_fields' => [[
            // just for system, this key will be ignored in the clone names
            'key' => $field['key'] . '_item',
            'label' => $repeaterItemLabel,
            'name' => $field['name'] . '_item',
            'type' => 'clone',
            'clone' => [$targetGroupName],
            'display' => 'seamless',
            'layout' => 'row',
            'prefix_label' => 0,
            // don't need, because it's a repeater and prefix already will exist
            // if set up to '1' it'll create a double prefix
            'prefix_name' => 0,
        ]]];
    }
    /**
     * @param array<string,mixed> $field
     *
     * @return array<string,mixed>
     */
    protected static function getCloneField(array $field, string $targetGroupName) : array
    {
        return ['type' => 'clone', 'clone' => [$targetGroupName], 'display' => 'group', 'layout' => 'block', 'prefix_label' => 0, 'prefix_name' => 1];
    }
    /**
     * @param array<string,mixed> $field
     *
     * @return array<string,mixed>
     * @throws Exception
     */
    protected static function getDefaultsForRepeaterField(FieldInfoInterface $fieldInfo, array $field) : array
    {
        $itemClass = $fieldInfo->getArguments()['item'] ?? '';
        $getAcfGroupNameCallback = [$itemClass, 'getAcfGroupName'];
        if ('' === $itemClass || !\is_callable($getAcfGroupNameCallback)) {
            throw new Exception('Array field must have the "item" php-doc attribute with callable value, class :' . static::class);
        }
        $itemAcfGroupName = \call_user_func($getAcfGroupNameCallback);
        return \array_merge($field, static::getRepeaterField($field, $itemAcfGroupName));
    }
    /**
     * @param array<string,mixed> $field
     *
     * @return array<string,mixed>
     * @throws Exception
     */
    protected static function getDefaultsForCloneField(FieldInfoInterface $fieldInfo, array $field) : array
    {
        $targetClassName = $fieldInfo->getType();
        $getAcfGroupNameCallback = [$targetClassName, 'getAcfGroupName'];
        if ('' === $targetClassName || !\is_callable($getAcfGroupNameCallback)) {
            throw new Exception('Array field must have the "item" php-doc attribute with callable value, class :' . static::class);
        }
        $itemAcfGroupName = \call_user_func($getAcfGroupNameCallback);
        return \array_merge($field, static::getCloneField($field, $itemAcfGroupName));
    }
    /**
     * @param array<string,mixed> $field
     *
     * @return array<string,mixed>
     * @throws Exception
     */
    protected static function getDefaultsForField(FieldInfoInterface $fieldInfo, array $field) : array
    {
        switch ($fieldInfo->getType()) {
            case 'int':
            case 'float':
                $field['type'] = 'number';
                break;
            case 'bool':
                $field['type'] = 'true_false';
                $field['ui'] = 1;
                break;
            case 'string':
                $field['type'] = 'text';
                break;
            case 'array':
                if ($fieldInfo->isRepeater()) {
                    $field = static::getDefaultsForRepeaterField($fieldInfo, $field);
                }
                break;
            default:
                $field = static::getDefaultsForCloneField($fieldInfo, $field);
                break;
        }
        return $field;
    }
    protected static function getTableColumnType(string $fieldType) : string
    {
        switch ($fieldType) {
            case 'int':
            case 'post_object':
                $columnType = 'BIGINT';
                break;
            case 'bool':
                $columnType = 'TINYINT(1)';
                break;
            case 'textarea':
            case 'wysiwyg':
                $columnType = 'TEXT';
                break;
            case 'date_picker':
                $columnType = 'DATE';
                break;
            case 'datetime_picker':
                $columnType = 'DATETIME';
                break;
            case 'time_picker':
                $columnType = 'TIME';
                break;
            default:
                $columnType = 'VARCHAR(255)';
                break;
        }
        return $columnType;
    }
    /**
     * @param array<string,mixed> $field
     *
     * @return array<string,mixed>
     */
    protected static function getFieldOpeningTab(array $field) : array
    {
        return ['key' => $field['key'] . '__tab', 'label' => $field['label'], 'name' => '', 'type' => 'tab', 'open' => 0, 'multi_expand' => 0, 'endpoint' => 0];
    }
    /**
     * @param array<string,mixed> $field
     * @param array<int,mixed> $fields
     *
     * @return array<int,mixed>
     */
    protected static function addFieldToFields(array $field, array $fields) : array
    {
        $isWithTab = !isset($field['a-no-tab']);
        if (!$isWithTab) {
            unset($field['a-no-tab']);
        }
        if (\in_array($field['type'], ['clone', 'repeater'], \true) && $isWithTab) {
            $fields[] = static::getFieldOpeningTab($field);
        }
        $fields[] = $field;
        return $fields;
    }
    public static function isLocalGroup() : bool
    {
        return static::IS_LOCAL_GROUP;
    }
    public static function getAcfGroupName(bool $isWithoutPrefix = \false) : string
    {
        $customGroupName = static::getCustomGroupName();
        if ('' !== $customGroupName) {
            return $customGroupName;
        }
        $acfGroupName = static::convertCamelCaseToDashes(static::getGroupName());
        return \false === $isWithoutPrefix ? static::getGroupNamePrefix() . $acfGroupName : $acfGroupName;
    }
    public static function getTableName(bool $isWithoutPrefix = \false) : string
    {
        return \str_replace('-', '_', static::getAcfGroupName($isWithoutPrefix));
    }
    public static function getAcfFieldName(string $fieldName) : string
    {
        return static::FIELD_NAME_PREFIX . static::getAcfGroupName() . '__' . static::convertCamelCaseToDashes($fieldName);
    }
    public static function getTableColumnName(string $fieldName) : string
    {
        $columnName = static::convertCamelCaseToDashes($fieldName);
        return \str_replace('-', '_', $columnName);
    }
    // https://www.advancedcustomfields.com/resources/register-fields-via-php/
    /**
     * @return array<string|int,mixed>
     * @throws Exception
     */
    public static function getGroupInfo() : array
    {
        $acfGroupInfo = ['key' => static::getAcfGroupName(), 'title' => static::getGroupLabel(), 'location' => static::getAcfLocationRules(static::getLocationRules())];
        $fields = [];
        $fieldsInfo = static::getFieldsInfo();
        foreach ($fieldsInfo as $fieldInfo) {
            $acfFieldName = static::getAcfFieldName($fieldInfo->getName());
            $field = ['key' => $acfFieldName, 'label' => static::getAcfFieldLabel($fieldInfo->getName()), 'name' => $acfFieldName, 'type' => $fieldInfo->getType()];
            $field = static::getDefaultsForField($fieldInfo, $field);
            $field = \array_merge($field, $fieldInfo->getArguments());
            unset($field['a-order']);
            if (isset($field['a-type'])) {
                $field['type'] = $field['a-type'];
                unset($field['a-type']);
            }
            // was using for repeater creation, not for acf group info
            if (isset($field['item'])) {
                unset($field['item']);
            }
            if (\key_exists('label', $field) && \is_string($field['label'])) {
                $field['label'] = static::getMultilingualLabel($field['label']);
            }
            if (\key_exists('instructions', $field) && \is_string($field['instructions'])) {
                $field['instructions'] = static::getMultilingualLabel($field['instructions']);
            }
            if (\key_exists('button_label', $field) && \is_string($field['button_label'])) {
                $field['button_label'] = static::getMultilingualLabel($field['button_label']);
            }
            if (\key_exists('choices', $field) && \is_array($field['choices'])) {
                foreach ($field['choices'] as $choiceKey => $choiceValue) {
                    $field['choices'][$choiceKey] = static::getMultilingualLabel($choiceValue);
                }
            }
            $fields = static::addFieldToFields($field, $fields);
        }
        $acfGroupInfo['fields'] = $fields;
        return $acfGroupInfo;
    }
    public static function getTableDefinition(string $relatedObjectTableName, string $relatedObjectTableColumn, string $prefix = '') : string
    {
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();
        $structureRows = ['id bigint UNSIGNED NOT NULL AUTO_INCREMENT', 'PRIMARY KEY (id)', static::TABLE_FOREIGN_COLUMN . ' bigint UNSIGNED NOT NULL'];
        $structureRows[] = \sprintf('FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE CASCADE', static::TABLE_FOREIGN_COLUMN, $relatedObjectTableName, $relatedObjectTableColumn);
        $fieldsInfo = static::getFieldsInfo();
        $isTableNameWithoutPrefix = '' !== $prefix;
        $tableName = $prefix . static::getTableName($isTableNameWithoutPrefix);
        $foreignTableDefinitions = [];
        foreach ($fieldsInfo as $fieldInfo) {
            $fieldArgs = $fieldInfo->getArguments();
            $isRequired = \true === \key_exists('required', $fieldArgs) && 1 === $fieldArgs['required'];
            $fieldType = \true === \key_exists('a-type', $fieldArgs) && \true === \is_string($fieldArgs['a-type']) ? $fieldArgs['a-type'] : $fieldInfo->getType();
            if (\true === $fieldInfo->isRepeater() && \key_exists('item', $fieldArgs) && \true === \is_string($fieldArgs['item'])) {
                // '\namespace\folder\class' to 'namespace\folder\class', to make sure it's identical to the Class::class call.
                $foreignClassName = \ltrim($fieldArgs['item'], '\\');
                // table may have several fields with the same class, while we need to declare only once.
                if (\true === \key_exists($foreignClassName, $foreignTableDefinitions)) {
                    continue;
                }
                $getTableDefinitionCallback = [$foreignClassName, 'getTableDefinition'];
                if (\false === \is_callable($getTableDefinitionCallback)) {
                    throw new Exception('Array field must have the "item" php-doc attribute with callable value, class :' . static::class);
                }
                $foreignTableDefinitions[$foreignClassName] = \call_user_func($getTableDefinitionCallback, $tableName, 'id', $tableName . static::TABLE_REPEATER_PREFIX);
                continue;
            }
            // ignores layout tabs.
            if ('tab' === $fieldType) {
                continue;
            }
            $columnName = static::getTableColumnName($fieldInfo->getName());
            $columnType = static::getTableColumnType($fieldType);
            $notNull = \true === $isRequired ? ' NOT NULL' : '';
            $structureRows[] = \sprintf('%s %s%s', $columnName, $columnType, $notNull);
        }
        // add to the beginning of the array, as others table will rely on it.
        \array_unshift($foreignTableDefinitions, \sprintf("CREATE TABLE %s (%s) %s", $tableName, \implode(",\n", $structureRows), $charsetCollate));
        return \implode(';', $foreignTableDefinitions);
    }
}
