<?php
/*
Plugin Name: ACSagendaManager plugin
Plugin URI:
Description: This plugin is oriented to people who organize workshops of any kind 
Version: 2.1.1
@license           GPL-2.0-or-later
Author: Andrea Cristalli
Author URI: https://github.com/Esysc
*/

/*
 This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
defined( 'ABSPATH' ) or die( 'Nope, not accessing this' ); 
if (!defined('ACS_AGENDA_MANAGER_PLUGIN_VERSION'))
    define('ACS_AGENDA_MANAGER_PLUGIN_VERSION', '2.1.1');



function wpsx_redefine_locale() {  
    $browserlang = substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2);
    if ($browserlang == 'fr') {
        $browserlang = 'fr_FR.UTF-8';
        setlocale(LC_TIME, "");
        setlocale(LC_TIME, $browserlang);
    } else {
        $browserlang = "en_US";
    }
    return $browserlang;
}
add_filter('locale','wpsx_redefine_locale');
    
function acsagendamanager_plugin_init() {
  load_plugin_textdomain( 'ACSagendaManager', false, 'ACSagendaManager/lang/'  );
  load_plugin_textdomain( 'ACSagendaManagerAdmin', false, 'ACSagendaManager/lang/'  );
}
add_action('init', 'acsagendamanager_plugin_init');

function ACSagendaManager_enqueue_styles() {
    wp_enqueue_style('acs.css', plugins_url('/css/acs.css', __FILE__));
    wp_enqueue_style( 'jquery-ui.css', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
    wp_enqueue_style('thickbox');
}


function ACSagendaManager_scripts() {
        wp_enqueue_script('jquery');
        wp_register_script('mdp', plugins_url('/js/multidatespicker.js', __FILE__),array('jquery-ui-datepicker'),'1',TRUE);
        wp_enqueue_script('jquery-ui-dialog');
        wp_register_script('sca', plugins_url('/js/acs.js', __FILE__));     
        wp_enqueue_script('sca'); 
        wp_enqueue_script('mdp'); 
        wp_enqueue_script('thickbox');
}
add_action('wp_enqueue_scripts', 'ACSagendaManager_scripts');
add_action('wp_enqueue_scripts', 'ACSagendaManager_enqueue_styles');
add_action('admin_enqueue_scripts', 'ACSagendaManager_scripts');
add_action('admin_enqueue_scripts', 'ACSagendaManager_enqueue_styles');
function my_enqueue_media_lib_uploader() {
if ( ! did_action( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }
}
add_action('admin_enqueue_scripts', 'my_enqueue_media_lib_uploader');


function cmp($a, $b)
{
    $adate = strtotime(str_replace('/', '-', $a['date'][0]));
    $bdate = strtotime(str_replace('/', '-', $b['date'][0]));
    return $adate >= $bdate;
}

function agenda ($atts, $content = null) {
require_once(ABSPATH . 'wp-load.php');
global $wpdb;
  $ajaxUrl = admin_url( 'admin-ajax.php' );
  $table_name = $wpdb->prefix . 'acs_agenda_manager';
  $result = $wpdb->get_results("SELECT * FROM $table_name");
  $pages = []; 
  $now = strtotime('now');
$elements = $wpdb->get_col( "DESC " . $table_name, 0 );
if (($key = array_search('id', $elements)) !== false) {
    unset($elements[$key]);
}
$key = 0;
foreach ($result as $value) {
    foreach ($elements as $element) {
        if(!is_null($value->$element) || $value->$element != '') {
            if ($element == 'date') {
                $dates = explode(',', $value->$element);
                foreach ($dates as $index => $date) {
                    $pages[$key][$element][$index] = $date;
                }
            } else {
                $pages[$key][$element] = $value->$element;
            }
        }
    }
    $key++;
}
/* Remove expired events */
foreach ($pages as $key => $page) {
    $numberOfDates = count($page['date']);
    $lastEventSecs = strtotime(str_replace('/', '-',$page['date'][$numberOfDates-1])) ; 
    if ( $lastEventSecs + 24*60*60 < $now) {
        unset($pages[$key]);
    } else {
        foreach($page['date'] as $index => $eventDay) {
            $eventScheduleSecs = strtotime(str_replace('/', '-',$eventDay)) ; 
            if ( $eventScheduleSecs + 24*60*60 < $now) { //keep event until today (add one day in seconds)
                if ($page['candopartial'] != 1 && $page['candopartial'] != 2) {
                    unset($pages[$key]);
                } elseif ($page['candopartial'] == 1) {
                    unset($pages[$key]['date'][$index]);
                }
                elseif ($page['candopartial'] == 2) { //Add a notice for running event
                    $pages[$key]['today'] = __("Running", 'ACSagendaManager');
                }
            }
            elseif ( $eventScheduleSecs < $now && $page['date'][0] == $eventDay) { //Add a notice for today event
                $pages[$key]['today'] = __("Today", 'ACSagendaManager'); 
            }
        }
    }
}
$pages = array_values ($pages);
/* Reorder events by date */

