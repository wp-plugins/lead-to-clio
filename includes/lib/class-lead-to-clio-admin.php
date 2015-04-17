<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class lead_to_clio_Admin extends lead_to_clio{

	/**
	 * Constructor function
	 */
	public function __construct ( $parent ) {
		$this->parent = $parent;
		if( isset( $_GET['clio_auth'] ) ){
			$this->clio_auth = $_GET['clio_auth'];
			$clio_source = $_SERVER['SERVER_NAME'];
			update_option('clio_source', $clio_source);
			$this->casewave_account_id = $_GET['casewave_account_id'];
			update_option('casewave_account_id', $_GET['casewave_account_id']);
		}
		else {
			$this->clio_auth = false;
			$this->casewave_account_id = get_option('casewave_account_id');
		}
		if($this->casewave_account_id){
			$httpGet = 'http://www.casewave.com/include/Submit/submitCasewaveCheck.php?casewave_account_id='.$this->casewave_account_id;
			$casewaveCheckArray = wp_remote_get($httpGet);
			if( !is_wp_error( $casewaveCheckArray) ){
				if($casewaveCheckArray['body'] != 'is_valid'){
					unset($this->clio_auth);
				}
				else{
					$this->clio_auth = true;
				}
			}
		}
		if( isset($_GET['delete_auth'] )){
			delete_option('clio_source');
			$this->clio_auth = false;
		}	
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 1 );
		add_action( 'add_meta_boxes', array($this, 'ltc_add_meta_box' ));
		if( 'on' == get_option('add_to_pages')){
			add_action( 'add_meta_boxes', array($this, 'ltc_add_meta_box' ));
		}
	}

	/**
	 * Generate HTML for displaying fields
	 * @param  array   $field Field data
	 * @param  boolean $echo  Whether to echo the field HTML or return it
	 * @return void
	 */
	public function display_field ( $data = array(), $post = false, $echo = true ) {

		// Get field info
		if ( isset( $data['field'] ) ) {
			$field = $data['field'];
		} else {
			$field = $data;
		}

		// Check for prefix on option name
		$option_name = '';
		if ( isset( $data['prefix'] ) ) {
			$option_name = $data['prefix'];
		}

		// Get saved data
		$data = '';
		if ( $post ) {

			// Get saved field data
			$option_name .= $field['id'];
			$option = get_post_meta( $post->ID, $field['id'], true );

			// Get data to display in field
			if ( isset( $option ) ) {
				$data = $option;
			}

		} else {

			// Get saved option
			$option_name .= $field['id'];
			$option = get_option( $option_name );

			// Get data to display in field
			if ( isset( $option ) ) {
				$data = $option;
			}

		}

		// Show default data if no option saved and default is supplied
		if ( $data === false && isset( $field['default'] ) ) {
			$data = $field['default'];
		} elseif ( $data === false ) {
			$data = '';
		}

		$html = '';

		switch( $field['type'] ) {
			
			case 'header':
				$html .= esc_attr( $field['content'] );
			
			case 'text':
			case 'url':
			case 'email':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '" />' . "\n";
			break;

			case 'password':
			case 'number':
			case 'hidden':
				$min = '';
				if ( isset( $field['min'] ) ) {
					$min = ' min="' . esc_attr( $field['min'] ) . '"';
				}

				$max = '';
				if ( isset( $field['max'] ) ) {
					$max = ' max="' . esc_attr( $field['max'] ) . '"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $data ) . '"' . $min . '' . $max . '/>' . "\n";
			break;

			case 'text_secret':
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="" />' . "\n";
			break;

			case 'textarea':
				$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . $data . '</textarea><br/>'. "\n";
			break;

			case 'checkbox':
				$checked = '';
				if ( $data && 'on' == $data ) {
					$checked = 'checked="checked"';
				}
				$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" ' . $checked . '/>' . "\n";
			break;

			case 'checkbox_multi':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( in_array( $k, $data ) ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="checkbox_multi"><input type="checkbox" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
			break;

			case 'radio':
				foreach ( $field['options'] as $k => $v ) {
					$checked = false;
					if ( $k == $data ) {
						$checked = true;
					}
					$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $checked, true, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . $v . '</label> ';
				}
			break;

			case 'select':
				$html .= '<select name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( $k == $data ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
			break;

			case 'select_multi':
				$html .= '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple">';
				foreach ( $field['options'] as $k => $v ) {
					$selected = false;
					if ( in_array( $k, $data ) ) {
						$selected = true;
					}
					$html .= '<option ' . selected( $selected, true, false ) . ' value="' . esc_attr( $k ) . '">' . $v . '</option>';
				}
				$html .= '</select> ';
			break;

			case 'image':
				$image_thumb = '';
				if ( $data ) {
					$image_thumb = wp_get_attachment_thumb_url( $data );
				}
				$html .= '<img id="' . $option_name . '_preview" class="image_preview" src="' . $image_thumb . '" /><br/>' . "\n";
				$html .= '<input id="' . $option_name . '_button" type="button" data-uploader_title="' . __( 'Upload an image' , 'lead_to_clio' ) . '" data-uploader_button_text="' . __( 'Use image' , 'pushstate' ) . '" class="image_upload_button button" value="'. __( 'Upload new image' , 'pushstate' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '_delete" type="button" class="image_delete_button button" value="'. __( 'Remove image' , 'pushstate' ) . '" />' . "\n";
				$html .= '<input id="' . $option_name . '" class="image_data_field" type="hidden" name="' . $option_name . '" value="' . $data . '"/><br/>' . "\n";
			break;

			case 'button':
				$referer = $_SERVER['HTTP_HOST'];
				$script = $_SERVER['SCRIPT_NAME'];
				$redirect = "http://".$referer.$script;
				$clio_source = $_SERVER['SERVER_NAME'];
				$admin_page = $_SERVER['SCRIPT_NAME'];

				$state_array = array(
					"redirect"=> $redirect,
					"group_id"=>$this->casewave_account_id,
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
				if( !$this->clio_auth ||  isset( $_POST['delete_auth'] )){
					echo "<label><a href='".$request_to."'>Authorize CaseWave's Lead-to-Clio to Add Contacts to Clio</a></label>";
				}else{
					echo "<label>You Have authorized CaseWave's Lead-to-Clio to Add Contacts to Clio</label>
					<br><a class='button' href='/wp-admin/options-general.php?page=lead-to-clio-admin&delete_auth=true'>Delete Authorization</a>";
			    }

			break;
		}

		switch( $field['type'] ) {

			case 'checkbox_multi':
			case 'radio':
			case 'select_multi':
				$html .= '<br/><span class="description">' . $field['description'] . '</span>';
			break;

			default:
				if ( ! $post ) {
					$html .= '<label for="' . esc_attr( $field['id'] ) . '">' . "\n";
				}

				$html .= '<span class="description">' . $field['description'] . '</span>' . "\n";

				if ( ! $post ) {
					$html .= '</label>' . "\n";
				}
			break;
		}

		if ( ! $echo ) {
			return $html;
		}

		echo $html;

	}

	/**
	 * Validate form field
	 * @param  string $data Submitted value
	 * @param  string $type Type of field to validate
	 * @return string       Validated value
	 */
	public function validate_field ( $data = '', $type = 'text' ) {

		switch( $type ) {
			case 'text': $data = esc_attr( $data ); break;
			case 'url': $data = esc_url( $data ); break;
			case 'email': $data = is_email( $data ); break;
		}

		return $data;
	}

	/**
	 * Add meta box to the dashboard
	 * @param string $id            Unique ID for metabox
	 * @param string $title         Display title of metabox
	 * @param array  $post_types    Post types to which this metabox applies
	 * @param string $context       Context in which to display this metabox ('advanced' or 'side')
	 * @param string $priority      Priority of this metabox ('default', 'low' or 'high')
	 * @param array  $callback_args Any axtra arguments that will be passed to the display function for this metabox
	 * @return void
	 */
	public function add_meta_box ( $id = '', $title = '', $post_types = array(), $context = 'advanced', $priority = 'default', $callback_args = null ) {

		// Get post type(s)
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		// Generate each metabox
		foreach ( $post_types as $post_type ) {
			add_meta_box( $id, $title, array( $this, 'meta_box_content' ), $post_type, $context, $priority, $callback_args );
		}
	}

	/**
	 * Display metabox content
	 * @param  object $post Post object
	 * @param  array  $args Arguments unique to this metabox
	 * @return void
	 */
	public function meta_box_content ( $post, $args ) {

		$fields = apply_filters( $post->post_type . '_custom_fields', array(), $post->post_type );

		if ( ! is_array( $fields ) || 0 == count( $fields ) ) return;

		echo '<div class="custom-field-panel">' . "\n";

		foreach ( $fields as $field ) {

			if ( ! isset( $field['metabox'] ) ) continue;

			if ( ! is_array( $field['metabox'] ) ) {
				$field['metabox'] = array( $field['metabox'] );
			}

			if ( in_array( $args['id'], $field['metabox'] ) ) {
				$this->display_meta_box_field( $field, $post );
			}

		}

		echo '</div>' . "\n";

	}

	/**
	 * Dispay field in metabox
	 * @param  array  $field Field data
	 * @param  object $post  Post object
	 * @return void
	 */
	public function display_meta_box_field ( $field = array(), $post ) {

		if ( ! is_array( $field ) || 0 == count( $field ) ) return;

		$field = '<p class="form-field"><label for="' . $field['id'] . '">' . $field['label'] . '</label>' . $this->display_field( $field, $post, false ) . '</p>' . "\n";

		echo $field;
	}

	/**
	 * Save metabox fields
	 * @param  integer $post_id Post ID
	 * @return void
	 */
	public function save_meta_boxes ( $post_id = 0 ) {
		if ( ! $post_id ) return;
				update_option('add_to_pages', 'on');

		$post_type = get_post_type( $post_id );

		$fields = apply_filters( $post_type . '_custom_fields', array(), $post_type );
		if ( ! is_array( $fields ) || 0 == count( $fields ) ) return;

		foreach ( $fields as $field ) {
			if ( isset( $_REQUEST[ $field['id'] ] ) ) {
				update_post_meta( $post_id, $field['id'], $this->validate_field( $_REQUEST[ $field['id'] ], $field['type'] ) );
			} else {
				update_post_meta( $post_id, $field['id'], '' );
			}
		}
	}
	function ltc_add_meta_box() {
		$screens = array( 'post', 'page' );
		foreach ( $screens as $screen ) {	
			add_meta_box( 'lead-to-clio' ,	__( 'Lead-to-Clio', 'myplugin_textdomain' ), array($this,'ltc_add_to_page'),	$screen	);
		}
	}
	function ltc_add_to_page( $post ) {
	
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'myplugin_meta_box', 'myplugin_meta_box_nonce' );
	
		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		$meta = get_post_meta( $post->ID, 'lead_to_clio_add_to_page' , true);

		$value = "";
		if( $meta == 'on' ){
			$value = "checked=checked";
		}		
		if(	$meta == 'off' ){
			$value = ""; 			
		}	
		if($this->parent->add_to_pages){
			if( !$meta ){
				$value = "checked=checked";
			}		
			if(	$meta == 'off' ){
				$value = ""; 			
			}	
		}
		echo '<label for="add_to_page">';
		_e( 'Add the Lead-to-Clio form', 'myplugin_textdomain' );
		echo '</label> ';
		echo '<input type="checkbox" id="add_to_page" name="add_to_page" ' . $value  . '" />';
	}

}