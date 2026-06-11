<?php

use Illuminate\Support\Facades\Route;
use Pilot\Laravel\Http\Controllers\InContextBlockController;
use Pilot\Laravel\Http\Controllers\PreviewController;
use Pilot\Laravel\Http\Middleware\EnsureValidPilotPreview;

Route::get(trim((string) config('pilot.preview.path', '_pilot/preview'), '/').'/{content}', PreviewController::class)
    ->middleware(EnsureValidPilotPreview::class)
    ->name('pilot.preview');

Route::prefix(trim((string) config('pilot.in_context.path', '_pilot/in-context'), '/'))
    ->name('pilot.in-context.')
    ->group(function (): void {
        Route::get('/contents/{content}/sync', [InContextBlockController::class, 'contentSync'])->name('contents.sync');
        Route::get('/blocks/{block}', [InContextBlockController::class, 'show'])->name('blocks.show');
        Route::patch('/blocks/{block}', [InContextBlockController::class, 'update'])->name('blocks.update');
    });
