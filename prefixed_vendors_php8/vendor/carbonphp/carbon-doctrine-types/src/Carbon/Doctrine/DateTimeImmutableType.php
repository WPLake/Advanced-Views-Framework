<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Optional_Vendors\Carbon\Doctrine;

use Org\Wplake\Advanced_Views\Optional_Vendors\Carbon\CarbonImmutable;
use DateTimeImmutable;
use Org\Wplake\Advanced_Views\Optional_Vendors\Doctrine\DBAL\Platforms\AbstractPlatform;
use Org\Wplake\Advanced_Views\Optional_Vendors\Doctrine\DBAL\Types\VarDateTimeImmutableType;
class DateTimeImmutableType extends VarDateTimeImmutableType implements CarbonDoctrineType
{
    /** @use CarbonTypeConverter<CarbonImmutable> */
    use CarbonTypeConverter;
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform) : ?CarbonImmutable
    {
        return $this->doConvertToPHPValue($value);
    }
    /**
     * @return class-string<CarbonImmutable>
     */
    protected function getCarbonClassName() : string
    {
        return CarbonImmutable::class;
    }
}
