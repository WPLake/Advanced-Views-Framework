<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces;

interface FieldInfoInterface
{
    public function getName() : string;
    public function getType() : string;
    /**
     * @return array<string,mixed>
     */
    public function getArguments() : array;
    public function isRepeater() : bool;
    /**
     * for extending purposes, so arguments can be added/changed on a fly
     * @param mixed $value
     */
    public function setArgument(string $name, $value) : void;
    public function setName(string $name) : void;
}
