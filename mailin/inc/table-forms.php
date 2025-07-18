<?php
/**
 * Subscribe forms class
 */
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class SIB_Forms_List extends WP_List_Table {

    /**
     * Column headers for the table
     * @var array
     */
    public $_column_headers;

    /**
     * Items for the table
     * @var array
     */
    public $items;

    /** Class constructor */
    public function __construct() {

        parent::__construct(
            array('singular' => __( 'Form', 'mailin' ), //singular name of the listed records
            'plural'   => __( 'Forms', 'mailin' ), //plural name of the listed records
            'ajax'     => false) //does this table support ajax?
        );

        add_action( 'admin_head', array( &$this, 'admin_header' ) );

    }
    const PAGES = [
        'sib_page_form',
        'sib_page_home',
        'sib_page_statistics'
    ];
    /**
     * Retrieve contacts data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function getForms( $per_page = 5, $page_number = 1 ) {

        $result = SIB_Forms::getForms();
        $start = ( $page_number - 1 ) * $per_page;
        usort( $result, array(__CLASS__, 'usort_reorder' ) );
		$result = array_slice($result, $start, $per_page);
        return $result;
    }
    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count() {
        $result = SIB_Forms::getForms();
        return count($result);
    }
    /** Text displayed when no customer data is available */
    public function no_items() {
        _e( 'No forms avaliable.', 'mailin' );
    }
    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'id':
                return '[sibwp_form id='.$item['id'].']';
            case 'title':
            case 'attributes':
            case 'listName':
            case 'date':
                return $item[ $column_name ];
            //default:
                //return print_r( $item, true ); //Show the whole array for troubleshooting purposes
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb( $item ) {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />', $item['id']
        );
    }


    /**
     * Method for form title column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_title( $item ) {

        $delete_nonce = wp_create_nonce( 'sib_delete_form' );

        $title = '<strong>' . $item['title'] . '</strong>';
        $page = isset($_REQUEST['page']) && in_array(strtolower($_REQUEST['page']), self::PAGES) ? $_REQUEST['page'] : '';
        $actions = array(
            'edit' => sprintf( '<a href="?page=%s&action=%s&id=%s">Edit</a>', $page, 'edit', absint( $item['id'] ) ),
            'duplicate' => sprintf( '<a href="?page=%s&action=%s&id=%s">Duplicate</a>', $page, 'duplicate', absint( $item['id'] ) ),
            'delete' => sprintf( '<a class="sib-form-delete" href="?page=%s&action=%s&id=%s&_wpnonce=%s">Delete</a>', $page, 'delete', absint( $item['id'] ), $delete_nonce )
        );

        return $title . $this->row_actions( $actions );
    }

    function column_trans( $item ) {
        $languages = apply_filters('wpml_active_languages', NULL, array());

        $results = '';
        if(!empty( $languages ))
        {
            foreach($languages as $language)
            {
                $exist = SIB_Forms_Lang::get_form_ID($item['id'], $language['language_code']);
                $page = isset($_REQUEST['page']) && in_array(strtolower($_REQUEST['page']), self::PAGES) ? $_REQUEST['page'] : '';
                if($exist == null)
                {
                    $img_src = plugins_url('img/add_translation.png', dirname(__FILE__));

                    $href = sprintf( '<a href="?page=%s&action=%s&pid=%s&lang=%s" style="width: 20px; text-align: center;padding: 2px 1px;">', $page, 'edit', absint( $item['id'] ), sanitize_text_field( $language['language_code'] ) );
                    $results .= $href .'<img src="'.$img_src.'" style="margin:2px;"></a>';
                }
                else{
                    $img_src = plugins_url('img/edit_translation.png', dirname(__FILE__));
                    $href = sprintf( '<a href="?page=%s&action=%s&id=%s&pid=%s&lang=%s" style="width: 20px; text-align: center;padding: 2px 1px;">', $page, 'edit', absint( $exist ) , absint( $item['id']), sanitize_text_field( $language['language_code'] ) );
                    $results .= $href .'<img src="'.$img_src.'" style="margin:2px;"></a>';
                }

            }
        }
        return $results;
    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns() {
        $columns = array(
            'cb'      => '<input type="checkbox" />',
            'title' => __( 'Form Name', 'mailin' ),
            'id'    => __( 'Shortcode', 'mailin' ),
            'attributes'    => __( 'Visible attributes', 'mailin' ),
            'listName'    => __( 'Linked List', 'mailin' ),
            'date'    => __( 'Last Update', 'mailin' )
        );
        if(function_exists('icl_plugin_action_links'))
        {
            $languages = apply_filters('wpml_active_languages', NULL, array());

            $results = '';
            if(!empty( $languages ))
            {
                foreach($languages as $language)
                {
                    $results .= '<img src="'.$language['country_flag_url'].'" style="margin:2px;">';
                }
            }
            $columns['trans'] = $results;
        }
        return $columns;
    }


    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'date' => array( 'date', true ),
            'title' => array( 'title', false ),
        );

        return $sortable_columns;
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = array(
            'bulk-delete' => 'Delete'
        );
		
	$nonce = wp_create_nonce('mailin_bulk_action_nonce');
    	echo '<input type="hidden" name="mailin_bulk_action_nonce" value="' . esc_attr($nonce) . '">';

        return $actions;
    }


    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        $this->_column_headers = $this->get_column_info();

        /** Process bulk action */
        $this->process_bulk_action();

        $per_page     = $this->get_items_per_page( 'forms_per_page', 50 );
        $current_page = $this->get_pagenum();
        $total_items  = self::record_count();

        $this->set_pagination_args( array(
            'total_items' => $total_items, //WE have to calculate the total number of items
            'per_page'    => $per_page //WE have to determine how many items to show on a page
        ) );

        $this->items = self::getForms( $per_page, $current_page );
    }

    public function process_bulk_action() {

        //Detect when a bulk action is being triggered...
        if ( 'delete' === $this->current_action() ) {

            // In our file that handles the request, verify the nonce.
            $nonce = sanitize_text_field( $_REQUEST['_wpnonce'] );

            if ( ! wp_verify_nonce( $nonce, 'sib_delete_form' ) ) {
                die( 'Go get a life script kiddies' );
            }
            else {
                SIB_Forms::deleteForm( absint( sanitize_text_field($_GET['id']) ) );
                SIB_Forms_Lang::remove_trans( absint( sanitize_text_field($_GET['id']) ) );
                wp_redirect(add_query_arg('page', SIB_Page_Form::PAGE_ID, admin_url('admin.php'))); exit;
            }

        }

        // If the delete bulk action is triggered
        if ( ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' )
            || ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' )
        ) {
	    $bulk_action_nonce = isset($_REQUEST['mailin_bulk_action_nonce'] ) ? sanitize_text_field( $_REQUEST['mailin_bulk_action_nonce'] ) : "";
	    if ( ! wp_verify_nonce( $bulk_action_nonce, 'mailin_bulk_action_nonce' ) ) {
	        die( 'Go get a life script kiddies' );
            }
            $delete_ids = array_map('intval', $_POST['bulk-delete']);

            // loop over the array of record IDs and delete them
            foreach ( $delete_ids as $id ) {
                if( $id > 0 ) {
                    SIB_Forms::deleteForm( $id );
                    SIB_Forms_Lang::remove_trans( $id );
                }
            }
            wp_redirect(esc_url(add_query_arg(NULL,NULL))); exit;

        }
    }
    public function pagination($which){
        echo '<a href="'.add_query_arg(array('page' => 'sib_page_form','action'=>'edit'/*,'id'=>'new'*/), admin_url('admin.php')).'" class="btn btn-success" style="float:right; margin: 2px 1px 8px 15px;" >'.__("Add New Form", "mailin").'</a>';
        parent::pagination($which);
    }

    static function usort_reorder( $a, $b ) {
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? sanitize_text_field($_GET['orderby']) : 'title'; // by title
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? sanitize_text_field($_GET['order']) : 'ask';
        // Determine sort order
        $result = strcmp( $a[$orderby], $b[$orderby] );
        // Send final sort direction to usort
        return ( $order === 'ask' ) ? $result : -$result;
    }

    function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? sanitize_text_field( $_GET['page'] ) : false;
        if( 'sib_page_form' != $page )
            return;

        echo '<style type="text/css">';
        echo '.wp-list-table .column-date { width: 150px; }';
        echo '</style>';
    }
}
