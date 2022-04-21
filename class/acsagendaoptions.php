<?php
if( !defined('ABSPATH') ){ die('Are you trying to kid me?');}

class ACSagendaOptions_Plugin
{
    static $instance;
    private $options;
    // class constructor
    public function __construct()
    {
        add_action('admin_menu', [$this, 'ACSagendaplugin_menu']);
    }
    public function ACSagendaplugin_menu()
    {
        $hook = add_submenu_page('agenda', 'Settings', 'Settings', 'manage_options', 'acsagendaoptions', [$this, 'ACSagenda_plugin_Options_page']);
        add_action("load-$hook", $this);
    }
    public function ACSagenda_plugin_Options_page()
    { 
    if( $_POST['updated'] === 'true' ){
        $this->ACS_agenda_handle_form();
        }
    $agendapage = get_option('acsagendapage')?get_option('acsagendapage'):'Agenda';
?>

    <div style="clear: both;"></div>
    <div style="width: 100%">
    <div class="notice notice-success">
        <div style="position:relative;margin-top:1em">
        <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top" style="float:left;">
            <input type="hidden" name="cmd" value="_donations" />
            <input type="hidden" name="business" value="KG9SDHM7VUP6Y" />
            <input type="hidden" name="currency_code" value="CHF" />
            <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" title="PayPal - The safer, easier way to pay online!" alt="Donate with PayPal button" />
            <img alt="" border="0" src="https://www.paypal.com/en_CH/i/scr/pixel.gif" width="1" height="1" />
        </form>
        <strong><?php _e(" $10, $20 or $50!", "ACSagendaManagerAdmin"); ?>
    <?php echo __("If you like this plugin, please donate to support development and maintenance.", "ACSagendaManagerAdmin"); ?>
    </strong>
</div>
        <p><?php echo __('This plugin is very simple, the only setting consists in how you want the agenda to appear. The defaul page is "Agenda" and it\'s created at activation. You can change the default page here, the one you choose will be created with the shortcode inside "[agenda /]" and the previous deleted.',
                    'ACSagendaManagerAdmin'); ?></p>
        <p><?php echo __('To be consinstent trough different themes, this plugin uses a his own template. It has been tested on different default themes and Renden as well, if you have rendening problems you can contact me:',
                    'ACSagendaManagerAdmin'); ?>
        <a href='https://www.linkedin.com/in/andrea-cristalli-72427213/' target='_blank'> Andrea</a></p>
    </div>
</div>
<div class="wrap">
    <form method="POST" enctype= "multipart/form-data">
        <input type="hidden" name="updated" value="true" />
        <?php wp_nonce_field( 'acs_agenda_manager_settings', 'acs_agenda_manager_settings_form' ); ?>
        <table class="form-table">
            <tbody>
                    <tr>
                        <th><label for="acsagendapage"><?php echo __('Page name for Agenda', 'ACSagendaManagerAdmin'); ?>:</label></th>
                        <td><input name="acsagendapage" id="acsagendapage" type="text" value="<?php echo $agendapage; ?>" class="regular-text" /></td>
                    </tr>
            </tbody>
        </table>
        <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __('Submit', 'ACSagendaManagerAdmin'); ?>">
        </p>
        </form>
    </div> 
    <div class="spinner2"></div>
    <script>
    jQuery(document).ready(function() {
    var $loading = jQuery('div.spinner2').hide();
             jQuery(document).on('click','#submit', function () {
                    $loading.show();
             }).ajaxStop(function () {
                 //   $loading.hide();
             });
    })
    </script>
       
<div style="clear: both;"></div>
<?php

    
    }
    public function ACS_agenda_handle_form() 
    {
        if(
            ! isset( $_POST['acs_agenda_manager_settings_form'] ) ||
            ! wp_verify_nonce( $_POST['acs_agenda_manager_settings_form'], 'acs_agenda_manager_settings' )
        ){ ?>
            <div class="error">
            <p><?php echo __('Sorry, your nonce is not correct. Please try again.', 'ACSagendaManagerAdmin'); ?></p>
            </div> <?php
            exit;
        } else {
            $acsagendapage = sanitize_text_field( $_POST['acsagendapage'] );
            $updated = [];
            $the_page = get_page_by_title( get_option( 'acsagendapage' ));
            wp_delete_post($the_page->ID,true);
            foreach ($_POST as $key => $value) {
                if (${"$key"} && ${"$key"} != '') {
                    update_option( $key, stripslashes(${"$key"}) );
                    array_push($updated, $key);
                }
            }
           $post = array(
          'comment_status' => 'closed',
          'ping_status' =>  'closed' ,
          'post_author' => 1,
          'post_date' => date('Y-m-d H:i:s'),
          'post_name' => $acsagendapage,
          'post_status' => 'publish' ,
          'post_title' => $acsagendapage,
          'post_type' => 'page',
          'post_content' => '[agenda /]',
          'page_template' => ABSPATH . 'wp-content/plugins/ACSagendaManager/themefiles/page-agenda.php'
          );  
          $the_page = get_page_by_title( $post['post_title'] );
          if (!$the_page) {
              wp_insert_post( $post, false );
          }
            ?>
                <div class="updated">
                    <p><?php echo __('The new setting has been updated', 'ACSagendaManagerAdmin'); ?></p>
                </div> 
            <?php
        }
    }
    /** Singleton instance */
    public static function get_instance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

}

add_action('plugins_loaded', function ()
{
    ACSagendaOptions_Plugin::get_instance();
});
?>
