<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Optional_Vendors\Carbon\Doctrine;

use Org\Wplake\Advanced_Views\Optional_Vendors\Carbon\Carbon;
use DateTime;
use Org\Wplake\Advanced_Views\Optional_Vendors\Doctrine\DBAL\Platforms\AbstractPlatform;
use Org\Wplake\Advanced_Views\Optional_Vendors\Doctrine\DBAL\Types\VarDateTimeType;
class DateTimeType extends VarDateTimeType implements CarbonDoctrineType
{
    /** @use CarbonTypeConverter<Carbon> */
    use CarbonTypeConverter;
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform) : ?Carbon
    {
        return $this->doConvertToPHPValue($value);
    }
}
