<?php
if( !defined( 'ABSPATH' ) )
    exit;

$options = array(
  'survey-settings' =>   array(

      'section_survey_settings'     => array(
          'name' => __( 'Survey Option', 'yith-woocommerce-surveys' ),
          'type' => 'title',
          'id'   => 'ywcsur_section_general'
      ),

      'survey_enabled' => array(
          'name' => __( 'Enable plugin', 'yith-woocommerce-surveys' ),
          'type'    => 'checkbox',
          'default' => 'yes',
          'id' =>'ywcsur_enable_plugin'
      ),

      'survey_thanks_message'   => array(
          'name'    =>  __( 'Thank-you message', 'yith-woocommerce-surveys' ),
          'type'    =>  'text',
          'default' =>  __( 'Thanks for voting', 'yith-woocommerce-surveys' ),
          'id'      => 'ywcsur_thanks_message',
          'desc'    => __( 'Works only in Survey, visible in product or via shortcode or widget', 'yith-woocommerce-surveys' ),
          'css'   => 'width:60%;'
      ),

      'survey_hide_after_answer' => array(
          'name' => __( 'Hide survey only after one answer is given. ', 'yith-woocommerce-surveys' ),
          'type' => 'checkbox',
          'default' => 'no',
          'desc'    => __( 'If checked, users will no longer be able to see surveys they have answered. Works only in Survey, visible in product or via shortcode or widget' ,'yith-woocommerce-surveys' ),
          'id'      =>  'ywcsur_hide_after_answer'
      ),

      'section_survey_settings_end' => array(
          'type' => 'sectionend',
          'id'   => 'ywcsur_section_general_end'
      )

  )
);


return apply_filters( 'yith_wc_survey_settings_options', $options );