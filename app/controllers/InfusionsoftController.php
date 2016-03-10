<?php

use Token;

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

	    if (Request::has('code') and !$infusionsoft->getToken()) {
	        $infusionsoft->requestAccessToken(Request::get('code'));
	    }

	    if ($infusionsoft->getToken()) {
	    	$token = new Token;
	    	$token->fill(serialize($infusionsoft->getToken()));
	        $token->save();

	        return Response::json(['token' => $token]);
	    }

	    return Response::json(['error' => 'Token was not found']);
	}
}