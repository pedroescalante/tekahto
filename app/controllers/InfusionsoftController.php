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
	}

}
