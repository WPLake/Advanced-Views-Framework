<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups;

use Exception;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\AcfGroupInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\CreatorInterface;
use Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces\LoaderInterface;
use ReflectionClass;
class Loader implements LoaderInterface
{
    /**
     * @var string[]
     */
    private array $loadedGroups;
    private float $loadedTimeInSeconds;
    private int $numberOfGroups;
    public function __construct()
    {
        $this->loadedGroups = [];
        $this->loadedTimeInSeconds = 0;
        $this->numberOfGroups = 0;
    }
    /**
     * @param string $phpClass
     *
     * @return array<string,mixed>|null
     * @throws Exception
     */
    protected function getAcfGroupInfo(string $phpClass) : ?array
    {
        if (!\class_exists($phpClass) || !\in_array(AcfGroupInterface::class, \class_implements($phpClass), \true)) {
            // without any error, because php files can contain other things
            return null;
        }
        $reflectionClass = new ReflectionClass($phpClass);
        // ignore abstract or not local groups
        if ($reflectionClass->isAbstract()) {
            return null;
        }
        // @phpstan-ignore-next-line
        $isLocalGroup = \call_user_func([$phpClass, 'isLocalGroup']);
        $isLocalGroup = \true === $isLocalGroup;
        if (\true === $isLocalGroup) {
            // @phpstan-ignore-next-line
            $groupInfo = \call_user_func([$phpClass, 'getGroupInfo']);
            $this->loadedGroups[] = $phpClass;
            return \is_array($groupInfo) ? $groupInfo : null;
        }
        return null;
    }
    /**
     * @param string[] $phpFileNames
     *
     * @return array<int,array<string,mixed>>
     * @throws Exception
     */
    protected function loadFiles(string $namespace, array $phpFileNames) : array
    {
        $acfGroupsInfo = [];
        foreach ($phpFileNames as $phpFileName) {
            $phpClass = \implode('\\', [$namespace, \str_replace('.php', '', $phpFileName)]);
            $acfGroupInfo = $this->getAcfGroupInfo($phpClass);
            if (null === $acfGroupInfo) {
                continue;
            }
            $acfGroupsInfo[] = $acfGroupInfo;
        }
        return $acfGroupsInfo;
    }
    /**
     * @return array<int,array<string,mixed>>
     * @throws Exception
     */
    protected function loadDirectory(string $directory, string $namespace, string $phpFilePreg = '/.php$/') : array
    {
        $acfGroupsInfo = [];
        $fileNames = \scandir($directory);
        $fileNames = \false === $fileNames ? [] : $fileNames;
        // exclude ., ..
        $fs = \array_diff($fileNames, ['.', '..']);
        $phpFileNames = \array_filter($fs, function ($f) use($phpFilePreg) {
            return 1 === \preg_match($phpFilePreg, $f);
        });
        $phpFileNames = \array_values($phpFileNames);
        $subDirectoryNames = \array_filter($fs, function ($f) {
            return \false === \strpos($f, '.');
        });
        $subDirectoryNames = \array_values($subDirectoryNames);
        foreach ($subDirectoryNames as $subDirectoryName) {
            $subDirectory = \implode(\DIRECTORY_SEPARATOR, [$directory, $subDirectoryName]);
            $subNamespace = \implode('\\', [$namespace, $subDirectoryName]);
            $acfGroupsInfo = \array_merge($acfGroupsInfo, $this->loadDirectory($subDirectory, $subNamespace, $phpFilePreg));
        }
        return \array_merge($acfGroupsInfo, $this->loadFiles($namespace, $phpFileNames));
    }
    /**
     * @param array<int,array<string,mixed>> $acfGroupsInfo
     */
    protected function signUpGroupsInAcf(array $acfGroupsInfo) : void
    {
        $signUpFunction = function () use($acfGroupsInfo) {
            if (!\function_exists('acf_add_local_field_group')) {
                return;
            }
            foreach ($acfGroupsInfo as $acfGroupInfo) {
                \acf_add_local_field_group($acfGroupInfo);
            }
        };
        // acf_add_local_field_group() method should be called in the acf init action
        if (\function_exists('add_action')) {
            \add_action('acf/init', $signUpFunction);
        } else {
            // just for tests
            $signUpFunction();
        }
    }
    /**
     * @return string[]
     */
    public function getLoadedGroups() : array
    {
        return $this->loadedGroups;
    }
    /**
     * @throws Exception
     */
    public function signUpGroup(string $namespace, string $fileNameWithExtension) : void
    {
        $acfGroupsInfo = $this->loadFiles($namespace, [$fileNameWithExtension]);
        $this->signUpGroupsInAcf($acfGroupsInfo);
    }
    /**
     * @throws Exception
     */
    public function signUpGroups(string $namespace, string $folder, string $phpFilePreg = '/.php$/') : void
    {
        $loadStartTime = \microtime(\true);
        $acfGroupsInfo = $this->loadDirectory($folder, $namespace, $phpFilePreg);
        $this->loadedTimeInSeconds += \microtime(\true) - $loadStartTime;
        $this->numberOfGroups += \count($acfGroupsInfo);
        $this->signUpGroupsInAcf($acfGroupsInfo);
    }
    public function getLoadedTimeInSeconds() : float
    {
        return $this->loadedTimeInSeconds;
    }
    public function getNumberOfGroups() : int
    {
        return $this->numberOfGroups;
    }
}
