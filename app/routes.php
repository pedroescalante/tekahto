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

    $data = array();
    foreach ($contacts as $c) 
    {
        $credit_cards = $infusionsoft->data->query(
                    'CreditCard',
                    10, 0,
                    ['ContactID' => $c['ID']],
                    ['CardType', 'Last4'],
                    'Last4',
                    true);
        $n = count($credit_cards);
        $c['CreditCards'] = $n;
        $data[] = $c;
    }

    return View::make('contacts', ['contacts'=>$data]);

}]);

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

    foreach ($contacts as $contact) 
    {
        $c = $infusionsoft->contacts->load($contact['Id'], ['Id', 'FirstName', 'LastName']);

        echo "<table>";
        echo "<tr> <td> First Name: <b> ".$c['FirstName']."</b> </td></tr>";
        echo "<tr> <td> Last Name: <b> ".$c['LastName']."</b> </td></tr>";
        echo "</table>";

        $credit_cards = $infusionsoft->data->query(
                    'CreditCard',               //Table
                    10, 0,                      //Limit - Paging
                    ['ContactID' => $c['Id']],  //Query Data
                    ['CardType', 'Last4', 'Status'],      //Selected Fields
                    'Last4',                    //Order By
                    true);                      //Ascending

        echo "<table>";
        foreach ($credit_cards as $card) 
        {
            echo "<tr> <td> ".$card['CardType']."</td> <td> ".$card['Last4']." </td> <td> ".$card['Status']." </td> </tr>";
        }
        echo "</table>";
    }
    
}]);