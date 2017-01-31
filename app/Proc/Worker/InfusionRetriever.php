<?php

namespace Proc\Worker;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Log;
use Infusionsoft\Infusionsoft;
use Token;

class InfusionRetriever 
{
	public function getInfusionsoftObject()
	{	
		$infusionsoft = new Infusionsoft([  'clientId'     => $_ENV['clientId'],
	        								'clientSecret' => $_ENV['clientSecret'],
	        								'redirectUri'  => $_ENV['redirectUri'] ]);

    	return $infusionsoft;
	}

	public function refreshToken($infusionsoft)
	{
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

	public function refreshTokenTwo($infusionsoft)
	{
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

	public function fire($job, $package)
	{
		try 
		{
			$infusionsoft = $this->getInfusionsoftObject();
			$infusionsoft = $this->refreshTokenTwo($infusionsoft);

			$contacts = $infusionsoft->contacts->findByEmail($package['email'], ['Id', 'FirstName', 'LastName', 'Phone1']);

			if( !isset($contacts[0]) ) {
				$package['plan_count'] = 0;
				$package['plans']	   = [];
			}
			else {
				$contact = $infusionsoft->contacts->load($contacts[0]['Id'], ['Id', 'FirstName', 'LastName', 'Phone1']);
				$subs = $this->getSubscriptionsAllData($infusionsoft, $contact['Id']);
				$subs_array =[];
				foreach($subs as $sub)
				{
					/*if( isset($products[$sub['ProductId']]['ProductName']) )
						$sub['ProductName'] = $products[$sub['ProductId']]['ProductName'];
					else
						$sub['ProductName'] = "";
					
					if( isset($merchants[$sub['merchantAccountId']]) )
						$sub['Merchant'] = $merchants[ $sub['merchantAccountId'] ];
					else
						$sub['Merchant'] = "Merchant: ".$sub['merchantAccountId'];

					if( isset($billcycle[$sub['BillingCycle']]) )
						$sub['BillingCycle'] = $billcycle[ $sub['BillingCycle'] ];

					if( $sub['AutoCharge'] == 1 ) $sub['AutoCharge'] = "Yes"; else $sub['AutoCharge'] = "No";
					*/
					if( isset( $sub['StartDate']) )
						$sub['StartDate'] 	 = $sub['StartDate']->format('Y-m-d H:i:s');
					if( isset( $sub['LastBillDate']) )
						$sub['LastBillDate'] = $sub['LastBillDate']->format('Y-m-d H:i:s');
					if( isset( $sub['NextBillDate']) )
						$sub['NextBillDate'] = $sub['NextBillDate']->format('Y-m-d H:i:s');

					$subs_array[] = $sub;
				}
				$contact['subscriptions'] = $subs_array;

				$package['plan_count'] = count( $contact['subscriptions'] );
				$package['plans']      = $contact['subscriptions'];
			}

			Log::info("Email: ".$package['email']." - Plans: ".$package['plan_count'] );
		} catch (Exception $e) {
			Log::error("Error: ".$e->getMessage());
		}

		$job->delete();
	}
}
