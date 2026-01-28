<?php

use App\Http\Controllers\EntityController;
use App\Http\Controllers\EntityEventController;
use App\Http\Controllers\EntityRankingController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/settings', [SettingController::class, 'index']);
Route::get('/settings/{key}', [SettingController::class, 'show']);
Route::patch('/settings/{key}', [SettingController::class, 'update']);

Route::post('/entities', [EntityController::class, 'store']);
Route::get('/entities', [EntityController::class, 'index']);

Route::post('/entities/{entityId}/events', [EntityEventController::class, 'store']);

Route::get('/entities/ranking/critical', [EntityRankingController::class, 'critical']);
