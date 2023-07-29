<?php
	use phpish\shopify;
    $shopify = shopify\client($_REQUEST['shop'], SHOPIFY_APP_API_KEY, $_SESSION['oauth_token']);
    if($getuser['payment_status'] != 'active') {
        $return = $shopify(
            'POST /admin/api/2019-04/recurring_application_charges.json',
            array(
                "recurring_application_charge" => array(
                    "name" => "Shopify Marketo Connector",
                    "price" => 5,
                    "return_url" => "https://api.grazitti.com/marketo/clients/rohitgoel/shopify-app-dev/payment.php?shop=".$_REQUEST['shop'],
                    "trial_days" => 1,
                    "test" => true
                )
            )
        );
        updateSingleFieldData($_REQUEST['shop'],'payment_status',$return['status']);
        ?>
        <script>
            window.top.location.href = "<?php echo $return['confirmation_url']; ?>";
        </script>
        <?php
    }