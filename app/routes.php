<?php

Route::get('/', ['https', function(){ return View::make('hello'); }]);

Route::get('/infusionsoft',             ['https', 'uses' => 'InfusionsoftController@getLink']);
Route::get('/infusionsoft/callback',    ['https', 'uses' => 'InfusionsoftController@callback']);
Route::get('/infusionsoft/contacts',    ['https', 'uses' => 'InfusionsoftController@contacts']);
Route::get('/infusionsoft/contact',     ['https', 'uses' => 'InfusionsoftController@contact']);
Route::get('/infusionsoft/products',    ['https', 'uses' => 'InfusionsoftController@products']);
Route::get('/infusionsoft/product',     ['https', 'uses' => 'InfusionsoftController@product']);

Route::post('/infusionsoft/payment', 	['https', 'uses' => 'InfusionsoftController@paymentInfo']);
Route::post('/infusionsoft/subscription',     ['https', 'uses' => 'InfusionsoftController@makeSubscription']);


Route::get('/infusionsoft/retrieve',     ['https', 'uses' => 'InfusionsoftController@retrieve']);