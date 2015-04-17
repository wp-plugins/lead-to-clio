<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class lead_to_clio {

	/**
	 * The single instance of lead_to_clio.
	 * @var 	object
	 * @access  private
	 * @since 	1.0.0
	 */
	private static $_instance = null;

	/**
	 * Settings class object
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;

	/**
	 * jquery vars to output to html
	 * @var 	object
	 * @access  
	 * @since 	1.0.0
	 */
	protected static $jquery_vars = "";
	
	/**
	 * The version number.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version;

	/**
	 * The token.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token;

	/**
	 * The main plugin file.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * Suffix for Javascripts.
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor function.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct ( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token = 'lead_to_clio';

		// Load plugin environment variables
		$this->file = $file;
		$this->dir = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		//	Get options for plugin
		$this->settings = $this->settings_fields();

		$this->add_to_pages = get_option('lead_to_clio_default_every_page');

		// Load appropriate functions
		if ( is_admin() ) {
			$settings = lead_to_clio_Settings::instance( $this );
			$this->admin = new lead_to_clio_Admin( $this );
		}
		else{
			self::$jquery_vars = $this->get_option_values();
			$this->front = new lead_to_clio_Front( $this );
			add_action ('wp_footer' , array(__CLASS__,'output_jquery_vars') , 999 );
			add_action( 'wp_enqueue_scripts', array( $this, 'ltc_add_my_stylesheet') );
		}

	//	$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Load frontend JS & CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Load admin JS & CSS
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

		// Handle localisation
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		add_action( 'post_updated', array( $this, 'save_page_meta' ), 10, 1 ); 

	} // End __construct ()
	
	function ltc_add_my_stylesheet() {
	    wp_register_style( 'ltc-style', plugins_url('../assets/css/frontend.css', __FILE__) );
	    wp_enqueue_style( 'ltc-style',9999 );
		wp_register_script( 'ltc-script', plugins_url( '../assets/js/frontend.js', __FILE__ ) );
		wp_enqueue_script( 'ltc-script',9999 );
	}

	// for individual post pages save whether to show the form or not as post meta
	public function save_page_meta( $post_ID ){
		if( isset($_POST['add_to_page'])){
			$meta_value = $_POST['add_to_page'];
			update_post_meta( $post_ID, 'lead_to_clio_add_to_page', $meta_value );
		}
		else{
			update_post_meta( $post_ID, 'lead_to_clio_add_to_page', 'off' );	
		}	
	}

	/**
	 * Settings Options
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields () {

		$settings['general'] = array(
			'title'					=> __( 'General', 'lead_to_clio' ),
			'description'			=> __( '', 'lead_to_clio' ),
			'fields'				=> array(
				array(
					'id' 			=> 'authorize_clio',
					'label'			=> __( '', 'lead_to_clio' ),
					'description'	=> __( '', 'lead_to_clio' ),
					'type'			=> 'button',
					'default'		=> 'on'
				),
				array(
					'id' 			=> 'default_every_page',
					'label'			=> __( 'Contact Form Placement', 'lead_to_clio' ),
					'description'	=> __( 'Add a Contact Form to Every Page by Default', 'lead_to_clio' ),
					'type'			=> 'checkbox',
					'default'		=> 'on'
				),
				array(
					'id' 			=> 'form_location',
					'label'			=> __( 'Location of Form', 'lead_to_clio' ),
					'description'	=> __( '', 'lead_to_clio' ),
					'type'			=> 'radio',
					'options'		=> array( 'above' => 'Above Content', 'below' => 'Below Content' ),
					'default'		=> 'above'
				),
				array(
					'id' 			=> 'intro_message',
					'label'			=> __( 'Form Intro Message' , 'lead_to_clio' ),
					'description'	=> __( 'An intro message.', 'lead_to_clio' ),
					'type'			=> 'textarea',
					'placeholder'	=> 'We can help you immediately.'
				),
				array(
					'id' 			=> 'form_message',
					'label'			=> __( 'Form Message' , 'lead_to_clio' ),
					'description'	=> __( 'This is the message shown with your contact form.', 'lead_to_clio' ),
					'type'			=> 'textarea',
					'placeholder'	=> 'Your request goes straight to an associate. We\'ll get in touch with you today.'
				),
				array(
					'id' 			=> 'success_message',
					'label'			=> __( 'Success Message' , 'lead_to_clio' ),
					'description'	=> __( 'Message shown if contact info is successfully submitted', 'lead_to_clio' ),
					'type'			=> 'textarea',
					'placeholder'	=> 'One of our lawyers will be in touch with you soon!'
				),
				array(
					'id' 			=> 'fail_message',
					'label'			=> __( 'Failure Message' , 'lead_to_clio' ),
					'description'	=> __( 'Message shown if contact info isn\'t submitted.', 'lead_to_clio' ),
					'type'			=> 'textarea',
					'placeholder'	=> 'We weren\'t able to take care of your request. Please call us.'
				)
			)
		);
		
		$settings = apply_filters( $this->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Wrapper function to register a new post type
	 * @param  string $post_type   Post type name
	 * @param  string $plural      Post type item plural name
	 * @param  string $single      Post type item single name
	 * @param  string $description Description of post type
	 * @return object              Post type class object
	 */
	public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '' ) {

		if ( ! $post_type || ! $plural || ! $single ) return;

		$post_type = new lead_to_clio_Post_Type( $post_type, $plural, $single, $description );

		return $post_type;
	}

	/**
	 * Wrapper function to register a new taxonomy
	 * @param  string $taxonomy   Taxonomy name
	 * @param  string $plural     Taxonomy single name
	 * @param  string $single     Taxonomy plural name
	 * @param  array  $post_types Post types to which this taxonomy applies
	 * @return object             Taxonomy class object
	 */
	public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array() ) {

		if ( ! $taxonomy || ! $plural || ! $single ) return;

		$taxonomy = new lead_to_clio_Taxonomy( $taxonomy, $plural, $single, $post_types );

		return $taxonomy;
	}

	/**
	 * Load frontend CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return void
	 */
	public function enqueue_styles () {
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function enqueue_scripts () {
	
	} // End enqueue_scripts ()

	/**
	 * Load admin CSS.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_styles ( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function admin_enqueue_scripts ( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
		wp_enqueue_script( $this->_token . '-admin' );
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_localisation () {
		load_plugin_textdomain( 'lead_to_clio', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain () {
	    $domain = 'lead_to_clio';

	    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	    load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
	    load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main lead_to_clio Instance
	 *
	 * Ensures only one instance of lead_to_clio is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see lead_to_clio()
	 * @return Main lead_to_clio instance
	 */
	public static function instance ( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}
		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install () {
		$this->_log_version_number();
//		$this->_log_defaults();
	} // End install ()

	/**
	 * Log the plugin version number.
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	private function _log_version_number () {
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()

	/**
	 * Log the defaults
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */	 
	private function _log_defaults () {
		$token = $this->_token;
		// iterate over settings and save the default as initial option
		if ( is_array( $this->settings ) ) {
			foreach ( $this->settings as $section => $data ) {
				foreach ( $data['fields'] as $key=>$field ) {
					$option_name = $field['id'];
					$default = $field['default'];
					if( $default ){
						$name = $token."_".$option_name;
						update_option( $name , $default );
					}					
				}
			}
		}
	}

	/**
	 * Check if lead_to_clio.js is in footer
	 * @return void
	 */
	private function lead_to_clio_in_footer () {
		if( $this->get_option_value('lead_to_clio_in_footer') == 'on' ){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * Return option value
	 * @return void
	 */
	private function get_option_value ( $id ) {
		if ( is_array( $this->settings ) ) {
			foreach ( $this->settings as $section => $data ) {
				foreach ( $data['fields'] as $key=>$field ) {
					$option_name = $field['id'];
					if( $option_name == $id ){	
						return $field['value'];
					}
				}
			}
		}				
	}
	
	/**
	 * Adds values to all options
	 * @return void
	 */
	private function get_option_values () {
		if ( is_array( $this->settings ) ) {
			foreach ( $this->settings as $section => $data ) {
				foreach ( $data['fields'] as $key=>$field ) {
					$option_name = $this->_token."_".$field['id'];
					$option = get_option( $option_name );
					$this->settings[$section]['fields'][$key]['value'] = $option;
					$jquery_vars[$field['id']] = $option; 
				}
			}			
			return $jquery_vars;
		}
	}

	public static function output_jquery_vars(){
		echo "<script type='text/javascript'>
		/*
		 * lead_to_clio plugin javascript; output from plugins/lead-to-clio/includes/class.lead-to-clio.php
		 */
		 
		var lead_to_clio_variables = " . json_encode( self::$jquery_vars ) . "; 
		jQuery(document).ready(function($) {

		});
		</script>";
	}

}