<?php
class marketo
{
    public $host; //Marketo host
    public $clientId; //Marketo client id
    public $clientSecret; //Marketo client secret
    public $fields; //one or more fields to return
    public $batchSize; //max 300 default 300
    public $nextPageToken; //token returned from previous call for paging
    public $input;
    public $action = "createOrUpdate";
    public $dedupeBy; //dedupefields, or idField, see describe ca;
    public $name; //name of custom object
    /* create and update lead */
    public function createUpdateLead()
    {
        $url = $this->host . "/rest/v1/leads.json?access_token=" . $this->getToken();
        $ch = curl_init($url);
        $requestBody = $this->bodyBuilder();
        //print_r($requestBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'accept: application/json',
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_getinfo($ch);
        $response = curl_exec($ch);
        return $response;
    }

    /*Create and update order/product custom object for a lead */
    public function createUpdateCustomObject($host, $token)
    {
        $url = $host . "/rest/v1/customobjects/" . $this->name . ".json?access_token=" . $token;
        $ch = curl_init($url);
        $requestBody = $this->bodyBuilder();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'accept: application/json',
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_getinfo($ch);
        $response = curl_exec($ch);
        return $response;
    }

    /*get Marketo token */
    public function getToken()
    {
        $ch = curl_init($this->host . "/identity/oauth/token?grant_type=client_credentials&client_id=" . $this->clientId . "&client_secret=" . $this->clientSecret);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'accept: application/json'
        ));
        $response = json_decode(curl_exec($ch));
        curl_close($ch);
        $token = $response->access_token;
        return $token;
    }

    public static function csvString($fields)
    {
        $csvString = implode(",", $fields);
        return $csvString;
    }

    public function bodyBuilder()
    {
        $body = new stdClass();
        if (isset($this->action))
        {
            $body->action = $this->action;
        }
        if (isset($this->lookupField))
        {
            $body->lookupField = $this->lookupField;
        }

        if (isset($this->dedupeBy))
        {
            $body->dedupeBy = $this->dedupeBy;
        }

        $body->input = $this->input;
        $json = json_encode($body);
        return $json;
    }
}

function dbConnect()
{
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    // Check connection
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }
}

function wh_log($log_msg, $shopname)
{
    $log_filename = $shopname;
    if (!file_exists($log_filename))
    {
        mkdir($log_filename, 0777, true);
    }
    $log_file_data = $log_filename . '/log_' . date('d-M-Y') . '.log';
    file_put_contents($log_file_data, PHP_EOL . '' . PHP_EOL . '' . $log_msg . ': log at:' . date('Y-m-d H:i:s') . ', storname:' . $shopname . "\n", FILE_APPEND);
}

