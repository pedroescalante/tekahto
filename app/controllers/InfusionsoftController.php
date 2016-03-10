<?php

class InfusionsoftController extends BaseController {

	public function getInfusionsoftObject(){
		
		$infusionsoft = new Infusionsoft\Infusionsoft(array(
	        'clientId'     => $_ENV['clientId'],
	        'clientSecret' => $_ENV['clientSecret'],
	        'redirectUri'  => $_ENV['redirectUri']
    	));
	}

	public function getLink()
	{
		return View::make('link', ['infusionsoft' => $this->getInfusionsoftObject()]);
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
