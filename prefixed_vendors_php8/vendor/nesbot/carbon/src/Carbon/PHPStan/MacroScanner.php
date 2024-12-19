<?php

declare (strict_types=1);
/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Org\Wplake\Advanced_Views\Optional_Vendors\Carbon\PHPStan;

use Org\Wplake\Advanced_Views\Optional_Vendors\Carbon\CarbonInterface;
use Org\Wplake\Advanced_Views\Optional_Vendors\Carbon\FactoryImmutable;
use InvalidArgumentException;
use Org\Wplake\Advanced_Views\Optional_Vendors\PHPStan\Reflection\ReflectionProvider;
use ReflectionException;
final class MacroScanner
{
    /**
     * @var \PHPStan\Reflection\ReflectionProvider
     */
    private $reflectionProvider;
    /**
     * MacroScanner constructor.
     *
     * @param \PHPStan\Reflection\ReflectionProvider $reflectionProvider
     */
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }
    /**
     * Return true if the given pair class-method is a Carbon macro.
     *
     * @param class-string $className
     * @param string       $methodName
     *
     * @return bool
     */
    public function hasMethod(string $className, string $methodName) : bool
    {
        $classReflection = $this->reflectionProvider->getClass($className);
        if ($classReflection->getName() !== CarbonInterface::class && !$classReflection->isSubclassOf(CarbonInterface::class)) {
            return \false;
        }
        return \is_callable([$className, 'hasMacro']) && $className::hasMacro($methodName);
    }
    /**
     * Return the Macro for a given pair class-method.
     *
     * @param class-string $className
     * @param string       $methodName
     *
     * @throws ReflectionException
     *
     * @return Macro
     */
    public function getMethod(string $className, string $methodName) : Macro
    {
        $macros = FactoryImmutable::getDefaultInstance()->getSettings()['macros'] ?? [];
        $macro = $macros[$methodName] ?? throw new InvalidArgumentException("Macro '{$methodName}' not found");
        return new Macro($className, $methodName, $macro);
    }
}
