<?php

use Illuminate\Support\Facades\Route;

Auth::routes();

Route::get('/', 'HomeController@index')->name('home');

Route::group([
    'prefix' => 'admin',
    'as' => 'admin.',
    'namespace' => 'Admin',
    'middleware' => ['auth']
], function () {
    Route::view('/', 'admin.layouts.master');
    
    // Profile Routes
    Route::view('profile', 'admin.profile.index')->name('profile.index');;
    Route::view('profile/edit', 'admin.profile.edit')->name('profile.edit');
    Route::put('profile/edit', 'ProfileController@update')->name('profile.update');
    Route::put('profile/updateProfileImage', 'ProfileController@updateProfileImage')->name('profile.updateProfileImage');
    Route::put('profile/updatePrinterName', 'ProfileController@updatePrinterName')->name('profile.updatePrinterName');
    Route::view('profile/password', 'admin.profile.edit_password')->name('profile.edit.password');
    Route::post('profile/password', 'ProfileController@updatePassword')->name('profile.update.password');
    Route::get('profile/printers', 'ProfileController@printers')->name('profile.printers');

    // User Routes
    
    Route::resource('/user', 'UserController');

    // Role Routes
    Route::put('role/{id}/update', 'RoleController@update');
    Route::resource('role', 'RoleController');

    // Company Routes
    Route::resource('packlist', 'PackOrdersController');
    Route::get('packlist/ajax/data', 'PackOrdersController@datatables'); // For Datatables
    Route::post('packlist/ajax/printlabel', 'PackOrdersController@printlabel');
    Route::post('packlist/ajax/multiple_orders_printlabels', 'PackOrdersController@multiple_orders_printlabels');
    Route::get('packlist/order_details/{OrderId}', 'PackOrdersController@order_details')->name('packlist.order_details'); 

});