usort($pages, "cmp");
/* Parse the content as parameters */
if ( is_user_logged_in() ) { // Only show the output below if a user is logged in
    $admin_link  = esc_url( admin_url() ); // Escaped admin link
    if ( current_user_can( 'manage_options' ) ) { // Current user is an admin, show the admin link
        echo '<h3><a href="' . $admin_link . 'admin.php?page=agenda" class="button4 warning">Agenda Administration</a></h3>';
    }
}
$return = '';
$return .= "<div class='container-agenda'>";
$months = ["start"];
for ($m=1; $m<=12; $m++) {
     $month = strftime('%B', mktime(0,0,0,$m, 1, date('Y')));
     array_push($months, $month) ;
}

foreach ($pages as $key => $page) {
    $return .= "<div class='acsagenda'>";
    $return .= '<div class="column-left">';
    $elementDate = "";
    $datesQty = count($page['date']);
    $years = array();
    foreach ($page['date'] as $i => $date) {
        $tmp = explode('/', $date);
        $years[$i] = $tmp[2];
        $blink = '';$today='';
        $eventScheduleSecs = strtotime(str_replace('/', '-',$date)) ;
        if ($eventScheduleSecs < $now  && $eventScheduleSecs + 24*60*60 > $now ) {
             $blink = 'blink_me';
        } elseif ( $eventScheduleSecs < $now && $page['candopartial'] == 2  ) {
            if ($i < $datesQty -1 && $datesQty ==  2) {
                $today = date('d/m/Y');
                if ($today == $page['date'][$i+1]) {
                    $today = '';
                }
            }
             $blink = 'acsagendaexpired';
        }
        
        $humanDate =  strftime("%A", mktime(0, 0, 0, $tmp[1], $tmp[0], $tmp[2]));
        $elementDate .= "<span class='ACSdate $blink'>";
        $day = $tmp[0];
        $month = $months[(int)$tmp[1]];
        $elementDate .= "<span class='month'>$month</span>";
        $elementDate .= "<span class='day'>$day</span>";
        $elementDate .= "<span class='week'>$humanDate</span>";
        $elementDate .= "</span>";
        if ($today !== '') {
            $blink = 'blink_me';
            $tmp = explode('/', $today);
            $humanDate =  strftime("%A", mktime(0, 0, 0, $tmp[1], $tmp[0], $tmp[2]));
            $elementDate .= "<span class='ACSdate $blink'>";
            $day = $tmp[0];
            $month = $months[(int)$tmp[1]];
            $elementDate .= "<span class='month'>$month</span>";
            $elementDate .= "<span class='day'>$day</span>";
            $elementDate .= "<span class='week'>$humanDate</span>";
            $elementDate .= "</span>";
        }
    }
    /* Default value for year */
    $year = $years[0];
    if ( $datesQty ==  2 && $years[0] != $years[1]) {
        $year = "$years[0] - $years[1]".PHP_EOL;
    }
    $postid = url_to_postid( $page['link'] );
    $return .= '<div class="acsAgenda-title"><h4 style="color: white; font-weight:bold;padding-top: 0.4em;">'.$year.'</h4></div>'; 
    $return .= '<div class="column-left-container">';
    $return .= $elementDate;
    $return .= '</div>';
    $return .= '<div class="placement">';
    $return .= "<h5 style='padding-top:1em'><span class='dashicons dashicons-location'></span>&nbsp;".$page['emplacement']."</h5>";
    $return .= "</div>";
    $return .= "</div>";
    $return .= '<div class="column-center" id="section-'.$key.'">';
    $today = $page['today']?"<span class='blink_me'>".$page['today'].":&nbsp;</span>":"";
    $return .= "<div class='acsAgenda-title'><h4 style='color: white; font-weight:bold;padding-top: 0.4em;' >$today ".$page['categorie']."</h4></div>";
    $return .= "<h3 style='text-align:center;'>".$page['title']."</h3>";
    $return .= "<div class='course-description acsAgenda'>";
    $return .= "<h5>".$page['intro']."</h5>";
    $return .= "<button data-href='".$page['link']."' class='readmore show' data-postid='$postid' data-id='section-$key'>".__("Read more", 'ACSagendaManager')."</button>";
    $return .= "</div></div>";
    $return .= '<div class=column-right>';
    $return .= "<img src='".$page['image']."'  class='image-agenda' alt='".$page['image']."'/>";
    $return .= "</div>";
    $ACScontactform = '[ACScontactform dates="'.implode(',',$page['date']).'" subject="'.$page['title'].'" price="'.$page['price'].'" account="'.$page['account'].'" candopartial="'.$page['candopartial'].'" redirect="'.$page['redirect'].'"]';
    if (shortcode_exists('ACScontactform')) {
        $return .= do_shortcode($ACScontactform);
    }
    $return .= "</div>";
}
$return .= "</div>";
$return .= '<div id="postid"></div>';
//Add some javascript here
$ajaxUrl = admin_url( 'admin-ajax.php' );
$return .=<<<EOT
    <script>
    var id
    jQuery(document).on('click', '.readmore', function(e) {
    //return
        e.preventDefault()
        var ajaxurl = '$ajaxUrl';
        var postid = jQuery(this).data('postid')
        id = jQuery(this).data('id')
        var data = {
        'action': 'read_more',
        'postid': postid,
        'href': jQuery(this).data('href')
        };
        jQuery.post(ajaxurl, data, function(response) {
            jQuery('#postid').html(response)
            showDialog()
        })
    })
    const showDialog = () => {
  document.getElementById('dialog').classList.add('shown')
  const scrollY = document.documentElement.style.getPropertyValue('--scroll-y');
  const body = document.body;
  body.style.position = 'absolute';(function ($) {
  // initalise the dialog
  $('#my-dialog').dialog({
    title: 'My Dialog',
    dialogClass: 'wp-dialog',
    autoOpen: false,
    draggable: false,
    width: 'auto',
    modal: true,
    resizable: false,
    closeOnEscape: true,
    position: {
      my: "center",
      at: "center",
      of: window
    },
    open: function () {
      // close dialog by clicking the overlay behind it
      $('.ui-widget-overlay').bind('click', function(){
        $('#my-dialog').dialog('close');
      })
    },
    create: function () {
      // style fix for WordPress admin
      $('.ui-dialog-titlebar-close').addClass('ui-button');
    },
  });

  // bind a button or a link to open the dialog
  $('a.open-my-dialog').click(function(e) {
    e.preventDefault();
    $('#my-dialog').dialog('open');
  });
})(jQuery);
  body.style.top = "-"+scrollY;
};
const closeDialog = () => {
  const body = document.body;
  const scrollY = body.style.top;
  body.style.position = '';
  body.style.top = '';
  window.scrollTo(0, parseInt(scrollY || '0') * -1);
  document.getElementById('dialog').classList.remove('shown');
  var elmnt = document.getElementById(id);
  elmnt.scrollIntoView();
}
window.addEventListener('scroll', () => {
  document.documentElement.style.setProperty('--scroll-y', window.scrollY+'px');
});
    </script>
