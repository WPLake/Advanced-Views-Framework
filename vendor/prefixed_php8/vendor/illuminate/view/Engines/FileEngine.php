<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\View\Engines;

use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Contracts\View\Engine;
use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Filesystem\Filesystem;
class FileEngine implements Engine
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;
    /**
     * Create a new file engine instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }
    /**
     * Get the evaluated contents of the view.
     *
     * @param  string  $path
     * @param  array  $data
     * @return string
     */
    public function get($path, array $data = [])
    {
        return $this->files->get($path);
    }
}
