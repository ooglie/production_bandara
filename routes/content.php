<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Code-managed Bandara content pages
|--------------------------------------------------------------------------
*/

Route::view('/about-us', 'pages.about')->name('content.about');
Route::view('/help', 'pages.help')->name('content.help');
Route::view('/terms', 'pages.terms')->name('content.terms');
Route::view('/privacy', 'pages.privacy')->name('content.privacy');

Route::redirect('/faq', '/help', 301)->name('content.faq.redirect');
Route::redirect('/terms-and-conditions', '/terms', 301)->name('content.terms.redirect');
Route::redirect('/privacy-policy', '/privacy', 301)->name('content.privacy.redirect');
