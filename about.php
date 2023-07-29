<?php 
	session_start();
	require __DIR__.'/vendor/autoload.php';
	use phpish\shopify;
	require __DIR__.'/conf.php'; 
	require __DIR__.'/marketo_class.php'; 
	$getuser = getUserDetails($_REQUEST['shop']);
	$shopifytoken = $getuser['access_token'];
	$shopify = shopify\client($_REQUEST['shop'], SHOPIFY_APP_API_KEY, $shopifytoken);
	if(!isset($getuser['access_token'])){
		echo '<script>window.top.location.href="https://'.$_REQUEST['shop'].'/admin/apps/"</script>';
	}
	$charge = $shopify('GET /admin/api/2019-04/recurring_application_charges/'.$getuser['charge_id'].'.json',array());
	checkexpiredate($_REQUEST['shop'], $charge);
	$css = ".Polaris-Card {border-radius: 3px;width: 600px;margin: 10px auto;}";
	$data = '<div class="Polaris-Card"><div class="Polaris-Card__Header"><h2 class="Polaris-Heading">Marketo Shopify Connector</h2></div><div class="Polaris-Card__Section"><p>version 0.07.0</p><p>Copyright &copy; 2019-'.date('Y').'</p></div></div>';
	returnwithouthtml($data, $_REQUEST['shop'], $css);
?>