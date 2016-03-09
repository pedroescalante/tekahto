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
		return View::make('link', ['infusionsoft' => $this->getInfusionsoftObject()]);
	}

}
