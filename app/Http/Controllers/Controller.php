<?php

namespace App\Http\Controllers;

use App\Traits\GetsAuthUser;
use App\Traits\GetsPagination;
use App\Traits\RespondsWithHttpStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, GetsAuthUser, GetsPagination, RespondsWithHttpStatus, ValidatesRequests;
}
