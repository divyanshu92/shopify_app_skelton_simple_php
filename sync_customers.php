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
	$shopname= $getuser['shop_name'];
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
		$customer_ids = implode(",",$_REQUEST['ids']);// need comma separated values for getting multiple orders data
	}
	
	/* when request is for single order to sync from order detail page */
	if(isset($_REQUEST['id'])){
		$customer_ids = $_REQUEST['id'];
	}	
	$shopify = shopify\client($_REQUEST['shop'], SHOPIFY_APP_API_KEY, $shopifytoken);
	try
	{
		# Making an API request can throw an exception
		$customersdata =  $shopify('GET /admin/api/2020-01/customers.json', array('ids'=> $customer_ids,"status"=>"any"));
		$customer_data_array = array();
		$customer_tags_array = array();
		$explode_tags = array();
		foreach($customersdata as $data){
			$first_name = $data['first_name'];
			$last_name =  $data['last_name'];
			$email = $data['email'];
			$customer_id = $data['id'];
			$tags = $data['tags'];
			
			/* customer data array from order */
			$customer_data_array[] = array("email"=>$email ,"firstName"=>$first_name,"lastName"=>$last_name);
			
			/* to get current tags and append sunced t amrketo tag */
				if(!empty($tags)){
					$explode_tags = explode(",",$tags);
				}
				array_push($explode_tags,"synced with marketo");
				$unique_tags = array_unique($explode_tags);
				$implode_tags = implode(",",$unique_tags );
				$customer_tags_array[$customer_id ] = $implode_tags ;
			/* to get current tags and append sunced t amrketo tag */
		}
		/*  to get only unique emsils from all customers data */
		foreach($customer_data_array as $dataarray) {
				$hash = $dataarray["email"];
				$unique_array[$hash] = $dataarray;
		}
		$uniques_emails_array = array_values($unique_array);
		$split_customer_array = array_chunk($uniques_emails_array,5);
		foreach($split_customer_array as $customer_array){
		/*create or upadte lead from order data */
				$leads = new stdClass();
				$create_lead = new marketo();
				$create_lead->host = $host;
				$create_lead->clientId = $clientId;
				$create_lead->clientSecret =  $clientSecret;
				$create_lead->input = $customer_array;
				$execute_curl = $create_lead->createUpdateLead();
		}
		/*create or upadte lead from order data */	
		/*Assign tag to customer */
		foreach($customer_tags_array as $customertag_id => $customertag_value){
			$tag_url = "https://".$shopname."/admin/api/2020-01/customers/".$customertag_id .".json";
			$customer_tag_data = json_encode(array("customer"=>array("id"=> $customertag_id ,"tags"=>$customertag_value)));
			$execute_customer_tag = assignTags($tag_url,$customer_tag_data ,$shopifytoken);
		}
		/*Assign tag to customer */
		
		$execute_curl_response = json_decode($execute_curl);
		
		$index = 0;
		echo "<table>";
		echo '<tr class="row head_row"><td>Name</td><td>City</td><td>Orders</td><td>Spent</td><td>Sync Status</td></tr>';
		foreach($customersdata as $single_customer){
			echo "<tr class='row'>";
			echo "<td>".$single_customer['first_name'].' '.$single_customer['last_name']."</td>";
			echo "<td>".$single_customer['default_address']['city'].', '.$single_customer['default_address']['country_code']."</td>";
			echo "<td>".$single_customer['orders_count']." orders</td>";
			echo "<td>".$single_customer['currency'].' '.$single_customer['total_spent']." spent</td>";
			echo "<td class='status_row'>".$execute_curl_response->result[$index]->status."</td>";
			echo "</tr>";
			$index++;
		}
		echo '</table>';
		$index_flag = 0;
		foreach($customersdata as $single_customer){
			$log_data['log_id'] = $single_customer['id'];
			$log_data['shop_name'] = 'nouser';
			if(isset($_REQUEST['shop'])){ $log_data['shop_name'] = $_REQUEST['shop']; }
			$log_data['log_name'] = $single_customer['first_name'].' '.$single_customer['last_name'];
			$log_data['log_type'] = 'customer';
			$log_data['log_misc_field'] = $single_customer['default_address']['city'];
			$log_data['log_status1'] = $execute_curl_response->result[$index_flag]->status;
			$log_data['log_status2'] = null;
			$log_data['log_updated_id'] = $execute_curl_response->result[$index_flag]->id;
			saveLogsDetails($log_data);
			$index_flag++;
		}
		
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
<style>
	table{ width:80%; font-size:15px; font-family:segoe ui; border-collapse: collapse; background-color:#FFF; }
	table tr{ padding:20px; }
	.head_row{ font-weight:bold; }
	.row td{ padding:15px 10px; }
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
