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

Route::get('/infusionsoft/callback', [ function()
{
	$infusionsoft = new Infusionsoft\Infusionsoft(array(
            'clientId'     => 'ufh3eanp6kfmudvnmsa78azy',
            'clientSecret' => 'W8QcHqAxbk',
            'redirectUri'  => 'https://www.tekahto.com/infusionsoft/callback',
        ));

        // If the serialized token is available in the session storage, we tell the SDK
        // to use that token for subsequent requests.
        if (isset($_SESSION['token'])) {
            $infusionsoft->setToken(unserialize($_SESSION['token']));
        }

        // If we are returning from Infusionsoft we need to exchange the code for an
        // access token.
        if (isset($_GET['code']) and !$infusionsoft->getToken()) {
            //$infusionsoft->requestAccessToken($_GET['code']);
	    $client = new \GuzzleHttp\Client();
	    $data = [
		'client_id' => 'ufh3eanp6kfmudvnmsa78azy',
		'client_secret' => 'W8QcHqAxbk',
		'code' => $_GET['code'],
		'grant_type' => 'authorization_code',
		'redirectUri'  => 'https://www.tekahto.com/infusionsoft/callback'

	    ];
dd($data);
	    $response = $client->post('https://api.infusionsoft.com/token', $data );
	    dd($response);
        }

        if( isset($_GET) )
            var_dump($_GET);

        if ($infusionsoft->getToken()) {
            // Save the serialized token to the current session for subsequent requests
            $_SESSION['token'] = serialize($infusionsoft->getToken());

            // MAKE INFUSIONSOFT REQUEST
        } else {
            echo '<a href="' . $infusionsoft->getAuthorizationUrl() . '">Click here to authorize</a>';
        }
}]);
