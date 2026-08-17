<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Luiscamp\LaravelLogMonitor\Http\Controllers\LogClearAllController;
use Luiscamp\LaravelLogMonitor\Http\Controllers\LogClearController;
use Luiscamp\LaravelLogMonitor\Http\Controllers\LogDeleteAllController;
use Luiscamp\LaravelLogMonitor\Http\Controllers\LogDeleteController;
use Luiscamp\LaravelLogMonitor\Http\Controllers\LogDownloadController;
use Luiscamp\LaravelLogMonitor\Http\Controllers\LogViewerController;

Route::get('/', [LogViewerController::class, 'index'])->name('log-monitor.index');
Route::get('/files', [LogViewerController::class, 'files'])->name('log-monitor.files');
Route::post('/clear-all', LogClearAllController::class)->name('log-monitor.clear-all');
Route::delete('/delete-all', LogDeleteAllController::class)->name('log-monitor.delete-all');
Route::get('/{file}/download', LogDownloadController::class)->name('log-monitor.download');
Route::post('/{file}/clear', LogClearController::class)->name('log-monitor.clear');
Route::delete('/{file}', LogDeleteController::class)->name('log-monitor.delete');
Route::get('/{file}', [LogViewerController::class, 'show'])->name('log-monitor.show');
