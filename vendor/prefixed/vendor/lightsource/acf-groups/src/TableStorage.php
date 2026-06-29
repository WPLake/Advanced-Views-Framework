<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups;

use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\AcfGroupInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\CreatorInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\TableStorageInterface;
class TableStorage implements TableStorageInterface
{
    private CreatorInterface $creator;
    /**
     * @var array<string,array<string,mixed>>
     */
    private array $loadValues;
    /**
     * @var array<string,AcfGroupInterface>
     */
    private array $loadInstances;
    /**
     * @var array<string,mixed>
     */
    private array $saveValues;
    private ?AcfGroupInterface $validationInstance;
    public function __construct(CreatorInterface $creator)
    {
        $this->creator = $creator;
        $this->loadValues = [];
        $this->loadInstances = [];
        $this->saveValues = [];
        $this->validationInstance = null;
    }
    protected function isTargetSource(string $sourceToCheck, string $targetPostId, string $targetPostType) : bool
    {
        // 1. posts checks
        if ('options' !== $targetPostType) {
            // filter by postId
            if ('' !== $targetPostId && $targetPostId !== $sourceToCheck) {
                return \false;
            }
            // filter by postType
            if ('' !== $targetPostType) {
                if (\false === \is_numeric($sourceToCheck) || '0' === $sourceToCheck) {
                    return \false;
                }
                $sourceToCheck = \intval($sourceToCheck);
                $sourcePostType = get_post_type($sourceToCheck);
                if ($targetPostType !== $sourcePostType) {
                    return \false;
                }
            }
            return \true;
        }
        // 2. option pages checks
        // filter by source. Also allow '0' for the 'admin_head' hook
        if (\false === \in_array($sourceToCheck, ['0', 'options'], \true)) {
            return \false;
        }
        $screen = get_current_screen();
        // filter by target screen id
        return null !== $screen && $targetPostId === $screen->id;
    }
    /**
     * @template T of AcfGroupInterface
     * @param class-string<T> $targetGroupClass
     * @param array<string,mixed> $field
     * @param mixed $value
     *
     * @return mixed
     */
    protected function preloadValue(string $targetGroupClass, string $source, array $field, $value)
    {
        if (\false === \key_exists($source, $this->loadValues)) {
            $instance = $this->creator->create($targetGroupClass);
            $instance->loadFromTable($source);
            // not loaded if it's a new post.
            $this->loadValues[$source] = \true === $instance->isLoaded() ? $instance->getFieldValues() : array();
            $this->loadInstances[$source] = $instance;
        }
        $field_name = \key_exists('name', $field) && \is_string($field['name']) ? $field['name'] : '';
        // skip sub-fields or fields from other groups.
        if (\false === \key_exists($field_name, $this->loadValues[$source]) || \false === \key_exists($source, $this->loadInstances)) {
            return $value;
        }
        $value = $this->loadValues[$source][$field_name];
        $instance = $this->loadInstances[$source];
        // convert repeater format. don't check simply 'is_array(value)' as not every array is a repeater
        // also check to make sure it's array (can be empty string).
        return \true === \in_array($field_name, $instance->getRepeaterFieldNames(), \true) && \true === \is_array($value) ? AcfGroup::convertRepeaterFieldValues($field_name, $value, \false) : $value;
    }
    /**
     * @param array<string,mixed> $field
     * @param mixed $value
     */
    protected function preUpdateValue(array $field, $value) : bool
    {
        if (null === $this->validationInstance) {
            return \false;
        }
        $field_name = \key_exists('name', $field) && (\is_string($field['name']) || \is_numeric($field['name'])) ? (string) $field['name'] : '';
        // convert repeater format. don't check simply 'is_array(value)' as not every array is a repeater
        // also check to make sure it's array (can be empty string).
        if (\in_array($field_name, $this->validationInstance->getRepeaterFieldNames(), \true) && \is_array($value)) {
            $value = AcfGroup::convertRepeaterFieldValues($field_name, $value);
        }
        $this->saveValues[$field_name] = $value;
        // avoid saving to the postmeta.
        return \true;
    }
    protected function saveValidationInstance(string $source) : void
    {
        if (null === $this->validationInstance) {
            return;
        }
        // remove slashes added by WP, as it's wrong to have slashes so early
        // (corrupts next data processing, like markup generation (will be \&quote; instead of &quote; due to this escaping)
        // in the 'saveToPostContent()' method using $wpdb that also has 'addslashes()',
        // it means otherwise \" will be replaced with \\\" and it'll create double slashing issue (every saving amount of slashes before " will be increasing).
        // @phpstan-ignore-next-line
        $fieldValues = \array_map('stripslashes_deep', $this->saveValues);
        $instance = $this->validationInstance->getDeepClone();
        $instance->loadFromTable($source);
        // special method which updates field values, but keeps origin values cache in place,
        // so only the diff will be computed and updated.
        $instance->setFieldValues($fieldValues);
        $instance->saveToTable();
    }
    /**
     * @param string[] $groupClasses
     *
     * @return array<string,string>
     */
    public function getTableDefinitions(array $groupClasses, string $relatedObjectTableName, string $relatedObjectTableColumn) : array
    {
        $tableDefinitions = [];
        foreach ($groupClasses as $groupClass) {
            if (\false === \class_exists($groupClass) || \false === \in_array(AcfGroupInterface::class, \class_implements($groupClass), \true)) {
                continue;
            }
            // @phpstan-ignore-next-line
            $tableDefinitions[$groupClass] = \call_user_func([$groupClass, 'getTableDefinition'], $relatedObjectTableName, $relatedObjectTableColumn);
        }
        // @phpstan-ignore-next-line
        return $tableDefinitions;
    }
    /**
     * @param string[] $groupClasses
     *
     * @return array<string,string>
     */
    public function signUpTables(array $groupClasses, string $relatedObjectTableName, string $relatedObjectTableColumn) : array
    {
        $tableDefinitions = $this->getTableDefinitions($groupClasses, $relatedObjectTableName, $relatedObjectTableColumn);
        if ([] === $tableDefinitions) {
            return [];
        }
        $sqlCreationQueries = \implode(';', $tableDefinitions);
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sqlCreationQueries);
        return $tableDefinitions;
    }
    /**
     * @template T of AcfGroupInterface
     *
     * @param class-string<T> $targetGroupClass
     * @param string $targetPostType postType or 'options'
     */
    public function enableTableStorage(string $targetGroupClass, string $targetPostType, string $targetId = '') : void
    {
        if (\false === is_admin()) {
            return;
        }
        \add_action(
            'acf/save_post',
            function ($source) use($targetGroupClass, $targetId, $targetPostType) {
                $source = (string) $source;
                if (\false === $this->isTargetSource($source, $targetId, $targetPostType)) {
                    return;
                }
                $this->validationInstance = $this->creator->create($targetGroupClass);
                add_filter(
                    'acf/pre_update_value',
                    /**
                     * @return mixed
                     */
                    function ($is_updated, $value, $source, array $field) use($targetId, $targetPostType) {
                        $source = (string) $source;
                        // extra check, as probably it's about another post.
                        if (\false === $this->isTargetSource($source, $targetId, $targetPostType)) {
                            return $is_updated;
                        }
                        return $this->preUpdateValue($field, $value);
                    },
                    10,
                    4
                );
                \add_action(
                    'acf/save_post',
                    function ($source) use($targetId, $targetPostType) {
                        $source = (string) $source;
                        // extra check, as probably it's about another post.
                        if (\false === $this->isTargetSource($source, $targetId, $targetPostType)) {
                            return;
                        }
                        $this->saveValidationInstance($source);
                    },
                    // priority is 20, to be after the 'acf/pre_update_value' filter.
                    20
                );
            },
            // must be less 10, to run before 'acf/pre_update_value' filter.
            9
        );
        \add_action('acf/input/admin_head', function () use($targetGroupClass, $targetId, $targetPostType) {
            global $post;
            $source = null !== $post ? (string) $post->ID : '0';
            if (\false === $this->isTargetSource($source, $targetId, $targetPostType)) {
                return;
            }
            add_filter('acf/pre_load_value', function ($value, $source, $field) use($targetGroupClass, $targetId, $targetPostType) {
                $source = (string) $source;
                // extra check, as probably it's about another post.
                if (\false === $this->isTargetSource($source, $targetId, $targetPostType)) {
                    return $value;
                }
                return $this->preloadValue($targetGroupClass, $source, $field, $value);
            }, 10, 3);
        });
    }
}
