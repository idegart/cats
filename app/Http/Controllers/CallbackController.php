<?php

namespace App\Http\Controllers;

use App\Services\CatService;
use Illuminate\Http\Response;

class CallbackController extends Controller
{
    public function webhook(CatService $catService): Response
    {
        return $catService->reply();
    }
}
