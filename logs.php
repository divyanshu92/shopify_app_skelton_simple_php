<?php
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
$logs_response = null;
$log_filter_datepicker_min= null;
$log_filter_datepicker_max = null;

if(isset($_POST['log_filter_select']) && isset($_POST['log_filter_datepicker_min']) && $_POST['log_filter_datepicker_min'] != '' && isset($_POST['log_filter_datepicker_max']) && $_POST['log_filter_datepicker_max'] != '' && isset($_REQUEST['shop'])){
	$data['log_filter_select'] = $_POST['log_filter_select'];
	$data['log_filter_datepicker_min'] = $_POST['log_filter_datepicker_min'];
	$data['log_filter_datepicker_max'] = $_POST['log_filter_datepicker_max'];
	$data['shop_name'] = $_REQUEST['shop'];
	$logs_response = getLogsDetails($data);
}

?>

<head>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="https://unpkg.com/@shopify/polaris@4.10.2/styles.min.css"/>
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
</head>
<div class="Polaris-Page">
  <div class="Polaris-Page__Content">
    <form id="log_filter_form" data-shopify-app-submit="unicorn_form_submit" action="" method="post" >
      <div class="Polaris-FormLayout">
        <div class="filter_container main">
          <!-- select field -->
          <div class="Polaris-Select fields_sub">
            <select id='log_filter_select' name="log_filter_select" class="Polaris-Select__Input" aria-invalid="false">
              <option value="order" <?php if(isset($_POST['log_filter_select'])){ if($_POST['log_filter_select']=='order'){ echo 'selected'; }} ?> >Order</option>
              <option value="customer" <?php if(isset($_POST['log_filter_select'])){ if($_POST['log_filter_select']=='customer'){ echo 'selected'; }} ?> >Customer</option>
            </select> 
            <div class="Polaris-Select__Content" aria-hidden="true">
              <span class="Polaris-Select__SelectedOption afterchange">Order</span>
                  <span class="Polaris-Select__Icon"><span class="Polaris-Icon">
                      <svg viewBox="0 0 20 20" class="Polaris-Icon__Svg" focusable="false" aria-hidden="true">
                          <path d="M13 8l-3-3-3 3h6zm-.1 4L10 14.9 7.1 12h5.8z" fill-rule="evenodd"></path>
                  </svg>
                  </span>
              </span>
            </div>
            <div class="Polaris-Select__Backdrop"></div>
          </div>
          <!-- select field -->

          <!-- log_filter_datepicker_min -->
          <div class="Polaris-Connected fields_sub">  
            <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
              <div class="Polaris-TextField Polaris-TextField--hasValue">
                  <input class="Polaris-TextField__Input" aria-labelledby="PolarisTextField2Label" aria-invalid="false" aria-multiline="false" type="text" id='log_filter_datepicker_min' name="log_filter_datepicker_min" min="2000-01-01"  placeholder='start date' >
                <div class="Polaris-TextField__Backdrop"></div>
              </div>
            </div>
          </div>
          <!-- log_filter_datepicker_min -->

          <!-- log_filter_datepicker_max -->
          <div class="Polaris-Connected fields_sub">  
            <div class="Polaris-Connected__Item Polaris-Connected__Item--primary">
              <div class="Polaris-TextField Polaris-TextField--hasValue">
                  <input class="Polaris-TextField__Input" aria-labelledby="PolarisTextField2Label" aria-invalid="false" aria-multiline="false" type="text" id='log_filter_datepicker_max' name="log_filter_datepicker_max" min="2000-01-01"  placeholder='end date' >
                <div class="Polaris-TextField__Backdrop"></div>
              </div>
            </div>
          </div>
          <!-- log_filter_datepicker_max -->

          <!-- submit button -->
            <button type="submit" class="Polaris-Button fields_sub Polaris-Button--primary" value='submit' id='' name="" aria-hidden="true" tabindex="-1">Submit</button>
          <!-- submit button -->
        </div>
        <?php 
          if($logs_response != null && $logs_response != 'no result found'){
            echo '<div class="Polaris-DataTable"><div class="Polaris-DataTable__ScrollContainer"><table class="Polaris-DataTable__Table"><thead><tr><th data-polaris-header-cell="true" class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--header Polaris-DataTable__Cell--numeric" scope="col">id</th><th data-polaris-header-cell="true" class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--header Polaris-DataTable__Cell--numeric" scope="col">type</th><th data-polaris-header-cell="true" class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--header Polaris-DataTable__Cell--numeric" scope="col">name</th><th data-polaris-header-cell="true" class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--header Polaris-DataTable__Cell--numeric" scope="col"></th><th data-polaris-header-cell="true" class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--header Polaris-DataTable__Cell--numeric" scope="col"></th><th data-polaris-header-cell="true" class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--header Polaris-DataTable__Cell--numeric" scope="col">status</th><th data-polaris-header-cell="true" class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--header Polaris-DataTable__Cell--numeric" scope="col">Updated id</th><th data-polaris-header-cell="true" class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--header Polaris-DataTable__Cell--numeric" scope="col">date</th></tr></thead>';
            while ($rowss = $logs_response->fetch_assoc()) {
              echo '<tr class="Polaris-DataTable__TableRow">
              <td class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn" scope="row"><a target="_blank" href="https://'.$data['shop_name'].($_POST['log_filter_select']=='order' ? '/admin/orders/' : '/admin/customers/').$rowss['id'].'">'.$rowss['id'].'</a></td>
              <td class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn" scope="row">'.$rowss['type'].'</td>
              <td class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn" scope="row">'.$rowss['name'].'</td>
              <td class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn" scope="row">'.$rowss['misc_field'].'</td>
              <td class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn" scope="row">'.$rowss['status1'].'</td>
              <td class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn" scope="row">'.$rowss['status2'].'</td>
              <td class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn" scope="row">'.$rowss['updated_id'].'</td>
              <td class="Polaris-DataTable__Cell Polaris-DataTable__Cell--verticalAlignTop Polaris-DataTable__Cell--firstColumn" scope="row">'.$rowss['date'].'</td>
              </tr>'; 
            }
            echo '</table></div><div class="Polaris-DataTable__Footer">Showing '.$logs_response->num_rows.' of '.$logs_response->num_rows.' results</div></div>';
          }else{
            echo $logs_response;
          }
        ?>
      </div>
    </form>
  </div>
