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

	    try
	    {
		    if (Request::has('code') and !$infusionsoft->getToken()) {
		        $infusionsoft->requestAccessToken(Request::get('code'));
		    }

		    if ($infusionsoft->getToken()) {
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
		$c=[];
		foreach($contacts as $contact){
			$creditCards = $infusionsoft->data->query('CreditCard',1000, 0, ['ContactId'=>$contact['ID']], ['Id','Last4','CardType','Status'], 'Id', true);
			$contact['CreditCards'] = $creditCards;
			$c[] = $contact;
		}
	    
	    return View::make('contacts', ['contacts'=>$c]);
	}

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

	    $products = $this->getProducts();

	    $data = array();
	    foreach ($contacts as $contact) 
	    {
	        $c = $infusionsoft->contacts->load($contact['Id'], ['Id', 'FirstName', 'LastName']);

	        $credit_cards = $infusionsoft->data->query(
	                    'CreditCard',
	                    10, 0,
	                    ['ContactID' => $c['Id']],
	                    ['Id', 'CardType', 'Last4', 'Status'],
	                    'Last4',
	                    true);
	        $c['CreditCards'] = $credit_cards;
	        
	        $jobs = 	$infusionsoft->data->query(
	                    'Job',
	                    10, 0,
	                    ['ContactID' => $c['Id']],
	                    ['Id', 'JobTitle', 'ProductId', 'DateCreated'],
	                    'Id',
	                    true);
	        
	        $job_array = [];
	        foreach ($jobs as $job) {
	        	$invoices =	$infusionsoft->data->query(
	                    	'Invoice',
	                    	10, 0,
	                    	['JobID' => $job['Id']],
	                    	['Id', 'Description', 'InvoiceType', 'PayStatus', 'InvoiceTotal','TotalDue', 'TotalPaid'],
	                    	'Id',
	                    	true);
	        	$job['invoices'] = $invoices;
	        	$job_array [] = $job;
	        }

	        $recs = 	$infusionsoft->data->query(
	                    'RecurringOrder',
	                    10, 0,
	                    ['ContactID' => $c['Id']],
	                    ['Id', 'merchantAccountId', 'ProductId', 'StartDate', 'EndDate'],
	                    'Id',
	                    true);
		$r=[];
		foreach($recs as $rec){
			$rec['ProductName']=$products[$rec['ProductId']]['ProductName'];
			$r[]=$rec;
		}

	        $c['Jobs'] = $job_array;
	        $c['Recs'] = $r;
	        
	        $data[] = $c;
	    }
		
	    if( isset($data[0]))
		    return View::make('contactbyemail', ['contact'=>$data[0]]);
	    else
		throw new Exception("The Contact not exists");
	}	

	public function products()
	{
		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('id', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));
	    
	    try 
	    {
	        $products = $infusionsoft->data->query(
	                    'ContactGroup',
	                    1000, 0,
	                    ['Id' => 2496],
	                    ['GroupCategoryId', 'GroupDescription', 'GroupName', 'Id'],
	                    'Id',
	                    true);
		return $products;
		return View::make('SubscriptionPlans', ['subscription_plans'=>$products, 'products'=>$this->getProducts()]);
		//dd($products);
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
	                    ['Status' => '1'],
	                    ['Id', 'ProductName', 'Description', 'ProductPrice', 'Status'],
	                    'ProductName',
	                    true);
	    }

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

	    return Response::json(['product'=>$products[0], 'contact'=>$contacts[0], 'credit_card'=>$cc]);
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

	public function getProducts(){
		$infusionsoft = $this->getInfusionsoftObject();
		$last_token = Token::orderBy('id', 'desc')->first();
		$infusionsoft->setToken(unserialize($last_token->token));

                $products = $infusionsoft->data->query(
                            'Product',
                            1000, 0,
                            ['Status' => '1'],
                            ['Id', 'ProductName', 'Description', 'ProductPrice', 'Status'],
                            'ProductName',
                            true);
		$p=[];
		foreach($products as $product){
			$p[$product['Id']]=['ProductName'=>$product['ProductName'], 'ProductPrice'=>$product['ProductPrice'], 'Status'=>$product['Status']];
			if( isset($product['Description']) ) $p['Description']=$product['Description']; else $p['Description']="-";
		}
		
	    return $p;
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
		$products = $this->getProducts();
		if( !isset($products[$plan_id]) )
			throw new Exception("Error: The Plan is invalid");
		$product = $products[$plan_id];
		
		//Contact
		$query = $infusionsoft->contacts->findByEmail($email, ['Id', 'FirstName', 'LastName']);
		if( !isset($query[0]['Id']) )
			throw new Exception("Error: The Contact is invalid");
		$contact_id = $query[0]['Id'];
		$contact = $infusionsoft->contacts->load($contact_id, ['Id', 'FirstName', 'LastName']);

		//Credit Card
		$query = $infusionsoft->data->query('CreditCard',10, 0,['ContactID' => $contact['Id'], 'Id'=>$card_id, 'Status' => 3],['Id', 'CardType', 'Last4', 'Status'],'Last4',true);
		if( !isset($query[0]) )
			throw new Exception("Error: The Credit Card is invalid");
		$credit_card = $query[0];

		//Invoice and Subscription
		$contactID = $contact_id;
		$AllowDuplicate = false;
		$subscriptionID = 94; //Professional selected this instead of 94(97$)
		$quantity = 1;
		$price = $product['ProductPrice'];
		$taxable = false;
		$merchantAccountID = 25; //Test Merchant
		$creditCardID = $credit_card['Id'];
		$affiliateID = 0;
		$trialPeriod = 0;

		$subscriptionID = $infusionsoft->invoices()->addRecurringOrder(
				$contactID, 
				$AllowDuplicate, 
				$subscriptionID, 
				$quantity, 
				$price, 
				$taxable, 
				$merchantAccountID, 
				$creditCardID, 
				$affiliateID, 
				$trialPeriod);
		dd($suscriptionID);

/*		$invoices = $infusionsoft->data->query('Invoice', 10, 0,
                                                                ['Id' => 53340],
                                                                ['AffiliateId', 'ContactId', 'CreditStatus', 'DateCreated', 'Description', 'Id', 'InvoiceTotal', 'Invoice'],
                                                                'Id',
                                                                true);*/


		$invoiceID = $infusionsoft->invoices()->createInvoiceForRecurring($subscriptionID);

		$invoiceID = 53430;
		$notes = "ChargeInvoice Testing";
		$creditCardID = $credit_card['Id'];
		$merchantAccountID = 25; //TestMerchant
		$bypassComissions = false;
		$payment = $infusionsoft->invoices()->chargeInvoice($invoiceID, $notes, $creditCardID, $merchantAccountID, $bypassComissions);*/

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
}
