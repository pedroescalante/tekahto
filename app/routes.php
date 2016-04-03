<?php

Route::get('/', ['https', function(){ return View::make('hello'); }]);

//Views
Route::get('/infusionsoft',             	['https', 'uses' => 'InfusionsoftController@getLink']);
Route::get('/infusionsoft/callback',    	['https', 'uses' => 'InfusionsoftController@callback']);
Route::get('/infusionsoft/contacts',    	['https', 'uses' => 'InfusionsoftController@contacts']);
Route::get('/infusionsoft/contact',     	['https', 'uses' => 'InfusionsoftController@contact']);
Route::get('/infusionsoft/products',    	['https', 'uses' => 'InfusionsoftController@products']);
Route::get('/infusionsoft/product',     	['https', 'uses' => 'InfusionsoftController@product']);

//BOF Endpoints
Route::post('/infusionsoft/payment', 		['https', 'uses' => 'InfusionsoftController@paymentInfo']);
Route::post('/infusionsoft/upgrade', 		['https', 'uses' => 'InfusionsoftController@makeSubscription']);

//Test Endpoints
Route::post('/infusionsoft/test',     		['https', 'uses' => 'InfusionsoftController@payment']);
Route::post('/infusionsoft/token',     		['https', 'uses' => 'InfusionsoftController@refreshToken']);