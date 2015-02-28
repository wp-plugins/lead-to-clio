<?php 
	$referer = $_SERVER['HTTP_HOST'];
	$script = $_SERVER['SCRIPT_NAME'];
	$redirect = "http://".$referer.$script;
	$clio_source = $_SERVER['SERVER_NAME'];
	$admin_page = $_SERVER['SCRIPT_NAME'];

	if( isset( $_GET['clio_auth'] ) ){
		$clio_auth = $_GET['clio_auth'];
		update_option('clio_source', $clio_source);
		update_option('casewave_account_id', $_GET['casewave_account_id']);
		echo "<div class='updated'><p><strong>";
		_e('Options saved.' );
		echo"</strong></p></div>";
	}
	else {
		$clio_auth = false;
		$casewave_account_id = get_option('casewave_account_id');
	}
	if( isset( $_GET['casewave_account_id'] ) ){
		$casewave_account_id = $_GET['casewave_account_id'];
	}
	if($casewave_account_id){
		$httpGet = 'http://www.casewave.com/include/Submit/submitCasewaveCheck.php?casewave_account_id='.$casewave_account_id;
		$casewaveCheckArray = wp_remote_get($httpGet);
		if($casewaveCheckArray['body'] != 'is_valid'){
			unset($clio_auth);
		}
		else{
			$clio_auth = true;
		}
	}
	if( isset($_POST['clear_clio']) && $_POST['clear_clio']=='Y'){
		delete_option('clio_source');
	}
?>
		<div class="wrap">
			<h2>Lead-to-Clio Options</h2>
<?php 
	$state_array = array(
		"redirect"=> $redirect,
		"group_id"=>$casewave_account_id,
		"clio_source"=>$clio_source,
		"admin_page"=>$admin_page
	);
	$state_array = json_encode($state_array);
	$url = "https://app.goclio.com/oauth/authorize";
	$params = array(
		"response_type" => "code",
		"client_id" => "HwhWVxxMgPHDR1E6T2GGHh24GskDCN15OhnqzOWP",
		"redirect_uri" => "http://www.casewave.com/include/Submit/submitClioClientAuth.php",
		"state" => $state_array
  	);
	$request_to = $url . '?' . http_build_query($params);
	if(!$clio_auth || ( isset( $_POST['clear_clio'] ) && $_POST['clear_clio']=='Y' )){
	?>
				<form name="lead_to_clio_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
				<input type="hidden" name="lead_to_clio_hidden" value="Y">
<?php
		_e("<a href='".$request_to."'>Authorize CaseWave's Lead-to-Clio to Add Contacts to Clio</a>");
	}
	else{
	?>
				<form name="clear_clio" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
				<input type="hidden" name="clear_clio" value="Y">
<?php
		_e("You Have authorized CaseWave's Lead-to-Clio to Add Contacts to Clio");
		echo "<br><button>Delete Authorization</button>";	
	}
?>
				<!--<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Update Options', 'oscimp_trdom' ) ?>" />
				</p>-->
			</form>
		</div>