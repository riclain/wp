<?php
if( !defined( 'ABSPATH' ) )
    exit;

if( !class_exists( 'YITH_WC_Surveys_Utility' ) ){

    class YITH_WC_Surveys_Utility{


        /**
         * add new survey in user list
         * @author YIThemes
         * @since 1.0.0
         * @param array $survey
         */
        public static function add( $survey ){

            $user_id    =   isset( $survey['user_id'] ) ? $survey['user_id']    : -1;
            $survey_id  =   isset( $survey['survey_id'] )   ?    $survey['survey_id']    :   -1;
            $survey_qst =   $survey['question'];
            $survey_ans =   $survey['answer'];

           if( $survey_id == -1 )
               return 'invalid_survey';


            if( self::is_user_survey_in_list( $survey_id ) )
                return 'already_response';

            if( $user_id!= -1 ){

                $user_list = get_user_meta( $user_id, '_yith_user_survey_meta', true );

                if( empty( $user_list ) )
                    $user_list = array();

                $user_list[]=array(
                    'survey_id'    =>  $survey_id,
                    'question'  => $survey_qst,
                    'answer'      =>  $survey_ans
                );


                $res = update_user_meta( $user_id, '_yith_user_survey_meta', $user_list );


            }
            else{
                $cookie =   array(
                    'survey_id'    =>  $survey_id,
                    'question'  => $survey_qst,
                    'answer'      =>  $survey_ans

                );

                $savelist_cookie    =   yith_getcookie( 'yith_user_surveys_cookie' );

                $savelist_cookie[]=$cookie;

                yith_setcookie( 'yith_user_surveys_cookie', $savelist_cookie );

                $res    =   true;

            }
            if( $res ) {

                return "true";
            }
            else
            {
                return "error";
            }

        }

        /**
         * check if the user has already responded to the survey
         * @author YITHEMES
         * @since 1.0.0
         * @param $survey_id
         * @return bool
         */
        public static function is_user_survey_in_list( $survey_id ){
            $exist =   false;

            if ( is_user_logged_in() ){


                $user_id    =   get_current_user_id();

                $user_list = get_user_meta( $user_id,  '_yith_user_survey_meta', true );


                if( empty( $user_list ) )
                    return false;


                $key =  'survey_id';
                $value = $survey_id;


                foreach( $user_list as $i=>$list ) {

                    if( $list[$key]==$value )
                        return true;
                }


                return false;
            }
            else
            {
                $cookie =   yith_getcookie( 'yith_user_surveys_cookie' );

                $key =  'survey_id';
                $value = $survey_id;

                foreach( $cookie as $k=>$item ){
                    if( $item[$key]==$value ) {
                        $exist = true;
                        break;
                    }
                }
                return $exist;
            }

        }

        /**
         * @param $survey_id
         * @return bool|string
         */
        public static function get_user_answer_by_survey_id( $survey_id ){

            $answer = '';

            if ( is_user_logged_in() ){


                $user_id    =   get_current_user_id();

                $user_list = get_user_meta( $user_id,  '_yith_user_survey_meta', true );


                if( empty( $user_list ) )
                    return false;


                $key =  'survey_id';
                $value = $survey_id;


                foreach( $user_list as $i=>$list ) {

                    if( $list[$key]==$value ) {
                        $answer = $list['answer'];
                        break;
                    }
                }


                return $answer;
            }
            else
            {
                $cookie =   yith_getcookie( 'yith_user_surveys_cookie' );

                $key =  'survey_id';
                $value = $survey_id;

                foreach( $cookie as $k=>$item ){
                    if( $item[$key]==$value ) {
                        $answer = $item['answer'];
                        break;
                    }
                }
                return $answer;
            }

        }
        /**
         * get data
         * @author YIThemes
         * @since 1.0.0
         * @return array|mixed
         */
        public static function generate_data()
        {


            $items =  get_transient('yith_surveys_results_transient');

            if (false === $items) {

                //get all surveyes visible in checkout
                $items = array();

                $items = self::generate_checkout_surveys_answers($items);

                $items = self::generate_checkout_surveys_no_answers($items);

                $items = self::generate_product_surveys($items);

                $items = self::generate_other_page_surveys($items);

                set_transient('yith_surveys_results_transient', $items, 24 * HOUR_IN_SECONDS);
            }

            return $items;
        }


        /**
         * get checkout survey with a answers
         * @author YIThemes
         * @since 1.0.0
         * @param $items
         * @return array
         */
        public static function generate_checkout_surveys_answers( $items ){

            $order_with_survey = yith_woocommerce_get_orders();
            //insert all checkout surveys answers
            foreach ($order_with_survey as $order_id) {

                $order_surveys = get_post_meta($order_id, '_yith_order_survey_voting', true);
                $order = wc_get_order($order_id);
                $order_total = $order->get_total();

                foreach ($order_surveys as $order_survey) {


                    $survey_id = isset( $order_survey['survey_id'] ) ? $order_survey['survey_id'] : YITH_Surveys_Type()->is_survey_child_exist( $order_survey['survey_title'], 0 ) ;
                    $answer = $order_survey['answer_title'];


                    $key = self::is_element_in_items($items, $survey_id, $answer);

                    if ($key != -1) {

                        $old_total = isset ($items[$key]['tot_order']) ? $items[$key]['tot_order'] : 0;
                        $new_total = $old_total + $order_total;
                        $items[$key]['tot_order'] = $new_total;

                        $old_vote_total = isset ($items[$key]['tot_votes']) ? $items[$key]['tot_votes'] : 0;
                        $new_vote_total = $old_vote_total + 1;
                        $items[$key]['tot_votes'] = $new_vote_total;

                        $items[$key]['order_details'][] = $order_id;


                    } else {

                        $new_item = array(
                            'survey_id' => $survey_id,
                            'answer' => $answer,
                            'tot_order' => $order_total,
                            'visible_in' => 'checkout',
                            'tot_votes' => 1,
                            'order_details' => array($order_id)
                        );


                        $items[] = $new_item;
                    }
                }
            }

            return $items;
        }

        /**
         * get checkout survey with no answers
         * @param $items
         * @return array
         */
        public static function generate_checkout_surveys_no_answers( $items ){

            $all_surveys_checkout = YITH_Surveys_Type()->get_checkout_surveys();

            foreach( $all_surveys_checkout as $survey_id ){

                $all_answer = YITH_Surveys_Type()->get_survey_children( array('post_parent'=> $survey_id ) );

                foreach( $all_answer as $answer_id ){

                    $answer = get_the_title( $answer_id );

                    $exist = self::is_element_in_items( $items, $survey_id, $answer ) ;

                    if( $exist == -1 ){

                        $new_item = array(
                            'survey_id' => $survey_id,
                            'answer' => $answer,
                            'tot_order' => 0,
                            'visible_in' => 'checkout',
                            'tot_votes' => 0,
                            'order_details' => array()
                        );

                        $items[] = $new_item;
                    }
                }
            }
            return $items;
        }

        /**
         * get product survey data
         * @author YIThemes
         * @since 1.0.0
         * @param $items
         * @return array
         */
        public static function generate_product_surveys( $items ){

            $all_surveys_product = YITH_Surveys_Type()->get_product_surveys();

            foreach( $all_surveys_product as $survey_id ){

                $all_answer =  YITH_Surveys_Type()->get_survey_children( array('post_parent'=> $survey_id ) );

                foreach( $all_answer as $answer_id ){

                    $answer = get_the_title( $answer_id );
                    $exist = self::is_element_in_items( $items, $survey_id, $answer ) ;

                    if( $exist == -1 ){

                        $tot_votes = get_post_meta( $answer_id, '_yith_answer_votes', true );
                        $tot_votes = $tot_votes ? $tot_votes : 0;
                        $new_item = array(
                            'survey_id' => $survey_id,
                            'answer' => $answer,
                            'tot_order' => 0,
                            'visible_in' => 'product',
                            'tot_votes' => $tot_votes,
                            'order_details' => array()
                        );

                        $items[] = $new_item;
                    }
                }
            }

            return $items;
        }

        /**
         * get surveys data visible in other page
         * @author YIThemes
         * @since 1.0.0
         * @param $items
         * @return array
         */
        public static function generate_other_page_surveys( $items ){

            $all_surveys_product = YITH_Surveys_Type()->get_other_surveys();

            foreach( $all_surveys_product as $survey_id ){

                $all_answer =  YITH_Surveys_Type()->get_survey_children( array('post_parent'=> $survey_id ) );

                foreach( $all_answer as $answer_id ){

                    $answer =get_the_title( $answer_id );

                    $exist = self::is_element_in_items( $items, $survey_id, $answer ) ;

                    if( $exist == -1 ){
                        $tot_votes = get_post_meta( $answer_id, '_yith_answer_votes', true );
                        $tot_votes = $tot_votes ? $tot_votes : 0;
                        $new_item = array(
                            'survey_id' => $survey_id,
                            'answer' => $answer,
                            'tot_order' => 0,
                            'visible_in' => 'other_page',
                            'tot_votes' => $tot_votes,
                            'order_details' => array()
                        );

                        $items[] = $new_item;
                    }
                }
            }

            return $items;
        }

        /**
         * check if a couple (survey_id, answer ) is already present in items
         * @author YIThemes
         * @since 1.0.0
         * @param $items
         * @param $value_1
         * @param $value_2
         * @return int|string
         */
        public static function is_element_in_items($items, $value_1, $value_2)
        {

            foreach ($items as $key => $item) {

                if ( ( isset($item['survey_id'] ) && $item['survey_id'] == $value_1 ) && ( isset( $item['answer'] ) && strcasecmp( $item['answer'], $value_2 ) == 0 ) )
                    return $key;
            }
            return -1;
        }
    }
}