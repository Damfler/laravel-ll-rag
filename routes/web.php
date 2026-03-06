<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SpaceController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Главная — редирект на список разделов ────────────────────────────────────
Route::get('/', fn () => redirect()->route('spaces.index'));

// ─── Аутентифицированные роуты ────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

    // Профиль
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Поиск
    Route::get('/search', SearchController::class)->name('search');

    // Разделы (Spaces)
    Route::resource('spaces', SpaceController::class);

    // Страницы внутри раздела
    Route::prefix('spaces/{space}')->name('pages.')->group(function () {
        Route::get('/pages/create', [PageController::class, 'create'])->name('create');
        Route::post('/pages', [PageController::class, 'store'])->name('store');
        Route::get('/pages/{page}', [PageController::class, 'show'])->name('show');
        Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('edit');
        Route::put('/pages/{page}', [PageController::class, 'update'])->name('update');
        Route::delete('/pages/{page}', [PageController::class, 'destroy'])->name('destroy');
        Route::get('/pages/{page}/history', [PageController::class, 'history'])->name('history');
    });
});

require __DIR__.'/auth.php';
