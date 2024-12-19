<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups;

use Exception;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\AcfGroupInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\CreatorInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\DbQueryManagerInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\FieldInfoInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\GroupInfoInterface;
use ReflectionProperty;
class Creator implements CreatorInterface
{
    /**
     * @var class-string<GroupInfoInterface>[]
     */
    private array $creationChain;
    /**
     * @var array<string, AcfGroupInterface>
     */
    private array $cache;
    private DbQueryManagerInterface $dbQueryManager;
    public function __construct(DbQueryManagerInterface $dbQueryManager = null)
    {
        $this->creationChain = [];
        $this->cache = [];
        $this->dbQueryManager = null !== $dbQueryManager ? $dbQueryManager : new DbQueryManager();
    }
    /**
     * @template T of AcfGroupInterface
     *
     * @param class-string<T> $groupClass
     *
     * @return T
     * @throws Exception
     */
    public function create(string $groupClass) : AcfGroupInterface
    {
        // using cache + getDeepClone() is much faster then the raw creation,
        // because inside the Group the PHP reflection features are in use, which are expensive.
        if (\false === \key_exists($groupClass, $this->cache)) {
            if (!\class_exists($groupClass) || !\in_array(AcfGroupInterface::class, \class_implements($groupClass), \true)) {
                throw new Exception('Fail to create a group instance, group class must implement AcfGroupInterface, class : ' . $groupClass);
            }
            if (\in_array($groupClass, $this->creationChain, \true)) {
                throw new Exception('Fail to create a group instance.' . 'The next group constructor (' . $groupClass . ') will run a recursion, current classes chain is :' . \print_r($this->creationChain, \true));
            }
            $this->creationChain[] = $groupClass;
            try {
                $group = new $groupClass($this);
                // we need try/catch here, as class may expect more arguments in constructor
                // @phpstan-ignore-next-line
            } catch (Exception $exception) {
                throw new Exception('Fail to create instance of an acf group class, class : ' . $groupClass . ', issue : ' . $exception->getMessage());
            }
            \array_splice($this->creationChain, \count($this->creationChain) - 1, 1);
            $this->cache[$groupClass] = $group;
        }
        // @phpstan-ignore-next-line
        return $this->cache[$groupClass]->getDeepClone();
    }
    public function getDbQueryManager() : DbQueryManagerInterface
    {
        return $this->dbQueryManager;
    }
}
