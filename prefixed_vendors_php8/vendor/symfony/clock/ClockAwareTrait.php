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

use Org\Wplake\Advanced_Views\Optional_Vendors\Psr\Clock\ClockInterface;
use Org\Wplake\Advanced_Views\Optional_Vendors\Symfony\Contracts\Service\Attribute\Required;
/**
 * A trait to help write time-sensitive classes.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
trait ClockAwareTrait
{
    private readonly ClockInterface $clock;
    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setClock(ClockInterface $clock) : void
    {
        $this->clock = $clock;
    }
    protected function now() : DatePoint
    {
        $now = ($this->clock ??= new Clock())->now();
        return $now instanceof DatePoint ? $now : DatePoint::createFromInterface($now);
    }
}