</div>

<!-- footer -->
<div class="Polaris-FooterHelp">
    <div class="Polaris-FooterHelp__Content">
      <div class="Polaris-FooterHelp__Icon"><span class="Polaris-Icon Polaris-Icon--colorTeal Polaris-Icon--isColored Polaris-Icon--hasBackdrop"><svg viewBox="0 0 20 20" class="Polaris-Icon__Svg" focusable="false" aria-hidden="true">
            <circle cx="10" cy="10" r="9" fill="currentColor"></circle>
            <path d="M10 0C4.486 0 0 4.486 0 10s4.486 10 10 10 10-4.486 10-10S15.514 0 10 0m0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8m0-4a1 1 0 1 0 0 2 1 1 0 1 0 0-2m0-10C8.346 4 7 5.346 7 7a1 1 0 1 0 2 0 1.001 1.001 0 1 1 1.591.808C9.58 8.548 9 9.616 9 10.737V11a1 1 0 1 0 2 0v-.263c0-.653.484-1.105.773-1.317A3.013 3.013 0 0 0 13 7c0-1.654-1.346-3-3-3"></path>
          </svg></span></div>
      <div class="Polaris-FooterHelp__Text">Check Logs about Order's and Customers</div>
    </div>
</div>
<!-- footer -->

<script>
  $( function() {
    $( "#log_filter_datepicker_min" ).datepicker();
    $( "#log_filter_datepicker_min" ).datepicker("option", "dateFormat", "yy-mm-dd");
    $( "#log_filter_datepicker_max" ).datepicker();
    $( "#log_filter_datepicker_max" ).datepicker("option", "dateFormat", "yy-mm-dd");
  });
  $('#log_filter_select').on('change', function() {$('.afterchange').html(this.value);});  
</script>
<style>
  .main{width:100%;text-align: center; }
  .fields_sub{display: inline-block;width: 150px;}
  .afterchange{text-transform: capitalize;}
</style>