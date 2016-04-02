<?php

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
		$last_token = Token::orderBy('id', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));

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
		$last_token = Token::orderBy('id', 'desc')->first();
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

	    if( !isset($contacts[0]))
	    	return Response::json(['error' => 'Invalid Contact']);

	    $contact = $infusionsoft->contacts->load($contacts[0]['Id'], ['Id', 'FirstName', 'LastName']);
		
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
			$sub['ProductName'] = $products[$sub['ProductId']]['ProductName'];
			$sub['invoices'] 	= $this->getInvoicesBySubscription($infusionsoft, $sub['Id']);
			$subs_array[] 		= $sub;
		}
		$contact['subscriptions'] = $subs_array;
		
	    return View::make('contact', ['contact' => $contact]);
	}	

	public function products()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('id', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));
	    
	    $products = $this->getProducts($infusionsoft);

	    return View::make('products', ['products'=>$products]);
	}

	public function product()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('id', 'desc')->first();
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
		$last_token = Token::orderBy('id', 'desc')->first();
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

	public function payment()
	{
		$plan_id = Input::get('plan_id');
		$email 	 = Input::get('email');

		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('id', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));
	    
	    try 
	    {
	        $products = $infusionsoft->data->query(
	                    'Product',
	                    10, 0,
	                    ['ID' => $plan_id],
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
	                    ['ID' => $plan_id],
	                    ['Id', 'ProductName', 'Description', 'ProductPrice', 'Status'],
	                    'ProductName',
	                    true);
	    }

	    $contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
	    
	    $cc = [];
	    foreach ($contacts as $contact) 
	    {
	        $c = $infusionsoft->contacts->load($contact['Id'], ['Id', 'FirstName', 'LastName']);

	        $credit_cards = $infusionsoft->data->query(
	                    'CreditCard',
	                    10, 0,
	                    ['ContactID' => $c['Id'], 'Status' => 3],
	                    ['Id', 'CardType', 'Last4', 'Status'],
	                    'Last4',
	                    true);

	       	foreach ($credit_cards as $card)
	       		$cc[]=$card;
	    }

	    return Response::json(['product'=>$products[0], 'contact'=>$contacts[0], 'credit_card'=>$cc]);
	}

	public function paymentInfo()
	{
		$plan_id = Input::get('plan_id');
		$email 	 = Input::get('email');

		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('id', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));
	    
	    try 
	    {
	        $products = $infusionsoft->data->query(
	                    'Product',
	                    10, 0,
	                    ['ID' => $plan_id],
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
	                    ['ID' => $plan_id],
	                    ['Id', 'ProductName', 'Description', 'ProductPrice', 'Status'],
	                    'ProductName',
	                    true);
	    }

	    $contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
	    
	    $cc = [];
	    foreach ($contacts as $contact) 
	    {
	        $c = $infusionsoft->contacts->load($contact['Id'], ['Id', 'FirstName', 'LastName']);

	        $credit_cards = $infusionsoft->data->query(
	                    'CreditCard',
	                    10, 0,
	                    ['ContactID' => $c['Id'], 'Status' => 3],
	                    ['Id', 'CardType', 'Last4', 'Status'],
	                    'Last4',
	                    true);

	       	foreach ($credit_cards as $card)
	       		$cc[]=$card;
	    }

	    if( isset($contacts[0]) )
	    	return Response::json(['product'=>$products[0], 'contact'=>$contacts[0], 'credit_card'=>$cc]);
	    else
	    	return Response::json(['error'=>'Invalid Contact']);
	}

	public function makeInvoice()
	{
		$plan_id = Input::get('plan_id');
		$card_id = Input::get('card_id');
		$email 	 = Input::get('email');

		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('id', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));

		$products = null;
	    
		try 
		{
			$products = $infusionsoft->data->query(
	                    'Product',
	                    10, 0,
	                    ['ID' => $plan_id],
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
	                    ['ID' => $plan_id],
	                    ['Id', 'ProductName', 'Description', 'ProductPrice', 'Status'],
	                    'ProductName',
	                    true);
		}

		if( $products ){
			$contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
			$contact = null;
			$credit_card = null;

			foreach ($contacts as $c) 
			{
				$contact = $infusionsoft->contacts->load($c['Id'], ['Id', 'FirstName', 'LastName']);
		
				$credit_cards = $infusionsoft->data->query(
								'CreditCard',
								10, 0,
								['ContactID' => $contact['Id'], 'Id'=>$card_id, 'Status' => 3],
								['Id', 'CardType', 'Last4', 'Status'],
								'Last4',
								true);
				
				foreach ($credit_cards as $card)
	       			$credit_card = $card;
			}

			if( $credit_card ){
	    		$name = "Test Invoice";
	    		$orderDate = new DateTime("now");
	    		$leadAffiliateID = 0;
	    		$saleAffiliateID = 0;

	    		$invoiceID 	 = $infusionsoft->invoices()->createBlankOrder($contact['Id'], $name, $orderDate, $leadAffiliateID, $saleAffiliateID);
	    		$productID 	 = $products[0]['Id'];
	    		$type 		 = 4; //Product
	    		$price 		 = $products[0]['ProductPrice'];
	    		$quantity	 = 1;
	    		$description 	 = "New Invoice Item";
	    		$notes		 = "Test Item";

	    		$infusionsoft->invoices()->addOrderItem($invoiceID, $productID, $type, $price, $quantity, $description, $notes);
				//$invoiceID = 53334;

	    		$job = $infusionsoft->data->query(
								'Invoice',
								10, 0,
								['Id' => $invoiceID],
								['Id', 'JobId'],
								'Id',
								true);
			$infusionsoft->data->update("Job", $job[0]['JobId'], ['JobTitle'=>'Test Job']);

			$jobs = $infusionsoft->data->query('Job',
							   10, 0,
							   ['Id' => $job[0]['JobId']],
							   ['Id', 'JobTitle'],
							   'Id',
							   true);
			$notes = "Test Payment";
			$creditCardId = $credit_card;
			$merchantAccountID = "25";
			$bypassCommissions = false;

			$result = $infusionsoft->invoices()->chargeInvoice($invoiceID, $notes, $creditCardID, $merchantAccountID, $bypassComissions);

	    		dd($result);
	    	}
	    	else {
				return Response::view('error', ['error' => 'The Credit Card Id is not valid'], 404);
			}
		}
		else{
			return Response::view('error', ['error' => 'The Product Id is not valid'], 404);
		}
	}

	public function makeSubscription()
	{
		$plan_id = Input::get('plan_id');
		$card_id = Input::get('card_id');
		$email 	 = Input::get('email');

		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('id', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));

		//Product (Pricing Plan)
		$products = $this->getProducts($infusionsoft);
		if( !isset($products[$plan_id]) )
			return Response::json(['error' => 'Invalid Product']);
		$product = $products[$plan_id];
		
		//Contact
		$query = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
		if( !isset($query[0]['Id']) )
			return Response::json(['error' => 'Invalid Contact']);
		$contact_id = $query[0]['Id'];
		$contact = $infusionsoft->contacts->load($contact_id, ['Id', 'FirstName', 'LastName']);

		//Credit Card
		$query = $infusionsoft->data->query('CreditCard',10, 0,['ContactID' => $contact['Id'], 'Id'=>$card_id, 'Status' => 3],['Id', 'CardType', 'Last4', 'Status'],'Last4',true);
		if( !isset($query[0]) )
			return Response::json(['error' => 'Invalid Credit Card']);
		$credit_card = $query[0];

		//Subscription
		$contactID = $contact_id;
		$AllowDuplicate = false;
		$subscriptionID = 94; //Subscription = [92 => 197$, 94 => 97$]
		$quantity = 1;
		$price = $product['ProductPrice'];
		$taxable = false;
		$merchantAccountID = 25; //Test Merchant
		$creditCardID = $credit_card['Id'];
		$affiliateID = 0;
		$trialPeriod = 0;
		$subscriptionID = $infusionsoft->invoices()->addRecurringOrder($contactID, $AllowDuplicate, $subscriptionID, $quantity, $price, $taxable, $merchantAccountID, $creditCardID, $affiliateID, $trialPeriod);
		dd($subscriptionID);
		//$subscriptionID = 

		//Invoice For Recurring
		$invoiceID = $infusionsoft->invoices()->createInvoiceForRecurring($subscriptionID);
		//$invoiceID = 53430;

		//Charge Invoice
		
		$notes = "ChargeInvoice Testing";
		$creditCardID = $credit_card['Id'];
		$merchantAccountID = 25; //TestMerchant
		$bypassComissions = false;
		$payment = $infusionsoft->invoices()->chargeInvoice($invoiceID, $notes, $creditCardID, $merchantAccountID, $bypassComissions);

