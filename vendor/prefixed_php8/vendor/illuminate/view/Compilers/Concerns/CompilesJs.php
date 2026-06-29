<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\View\Compilers\Concerns;

use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Support\Js;
trait CompilesJs
{
    /**
     * Compile the "@js" directive into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileJs(string $expression)
    {
        return \sprintf("<?php echo \\%s::from(%s)->toHtml() ?>", Js::class, $this->stripParentheses($expression));
    }
}
