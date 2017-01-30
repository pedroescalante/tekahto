<?php

namespace Proc\Worker;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Log;

class InfusionRetriever 
{
	public function getInfusionsoftObject(){
		
		$infusionsoft = new Infusionsoft\Infusionsoft(array(
	        'clientId'     => $_ENV['clientId'],
	        'clientSecret' => $_ENV['clientSecret'],
	        'redirectUri'  => $_ENV['redirectUri']
    	));

    	return $infusionsoft;
	}

	public function refreshToken($infusionsoft){
		$token = Token::orderBy('created_at', 'desc')->first();

		if($token){
			$infusionsoft->setToken(unserialize($token->token));
			$infusionsoft->refreshAccessToken();
			$new_token = new Token;
			$new_token->token = serialize($infusionsoft->getToken());
			$new_token->save();
			return $infusionsoft;
		}
	}

	public function refreshTokenTwo($infusionsoft){
		$token = Token::orderBy('created_at', 'desc')->first();
		if($token){
			$infusionsoft->setToken(unserialize($token->token));
			if( $infusionsoft->getToken()->endOfLife > time() )
			{
				return $infusionsoft;
			}
			else
			{
				$infusionsoft->refreshAccessToken();
				$new_token = new Token;
				$new_token->token = serialize( $infusionsoft->getToken() );
				$new_token->save();
				return $infusionsoft();
			}
		}
	}

    	public function fire($job, $data)
	{
		Log::info(json_encode($data));

		$job->delete();
	}
}
