<?php

use App\Http\Controllers\CharacterController;
use App\Http\Controllers\CharacterEquipmentController;
use App\Http\Controllers\CharacterRaidLockController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PublicCharacterController;
use App\Http\Controllers\Settings\AccountController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->to(request()->user() ? route('dashboard') : route('login'));
});

Route::inertia('/privacy-policy', 'privacy')->name('privacy');

Route::middleware('throttle:30,1')->group(function () {
    Route::get('p/{token}', [PublicCharacterController::class, 'show'])->name('characters.public');
    Route::get('p/{token}/preview.png', [PublicCharacterController::class, 'image'])->name('characters.public.image');
});

// Fora do grupo auth de propósito: dado de item não é sensível. Usado hoje
// só pelas páginas React (dashboard/edição) — o perfil público é Blade puro
// sem JS (ver PublicCharacterController) e fica de fora por enquanto.
Route::middleware('throttle:60,1')->group(function () {
    Route::get('items/{item}/tooltip', [ItemController::class, 'tooltip'])->name('items.tooltip');
});

Route::middleware('auth')->group(function () {
    Route::get('dashboard', [CharacterController::class, 'index'])->name('dashboard');

    Route::get('characters/create', [CharacterController::class, 'create'])->name('characters.create');
    Route::post('characters', [CharacterController::class, 'store'])->name('characters.store');
    Route::post('characters/import-json', [CharacterController::class, 'importJson'])->name('characters.import-json');
    Route::patch('characters/reorder', [CharacterController::class, 'reorder'])->name('characters.reorder');
    Route::get('characters/{character}/edit', [CharacterController::class, 'edit'])->name('characters.edit');
    Route::get('characters/{character}/export', [CharacterController::class, 'export'])->name('characters.export');
    Route::put('characters/{character}', [CharacterController::class, 'update'])->name('characters.update');
    Route::delete('characters/{character}', [CharacterController::class, 'destroy'])->name('characters.destroy');

    Route::post('characters/{character}/equipment/{spec}/import', [CharacterEquipmentController::class, 'import'])->name('characters.equipment.import');
    Route::put('characters/{character}/equipment/{spec}/{slot}', [CharacterEquipmentController::class, 'update'])->name('characters.equipment.update');
    Route::delete('characters/{character}/equipment/{spec}/{slot}', [CharacterEquipmentController::class, 'destroy'])->name('characters.equipment.destroy');
    Route::put('characters/{character}/equipment/{spec}/{slot}/gems/{position}', [CharacterEquipmentController::class, 'updateGem'])->name('characters.equipment.gems.update');
    Route::delete('characters/{character}/equipment/{spec}/{slot}/gems/{position}', [CharacterEquipmentController::class, 'destroyGem'])->name('characters.equipment.gems.destroy');

    Route::put('characters/{character}/raid-locks/{raid}/{size}', [CharacterRaidLockController::class, 'update'])->name('characters.raid-locks.update');
    Route::delete('characters/{character}/raid-locks/{raid}/{size}', [CharacterRaidLockController::class, 'destroy'])->name('characters.raid-locks.destroy');

    Route::get('items/search', [ItemController::class, 'search'])->name('items.search');

    Route::get('settings/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::delete('account', [AccountController::class, 'destroy'])->name('account.destroy');
});

require __DIR__.'/auth.php';
