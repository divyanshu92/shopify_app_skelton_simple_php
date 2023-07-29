<?php
	session_start();
	require __DIR__.'/vendor/autoload.php';
	use phpish\shopify;
	require __DIR__.'/conf.php';
	require __DIR__.'/marketo_class.php'; 

	$getuser = getUserDetails($_REQUEST['shop']);
	$shopifytoken = $getuser['access_token'];
	$host = $getuser['mkto_rest_endpoint'];
	$clientId = $getuser['mkto_client_id'];
	$clientSecret =  $getuser['mkto_secret'];
	$shopifytoken = $getuser['access_token'];
	$shopname= $getuser['shop_name'];
	$abandoned_checkout_id = $_REQUEST['id'];
	$crontime = 1;
	$shopify = shopify\client($_REQUEST['shop'], SHOPIFY_APP_API_KEY, $shopifytoken);
	$charge = $shopify('GET /admin/api/2019-04/recurring_application_charges/'.$getuser['charge_id'].'.json',array());
	checkexpiredate($_REQUEST['shop'], $charge);

/* Get token */
	$gettoken_class = new marketo();
	$gettoken_class->host = $host;
	$gettoken_class->clientId = $clientId;
	$gettoken_class->clientSecret =  $clientSecret;
	$get_token = $gettoken_class->getToken();
/* Get token */
$limit = 10;
$count =  $shopify('GET /admin/api/2020-01/checkouts/count.json');
$shop_json =  $shopify('GET /admin/api/2020-01/shop.json');
$forlooprun = ceil($count/$limit);
$date = new DateTime("now", new DateTimeZone($shop_json['iana_timezone']) );
$ordersdatasss_raw = '';
$ordersdatas_raw = getabundantcartdata('https://'.$shopname.'/admin/api/2020-01/checkouts.json?limit=2', $shopifytoken, $ordersdatasss_raw,0);

