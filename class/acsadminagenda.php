<?php
ob_start();
if (!class_exists('WP_List_Table'))
{
    require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class agendaManager extends WP_List_Table
{
    /** Class constructor */
    public function __construct()
    {

        parent::__construct(['singular' => __('title', 'sp') , //singular name of the listed records
        'plural' => __('titles', 'sp') , //plural name of the listed records
        'ajax' => false
        //does this table support ajax?
        ]);

    }

    /**
     * Retrieve users data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_agendas($per_page = 5, $page_number = 1)
    {
        global $wpdb;
        $sql = "SELECT * FROM {$wpdb->prefix}acs_agenda_manager";
        if( ! empty( $_REQUEST['s'] ) ){
            $search = esc_sql( $_REQUEST['s'] );
            $sql .= " WHERE categorie LIKE '%{$search}%'
                      OR title LIKE '%{$search}%'
                      OR intro LIKE '%{$search}%'
                      OR date LIKE '%{$search}%'";
        }
        if( !empty($_REQUEST['event-filter']  )){
            $eventfilter = esc_sql($_GET['event-filter']);
            $filters = explode ('-', $eventfilter);
            $sql .= " WHERE `title` LIKE '%{$filters[0]}%'";
        }

        if (!empty($_REQUEST['orderby']))
        {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        } else {
            $sql .= ' ORDER BY id DESC';
        }
        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;
        $result = $wpdb->get_results($sql, 'ARRAY_A');
        $count = count($result);
        return array("values" => $result, "count" => $count);
    }
        
    /**
     * Delete a customer record.
     *
     * @param int $id user ID
     */
    public static function delete_entry($id)
    {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}acs_agenda_manager", ['id' => $id], ['%d']);
        $_REQUEST['submitAjaxreturn'] = '<div class="notice notice-success is-dismissible"><p>'.__('The event(s) has/have been definitevely removed!','ACSagendaManagerAdmin').'</p></div>';
    }
    /** Text displayed when no customer data is available */
    public function no_items()
    {
        __('No entry avaliable.', 'ACSagendaManagerAdmin');
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function my_extra_tablenav( $which) {
        global $wpdb;
        if ( $which == "top" ){
            ?>
            <div class="tablenav top">
            <div class="alignleft actions bulkactions">
            <?php
            $items = $wpdb->get_results("select distinct title,categorie from {$wpdb->prefix}acs_agenda_manager order by title asc", ARRAY_A);
            if( $items ){
                ?>
                <select name="event-filter" class="ewc-filter-event" onchange="this.form.submit()">
                    <option value=""><?php __('Select the event'); ?></option>
                    <?php
                    foreach( $items as $item ){
                        $selected = '';
                        if( $_REQUEST['event-filter'] == esc_attr($item['title']."-".$item['categorie']) ){
                            $selected = 'selected';
                        }
                    ?>
                    <option value="<?php echo $item['title']."-".$item['categorie']; ?>" <?php echo $selected; ?>><?php echo $item['title']." - ".$item['categorie']; ?></option>
                    <?php
                    }
                    ?>
                </select>
                <span class="displaying-num span-filter-event"></span>
                <?php   
            }
            ?>  
            </div>
            </div>
            <?php
        }
        if ( $which == "bottom" ){
            //Code for bottom
        }
    }
        
    public function column_default($item, $column_name)
    {
        $options1 = array(__('No', 'ACSagendaManagerAdmin'),__('Yes','ACSagendaManagerAdmin'),__('Keep event until the end','ACSagendaManagerAdmin'));
        $options2 = array(__('No', 'ACSagendaManagerAdmin'),__('Yes','ACSagendaManagerAdmin'));
        $nameattr = "data-name='$column_name'";
        switch ( $column_name ) {
        	case 'candopartial':
                    return "<span class='origItem_".$item['id']."' $nameattr>".$options1[$item[$column_name]]."</span>";
                case 'intro':
                    return "<a class='read_more button4 info'>".__('Read more','ACSagendaManagerAdmin')."</a><a class='button4 info hide_more' style='display:none'>".__('Hide','ACSagendaManagerAdmin')."</a></br><span class='fullcontent origItem_".$item['id']."' style='display:none' $nameattr>".$item[$column_name]."</span>";
                case 'account':
                    $options = '';
                    return "<span class='origItem_".$item['id']."' $nameattr>".$options2[$item[$column_name]]."</span>";
                case 'image':
                    return "<span style='display:none;' class='origItem_".$item['id']."' $nameattr >".$item[$column_name]."</span><img src='".$item[$column_name]."' style='width: 100%;height:auto;' />";
                case 'link':
                    return "<span style='max-width:100%;' class='origItem_".$item['id']."' $nameattr>".$item[$column_name]."</span>
                            <a href='".$item[$column_name]."' target='_blank' class='button4 info'>".__('Open the page','ACSagendaManagerAdmin')."</a>";
        	default:
                    return "<span style='max-width:100%;' class='origItem_".$item['id']."' $nameattr>".$item[$column_name]."</span>";
        }
        
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']);
    }

    /**
     * Method for categorie column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_categorie($item)
    {
        $my_nonce = wp_create_nonce('sp_manage_agenda');
        $name = '<strong id="categorie'.$item['id'].'" class="origItem_'.$item['id'].'" data-name="categorie">' . $item['categorie'] . '</strong>';
        $actions = ['delete' => sprintf('<a class="ACSdelete button4 danger" href="?page=%s&action=%s&id=%s&_wpnonce=%s">'.__('Delete','ACSagendaManagerAdmin').'</a>', esc_attr($_REQUEST['page']) , 'delete', absint($item['id']) , $my_nonce) ,
                    'edit' =>  '<a href="#" data-id="'.$item['id'].'" class="editItems button4 info">'.__('Edit','ACSagendaManagerAdmin').'</a>'
                   ];
        if (shortcode_exists('ACScontactform')) {
            $ACScontactform = '[ACScontactform dates="'.$item['date'].'" subject="'.$item['title'].'" price="'.$item['price'].'" account="'.$item['account'].'" candopartial="'.$item['candopartial'].'" redirect="'.$item['redirect'].'"]';
            $shortcode = "<div id='shortcode".$item['id']."' style='display:none'>
                                 <h2>".__('Shortcode for contact form','ACSagendaManagerAdmin')."</h2>
                                 <div class='alert alert-success' id='MSGWrappershortcodeText".$item['id']."' style='display:none'>
                                        <span class='screen-reader-text'>".__('Dismiss this notice','ACSagendaManagerAdmin')."</span>
                                    <p id='ACSmessageshortcodeText".$item['id']."'></p>
                                 </div>
                                <p><textarea id='shortcodeText".$item['id']."' disabled>$ACScontactform</textarea></p>
                                <p><button class='button4 info' onclick='clipboard(\"shortcodeText".$item['id']."\")'>".__('Copy!','ACSagendaManagerAdmin')."</button>
              </div>";
              $actions['shortcode'] = "<a href='#TB_inline?width=400&height=250&inlineId=shortcode".$item['id']."' class='thickbox button4 info'>".__('Form shortcode','ACSagendaManagerAdmin')."</a>";
        }
        return $name .$shortcode . str_replace('|','',$this->row_actions($actions));
    }
    
    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        $columns = ['cb' => '<input type="checkbox" />', 
                    'categorie' => __('Category', 'ACSagendaManagerAdmin') , 
                    'title' => __('Title', 'ACSagendaManagerAdmin') , 
                    'emplacement' => __('Emplacement', 'ACSagendaManagerAdmin') ,
                    'image' => __('Image', 'ACSagendaManagerAdmin') ,
                    'intro' => __('Description', 'ACSagendaManagerAdmin') ,
                    'link' => __('Full Page Link', 'ACSagendaManagerAdmin') ,
                    'date' => __('Schedule', 'ACSagendaManagerAdmin') ,
                    'price' => __('Price', 'ACSagendaManagerAdmin') ,
                    'account' => __('Advance Payment', 'ACSagendaManagerAdmin') ,
                    'candopartial' => __('May be one day only', 'ACSagendaManagerAdmin') ,
                    'redirect' => __('External url', 'ACSagendaManagerAdmin'),
                    'created_at' => __('Created', 'ACSagendaManagerAdmin'),
                    'updated_at' => __('Updated', 'ACSagendaManagerAdmin')
        ];

        return $columns;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'categorie' => array(
                'categorie',
                true
            ) ,
            'title' => array(
                'title',
                false
            ) ,
            'date' => array(
                'date',
                false
            ) ,
            'price' => array(
                'price',
                false
            ),
            'created_at' => array(
                'created_at',
                false
            ),
            'updated_at' => array(
                'updated_at',
                false
            ) 
        );

        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = ['bulk-delete' => 'Delete'];

        return $actions;
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {
        $this->_column_headers = $this->get_column_info();
        /** Process bulk action */
        $this->process_bulk_action();
        $per_page = $this->get_items_per_page('agenda_per_page', 10);
        $current_page = $this->get_pagenum();
        $agendas = self::get_agendas($per_page, $current_page);
        $this->items = $agendas["values"];
        $total_items = $this->items["count"];
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
        ]);
        $this->search_box( __( 'Search...', 'ACSagendaManagerAdmin' ),'agendaSearch');
        $this->my_extra_tablenav('top');
    }

    public function process_bulk_action()
    {
        //Detect when a bulk action is being triggered...
        $pluginUrl = explode('&', esc_url_raw(add_query_arg())) [0];
        if ('delete' === $this->current_action())
        {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr($_REQUEST['_wpnonce']);
            if (!wp_verify_nonce($nonce, 'sp_manage_agenda'))
            {
                die('Go get a life script kiddies');
            }
            else
            {
                self::delete_entry(absint($_GET['id']));
                wp_redirect($pluginUrl);
                exit;
            }
        }
        // If the delete bulk action is triggered
        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete') || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete'))
        {
            if (!isset($_POST['bulk-delete'])) {
                return;
            }
            $delete_ids = esc_sql($_POST['bulk-delete']);
            // loop over the array of record IDs and delete them
            foreach ($delete_ids as $id)
            {
                self::delete_entry($id);
            }
            wp_redirect($pluginUrl);
            exit;
        }
    }
}
    
    
class AGENDA_Plugin
{

