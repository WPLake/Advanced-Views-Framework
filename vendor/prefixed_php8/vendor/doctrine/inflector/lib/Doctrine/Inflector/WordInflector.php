<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Optional_Vendors\Doctrine\Inflector;

interface WordInflector
{
    public function inflect(string $word) : string;
}
