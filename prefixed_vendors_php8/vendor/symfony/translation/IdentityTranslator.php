<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Org\Wplake\Advanced_Views\Optional_Vendors\Symfony\Component\Translation;

use Org\Wplake\Advanced_Views\Optional_Vendors\Symfony\Contracts\Translation\LocaleAwareInterface;
use Org\Wplake\Advanced_Views\Optional_Vendors\Symfony\Contracts\Translation\TranslatorInterface;
use Org\Wplake\Advanced_Views\Optional_Vendors\Symfony\Contracts\Translation\TranslatorTrait;
/**
 * IdentityTranslator does not translate anything.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IdentityTranslator implements TranslatorInterface, LocaleAwareInterface
{
    use TranslatorTrait;
}
