<?php

Route::get('/', function()
{
	return View::make('hello');
});

Route::get('/infusionsoft', [ function()
{
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to connect to Infusionsoft';

}]);

Route::get('/infusionsoft/callback', [ 'https', function()
{
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    if (Session::has('token')) {
        $infusionsoft->setToken(unserialize(Session::get('token')));
    }

    if (Request::has('code') and !$infusionsoft->getToken()) {
        $infusionsoft->requestAccessToken(Request::get('code'));
    }

    if ($infusionsoft->getToken()) {
        Session::put('token', serialize($infusionsoft->getToken()));

        return Redirect::to('/contacts');
    }

    return Redirect::to('/infusionsoft');

}]);

Route::get('/contacts', [ function()
{
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    $infusionsoft->setToken(unserialize(Session::get('token')));

    try 
    {
        
        $contacts = $infusionsoft->data->query(
                    'Contact',                                  //Table
                    100, 0,                                     //Limit - Paging
                    ['FirstName' => 'John'],                    //Query Data
                    ['FirstName', 'LastName', 'Email', 'ID'],   //Selected Fields
                    'FirstName',                                //Order By
                    true);                                      //Ascending

    } 
    catch (InfusionsoftTokenExpiredException $e) 
    {
        $infusionsoft->refreshAccessToken();

        Session::put( 'token', serialize( $infusionsoft->getToken() ) );

        $contacts = $infusionsoft->data->query(
                    'Contact',                                  //Table
                    100, 0,                                     //Limit - Paging
                    ['FirstName' => 'John'],                    //Query Data
                    ['FirstName', 'LastName', 'Email', 'ID'],   //Selected Fields
                    'FirstName',                                //Order By
                    true);                                      //Ascending
    }

    return $contacts;

}]);

Route::get('contacts/byemail', [ function()
{
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    $infusionsoft->setToken(unserialize(Session::get('token')));

    try 
    {
        $contacts = $infusionsoft->contacts->findByEmail('johnlong@laiusa.net', ['Id', 'FirstName', 'LastName']);
    } 
    catch (InfusionsoftTokenExpiredException $e) 
    {
        $infusionsoft->refreshAccessToken();
        Session::put( 'token', serialize( $infusionsoft->getToken() ) );

        $contacts = $infusionsoft->contacts->findByEmail('johnlong@laiusa.net', ['Id', 'FirstName', 'LastName']);
    }    

    foreach ($contacts as $contact) 
    {
        $c = $infusionsoft->contacts->load($contact['Id'], ['Id', 'FirstName', 'LastName']);
    }
    
}]);