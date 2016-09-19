<?php

Route::any('/contact', ['uses' => 'InfusionsoftController@contact']);

Route::get('/', ['https', function(){ return View::make('index'); }]);
Route::get('/infusionsoft',             	['https', 'uses' => 'InfusionsoftController@getLink']);
Route::get('/infusionsoft/callback',    	['https', 'uses' => 'InfusionsoftController@callback']);
Route::get('/infusionsoft/contacts',    	['https', 'uses' => 'InfusionsoftController@contacts']);

Route::get('/infusionsoft/list/{file_name}',    ['https', 'uses' => 'InfusionsoftController@contactList']);

//Views
//Route::group(['before'=>'bof'], function(){
	Route::get('/infusionsoft/contact',     	['https', 'uses' => 'InfusionsoftController@contact']);
	Route::get('/infusionsoft/products',    	['https', 'uses' => 'InfusionsoftController@products']);
	Route::get('/infusionsoft/product',     	['https', 'uses' => 'InfusionsoftController@product']);
	Route::get('/infusionsoft/tags', 		['https', 'uses' => 'InfusionsoftController@allTags']);
	Route::get('/infusionsoft/tags/{tag_id}',       ['https', 'uses' => 'InfusionsoftController@tags']);
	Route::get('/infusionsoft/subscr',     		['https', 'uses' => 'InfusionsoftController@subscr']);
//});

//BOF Endpoints
Route::post('/infusionsoft/payment', 		['https', 'uses' => 'InfusionsoftController@paymentInfo']);
Route::post('/infusionsoft/upgrade', 		['https', 'uses' => 'InfusionsoftController@makeSubscription']);

//Test Endpoints
Route::post('/infusionsoft/test',     		['https', 'uses' => 'InfusionsoftController@payment']);
Route::post('/infusionsoft/token',     		['https', 'uses' => 'InfusionsoftController@refreshToken']);
