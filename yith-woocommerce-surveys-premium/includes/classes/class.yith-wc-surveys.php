<?php
if( !defined( 'ABSPATH' ) )
    exit;

if( !class_exists( 'YITH_WC_Surveys' ) ){

    class YITH_WC_Surveys{
        /**
         * @var YITH_WC_Surveys static instance
         */
        protected  static $instance;
        /**
         * @var YITH_Surveys_Post_Type Post Type
         */
        protected $survey;

        /**
         * @var YIT_Plugin_Panel_Woocommerce instance
         */
        protected $_panel;

        /**
         * @var YIT_Plugin_Panel_Woocommerce instance
         */
        protected $_panel_page = 'yith_wc_surveys_panel';

        /**
         * @var string Official plugin documentation
         */
        protected $_official_documentation = 'http://yithemes.com/docs-plugins/yith-woocommerce-surveys/' ;

        /**
         * @var string Official plugin landing page
         */
        protected $_premium_landing_url = 'http://yithemes.com/themes/plugins/yith-woocommerce-surveys/' ;

        /**
         * @var string Official plugin landing page
         */
        protected $_premium_live_demo = 'http://plugins.yithemes.com/yith-woocommerce-surveys/' ;

        /**
         * @var string Premium page
         */
        protected $_premium = 'premium.php';


        public function __construct(){

            /* Plugin Informations */
            add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader') ,15 );
            add_filter( 'plugin_action_links_' . plugin_basename( YITH_WC_SURVEYS_DIR . '/' . basename( YITH_WC_SURVEYS_FILE ) ), array( $this, 'action_links' ) );
            add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 4 );

            /*Add Surveys in YITH PLUGIN*/
            add_action( 'admin_menu', array( $this, 'add_surveys_menu' ), 5 );

            add_action( 'yith_wc_surveys_premium', array( $this, 'show_premium_tab' ) );
            add_action( 'yith_wc_add_survey', array( $this, 'show_survery_options' ) );

            //if plugin is enabled start game!
           if( 'yes' == get_option( 'ywcsur_enable_plugin', 'yes' ) ) {

               $this->survey = YITH_Surveys_Type();

               if ( is_admin() ) {
                   global $YIT_surveys;
                   $YIT_surveys = YITH_Surveys_Admin();
               } else {
                   global $YIT_surveys;
                   $YIT_surveys = YITH_Surveys_Frontend();
               }
           }

        }

        /**
         * return single instance
         * @author YIThemes
         * @since 1.0.0
         * @return YITH_WC_Surveys
         */
        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * load plugin framework 2.0
         * @author YIThemes
         * @since 1.0.0
         */
        public function plugin_fw_loader() {
            if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
                global $plugin_fw_data;
                if( ! empty( $plugin_fw_data ) ){
                    $plugin_fw_file = array_shift( $plugin_fw_data );
                    require_once( $plugin_fw_file );
                }
            }
        }

        /**
         * Action Links
         *
         * add the action links to plugin admin page
         *
         * @param $links | links plugin array
         *
         * @return   mixed Array
         * @since    1.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @return mixed
         * @use plugin_action_links_{$plugin_file_name}
         */
        public function action_links($links)
        {

            $links[] = '<a href="' . admin_url("admin.php?page={$this->_panel_page}") . '">' . __('Settings', 'yith-woocommerce-surveys') . '</a>';

            $premium_live_text = !ywcsur_is_premium_active() ?  __( 'Premium live demo', 'yith-woocommerce-surveys' ) : __( 'Live demo', 'yith-woocommerce-surveys' );

            $links[] = '<a href="'.$this->_premium_live_demo.'" target="_blank">'.$premium_live_text.'</a>';

            if ( !ywcsur_is_premium_active() )
                $links[] = '<a href="' . $this->get_premium_landing_uri() . '" target="_blank">' . __('Premium Version', 'yith-woocommerce-surveys') . '</a>';


            return $links;
        }

        /**
         * plugin_row_meta
         *
         * add the action links to plugin admin page
         *
         * @param $plugin_meta
         * @param $plugin_file
         * @param $plugin_data
         * @param $status
         *
         * @return   Array
         * @since    1.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @use plugin_row_meta
         */
        public function plugin_row_meta($plugin_meta, $plugin_file, $plugin_data, $status)
        {
            if ( ( defined( 'YITH_WC_SURVEYS_INIT' ) && ( YITH_WC_SURVEYS_INIT == $plugin_file ) ) ||
                ( defined( 'YITH_WC_SURVEYS_FREE_INIT' ) && ( YITH_WC_SURVEYS_FREE_INIT == $plugin_file ) ) ) {

                $plugin_meta[] = '<a href="' . $this->_official_documentation . '" target="_blank">' . __('Plugin Documentation', 'yith-woocommerce-surveys') . '</a>';
            }

            return $plugin_meta;
        }

        /**
         * Get the premium landing uri
         *
         * @since   1.0.0
         * @author  Andrea Grillo <andrea.grillo@yithemes.com>
         * @return  string The premium landing link
         */
        public function get_premium_landing_uri()
        {
            return defined('YITH_REFER_ID') ? $this->_premium_landing_url . '?refer_id=' . YITH_REFER_ID : $this->_premium_landing_url .'?refer_id=1030585';
        }

        /**
         * Premium Tab Template
         *
         * Load the premium tab template on admin page
         *
         * @since   1.0.0
         * @author  Andrea Grillo <andrea.grillo@yithemes.com>
         * @return  void
         */
        public function show_premium_tab()
        {
            $premium_tab_template = YITH_WC_SURVEYS_TEMPLATE_PATH . '/admin/' . $this->_premium;
            if (file_exists($premium_tab_template)) {
                include_once($premium_tab_template);
            }
        }

        /**
         *
         */
        public function show_survery_options(){

            $survey_tab_option = YITH_WC_SURVEYS_TEMPLATE_PATH . '/admin/survey_options.php';

            if( file_exists(  $survey_tab_option  ) )
                include_once( $survey_tab_option );
        }

        /**
         * Add a panel under YITH Plugins tab
         *
         * @return   void
         * @since    1.0.0
         * @author   Andrea Grillo <andrea.grillo@yithemes.com>
         * @use     YIT_Plugin_Panel_WooCommerce class
         * @see      plugin-fw/lib/yit-plugin-panel-wc.php
         */
        public function add_surveys_menu(){

            if (!empty($this->_panel)) {
                return;
            }

            $admin_tabs['survey-settings'] = __( 'Survey Settings', 'yith-woocommerce-surveys' );

            if ( !ywcsur_is_premium_active() )
                $admin_tabs['premium-landing'] = __( 'Premium Version', 'yith-woocommerce-surveys' );


            $args = array(
                'create_menu_page' => true,
                'parent_slug' => '',
                'page_title' => __( 'Surveys', 'yith-woocommerce-surveys' ),
                'menu_title' => __( 'Surveys', 'yith-woocommerce-surveys' ),
                'capability' => 'manage_options',
                'parent' => '',
                'parent_page' => 'yit_plugin_panel',
                'page' => $this->_panel_page,
                'admin-tabs' => apply_filters( 'yith_wc_survey_add_premium_tab', $admin_tabs ),
                'options-path' => YITH_WC_SURVEYS_DIR . '/plugin-options'
            );

            $this->_panel =  new YIT_Plugin_Panel_WooCommerce( $args );
        }

    }
}