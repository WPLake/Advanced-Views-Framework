<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Support;

use Org\Wplake\Advanced_Views\Optional_Vendors\Carbon\Carbon as BaseCarbon;
use Org\Wplake\Advanced_Views\Optional_Vendors\Carbon\CarbonImmutable as BaseCarbonImmutable;
use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Support\Traits\Conditionable;
use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Support\Traits\Dumpable;
use Org\Wplake\Advanced_Views\Optional_Vendors\Ramsey\Uuid\Uuid;
use Org\Wplake\Advanced_Views\Optional_Vendors\Symfony\Component\Uid\Ulid;
class Carbon extends BaseCarbon
{
    use Conditionable, Dumpable;
    /**
     * {@inheritdoc}
     */
    public static function setTestNow(mixed $testNow = null) : void
    {
        BaseCarbon::setTestNow($testNow);
        BaseCarbonImmutable::setTestNow($testNow);
    }
    /**
     * Create a Carbon instance from a given ordered UUID or ULID.
     */
    public static function createFromId(Uuid|Ulid|string $id) : static
    {
        if (\is_string($id)) {
            $id = Ulid::isValid($id) ? Ulid::fromString($id) : Uuid::fromString($id);
        }
        return static::createFromInterface($id->getDateTime());
    }
}
