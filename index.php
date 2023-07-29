<?php
	session_start();
	require __DIR__.'/vendor/autoload.php';
	use phpish\shopify;
	require __DIR__.'/conf.php'; 
	require __DIR__.'/marketo_class.php';
?>
<head>
  <script src="https://cdn.shopify.com/s/assets/external/app.js"></script>
  <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
	<?php
		$protocol = $_REQUEST['protocol'];
		$shop = $_REQUEST['shop'];
		$hmac = $_REQUEST['hmac'];
		$shopurl = $protocol.$shop;
	?>
  <script type="text/javascript">
    ShopifyApp.init({
      apiKey: '<?php echo SHOPIFY_APP_API_KEY ?>',
      shopOrigin: '<?php echo $shopurl ; ?>',
	  forceRedirect: false,
	  debug: false
    });
  </script>
<style>
	section {
	color: #1a1a1a;
	font-family: -apple-system, BlinkMacSystemFont, San Francisco, Roboto, Segoe UI, Helvetica Neue, sans-serif;
	}

	/* Pattern styles */
	.container {
	width: 100%;text-align: center;
	}
	.simple-button, input[type="submit"] {
	background: #4959bd;color: white;padding: 10px 20px;border: #4959bd;font-family: -apple-system, BlinkMacSystemFont, San Francisco, Roboto, Segoe UI, Helvetica Neue, sans-serif;border-radius: 3px;margin-top: 20px;cursor: pointer;	
	}
	input[type="text"] {
		padding: 5px 10px;
		border: 1px solid #d3dbe2;
		border-radius: 3px;
		font-size: 14px;    
		line-height: 1.71429rem;
		font-weight: 400;
		text-transform: initial;
		letter-spacing: initial;
		box-sizing: border-box;
		display: block;
		width: 100%;margin: 5px 0px 10px 0px;
	}
	.left-half {
	display:inline-block;vertical-align: top;
	left: 0px;
	width: 30%;text-align: left;
	}

	.right-half {display: inline-block;text-align: left;
	width: 55%;
	background-color: #ffffff;
	padding: 35px 40px;
	border-radius: 3px;
	}
	.main-div{width: 99%;padding: 15px 3px;}
	.left-half span {font-weight: 600;font-size: 16px;padding: 10px 30px;}
	.install-webook input[type="submit"]{margin-top: 5px;}
	#error-msg{ color:#de3618; }
	.error_input{ border: 1px solid #de3618; background: #fbeae5; }
	#success-msg{ color: #3c763d; }
	.success-msg{ color: #3c763d; }
	.error-msg{ color:#de3618; }
	.info-msg{ color:#004085; padding:0px 10px; }
	.row div{ float:left; }
</style>
</head>
<?php
$getuser = getUserDetails($_REQUEST['shop']);
if(!isset($getuser['access_token'])){
	echo '<script>window.top.location.href="https://'.$_REQUEST['shop'].'/admin/apps/"</script>';
}
$shopifytoken = $getuser['access_token'];
$host = $getuser['mkto_rest_endpoint'];
$clientId = $getuser['mkto_client_id'];
$clientSecret =  $getuser['mkto_secret'];
$shopname= $getuser['shop_name'];
$shopify = shopify\client($_REQUEST['shop'], SHOPIFY_APP_API_KEY, $shopifytoken);

if($getuser['charge_id'] != ''){
	$charge = $shopify('GET /admin/api/2020-01/recurring_application_charges/'.$getuser['charge_id'].'.json',array());
}else{
	$charge['status'] = '';
}

wh_log('90:'.'charge:'.json_encode($charge), $shop);
checkexpiredate($_REQUEST['shop'], $charge);
wh_log('92:', $shop);


if(!isset($getuser['access_token'])){
	echo '<script>window.top.location.href="https://'.$_REQUEST['shop'].'/admin/apps/"</script>';
}

$webhooks =  $shopify('GET /admin/api/2020-01/webhooks.json');
$user_mkto_rest_endpoint = $user_mkto_client_id = $user_mkto_secret = $user_createorder_status = $user_updateorder_status = $user_createcustomer_status = $user_updatecustomer_status = "" ; 

foreach ($webhooks as $key => $webhook) {
	if ($webhook['topic'] == 'orders/create') {
		$user_createorder_status  = '1';
	}elseif($webhook['topic'] == 'orders/updated'){
		$user_updateorder_status  = '1';		
	}elseif($webhook['topic'] == 'customers/enable'){
		$user_createcustomer_status  = '1';		
	}elseif($webhook['topic'] == 'customers/update'){
		$user_updatecustomer_status  = '1';		
	}else{}	
}

if(isset($getuser['mkto_rest_endpoint'])){
	$user_mkto_rest_endpoint  = $getuser['mkto_rest_endpoint'];
}if(isset($getuser['mkto_client_id'])){
	$user_mkto_client_id  = $getuser['mkto_client_id'];
}if(isset($getuser['mkto_secret'])){
	$user_mkto_secret  = $getuser['mkto_secret'];
}
//print_r($getuser);
?>
<form id="config_form" data-shopify-app-submit="unicorn_form_submit" action="<?php echo SITE_URL ?>validate_marketo.php">
  <section class="container">
  <div class="main-div">
	  <div class="left-half">
		  <span>Marketo Configuration</span>
	  </div>
	  <div class="right-half">
	  	    <span>Marketo Rest Endpoint</span>
			<input type="text" id='mkto_endpoint' name="mkto_endpoint" value="<?php echo $user_mkto_rest_endpoint; ?>" required>
		    <span>Client ID</span>
			<input type="text" id='mkto_clientid' name="mkto_clientid" value="<?php echo $user_mkto_client_id; ?>" required>
			<span>Client Secret</span>
			<input type="text" id='mkto_secret' name="mkto_secret" value="<?php echo $user_mkto_secret; ?>" required>
			<input type="hidden" name="shop_url" value="<?php echo $shop; ?>">
			<input type="hidden" name="hmac" value="<?php echo $hmac; ?>">
			<span id='error-msg' style="display:none;">please provide the valid credentials.</span>
			<span id='success-msg' style="display:none;">your crendentials has been updated.</span>
			<span id='server-error-msg' style="display:none;">please provide the valid credentials.</span>
			<!---<input type="submit" value="Submit">-->
			<input class="simple-button" type="button" value="Submit" onclick='submit_credentials();' />

	  </div>
  </div> 
  
 

</section>

</form>


  <section class="container">
  <div class="main-div install-webook">
	  <div class="left-half">
		  <span>Install Webhooks</span>
	  </div>
	  <div class="right-half">
	  	    <span>Install webhook for Create Order</span>
			<form id="installcreateorderwebhook" action="<?php echo SITE_URL ?>install-webook.php" name="installorderwebhook">
			  <input type="hidden" name="shop_url" value="<?php echo $shop ; ?>">
				<input type="hidden" name="webhookname" value="orders">
				<input class="simple-button create_order_button" type="button" value="click here to install" onclick="create_webhook();" style="background-color:<?php if($user_createorder_status == 1){ echo 'grey;'; } else { echo '#4959bd;'; } ?>" <?php if($user_createorder_status == 1){ echo 'disabled__'; } ?> />
				<span class="info-msg" style="display:<?php if($user_createorder_status == 1){ echo 'inline;'; } else { echo 'none;'; } ?>">this webhook is already installed</span>
				<span id="createhook_success_msg" style="display:none;" class="success-msg">'create order' webhook is installed/updated.</span>
				<span id="createhook_error_msg" style="display:none;" class="error-msg">'create order' webhook cannot be installed/updated.</span>
			</form>			
			
		    <span>Install webhook for Update Order </span>
			<form id="installupdateorderwebhook" action="<?php echo SITE_URL ?>install-webook.php" name="updateorderwebhook">
			  <input type="hidden" name="shop_url" value="<?php echo $shop ; ?>">
				<input type="hidden" name="webhookname" value="order_upadte">
			  <input class="simple-button update_order_button" type="button" value="click here to install" onclick="update_webhook();" style="background-color:<?php if($user_updateorder_status == 1){ echo 'grey;'; } else { echo '#4959bd;'; } ?>" <?php if($user_updateorder_status == 1){ echo 'disabled__'; } ?> />
				<span class="info-msg"  style="display:<?php if($user_updateorder_status == 1){ echo 'inline;'; } else { echo 'none;'; } ?>" >this webhook is already installed</span>
				<span id="updateorderhook_success_msg" style="display:none;" class="success-msg">'update order' webhook is installed/updated.</span>
				<span id="updateorderhook_error_msg" style="display:none;" class="error-msg">"update order" webhook cannot be installed/updated.</span>
			</form>
			<span>Install webhook for Create customer</span>
			<form id="installcustomerwebhook" action="<?php echo SITE_URL ?>install-webook.php" name="installcustomerwebhook">
			  <input type="hidden" name="shop_url" value="<?php echo $shop ; ?>">
			  <input type="hidden" name="webhookname" value="customers">
			  <input class="simple-button create_customer_button" type="button" value="click here to install" onclick="install_customer_webhook();" style="background-color:<?php if($user_createcustomer_status == 1){ echo 'grey;'; } else { echo '#4959bd;'; } ?>" <?php if($user_createcustomer_status == 1){ echo 'disabled__'; } ?> />
				<span class="info-msg"  style="display:<?php if($user_createcustomer_status == 1){ echo 'inline;'; } else { echo 'none;'; } ?>" >this webhook is already installed</span>
				<span id="createcustomerhook_success_msg" style="display:none;" class="success-msg">'create customer' webhook is installed/updated.</span>
				<span id="createcustomerhook_error_msg" style="display:none;" class="error-msg">"create customer" webhook cannot be installed/updated.</span>
			</form>		
			
			<span>Install webhook for Update customer</span>
			<form id="updatecustomerwebhook" action="<?php echo SITE_URL ?>install-webook.php" name="updatecustomerwebhook">
			  <input type="hidden" name="shop_url" value="<?php echo $shop ; ?>">
			  <input type="hidden" name="webhookname" value="customer_upadte">
			  <input class="simple-button update_customer_button" type="button" value="click here to install" onclick="update_customer_webhook();" style="background-color:<?php if($user_updatecustomer_status == 1){ echo 'grey;'; } else { echo '#4959bd;'; } ?>" <?php if($user_updatecustomer_status == 1){ echo 'disabled__'; } ?> />
				<span class="info-msg"  style="display:<?php if($user_updatecustomer_status == 1){ echo 'inline;'; } else { echo 'none;'; } ?>" >this webhook is already installed</span>
				<span id="updatecustomerhook_success_msg" style="display:none;" class="success-msg">'update customer' webhook is installed/updated.</span>
				<span id="updatecustomerhook_error_msg" style="display:none;" class="error-msg">"update customer" webhook cannot be installed/updated.</span>
			</form>
	  </div>
  </div> 
 </section>

<script>
	<?php if($getuser['app_uninstall_webhook_status'] == 0){ ?>
		app_uninstall();	
	<?php } ?>
	function submit_credentials(){
		var mkto_endpoint = document.getElementById("mkto_endpoint");
		var mkto_clientid = document.getElementById("mkto_clientid");
		var mkto_secret = document.getElementById("mkto_secret");
		if( mkto_endpoint.value == '' || mkto_clientid.value == '' || mkto_secret.value == '' ){
			$('#server-error-msg,#error-msg,#success-msg').css('display','none');
			if( mkto_endpoint.value == ''){ $('#mkto_endpoint').addClass('error_input'); }
			if( mkto_clientid.value == ''){ $('#mkto_clientid').addClass('error_input'); }
			if( mkto_secret.value == ''){ $('#mkto_secret').addClass('error_input'); }
			$('#error-msg').css('display','block');
			return false;
		} else { 
			$('input').removeClass('error_input');
			$('#server-error-msg,#error-msg,#success-msg').css('display','none');
		}
		$.ajax({
			type: 'post',
			url: $('#config_form').attr('action'),
			data: $('#config_form').serialize(),
			success: function(response){
				$('#server-error-msg,#error-msg,#success-msg').css('display','none');
				if(response=='0'){ $('#error-msg').css('display','block'); }
				if(response=='1'){ $('#success-msg').css('display','block'); }
			},
			error : function(error) {
				$('#server-error-msg,#error-msg,#success-msg').css('display','none');
				$('#server-error-msg').html(error);
				$('#server-error-msg').css('display','block');				
			}
		});
	}

	function create_webhook(){
		$.ajax({
			type: 'post',
			url: $('#installcreateorderwebhook').attr('action'),
			data: $('#installcreateorderwebhook').serialize(),
			success: function(response){
				$('#createhook_success_msg,#createhook_error_msg').css('display','none');
				if(response=='0'){ $('#createhook_error_msg').css('display','block'); }
				if(response=='1'){ $('#createhook_success_msg').css('display','block'); $('.create_order_button').css('background-color','grey'); }
			},
			error : function(error) {
				$('#createhook_success_msg,#createhook_error_msg').css('display','none');
				$('#createhook_error_msg').css('display','block');
				
			}
		});
	}

	function update_webhook(){
		$.ajax({
			type: 'post',
			url: $('#installupdateorderwebhook').attr('action'),
			data: $('#installupdateorderwebhook').serialize(),
			success: function(response){
				$('#updateorderhook_success_msg,#updateorderhook_error_msg').css('display','none');
				if(response=='0'){ $('#updateorderhook_error_msg').css('display','block'); }
				if(response=='1'){ $('#updateorderhook_success_msg').css('display','block'); $('.update_order_button').css('background-color','grey'); }
			},
			error : function(error) {
				$('#updateorderhook_success_msg,#updateorderhook_error_msg').css('display','none');
				$('#updateorderhook_error_msg').css('display','block');
				
			}
		});
	}

	function install_customer_webhook(){
		$.ajax({
			type: 'post',
			url: $('#installcustomerwebhook').attr('action'),
			data: $('#installcustomerwebhook').serialize(),
			success: function(response){
				$('#createcustomerhook_success_msg,#createcustomerhook_error_msg').css('display','none');
				if(response=='0'){ $('#createcustomerhook_error_msg').css('display','block'); }
				if(response=='1'){ $('#createcustomerhook_success_msg').css('display','block'); $('.create_customer_button').css('background-color','grey'); }
			},
			error : function(error) {
				$('#createcustomerhook_success_msg,#createcustomerhook_error_msg').css('display','none');
				$('#createcustomerhook_error_msg').css('display','block');
				
			}
		});
	}

	function app_uninstall(){
		console.log('app_uninstall');
		$.ajax({
			type: 'post',
			url: $('#installcustomerwebhook').attr('action'),
			data: 'shop_url=<?php echo $_REQUEST['shop'];?>&webhookname=app_uninstall',
			success: function(response){
				console.log(response);
			},
			error : function(error) {
				console.log(error);
			}
		});
	}

	function update_customer_webhook(){
		$.ajax({
			type: 'post',
			url: $('#updatecustomerwebhook').attr('action'),
			data: $('#updatecustomerwebhook').serialize(),
			success: function(response){
				$('#updatecustomerhook_success_msg,#updatecustomerhook_error_msg').css('display','none');
				if(response=='0'){ $('#updatecustomerhook_error_msg').css('display','block'); }
				if(response=='1'){ $('#updatecustomerhook_success_msg').css('display','block'); $('.update_customer_button').css('background-color','grey'); }
			},
			error : function(error) {
				$('#updatecustomerhook_success_msg,#updatecustomerhook_error_msg').css('display','none');
				$('#updatecustomerhook_error_msg').css('display','block');
				
			}
		});
	}
</script>
