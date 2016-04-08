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
		$contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
	    
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
		dd($contact);
	    return View::make('contact', ['contact' => $contact]);
	}	

	/**
		Returns a view with data from a specific Tag
	**/
	public function tags($tag_id)
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);

		$tags = $this->getTag($infusionsoft, $tag_id);
		return Response::json($tags);
	}

	/**
		Returns a view with all the Subscription Plans stored on Infusionsoft
	**/
	public function subscr(){

		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);
		$sus = $this->getSubscriptionPlans($infusionsoft);
		return Response::json($sus);
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
		
		$infusionsoft = $this->getInfusionsoftObject();
		$infusionsoft = $this->refreshToken($infusionsoft);

	    $products = $this->getProducts($infusionsoft);
	    if( !isset($products[$plan_id]) )
	    	return Response::json(['errors' => ['Invalid Plan']]);

	    $contacts = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
		if( !isset($contacts[0]) )
			return Response::json(['errors' => ['Invalid Contact']]);
	    
		$contact = $infusionsoft->contacts->load($contacts[0]['Id'], ['Id', 'FirstName', 'LastName']);
		$credit_cards = $this->getCreditCards($infusionsoft, $contact['Id']);
		$cc = [];
		foreach ($credit_cards as $card)
			$cc[]=$card;

		return Response::json(['product'=>$products[$plan_id], 'contact'=>$contact, 'credit_card'=>$cc]);
	}

	/**
		Creates a new Subscription for the contact. 
		We should manage the plan (Product), the contact (Customer) and the Credit Card.
		Then we create the subscription using the SubscriptionPlanID
	**/
	public function makeSubscription()
	{
		$plan_id = Input::get('plan_id');
		$card_id = Input::get('card_id');
		$email 	 = Input::get('email');
		
		$subscriptionPlans	= [216 => 88,    220 => 94,   218 => 100];
		$successTags 		= [216 => 2494 , 220 => 2496, 218 => 2498];
		$failedTags  		= [216 => 2074];

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
		return Response::json(['success'=>'true', 'info' => ['Test approach'], 'product_id' => $product['Id'] ]);

		//Subscription
		$subscriptionPlanId  = $subscriptionPlans[ $product['Id'] ];
		$merchantAccountID 	 = 25;
		$actualSubscriptions = $this->getSubscriptionsByProduct($infusionsoft, $contact['Id'], $product['Id'], 'Active');
		dd($actualSubscriptions);
		if( !count($actualSubscriptions) ){
			$subscriptionID = $infusionsoft->invoices()->addRecurringOrder(
							$contact['Id'], false, $subscriptionPlanId, 1, 
							$product['ProductPrice'], 
							false, 
							$merchantAccountID, 
							$credit_card['Id'], 
							0, 0);	
		}
		else{
			$inactiveSubscription = $actualSubscriptions[0];
			//$infusionsoft->data->update('RecurringOrder', $inactiveSubscription['Id'], ['Status'=>'Active']);
			//$subscriptionID = $inactiveSubscription['Id'];
		}

		//If the Subscription is New or Reactivated, this is its Id
		dd($subscriptionID);

		//Invoice For Recurring
		$invoiceID = $infusionsoft->invoices()->createInvoiceForRecurring($subscriptionID);
		
		//Charge Invoice
		$notes = "Invoice - ".$product['Description'];
		$payment = $infusionsoft->invoices()->chargeInvoice(
							$invoiceID, $notes, 
							$credit_card['Id'], $merchantAccountID, false);
		//Invoice Charged Successfully
		if( $payment['Successful'] ){	
			$infusionsoft->contacts()->addToGroup($contact['Id'], $successTags[ $product['Id']]);
			return Response::json(['success'=>'false', "info" => $payment, "product_id" => $product['Id']] );
		}
		else
		{
			$infusionsoft->contacts()->addToGroup($contact['Id'], $failedTags[ $product['Id']]);
			return Response::json(['success'=>'false', 'info'=>'Payment failed']);
		}
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
					['Id','ProductId','StartDate','merchantAccountId','Status','SubscriptionPlanId'], 
					'Id', 
					true);
		return $subscrip;
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
}