EOT;
    return $return;
}

add_shortcode ('agenda', 'agenda');

// for the admin area
add_action( 'wp_ajax_read_more', 'callback_read_more' );
// for the public area
add_action( 'wp_ajax_nopriv_read_more', 'callback_read_more' );

function callback_read_more() {
    require_once(ABSPATH . 'wp-load.php');
    $postid = intval( $_POST['postid'] );
    $href = $_POST['href'];
    $the_query  = new WP_Query(array('p' => $postid));
    if ($the_query->have_posts()) {
        while ( $the_query->have_posts() ) {
            $the_query->the_post();

            $data = '
            <div id="dialog">
  <button id="close"  onClick="closeDialog()">&times;</button>
  <h2 style="text-align: center;">'.get_the_title().'</h2>
  <p style="text-align: center;"><a href="'.$href.'" target="_blank">Aller sur la page</a></p>
  '.do_shortcode(get_the_content()).'
</div>
               
            ';

        }
    } 
    else {
        echo '<div id="postdata">'.__('Didnt find anything', 'ACSagendaManager').'</div>';
    }
    wp_reset_postdata();


    echo '<div id="postdata">'.$data.'</div>';
}
function create_plugin_database_table()
{
    global $table_prefix, $wpdb;
    $tblname = 'acs_agenda_manager';
    $wp_ACSagendaManager_table = $table_prefix . "$tblname ";
    #Check to see if the table exists already, if not, then create it
    if($wpdb->get_var( "show tables like '$wp_ACSagendaManager_table'" ) != $wp_ACSagendaManager_table) 
    {
        $sql = "CREATE TABLE ". $wp_ACSagendaManager_table . " ( ";
        $sql .= "  id  int(11)   NOT NULL auto_increment, ";
        $sql .= "  categorie  VARCHAR(120) NOT NULL, ";
        $sql .= "  title  VARCHAR(120) NOT NULL, ";
        $sql .= "  emplacement  VARCHAR(120) NOT NULL, ";
        $sql .= "  image  VARCHAR(120) NOT NULL, ";
        $sql .= "  intro  VARCHAR(1200) NOT NULL, ";
        $sql .= "  link  VARCHAR(120) NOT NULL, ";
        $sql .= "  date  VARCHAR(120) NOT NULL, ";
        $sql .= "  price  VARCHAR(60)  NULL, ";
        $sql .= "  account  BOOLEAN DEFAULT TRUE, ";
        $sql .= "  candopartial  BOOLEAN DEFAULT FALSE, ";
        $sql .= "  redirect  VARCHAR(120) NULL, ";
        $sql .= "  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP , ";
        $sql .= "  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, ";
        $sql .= "  PRIMARY KEY agendaid (id) "; 
        $sql .= ") ; ";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        $success = empty($wpdb->last_error);
        return $success;
    }
}
function acs_agenda_manager_plugin_update() {
    global $table_prefix, $wpdb;
    $tblname = 'acs_agenda_manager';
    $wp_ACSagendaManager_table = $table_prefix . "$tblname ";
    $existing_columns = $wpdb->get_col("DESC {$wp_ACSagendaManager_table}", 0);
    $db_structure = array(
                          'id' => array('type' => 'int(11) NOT NULL AUTO_INCREMENT'),
                          'categorie' => array('type' =>'varchar(120) NOT NULL'),
                          'title' => array('type' =>'varchar(120) NOT NULL'),
                          'emplacement' => array('type' =>'varchar(120) NOT NULL'),
                          'image' => array('type' =>'varchar(120) NOT NULL'),
                          'intro' => array('type' =>'varchar(1200) NOT NULL'),
                          'link' => array('type' =>'varchar(1200) NOT NULL'),
                          'date' => array('type' => 'varchar(1200) NOT NULL'),
                          'price' => array('type' =>'varchar(60) DEFAULT NULL'),
                          'account' => array('type' => "tinyint(1) DEFAULT '1'"),
                          'candopartial' => array('type' => "tinyint(1) DEFAULT '0'"),
                          'created_at' => array('type' =>'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP'),
                          'updated_at' => array('type' =>'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')
                          );
    foreach ( $existing_columns as $column) {
        unset ($db_structure[$column]);
    }
    $success = true;
    if (count($db_structure) > 0) {
        $update = array();
        foreach ($db_structure as $key => $value) {
            $update[] = "ADD COLUMN ${key} ".$value['type'];
        }
        $sql = "ALTER TABLE ${wp_ACSagendaManager_table} ". implode(', ', $update) . ";";
        require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
            dbDelta($sql);
            $success = empty($wpdb->last_error);
            if ($success)
                update_option('acs_agenda_manager_plugin_version', ACS_AGENDA_MANAGER_PLUGIN_VERSION);
    }
        return $success;
}

