<?php
	session_start();
	require __DIR__.'/vendor/autoload.php';
	use phpish\shopify;
	require __DIR__.'/conf.php'; 
    require __DIR__.'/marketo_class.php';

    $shopify = shopify\client($_REQUEST['shop'], SHOPIFY_APP_API_KEY, $_SESSION['oauth_token']);

    updateSingleFieldData($_REQUEST['shop'],'charge_id',$_GET['charge_id']);

    $charge = $shopify('GET /admin/api/2019-04/recurring_application_charges/'.$_GET['charge_id'].'.json',array());

    if($charge['status'] == 'accepted') {
        $activate = $shopify('POST /admin/api/2019-04/recurring_application_charges/'.$_GET['charge_id'].'/activate.json',array());
        updateSingleFieldData($_REQUEST['shop'],'payment_status',$activate['status']);
        ?> <script> window.top.location.href = "https://<?php echo $_REQUEST['shop']; ?>/admin/apps"; </script> <?php
    } elseif($charge['status'] == 'declined') {
        updateSingleFieldData($_REQUEST['shop'],'payment_status',$charge['status']);
        ?> <script> window.top.location.href = "https://<?php echo $_REQUEST['shop']; ?>/admin/apps"; </script> <?php
    } elseif($charge['status'] == 'pending') {
        updateSingleFieldData($_REQUEST['shop'],'payment_status',$charge['status']);
        ?> <script> window.top.location.href = "https://<?php echo $_REQUEST['shop']; ?>/admin/apps"; </script> <?php
    } elseif($charge['status'] == 'cancelled') {
        updateSingleFieldData($_REQUEST['shop'],'payment_status',$charge['status']);
        ?> <script> window.top.location.href = "https://<?php echo $_REQUEST['shop']; ?>/admin/apps"; </script> <?php
    }