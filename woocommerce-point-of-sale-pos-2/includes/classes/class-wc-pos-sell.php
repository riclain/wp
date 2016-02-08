<?php
/**
 * Responsible for the POS front-end
 *
 * @author    Actuality Extensions
 * @package   WoocommercePointOfSale/Classes/Sell
 * @category  Class
 * @since     3.0
 */


if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


class WC_Pos_Sell{

	/**
	 * @var WC_Pos_Registers The single instance of the class
	 * @since 1.9
	 */
	protected static $_instance = null;
	
	public $data           = null;
	public $id             = null;
	public $active_plugins = array();

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		$this->get_active_plugins();

		add_filter('wc_pos_enqueue_scripts', array($this, 'enqueue_scripts_payment_gateway'), 1, 1 );
		add_filter('wc_pos_inline_js', array($this, 'inline_js_payment_gateway'), 1, 1 );

		add_filter('woocommerce_get_return_url', array($this, 'wc_pos_get_return_url'), 100, 2 );

		add_filter('show_admin_bar', array($this, 'show_admin_bar') );
		add_filter('wc_pos_enqueue_styles', array($this, 'wc_pos_register_layout') );
		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
		
        add_action( 'woocommerce_api_server_before_serve', array( $this, 'wc_api_init') );
        add_action( 'woocommerce_api_loaded', array( $this, 'wc_api_loaded') );
        add_action( 'woocommerce_api_classes', array( $this, 'wc_api_classes') );

