<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Pete\LaravelManager\Http\LaravelManagerController as LM;

Route::middleware(['web'])
    ->prefix('laravel-manager')
    ->name('lm.')
    ->group(function (): void {
        Route::get('/',        [LM::class, 'index'])->name('index');
        Route::get('/create',  [LM::class, 'create'])->name('create');
        Route::post('/',       [LM::class, 'store'])->name('store');

        Route::get('/logs/{id}', [LM::class, 'logs'])->whereNumber('id')->name('logs');

        Route::post('/delete',       [LM::class, 'delete'])->name('delete');
        Route::post('/generate-ssl', [LM::class, 'generateSsl'])->name('generate-ssl');
    });
