<?php

Route::get('/', function(){ return View::make('hello'); });

Route::get('/infusionsoft',             ['uses' =>'InfusionsoftController@getLink']);
Route::get('/infusionsoft/callback',    ['https', 'uses' => 'InfusionsoftController@callback']);
Route::get('/infusionsoft/token',       ['uses' => 'InfusionsoftController@sendToken']);
Route::get('/infusionsoft/contacts',    ['uses' => 'InfusionsoftController@contacts']);
Route::get('/infusionsoft/contact',     ['uses' => 'InfusionsoftController@contact']);
Route::get('/infusionsoft/products',    ['uses' => 'InfusionsoftController@products']);
Route::get('/infusionsoft/product',     ['uses' => 'InfusionsoftController@product']);
Route::get('/infusionsoft/invoice',     ['uses' => 'InfusionsoftController@invoice']);