<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// Class for Front End Work
class lead_to_clio_Front extends lead_to_clio {

	/**
	 * Constructor function
	 */
	public function __construct ( $parent ) {
		// get parent settings
		foreach( $this as $key=>$val ){
			$val = $parent->{$key}; 
			$this->{$key} = $val;
		}
		$this->parent = $parent;
		add_filter('the_content', array( $this, 'add_clio_form'));

	}
	function add_clio_form($content){
		$added_content ="";
		global $post;
		$meta = get_post_meta( $post->ID, 'lead_to_clio_add_to_page' );
		$add = false;
		if ( count($meta)>0 && $meta[0] == 'on' ){
			$add = true;
		}
		if( $this->parent->add_to_pages == 'on' && ( count($meta)>0  && $meta[0] != 'off' )){
			$add = true;
		}
		if( count($meta) < 1 && $this->parent->add_to_pages == 'on' ){
			$add = true;
		}
		if(is_single() || is_page() && $add ){
			if( isset($_COOKIE['clio_test']) && $_COOKIE['clio_test'] == 1234){
				$added_content = "
			<div id='lead_to_clio'>
				Former Client	
			</div>";
			}
			else{
				if( isset($_GET['cliolead_contact']) && $_GET['cliolead_contact']=='true'){
					$added_content = "			<div id='lead_to_clio_reply'>One of our lawyers will be in touch with you soon!</div>";
				}
				elseif( isset( $_GET['cliolead_contact']) && $_GET['cliolead_contact']=='false'){
					$added_content = "			<div id='lead_to_clio_reply'>We weren't able to take care of your request. Please call us.</div>";
				}
				else{
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
		$loc = get_option('lead_to_clio_form_location' ,'option');
		if( !$loc || $loc == 'above'){
			return $added_content.$content;
		}
		else{
			return $content.$added_content;
		}
	}
}
?>