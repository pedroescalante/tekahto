<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

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
    // Setup a new Infusionsoft SDK object
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    // If the serialized token is already available in the session storage,
    // we tell the SDK to use that token for subsequent requests, rather
    // than try and retrieve another one.
    if (Session::has('token')) {
        $infusionsoft->setToken(unserialize(Session::get('token')));
    }

    // If we are returning from Infusionsoft we need to exchange the code
    // for an access token.
    if (Request::has('code') and !$infusionsoft->getToken()) {
        $infusionsoft->requestAccessToken(Request::get('code'));
    }

    // NOTE: there's some magic in the step above - the Infusionsoft SDK has
    // not only requested an access token, but also set the token in the current
    // Infusionsoft object, so there's no need for you to do it.

    if ($infusionsoft->getToken()) {
        // Save the serialized token to the current session for subsequent requests
        // NOTE: this can be saved in your database - make sure to serialize the
        // entire token for easy future access
        Session::put('token', serialize($infusionsoft->getToken()));

        // Now redirect the user to a page that performs some Infusionsoft actions
        return Redirect::to('/contacts');
    }

    // something didn't work, so let's go back to the beginning
    return Redirect::to('/infusionsoft');
}]);

Route::get('/contacts', [ function(){

    // Setup a new Infusionsoft SDK object
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    // Set the token if we have it in storage (in this case, a session)
    $infusionsoft->setToken(unserialize(Session::get('token')));

    try {
        // Retrieve a list of contacts by querying the data service
        $contacts = $infusionsoft->data->query(
                    'Contact',                                  //Table
                    100, 0,                                   //Limit - Paging
                    ['FirstName' => 'John'],                    //Query Data
                    ['FirstName', 'LastName', 'Email', 'ID'],   //Selected Fields
                    'FirstName',                                //Order By
                    true);                                      //Ascending

    } catch (InfusionsoftTokenExpiredException $e) {
        // Refresh our access token since we've thrown a token expired exception
        $infusionsoft->refreshAccessToken();

        // We also have to save the new token, since it's now been refreshed. 
        // We serialize the token to ensure the entire PHP object is saved 
        // and not accidentally converted to a string
        Session::put( 'token', serialize( $infusionsoft->getToken() ) );

        // Retrieve the list of contacts again now that we have a new token
        $contacts = $infusionsoft->data->query(
                    'Contact',                                  //Table
                    100, 0,                                   //Limit - Paging
                    ['FirstName' => 'John'],                    //Query Data
                    ['FirstName', 'LastName', 'Email', 'ID'],   //Selected Fields
                    'FirstName',                                //Order By
                    true);                                      //Ascending
    }

    return $contacts;
    //return Redirect::to('/contacts/byemail');

}]);

Route::get('contacts/byemail', [ function()
{
    // Setup a new Infusionsoft SDK object
    $infusionsoft = new Infusionsoft\Infusionsoft(array(
        'clientId'     => $_ENV['clientId'],
        'clientSecret' => $_ENV['clientSecret'],
        'redirectUri'  => $_ENV['redirectUri']
    ));

    // Set the token if we have it in storage (in this case, a session)
    $infusionsoft->setToken(unserialize(Session::get('token')));

    try {
        // Retrieve a list of contacts by querying the data service
        //$contact = $infusionsoft->data->query('Contact', 10, 0, ['Email' => 'johnlong@laiusa.net'], ['FirstName', 'LastName', 'Email', 'ID'], 'FirstName', true);
        $contact = $infusionsoft->data->findByEmail('johnlong@laiusa.net', ['Id', 'FirstName', 'LastName']);
    } catch (InfusionsoftTokenExpiredException $e) {
        // Refresh our access token since we've thrown a token expired exception
        $infusionsoft->refreshAccessToken();

        // We also have to save the new token, since it's now been refreshed. 
        // We serialize the token to ensure the entire PHP object is saved 
        // and not accidentally converted to a string
        Session::put( 'token', serialize( $infusionsoft->getToken() ) );

        // Retrieve the list of contacts again now that we have a new token
        //$contact = $infusionsoft->data->query('Contact', 10, 0, ['Email' => 'johnlong@laiusa.net'], ['FirstName', 'LastName', 'Email', 'ID'], 'FirstName', true);
        $contact = $infusionsoft->data->findByEmail('johnlong@laiusa.net', ['Id', 'FirstName', 'LastName']);
    }    

    return $contact;
}]);