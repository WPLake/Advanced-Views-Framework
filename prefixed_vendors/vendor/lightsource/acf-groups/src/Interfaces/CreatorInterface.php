<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces;

use Exception;
interface CreatorInterface
{
    /**
     * @template T of AcfGroupInterface
     *
     * @param class-string<T> $groupClass
     *
     * @return T
     * @throws Exception
     */
    public function create(string $groupClass) : AcfGroupInterface;
    public function getDbQueryManager() : DbQueryManagerInterface;
}
