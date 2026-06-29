<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces;

use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\FieldInfo;
use Exception;
interface FieldsInfoInterface
{
    /**
     * @return array<string,FieldInfoInterface>
     * @throws Exception
     */
    public static function getFieldsInfo() : array;
}