function returnhtml($data, $shopname)
{
    $data_send = '<!DOCTYPE html><html> <head> <link rel="stylesheet" href="https://unpkg.com/@shopify/polaris@4.10.2/styles.min.css"/> <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script> <style>.back_button{width: 20px;max-width: 20px;float: left;padding-right: 3px;}</style> </head> <body> <div class="Polaris-Modal-Dialog__Container undefined" data-polaris-layer="true" data-polaris-overlay="true"> <div class="Polaris-Modal-Dialog__Modal" role="dialog" aria-labelledby="modal-header2" tabindex="-1"> <div class="Polaris-Modal-Header"> <div id="modal-header2" class="Polaris-Modal-Header__Title"> <h2 class="Polaris-DisplayText Polaris-DisplayText--sizeSmall">Notification</h2> </div><button class="Polaris-Modal-CloseButton" aria-label="Close"> <span class="Polaris-Icon Polaris-Icon--colorInkLighter Polaris-Icon--isColored"> <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg" focusable="false" aria-hidden="true"> <path d="M11.414 10l6.293-6.293a.999.999 0 1 0-1.414-1.414L10 8.586 3.707 2.293a.999.999 0 1 0-1.414 1.414L8.586 10l-6.293 6.293a.999.999 0 1 0 1.414 1.414L10 11.414l6.293 6.293a.997.997 0 0 0 1.414 0 .999.999 0 0 0 0-1.414L11.414 10z" fill-rule="evenodd"></path> </svg> </span> </button> </div><div class="Polaris-Modal__BodyWrapper"> <div class="Polaris-Modal__Body Polaris-Scrollable Polaris-Scrollable--vertical" data-polaris-scrollable="true"> <section class="Polaris-Modal-Section"> <div class="Polaris-TextContainer"> <p>' . $data . '</p></div></section> </div></div><div class="Polaris-Modal-Footer"> <div class="Polaris-Modal-Footer__FooterContent"> <div class="Polaris-Stack Polaris-Stack--alignmentCenter"> <div class="Polaris-Stack__Item Polaris-Stack__Item--fill"></div><div class="Polaris-Stack__Item"> <div class="Polaris-ButtonGroup"> <div class="Polaris-ButtonGroup__Item"> <button type="button" class="Polaris-Button Polaris-Button--primary"> <span class="Polaris-Button__Content"> <span class="Polaris-Button__Text"> <svg viewBox="0 0 20 20" class="back_button Polaris-Icon__Svg" focusable="false" aria-hidden="true"> <path d="M17 9H5.414l3.293-3.293a.999.999 0 1 0-1.414-1.414l-5 5a.999.999 0 0 0 0 1.414l5 5a.997.997 0 0 0 1.414 0 .999.999 0 0 0 0-1.414L5.414 11H17a1 1 0 1 0 0-2" fill-rule="evenodd"></path> </svg> Go Back </span> </span> </button> </div></div></div></div></div></div></div></div><script>var redirect_id=location.pathname.split("/").slice(-1)[0]; if(redirect_id=="abandoned_checkouts.php"){}else if (redirect_id=="sync_orders.php"){$(".Polaris-Modal-Dialog__Modal").css({"max-width": "max-content"});}else{console.log("undefined");}function redirect_fucntion(){var redirect_id=location.pathname.split("/").slice(-1)[0]; if(redirect_id=="abandoned_checkouts.php"){var id=getUrlParameter("id"); var idget=(id) ? id : ""; window.top.location.href="https://' . $shopname . '/admin/checkouts/" + idget;}else if (redirect_id=="sync_orders.php"){var id=getUrlParameter("id"); var idget=(id) ? id : ""; window.top.location.href="https://' . $shopname . '/admin/orders/" + idget;}else{console.log("undefined");}}jQuery(".Polaris-Modal-CloseButton").on("click", redirect_fucntion), jQuery(".Polaris-Button--primary").on("click", redirect_fucntion); var getUrlParameter=function(r){var o, t, e=window.location.search.substring(1).split("&"); for (t=0; t < e.length; t++) if ((o=e[t].split("="))[0]===r) return void 0===o[1] || decodeURIComponent(o[1])};function orderfunction(data){window.top.location.href="https://' . $shopname . '/admin/orders/" + data;};function customerfunction(data){window.top.location.href="https://' . $shopname . '/admin/customers/" + data;};</script> </body></html>';
    echo $data_send;
    die;
}

function returnwithouthtml($data, $shopname, $css)
{
    $data_send = '<!DOCTYPE html><html><head><link rel="stylesheet" href="https://unpkg.com/@shopify/polaris@4.10.2/styles.min.css"/><script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script><style>' . $css . '</style></head><body>' . $data . '</body></html>';
    echo $data_send;
    die;
}

function saveuserDetails($data)
{
    $shop_url = $data['shop_url'];
    $mkto_endpoint = $data['mkto_endpoint'];
    $mkto_clientid = $data['mkto_clientid'];
    $mkto_secret = $data['mkto_secret'];
    $hmac = $data['hmac'];
    // Create connection
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    // Check connection
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT *FROM `users_details`WHERE `shop_name` = '$shop_url'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0)
    {
        $sql = "UPDATE `users_details` SET 
					`mkto_rest_endpoint` = '$mkto_endpoint',
					`mkto_client_id` = '$mkto_clientid', 
					`mkto_secret` = '$mkto_secret', 
					`hmac` = '$hmac', 
					`shop_name` = '$shop_url'
					WHERE `shop_name` = '$shop_url'";

        if ($conn->query($sql) === true)
        {
            return "1";
        }
        else
        {
            return "0";
        }
    }
    else
    {
        $sql = "INSERT INTO `users_details` (`id` ,`shop_name` ,`mkto_rest_endpoint` ,`mkto_client_id` ,`mkto_secret`,`hmac`
			)VALUES (NULL , '$shop_url','$mkto_endpoint', '$mkto_clientid', '$mkto_secret','$hmac');";
        if ($conn->query($sql) === true)
        {
            // return "New record created successfully";
            return "1";
        }
        else
        {
            return "0";
            // return "Error: " . $sql . "<br>" . $conn->error;
            
        }
    }

}

