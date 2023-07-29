<?php 
require __DIR__.'/vendor/autoload.php';
use phpish\shopify;
require __DIR__.'/conf.php'; 
require __DIR__.'/marketo_class.php';
$leads = new marketo();
	$leads->host = $_REQUEST['mkto_endpoint'];
	$leads->clientId = $_REQUEST['mkto_clientid'];
	$leads->clientSecret =  $_REQUEST['mkto_secret'];
	if($leads->getToken()){
			saveuserDetails($_REQUEST);
			echo "1";
	}
	else{
		echo "0";
	}
?>