        add_action( 'woocommerce_available_payment_gateways', array($this, 'wc_pos_available_payment_gateways'), 100, 1 );
        add_action( 'option_woocommerce_stripe_settings', array($this, 'woocommerce_stripe_settings'), 100, 1 );
        add_action( 'init', array($this, 'wc_pos_checkout_gateways') );
        add_action( 'wp_login', array( $this, 'set_last_login') );
	}

	public function get_active_plugins()
	{
		if (in_array('woocommerce-gateway-stripe/woocommerce-gateway-stripe.php', apply_filters('active_plugins', get_option('active_plugins')))){
			$this->active_plugins[] = 'stripe';
		}
	}

	/**
	 * Display POS page or login screen
	 */
	public function template_redirect() {
	    // bail if not pos
	    if( !is_pos() ) 
	        return;

	    // set up $current_user for use in includes
	    global $current_user;
	    get_currentuserinfo();
	    $pos_ssl = get_option('woocommerce_pos_force_ssl_checkout');
	    if ( ! is_ssl() && $pos_ssl == 'yes') {
	            
	            if ( 0 === strpos( $_SERVER['REQUEST_URI'], 'http' ) ) {
	                wp_safe_redirect( preg_replace( '|^http://|', 'https://', $_SERVER['REQUEST_URI'] ) );
	                exit;
	            } else {
	                wp_safe_redirect( 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
	                exit;
	            }        
	        }
	    // check page and credentials
	    if ( is_user_logged_in() ) {
	    	global  $wp;
	    	$id     = $_GET['reg']    = $wp->query_vars['reg'];
	    	$outlet = $_GET['outlet'] = $wp->query_vars['outlet'];
	    	
			setcookie ("wc_point_of_sale_register", $id ,time()+3600*24*120, '/');

	    	$data = $this->getRegisted($_GET['reg']);
	    	if ( get_post_status ( $data['order_id'] ) != 'publish' || get_post_type( $data['order_id'] ) != 'pos_temp_register_or') {
		      $data['order_id'] = 0;
		    }
		    $data['order_id'] = (int)( $data['order_id'] != 0 ? $data['order_id'] : WC_POS()->register()->crate_order_id($data['ID']) );

	        include_once( WC_POS()->plugin_path() . '/includes/views/html-admin-pos.php' );	        
	        exit;
	    } else {
	        auth_redirect();
	    }   
	}
    function set_last_login($login) {
       $user = get_user_by('login', $login);
       update_user_meta( $user->ID, 'last_login', current_time('mysql') );
    }
    

    public function is_pos_referer()
    {
        $referer = wp_get_referer();
        $pos_url = get_home_url()."/point-of-sale/";

        if( strpos( $referer, $pos_url) !== false){
            return true;
        }
        return false;
    }

    /**
     * Instantiate the Product Class when making api requests
     * @param  object $api_server  WC_API_Server Object      
     */
    public function wc_api_init( $api_server ) {
        if( $this->is_pos_referer() === true || is_pos() ){
        // check both GET & POST requests
        //$params = array_merge($api_server->params['GET'], $api_server->params['POST']);
        //if( isset($params['action']) && $params['action'] ==  'wc_pos_json_api' ) {
            include_once( 'api/class-wc-pos-api.php' );
            $this->product = new WC_Pos_API();
        }
    }

    /**
	 * Include required files for REST API request
	 *
	 * @since 3.0.0
	 */
    public function wc_api_loaded()
    {
    	include_once( 'api/class-wc-pos-api-orders.php' );
    }

    /**
	 * Register available API resources
	 *
	 * @since 3.0.0
	 * @param WC_API_Server $server the REST server
	 */
	public function wc_api_classes( $api_classes ) {

		$api_classes[] = 'WC_API_POS_Orders';
		return $api_classes;
		
	}
    

    public function show_admin_bar($value)
    {
    	if( is_pos() ){
	    	$admin_bar = get_option('woocommerce_pos_register_layout_admin_bar', 'no');
	    	if ($admin_bar == 'yes'){
	    		$value = false;
	    	}else{
	    		$value = true;
	    	}
    	}
    	return $value;
    }

    public function wc_pos_register_layout($styles)
    {
    	$layout = get_option('woocommerce_pos_register_layout', 'two');
		if($layout == 'one'){
			$styles['wc-pos-layout-one'] = 	WC_POS()->plugin_url() . '/assets/css/register/register_layout_one.css';
		}
    	return $styles;
    }

    public function wc_pos_available_payment_gateways($_available_gateways)
    {
    	 
    	if( ( defined('DOING_AJAX') && DOING_AJAX && isset($_GET['action']) && $_GET['action'] == 'wc_pos_checkout' ) || is_pos() || $this->is_pos_api() ){
	    	$_available_gateways = array();
	    	$payment_gateways = WC()->payment_gateways->payment_gateways;
	    	$enabled_gateways = get_option( 'pos_enabled_gateways', array() );

			foreach ( $payment_gateways as $gateway ) {
				if ( in_array($gateway->id, $enabled_gateways) ) {
					$_available_gateways[ $gateway->id ] = $gateway;
				}
			}
		}

		return $_available_gateways;
    }

    public function wc_pos_get_return_url( $return_url, $order )
    {

    	//woocommerce_get_return_url
    	 
    	if( is_pos() || $this->is_pos_api() ){
	    	
	    	if ( $order && $reg_id = get_post_meta( $order->id, 'wc_pos_id_register', true) ) {
	    		$data         = WC_POS()->register()->get_data($reg_id);
	    		if( $data ){
		    		$data         = $data[0];
		    		$outlets_name = WC_POS()->outlet()->get_data_names();
		    		$register     = $data['slug'];
		    		$outlet       = sanitize_title($outlets_name[$data['outlet']]);

					if(class_exists('SitePress')) {
						$settings = get_option( 'icl_sitepress_settings' );
						if($settings['urls']['directory_for_default_language'] == 1){
							$return_url = get_home_url() . '/' . ICL_LANGUAGE_CODE . "/point-of-sale/$outlet/$register";
						}else{
							$return_url = get_home_url() . "/point-of-sale/$outlet/$register";
						}
					}else{
						$return_url = get_home_url()."/point-of-sale/$outlet/$register";
					}

					if ( is_ssl() || get_option('woocommerce_pos_force_ssl_checkout') == 'yes' ) {
						$return_url = str_replace( 'http:', 'https:', $return_url );
					}

			  	}

			}

		}
		return $return_url;
    }
    private function is_pos_api()
    {
    	global $wp;   
		$result = false;

    	if( isset($wp->query_vars) && isset($wp->query_vars['wc-api-route'] ) && strpos($wp->query_vars['wc-api-route'], 'pos_orders') !== false ){
    		$result = true;
    	}

		return $result;
    }

    public function woocommerce_stripe_settings( $value )
    {
    	if( is_pos() ) {
	    	$value['saved_cards'] = 'no';
	    	$value['stripe_checkout'] = 'no';
	    }
    	return $value;
    }

    public function wc_pos_checkout_gateways(){        
        if( ( defined('DOING_AJAX') && DOING_AJAX && isset($_GET['action']) && $_GET['action'] == 'wc_pos_checkout' ) || is_pos() ){
        	$enabled_gateways = get_option( 'pos_enabled_gateways', array() );
	        $pos_exist_gateways = get_option( 'pos_exist_gateways', array() );

	        foreach ($pos_exist_gateways as $gateway_id) {
	            if(!in_array($gateway_id, $enabled_gateways) ){
	                add_filter( 'option_woocommerce_'.$gateway_id.'_settings', array($this, 'disable_gateway') );
	            }else{
	                if($gateway_id == 'cod')
	                    add_filter( 'pre_option_woocommerce_'.$gateway_id.'_settings', array($this, 'enable_gateway_cod') );
	                else
	                    add_filter( 'option_woocommerce_'.$gateway_id.'_settings', array($this, 'enable_gateway') );
	            }

	        }
        }
    }

    public function disable_gateway($val){
        $val['enabled'] = 'no';
        return $val;
    }
    public function enable_gateway($val){
    	
        $val['enabled'] = 'yes';
        if(isset($val['enable_for_virtual']))
            $val['enable_for_virtual'] = 'yes';

        if(isset($val['enable_for_methods']))
            $val['enable_for_methods'] = array();
        
        return $val;
    }
    public function enable_gateway_cod(){

    	$val = array();
        $val['enabled'] = 'yes';
        $val['enable_for_virtual'] = 'yes';
        $val['enable_for_methods'] = array();
        
        return $val;
    }

    public function getRegisted($id)
    {
    	$data = WC_POS()->register()->get_data_by_slug($id);
	    $data = $data[0];
	    foreach ($data['detail'] as $i => $val) {
	        $data[$i] = $val;
	    }
	    foreach ($data['settings'] as $i => $val) {
	        $data[$i] = $val;
	    }
	    $this->data = $data;
	    $this->id = $data['ID'];
	    return $this->data;
    }

    public function validate()
    {
    	?>
    	<script type="text/javascript">var pos_ready_to_start = false; </script>
    	<?php
    	if(!$this->data){ ?>

		<div class="md-modal md-openmodal" id="modal-1">
			<div class="md-content">
				<div>
		        	<center>		        	
					<p tabindex="0"><?php _e('This register does not exist.', 'wc_point_of_sale'); ?></p>
			        <p>
		        		<a class="button" href="<?php echo admin_url('admin.php?page=wc_pos_registers'); ?>" tabindex="0">
			        		<?php  _e( 'Add Register', 'wc_point_of_sale' ); ?>
		        		</a>
			        </p>
		        	</center>
				</div>
			</div>
		</div>
		<?php
		return;
		}
    	$error_string = '';

		$detail_fields    = WC_POS()->register()->get_register_detail_fields();
		$detail_data      = $this->data['detail'];
		if(isset($detail_fields['grid_template']['options'][$detail_data['grid_template']]))
		    $grid_template    = $detail_fields['grid_template']['options'][$detail_data['grid_template']];
		else
		    $grid_template = '';
		$receipt_template = $detail_fields['receipt_template']['options'][$detail_data['receipt_template']];



		if(!$grid_template || empty($grid_template))
		  $error_string   .= '<p>No product grid assigned.</p>';
		if(!$receipt_template)
		  $error_string   .= '<b>Receipt Template </b> is required<br>';

		$outlets_name = WC_POS()->outlet()->get_data_names();

		if(!$outlets_name[$this->data['outlet']]){
		  $error_string   .= '<b>Outlet </b> is required<br>';
		}

		if(!empty($error_string)){ ?>

		<div class="md-modal md-openmodal" id="modal-1">
			<div class="md-content">
				<h3>Error</h3>
				<div>
		        	<center>
					<p tabindex="0"><?php echo $error_string; ?></p>
			        <p>
			        		<a class="button" href="<?php echo admin_url('admin.php?page=wc_pos_registers&action=edit&id='.$this->data['ID']); ?>" tabindex="0">
				        		<?php  _e( 'Edit Register', 'wc_point_of_sale' ); ?>
			        		</a>
			        </p>
		        	</center>
				</div>
			</div>
		</div>
		<?php
		return;
		}
		
		if(!WC_POS()->wc_api_is_active){
		?>
		<div class="md-modal md-openmodal" id="modal-1">
			<div class="md-content">
				<h3>Error</h3>
				<div>
		        	<center>
					<p class="currently-editing wp-tab-first" tabindex="0"><?php _e('The WooCommerce API is disabled on this site.', 'wc_point_of_sale'); ?></p>
			        <p>
			            <a class="button" href="<?php echo admin_url('admin.php?page=wc-settings&tab=api'); ?>"><?php _e( 'Enable the REST API', 'wc_point_of_sale' ); ?></a>
			            <a class="button" href="<?php echo admin_url('admin.php?page=wc_pos_registers' ); ?>"><?php _e( 'All Registers', 'wc_point_of_sale' ); ?></a>
			        </p>
		        	</center>
				</div>
			</div>
		</div>
		<?php
		return;
		}
		if( !pos_check_user_can_open_register( $this->id )){
		?>
		<div class="md-modal md-openmodal" id="modal-1">
			<div class="md-content">
				<h3>Error</h3>
				<div>
		        	<center>
					<p class="currently-editing wp-tab-first" tabindex="0"><?php _e( 'You do not have permission to access this register.', 'wc_point_of_sale' ); ?></p>
			        <p>
			            <a class="button" href="<?php echo admin_url('admin.php?page=wc_pos_registers' ); ?>"><?php _e( 'All Registers', 'wc_point_of_sale' ); ?></a>
			        </p>
		        	</center>
				</div>
			</div>
		</div>
		<?php
		return;
		}
		if( $user_id = pos_check_register_lock( $this->id ) ){
			$user = get_userdata( $user_id );
		?>
		<div class="md-modal md-openmodal lodal-locked-register" id="modal-1">
			<div class="md-content">
				<div>
				    <div class="icons-header">
						<div class="sa-icon sa-warning pulseWarning">
					      <span class="dashicons dashicons-lock pulseWarningInsColor"></span>
					    </div>
						<div class="post-locked-avatar sa-icon sa-warning pulseWarning"><?php echo get_avatar( $user->ID, 80 ); ?></div>				    	
				    </div>
				    <center>
				    	<h2><?php _e('Locked', 'wc_point_of_sale'); ?></h2>
						<p tabindex="0">
							<?php printf( __('This register currently has a user (%s) logged on.', 'wc_point_of_sale'), $user->display_name ); ?>
						</p>
			            <a class="button" href="<?php echo admin_url('admin.php?page=wc_pos_registers' ); ?>"><?php _e( 'All Registers', 'wc_point_of_sale' ); ?></a>
				    </center>
				</div>
			</div>
		</div>
		<?php
		return;
		}
		else{
		    if( defined('WP_DEBUG') && WP_DEBUG === true ) { ?>
			<div class="md-modal md-openmodal" id="modal-1">
				<div class="md-content">
					<h3><?php _e( 'Loading...', 'wc_point_of_sale' ); ?></h3>
					<div>
			        	<div id="process_loding">
							
						</div>
						<center>		
							<p><span class="spinner" style="display: block; float: none; visibility: visible;"></span></p>
							<p><button class="md-close button hidden"><?php _e('Close', 'wc_point_of_sale'); ?></button></p>
						</center>
					</div>
				</div>
			</div>
			<?php } else { ?>
			<div class="md-modal md-openmodal pos_logo" id="modal-1">
				<div id="pos_logo"></div>
				<h3><?php _e( 'Loading...', 'wc_point_of_sale' ); ?></h3>
			</div>
			<div class="md-overlay-logo"></div>
			<?php }?>
			<script type="text/javascript">pos_ready_to_start = true; </script>
			<?php
		    if (!pos_check_register_is_open($this->id)) {
		        pos_set_register_lock($this->id);
		    }
		}
    }
    /**
	* Output the footer scripts
	*/
	protected function header() {
		$assets_path          = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';
		// required scripts
		$styles = array(
		  'wc-admin'                      => $assets_path . 'css/admin.css',
		  'wc-layout'                     => $assets_path . 'css/woocommerce-layout.css',
		  'bootstrap-switch'              => WC_POS()->plugin_url() . '/assets/plugins/bootstrap-switch/bootstrap-switch.min.css',
		  'bootstrap-ladda-themeless'     => WC_POS()->plugin_url() . '/assets/plugins/ladda-bootstrap/ladda-themeless.css',
		  'wc-pos-offline'                => WC_POS()->plugin_url() . '/assets/plugins/offline/offline-theme-chrome-indicator.css',
		  'offline-language-english'      => WC_POS()->plugin_url() . '/assets/plugins/offline/offline-language-english.css',
		  'wc-pos-keypad'                 => WC_POS()->plugin_url() . '/assets/plugins/jquery_keypad/jquery.keypad.css',
		  'wc-pos-toastr'                 => WC_POS()->plugin_url() . '/assets/plugins/toastr/toastr.css',
		  'wc-pos-fonts'                  => WC_POS()->plugin_url() . '/assets/css/fonts.css',
		  'wc-pos-modal'                  => WC_POS()->plugin_url() . '/assets/css/register/modal-component.css',
		  'wc-pos-admin'                  => WC_POS()->plugin_url() . '/assets/css/admin.css',
		  'wc-pos-main'                   => WC_POS()->plugin_url() . '/assets/css/register/main.css',
	    );
	    $styles = apply_filters( 'wc_pos_enqueue_styles', $styles );

	    foreach( $styles as $key => $style ) {
			//$manifest .= str_replace(' ', '%20', $script) . "\n";
	        echo "\n".'<link media="all" type="text/css" href="'.$style.'" id="'.$key.'" rel="stylesheet">';
	    }

	    $custom_styles = get_option('pos_custom_styles', '');
	    echo "\n".'<style type="text/css">';
	    	echo $custom_styles;
	    echo '</style>';
	}

	/**
	* Output the footer scripts
	*/
	protected function footer() {
		//
		$build = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'build' : 'min';
		$assets_path          = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';
		$wc_frontend_script_path = $assets_path . 'js/frontend/';
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '.min' : '';

		// required scripts
		$scripts = array(
		  'wc-address-i18n'                  => $wc_frontend_script_path . 'address-i18n' . $suffix . '.js',
		  'wc-jquery-blockui'                => $assets_path . 'js/jquery-blockui/jquery.blockUI' . $suffix . '.js',
		  'wc-jquery-tipTip'                 => $assets_path . 'js/jquery-tiptip/jquery.tipTip' . $suffix . '.js',
		  'wc-select2'                       => $assets_path . 'js/select2/select2' . $suffix . '.js',
		  'wc-enhanced-select'               => $assets_path . 'js/admin/wc-enhanced-select' . $suffix . '.js',
		  'wc-accounting'                    => $assets_path . 'js/admin/accounting' . $suffix . '.js',
		  'wc-round'                         => $assets_path . 'js/admin/round' . $suffix . '.js',
		  /********/
		  'wc-pos-php-js'                    => WC_POS()->plugin_url() . '/assets/js/register/plugins/php.js',
		  'wc-pos-md5'                       => WC_POS()->plugin_url() . '/assets/js/register/plugins/md5-min.js',
		  'wc-pos-ydn'                       => WC_POS()->plugin_url() . '/assets/js/register/plugins/ydn.db-dev.js',
		  'wc-pos-event-manager'             => WC_POS()->plugin_url() . '/assets/js/register/plugins/event-manager.js',
		  /********/
		  'wc-pos-bootstrap'                 => WC_POS()->plugin_url() . '/assets/plugins/bootstrap.min.js',
		  'wc-pos-bootstrap-switch'          => WC_POS()->plugin_url() . '/assets/plugins/bootstrap-switch/bootstrap-switch.min.js',
		  'wc-pos-bootstrap-ladda-main'      => WC_POS()->plugin_url() . '/assets/plugins/ladda-bootstrap/bootstrap.min.js',
		  'wc-pos-bootstrap-ladda'           => WC_POS()->plugin_url() . '/assets/plugins/ladda-bootstrap/ladda.min.js',
		  'wc-pos-timeago'                   => WC_POS()->plugin_url() . '/assets/plugins/jquery.timeago.js',
		  'wc-pos-toastr'                    => WC_POS()->plugin_url() . '/assets/plugins/toastr/toastr.js',
		  'wc-pos-sound'                     => WC_POS()->plugin_url() . '/assets/plugins/ion.sound/ion.sound.min.js',
		  'wc-pos-jquery_keypad_plugin'      => WC_POS()->plugin_url() . '/assets/plugins/jquery_keypad/jquery.plugin.min.js',
		  'wc-pos-jquery_keypad'             => WC_POS()->plugin_url() . '/assets/plugins/jquery_keypad/jquery.keypad.min.js',
		  'wc-pos-offline'                   => WC_POS()->plugin_url() . '/assets/plugins/offline/offline.min.js',
		  'wc-pos-barcodelistener'           => WC_POS()->plugin_url() . '/assets/plugins/anysearch.js',
		  'wc-pos-cardswipe'                 => WC_POS()->plugin_url() . '/assets/plugins/jquery.cardswipe.js',
		  
		  /********/
		  'wc-pos-keypad'                    => WC_POS()->plugin_url() . '/assets/js/register/keypad.js',
		  'wc-pos-category_cycle'            => WC_POS()->plugin_url() . '/assets/js/register/category_cycle.js',
		  'wc-pos-modal-classie'             => WC_POS()->plugin_url() . '/assets/js/register/modal/classie.js',
		  'wc-pos-modal-modalEffects'        => WC_POS()->plugin_url() . '/assets/js/register/modal/modalEffects.js',
		  'wc-pos-modal-cssParser'           => WC_POS()->plugin_url() . '/assets/js/register/modal/cssParser.js',
		  /********/
		  'wc-pos-handlebars'                => WC_POS()->plugin_url() . '/assets/js/register/handlebars/handlebars.min.js',
		  'wc-pos-handlebars-helpers'        => WC_POS()->plugin_url() . '/assets/js/register/handlebars/handlebars.helpers.js',
		  /********/
		  
		  /********/
		  'wc-pos-check-clone-windows'       => WC_POS()->plugin_url() . '/assets/js/register/check-clone-windows.js',
		  'wc-pos-country-select'            => WC_POS()->plugin_url() . '/assets/js/register/country-select.js',
		  'wc-pos-functions'                 => WC_POS()->plugin_url() . '/assets/js/register/functions.js',
		  'wc-pos-coupon'                    => WC_POS()->plugin_url() . '/assets/js/register/coupon.js',
		  'wc-pos-customer'                  => WC_POS()->plugin_url() . '/assets/js/register/customer.js',
		  'wc-pos-tax'                       => WC_POS()->plugin_url() . '/assets/js/register/tax.js',
		  'wc-pos-cart'                      => WC_POS()->plugin_url() . '/assets/js/register/cart.js',
		  'wc-pos-addons'                    => WC_POS()->plugin_url() . '/assets/js/register/addons.js',
		  'wc-pos-init'                      => WC_POS()->plugin_url() . '/assets/js/register/app.js',
		  /********/
		);

		$scripts = apply_filters( 'wc_pos_enqueue_scripts', $scripts );

	    // inline start app with params
	    $grid       = $this->getGrid();
	    $params     = self::getJsParams();
	    $cart_param = self::getJsCartParams();
	    $pos_i18n   = self::getJSi18n();
	    $templates  = self::getJSTemplates();
	    $wc         = self::getJsWCParams();

	    $default_customer_id = absint($this->data['default_customer']);
	    $default_customer    = self::getJsDefaultCastomer($default_customer_id);
	    $custom_product      = self::getJs_Custom_Product();
	    $wc_select_params    = self::getJsWCSelectParams();

	    $inline = array(
	      'pos_register_id'                 => '<script type="text/javascript" class="wc_pos_register_id" >  var wc_pos_register_id = '. $this->id .'; </script>',
	      'pos_default_customer'            => '<script type="text/javascript" class="pos_default_customer"> var pos_default_customer = '. $default_customer .'; </script>',
	      'pos_custom_product'              => '<script type="text/javascript" class="pos_custom_product">   var pos_custom_product = '. $custom_product .'; </script>',
	      'pos_params'                      => '<script type="text/javascript" class="wc_pos_params" >       var wc_pos_params = '. $params .'; </script>',
	      'pos_grid'                        => '<script type="text/javascript" class="pos_grid" >            var pos_grid = '. $grid .'; </script>',
	      'pos_cart'                        => '<script type="text/javascript" class="pos_cart" >            var pos_cart = '. $cart_param .'; </script>',
	      'pos_wc'                          => '<script type="text/javascript" class="pos_wc" >              var pos_wc = '. $wc .'; </script>',
	      'wc_country_select_params'        => '<script type="text/javascript" class="wc_country_select" >   var wc_country_select_params = '. $wc_select_params .'; </script>',
	    );	    

	    foreach ($pos_i18n as $key => $array) {
	    	$array = json_encode( $array );
	    	$inline[$key] = '<script type="text/javascript" class="pos_i18n_'.$key.'" >var ' . $key . ' = '. $array .'; </script>';
	    }

	    $inline_js = apply_filters( 'wc_pos_inline_js', $inline );


	    // output inline js
	    foreach( $inline_js as $js ) {
	      echo "\n".$js;
	    }
	    
	    foreach( $templates as $template ) {
	      include_once WC_POS()->plugin_views_path() . '/' . $template;
	    }

	    $manifest = "CACHE MANIFEST\n";
		foreach( $scripts as $script ) {
			$manifest .= str_replace(' ', '%20', $script) . "\n";
	        echo "\n".'<script src="'. $script . '"></script>';
	    }

	    global $wp_scripts;
	    
	    foreach( $wp_scripts->registered as $script ){
	    	if( strpos($script->src, 'http:') === 0 || strpos($script->src, 'https:') === 0){
	    		$manifest .= str_replace(' ', '%20', $script->src) . "\n";	
	    	}else{
	    		$manifest .= site_url() . str_replace(' ', '%20', $script->src) . "\n";
	    	}	    	
	    }
		global $wp_styles;
	    foreach( $wp_styles->registered as $style ){
	    	if( strpos($style->src, 'http:') === 0 || strpos($style->src, 'https:') === 0){
	    		$manifest .= str_replace(' ', '%20', $style->src) . "\n";	
	    	}else{
	    		$manifest .= site_url() . str_replace(' ', '%20', $style->src) . "\n";
	    	}	    	
	    }
	    #$file = WC_POS()->plugin_path() . '/assets/cache.manifest';
	    #file_put_contents($file, $manifest);    
	    
	}

	public static function getJSi18n()
	{
		$i18n = array(
	      'pos_i18n'        => include_once WC_POS()->plugin_path() . '/i18n/app.php',
	      'coupon_i18n' => include_once WC_POS()->plugin_path() . '/i18n/coupon.php',
	    );
	    return apply_filters( 'wc_pos_i18n_js', $i18n );
	}
	public static function getJSTemplates()
	{
		$templates = array(
	      'product-item' => 'templates/cart.php',
	      'modal' => 'templates/modal.php',
	    );
	    return apply_filters( 'wc_pos_templates_js', $templates );
	}
	public static function getJsParams()
	{

		$complete_order_status  = get_option( 'woocommerce_pos_end_of_sale_order_status', 'processing' );
		$save_order_status      = get_option( 'wc_pos_save_order_status', 'pending' );
		if(empty($complete_order_status)){
        	$complete_order_status = 'processing';
		}
		
		if(empty($save_order_status)){
        	$save_order_status = 'pending';
		}else if(strpos($save_order_status, 'wc-') === 0){
			$save_order_status = substr($save_order_status, 3);
		}

		$load_order_status = array();
		$statuses_arr = get_option('wc_pos_load_order_status');
		if( !$statuses_arr || empty($statuses_arr) ){
			$statuses_arr = array('wc-pending');
		}
		foreach ($statuses_arr as $status) {
			$load_order_status[] = substr($status, 3);
		}
		$load_order_status = implode(',', $load_order_status);


		$params = apply_filters('wc_pos_params', array(
	      'wp_debug'                => defined('WP_DEBUG') ? WP_DEBUG : false,
	      'avatar'                  => get_avatar_url( 0, array( 'size' => 30 ) ),
	      'sound_path'              => WC_POS()->plugin_sound_url(),
	      'ajax_url'                => WC()->ajax_url(),
	      'edit_link'               => get_admin_url(get_current_blog_id(), '/post.php?post={{post_id}}&action=edit'),
	      'admin_url'               => admin_url(),
	      'ajax_loader_url'         => apply_filters('woocommerce_ajax_loader_url', WC()->plugin_url() . '/assets/images/ajax-loader@2x.gif'),
	      'def_img'                 => wc_placeholder_img_src(),
	      'offline_url'             => WC_POS()->plugin_url() . '/assets/plugins/offline/blank.png',
	      'void_register_nonce'     => wp_create_nonce("void_register"),
	      
	      'load_order_status'            => $load_order_status,
	      'load_web_order'               => ( get_option('wc_pos_load_web_order', 'no') == 'yes' ? true : false),
	      'load_customer'                => ( get_option('wc_pos_load_customer_after_selecting', 'no') == 'yes' ? true : false),
	      'disable_sound_notifications'  => ( get_option('wc_pos_disable_sound_notifications', 'no') == 'yes' ? true : false),
	      'mon_decimal_point'            => get_option('woocommerce_price_decimal_sep'),
	      'default_country'              => get_option('wc_pos_default_country'),
	      'currency_format_num_decimals' => absint(get_option('woocommerce_price_num_decimals')),
	      'currency_format_symbol'       => get_woocommerce_currency_symbol(),
	      'currency_format_decimal_sep'  => esc_attr(stripslashes(get_option('woocommerce_price_decimal_sep'))),
	      'currency_format_thousand_sep' => esc_attr(stripslashes(get_option('woocommerce_price_thousand_sep'))),

	      'pos_calc_taxes'           => wc_pos_tax_enabled(),
	      'currency_format'          => esc_attr(str_replace(array('%1$s', '%2$s'), array('%s', '%v'), get_woocommerce_price_format())), // For accounting JS

	      'ready_to_scan'            => get_option('woocommerce_pos_register_ready_to_scan'),
	      'cc_scanning'              => get_option('woocommerce_pos_register_cc_scanning'),
	      'instant_quantity'         => get_option('woocommerce_pos_register_instant_quantity'),
	      'instant_quantity_keypad'  => get_option('woocommerce_pos_register_instant_quantity_keypad'),	      
	      'default_customer_addr'    => get_option( 'woocommerce_pos_tax_default_customer_address', 'outlet' ),  
	      'complete_order_status'    => $complete_order_status,
	      'save_order_status'        => $save_order_status,

	      'barcode_url'        => plugins_url( 'includes/classes/barcode/image.php?filetype=PNG&dpi=72&scale=2&rotation=0&font_family=Arial.ttf&&thickness=30&start=NULL&code=BCGcode128' , realpath(dirname(__FILE__) ) ), 

	      'wc_api_url'  => WC_POS()->wc_api_url(),
	      
	      'discount_presets'       => WC_Admin_Settings::get_option( 'woocommerce_pos_register_discount_presets', array(5,10,15,20) ),
	      'show_stock'             => WC_Admin_Settings::get_option( 'wc_pos_show_stock', 'yes' ),
	      'user_can_edit_product'  => current_user_can( 'edit_private_products' ),
	      'user_can_edit_order'    => current_user_can( 'edit_private_shop_orders' ),
	      'lock_screen'            => get_option('wc_pos_lock_screen', 'no') == 'yes' ? true : false,
	      'unlock_pass'            => md5(get_option('wc_pos_unlock_pass')),
	    ));
		return json_encode( $params );
	}
	public static function getJsWCParams()
	{
		$pos_tax_based_on = get_option( 'woocommerce_pos_calculate_tax_based_on', 'outlet' );
		if( $pos_tax_based_on == 'default' ){
			$pos_tax_based_on = get_option( 'woocommerce_tax_based_on' );
		}
		
		$params = apply_filters('wc_pos_wc_params', array(
			'tax_display_shop'      => get_option( 'woocommerce_tax_display_shop' ),
			'calc_taxes'            => get_option( 'woocommerce_calc_taxes' ),
			'prices_include_tax'    => wc_prices_include_tax(),
			'tax_round_at_subtotal' => get_option( 'woocommerce_tax_round_at_subtotal' ),
			'tax_display_cart'      => get_option( 'woocommerce_tax_display_cart' ),			
			'default_customer_addr' => get_option( 'woocommerce_default_customer_address' ),
			'calc_discounts_seq'    => get_option( 'woocommerce_calc_discounts_sequentially', 'no' ),
			'pos_tax_based_on'      => $pos_tax_based_on,
			'precision'             => WC_ROUNDING_PRECISION,
			'all_rates'             => wc_pos_find_all_rates(),
			'outlet_location'       => wc_pos_get_outlet_location(),
			'shop_location'         => wc_pos_get_shop_location(),
			'tax_enabled'           => wc_tax_enabled(),
			'european_union_countries' => WC()->countries->get_european_union_countries(),
			'base_country'             => WC()->countries->get_base_country(),
			'base_state' 	           => WC()->countries->get_base_state(),
			'base_postcode' 	       => WC()->countries->get_base_postcode(),
			'base_city' 		       => WC()->countries->get_base_city(),			
	      
	    ));
		return json_encode( $params );
	}

	public static function getJsCartParams()
	{
		$tax_display_cart   = get_option( 'woocommerce_tax_display_cart' );		
		$params = apply_filters('wc_pos_cart_params', array(
			'prices_include_tax'    => wc_prices_include_tax(),
			'calc_shipping'         => ( get_option('woocommerce_calc_shipping') == 'no' ) ? false : true,
			'round_at_subtotal'     => get_option( 'woocommerce_tax_round_at_subtotal' ) == 'yes',
			'tax_total_display'     => get_option( 'woocommerce_tax_total_display' ),
			'tax_display_cart'      => $tax_display_cart,
			'dp'                    => wc_get_price_decimals(),
			'display_totals_ex_tax' => $tax_display_cart == 'excl',
			'display_cart_ex_tax'   => $tax_display_cart == 'excl',
			'enable_coupons'        => apply_filters( 'woocommerce_coupons_enabled', get_option( 'woocommerce_enable_coupons' ) == 'yes' ),
			'tax_or_vat'            => WC()->countries->tax_or_vat(),
			'ex_tax_or_vat'         => WC()->countries->ex_tax_or_vat(),
			'inc_tax_or_vat'        => WC()->countries->inc_tax_or_vat(),
			'shipping_tax_class'    => get_option( 'woocommerce_shipping_tax_class' ),
			'tax_classes'           => array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) ),
		));
		return json_encode( $params );
	}

	public static function getJsDefaultCastomer($default_customer_id = 0)
	{
    	$default_customer  = 'false';
		if( $default_customer_id > 0){
	    	WC()->api->includes();
			WC()->api->register_resources( new WC_API_Server( '/' ) );
	    	$customer          = WC()->api->WC_API_Customers->get_customer( $default_customer_id );
	    	
	    	$customer          = $customer['customer'];
	    	if(empty($customer['billing_address']['first_name'])){
	    		$customer['billing_address']['first_name'] = $customer['first_name'];
	    	}
	    	if(empty($customer['billing_address']['last_name'])){
	    		$customer['billing_address']['last_name'] = $customer['last_name'];
	    	}
	    	if(empty($customer['billing_address']['email'])){
	    		$customer['billing_address']['email'] = $customer['email'];
	    	}
	    	if(empty($customer['shipping_address']['first_name'])){
	    		$customer['shipping_address']['first_name'] = $customer['billing_address']['first_name'];
	    	}
	    	if(empty($customer['shipping_address']['last_name'])){
	    		$customer['shipping_address']['last_name'] = $customer['billing_address']['last_name'];
	    	}
	    	$default_customer  = json_encode($customer);
	    }

	    return $default_customer;
	}

	public function getJs_Custom_Product()
	{
		$product_id       = (int)get_option('wc_pos_custom_product_id');
    	$product          = wc_get_product( $product_id );
    	$prices_precision = wc_get_price_decimals();
    	$product_data     = array(
			'title'              => $product->get_title(),
			'id'                 => (int) $product->is_type( 'variation' ) ? $product->get_variation_id() : $product->id,
			'created_at'         => '',
			'updated_at'         => '',
			'type'               => $product->product_type,
			'status'             => $product->get_post_data()->post_status,
			'downloadable'       => $product->is_downloadable(),
			'virtual'            => $product->is_virtual(),
			'permalink'          => $product->get_permalink(),
			'sku'                => $product->get_sku(),
			'price'              => wc_format_decimal( $product->get_price(), $prices_precision ),
			'regular_price'      => wc_format_decimal( $product->get_regular_price(), $prices_precision ),
			'sale_price'         => $product->get_sale_price() ? wc_format_decimal( $product->get_sale_price(), $prices_precision ) : null,
			'price_html'         => $product->get_price_html(),
			'taxable'            => $product->is_taxable(),
			'tax_status'         => $product->get_tax_status(),
			'tax_class'          => $product->get_tax_class(),
			'managing_stock'     => $product->managing_stock(),
			'stock_quantity'     => $product->get_stock_quantity(),
			'in_stock'           => $product->is_in_stock(),
			'backorders_allowed' => $product->backorders_allowed(),
			'backordered'        => $product->is_on_backorder(),
			'sold_individually'  => $product->is_sold_individually(),
			'purchaseable'       => $product->is_purchasable(),
			'featured'           => $product->is_featured(),
			'visible'            => $product->is_visible(),
			'catalog_visibility' => $product->visibility,
			'on_sale'            => $product->is_on_sale(),
			'product_url'        => $product->is_type( 'external' ) ? $product->get_product_url() : '',
			'button_text'        => $product->is_type( 'external' ) ? $product->get_button_text() : '',
			'weight'             => $product->get_weight() ? wc_format_decimal( $product->get_weight(), 2 ) : null,
			'dimensions'         => array(
				'length' => $product->length,
				'width'  => $product->width,
				'height' => $product->height,
				'unit'   => get_option( 'woocommerce_dimension_unit' ),
			),
			'shipping_required'  => $product->needs_shipping(),
			'shipping_taxable'   => $product->is_shipping_taxable(),
			'shipping_class'     => $product->get_shipping_class(),
			'shipping_class_id'  => ( 0 !== $product->get_shipping_class_id() ) ? $product->get_shipping_class_id() : null,
			'description'        => wpautop( do_shortcode( $product->get_post_data()->post_content ) ),
			'short_description'  => apply_filters( 'woocommerce_short_description', $product->get_post_data()->post_excerpt ),
			'reviews_allowed'    => ( 'open' === $product->get_post_data()->comment_status ),
			'average_rating'     => wc_format_decimal( $product->get_average_rating(), 2 ),
			'rating_count'       => (int) $product->get_rating_count(),
			'related_ids'        => array_map( 'absint', array_values( $product->get_related() ) ),
			'upsell_ids'         => array_map( 'absint', $product->get_upsells() ),
			'cross_sell_ids'     => array_map( 'absint', $product->get_cross_sells() ),
			'parent_id'          => $product->post->post_parent,
			'categories'         => wp_get_post_terms( $product->id, 'product_cat', array( 'fields' => 'names' ) ),
			'tags'               => wp_get_post_terms( $product->id, 'product_tag', array( 'fields' => 'names' ) ),
			'featured_src'       => wp_get_attachment_url( get_post_thumbnail_id( $product->is_type( 'variation' ) ? $product->variation_id : $product->id ) ),
			'attributes'         => array(),
			'downloads'          => array(),
			'download_limit'     => (int) $product->download_limit,
			'download_expiry'    => (int) $product->download_expiry,
			'download_type'      => $product->download_type,
			'purchase_note'      => wpautop( do_shortcode( wp_kses_post( $product->purchase_note ) ) ),
			'total_sales'        => metadata_exists( 'post', $product->id, 'total_sales' ) ? (int) get_post_meta( $product->id, 'total_sales', true ) : 0,
			'variations'         => array(),
			'parent'             => array(),
		);
    	$custom_product = json_encode( $product_data );
    	return $custom_product;
	}
	public static function getJsWCSelectParams()
	{
		$params = array(
					'countries'                 => json_encode( array_merge( WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states() ) ),
					'allowed_countries'         => WC()->countries->get_allowed_countries(),
					'i18n_select_state_text'    => esc_attr__( 'Select an option&hellip;', 'woocommerce' ),
					'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce' ),
					'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce' ),
					'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce' ),
					'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce' ),
					'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce' ),
					'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce' ),
					'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce' ),
					'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce' ),
					'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce' ),
					'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce' ),
					'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce' ),
					'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce' ),
				);
		return json_encode( $params );
	}

	public function getGrid()
	{
		$out_of_stock  = get_option('wc_pos_show_out_of_stock_products');
		$data          = $this->data;
		$grid_id       = $data['grid_template'];
		$tile_styles   = array();
		$products_sort = array();
		if( $grid_id != 'all' && $grid_id != 'categories'){
			global $wpdb;
	    	$tiles = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "wc_poin_of_sale_tiles WHERE grid_id = {$grid_id}" );
	    	if($tiles){
	    		foreach ($tiles as $key => $style) {
	    			$_id = absint($style->product_id);
	    			$add = true;
	    			if( $out_of_stock != 'yes' ){
	    				$product = wc_get_product($_id);
	    				if( !$product->is_in_stock() ){
	    					$add = false;	    					
	    				}
	    			}
	    			if( $add ){
		    			$products_sort[]   = $_id;	
		    			$tile_styles[$_id] = $style;	    				
	    			}
	    		}
	    	}
	    }else{
			$products = the_grid_layout_cycle($grid_id, true);
			foreach ($products as $key => $value) {
				$_id = absint($value->ID);
				$add = true;
    			if( $out_of_stock != 'yes' ){
    				$product = wc_get_product($_id);
    				if( !$product->is_in_stock() ){
    					$add = false;	    					
    				}
    			}
    			if( $add ){
	    			$products_sort[]   = $_id;
    			}
			}
		}
		$terms = get_terms( 'product_cat');
		$categories = array();
		if($terms){
			foreach ($terms as $term) {
				$thumbnail_id = absint( get_woocommerce_term_meta( $term->term_id, 'thumbnail_id', true ) );
                if ( $thumbnail_id ) {
                  $thumbnail = wp_get_attachment_image_src( $thumbnail_id, $size );
                  $image     = $thumbnail[0];
                } else {
                  $image = wc_placeholder_img_src();
                }
                if(!$image || $image == NULL) $image = wc_placeholder_img_src();
                
				$term->image  = $image;
				$display_type = get_woocommerce_term_meta( $term->term_id, 'display_type', true );
				$term->display_type  = $display_type;

				$categories[$term->term_id] = $term;
			}
		}
	    $grid = array(
	        'category_archive_display' => get_option( 'woocommerce_category_archive_display', ''),
	      	'register_layout'          => get_option('woocommerce_pos_register_layout', 'product_grids'),
	      	'second_column_layout'     => get_option('woocommerce_pos_second_column_layout', 'product_grids'),
	      	'tile_variables'           => get_option('wc_pos_tile_variables', 'overlay'),
	      	'hide_text'                => get_option('wc_pos_hide_text_on_tiles', 'no') == 'yes' ? true : false,
	      	'tile_layout'              => get_option('wc_pos_tile_layout', 'image_title'),
	      	'grid_id'                  => $grid_id,
			'term_relationships'       => pos_term_relationships(),
	        'categories'               => $categories,
	        'products_sort'            => $products_sort,
	        'tile_styles'              => $tile_styles,
	    	);
	    
		return json_encode( $grid );
	}

	public function enqueue_scripts_payment_gateway($scripts)
	{
		$new_arr = array();
		if ( in_array('stripe', $this->active_plugins ) ){
			$new_arr = array(
				'jquery-payment'        => WC()->plugin_url() . '/assets/js/jquery-payment/jquery.payment.min.js',
				'stripe'                => 'https://js.stripe.com/v1/',
				'woocommerce_stripe'    => WC_STRIPE_PLUGIN_URL . '/assets/js/stripe.js',
			);
		}
		return $new_arr + $scripts;
	}

	public function inline_js_payment_gateway($inline_js)
	{
		if ( in_array('stripe', $this->active_plugins ) ){
			$stripe          = new WC_Gateway_Stripe();
			$publishable_key = $stripe->testmode ? $stripe->get_option( 'test_publishable_key' ) : $stripe->get_option( 'publishable_key' );

			$stripe_params   = array(
				'key'        => $publishable_key,
				'i18n_terms' => __( 'Please accept the terms and conditions first', 'woocommerce-gateway-stripe' )
			);
			
			$inline_js[] = '<script type="text/javascript" class="wc_stripe_params" >  var wc_stripe_params = '. json_encode( $stripe_params ) .'; </script>';
		}
		return $inline_js;
	}


    /**
	 * Main WC_Pos_Registers Instance
	 *
	 * Ensures only one instance of WC_Pos_Registers is loaded or can be loaded.
	 *
	 * @since 1.9
	 * @static
	 * @return WC_Pos_Registers Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.9
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '1.9' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.9
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'woocommerce' ), '1.9' );
	}

}

return new WC_Pos_Sell();