<?php
if( !defined( 'ABSPATH' ) )
    exit;

if( !class_exists( 'YITH_WC_Surveys_Admin_Premium' ) ){

    class YITH_WC_Surveys_Admin_Premium extends  YITH_WC_Surveys_Admin{

        protected static $instance;

        /**
         * __construct function
         */
        public function  __construct(){

            parent::__construct();

            /* remove free actions */
            remove_action( 'init', array( $this, 'save_survey_post_meta' ) );

            add_action( 'admin_enqueue_scripts', array( $this, 'premium_admin_add_scripts' ) );
            //Add context menu to TinyMCE editor
            add_action( 'admin_init', array( $this, 'add_shortcodes_button' ) );
            add_action( 'media_buttons_context', array( &$this, 'surveys_media_buttons_context' ) );
            add_action( 'admin_print_footer_scripts', array( &$this, 'surveys_add_quicktags' ) );
            add_action( 'init', array( $this, 'check_if_export' ) );
            add_action( 'init', array( $this, 'add_multivendor_compatibility' ) );

            add_action( 'woocommerce_order_status_changed', array( $this, 'delete_survey_transient' ), 99, 3 );

        }

        /**
         * @author YIThemes
         * @since 1.0.0
         * @return YITH_WC_Surveys_Admin_Premium
         */
        public static function  get_instance(){

            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        /**
         * include premium admin script
         * @author YIThemes
         * @since 1.0.0
         */
        public function premium_admin_add_scripts(){

            $suffix = !( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '';
            wp_enqueue_script( 'yit_survey_script_thickbox', YITH_WC_SURVEYS_ASSETS_URL.'js/thickbox'.$suffix.'.js', array( 'jquery'), YITH_WC_SURVEYS_VERSION, true );


        }

        public function  add_multivendor_compatibility(){

            YITH_Surveys_Compatibility();
        }



        /**
         * add new survey answer in plugin settings
         * @author YIThemes
         * @since 1.0.0
         */
        public function add_new_survey_answer_admin(){

            if( isset( $_POST['ywcsur_loop'] ) ){

                $loop = $_POST['ywcsur_loop'];
                $params = array(
                    'loop' => $loop,
                );

                $params['params'] = $params;
                ob_start();
                wc_get_template( 'metaboxes/types/surveys_answer.php', $params,'', YITH_WC_SURVEYS_TEMPLATE_PATH );
                $template = ob_get_contents();
                ob_end_clean();

                wp_send_json( array('result' =>   $template ) );

            }
        }

        /**
         * on init check if export data
         * @author YIThemes
         * @since 1.0.0
         */
        public function  check_if_export(){

            if( isset( $_REQUEST['survey_export'] ) && $_REQUEST['survey_export'] == 1 ){

                $items = YITH_WC_Surveys_Utility::generate_data();

                $export = YITH_WC_Surveys_Export::get_instance();

                $filters =  array();


                if( !isset( $_REQUEST['survey_checkout_type'] ) )
                    $filters[]='checkout';

                if( !isset( $_REQUEST['survey_product_type'] ) )
                    $filters[]='product';

                if( !isset( $_REQUEST['survey_other_type'] ) )
                    $filters[]='other_page';



                $export->export_data( $items, $filters );

            }
        }

        /**
         * Add shortcode button
         *
         * Add shortcode button to TinyMCE editor, adding filter on mce_external_plugins
         *
         * @return void
         * @since 1.0.0
         * @author Antonio La Rocca <antonio.larocca@yithemes.it>
         */
        public function add_shortcodes_button()
        {
            if (!current_user_can('edit_posts') && !current_user_can('edit_pages'))
                return;
            if (get_user_option('rich_editing') == 'true') {
                add_filter('mce_external_plugins', array(&$this, 'add_shortcodes_tinymce_plugin'));
                add_filter('mce_buttons', array(&$this, 'register_shortcodes_button'));
            }
        }

        /**
         * Add shortcode plugin
         *
         * Add a script to TinyMCE script list
         *
         * @param $plugin_array array() Array containing TinyMCE script list
         *
         * @return array() The edited array containing TinyMCE script list
         * @since 1.0.0
         * @author Antonio La Rocca <antonio.larocca@yithemes.it>
         */
        public function add_shortcodes_tinymce_plugin($plugin_array)
        {
            $suffix = !( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '.min' : '';
            $plugin_array['surveys_shortcode'] = YITH_WC_SURVEYS_ASSETS_URL . 'js/tinymce'.$suffix.'.js';
            return $plugin_array;
        }

        /**
         * Register shortcode button
         *
         * Make TinyMCE know a new button was included in its toolbar
         *
         * @param $buttons array() Array containing buttons list for TinyMCE toolbar
         *
         * @return array() The edited array containing buttons list for TinyMCE toolbar
         * @since 1.0.0
         * @author Antonio La Rocca <antonio.larocca@yithemes.it>
         */
        public function register_shortcodes_button($buttons)
        {
            array_push($buttons, "|", "surveys_shortcode");
            return $buttons;
        }


        /**
         * The markup of shortcode
         *
         * @since   1.0.0
         *
         * @param   $context
         *
         * @return  mixed
         * @author  Alberto Ruggiero
         */
        public function surveys_media_buttons_context( $context ) {

            $out = '<a id="surveys_shortcode" style="display:none" href="#" class="hide-if-no-js" title="' . __( 'Add YITH WooCommerce Surveys shortcode', 'yith-woocommerce-surveys' ) . '"></a>';

            return $context . $out;

        }

        /**
         * Add quicktags to visual editor
         *
         * @since   1.0.0
         * @return  void
         * @author  Alberto Ruggiero
         */
        public function surveys_add_quicktags() {

            global $post_ID, $temp_ID;

            $query_args   = array(
                'post_id'   => (int) ( 0 == $post_ID ? $temp_ID : $post_ID ),
                'KeepThis'  => true,
                'TB_iframe' => true
            );
            $lightbox_url = esc_url( add_query_arg( $query_args, YITH_WC_SURVEYS_URL . '/templates/admin/lightbox.php' ) );



            ?>
            <script type="text/javascript">

                if ( window.QTags !== undefined ) {
                    QTags.addButton( 'surveys_shortcode', 'add yith surveys shortcode', function () {
                        jQuery('#surveys_shortcode').click()
                    } );
                }

                jQuery('#surveys_shortcode').on( 'click', function() {

                    tb_show( 'Add YITH WooCommerce Surveys shortcode', '<?php echo $lightbox_url ?>');
                    yith_resize_thickbox( 500,200 );

                });

            </script>
        <?php
        }

        /**
         * delete survey transient when a order change status
         * @author YIThemes
         * @since 1.0.1
         * @param $order_id
         * @param $old_status
         * @param $new_status
         */
        public function delete_survey_transient( $order_id, $old_status, $new_status ){

            delete_transient( 'yith_surveys_results_transient' );
        }



    }
}