<?php
require __DIR__.'/vendor/autoload.php';
use phpish\shopify;
require __DIR__.'/conf.php'; 
require __DIR__.'/marketo_class.php';
$info = file_get_contents('php://input'); 
$headers_array = array();
foreach (getallheaders() as $name => $value) {
    $headers_array[$name] =  $value ;	
}
$shop = $headers_array["X-Shopify-Shop-Domain"];
$webhook_type = $headers_array["X-Shopify-Topic"];
$orderdata = json_decode($info,true);
$getuser = getUserDetails($shop);
$shopify = shopify\client($shop, SHOPIFY_APP_API_KEY, $getuser['access_token']);
$charge = $shopify('GET /admin/api/2019-04/recurring_application_charges/'.$getuser['charge_id'].'.json',array());
wh_log('16:'.'charge:'.json_encode($charge), $shop);
// checkexpiredate($shop, $charge);
if(!isset($shop)){
	echo "Something went wrong";
	return;	
}
wh_log('22:'.'create_order file start', $shop);
wh_log('23:'.$info, $shop);

$host = $getuser['mkto_rest_endpoint'];
$clientId = $getuser['mkto_client_id'];
$clientSecret =  $getuser['mkto_secret'];
$shopifytoken = $getuser['access_token'];
$shopname= $getuser['shop_name'];
/* Get token */
	$gettoken_class = new marketo();
	$gettoken_class->host = $host;
	$gettoken_class->clientId = $clientId;
	$gettoken_class->clientSecret =  $clientSecret;
	$get_token = $gettoken_class->getToken();
/* Get token */

$billing_address = $orderdata['billing_address'];
$first_name = $billing_address['first_name'];
$last_name =  $billing_address['last_name'];
$email = $orderdata['email'];
$customer_id = $orderdata['customer']['id'];

/* get shipping cost from order */
	$shipiingprice = array();
	foreach( $orderdata['shipping_lines'] as $shippriceprice ){
		$shipiingprice[] = $shippriceprice['price'];
	}
	$final_ship_price =  array_sum($shipiingprice );
/* get shipping cost from order */

/* get total qty of products from order */
	$qty = array();
	foreach( $orderdata['line_items'] as $line_items ){
		$qty[] = $line_items['quantity'];
	}
	$totalqty =  array_sum($qty );
/* get total qty of products from order */

/* get shipping address from order */
	$address=  $orderdata['shipping_address'];
	$shipping_address =  $address['name'].", ".$address['address1'].", ".$address['address1'].", ".$address['city'].", ".$address['province'].", ".$address['zip'].", ".$address['country'];
/* get shipping address from order */


/* get billing address from order */
	$billingaddress=  $orderdata['billing_address'];
	$billing_address =  $billingaddress['name'].", ".$billingaddress['address1'].", ".$billingaddress['address1'].", ".$billingaddress['city'].", ".$billingaddress['province'].", ".$billingaddress['zip'].", ".$billingaddress['country'];
/* get billing address from order */

/*create or upadte lead from order data */
	$leads = new stdClass();
	$leads->email = $email;
	$leads->firstName = $first_name;
	$leads->lastName =  $last_name;
	$create_lead = new marketo();
	$create_lead->host = $host;
	$create_lead->clientId = $clientId;
	$create_lead->clientSecret =  $clientSecret;
	$create_lead->lookupField =  "email";
	$create_lead->input = array($leads);
	$execute_curl = $create_lead->createUpdateLead();
	wh_log('84:'."lead created or updated", $shop);
	wh_log('85:'.$execute_curl, $shop);
/*create or upadte lead from order data */

/*Assign tag to customer */
if($webhook_type=="orders/create"){
	$tag_url = "https://".$shopname."/admin/api/2020-01/customers/".$customer_id .".json";
	$customer_tag_data = json_encode(array("customer"=>array("id"=> $customer_id ,"tags"=>"synced with marketo")));
	$execute_customer_tag = assignTags($tag_url,$customer_tag_data ,$shopifytoken);
	wh_log('93:'."Customer tag updated", $shop);
	wh_log('94:'.$execute_customer_tag, $shop);
}		
/*Assign tag to customer */

/* create or upadte order custom object from order data */ 
	$order_inputarray = array();
	$order_inputarray[] =  array('emailAddress'=> $orderdata['email'],
	'orderId' =>  $orderdata['id'],
	'totalOrderAmount' => $orderdata['total_price'],
	'shippingCostInOrder' =>  $final_ship_price ,
	'billingDetails' =>  $billing_address,
	'shippingDetails' =>  $shipping_address,
	'taxInOrder' =>  $orderdata['total_tax'],
	'totalOrderDiscount' =>  $orderdata['total_discounts'],
	'totalQuantity' => $totalqty );
	$order_customobject  = new marketo();
	$order_customobject->name = "magentoOrderDetail_c";
	$order_customobject->input = $order_inputarray;
	$order_customobject->dedupeBy = "dedupeFields";
	$execute_order_customobject = $order_customobject->createUpdateCustomObject($host,$get_token);

	wh_log('116:'."Order custom object updated", $shop);
	wh_log('117:'.$execute_order_customobject, $shop);

/* create or update order custom object from order data */

/*Assign tag to order */
if($webhook_type=="orders/create" && $orderdata['email'] != "") {
		$orderid = $orderdata['id'];
		$tag_url = "https://".$shopname."/admin/api/2020-01/orders/".$orderid .".json";
		$tagdata = json_encode(array("order"=>array("id"=> $orderid ,"tags"=>"synced with marketo")));
		$execute_tag = assignTags($tag_url,$tagdata ,$shopifytoken);
		echo $execute_tag;
}		
/*Assign tag to order */

/* create or upadte product custom object from order data */
	$inputarray = array();// create array for product data from order data */
	foreach($orderdata['line_items'] as $key => $productsdata){
			$inputarray[] =  array('emailAddress'=> $orderdata['email'],
			'incrementId' => $data['order_number'].$key,
			'orderId' =>  $orderdata['id'],
			'productName' => $productsdata['name'],
			'productSku' =>  $productsdata['sku'],
			'productAmount' => $productsdata['price'],
			'productQty' => $productsdata['quantity'],
			'itemStatus' => $productsdata['fulfillment_status'],
			'productid' => $productsdata['id']);
	}
	wh_log('161:'."Product data populated", $shop);
	wh_log('162:'."inputarray".json_encode($inputarray), $shop);
	
	/* create or update product custom object in marketo */
	$upsert = new marketo();
	$upsert->name = "magentoProductDetail_c";
	$upsert->input = $inputarray;
	$upsert->dedupeBy = "dedupeFields";
	$execute_curl = $upsert->createUpdateCustomObject($host,$get_token);
	wh_log('170:'."Product custom object updated".json_encode($inputarray), $shop);
	wh_log('171:'.$execute_curl.json_encode($inputarray), $shop);
/* create or upadte product custom object from order data */
wh_log('173:'.'create_order file close', $shop);