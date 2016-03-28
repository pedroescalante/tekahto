<?php

Route::get('/', function(){ return View::make('hello'); });

Route::get('/infusionsoft',             ['https', 'uses' =>'InfusionsoftController@getLink']);
Route::get('/infusionsoft/callback',    ['https', 'uses' => 'InfusionsoftController@callback']);
Route::get('/infusionsoft/token',       ['https', 'uses' => 'InfusionsoftController@sendToken']);
Route::post('/infusionsoft/token', 	['https', 'uses' => 'InfusionsoftController@sendToken']);
Route::get('/infusionsoft/contacts',    ['https', 'uses' => 'InfusionsoftController@contacts']);
Route::get('/infusionsoft/contact',     ['https', 'uses' => 'InfusionsoftController@contact']);
Route::get('/infusionsoft/products',    ['https', 'uses' => 'InfusionsoftController@products']);
Route::get('/infusionsoft/product',     ['https', 'uses' => 'InfusionsoftController@product']);
Route::get('/infusionsoft/invoice',     ['https', 'uses' => 'InfusionsoftController@invoice']);
