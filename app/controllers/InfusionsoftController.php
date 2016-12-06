<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class InfusionsoftController extends BaseController {

	/**
		Returns the InfusionSoft Object
	**/
	public function getInfusionsoftObject(){
		
		$infusionsoft = new Infusionsoft\Infusionsoft(array(
	        'clientId'     => $_ENV['clientId'],
	        'clientSecret' => $_ENV['clientSecret'],
	        'redirectUri'  => $_ENV['redirectUri']
    	));

    	return $infusionsoft;
	}

	/**
		Set Stored token to get the Refreshed Access Token
	**/
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

	/**
		Shows a view with the InfusionSoft link to get the Authentication Token
	**/
	public function getLink()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$link = $infusionsoft->getAuthorizationUrl();
		return View::make('link', ['link' => $link]);
	}

	/**
		Receives the Token from InfusionSoft API and configures it. 
		Then it shows the Dashboard
	**/
	public function callback()
	{
		$infusionsoft = $this->getInfusionsoftObject();

	    try
	    {
		    if ( Request::has('code') and !$infusionsoft->getToken() ) {
		        $infusionsoft->requestAccessToken(Request::get('code'));
		    }

		    if ( $infusionsoft->getToken() ) {
				$token = new Token;
				$token->token = serialize($infusionsoft->getToken());
				$token->save();

				return View::make('token');
			}
		} 
		catch (Exception $e)
		{
			return Response::json(['error' => $e->getMessage()]);
		}

	    return Response::json(['error' => "Code or Access Token wasn't found"]);
	}

	/**
		Return a list of contacts
	**/
	public function contacts()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);

		$contacts = $infusionsoft->data->query(
                    'Contact',
                    10, 0,
                    ['FirstName' => 'John'],
                    ['ID', 'FirstName', 'LastName', 'Email'],
                    'ID',
                    true);
		$arr = [];
		foreach($contacts as $contact){
			$creditCards = 	$infusionsoft->data->query(
							'CreditCard', 
							1000, 0, 
							['ContactId' => $contact['ID']], 
							['Id','Last4','CardType','Status'], 
							'Id', 
							true);
			$contact['CreditCards'] = $creditCards;
			$arr[] = $contact;
		}
		    
	    return View::make('contacts', ['contacts' => $arr]);
	}

	/**
		Shows a view with the information of a specific contact
	**/
	public function contact()
	{
	    $infusionsoft = $this->getInfusionsoftObject();
	    $infusionsoft = $this->refreshToken($infusionsoft);

	    $email = Request::get('email');

	    $contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName', 'Phone1']);
	    
	    if( !isset($contacts[0]))
		return Response::json(['error' => 'Invalid Contact']);

	    $contact = $infusionsoft->contacts->load($contacts[0]['Id'], ['Id', 'FirstName', 'LastName', 'Phone1']);
		
	    $contact['CreditCards'] = $this->getCreditCards($infusionsoft, $contact['Id']);
	        
	    $jobs = $this->getJobs($infusionsoft, $contact['Id']);
            $job_array = [];
            foreach ($jobs as $job) {
        	$job['invoices'] 	= $this->getInvoicesByJob($infusionsoft, $job['Id']);
        	$job_array [] 		= $job;
            }
            $contact['Jobs'] = $job_array;

	    $subs 	  = $this->getSubscriptions($infusionsoft, $contact['Id']);
	    $products = $this->getProducts($infusionsoft);
	    $subs_array =[];
	    foreach($subs as $sub){
			if( isset($products[$sub['ProductId']]['ProductName']) )
		    	$sub['ProductName'] = $products[$sub['ProductId']]['ProductName'];
			else 
		    	$sub['ProductName'] = "";
			$sub['invoices'] 	= $this->getInvoicesBySubscription($infusionsoft, $sub['Id']);
			$subs_array[] 		= $sub;
	    }
	    $contact['subscriptions'] = $subs_array;

		//Send Contact Data to BOF
		Account::where('email', $email)->delete();
		$subs = $this->getSubscriptionsAllData($infusionsoft, $contact['Id']);
                $subs_array =[];
                foreach($subs as $sub)
                {
                        if( isset($products[$sub['ProductId']]['ProductName']) )
                                $sub['ProductName'] = $products[$sub['ProductId']]['ProductName'];
                        else
                                $sub['ProductName'] = "";
                        $subs_array[] = $sub;

                        $account = new Account();
                        $account->email = $email;

                        //ProductName
                        if( isset($sub['ProductName']) )
                                $account->pricing_plan    = $sub['ProductName'];
                        else
                                $account->pricing_plan    = "Product ".$sub['ProductId']." - No Product Name from IS";
                        //Start Date
                        if( isset($sub['StartDate']) )
                                $account->start_date      = $sub['StartDate'];
                        else
                                $account->start_date = null;
                       //Merchant ID
                        if( isset($merchants[$sub['merchantAccountId']]) )
                                $account->merchant_id = $merchants[ $sub['merchantAccountId'] ];
                        else
                                $account->merchant_id = "Merchant: ".$sub['merchantAccountId'];
                        //Subscription ID
                        $account->subscription_id = $sub['Id'];
                        //Status
                        $account->status          = $sub['Status'];
                        //Last Bill Date
                        if( isset($sub['LastBillDate']) )
                                $account->last_bill_date  = $sub['LastBillDate'];
                        //Next Bill Date
                        if( isset($sub['NextBillDate']) )
                                $account->next_bill_date  = $sub['NextBillDate'];

                        $account->save();
                }
		$plans = Account::where('email', $email)->get();

                $client = new Client;
                try {
                        $response = $client->post( 'http://api.buyersonfire.net/infusion/plans',
                                    [ 'form_params' => [
                                        'plans' => json_encode($plans)]
                                      , 'verify' => false ]);
                        $res = json_decode($response->getBody()->getContents());
                }
                catch (ClientException $e){
                        return json_decode($e->getMessage());
                }



	    return View::make('contact', ['contact' => $contact]);
	}	

	/**
		Show a list of contacts from a file
	**/
	public function contactList($filename)
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);

		$response = array();
		$products = $this->getProducts($infusionsoft);

	        $myfile = fopen($filename, "r") or die("Unable to open file!");
		$stage = "http://api.buyersonfire.net";

	        while(!feof($myfile)) {
			$email = fgets($myfile);
			$email = preg_replace('/\s+/', '', $email);
			if( strlen($email) > 0 ){

		//$email = Input::get('email');
		$merchants = [34 => "EasyPayDirect", 25=> "Test Merchant", 36 => "EasyPayDirect BOF"];
		Log::info($email);
		Account::where('email', $email)->delete();
		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshTokenTwo($infusionsoft);
		try {
                $contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName', 'Phone1']);
		//}catch( Exception $e ){
		//}
               if( !isset($contacts[0]))
                  return Response::json(['error' => 'Invalid Contact']);
               $contact = $infusionsoft->contacts->load($contacts[0]['Id'], ['Id', 'FirstName', 'LastName', 'Phone1']);
		
                $products = $this->getProducts($infusionsoft);
                $subs = $this->getSubscriptionsAllData($infusionsoft, $contact['Id']);
                $subs_array =[];
                foreach($subs as $sub)
		{
                        if( isset($products[$sub['ProductId']]['ProductName']) )
                                $sub['ProductName'] = $products[$sub['ProductId']]['ProductName'];
                        else
                                $sub['ProductName'] = "";
                        $subs_array[] = $sub;
			
			$account = new Account();
			$account->email = $email;
			//ProductName
			if( isset($sub['ProductName']) )
	                        $account->pricing_plan    = $sub['ProductName'];
			else
				$account->pricing_plan    = "Product ".$sub['ProductId']." - No Product Name from IS";
			//Start Date
			if( isset($sub['StartDate']) )
	                        $account->start_date      = $sub['StartDate'];
			else
				$account->start_date = null;
			//Merchant ID
			if( isset($merchants[$sub['merchantAccountId']]) )
                        	$account->merchant_id = $merchants[ $sub['merchantAccountId'] ];
			else
				$account->merchant_id = "Merchant: ".$sub['merchantAccountId'];
			//Subscription ID
                        $account->subscription_id = $sub['Id'];
			//Status
                        $account->status          = $sub['Status'];
			//Last Bill Date
			if( isset($sub['LastBillDate']) )
				$account->last_bill_date  = $sub['LastBillDate'];
			//Next Bill Date
			if( isset($sub['NextBillDate']) )
				$account->next_bill_date  = $sub['NextBillDate'];
			$account->save();
                }
                $contact['subscriptions'] = $subs_array;
		}
		catch( Exception $e){
			Log::error("Error: ". $email);
                	return Response::json(['error' => 'Invalid Contact']);
		}
		$plans = Account::where('email', $email)->get();
		
		$client = new Client;
        	try {
            		$response = $client->post( $stage.'/infusion/plans',
		            	    [ 'form_params' => [ 
					'plans' => json_encode($plans)]  
				      , 'verify' => false ]);
            		$res = json_decode($response->getBody()->getContents());
			//return Response::json(['plans'=>$res]);
        	} 
        	catch (ClientException $e){
            		return json_decode($e->getMessage());
        	}
	}
	    }
	    fclose($myfile);
	}

	/**
		Returns a view with data from a specific Tag
	**/
	public function tags($tag_id)
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);

		$tags = $this->getTag($infusionsoft, $tag_id);
		return View::make('tag', ['tags' => $tags]);
	}

	/**
		Returns a view with all the Subscription Plans stored on Infusionsoft
	**/
	public function subscr(){

		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);
		$sus = $this->getSubscriptionPlans($infusionsoft);
		$products = $this->getProducts($infusionsoft);
		
		$subscriptions = [];
		foreach($sus as $s){
			if( isset($products[$s['ProductID']]))
			$s['ProductName']= $products[$s['ProductID']]['ProductName'];
			$subscriptions[] = $s;
		}
		return Response::json($subscriptions);
	}

	/**
		Returns a view with all the products stored on InfusionSoft
	**/
	public function products()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);

	    
	    $products = $this->getProducts($infusionsoft);

	    return View::make('products', ['products'=>$products]);
	}

	/**
		Returns a view with a specific Product information
	**/
	public function product()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);


	    $id = Request::get('id');
	    $product = $infusionsoft->products->find($id);
	    
	    return View::make('product', ['product'=>$product]);
	}

	/**
		Returns the Payment Information to be shown on BOF Plan Upgrade
	**/
	public function paymentInfo()
	{
		$plan_id = Input::get('plan_id');
		$email 	 = Input::get('email');

		$subscriptionPlans = [216 => 88,    220 => 94,   218 => 100];
		
		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);

	    	$products = $this->getProducts($infusionsoft);
	    	if( !isset($products[$plan_id]) )
	    		return Response::json(['success'=>'false', 'errors' => ['Invalid Plan']]);
		$product = $products[$plan_id];
		$subscriptionPlan = $this->getSubscriptionInfo($infusionsoft, $subscriptionPlans[$product['Id']]);
		$product['ProductPrice']=$subscriptionPlan['PlanPrice'];

	    	$contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
		if( !isset($contacts[0]) )
			return Response::json(['success'=>'false', 'errors' => ['Invalid Contact']]);
	    
		$contact = $infusionsoft->contacts->load($contacts[0]['Id'], ['Id', 'FirstName', 'LastName']);
		$credit_cards = $this->getValidCreditCards($infusionsoft, $contact['Id']);
		$cc = [];
		if($credit_cards != NULL){
		    foreach ($credit_cards as $card)
			$cc[]=$card;
		}

		return Response::json(['product'=>$product, 'contact'=>$contact, 'credit_card'=>$cc]);
	}

	/**
		Creates a new Subscription for the contact. 
		We should manage the plan (Product), the contact (Customer) and the Credit Card.
		Then we create the subscription using the SubscriptionPlanID
	**/
	public function makeSubscription()
	{
		$actual_plan_id = Input::get('actual_plan_id');
		$plan_id 	= Input::get('plan_id');
		$card_id 	= Input::get('card_id');
		$email 	 	= Input::get('email');
		
		//Product IDs
		// 216: BOF Starter
		// 220: BOF Professional
		// 218: BOF Premium
		$planOrdering		= [216 => 1,     220 => 2,    218 => 3];
		
		//Subscription Plans
		//  88:  Starter Plan 29$
		//  94:  Professional Plan 97$
		//  100: Premium 149$
		$subscriptionPlans	= [216 => 88,    220 => 94,   218 => 100];
		
		//Upgrade Tags
		// 2494: Upgrade: BOF3 Starter
		// 2496: Upgrade: BOF3 Professional
		// 2498: Upgrade: BOF3 Premium
		$upgradeTags 		= [216 => 2494 , 220 => 2496, 218 => 2498];

		//Downgrade Tags
		// 2570: Downgrade: BOF3 Starter
		// 2570: Downgrade: BOF3 Professional
		$downgradeTags		= [216 => 2570,  220 => 2568];
		
		//Failed Tags
		// 2074: CC Failed Subscription
		$failedTags  		= [216 => 2074];

		//Merchant Account ID
		// 25: Test
		// 34: EasyPayDirect
		$merchantAccountID 	 = 34;

		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);

		//Product (Pricing Plan)
		$products = $this->getProducts($infusionsoft);
		if( !isset($products[$plan_id]) )
			return Response::json(['success'=>'false', 'errors' => ['Invalid Product']]);
		$product = $products[$plan_id];

		//Contact
		$query = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
		if( !isset($query[0]['Id']) )
			return Response::json(['success'=>'false', 'errors' => ['Invalid Contact']]);
		$contact_id = $query[0]['Id'];
		$contact = $infusionsoft->contacts->load($contact_id, ['Id', 'FirstName', 'LastName']);

		//Credit Card
		$credit_card = $this->getSpecificCreditCard($infusionsoft, $contact['Id'], $card_id);
		if( !isset($credit_card) )
			return Response::json(['success'=>'false', 'errors' => ['Invalid Credit Card']]);
		
		//Actual Plan
		//$subscriptions = $this->getActiveSubscriptions($infusionsoft, $contact['Id']);
		//return Response::json($subscriptions);
		//if( !$subscriptions )
		//	return Response::json(['success'=>'false', 'errors' => ['Invalid actual plan']]);
		//$actual_plan_id = $subscriptions['ProductId'];

		//Subscription
		$subscriptionPlanId  = $subscriptionPlans[ $product['Id'] ];
		$subscriptionPlan = $this->getSubscriptionInfo($infusionsoft, $subscriptionPlanId);

		//$actualSubscriptions = $this->getSubscriptionsByProduct($infusionsoft, $contact['Id'], $product['Id'], 'Active');
		//if(count($actualSubscriptions))
		if( $plan_id == $actual_plan_id )
			return Response::json(['success'=>'false', 'errors' => ['The product is already active. Operation Failed']]);
		
		$subscriptionID = $infusionsoft->invoices()->addRecurringOrder(
				  $contact['Id'], false, $subscriptionPlanId, 1, 
				  $subscriptionPlan['PlanPrice'], 
				  false, 
				  $merchantAccountID, 
				  $credit_card['Id'], 
				  0, 0);	

		//Invoice For Recurring
		$invoiceID = $infusionsoft->invoices()->createInvoiceForRecurring($subscriptionID);

		//Charge Invoice
		$notes = "BOF3 API Plan Change - ".$product['ProductName'];
		$payment = $infusionsoft->invoices()->chargeInvoice(
							$invoiceID, $notes, 
							$credit_card['Id'], $merchantAccountID, false);

		//Log Info
                $view_log = new Logger('View Logs');
                $view_log->pushHandler(new StreamHandler(storage_path('logs/view.log'), Logger::INFO));
		
		//Invoice Charged Successfully
		if( $payment['Successful'] ){	
			if( $planOrdering[$plan_id] < $planOrdering[$actual_plan_id])
			    $infusionsoft->contacts()->addToGroup($contact['Id'], $downgradeTags[$product['Id']]);
			else
			    $infusionsoft->contacts()->addToGroup($contact['Id'], $upgradeTags[$product['Id']]);

			//Log Info
                	$view_log->addInfo("____________________________________");
			$view_log->addInfo("Email: ".$email);
			$view_log->addInfo("New Plan ID: ".$plan_id);
			$view_log->addInfo("Previous Plan ID: ".$actual_plan_id);
			$view_log->addInfo($payment);
			

			return Response::json(['success'=>'true', "info" => $payment, "product_id" => $product['Id']] );
		}
		else
		{
			//Log Info
			$view_log->addInfo("____________________________________");
                        $view_log->addInfo("Email: ".$email);
                        $view_log->addInfo("New Plan ID: ".$plan_id);
                        $view_log->addInfo("Previous Plan ID: ".$actual_plan_id);
                        $view_log->addInfo($payment);

			return Response::json(['success'=>'false', 'info'=>'Payment Failed', 'payment'=>$payment]);
		}

	}

	/**
		Get all the Products on InfusionSoft
		$infusionsoft 	= InfusionSoft object
	**/
	public function getProducts($infusionsoft){
		
		$products = Product::get();
		if( !count($products) )
		{
		    $products = $infusionsoft->data->query(
					'Product',
					1000, 0,
					['Status' => '1'],
					['Id', 'ProductName', 'Description', 'ProductPrice', 'Status'],
					'ProductName',
					true);

		    $array = [];
		    foreach($products as $product){
			$array[$product['Id']] = [
					'Id'		=> $product['Id'],
					'ProductName'	=> $product['ProductName'], 
					'ProductPrice'	=> $product['ProductPrice'], 
					'Status'	=> $product['Status'],
					'Description'	=> (isset($product['Description'])) ? $product['Description'] : "-" ];
			$p = new Product;
			$p->id = $product['Id'];
			$p->product_name = $product['ProductName'];
			$p->product_price = $product['ProductPrice'];
			$p->status = $product['Status'];
			$p->description = isset($product['Description']) ? $product['Description'] : "-";
			$p->save();
		    }

		    $products = $infusionsoft->data->query(
                                        'Product',
                                        1000, 0,
                                        ['Status' => '0'],
                                        ['Id', 'ProductName', 'Description', 'ProductPrice', 'Status'],
                                        'ProductName',
                                        true);

                    foreach($products as $product){
                        $array[$product['Id']] = [
                                        'Id'            => $product['Id'],
                                        'ProductName'   => $product['ProductName'],
                                        'ProductPrice'  => $product['ProductPrice'],
                                        'Status'        => $product['Status'],
                                        'Description'   => (isset($product['Description'])) ? $product['Description'] : "-" ];
                        $p = new Product;
                        $p->id = $product['Id'];
                        $p->product_name = $product['ProductName'];
                        $p->product_price = $product['ProductPrice'];
                        $p->status = $product['Status'];
                        $p->description = isset($product['Description']) ? $product['Description'] : "-";
                        $p->save();
                    }
			
                }
		else {
		    $array = [];
		    foreach( $products as $product){
			$array[$product->id] = [
				'Id' => $product->id,
				'ProductName'	=> $product->product_name,
				'ProductPrice'	=> $product->product_price,
				'Status' 	=> $product->status,
				'Description' 	=> ( isset($product->description)? $product->description: "-")];
		    }
		}
		return $array;
	}

	/**
		Get all the Tags from InfusionSoft
		$infusionsoft 	= InfusionSoft object
	**/
	public function getTag($infusionsoft, $tag_id){
		
		$tags = $infusionsoft->data->query(
	            'ContactGroup',
	            1000, 0,
	            ['Id' => $tag_id],
	            ['GroupCategoryId', 'GroupDescription', 'GroupName', 'Id'],
	            'Id',
	            true);

		$array = [];
		foreach($tags as $tag){
			$array[$tag['Id']] = [
					'Id'			=> $tag['Id'],
					'GroupCategoryId' 	=> $tag['GroupCategoryId'], 
					'GroupDescription' 	=> (isset($tag['GroupDescription'])) ? $tag['GroupDescription'] : "-", 
					'GroupName'		=> $tag['GroupName'],
					'Description' 		=> (isset($tag['Description'])) ? $tag['Description'] : "-"
					 ];
		}
		
	    return $array;
	}

	/**
		Get all the Credit Cards from a specific contact
		$infusionsoft 	= InfusionSoft object
		$contact_id 	= Contact ID from previous queries
	**/
	public function getCreditCards($infusionsoft, $contact_id){

		$credit_c = $infusionsoft->data->query(
					'CreditCard', 
					1000, 0, 
					['ContactId' => $contact_id], 
					['Id','Last4','CardType','Status'], 
					'Id', 
					true);
		return $credit_c;
	}

	public function getValidCreditCards($infusionsoft, $contact_id){
	
		$credit_c = $infusionsoft->data->query(
					'CreditCard',
					1000,0,
					['ContactId'=>$contact_id, "Status"=>3],
					['Id','Last4','CardType','Status'],
					'Id',
					true);
		return $credit_c;
	}

	/**
		Get a specific Credit Card from a specific contact
		$infusionsoft 	= InfusionSoft object
		$contact_id 	= Contact ID from previous queries
		$card_id 		= Card ID from previous queries
	**/
	public function getSpecificCreditCard($infusionsoft, $contact_id, $card_id){

		$credit_c = $infusionsoft->data->query(
					'CreditCard', 
					1000, 0, 
					['ContactId' => $contact_id, 'Id' => $card_id], 
					['Id','Last4','CardType','Status'], 
					'Id', 
					true);
		if( !isset($credit_c[0]))
			return null;
		return $credit_c[0];
	}

	/**
		Get all the Subscription Plans
		$infusionsoft 	= Infusionsoft object
	**/
	public function getSubscriptionPlans($infusionsoft){
		$sus = $infusionsoft->data->query(
			'SubscriptionPlan',
			1000, 0,
			['Active' => 1],
			['Id', 'ProductID', 'PlanPrice'],
			'Id',
			true);
		return $sus;
	}

	/**
		Get all the Subscriptions of a contact
		$infusionsoft 	= InfusionSoft object
		$contact_id 	= Contact ID from previous queries
	**/
	public function getSubscriptions($infusionsoft, $contact_id){

		$subscrip = $infusionsoft->data->query(
					'RecurringOrder', 
					1000, 0, 
					['ContactId' => $contact_id], 
					['Id','ProductId','StartDate','merchantAccountId','Status','SubscriptionPlanId','AutoCharge'], 
					'Id', 
					true);
		return $subscrip;
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

	public function getActiveSubscriptions($infusionsoft, $contact_id){
		$subscrip = $infusionsoft->data->query(
                                        'RecurringOrder',
                                        1000, 0,
                                        ['ContactId' => $contact_id, 'Status'=>'Active'],
                                        ['Id','ProductId','StartDate','merchantAccountId','Status','SubscriptionPlanId'],
                                        'Id',
                                        true);			
		return $subscrip;
		if( isset($subscrip[0]) )
	                return $subscrip[0];
		else
			return null;
	}

	public function getSubscriptionInfo($infusionsoft, $id){
		$subs = $infusionsoft->data->query(
			'SubscriptionPlan',
			1000, 0,
			['Id'=>$id],
			['Id', 'ProductId', 'PlanPrice'],
			'Id',
			true);
		return $subs[0];
	}

	/**
		Get the User Subscriptions, by Subscription Plan and Status
		$infusionsoft 		= Infusionsoft object
		$subscriptionPlanId = Subscription Plan
		$status 			= Subscription Status
	**/
	public function getSubscriptionsByProduct($infusionsoft, $contactId, $productId, $status){
		$sub = $infusionsoft->data->query(
			'RecurringOrder',
			1000, 0,
			['ContactID'=>$contactId, 'ProductId' => $productId, 'Status' => $status],
			['Id', 'SubscriptionPlanId', 'Status', 'ProductId'],
			'Id',
			true);
		return $sub;
	}

	/**
		Get all Invoices from a Job
		$infusionsoft 	= InfusionSoft object
		$job_id			= Job ID to retrieve data
	**/
	public function getInvoicesByJob($infusionsoft, $job_id){

		$invoices = $infusionsoft->data->query(
					'Invoice', 
					1000, 0, 
					['JobId' => $job_id], 
					['Id','Description','JobId','ContactId','PayStatus','TotalDue','TotalPaid'], 
					'Id', 
					true);
		return $invoices;
	}

	/**
		Get info from an Invoice
		$infusionsoft 	= InfusionSoft object
		$invoice_id		= Invoice ID to retrieve data
	**/
	public function getInvoice($infusionsoft, $invoice_id){

		$invoices = $infusionsoft->data->query(
					'Invoice', 
					1000, 0, 
					['Id' => $invoice_id], 
					['Id','Description','JobId','ContactId','PayStatus','TotalDue','TotalPaid'], 
					'Id', 
					true);
		if( isset($invoices[0]) )
			return $invoices[0];
		return null;
	}

	/**
		Get all the Jobs from a contact
		$infusionsoft 	= InfusionSoft object
		$contact_id 	= Contact ID from previous queries
	**/
	public function getJobs($infusionsoft, $contact_id){

		$jobs = $infusionsoft->data->query(
                'Job',
                10, 0,
                ['ContactID' => $contact_id],
                ['Id', 'JobTitle', 'ProductId', 'DateCreated'],
                'Id',
                true);
		return $jobs;
	}

	public function getContactTags($infusionsoft, $contact_id){
		$tags = $infusionsoft->data->query(
	                'ContactGroupAssign',
        	        1000, 0,
                	['ContactId' => $contact_id],
	                ['Id', 'ContactGroup', 'GroupId', 'DateCreated'],
        	        'Id',
                	true);
                return $tags;
	}

	/**
		Get all the invoices linked to a Subscription
		$infusionsoft 		= InfusionSoft object
		$subscription_id 	= Susbcription ID from previous queries
	**/
	public function getInvoicesBySubscription($infusionsoft, $subscription_id){

		$jobs = $infusionsoft->data->query(
                'Job',
                10, 0,
                ['JobRecurringId' => $subscription_id],
                ['Id', 'JobTitle', 'ProductId', 'DateCreated'],
                'Id',
                true);
		if(!isset($jobs[0]))
			return null;

		$invoic = $infusionsoft->data->query(
                'Invoice',
                10, 0,
                ['JobId' => $jobs[0]['Id']],
                ['Id','Description','JobId','ContactId','PayStatus','TotalDue','TotalPaid'], 
                'Id',
                true);

		return $invoic[0];
	}

	public function bof3account()
	{
		$email = Input::get('email');
		$stage = Input::get('stage');
		$merchants = [34 => "EasyPayDirect", 25=> "Test Merchant", 36 => "EasyPayDirect BOF"];

		Log::info($email);

		Account::where('email', $email)->delete();

		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshTokenTwo($infusionsoft);

		try {

               $contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName', 'Phone1']);


               if( !isset($contacts[0])){
                  $client = new Client;
		        	try {
		            		$response = $client->post( $stage.'/infusion/no_plans',
				            	    [ 'form_params' => [ 
									'email' => $email]  
						      , 'verify' => false ]);
		            		$res = json_decode($response->getBody()->getContents());
					return Response::json(['email'=>$email]);
		        	} 
		        	catch (ClientException $e){
		            		return json_decode($e->getMessage());
		        	}
               }

                $contact = $infusionsoft->contacts->load($contacts[0]['Id'], ['Id', 'FirstName', 'LastName', 'Phone1']);
		
                $products = $this->getProducts($infusionsoft);

                $subs = $this->getSubscriptionsAllData($infusionsoft, $contact['Id']);
                $subs_array =[];

                foreach($subs as $sub)
		{
                        if( isset($products[$sub['ProductId']]['ProductName']) )
                                $sub['ProductName'] = $products[$sub['ProductId']]['ProductName'];
                        else
                                $sub['ProductName'] = "";
                        $subs_array[] = $sub;
			
			$account = new Account();
			$account->email = $email;

			//ProductName
			if( isset($sub['ProductName']) )
	                        $account->pricing_plan    = $sub['ProductName'];
			else
				$account->pricing_plan    = "Product ".$sub['ProductId']." - No Product Name from IS";
			//Start Date
			if( isset($sub['StartDate']) )
	                        $account->start_date      = $sub['StartDate'];
			else
				$account->start_date = null;
			//Merchant ID
			if( isset($merchants[$sub['merchantAccountId']]) )
                        	$account->merchant_id = $merchants[ $sub['merchantAccountId'] ];
			else
				$account->merchant_id = "Merchant: ".$sub['merchantAccountId'];
			//Subscription ID
                        $account->subscription_id = $sub['Id'];
			//Status
                        $account->status          = $sub['Status'];
			//Last Bill Date
			if( isset($sub['LastBillDate']) )
				$account->last_bill_date  = $sub['LastBillDate'];
			//Next Bill Date
			if( isset($sub['NextBillDate']) )
				$account->next_bill_date  = $sub['NextBillDate'];

			$account->save();
                }
                $contact['subscriptions'] = $subs_array;

		}
		catch( Exception $e){
			Log::error("Error: ". $email);
            return Response::json(['error' => 'Invalid Contact']);
		}

		$plans = Account::where('email', $email)->get();
		
		$client = new Client;
        	try {
            		$response = $client->post( $stage.'/infusion/plans',
		            	    [ 'form_params' => [ 
					'plans' => json_encode($plans)]  
				      , 'verify' => false ]);
            		$res = json_decode($response->getBody()->getContents());
			return Response::json(['plans'=>$res]);
        	} 
        	catch (ClientException $e){
            		return json_decode($e->getMessage());
        	}
	}

	public function getAccounts()
	{
		$email = Input::get('email');
		$merchants = [34 => "EasyPayDirect", 25=> "Test Merchant"];
		if($email){

		    Account::where('email', $email)->delete();
		    $exists = Account::where('email', $email)->count();

		    if( !$exists ) {

		    	$account = new Account;
			$account->email = $email;	    	 

			$infusionsoft = $this->getInfusionsoftObject();
			$infusionsoft = $this->refreshToken($infusionsoft);

			$contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName', 'Phone1']);
			$products = $this->getProducts($infusionsoft);

			if( !isset($contacts[0]))
    			    return Response::json(['error' => 'Invalid Contact']);

			$contact = $infusionsoft->contacts->load($contacts[0]['Id'], ['Id', 'FirstName', 'LastName', 'Phone1']);
			$subs = $this->getSubscriptions($infusionsoft, $contact['Id']);
    
    			$subs_array =[];
    			foreach($subs as $sub){
    			    if( isset($products[$sub['ProductId']]['ProductName']) )
			        $sub['ProductName'] = $products[$sub['ProductId']]['ProductName'];
        		    else
		        	$sub['ProductName'] = "";
        		    $subs_array[] = $sub;
			    if( $sub['Status'] == "Active" ){
				$account->pricing_plan = $sub['ProductName'];
				$account->start_date = $sub['StartDate'];
			    if( isset($merchants[ $sub['merchantAccountId'] ]))
				$account->merchant_id = $merchants[ $sub['merchantAccountId'] ];
			    else
				$account->merchant_id = "Merchant ".$sub['merchantAccountId'];
				$account->subscription_id =$sub['Id'];
				$account->status = $sub['Status'];	
			    }
    			}
    			$contact['subscriptions'] = $subs_array;

			$account->save();
		    }

		    $plans = Account::where('email', $email)->get();
		    return Response::json($plans);
		}
	}

	public function logger()
	{
		$view_log = new Logger('View Logs');
		$view_log->pushHandler(new StreamHandler(storage_path('logs/view.log'), Logger::INFO));

		$view_log->addInfo("User clicked");
	}

	public function sendDataBof(){
		
	}

}
