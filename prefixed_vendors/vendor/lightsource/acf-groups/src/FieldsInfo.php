<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups;

use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\FieldInfoInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\FieldsInfoInterface;
use ReflectionProperty;
use Exception;
class FieldsInfo implements FieldsInfoInterface
{
    /**
     * @var array<string,FieldInfoInterface[]>
     */
    protected static array $cache = [];
    protected static function getFieldInfoInstance(ReflectionProperty $property) : FieldInfoInterface
    {
        return new FieldInfo($property);
    }
    /**
     * @throws Exception
     */
    protected static function getFieldInfo(string $fieldName) : ?FieldInfoInterface
    {
        try {
            $property = new ReflectionProperty(static::class, $fieldName);
        } catch (Exception $ex) {
            throw new Exception('Fail to create ReflectionProperty, reason : ' . $ex->getMessage());
        }
        if (\false === $property->isPublic() || \true === $property->isStatic()) {
            return null;
        }
        $fieldInfo = static::getFieldInfoInstance($property);
        // only with supported types
        return '' !== $fieldInfo->getType() ? $fieldInfo : null;
    }
    /**
     * @return array<string,FieldInfoInterface>
     * @throws Exception
     */
    protected static function readFieldsInfo() : array
    {
        $fieldNames = \array_keys(\get_class_vars(static::class));
        $fieldsByOrder = [];
        $fieldsInfo = [];
        foreach ($fieldNames as $fieldName) {
            $fieldInfo = static::getFieldInfo($fieldName);
            // only public with a supported type
            if (\is_null($fieldInfo)) {
                continue;
            }
            $order = $fieldInfo->getArguments()['a-order'] ?? 0;
            $order = \is_numeric($order) ? \intval($order) : 0;
            if (!isset($fieldsByOrder[$order])) {
                $fieldsByOrder[$order] = [];
            }
            $fieldsByOrder[$order][$fieldInfo->getName()] = $fieldInfo;
        }
        \ksort($fieldsByOrder);
        foreach ($fieldsByOrder as $fields) {
            $fieldsInfo = \array_merge($fieldsInfo, $fields);
        }
        return $fieldsInfo;
    }
    /**
     * @return array<string,FieldInfoInterface>
     * @throws Exception
     */
    public static function getFieldsInfo() : array
    {
        $className = static::class;
        if (\false === \key_exists($className, self::$cache)) {
            self::$cache[$className] = static::readFieldsInfo();
        }
        return self::$cache[$className];
    }
}
