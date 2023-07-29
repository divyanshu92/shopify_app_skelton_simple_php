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
$app_uninstall = json_decode($info,true);

updateSingleFieldData($app_uninstall['domain'],'status','0');
updateSingleFieldData($app_uninstall['domain'],'access_token','');
updateSingleFieldData($app_uninstall['domain'],'mkto_rest_endpoint','');
updateSingleFieldData($app_uninstall['domain'],'mkto_client_id ','');
updateSingleFieldData($app_uninstall['domain'],'mkto_secret','');
updateSingleFieldData($app_uninstall['domain'],'code','');
updateSingleFieldData($app_uninstall['domain'],'customer_create_webhook_status',0);	
updateSingleFieldData($app_uninstall['domain'],'order_create_webhook_status',0);	
updateSingleFieldData($app_uninstall['domain'],'customer_update_webhook_status',0);	
updateSingleFieldData($app_uninstall['domain'],'order_update_webhook_status',0);	
updateSingleFieldData($app_uninstall['domain'],'app_uninstall_webhook_status',0);
updateSingleFieldData($app_uninstall['domain'],'is_installed','0');
updateSingleFieldData($app_uninstall['domain'],'payment_status','');
updateSingleFieldData($app_uninstall['domain'],'charge_id','');