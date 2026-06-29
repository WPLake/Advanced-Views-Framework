<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Org\Wplake\Advanced_Views\Optional_Vendors\Symfony\Component\Clock;

use Org\Wplake\Advanced_Views\Optional_Vendors\Psr\Clock\ClockInterface as PsrClockInterface;
/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
interface ClockInterface extends PsrClockInterface
{
    public function sleep(float|int $seconds) : void;
    public function withTimeZone(\DateTimeZone|string $timezone) : static;
}
