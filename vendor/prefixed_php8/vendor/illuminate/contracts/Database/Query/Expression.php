<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Contracts\Database\Query;

use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Database\Grammar;
interface Expression
{
    /**
     * Get the value of the expression.
     *
     * @param  \Illuminate\Database\Grammar  $grammar
     * @return string|int|float
     */
    public function getValue(Grammar $grammar);
}
