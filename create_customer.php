<?php 
	require __DIR__.'/vendor/autoload.php';
	use phpish\shopify;
	require __DIR__.'/conf.php'; 
	require __DIR__.'/marketo_class.php'; 
	$info = file_get_contents('php://input');
	$headers_array = array();
	foreach (getallheaders() as $name => $value) {$headers_array[$name] =  $value ;}
	$shop = $headers_array["X-Shopify-Shop-Domain"];
	$webhook_type = $headers_array["X-Shopify-Topic"];
	wh_log('14:'.'create_customer file start', $shop);
	$webhookValue = json_decode($info,true);
	wh_log('15:'.json_encode($webhookValue), $shop);
	$getuser = getUserDetails($shop);
	wh_log('17:'.json_encode($getuser), $shop);

	// $charge = $shopify('GET /admin/api/2019-04/recurring_application_charges/'.$getuser['charge_id'].'.json',array());
	// checkexpiredate($shop, $charge);

	$shopifytoken = $getuser['access_token'];
	$shopname= $getuser['shop_name'];
	$customer_id = $webhookValue['id']; 
	wh_log('24:'."hello", $shop);

	$leads = new stdClass();
	$leads->email = $webhookValue['email']; 
	$leads->firstName = $webhookValue['first_name']; 
	$leads->lastName =  $webhookValue['last_name']; 
	$upsert = new marketo();
	$upsert->host = $getuser['mkto_rest_endpoint'];
	$upsert->clientId =$getuser['mkto_client_id'];
	$upsert->clientSecret =  $getuser['mkto_secret'];
	$upsert->input = array($leads);
	$execute_curl = $upsert->createUpdateLead();
	wh_log('36:'.$execute_curl, $shop);
	wh_log('37:'."lead created or updated", $shop);
			
	/*Assign tag to customer */
	if($webhook_type == "customers/enable"){
		$tag_url = "https://".$shopname."/admin/api/2020-01/customers/".$customer_id .".json";
		$customer_tag_data = json_encode(array("customer"=>array("id"=> $customer_id ,"tags"=>"synced with marketo")));
		$execute_customer_tag = assignTags($tag_url,$customer_tag_data ,$shopifytoken);
		wh_log('44:'."Customer tag updated", $shop);
		wh_log('45:'.$execute_customer_tag, $shop);
	}
	wh_log('47:'.'create_customer file close', $shop);
	/*Assign tag to customer */
?>