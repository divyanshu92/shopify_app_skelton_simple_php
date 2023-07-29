<?php 
	session_start();
	require __DIR__.'/vendor/autoload.php';
	use phpish\shopify;
	require __DIR__.'/conf.php';
	require __DIR__.'/marketo_class.php';
	$getuser = getUserDetails($_REQUEST['shop']);
	$host = $getuser['mkto_rest_endpoint'];
	$clientId = $getuser['mkto_client_id'];
	$clientSecret =  $getuser['mkto_secret'];
	$shopifytoken = $getuser['access_token'];
	$shopname= $getuser['shop_name'];
	$shopify = shopify\client($_REQUEST['shop'], SHOPIFY_APP_API_KEY, $shopifytoken);
	$charge = $shopify('GET /admin/api/2019-04/recurring_application_charges/'.$getuser['charge_id'].'.json',array());
	checkexpiredate($getuser['shop_name'], $charge);
 
	/* Get token */
		$gettoken_class = new marketo();
		$gettoken_class->host = $host;
		$gettoken_class->clientId = $clientId;
		$gettoken_class->clientSecret =  $clientSecret;
		$get_token = $gettoken_class->getToken();
	/* Get token */ 
	
	/* when request is from mass action for bulk sync */
	if(isset($_REQUEST['ids'])){
		$order_ids = implode(",",$_REQUEST['ids']);// need comma separated values for getting multiple orders data
	}
	
	/* when request is for single order to sync from order detail page */
	if(isset($_REQUEST['id'])){
		$order_ids = $_REQUEST['id'];
	}	
	
	try
	{
		# Making an API request can throw an exception
		$ordersdata =  $shopify('GET /admin/api/2020-01/orders.json', array('ids'=> $order_ids,"status"=>"any"));
		$customer_data_array = array();
		$orders_data_array = array();
		$orders_products_data_array = array();
		$orders_tags_array = array();
		$explode_tags = array();
		foreach($ordersdata as $data){
			$billing_address = $data['billing_address']; //to get first name and last name f a customer
			$first_name = $billing_address['first_name'];
			$last_name =  $billing_address['last_name'];
			$email = $data['email'];
			$order_id = $data['id'];
			$tags = $data['tags'];
			$customer_id = $data['customer']['id'];
			/* to get current tags and append sunced t amrketo tag */
				if(!empty($tags)){
					$explode_tags = explode(",",$tags);
				}
				array_push($explode_tags,"synced with marketo");
				$unique_tags = array_unique($explode_tags);
				$implode_tags = implode(",",$unique_tags );
				$orders_tags_array[$order_id ] = $implode_tags ;
			/* to get current tags and append sunced t amrketo tag */
			
			/* get shipping address from order */
				$address=  $data['shipping_address'];
				$shipping_address =  $address['name'].", ".$address['address1'].", ".$address['address1'].", ".$address['city'].", ".$address['province'].", ".$address['zip'].", ".$address['country'];
			/* get shipping address from order */


			/* get billing address from order */
				$billingaddress=  $data['billing_address'];
				$billing_address =  $billingaddress['name'].", ".$billingaddress['address1'].", ".$billingaddress['address1'].", ".$billingaddress['city'].", ".$billingaddress['province'].", ".$billingaddress['zip'].", ".$billingaddress['country'];
			/* get billing address from order */
			
			/* get total qty of products from order */
				$qty = array();
				foreach( $data['line_items'] as $line_items ){
				$qty[] = $line_items['quantity'];
				}
				$totalqty =  array_sum($qty );
			/* get total qty of products from order */
		
			/* get shipping cost from order */
				$shipiingprice = array();
				foreach( $data['shipping_lines'] as $shippriceprice ){
					$shipiingprice[] = $shippriceprice['price'];
				}
				$final_ship_price =  array_sum($shipiingprice );
			/* get shipping cost from order */

			/* customer data array from order */
			$customer_data_array[] = array("email"=>$email ,"firstName"=>$first_name,"lastName"=>$last_name);
		
			// create array for orders data from order data */
			$orders_data_array[] =  array(	
				'emailAddress'=> $email,	
				'orderId' =>  $data['id'],	
				'totalOrderAmount' => $data['total_price'],	
				'shippingCostInOrder' =>  $final_ship_price ,	
				'billingDetails' =>  $billing_address,	
				'shippingDetails' =>  $shipping_address,	
				'taxInOrder' =>  $data['total_tax'],	
				'totalOrderDiscount' =>  $data['total_discounts'],	
				'totalQuantity' => $totalqty	
			);
			// echo '<pre>';print_r($data['line_items']);

			// create array for product data from order data */
			foreach($data['line_items'] as $key => $productsdata){
					$orders_products_data_array[] =  array(
						'emailAddress'=> $data['email'],
						'incrementId' => $data['order_number'].$key,
						'orderId' =>  $data['id'],
						'productName' => $productsdata['name'],
						'productSku' =>  $productsdata['sku'],
						'productAmount' => $productsdata['price'],
						'productQty' => $productsdata['quantity'],
						'itemStatus' => $productsdata['fulfillment_status'], 
						'productid' => $productsdata['id']
					);
			}			
		}		
		
		/*Assign tag to order */
		$all_orders = array();
		foreach($orders_tags_array as $oredertag_id => $ordertag_value){
			$tag_url = "https://".$shopname."/admin/api/2020-01/orders/".$oredertag_id .".json";
			$tagdata = json_encode(array("order"=>array("id"=> $oredertag_id ,"tags"=>"synced with marketo")));
			$execute_tag = assignTags($tag_url,$tagdata ,$shopifytoken);
			array_push($all_orders,$execute_tag);
		}
		/*Assign tag to order */
		
		$tag_url = "https://".$shopname."/admin/api/2020-01/customers/".$customer_id .".json";
		$customer_tag_data = json_encode(array("customer"=>array("id"=> $customer_id ,"tags"=>"synced with marketo")));
		$execute_customer_tag = assignTags($tag_url,$customer_tag_data ,$shopifytoken);
		
		/*  to get only unique emsils from all customers data */
		foreach($customer_data_array as $dataarray) {
				$hash = $dataarray["email"];
				$unique_array[$hash] = $dataarray;
		}
		$uniques_emails_array = array_values($unique_array);
		$split_cistomers_array = array_chunk($uniques_emails_array,5);

		foreach($split_cistomers_array as $istomers_array){
		/*create or upadte lead from order data */
			$leads = new stdClass();
			$create_lead = new marketo();
			$create_lead->host = $host;
			$create_lead->clientId = $clientId;
			$create_lead->clientSecret =  $clientSecret;
			$create_lead->input = $istomers_array;
			$execute_curl = $create_lead->createUpdateLead();
		}
		/*create or upadte lead from order data */
	
		$execute_curl_lead = json_decode($execute_curl);
		
		/* create or upadte order custom object from order data */ 
		$execute_order_customobject = null;
			$split_orders_array = array_chunk($orders_data_array,10);
			foreach($split_orders_array as $orders_array){
				$order_customobject  = new marketo();
				$order_customobject->name = "magentoOrderDetail_c";
				$order_customobject->input = $orders_array;
				$order_customobject->dedupeBy = "dedupeFields";
				$execute_order_customobject = $order_customobject->createUpdateCustomObject($host,$get_token);
				$execute_order_customobject = json_decode($execute_order_customobject);
			}
		/* create or upadte order custom object from order data */  

		$execute_curl = null;
		/*Product custom object update */
			$split_products_array = array_chunk($orders_products_data_array,10);
			foreach($split_products_array as $products_array){
				$upsert = new marketo();
				$upsert->name = "magentoProductDetail_c";
				$upsert->input = $products_array;
				$upsert->dedupeBy = "dedupeFields";
				$execute_curl = $upsert->createUpdateCustomObject($host,$get_token);
				$execute_curl = json_decode($execute_curl);  
			}
		/*Product custom object update */	

		$index = 0;
		$echo = '<table class="Polaris-DataTable__Table">';
		$echo .= "<tr><th  class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn Polaris-DataTable__Cell--total' scope='row'>ID</th><th  class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn Polaris-DataTable__Cell--total' scope='row'>Customer</th><th  class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn Polaris-DataTable__Cell--total' scope='row'>Email</th><th  class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn Polaris-DataTable__Cell--total' scope='row'>Date</th><th  class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn Polaris-DataTable__Cell--total' scope='row'>Payment</th><th  class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn Polaris-DataTable__Cell--total' scope='row'>Fulfillment</th><th  class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn Polaris-DataTable__Cell--total' scope='row'>Total</th><th  class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn Polaris-DataTable__Cell--total' scope='row'>Object1</th><th  class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn Polaris-DataTable__Cell--total' scope='row'>Object2</th></tr>";

		foreach($all_orders as $single_order){
			$single_order = json_decode($single_order);
			$the_fulfillment_status = 'Unfulfilled';
			if($single_order->order->fulfillment_status != null){ $the_fulfillment_status = $single_order->order->fulfillment_status; }
			$echo .=  "<tr class='row'>";			
			$echo .=  "<td class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--numeric'><a class='Polaris-Link' onclick='orderfunction(\"".$single_order->order->id."\")' href='https://".$shopname."/admin/orders/".$single_order->order->id."' data-polaris-unstyled='true'>".$single_order->order->order_number."</a></td>";
			$echo .=  "<td class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--numeric'><a onclick='customerfunction(\"".$single_order->order->customer->id."\")' class='Polaris-Link' href='https://".$shopname."/admin/customers/".$single_order->order->customer->id."' data-polaris-unstyled='true'>".$single_order->order->billing_address->name."</a></td>";
			$echo .=  "<td class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--numeric'>".$single_order->order->email."</td>";
			$echo .=  "<td class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--numeric'>".date("d-M-Y", strtotime($single_order->order->created_at))."</td>";
			$echo .=  "<td class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--numeric'>".$single_order->order->financial_status."</td>";
			$echo .=  "<td class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--numeric'>".$the_fulfillment_status."</td>";
			$echo .=  "<td class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--numeric'>".$single_order->order->total_price."</td>";
			$echo .=  "<td class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--numeric'>".$execute_order_customobject->result[$index]->status."</td>";
			$echo .=  "<td class='Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--numeric'>".$execute_curl->result[$index]->status."</td>";
			$echo .=  "</tr>";
			$index++;
		}
		$echo .=  '</table>';

		$index_flag = 0;
		foreach($all_orders as $single_order){
			$single_order = json_decode($single_order);
			$log_data['log_id'] = $single_order->order->id;
			$log_data['shop_name'] = 'nouser';
			if(isset($_REQUEST['shop'])){ $log_data['shop_name'] = $_REQUEST['shop']; }
			$log_data['log_name'] = $single_order->order->billing_address->name;
			$log_data['log_type'] = 'order';
			$log_data['log_misc_field'] = $single_order->order->email;
			$log_data['log_status1'] = $execute_curl->result[$index_flag]->id;
			$log_data['log_status2'] = $execute_curl->result[$index_flag]->status;
			$log_data['log_updated_id'] = $execute_curl_lead->result[$index_flag]->id;
			echo '<br>';			
			saveLogsDetails($log_data);
			$index_flag++;
		}
		returnhtml($echo, $shopname);
		
	}
	catch (shopify\ApiException $e)
	{
		# HTTP status code was >= 400 or response contained the key 'errors'
		echo $e;
		print_r($e->getRequest());
		print_r($e->getResponse());	
	}
	catch (shopify\CurlException $e)
	{
		# cURL error
		echo $e;
		print_r($e->getRequest());
		print_r($e->getResponse());
	}
?>
<script>
document.getElementsByClassName(".loader").setAttribute("display", "none;");
</script>
<style>
table{ width:100%; font-size:14px; font-family:segoe ui; border-collapse: collapse; background-color:#FFF; }
table tr{ padding:20px; }
.head_row{ font-weight:bold; font-size:15px; }
.row td{ padding:16px 10px; }
table, td, th {
	border-bottom: 1px solid #EAECF0;
}
.row:hover{ background-color:#EEF1F3; }
.loader {
	border: 16px solid #f3f3f3;
	border-radius: 50%;
	border-top: 16px solid #3498db;
	width: 120px;
	height: 120px;
	-webkit-animation: spin 2s linear infinite; /* Safari */
	animation: spin 2s linear infinite;
}

/* Safari */
@-webkit-keyframes spin {
	0% { -webkit-transform: rotate(0deg); }
	100% { -webkit-transform: rotate(360deg); }
}

@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}
.status_row{ font-weight:501; }
</style>