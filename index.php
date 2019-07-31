<?php
/*
Plugin Name: ecommerce
Description: Ecommerce test plugin 
Author: Marcin Gawronek
Version: 0.1
*/
if ( ! class_exists( 'Ecommerce_WP_List_Table' ) ) {

	class Ecommerce_WP_List_Table{
		public function __construct()
		{
			add_action('admin_menu', array($this, 'ecommerce_add_plugin_page'));
			add_action( 'admin_enqueue_scripts', array($this,'my_enqueue'));
			$this-> ecommerce_define();
			$this-> ecommerce_db_create();
		}
		public function ecommerce_define(){
			global $wpdb;
			define('Ecommerce_FS_TABLE', $wpdb->prefix . 'ecommerce_fs');
		}

		public function ecommerce_db_create() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = Ecommerce_FS_TABLE;

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) ,
			description text(500),
			price float(20),
			action varchar(255),					
			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
}

		public function ecommerce_add_plugin_page()
		{
			add_menu_page( esc_attr__( 'Ecommerce WP List Table', 'textdomain' ),esc_html__( 'Ecommerce List Table', 'textdomain' ),
			'administrator','ecommerce-listTable',array( $this, 'ecommerce_create_admin_page' ), '', 10);
			
		}

		public function ecommerce_create_admin_page()
		{
			global $wpdb;
		?>
		<div class="wrap pmc-fs">
		<?php
		$ecommerce_fs_table = new Ecommerce_FS_WP_List_Table();
		echo '<div class="wrap"><h2>E-commerce admin page</h2>'; 
		echo '<div id="tabs"><ul><li><a href="#tabs-1">Products</a></li>
			<li><a href="#tabs-2">Categories</a></li>
			<div id="tabs-1">';
		$ecommerce_fs_table->prepare_items();
		echo '<input type="hidden" name="page" value="" />';
		$ecommerce_fs_table->views();
		echo '<form method="post">';	
		echo ' <input type="hidden" name="page" value="ecommerce_fs_search">';
		$ecommerce_fs_table->search_box( 'search', 'search_id' );
		$ecommerce_fs_table->display();  
		echo '</div>
			<div id="tabs-2">Test Categories</div></form></div>';
		echo '<script>jQuery( function() {
    		jQuery( "#tabs" ).tabs();
  } );</script>';
		}
		public function my_enqueue($hook){
			wp_enqueue_script('jquery-ui-tabs');
  			wp_enqueue_style('e2b-admin-ui-css','http://ajax.googleapis.com/ajax/libs/jqueryui/1.9.0/themes/base/jquery-ui.css',false,"1.9.0",false);
		}
	}
	$Ecommerce_WP_List_Table = new Ecommerce_WP_List_Table();
}


if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Ecommerce_FS_WP_List_Table extends WP_List_Table 
{
	
    function __construct(){
        parent::__construct( array(
            'ajax'      => false        
    ) );
	
    }
    

	function get_columns(){
		$columns = array(		
		'name' => 'Name',
		'description'    => 'Description',
		'price'      => 'Price',	
		);
		return $columns;
	}	

	function column_default( $item, $column_name ) {
	switch( $column_name ) { 
                case 'id':
		case 'name':
		case 'description':
		case 'price':
		case 'action':	
	  return $item[ $column_name ];
	default:
	  return print_r( $item, true ) ; 
		}
	}

	protected function get_views() { 
  $views = array();
   $current = ( !empty($_REQUEST['customvar']) ? $_REQUEST['customvar'] : 'all');

 
   $class = ($current == 'all' ? ' class="current"' :'');
   $all_url = remove_query_arg('customvar');
   $views['all'] = "<a href='{$all_url }' {$class} >All</a>";


   $foo_url = add_query_arg('customvar','recovered');
   $class = ($current == 'recovered' ? ' class="current"' :'');
   $views['recovered'] = "<a href='{$foo_url}' {$class} >Recovered</a>";


   $bar_url = add_query_arg('customvar','abandon');
   $class = ($current == 'abandon' ? ' class="current"' :'');
   $views['abandon'] = "<a href='{$bar_url}' {$class} >Abandon</a>";

   return $views;
	}

	function prepare_items() {
	global $wpdb;

	$per_page = 50;
	$current_page = $this->get_pagenum();
	if ( 1 < $current_page ) {
		$offset = $per_page * ( $current_page - 1 );
	} else {
		$offset = 0;
	}

        $search = '';		

	$customvar = ( isset($_REQUEST['customvar']) ? $_REQUEST['customvar'] : '');
	if($customvar != '') {
		$search_custom_vars= "AND action LIKE '%" . esc_sql( $wpdb->esc_like( $customvar ) ) . "%'";
	} else	{
		$search_custom_vars = '';
	}
        if ( ! empty( $_REQUEST['s'] ) ) {
            $search = "AND name LIKE '%" . esc_sql( $wpdb->esc_like( $_REQUEST['s'] ) ) . "%'";
        }		
        $items = $wpdb->get_results( "SELECT id,name,description,price,action FROM ".Ecommerce_FS_TABLE." WHERE 1=1 {$search} {$search_custom_vars}" . $wpdb->prepare( "ORDER BY id DESC LIMIT %d OFFSET %d;", $per_page, $offset ),ARRAY_A);	
        	$columns = $this->get_columns();
	
        $hidden = array();
	$sortable = $this->get_sortable_columns();
	$this->_column_headers = array($columns, $hidden, $sortable);	
	usort( $items, array( &$this, 'usort_reorder' ) );
	$count = $wpdb->get_var( "SELECT COUNT(id) FROM ".Ecommerce_FS_TABLE." WHERE 1 = 1 {$search};" );

	$this->items = $items;

	$this->set_pagination_args( array(
		'total_items' => $count,
		'per_page'    => $per_page,
		'total_pages' => ceil( $count / $per_page )
	) );

	function get_sortable_columns() {
	$sortable_columns = array(
	'action'  => array('action',false),
	);
	return $sortable_columns;
	}

	function usort_reorder( $a, $b ) {
 
  	$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'id';
 
  	$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'desc';
  
  	$result = strcmp( $a[$orderby], $b[$orderby] );
  
  	return ( $order === 'asc' ) ? $result : -$result;
	}

	
}

}

?>