function saveLogsDetails($data)
{
    $log_id = $data['log_id'];
    $shop_name = $data['shop_name'];
    $log_type = $data['log_type'];
    $log_name = $data['log_name'];
    $log_misc_field = $data['log_misc_field'];
    $log_status1 = $data['log_status1'];
    $log_status2 = $data['log_status2'];
    $log_updated_id = $data['log_updated_id'];

    // Create connection
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    // Check connection
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }
    //echo "cONNECTION ESTABLISHED";
    $sql = "SELECT * FROM `logs` WHERE `id` = '$log_id'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0)
    {
        $sql = "UPDATE `logs` SET 
                `shop_name` = '$shop_name',
                `type` = '$log_type',
                `name` = '$log_name', 
                `misc_field` = '$log_misc_field', 
                `status1` = '$log_status1', 
                `status2` = '$log_status2',
                `updated_id` = '$log_updated_id'
                WHERE `id` = '$log_id'";

        if ($conn->query($sql) === true)
        {
            echo "1";
        }
        else
        {
            echo "0";
        }

    }
    else
    {
        $sql = "INSERT INTO `logs` (`id` , `shop_name`,`type` ,`name` ,`misc_field` ,`status1`,`status2`,`updated_id`
			)VALUES ('$log_id' ,'$shop_name', '$log_type','$log_name', '$log_misc_field', '$log_status1','$log_status2','$log_updated_id');";
        if ($conn->query($sql) === true)
        {
            echo "1";
        }
        else
        {
            echo "0";
        }
    }

}

function deletefromdb($shop_url)
{
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `users_details`WHERE `shop_name` = '$shop_url'";
    $result = $conn->query($sql);
    $result->num_rows;
    if ($result->num_rows > 0)
    {
        $sql = "DELETE FROM `users_details` WHERE `shop_name` = '$shop_url'";
        if ($conn->query($sql) === true)
        {
            echo "Entry Deleted from database: " . $shop_url;

        }
        else
        {
            echo "Something went wrong...";
        }
    }
}

function saveAceessToken($shop_url, $token, $code)
{
    // Create connection
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    // Check connection
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }
    //echo "cONNECTION ESTABLISHED";
    $sql = "SELECT * FROM `users_details`WHERE `shop_name` = '$shop_url'";
    $result = $conn->query($sql);

    $result->num_rows;
    if ($result->num_rows > 0)
    {
        $sql = "UPDATE `users_details` SET 
					`shop_name` = '$shop_url',
					`access_token` = '$token',
					`status` = '1',
					`is_installed` = '1'
					WHERE `shop_name` = '$shop_url'";

        if ($conn->query($sql) === true)
        {
            //echo "Record updated successfully";
            echo "";
        }
        else
        {
            echo "";
            //echo "Error updating record: " . $conn->error;
            
        }
    }
    else
    {
        $d1 = new Datetime("now");
        $d2 = new Datetime("now");
        $d2->add(new DateInterval('P1Y'));
        $currentdate = $d1->format('U');
        $expiredate = $d2->format('U');
        $sql = "INSERT INTO `users_details` (`shop_name` ,`access_token`,`code`,`app_installed_date`,`app_expire_date`,`status`,`is_installed`) VALUES ('$shop_url', '$token','$code','$currentdate','$expiredate','1','1');";
        if ($conn->query($sql) === true)
        {
            echo "";
        }
        else
        {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

function getabundantcartdata($shop_url, $token, $ordersdatas_raw, $i){
    // $shop_url shopify url
    // $token access token
    //$ordersdatas_raw empty array
    // $i number variable starting value = 0
    $array = $ordersdatas = array();
    $curl = curl_init();
    curl_setopt_array($curl, array(CURLOPT_URL => $shop_url,CURLOPT_RETURNTRANSFER => true,CURLOPT_ENCODING => "",CURLOPT_MAXREDIRS => 10,CURLOPT_TIMEOUT => 0,CURLOPT_HEADER => 1,CURLOPT_FOLLOWLOCATION => true,CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,CURLOPT_CUSTOMREQUEST => "GET",CURLOPT_HTTPHEADER => array("Content-Type: application/json","X-Shopify-Access-Token: ".$token.""),));    
    $response = curl_exec($curl);    
    $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    $headers = $lines = explode("\n",substr($response, 0, $header_size));
    foreach ($headers as $key => $header) {$array = explode(": ",$header);$headers[$array[0]] = htmlspecialchars($array[1]);unset($headers[$key]);}
    $body = json_decode(substr($response, $header_size),true);
    $array = $body['checkouts'];
    $ordersdatas_raw[$i] = $array;
    curl_close($curl);
    $links_raws = explode(', ',$headers['Link']);
    foreach ($links_raws as $key => $links_raw) {
        $array = explode("; ",$links_raw);
        $links_raws[trim(str_replace('&quot;', '', str_replace('rel=', '', $array[1])))] = str_replace('&gt;', '', str_replace('&lt;', '', $array[0]));
        unset($links_raws[$key]);
    }
    $i++;
    if($links_raws['next']){
        return getabundantcartdata($links_raws['next'], $token, $ordersdatas_raw, $i);
    }else{
        return $ordersdatas_raw;
    }
}

function getUserDetails($data)
{
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    // Check connection
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT *FROM `users_details`WHERE `shop_name` = '$data'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1)
    {
        $row = $result->fetch_assoc();
        return $row;
    }
    else
    {
        return "notfound";
    }
}

function savelastabanedlastentry($shop_url, $last_synced_id)
{
    // Create connection
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    // Check connection
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }
    //echo "cONNECTION ESTABLISHED";
    $sql = "SELECT * FROM `users_details`WHERE `shop_name` = '$shop_url'";
    $result = $conn->query($sql);

    $result->num_rows;
    if ($result->num_rows > 0)
    {
        $sql = "UPDATE `users_details` SET 
                        `abandoned_checkouts_last_entry` = '$last_synced_id'
                        WHERE `shop_name` = '$shop_url'";

        if ($conn->query($sql) === true)
        {
            //echo "Record updated successfully";
            echo "";
        }
        else
        {
            echo "";
            //echo "Error updating record: " . $conn->error;
            
        }
    }
}

