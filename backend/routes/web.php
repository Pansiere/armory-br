<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\Settings\AccountController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to(request()->user() ? route('dashboard') : route('login'));
});

Route::inertia('/privacy-policy', 'privacy')->name('privacy');

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [CharacterController::class, 'index'])->name('dashboard');

    Route::get('characters/create', [CharacterController::class, 'create'])->name('characters.create');
    Route::post('characters', [CharacterController::class, 'store'])->name('characters.store');
    Route::patch('characters/reorder', [CharacterController::class, 'reorder'])->name('characters.reorder');
    Route::get('characters/{character}/edit', [CharacterController::class, 'edit'])->name('characters.edit');
    Route::put('characters/{character}', [CharacterController::class, 'update'])->name('characters.update');
    Route::delete('characters/{character}', [CharacterController::class, 'destroy'])->name('characters.destroy');

    Route::get('settings/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::delete('account', [AccountController::class, 'destroy'])->name('account.destroy');
});

require __DIR__.'/auth.php';
