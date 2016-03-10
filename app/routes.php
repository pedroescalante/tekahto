<?php

Route::get('/', function(){ return View::make('hello'); });

Route::get('/infusionsoft', ['uses'=>'InfusionsoftController@getLink']);
Route::get('/infusionsoft/callback', [ 'https', 'uses'=>'InfusionsoftController@callback']);
Route::get('/infusionsoft/token', ['uses'=>'InfusionsoftController@sendToken']);
Route::get('/infusionsoft/contacts', ['uses'=>'InfusionsoftController@contacts']);

Route::get('contacts/byemail', [ function()
{
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    $infusionsoft->setToken(unserialize(Session::get('token')));
    $email = Request::get('email');
    
    try 
    {
        $email = Request::get('email');
        $contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
    } 
    catch (InfusionsoftTokenExpiredException $e) 
    {
        $infusionsoft->refreshAccessToken();
        Session::put( 'token', serialize( $infusionsoft->getToken() ) );

        $contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
    }    

    $data = array();
    foreach ($contacts as $contact) 
    {
        $c = $infusionsoft->contacts->load($contact['Id'], ['Id', 'FirstName', 'LastName']);

        $credit_cards = $infusionsoft->data->query(
                    'CreditCard',
                    10, 0,
                    ['ContactID' => $c['Id']],
                    ['CardType', 'Last4', 'Status'],
                    'Last4',
                    true);
        $c['CreditCards'] = $credit_cards;
        $data[] = $c;
    }

    return View::make('contactbyemail', ['contact'=>$data[0]]);
    
}]);

Route::get('products', [ function()
{
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    $infusionsoft->setToken(unserialize(Session::get('token')));
    
    try 
    {
        $email = Request::get('email');

        $products = $infusionsoft->data->query(
                    'Product',
                    10, 0,
                    ['Status' => '1'],
                    ['Id', 'ProductName', 'Description', 'ProductPrice', 'Status'],
                    'ProductName',
                    true);
    } 
    catch (InfusionsoftTokenExpiredException $e) 
    {
        $infusionsoft->refreshAccessToken();
        Session::put( 'token', serialize( $infusionsoft->getToken() ) );

        $products = $infusionsoft->data->query(
                    'Product',
                    10, 0,
                    ['Status' => '1'],
                    ['Id', 'ProductName', 'Description', 'ProductPrice', 'Status'],
                    'ProductName',
                    true);
    }

    return View::make('products', ['products'=>$products]);
    
}]);

Route::get('product', [ function()
{
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    $infusionsoft->setToken(unserialize(Session::get('token')));
    $id = Request::get('id');
    
    try 
    {
        $product = $infusionsoft->products->find($id);
    } 
    catch (InfusionsoftTokenExpiredException $e) 
    {
        $infusionsoft->refreshAccessToken();
        Session::put( 'token', serialize( $infusionsoft->getToken() ) );

        $product = $infusionsoft->products->find($id);
    }    

    return View::make('product', ['product'=>$product]);
    
}]);

Route::get('invoice', [ function()
{
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    $infusionsoft->setToken(unserialize(Session::get('token')));
    
    try
    {
        $email = Request::get('email');
        $product_id = Request::get('product_id');
        $cc_last4 = Request::get('cc_id');

        $product = $infusionsoft->products->find($product_id);
    }
    catch (InfusionsoftTokenExpiredException $e) 
    {
        $infusionsoft->refreshAccessToken();
        Session::put( 'token', serialize( $infusionsoft->getToken() ) );

        $product = $infusionsoft->products->find($product_id);
    }

    $contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
    $contact = $contacts[0];
    $credit_card = $infusionsoft->data->query(
                    'CreditCard',
                    10, 0,
                    ['Last4' => $cc_last4],
                    ['CardType', 'Last4', 'Status'],
                    'Last4',
                    true);

    return [$contact, $credit_card];
}]);