function getlastabanedlastentry($data)
{
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    // Check connection
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT *FROM `users_details`WHERE `shop_name` = '$data'";
    $result = $conn->query($sql);
    if ($result->num_rows == 1)
    {
        $row = $result->fetch_assoc();
        return $row['abandoned_checkouts_last_entry'];
    }
    else
    {
        return "";
    }
}

function getLogsDetails($data)
{
    $log_filter_select = $data['log_filter_select'];
    $log_filter_datepicker_min = $data['log_filter_datepicker_min'];
    $log_filter_datepicker_max = $data['log_filter_datepicker_max'];
    $log_filter_shop_name = $data['shop_name'];
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    // Check connection
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT * FROM `logs` WHERE `date` > '$log_filter_datepicker_min' AND `date` < '$log_filter_datepicker_max' AND `type` = '$log_filter_select' AND `shop_name` = '$log_filter_shop_name'";
    //die($sql);
    $result = $conn->query($sql);
    if ($result->num_rows > 0)
    {
        return $result;
    }
    else
    {
        return 'no result found';
    }
}

function updateSingleFieldData($shop_url, $field, $value)
{
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `users_details`WHERE `shop_name` = '$shop_url'";
    $result = $conn->query($sql);
    if ($result->num_rows > 0)
    {
        $sql = "UPDATE `users_details` SET `$field` = '$value'	WHERE `shop_name` = '$shop_url'";
        if ($conn->query($sql) === true)
        {
            echo '1';
        }
        else
        {
            echo '0';
        }
    }
    else
    {
        echo "Sorry something went wrong";
    }
}

function assignTags($url, $data, $shopifytoken)
{
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_HTTPHEADER => array(
            "Cache-Control: no-cache",
            "Content-Type: application/json",
            "X-Shopify-Access-Token: $shopifytoken"
        ) ,
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err)
    {
        return "cURL Error #:" . $err;
    }
    else
    {
        return $response;
    }
}

function checkexpiredate($shop, $charge)
{
    $getuser = getUserDetails($shop);
    if ($getuser['charge_id'] != '')
    {
        if ($charge['status'] != 'active')
        {
            require __DIR__ . '/chargeActivate.php';
        }
    }
    else
    {
        require __DIR__ . '/chargeActivate.php';
    }
    $d1 = new Datetime("now");
    $currentdatetime = $d1->format('U');
    if (($getuser['app_installed_date'] > $currentdatetime) || ($getuser['app_expire_date'] < $currentdatetime) || ($getuser['status'] == 0))
    {
        // if($getuser['status'] == 1){updateSingleFieldData($shop,'status',0);}
        $data = '<div class="Polaris-Card"><div class="Polaris-Card__Header"><h2 class="Polaris-Heading">Marketo Shopify Connector</h2></div><div class="Polaris-Card__Section"><div id="myFieldIDError" class="Polaris-InlineError"><div class="Polaris-InlineError__Icon"><span class="Polaris-Icon"><svg viewBox="0 0 20 20" class="Polaris-Icon__Svg" focusable="false" aria-hidden="true"><path d="M10 18a8 8 0 1 1 0-16 8 8 0 0 1 0 16zm-1-8h2V6H9v4zm0 4h2v-2H9v2z" fill-rule="evenodd"></path></svg></span></div>Your Connector is Expired. Please renew this first then you can use it further.</div></div></div>';
        $css = ".Polaris-Card {border-radius: 3px;width: 600px;margin: 10px auto;}";
        returnwithouthtml($data, $_REQUEST['shop'], $css);
    }
}

function admindetails()
{
    $conn = new mysqli(SERVER_NAME, USERNAME, PASSWORD, DATABASE);
    if ($conn->connect_error)
    {
        die("Connection failed: " . $conn->connect_error);
    }
    $sql = "SELECT * FROM `users_details`";
    $result = $conn->query($sql);
    for ($set = array();$row = $result->fetch_assoc();$set[array_shift($row) ] = $row);
    return $set;
}

function status_check($shop, $status)
{
    $value = ($status == 'true' ? 1 : 0);
    updateSingleFieldData($shop, 'status', $value);
}

