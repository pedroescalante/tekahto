<?php

class InfusionsoftController extends BaseController {

	public function getInfusionsoftObject(){
		
		$infusionsoft = new Infusionsoft\Infusionsoft(array(
	        'clientId'     => $_ENV['clientId'],
	        'clientSecret' => $_ENV['clientSecret'],
	        'redirectUri'  => $_ENV['redirectUri']
    	));
    	
    	return $infusionsoft;
	}

	public function getLink()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$link = $infusionsoft->getAuthorizationUrl();
		return View::make('link', ['link' => $link]);
	}

	public function callback()
	{
		$infusionsoft = $this->getInfusionsoftObject();

		$last_token = Token::orderBy('created_at', 'desc')->first();

		if (isset($last_token)) {
	        $infusionsoft->setToken(unserialize($last_token->token));
	    }

	    try
	    {
		    if (Request::has('code') and !$infusionsoft->getToken()) {
		        $infusionsoft->requestAccessToken(Request::get('code'));
		    }
		} 
		catch (Exception $e)
		{
			return Response::json(['error' => $e->getMessage()]);
		}

	    if ($infusionsoft->getToken()) {
	    	$token = new Token;
	    	$token->token = serialize($infusionsoft->getToken());
	    	$token->save();
	    	return Response::json(['success' => "Access Token saved"]);
	    }

	    return Response::json(['error' => "Code or Access Token wasn't found"]);
	}

	public function sendToken()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('created_at', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));
		$infusionsoft->refreshAccessToken();

		return Response::json(['infusionsoft'=>$infusionsoft]);
	}

	public function contacts()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('created_at', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));

	    try 
	    {
	        $contacts = $infusionsoft->data->query(
	                    'Contact',                                  //Table
	                    10, 0,                                      //Limit - Paging
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
	                    10, 0,                                     //Limit - Paging
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
	}
}