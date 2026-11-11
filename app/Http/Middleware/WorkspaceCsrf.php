<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Symfony\Component\HttpFoundation\Cookie;

class WorkspaceCsrf extends PreventRequestForgery
{
    protected function newCookie($request, $config)
    {
        $cookie = parent::newCookie($request, $config);

        return new Cookie('AMERTANALA-XSRF', $cookie->getValue(), $cookie->getExpiresTime(), $cookie->getPath(), $cookie->getDomain(), $cookie->isSecure(), false, false, $cookie->getSameSite());
    }
}