    // class instance
    static $instance;

    // customer WP_List_Table object
    public $agendas_obj;

    // class constructor
    public function __construct()
    {
        add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        add_action('admin_menu', [$this, 'plugin_menu']);
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    public function plugin_menu()
    {
        $hook = add_menu_page('Agenda',
                              'Agenda',
                              'manage_options', 
                              'agenda', 
                              [$this, 'agendaAdminPage'], 
                              'dashicons-calendar-alt');
        add_action("load-$hook", [$this, 'screen_option']);
    }
    
    /**
     * Inscriptions settings page
     */
    public function agendaAdminPage()
    {
    $columns = $this->agendas_obj->get_columns();
    //$total_items = $this->record_count();
    $options1 = array(__('No','ACSagendaManagerAdmin'),__('Yes','ACSagendaManagerAdmin'),__('Keep event until the end','ACSagendaManagerAdmin'));
    $options2 = array(__('No','ACSagendaManagerAdmin'),__('Yes','ACSagendaManagerAdmin'));
?>
<div class="wrap">
   <h2><a href="/?page_id=<?php echo get_page_by_title( get_option('acsagendapage') )->ID; ?>" class="button4 warning"><?php echo __('Go to Agenda','ACSagendaManagerAdmin'); ?></a></h2>
   <div id="ajaxmessage"></div>
   <div id="poststuff">
      <div id="post-body" class="metabox-holder">
         <div id="post-body-content">
            <div class="widget-control-actions">
                <div class="alignleft"><h1><button class='button4 info addItem'><?php echo __('Agenda | Add','ACSagendaManagerAdmin'); ?></button></h1></div>
                <div class="alignright">
                    <table class='widefat' id="ACSagendahelp" style='display:none;'>
                    <thead><tr><th colspan=2 style="text-align:center"><h3><?php echo __('Instructions', 'ACSagendaManagerAdmin'); ?></h3></th></tr></thead>
                    <tbody>
                        <tr><th colspan=2 style="text-align:center"><?php echo __("You can add an event clicking on add button, a popup form will appear. You can always edit your event and delete if you don't need anymore.", 'ACSagendaManagerAdmin'); ?></th></tr>
                        <tr>
                            <th><b><?php echo __('Category','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"><?php echo __("This is how you want to categorize your event. The category will be the first top field shown in the widget title (middle column).", 'ACSagendaManagerAdmin'); ?></td>
                        <tr>
                        <tr>
                            <th><b><?php echo __('Title','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"><?php echo __("The event title shown in the middle column.", 'ACSagendaManagerAdmin'); ?></td>
                        <tr>
                        <tr>
                            <th><b><?php echo __('Emplacement','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"><?php echo __("Where the event will be. It will be shown in the left column.", 'ACSagendaManagerAdmin'); ?></td>
                        <tr>
                        <tr>
                            <th><b><?php echo __('Image','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"><?php echo __("An image to associate to the event, shown in the right column.", 'ACSagendaManagerAdmin'); ?></td>
                        <tr>
                        <tr>
                            <th><b><?php echo __('Description','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"><?php echo __("The event introduction, something short that introduce the event.", 'ACSagendaManagerAdmin'); ?></td>
                        <tr>
                        <tr>
                            <th><b><?php echo __('Full Page Link','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"><?php echo __("An internal/external link to the page event.", 'ACSagendaManagerAdmin'); ?> </td>
                        <tr>
                        <tr>
                            <th><b><?php echo __('Schedule','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"><?php echo __("The event scheduling. May be one day, a range between two dates, several single dates.", 'ACSagendaManagerAdmin'); ?></td>
                        <tr>
                        <tr>
                            <th><b><?php echo __('Price','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"> * <?php echo __("The price to participate. You can insert only numbers, only string, both of them. This field is consumed by ACScontactform plugin to build the inscription form.", 'ACSagendaManagerAdmin'); ?></td>
                        <tr>
                        <tr>
                            <th><b><?php echo __('Advance Payment','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"> * <?php echo __("You can set if you accept advance payment or not. This field is consumed by ACScontactform plugin to build the inscription form.", 'ACSagendaManagerAdmin'); ?></td>
                        <tr>
                        <tr>
                            <th><b><?php echo __('May be one day only','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"><?php echo __("In the case of multiple days, if set to NO the event will disappear the day after the beginning automatically.<br />If set to YES, the event will be always shown until expiration, but each day already expired will be removed from view.<br />If set to the third option, all the days will be shown until expiraion of event.", 'ACSagendaManagerAdmin'); ?></td>
                        <tr>
                        <tr>
                            <th><b><?php echo __('External url','ACSagendaManagerAdmin'); ?>:</b></th><td style="text-align:left"> * <?php echo __("This a reference page to an event owned by somebody else. This field make sense if you installed ACScontactform plugin for inscriptions.", 'ACSagendaManagerAdmin'); ?></td>
                        <tr>
                    </tbody>
                </table>
                <div style="display:inline-block"><a href='#TB_inline?width=800&height=600&inlineId=ACSagendahelp' class='thickbox button4 info' ><?php echo __('Help','ACSagendaManagerAdmin'); ?></a></div>
        </div>
            <div class="meta-box-sortables ui-sortable">
               <form method="post" id="ACSMainForm">
                  <?php
                     $this
                         ->agendas_obj
                         ->prepare_items();
                     $this
                         ->agendas_obj
                         ->display(); ?>
               </form>
            </div>
         </div>
      </div>
      <br class="clear">
   </div>
</div>
<div id="ACSdialog" title="<?php echo __('Confirmation Required','ACSagendaManagerAdmin'); ?>">
  <?php echo __('Do you want really to delete','ACSagendaManagerAdmin'); ?><span id="title"></span> ?
</div>
<div class="spinner2"></div>
<script>
function clipboard(id) {
  var copyText = document.getElementById(id);
  copyText.select();
  copyText.setSelectionRange(0, 99999)
  document.execCommand("copy");
  document.getElementById('ACSmessage'+id).innerHTML = "<strong><?php echo __('Copied','ACSagendaManagerAdmin'); ?>!!:</strong>&nbsp;"+copyText.value
  document.getElementById('MSGWrapper'+id).style.display = "block"
}
jQuery(document).ready(function() {
    
    var $loading = jQuery('div.spinner2').hide();
    function submitAjaxreturn(message) {
        var form=jQuery('<form method="post" id="new-message-form"><input type="hidden" name="submitAjaxreturn" value="'+btoa(message)+'" /></form>')
        form.appendTo('body')
        form.submit();
    }
    var post = <?php echo json_encode($_REQUEST); ?>;
    if('submitAjaxreturn' in post) {
       jQuery('#ajaxmessage').html(atob(post['submitAjaxreturn'])); // display contents in .success
    }
    var formEdit = jQuery("<div> \
    <h4><?php echo $columns['categorie']; ?></h4><input class='col-editagenda' name='categorie' type='text' require /> \
    <h4><?php echo $columns['title']; ?></h4><input class='col-editagenda' name='title' type='text' require /> \
    <h4><?php echo $columns['emplacement']; ?></h4><input class='col-editagenda' name='emplacement' type='text' require /> \
    <h4><?php echo $columns['image']; ?></h4><input class='col-editagenda' name='image' type='text' require id='image001' /><button class='upload_image_button button4 info ' >Image</button> \
    <h4><?php echo $columns['intro']; ?></h4><textarea class='col-editagenda' name='intro'  require ></textarea> \
    <h4><?php echo $columns['link']; ?></h4><input class='col-editagenda' name='link' type='text' require /> \
    <h4><?php echo $columns['date']; ?></h4><input class='col-editagenda' name='date' type='text' require title='' id='date001' /> \
    <button class='button4 warning datepickerDestroy' id='datespickerDestroydate001' style='display:none'><?php echo __('Close','ACSagendaManagerAdmin'); ?></button> \
    <div class='date001 dates-container'></div> \
    <button class='button4 info dates-picker' data-id='date001'><?php echo __('Calendar','ACSagendaManagerAdmin'); ?></button> \
    <h4><?php echo $columns['price']; ?></h4><input class='col-editagenda' name='price' type='text' /> \
    <h4><?php echo $columns['account']; ?></h4><select class='col-editagenda' name='account'> \
    <option value='0'><?php echo __('No','ACSagendaManagerAdmin'); ?></option> \
    <option value='1'><?php echo __('Yes','ACSagendaManagerAdmin'); ?></option> \
    </select> \
    <h4><?php echo $columns['candopartial']; ?></h4><select class='col-editagenda' name='candopartial'> \
    <option value='0'><?php echo __('No','ACSagendaManagerAdmin'); ?></option> \
    <option value='1'><?php echo __('Yes','ACSagendaManagerAdmin'); ?></option> \
    <option value='2'><?php echo __('Keep event until the end','ACSagendaManagerAdmin'); ?></option> \
    </select> \
    <h4><?php echo $columns['redirect']; ?></h4><input class='col-editagenda' name='redirect' type='text' />\
    </div>")
    jQuery(document).on('click', ".datepickerDestroy", function(e) {
        e.preventDefault()
        jQuery(this).hide()
        var id = jQuery(this).attr('id').replace('datespickerDestroy','')
        jQuery('*[data-id="'+id+'"]').show();
        jQuery('.'+id).multiDatesPicker('destroy')
        jQuery('#'+id).val(jQuery('#'+id).val().replace(/\s/g,''))
   })
   jQuery(document).on('click', '.dates-picker', function(e) {
        e.preventDefault()
        jQuery(this).hide()
        var today = new Date();
        var id = jQuery(this).data('id')
        jQuery('#datespickerDestroy'+id).show()
        var date = jQuery('#'+id).val();
        var dates = date ? date.split(','):[] 
        var options = {dateFormat: "dd/mm/yy",
                       altField: '#'+id,
                       showButtonPanel: true,
                       onSelect: function() {
                                 jQuery('#'+id).prop('title', jQuery('.'+id).multiDatesPicker('getDates').join(','));jQuery('#'+id).trigger('change')
                                 },
                       separator: ','
                      }
        if (date) {
            options.defaultDate = dates[0]
            options.addDates = dates
        }
        setTimeout(function() {
            jQuery('.'+id).multiDatesPicker(options)
        }, 50)
   })
   jQuery(document).on("click", ".upload_image_button", function (e) {
        e.preventDefault();
        var $button = jQuery(this);
        // Create the media frame.
        var file_frame = wp.media.frames.file_frame = wp.media({
                    title: '<?php echo __('Select or upload an image','ACSagendaManagerAdmin'); ?>',
                    library: { // remove these to show all
                    type: 'image' // specific mime
                    },
                    button: {
                    text: 'Select'
                    },
                    multiple: false  // Set to true to allow multiple files to be selected
        });
        // When an image is selected, run a callback.
        file_frame.on('select', function () {
        // We set multiple to false so only get one image from the uploader
         var attachment = file_frame.state().get('selection').first().toJSON();
         $button.prev('input').val(new URL(attachment.url).pathname).change();
      });
      // Finally, open the modal
      file_frame.open();
   });                
   var elements = jQuery('#the-list tr').length
   var filterspan = jQuery('.span-filter-event')
   filterspan.text(elements + ' <?php echo __('Filtered events','ACSagendaManagerAdmin'); ?>')
    jQuery("#ACSdialog").dialog({
        autoOpen: false,
        modal: true
        });
    jQuery(document).on('click', '.ACSdelete', function(e) {
        e.preventDefault();
        var targetUrl = jQuery(this).attr("href");
        var urlParams = new URLSearchParams(targetUrl);
        var id = urlParams.get('id')
        var categorie = jQuery('#categorie'+id).text()
        jQuery('#categorie').text(categorie)
        jQuery("#ACSdialog").dialog({
            buttons : {
                "<?php echo __('Confirm','ACSagendaManagerAdmin'); ?>" : function() {
                    $loading.show();
                    window.location.href = targetUrl;
                    },
                "<?php echo __('Cancel','ACSagendaManagerAdmin'); ?>" : function() {
                    jQuery(this).dialog("close");
                    }
                }
        });
        jQuery("#ACSdialog").dialog("open");
    })
    jQuery(document).on('change', '.ewc-filter-event', function(){
        var filter = jQuery(this).val();
        if( filter != '' ){
            document.location.href = "admin.php?page=agenda&event-filter="+filter;
        } else {
            document.location.href = "admin.php?page=agenda"
        }
    });
    jQuery(document).on('click', '.read_more, .hide_more', function(e) {
        e.preventDefault();
        var eleClass=jQuery(this).attr('class')
        jQuery(this).toggle()
        if (eleClass.indexOf('read_more') >-1) {
            jQuery(this).nextAll('.hide_more:first').toggle()
        } else {
            jQuery(this).prev('.read_more:first').toggle()
        }
        jQuery(this).nextAll('.fullcontent:first').toggle()
    })
    jQuery(document).on('click', '.editItems', function(e) {
        e.preventDefault()
        e.stopPropagation()
        var editDiag = jQuery("<div id='editDialog' title='<?php echo __('Editor','ACSagendaManagerAdmin'); ?>'></div>")
        editDiag.append(formEdit)
        var item = jQuery(this)
        var id = item.data('id')
        var editClass ="origItem_"+id
        var items = jQuery('.'+editClass)
        jQuery.each(items, function(i,ele) {
            var name = jQuery(ele).data('name')
            var inputField = jQuery(formEdit).find(".col-editagenda[name='"+name+"']")
            if( inputField[0] && inputField[0].nodeName.toLowerCase() === 'select' ) {
                jQuery(inputField[0].options).filter(function() {
                    return jQuery(this).text().trim().toLowerCase() == jQuery(ele).text().trim().toLowerCase();
                }).attr('selected', true);
            } else {
                inputField.val(jQuery(ele).text())
            }
        })
    editDiag.dialog({
        width: '40%',
        maxHeight: window.innerHeight - 15,
	overflow:'scroll',
        open : function() {
            if (jQuery(this).closest('.ui-dialog').offset().top < 40) {
                jQuery(this).closest('.ui-dialog').css({'top':'40px'});
            }
        },
        buttons : {
            "<?php echo __('Update','ACSagendaManagerAdmin'); ?>" : function() {
                $loading.show();
                var body = {}
                var valid = true
                jQuery('span.danger').remove()
                jQuery.each(jQuery(formEdit).find(".col-editagenda"), function(k, item) {
                    if ((!jQuery(this).val() || jQuery(this).val().length == 0) && jQuery(this).attr('name') !== 'redirect' && jQuery(this).attr('name') !== 'price') {
                        jQuery(this).focus()
                        jQuery(this).after('<span class="button4 danger"><?php echo __('the field','ACSagendaManagerAdmin'); ?> "'+jQuery(this).attr('name')+'" <?php echo __('is empty','ACSagendaManagerAdmin'); ?>!</span>')
                        valid = false
                        return false
                    }
                    body[jQuery(this).attr('name')] = jQuery(this).val()
                });
                body.id=id
                body.action='update_agenda'
                if (valid) {
                    jQuery.ajax({
                        type:'POST',
                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                        data:body,
                        cache: false,
                        success: function(data) {
                            submitAjaxreturn(data)
                        },
                        error: function(err) {
                            submitAjaxreturn(err)
                        }
                    })
                }
            },
            "<?php echo __('Cancel','ACSagendaManagerAdmin'); ?>" : function() {
                jQuery(this).dialog("close");
                jQuery('.datepickerDestroy').trigger('click')
            }
        }
     })
    })
    jQuery(document).on('click', '.addItem', function(e) {
        e.preventDefault()
        e.stopPropagation()
        var editDiag = jQuery("<div id='AddDialog' title='<?php echo __('Add an event','ACSagendaManagerAdmin'); ?>'></div>")
        editDiag.append(formEdit)
        editDiag.dialog({
        width: '40%',
        maxHeight: window.innerHeight - 15,
	overflow:'scroll',
        open : function() {
            if (jQuery(this).closest('.ui-dialog').offset().top < 40) {
                jQuery(this).closest('.ui-dialog').css({'top':'40px'});
            }
        },
        buttons : {
            "<?php echo __('Add','ACSagendaManagerAdmin'); ?>" : function() {
                $loading.show();
                var body = {}
                var valid = true
                jQuery('span.danger').remove()
                jQuery.each(jQuery(formEdit).find(".col-editagenda"), function(k, item) {
                    if ((!jQuery(this).val() || jQuery(this).val().length == 0) && jQuery(this).attr('name') !== 'redirect' && jQuery(this).attr('name') !== 'price') {
                        jQuery(this).focus()
                        jQuery(this).after('<span class="button4 danger"><?php echo __('The field','ACSagendaManagerAdmin'); ?> "'+jQuery(this).attr('name')+'" <?php echo __('is empty','ACSagendaManagerAdmin'); ?>!</span>')
                        valid = false
                        $loading.hide();
                        return false
                    }
                    body[jQuery(this).attr('name')] = jQuery(this).val()
                });
                body.action='add_item_agenda'
                if (valid) {
                    jQuery.ajax({
                        type:'POST',
                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                        data:body,
                        cache: false,
                        success: function(data) {
                            submitAjaxreturn(data)
                        },
                        error: function(err) {
                            submitAjaxreturn(err)
                        }
                    })
                }
            },
            "<?php echo __('Cancel','ACSagendaManagerAdmin'); ?>" : function() {
                jQuery(this).dialog("close");
                jQuery('.datepickerDestroy').trigger('click')
            }
        }
     })
    })
})
</script>
	<?php
    }

    /**
     * Screen options
     */
    public function screen_option()
    {

        $option = 'per_page';
        $args = ['label' => 'titles', 'default' => 10, 'option' => 'agenda_per_page'];

        add_screen_option($option, $args);

        $this->agendas_obj = new agendaManager();
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
    
function my_enqueue_media_lib_uploader_agenda() {
if ( ! did_action( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }
}
    
add_action('admin_enqueue_scripts', 'my_enqueue_media_lib_uploader_agenda');
    
function callback_update_agenda() {
    global $wpdb;
    if (isset($_POST['id'])) {
        $data = [];
        $id = $_POST['id'];
        $table_name = "{$wpdb->prefix}acs_agenda_manager";
        foreach( $_POST as $key => $value ) {
            if ($key !== 'id' && $key!== 'pll_ajax_backend'&& $key !== 'action') {
                switch($value) {
                    case '1':
                        $tmp = 1;
                        break;
                    case '0':
                        $tmp = 0;
                        break;
                    case '2':
                        $tmp = 2;
                        break;
                    default:
                        $tmp = $value;
                        break;
                }
                $data[$key] = stripslashes($tmp);
            }
        }
        $wpdb->update($table_name, $data, array('id' => $id));
        if($wpdb->last_error !== '') {
            printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>',__('Sorry, the event has not been updated, an error occurred!','ACSagendaManagerAdmin'));
        } else {
            printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>',__('The event has been updated!','ACSagendaManagerAdmin'));
        }
        wp_die();
    }
}
    
function callback_add_item_agenda() {
    global $wpdb;
    if (isset($_POST['action']) && $_POST['action'] === 'add_item_agenda') {
        $data = [];
        $table_name = "{$wpdb->prefix}acs_agenda_manager";
        foreach( $_POST as $key => $value ) {
            if ( $key!== 'pll_ajax_backend'&& $key !== 'action') {
                $data[$key] = stripslashes($value);
            }
        }
        $wpdb->insert($table_name, $data);
        if($wpdb->last_error !== '') {
            printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>',__('Sorry, the event has not been added, an error occurred!','ACSagendaManagerAdmin'));
        } else {
            printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>',__('The event has been added!','ACSagendaManagerAdmin'));
        }
        wp_die();
    }
}   

add_action('plugins_loaded', function ()
{
    AGENDA_Plugin::get_instance();
});

add_action('wp_ajax_add_item_agenda', 'callback_add_item_agenda');
add_action('wp_ajax_update_agenda', 'callback_update_agenda');
