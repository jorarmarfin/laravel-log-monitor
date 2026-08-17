<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Luiscamp\LaravelLogMonitor\Http\Controllers\LogClearAllController;
use Luiscamp\LaravelLogMonitor\Http\Controllers\LogClearController;
use Luiscamp\LaravelLogMonitor\Http\Controllers\LogDownloadController;
use Luiscamp\LaravelLogMonitor\Http\Controllers\LogViewerController;

Route::get('/', [LogViewerController::class, 'index'])->name('log-monitor.index');
Route::get('/files', [LogViewerController::class, 'files'])->name('log-monitor.files');
Route::post('/clear-all', LogClearAllController::class)->name('log-monitor.clear-all');
Route::get('/{file}/download', LogDownloadController::class)->name('log-monitor.download');
Route::post('/{file}/clear', LogClearController::class)->name('log-monitor.clear');
Route::get('/{file}', [LogViewerController::class, 'show'])->name('log-monitor.show');
