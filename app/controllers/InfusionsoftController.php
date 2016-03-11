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

		/*$last_token = Token::orderBy('created_at', 'desc')->first();
		if (isset($last_token)) {
			$infusionsoft->setToken(unserialize($last_token->token));
	    }*/
	    if (Session::has('token')) {
			$infusionsoft->setToken(unserialize(Session::get('token')));
		}

	    try
	    {
		    if (Request::has('code') and !$infusionsoft->getToken()) {
		        $infusionsoft->requestAccessToken(Request::get('code'));
		    }

		    if ($infusionsoft->getToken()) {
				Session::put('token', serialize($infusionsoft->getToken()));
				$token = new Token;
				$token->token = serialize($infusionsoft->getToken());
				$token->save();

				return Response::json(['Session' => "Token: ".Session::get('token'), 'Token'=>$token->token]);
			}
		} 
		catch (Exception $e)
		{
			return Response::json(['error' => $e->getMessage()]);
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
		//$infusionsoft->setToken(unserialize(Session::get('token')));

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
	        $token = new Token;
	    	$token->token = serialize($infusionsoft->getToken());
	    	$token->save();
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

	public function contact()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('created_at', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));
	    
	    try 
	    {
	        $email = Request::get('email');
	        $contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
	    } 
	    catch (InfusionsoftTokenExpiredException $e) 
	    {
	        $infusionsoft->refreshAccessToken();
	        $token = new Token;
	    	$token->token = serialize($infusionsoft->getToken());
	    	$token->save();

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
	}	

	public function products()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('created_at', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));
	    
	    try 
	    {
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
	        $token = new Token;
	    	$token->token = serialize($infusionsoft->getToken());
	    	$token->save();

	        $products = $infusionsoft->data->query(
	                    'Product',
	                    10, 0,
	                    ['Status' => '1'],
	                    ['Id', 'ProductName', 'Description', 'ProductPrice', 'Status'],
	                    'ProductName',
	                    true);
	    }

	    return View::make('products', ['products'=>$products]);
	}

	public function product()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('created_at', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));

	    $id = Request::get('id');
	    
	    try 
	    {
	        $product = $infusionsoft->products->find($id);
	    } 
	    catch (InfusionsoftTokenExpiredException $e) 
	    {
	        $infusionsoft->refreshAccessToken();
	        $token = new Token;
	    	$token->token = serialize($infusionsoft->getToken());
	    	$token->save();

	        $product = $infusionsoft->products->find($id);
	    }    

	    return View::make('product', ['product'=>$product]);
	}

	public function invoice()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('created_at', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));
	    
	    try
	    {
	        $email 		= Request::get('email');
	        $product_id = Request::get('product_id');
	        $cc_last4 	= Request::get('cc_id');

	        $product 	= $infusionsoft->products->find($product_id);
	    }
	    catch (InfusionsoftTokenExpiredException $e) 
	    {
	        $infusionsoft->refreshAccessToken();
	        $token = new Token;
	    	$token->token = serialize($infusionsoft->getToken());
	    	$token->save();

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
	}
}
