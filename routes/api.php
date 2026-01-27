<?php

use App\Http\Controllers\EntityController;
use App\Http\Controllers\EntityEventController;
use App\Http\Controllers\EntityRankingController;
use Illuminate\Support\Facades\Route;

Route::post('/entities', [EntityController::class, 'store']);
Route::get('/entities', [EntityController::class, 'index']);

Route::post('/entities/{entityId}/events', [EntityEventController::class, 'store']);

Route::get('/entities/ranking/critical', [EntityRankingController::class, 'critical']);
