<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CharacterEquipmentController;
use App\Http\Controllers\ItemController;
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

    Route::post('characters/{character}/equipment/import', [CharacterEquipmentController::class, 'import'])->name('characters.equipment.import');
    Route::put('characters/{character}/equipment/{slot}', [CharacterEquipmentController::class, 'update'])->name('characters.equipment.update');
    Route::delete('characters/{character}/equipment/{slot}', [CharacterEquipmentController::class, 'destroy'])->name('characters.equipment.destroy');

    Route::get('items/search', [ItemController::class, 'search'])->name('items.search');

    Route::get('settings/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::delete('account', [AccountController::class, 'destroy'])->name('account.destroy');
});

require __DIR__.'/auth.php';
