<?php

declare (strict_types=1);
namespace Org\Wplake\Advanced_Views\Vendors\LightSource\AcfGroups\Interfaces;

use Exception;
interface LoaderInterface
{
    /**
     * @throws Exception
     */
    public function signUpGroup(string $namespace, string $fileNameWithExtension) : void;
    /**
     * @throws Exception
     */
    public function signUpGroups(string $namespace, string $folder, string $phpFilePreg = '/.php$/') : void;
    /**
     * @return string[]
     */
    public function getLoadedGroups() : array;
}
