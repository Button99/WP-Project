<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://ays-pro.com/
 * @since      1.0.0
 *
 * @package    Poll_Maker_Ays
 * @subpackage Poll_Maker_Ays/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Poll_Maker_Ays
 * @subpackage Poll_Maker_Ays/public
 * @author     Poll Maker Team <info@ays-pro.com>
 */
class Ays_Poll_Maker_Extra_Shortcodes_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    protected $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    private $html_class_prefix = 'ays-poll-extra-shortcodes-';
    private $html_name_prefix = 'ays-poll-';
    private $name_prefix = 'ays_poll_';
    private $unique_id;
    private $unique_id_in_class;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $plugin_name The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct($plugin_name, $version){

        $this->plugin_name = $plugin_name;
        $this->version = $version;

        add_shortcode('ays_poll_passed_users_count', array($this, 'ays_generate_passed_users_count_method'));
        add_shortcode('ays_poll_user_first_name', array($this, 'ays_generate_user_first_name_method'));
        add_shortcode('ays_poll_user_last_name', array($this, 'ays_generate_user_last_name_method'));
        add_shortcode('ays_poll_user_display_name', array($this, 'ays_generate_user_display_name_method'));
        add_shortcode('ays_poll_user_email', array($this, 'ays_generate_user_email_method'));
    }

    /*
    ==========================================
        Passed users count | Start
    ==========================================
    */

    public function ays_generate_passed_users_count_method( $attr ){

        $id = (isset($attr['id']) && $attr['id'] != '') ? absint( sanitize_text_field($attr['id']) ) : null;

        if (is_null($id) || $id == 0 ) {
            $passed_users_count_html = "<p class='wrong_shortcode_text' style='color:red;'>" . __('Wrong shortcode initialized', $this->plugin_name) . "</p>";
            return str_replace(array("\r\n", "\n", "\r"), "\n", $passed_users_count_html);
        }

        $unique_id = uniqid();
        $this->unique_id = $unique_id;
        $this->unique_id_in_class = $id . "-" . $unique_id;


        $passed_users_count_html = $this->ays_poll_passed_users_count_html( $id );
        return str_replace(array("\r\n", "\n", "\r"), "\n", $passed_users_count_html);
    }

    public function ays_poll_passed_users_count_html( $id ){

        $results = array();
        if ( class_exists( 'Poll_Maker_Ays_Public' ) ) {
            $results = Poll_Maker_Ays_Public::get_poll_results_count_by_id( $id );
        }

        $content_html = array();
        if( is_null( $results ) ){
            $content_html = "<p style='text-align: center;font-style:italic;'>" . __( "There are no results yet.", $this->plugin_name ) . "</p>";
            return $content_html;
        }

        $passed_users_count = (isset( $results['res_count'] ) && $results['res_count'] != '') ? sanitize_text_field( $results['res_count'] ) : 0;

        if ( $passed_users_count == 0 ) {
            $content_html = "<p style='text-align: center;font-style:italic;'>" . __( "There are no results yet.", $this->plugin_name ) . "</p>";
            return $content_html;
        }

        $content_html[] = "<span class='". $this->html_name_prefix ."passed-users-count-box' id='". $this->html_name_prefix ."passed-users-count-box-". $this->unique_id_in_class ."' data-id='". $this->unique_id ."'>";
            $content_html[] = $passed_users_count;
        $content_html[] = "</span>";

        $content_html = implode( '' , $content_html);

        return $content_html;
    }

    /*
    ==========================================
        Passed users count | End
    ==========================================
    */

    /*
    ==========================================
        Show users firstname | Start
    ==========================================
    */
    public function ays_generate_user_first_name_method(){

        $unique_id = uniqid();
        $this->unique_id = $unique_id;
        $this->unique_id_in_class = $unique_id;

        $user_first_name_html = "";
        if(is_user_logged_in()){
            $user_first_name_html = $this->ays_generate_users_html('first');
        }
        return str_replace(array("\r\n", "\n", "\r"), "\n", $user_first_name_html);
    }
    /*
    ==========================================
        Show users firstname | End
    ==========================================
    */

    /*
    ==========================================
        Show users lastname | Start
    ==========================================
    */
    public function ays_generate_user_last_name_method(){

        $unique_id = uniqid();
        $this->unique_id = $unique_id;
        $this->unique_id_in_class = $unique_id;

        $user_last_name_html = "";
        if(is_user_logged_in()){
            $user_last_name_html = $this->ays_generate_users_html('last');
        }
        return str_replace(array("\r\n", "\n", "\r"), "\n", $user_last_name_html);
    }
        /*
    ==========================================
        Show users lastname | Start
    ==========================================
    */

    /*
    ==========================================
        Show User Display name | Start
    ==========================================
    */

    public function ays_generate_user_display_name_method(){

        $unique_id = uniqid();
        $this->unique_id = $unique_id;
        $this->unique_id_in_class = $unique_id;

        $user_display_name_html = "";
        if(is_user_logged_in()){
            $user_display_name_html = $this->ays_generate_users_html('display');
        }
        return str_replace(array("\r\n", "\n", "\r"), "\n", $user_display_name_html);
    }

    /*
    ==========================================
        Show User Display name | End
    ==========================================
    */
    
    /*
    ==========================================
        Show User Display name | Start
    ==========================================
    */

    public function ays_generate_user_email_method(){

        $unique_id = uniqid();
        $this->unique_id = $unique_id;
        $this->unique_id_in_class = $unique_id;

        $user_email_html = "";
        if(is_user_logged_in()){
            $user_email_html = $this->ays_generate_users_html('email');
        }
        return str_replace(array("\r\n", "\n", "\r"), "\n", $user_email_html);
    }

    /*
    ==========================================
        Show User Display name | End
    ==========================================
    */

    public function ays_generate_users_html($arg){

        $results = $this->get_user_profile_data();

        $content_html = array();
        
        if( is_null( $results ) || $results == 0 ){
            $content_html = "";
            return $content_html;
        }

        $user_info = (isset( $results['user_'.$arg.'_name'] ) && $results['user_'.$arg.'_name']  != "") ? sanitize_text_field( $results['user_'.$arg.'_name'] ) : '';

        $content_html[] = "<span class='ays-poll-user-".$arg."-name' id='ays-poll-user-".$arg."-name-". $this->unique_id_in_class ."' data-id='". $this->unique_id ."'>";
            $content_html[] = $user_info;
        $content_html[] = "</span>";

        $content_html = implode( '' , $content_html);

        return $content_html;
    }

	public function get_user_profile_data(){

        $user_first_name = '';
        $user_last_name  = '';
        $user_nickname   = '';

        $user_id = get_current_user_id();
        if($user_id != 0){
            $usermeta = get_user_meta( $user_id );
            if($usermeta !== null){
                $user_first_name = (isset($usermeta['first_name'][0]) && sanitize_text_field( $usermeta['first_name'][0] != '') ) ? sanitize_text_field( $usermeta['first_name'][0] ) : '';
                $user_last_name  = (isset($usermeta['last_name'][0]) && sanitize_text_field( $usermeta['last_name'][0] != '') ) ? sanitize_text_field( $usermeta['last_name'][0] ) : '';
                $user_nickname   = (isset($usermeta['nickname'][0]) && sanitize_text_field( $usermeta['nickname'][0] != '') ) ? sanitize_text_field( $usermeta['nickname'][0] ) : '';
            }

            $current_user_data = get_userdata( $user_id );
            if ( ! is_null( $current_user_data ) && $current_user_data ) {
                $user_display_name = ( isset( $current_user_data->data->display_name ) && $current_user_data->data->display_name != '' ) ? sanitize_text_field( $current_user_data->data->display_name ) : "";
                $user_email = ( isset( $current_user_data->data->user_email ) && $current_user_data->data->user_email != '' ) ? sanitize_text_field( $current_user_data->data->user_email ) : "";
            }
        }

        $message_data = array(
            'user_first_name'   => $user_first_name,
            'user_last_name'    => $user_last_name,
            'user_nickname'     => $user_nickname,
            'user_display_name' => $user_display_name,
            'user_email_name'        => $user_email,
        );
		
        return $message_data;
    }

}
