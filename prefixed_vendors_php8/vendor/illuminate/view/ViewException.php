<?php

namespace Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\View;

use ErrorException;
use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Container\Container;
use Org\Wplake\Advanced_Views\Optional_Vendors\Illuminate\Support\Reflector;
class ViewException extends ErrorException
{
    /**
     * Report the exception.
     *
     * @return bool|null
     */
    public function report()
    {
        $exception = $this->getPrevious();
        if (Reflector::isCallable($reportCallable = [$exception, 'report'])) {
            return Container::getInstance()->call($reportCallable);
        }
        return \false;
    }
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|null
     */
    public function render($request)
    {
        $exception = $this->getPrevious();
        if ($exception && \method_exists($exception, 'render')) {
            return $exception->render($request);
        }
    }
}