if ($_REQUEST['id'] != '') {
	//already syced condition
	if($ordersdatas_raw == ''){
		$log_msg = 'This Abandoned Checkout is already synced-80-:'.$_REQUEST['id'].' ';wh_log($log_msg, $shopname);
		returnhtml('This Abandoned Checkout is already synced', $shopname);		
	}else{
		//if single id is not synced
		foreach ($ordersdatas_raw as $keys => $ordersdatass) {
			foreach ($ordersdatass as $key => $ordersdata) {
				if($ordersdata['id'] == $abandoned_checkout_id){$ordersdatas[0][0] = $ordersdata;}
			}
		}
	}
} else {
	//bulk check
	$ordersdatas = $ordersdatas_raw;

	//if no data to synced
	if($ordersdatas_raw == ''){
		$log_msg = 'All Data is already synced with Marketo-95-';wh_log($log_msg, $shopname);
		$log_msg = '_____________________________________________________________';wh_log($log_msg, $shopname);	
		returnhtml('All Data is already synced with Marketo', $shopname);
	}else{
		//check if some data need to synced		
		foreach ($ordersdatas as $key => $ordersdatass) {
			foreach ($ordersdatass as $keys => $ordersdata) {				
				$diff = abs($date->format('U') - strtotime($ordersdata[updated_at]));
				// set value on which cronjob runs 1 hour
				if(floor($diff / (60*60)) < $crontime){
					$ordersdatas[$key][$keys]['add_to_queue'] = 1;
				}else{
					unset($ordersdatas[$key][$keys]);
				}
			}
		}
		$ordersdatas = array_filter($ordersdatas);
	}
}
		# Making an API request can throw an exception
		$customer_data_array = array();
		$orders_data_array = array();
		$orders_products_data_array = array();
		$orders_tags_array = array();	
		$explode_tags = array();
		foreach ($ordersdatas as $key => $ordersdata) {
			foreach($ordersdata as $data){
				$shipping_address = $data['shipping_address']; //to get first name and last name f a customer
				$first_name = $shipping_address['first_name'];
				$last_name =  $shipping_address['last_name'];
				$email = $data['email'];
				$customer_id = $data['customer']['id'];

				/* customer data array from order */
				$customer_data_array[] = array("email"=>$email ,"firstName"=>$first_name,"lastName"=>$last_name);
				
				/* get product Names of products from order */
				$productNames = array();
				$productImage = array();
				foreach( $data['line_items'] as $line_items ){
					$productdata =  $shopify('GET /admin/api/2020-01/products/'. $line_items['product_id'] .'.json');
					$productNames[] = $line_items['title'];
					foreach( $productdata['images'] as $images ){
						$productImage[] = $images['src'];
					}			
				}
				$productName =  implode(", ",$productNames);
				$productImage =  implode(", ",$productImage);
				/* get product Names of products from order */
				
				
				/*create array for abandoned_checkout data from order data */
				$orders_data_array[] =  array(
					'cartLink'=>$data['abandoned_checkout_url'],
					'emailAddress'=>$data['email'],
					'productNames'=>$productName,
					'imageUrls'=>$productImage,
					'status'=>'abandoned_checkout',
					'websitePath'=>$shopname
				);
				/*create array for abandoned_checkout data from order data */	
			}
		}
		/*foreach lop on orderdata*/

		/*assign tag to customer*/
		$tag_url = "https://".$shopname."/admin/api/2020-01/customers".$customer_id .".json";
		$customer_tag_data = json_encode(array("customer"=>array("id"=> $customer_id ,"tags"=>"synced with marketo")));
		$execute_customer_tag = assignTags($tag_url,$customer_tag_data ,$shopifytoken);
		/*assign tag to customer*/
		
		/*  to get only unique emails from all customers data */
		foreach($customer_data_array as $dataarray) {
				$hash = $dataarray["email"];
				$unique_array[$hash] = $dataarray;
		}
		$uniques_emails_array = array_values($unique_array);
		$split_cistomers_array = array_chunk($uniques_emails_array,5);	
		// echo '<pre>';print_r($split_cistomers_array);die;
		/*create or upadte lead from order data */
		foreach($split_cistomers_array as $istomers_array){
			$leads = new stdClass();
			$create_lead = new marketo();
			$create_lead->host = $host;
			$create_lead->clientId = $clientId;
			$create_lead->clientSecret =  $clientSecret;
			$create_lead->input = $istomers_array;
			$execute_curl = $create_lead->createUpdateLead();
			$log_msg = 'create_lead '.json_encode($execute_curl).' ';wh_log($log_msg, $shopname);
		}
		
		/*create or upadte lead from order data */
	
		$execute_curl_lead = json_decode($execute_curl);
		
		/* create or upadte order custom object from order data */ 
		$execute_order_customobject = null;
			$split_orders_array = array_chunk($orders_data_array,10);
			foreach($split_orders_array as $orders_array){
				$order_customobject  = new marketo();
				$order_customobject->name = "shopifyabandonedcartdetails_c";
				$order_customobject->input = $orders_array;
				$order_customobject->dedupeBy = "dedupeFields";
				$execute_order_customobject = $order_customobject->createUpdateCustomObject($host,$get_token);
				$execute_order_customobject = json_decode($execute_order_customobject,true);
				$log_msg = 'shopify_Abandoned_checkout_c '.json_encode($execute_order_customobject).' ';wh_log($log_msg, $shopname);
			}
		/* create or upadte order custom object from order data */  
		
		$execute_curl = null;
		/*Product custom object update */


		/*Product custom object update */
		if($execute_order_customobject['success'] == 1){
			if ($_REQUEST['id'] != '') {				
				if($execute_order_customobject['result'][0]['status'] == 'updated'){
					$log_msg = 'Already Synced-225-: '.'#'.$ordersdata[0]['id'].' ';wh_log($log_msg, $shopname);
					$log_msg = '_____________________________________________________________';wh_log($log_msg, $shopname);
					returnhtml('#'.$ordersdata[0]['id'].' is already created and now updated  with marketo if any updation is done', $shopname);					
				}else{
					$log_msg = 'New Synced-228-: '.'#'.$ordersdata[0]['id'].' ';wh_log($log_msg, $shopname);
					$log_msg = '_____________________________________________________________';wh_log($log_msg, $shopname);
					returnhtml('#'.$ordersdata[0]['id'].' is now synced with marketo', $shopname);
				}
			}else{				
				$updated=$created=0;
				foreach ($execute_order_customobject['result'] as $key => $value) {
					if($value['status'] == 'updated'){
						$updated++;
					}elseif ($value['status'] == 'created') {
						$created++;
					}else{
						//echo '<pre>';print_r($execute_order_customobject);
					}
				}
				$log_msg = '-236-Updated='.$updated.', Created='.$created.', last synced id is '.$last_synced_id;wh_log($log_msg, $shopname);
				$log_msg = '_____________________________________________________________';wh_log($log_msg, $shopname);	
				returnhtml('All Data is synced with Marketo', $shopname);
			}

		}	

?>
