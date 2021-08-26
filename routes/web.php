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
    
    Route::view('notifications-dropdown-menu', 'admin.layouts.notifications')->name('notifications-dropdown-menu');
    Route::get('/notificationMarkAsRead/{id}', 'DashboardController@notificationMarkAsRead');
    Route::get('/notificationMarkAllAsRead/{id}', 'DashboardController@notificationMarkAllAsRead');
    
    // Profile Routes
    Route::view('profile', 'admin.profile.index')->name('profile.index');;
    Route::view('profile/edit', 'admin.profile.edit')->name('profile.edit');
    Route::put('profile/edit', 'ProfileController@update')->name('profile.update');
    Route::put('profile/updateProfileImage', 'ProfileController@updateProfileImage')->name('profile.updateProfileImage');
    Route::put('profile/updatePrinterName', 'ProfileController@updatePrinterName')->name('profile.updatePrinterName');
    Route::put('profile/updatePrinterZone', 'ProfileController@updatePrinterZone')->name('profile.updatePrinterZone');
    Route::view('profile/password', 'admin.profile.edit_password')->name('profile.edit.password');
    Route::post('profile/password', 'ProfileController@updatePassword')->name('profile.update.password');
    Route::get('profile/printers', 'ProfileController@printers')->name('profile.printers');
    Route::get('profile/printers_zone', 'ProfileController@printers_zone')->name('profile.printers_zone');

    // User Routes
    
    Route::resource('/user', 'UserController');
    Route::get('user/ajax/change_status', 'UserController@change_status')->name('user.ajax.change_status'); // For change status

    Route::get('linnworks-user', 'UserController@linnworks_user')->name('linnworks-user');
    Route::post('linnworks-user/create', 'UserController@linnworks_user_create')->name('linnworks-user.create');
    Route::post('linnworks-user/store', 'UserController@linnworks_user_store')->name('linnworks-user.store');

    // Role Routes
    Route::put('role/{id}/update', 'RoleController@update');
    Route::resource('role', 'RoleController');

    // packingwaves Routes
    Route::resource('packingwaves', 'PackingWavesController');
    Route::get('packingwaves/ajax/data', 'PackingWavesController@datatables'); // For Datatables

    // Packlist Routes
    Route::resource('packlist', 'PackOrdersController');
    Route::get('packlist/ajax/data', 'PackOrdersController@datatables'); // For Datatables
    Route::post('packlist/ajax/printlabel', 'PackOrdersController@printlabel');
    Route::post('packlist/ajax/multiple_orders_printlabels', 'PackOrdersController@multiple_orders_printlabels');
    Route::get('packlist/order_details/{OrderId}', 'PackOrdersController@order_details')->name('packlist.order_details'); 
    Route::post('packlist/ajax/changeShippingMethod', 'PackOrdersController@changeShippingMethod');
    Route::post('packlist/ajax/cancelOrderShippingLabel', 'PackOrdersController@cancelOrderShippingLabel');
    Route::post('packlist/ajax/assignFolder', 'PackOrdersController@assignFolder');
    Route::get('packlist/packorderslist/{PickingWaveId}', 'PackOrdersController@packorderslist')->name('packlist.packorderslist'); 
    Route::post('packlist/ajax/packingwavesCompletedNotificationSend', 'PackOrdersController@packingwavesCompletedNotificationSend')->name('packlist.ajax.packingwavesCompletedNotificationSend');
    

    // packingwaves Routes
    Route::resource('pickingwaves', 'PickingWavesController');
    Route::get('pickingwaves/ajax/data', 'PickingWavesController@datatables'); // For Datatables


    // Print Button Routes
    Route::get('print_buttons/user', 'PrintButtonsController@user')->name('print_buttons.user');
    Route::get('print_buttons/ajax/data_user', 'PrintButtonsController@datatables_user'); // For Datatables
    Route::get('print_buttons/ajax/change_status_user', 'PrintButtonsController@change_status_user')->name('print_buttons.ajax.change_status_user'); // For change status

    Route::resource('print_buttons', 'PrintButtonsController');
    Route::get('print_buttons/ajax/data', 'PrintButtonsController@datatables'); // For Datatables
    Route::get('print_buttons/ajax/change_status', 'PrintButtonsController@change_status')->name('print_buttons.ajax.change_status'); // For change status


    // Folder Setting Routes
    Route::get('folder_settings/user', 'FolderSettingsController@user')->name('folder_settings.user');
    Route::get('folder_settings/ajax/data_user', 'FolderSettingsController@datatables_user'); // For Datatables
    Route::get('folder_settings/ajax/change_status_user', 'FolderSettingsController@change_status_user')->name('folder_settings.ajax.change_status_user'); // For change status

    Route::resource('folder_settings', 'FolderSettingsController');
    Route::get('folder_settings/ajax/data', 'FolderSettingsController@datatables'); // For Datatables
    Route::get('folder_settings/ajax/change_status', 'FolderSettingsController@change_status')->name('folder_settings.ajax.change_status'); // For change status

    // Setting Routes
    Route::get('setting/folders', 'SettingController@folders')->name('setting.folders');
    Route::put('setting/updateFolder', 'SettingController@updateFolder')->name('setting.updateFolder');

});

