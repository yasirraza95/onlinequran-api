<?php

namespace App\Http\Controllers\V1;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponser;

use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use ApiResponser;

    protected function respondWithToken($request, $user, $token)
    {
        return $this->loginResponse($request, $user, $token);
    }
}
