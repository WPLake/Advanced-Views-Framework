<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Optional_Vendors\Doctrine\Inflector\Rules\Spanish;

use Org\Wplake\Advanced_Views\Optional_Vendors\Doctrine\Inflector\Rules\Patterns;
use Org\Wplake\Advanced_Views\Optional_Vendors\Doctrine\Inflector\Rules\Ruleset;
use Org\Wplake\Advanced_Views\Optional_Vendors\Doctrine\Inflector\Rules\Substitutions;
use Org\Wplake\Advanced_Views\Optional_Vendors\Doctrine\Inflector\Rules\Transformations;
final class Rules
{
    public static function getSingularRuleset() : Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getSingular()), new Patterns(...Uninflected::getSingular()), (new Substitutions(...Inflectible::getIrregular()))->getFlippedSubstitutions());
    }
    public static function getPluralRuleset() : Ruleset
    {
        return new Ruleset(new Transformations(...Inflectible::getPlural()), new Patterns(...Uninflected::getPlural()), new Substitutions(...Inflectible::getIrregular()));
    }
}
