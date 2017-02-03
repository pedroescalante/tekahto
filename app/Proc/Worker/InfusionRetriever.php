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

	public function getSubscriptionsAllData($infusionsoft, $contact_id){
		$subscrip = $infusionsoft->data->query(
			'RecurringOrder',
			1000, 0, 
			['ContactId' => $contact_id],
			['Id', 'ProductId', 'StartDate', 'merchantAccountId', 'Status', 'SubscriptionPlanId', 'AutoCharge', 'BillingCycle', 'LastBillDate', 'NextBillDate'],
			'Id',
			true
		);
		return $subscrip;
	}

	public function getProductsFromFile()
	{
		$data = file_get_contents( public_path()."/Products.json" );
		$array = json_decode($data, true);		
		return $array;
	}

	public function fire($job, $package)
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshTokenTwo($infusionsoft);

		Log::info(json_encode($package) );

		foreach ($package['accounts'] as $account) 
		{
			/*
			try 
			{
				$contacts = $infusionsoft->contacts->findByEmail($account->email, ['Id', 'FirstName', 'LastName', 'Phone1']);

				if( !isset($contacts[0]) ) {
					$new_package['plan_count'] = 0;
					$new_package['plans']      = [];
				}
				else 
				{
					$contact = $infusionsoft->contacts->load($contacts[0]['Id'], ['Id', 'FirstName', 'LastName', 'Phone1']);
					$subs = $this->getSubscriptionsAllData($infusionsoft, $contact['Id']);
					$subs_array =[];

					//Get Fixed Data
					$products  = $this->getProductsFromFile();
					$merchants = $this->getMerchants();
					$billcycle = $this->getBillingCycles();

					foreach($subs as $sub)
					{
						if( isset($products[$sub['ProductId']]['ProductName']) )
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

						if( isset( $sub['StartDate']) )
							$sub['StartDate'] 	 = $sub['StartDate']->format('Y-m-d H:i:s');
						if( isset( $sub['LastBillDate']) )
							$sub['LastBillDate'] = $sub['LastBillDate']->format('Y-m-d H:i:s');
						if( isset( $sub['NextBillDate']) )
							$sub['NextBillDate'] = $sub['NextBillDate']->format('Y-m-d H:i:s');

						$subs_array[] = $sub;
					}
					$contact['subscriptions'] = $subs_array;

					$new_package['plan_count'] = count( $contact['subscriptions'] );
					$new_ackage['plans']       = $contact['subscriptions'];
				}

				Log::info("AccountId: ".$account->id." - Email: ".$account->email." - Plans: ".$new_package['plan_count'] );

				try {
					$client = new Client;
					$response = $client->post( $package['server']."/twilio_reports/plans",
								[ 'form_params' => [ 'package' => $new_package ], 
								  'verify' => false ]);
					$res = json_decode($response->getBody()->getContents());
	        	} 
	        	catch (ClientException $e){
					Log::info("Error: Guzzle Error");
	        	}

			} catch (Exception $e) {
				Log::error("Error: ".$e->getMessage());
			}*/
		}
		
		$job->delete();
	}

	public function getMerchants(){
		$merchants = [	24 => "PowerPay The King Of Systems",
						25 => "Test Merchant", 
						27 => "Auth.Net - Buyers On Fire",
						28 => "Auth.net - EMS",
						30 => "Auth.net - Meritus",
						32 => "EasyPayDirect (AgentSoft - DO NOT USE)",
						34 => "EasyPayDirect (TKOS)", 
						36 => "EasyPayDirect BOF"];
		return $merchants;
	}

	public function getBillingCycles(){
		$billcycle = [  1  => "Year",
						2  => "Month",
						3  => "Week",
						6  => "Day"];
		return $billcycle;
	}

}
