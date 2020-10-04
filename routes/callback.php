<?php

Route::any('webhook', [\App\Http\Controllers\CallbackController::class, 'webhook']);