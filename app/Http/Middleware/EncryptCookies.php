<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EncryptCookies extends \Illuminate\Cookie\Middleware\EncryptCookies
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];
}
