<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces;

interface TableStorageInterface
{
    /**
     * @param string[] $groupClasses
     *
     * @return array<string,string>
     */
    public function getTableDefinitions(array $groupClasses, string $relatedObjectTableName, string $relatedObjectTableColumn) : array;
    /**
     * @param string[] $groupClasses
     *
     * @return array<string,string>
     */
    public function signUpTables(array $groupClasses, string $relatedObjectTableName, string $relatedObjectTableColumn) : array;
    /**
     * @template T of AcfGroupInterface
     *
     * @param class-string<T> $targetGroupClass
     * @param string $targetPostType postType or 'options'
     */
    public function enableTableStorage(string $targetGroupClass, string $targetPostType, string $targetId = '') : void;
}