function my_plugin_ACS_agenda_install_function()
  {
   update_option('acs_agenda_manager_plugin_version', ACS_AGENDA_MANAGER_PLUGIN_VERSION);
   //check if a previous installatio exists
   $pageagenda = get_option('acsagendapage')?get_option('acsagendapage'):'Agenda';
    $post = array(
          'comment_status' => 'closed',
          'ping_status' =>  'closed' ,
          'post_author' => 1,
          'post_date' => date('Y-m-d H:i:s'),
          'post_name' => $pageagenda,
          'post_status' => 'publish' ,
          'post_title' => $pageagenda,
          'post_type' => 'page',
          'post_content' => '[agenda /]',
          'page_template' => ABSPATH . 'wp-content/plugins/ACSagendaManager/themefiles/page-agenda.php'
    );  
     $the_page = get_page_by_title( $pageagenda );
    if (! $the_page) {
    wp_insert_post( $post, false );
    update_option( 'acsagendapage', $pageagenda );
    } 
  }
 register_activation_hook( __FILE__, 'create_plugin_database_table' );
 register_activation_hook( __FILE__, 'my_plugin_ACS_agenda_install_function' );
 function acs_agenda_manager_check_version() {
    if (ACS_AGENDA_MANAGER_PLUGIN_VERSION !== get_option('acs_agenda_manager_plugin_version'))
        acs_agenda_manager_plugin_update();
}

add_action('plugins_loaded', 'acs_agenda_manager_check_version');
include_once('class/acsadminagenda.php');

//Template fallback
add_action("template_redirect", 'ACS_agenda_redirect');

function ACS_agenda_redirect() {
    global $wp;
    $plugindir = dirname( __FILE__ );
    $templatefilename = 'page-agenda.php';
    $return_template = $plugindir . '/themefiles/' . $templatefilename;
    do_theme_redirect($return_template);
    print_r($wp->query_vars);
}

function do_theme_redirect($url) {
    global $post, $wp_query;
    if (have_posts()) {
        include($url);
        die();
    } else {
        $wp_query->is_404 = true;
    }
}
include_once('class/acsagendaoptions.php');
?>
