<?php 
	/*
	Plugin Name: Lead to Clio
	Plugin URI: http://www.casewave.com/Lead-to-Clio.php
	Description: Plugin for creating contacts and tasks in Clio from blog
	Author: Trip Grass - CaseWave
	Version: 1.0
	Author URI: http://www.casewave.com/About/TripGrass.php
	*/
	add_action( 'admin_menu', 'lead_to_clio_menu' );
	function lead_to_clio_menu() {
		add_options_page( 'Lead-to-Clio Admin', 'Lead-to-Clio', 'manage_options', 'lead-to-clio-admin', 'my_plugin_options' );
	}
	function my_plugin_options() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		include('lead_to_clio_admin.php');
	}
	add_action( 'wp_enqueue_scripts', 'cw_add_my_stylesheet' );
	function cw_add_my_stylesheet() {
	    wp_register_style( 'cw-style', plugins_url('lead_to_clio.css', __FILE__) );
	    wp_enqueue_style( 'cw-style',9999 );
	}
	function clio_js(){
		wp_register_script( 'custom-script', plugins_url( 'lead_to_clio_javascript.js', __FILE__ ) );
		wp_enqueue_script( 'custom-script',9999 );
	}
	add_action( 'wp_enqueue_scripts', 'clio_js' ); 
	function add_clio_form($content){
		if(is_single()){
			if($_COOKIE['clio_test'] == 1234){
				$added_content = "
			<div id='lead_to_clio'>
				Former Client	
			</div>";
			}
			else{
				if($_GET['cliolead_contact']=='true'){
					$added_content = "			<div id='lead_to_clio_reply'>One of our lawyers will be in touch with you soon!</div>";
				}
				elseif($_GET['cliolead_contact']=='false'){
					$added_content = "			<div id='lead_to_clio_reply'>We weren't able to take care of your request. Please call us.</div>";
				}
				else{
//print_r($_SERVER);
if(!preg_match("/\?/",$_SERVER['REQUEST_URI'])){
$url_append = $_SERVER['REQUEST_URI']."?";
}
else{
$url_append = $_SERVER['REQUEST_URI'];
}
					$redirect_address = $_SERVER['HTTP_HOST'].$url_append;
				$file_pre = plugins_url('lead_to_clio_submit.php', __FILE__);
				$casewave_account_id = get_option('casewave_account_id');
				$added_content = "
			<div id='lead_to_clio'>
				<div id='lead_to_clio_intro'>
					<p>We can help you immediately. 
						<button class='btn' onClick='toggleClio()'>Talk to us today!</button>
					</p>
				</div>
				<div id='lead_to_clio_form' class='no-display'>
					<form action='http://www.casewave.com/include/Submit/submitLead_to_ClioRequest.php' method='post'>
						<input type='hidden' name='casewave_account_id' value='".$casewave_account_id."'>
						<input type='hidden' name='lead_to_clio_redirect' value='".$redirect_address."'>
						<p>Your request goes straight to an associate. We'll get in touch with you today.</p>
						<span>	
							<label>First Name</label><input class='inline' name='first_name'>
						</span>
						<span>	
							<label>Last Name</label><input class='inline' name='last_name'>
						</span>
						<span>	
							<label>Phone</label><input class='inline' name='client_phone'>
						</span>
						<span>	
							<label>Email</label><input class='inline' name='client_email'>
						</span>
						<button class='btn'>Submit</button>
						<div class='clear'></div>
					</form>
				</div>
			</div<!--/lead_to_clio-->";
				}
			}
		}
		return $added_content.$content;
	}
	add_filter('the_content', 'add_clio_form'); 	
?>