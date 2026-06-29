<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups;

use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\AcfGroupInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\FieldInfoInterface;
use ReflectionProperty;
class FieldInfo implements FieldInfoInterface
{
    private ReflectionProperty $property;
    private string $name;
    private string $type;
    /**
     * @var array<string,mixed>
     */
    private array $arguments;
    public function __construct(ReflectionProperty $property)
    {
        $this->property = $property;
        $this->name = '';
        $this->type = '';
        $this->arguments = ['a-order' => 1];
        $this->read();
    }
    /**
     * @return string[]
     */
    protected function getSupportedFieldTypes() : array
    {
        return ['bool', 'int', 'float', 'string', 'array'];
    }
    protected function readType() : void
    {
        $typeObject = $this->property->getType();
        $type = null !== $typeObject && \is_callable([$typeObject, 'getName']) ? $typeObject->getName() : '';
        if (\in_array($type, $this->getSupportedFieldTypes(), \true)) {
            $this->type = $type;
            return;
        }
        if (!\class_exists($type) || !\in_array(AcfGroupInterface::class, \class_implements($type), \true)) {
            return;
        }
        $this->type = $type;
    }
    protected function readArguments() : void
    {
        $docArguments = (string) $this->property->getDocComment();
        /** @noinspection RegExpDuplicateCharacterInClass */
        \preg_match_all('/@([a-z-_]+)[\\s]+(.+)$/m', $docArguments, $matches, \PREG_SET_ORDER);
        foreach ($matches as $matchInfo) {
            if (3 !== \count($matchInfo)) {
                continue;
            }
            $argumentName = $matchInfo[1];
            $argumentValue = $matchInfo[2];
            // '1' and '0' to int
            if ('1' === $argumentValue) {
                $argumentValue = 1;
            } elseif ('0' === $argumentValue) {
                $argumentValue = 0;
            }
            if (\is_string($argumentValue)) {
                // \n trick
                if (\false !== \strpos($argumentValue, '\\n')) {
                    $argumentValue = \str_replace('\\n', "\n", $argumentValue);
                }
                // potential json decode
                $argumentValueLength = \mb_strlen($argumentValue);
                if ($argumentValueLength > 2 && \in_array(\mb_substr($argumentValue, 0, 1), ['{', '['], \true) && \in_array(\mb_substr($argumentValue, $argumentValueLength - 1, 1), ['}', ']'], \true)) {
                    $argumentValue = (array) \json_decode($argumentValue, \true);
                }
            }
            $this->arguments[$argumentName] = $argumentValue;
        }
        // remove phpDoc solely argument
        if (\true === \key_exists('var', $this->arguments)) {
            unset($this->arguments['var']);
        }
    }
    protected function read() : void
    {
        $this->name = $this->property->getName();
        $this->readType();
        $this->readArguments();
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getType() : string
    {
        return $this->type;
    }
    /**
     * @return array<string,mixed>
     */
    public function getArguments() : array
    {
        return $this->arguments;
    }
    public function isRepeater() : bool
    {
        return 'array' === $this->type && isset($this->arguments['item']);
    }
    /**
     * @param mixed $value
     */
    public function setArgument(string $name, $value) : void
    {
        $this->arguments[$name] = $value;
    }
    public function setName(string $name) : void
    {
        $this->name = $name;
    }
}
