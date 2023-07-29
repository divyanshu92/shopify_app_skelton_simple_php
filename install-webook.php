<?php
require __DIR__."/vendor/autoload.php";
use phpish\shopify;
require __DIR__."/conf.php";
require __DIR__."/marketo_class.php";
$getuser = getUserDetails($_REQUEST["shop_url"]);
if(isset($getuser["shop_name"])){
$shopname = $getuser["shop_name"];
if($_REQUEST["webhookname"]=="customers"){
$address = SITE_URL."create_customer.php";
$updatewebhookfield = "customer_create_webhook_status";
$webhook = "customers/enable";
}
if($_REQUEST["webhookname"]=="customer_upadte"){
$address =  SITE_URL."create_customer.php";
$updatewebhookfield = "customer_update_webhook_status";
$webhook = "customers/update";
}
if($_REQUEST["webhookname"]=="orders"){
$address =  SITE_URL."create_order.php";
$updatewebhookfield = "order_create_webhook_status";
$webhook = "orders/create";
}
if($_REQUEST["webhookname"]=="order_upadte"){
$address =  SITE_URL."create_order.php";
$updatewebhookfield = "order_update_webhook_status";
$webhook = "orders/updated";
}
if ( $_REQUEST["webhookname"] == "app_uninstall" ) {
    $address =  SITE_URL."app_uninstall.php";
    $updatewebhookfield = "app_uninstall_webhook_status";
    $webhook = "app/uninstalled";
}
$curl = curl_init();
$data = json_encode(array ("webhook" =>array ("topic" => $webhook,"address" => $address,"format" => "json",),));
$token = $getuser["access_token"];
curl_setopt_array($curl, array(
CURLOPT_URL => "https://".$shopname."/admin/api/2020-01/webhooks.json",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => "",
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 90,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => "POST",
CURLOPT_POSTFIELDS => $data,
CURLOPT_HTTPHEADER => array(
"Cache-Control: no-cache",
"Content-Type: application/json",
"X-Shopify-Access-Token: $token "
),
));
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);
if ($err) {
echo "cURL Error #:" . $err;
}else{
updateSingleFieldData($getuser["shop_name"],$updatewebhookfield,"1");
}
}else{
echo "0";
echo "Something went wrong. Please try again in sometime.";die;
}
?>