//		if( $payment['Successful'] )
//		{
			//Tag
//			$tagId = 2496;
//			$infusionsoft->contacts()->addToGroup($contact['Id'], $tagId);
//		}
//		else
//		{
//			//REMOVE THE SUBSCRIPTION!!!
//			throw new Exception("Error: The Payment process failed");
//		}
	}

	/**
		Get all the Products on InfusionSoft
		$infusionsoft 	= InfusionSoft object
	**/
	public function getProducts($infusionsoft){
		
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
					'Id'			=> $product['Id'],
					'ProductName'	=> $product['ProductName'], 
					'ProductPrice'	=> $product['ProductPrice'], 
					'Status'		=> $product['Status'],
					'Description'	=> (isset($product['Description'])) ? $product['Description'] : "-" ];
		}
		
	    return $array;
	}

	/**
		Get all the Tags from InfusionSoft
		$infusionsoft 	= InfusionSoft object
	**/
	public function getTags($infusionsoft){
		
		$tags = $infusionsoft->data->query(
	            'ContactGroup',
	            1000, 0,
	            ['Id' => 2496],
	            ['GroupCategoryId', 'GroupDescription', 'GroupName', 'Id'],
	            'Id',
	            true);

		$array = [];
		foreach($tags as $tag){
			$array[$tag['Id']] = [
					'GroupCategoryId' 	=> $tag['GroupCategoryId'], 
					'GroupDescription' 	=> $tag['GroupDescription'], 
					'GroupName'			=> $tag['GroupName'],
					'Description' 		=> (isset($tag['Description'])) ? $tag['Description'] : "-" ];
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
					['Id','ProductId','StartDate','merchantAccountId','Status'], 
					'Id', 
					true);
		return $subscrip;
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

	/**
		Get all the invoices linked to a Subscription
		$infusionsoft 		= InfusionSoft object
		$subscription_id 	= Susbcription ID from previous queries
	**/
	public function getInvoicesBySubscription($infusionsoft, $subscription_id){

		$jobs = $infusionsoft->data->query(
                'Job',
                10, 0,
                ['JobRecurringId' => $subscriptionID],
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

		return $invoic;
	}
}
