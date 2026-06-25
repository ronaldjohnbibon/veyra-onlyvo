<?php

use Illuminate\Support\Facades\Route;

Route::view('/reset-password', 'app')->name('web.tenant.password.reset');
Route::view('/leads', 'app')->name('web.tenant.leads');
Route::view('/design-requests', 'app')->name('web.tenant.design-requests');
Route::view('/analytics', 'app')->name('web.tenant.analytics');

Route::view('/{any?}', 'app')->where('any', '.*');
