<?php
add_action('admin_footer', 'ajax_script',0);
/**
 * This function adds the jQuery script to the plugin's page footer
 */
function ajax_script() {

	$screen = get_current_screen();
	
	if ( 'edit-shipment' != $screen->id )
		return false;
?>
<script type="text/javascript">
(function($) {
jQuery(document).on('click','.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a',function(e){
	e.preventDefault();
	var per_page = jQuery('#perpagerecord').val();
	jQuery.ajax({
			// /wp-admin/admin-ajax.php
			url: "<?php echo admin_url('admin-ajax.php');?>",
			// Add action and nonce to our collected data
			data: "action=ajax_set_per_page&per_page="+per_page,
			// Handle the successful result
			success: function( response ) {

				// Init back our event handlers
				// list.init();
			}
		});
});
list = {

	/**
	 * Register our triggers
	 * 
	 * We want to capture clicks on specific links, but also value change in
	 * the pagination input field. The links contain all the information we
	 * need concerning the wanted page number or ordering, so we'll just
	 * parse the URL to extract these variables.
	 * 
	 * The page number input is trickier: it has no URL so we have to find a
	 * way around. We'll use the hidden inputs added in TT_Example_List_Table::display()
	 * to recover the ordering variables, and the default paged input added
	 * automatically by WordPress.
	 */
	init: function() {

		// This will have its utility when dealing with the page number input
		var timer;
		var delay = 1000;
		
		// Pagination links, sortable link
		$('.tablenav-pages a, .manage-column.sortable a, .manage-column.sorted a').on('click', function(e) {
			//set number of record per page
			// var per_page = jQuery('#edit_shipment_per_page').val();
			var per_page = jQuery('#perpagerecord').val();

			// We don't want to actually follow these links
			e.preventDefault();
			var keys = ($('#post-search-input').val().length > 0) ? $('#post-search-input').val() : "";
			// Simple way: use the URL to extract our needed variables
			var query = this.search.substring( 1 );
			
			var data = {
				paged: list.__query( query, 'paged' ) || '1',
				order: list.__query( query, 'order' ) || 'asc',
				orderby: list.__query( query, 'orderby' ) || 'title',
				keywords: keys,
				per_page: per_page
			};
			var setpagerec = {
				per_page: per_page
			};
			// list.customSet(setpagerec);
			list.update( data );
		});

		// Page number input
		$('#post-search-input').on('keyup input', function(e) {
			//set number of record per page
			// var per_page = jQuery('#edit_shipment_per_page').val();
			var per_page = jQuery('#perpagerecord').val();
			var keys = ($('#post-search-input').val().length > 0) ? $('#post-search-input').val() : "";
			// If user hit enter, we don't want to submit the form
			// We don't preventDefault() for all keys because it would
			// also prevent to get the page number!
			if ( 13 == e.which )
				e.preventDefault();

			// This time we fetch the variables in inputs
			var data = {
				paged: 1,
				order: $('input[name=order]').val() || 'asc',
				orderby: $('input[name=orderby]').val() || 'title',
				keywords: keys,
				per_page: per_page
			};

			// Now the timer comes to use: we wait half a second after
			// the user stopped typing to actually send the call. If
			// we don't, the keyup event will trigger instantly and
			// thus may cause duplicate calls before sending the intended
			// value
			window.clearTimeout( timer );
			timer = window.setTimeout(function() {
				list.update( data );
			}, delay);
		});
	},

	/** AJAX call
	 * 
	 * Send the call and replace table parts with updated version!
	 * 
	 * @param    object    data The data to pass through AJAX
	 */
	update: function( data ) {
		$.ajax({
			// /wp-admin/admin-ajax.php
			url: ajaxurl,
			// Add action and nonce to our collected data
			data: $.extend(
				{
					_ajax_custom_list_nonce: $('#_ajax_custom_list_nonce').val(),
					action: 'ajax_fetch_custom_list',
				},
				data
			),
			// Handle the successful result
			success: function( response ) {

				// WP_List_Table::ajax_response() returns json
				var response = $.parseJSON( response );

				// Add the requested rows
				if ( response.rows.length )
					$('#the-list').html( response.rows );
				// Update column headers for sorting
				if ( response.column_headers.length )
					$('thead tr, tfoot tr').html( response.column_headers );
				// Update pagination for navigation
				if ( response.pagination.bottom.length )
					$('.tablenav.top .tablenav-pages').html( $(response.pagination.top).html() );
				if ( response.pagination.top.length )
					$('.tablenav.bottom .tablenav-pages').html( $(response.pagination.bottom).html() );

				// Init back our event handlers
				list.init();
			}
		});
	},
	
	/**
	 * Filter the URL Query to extract variables
	 * 
	 * @see http://css-tricks.com/snippets/javascript/get-url-variables/
	 * 
	 * @param    string    query The URL query part containing the variables
	 * @param    string    variable Name of the variable we want to get
	 * 
	 * @return   string|boolean The variable value if available, false else.
	 */
	__query: function( query, variable ) {

		var vars = query.split("&");
		for ( var i = 0; i <vars.length; i++ ) {
			var pair = vars[ i ].split("=");
			if ( pair[0] == variable )
				return pair[1];
		}
		return false;
	},
}

// Show time!
list.init();

})(jQuery);
</script>
<?php
}

if( is_admin() && !class_exists( 'WP_List_Table' ) )
	require_once ABSPATH.'wp-content/themes/logistic-pro-child/test.php';
    require_once ABSPATH.'wp-admin/includes/class-wp-list-table.php';

class TT_Example_List_Table extends WP_List_Table {
	
	/**
	 * Normally we would be querying data from a database and manipulating that
	 * for use in your list table. For this example, we're going to simplify it
	 * slightly and create a pre-built array. Think of this as the data that might
	 * be returned by $wpdb->query().
	 * 
	 * @var array 
	 */

	


	/**
	 * REQUIRED. Set up a constructor that references the parent constructor. We 
	 * use the parent reference to set some default configs.
	 */
	function __construct() {

		global $status, $page;

		//Set parent defaults
		parent::__construct(
			array(
				//singular name of the listed records
				'singular'	=> 'shipment',
				//plural name of the listed records
				'plural'	=> 'shipments',
				//does this table support ajax?
				'ajax'		=> true
			)
		);
		
	}


	/**
	 * Recommended. This method is called when the parent class can't find a method
	 * specifically build for a given column. Generally, it's recommended to include
	 * one method for each column you want to render, keeping your package class
	 * neat and organized. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title() 
	 * exists - if it does, that method will be used. If it doesn't, this one will
	 * be used. Generally, you should try to use custom column methods as much as 
	 * possible. 
	 * 
	 * Since we have defined a column_title() method later on, this method doesn't
	 * need to concern itself with any column with a name of 'title'. Instead, it
	 * needs to handle everything else.
	 * 
	 * For more detailed insight into how columns are handled, take a look at 
	 * WP_List_Table::single_row_columns()
	 * 
	 * @param array $item A singular item (one full row's worth of data)
	 * @param array $column_name The name/slug of the column to be processed
	 * 
	 * @return string Text or HTML to be placed inside the column <td>
	 */
	function column_default( $item, $column_name ) {

		switch ( $column_name ) {

			// TODO below comment case have to be replaced with your desired column names
			

			/*case 'bscd_tracking_num':
				return $item[ $column_name ];
			case 'author':
				return $item[ $column_name ];
			case 'title':
				return $item[ $column_name ];
			case 'cost_of_item':
				return $item[ $column_name ];
			case 'merchants':
				return $item[ $column_name ];
			case 'cu_tracking_num':
				return $item[ $column_name ];
			case 'taxonomy-logistic-order-status':
				return $item[ $column_name ];
			case 'comments':
				return $item[ $column_name ];
			case 'date':
				return $item[ $column_name ];
			default:
				//Show the whole array for troubleshooting purposes
				return print_r( $item, true );*/
		}
	}


	/**
	 * Recommended. This is a custom column method and is responsible for what
	 * is rendered in any column with a name/slug of 'title'. Every time the class
	 * needs to render a column, it first looks for a method named 
	 * column_{$column_title} - if it exists, that method is run. If it doesn't
	 * exist, column_default() is called instead.
	 * 
	 * This example also illustrates how to implement rollover actions. Actions
	 * should be an associative array formatted as 'slug'=>'link html' - and you
	 * will need to generate the URLs yourself. You could even ensure the links
	 * 
	 * @see WP_List_Table::single_row_columns()
	 * 
	 * @param array $item A singular item (one full row's worth of data)
	 * 
	 * @return string Text to be placed inside the column <td> (movie title only)
	 */
	function column_title( $item ) {
		
		//Build row actions
		$actions = array(
			'edit'		=> sprintf( '<a href="post.php?post=%s&action=%s">Edit</a>', $item['ID'], 'edit' ),
			'delete'	=> sprintf( '<a href="?page=%s&action=%s&movie=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['ID'] ),
		);
		
		//Return the title contents
		return sprintf('<a class="row-title" href="post.php?post=%1$s&action=%2$s">%3$s</a> %4$s',$item['ID'],"edit",
			/*$1%s*/ $item['post_title'],
			
			/*$3%s*/ $this->row_actions( $actions )
		);
	}


	/**
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 * 
	 * @see WP_List_Table::single_row_columns()
	 * 
	 * @param array $item A singular item (one full row's worth of data)
	 * 
	 * @return string Text to be placed inside the column <td> (movie title only)
	 */
	function column_cb( $item ) {

		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ $this->_args['singular'],  	//Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/ $item['ID']			//The value of the checkbox should be the record's id
		);
	}


	/**
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value 
	 * is the column's title text. If you need a checkbox for bulk actions, refer
	 * to the $columns array below.
	 * 
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 * 
	 * @see WP_List_Table::single_row_columns()
	 * 
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 */
	function get_columns() {

		return $columns = array(
			'cb'		=> '<input type="checkbox" />', //Render a checkbox instead of text
			'title'		=> 'Title',
			'post_status'	=> 'Post status',
			'post_modified'	=> 'Modified'
		);
	}


	/**
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle), 
	 * you will need to register it here. This should return an array where the 
	 * key is the column that needs to be sortable, and the value is db column to 
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 * 
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 * 
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 */
	function get_sortable_columns() {

		return $sortable_columns = array(
			'title'	 	=> array( 'title', false ),	//true means it's already sorted
			'post_status'	=> array( 'post_status', false ),
			'post_modified'	=> array( 'post_modified', false )
		);
	}


	/**
	 * Optional. If you need to include bulk actions in your list table, this is
	 * the place to define them. Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 * 
	 * If this method returns an empty value, no bulk action will be rendered. If
	 * you specify any bulk actions, the bulk actions box will be rendered with
	 * the table automatically on display().
	 * 
	 * Also note that list tables are not automatically wrapped in <form> elements,
	 * so you will need to create those manually in order for bulk actions to function.
	 * 
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 */
	function get_bulk_actions() {

		return $actions = array(
			'delete'	=> 'Delete'
		);
	}


	/**
	 * Optional. You can handle your bulk actions anywhere or anyhow you prefer.
	 * For this example package, we will handle it in the class to keep things
	 * clean and organized.
	 * 
	 * @see $this->prepare_items()
	 */
	function process_bulk_action() {
		
		//Detect when a bulk action is being triggered...
		if( 'delete'=== $this->current_action() ) {
			wp_die( 'Items deleted (or they would be if we had items to delete)!' );
		}
		
	}


	/**
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 * 
	 * @global WPDB $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 */
	
    
	function prepare_items($keywords="",$per_page_rec=6) {

		global $wpdb; //This is used only if making any database queries
		              // echo '--->'.$keywords;join $wpdb->wp_term_relationships wtr on wps.ID = wtr.object_id
				// join $wpdb->terms wtm on wtm.term_id = wtr.term_taxonomy_id
		              // exit;OR wtm.name like '%$keywords%'
		if($keywords!=""){

			/**
			 * Below is the search query. It will search all matched post_title, author, category and return record by date
			 *
			 * @var        <type>
			 */
			$search = $wpdb->get_results("select wps.*,wpm.* from $wpdb->posts wps 
				inner join $wpdb->postmeta wpm on wps.ID=wpm.post_id 
				inner join $wpdb->users wus on wps.post_author=wus.ID 
				inner join $wpdb->usermeta wum on wus.ID = wum.user_id
				inner join $wpdb->term_relationships wtr on wps.ID = wtr.object_id
				inner join $wpdb->terms wt on wt.term_id = wtr.term_taxonomy_id
				where (wps.post_title like '%$keywords%' OR (wum.meta_value like '%$keywords%' and wum.meta_key='first_name') OR (wum.meta_value like '%$keywords%' and wum.meta_key='last_name' ) OR (wt.name like '%$keywords%') ) and wps.post_type='post' group by wps.id order by DATE(wps.post_date) desc",ARRAY_A);
		}
		else{
			$tempPosts = get_posts(array('post_type'=>'shipment','post_status'=>'publish','posts_per_page'=>-1));
			$search = json_decode(json_encode($tempPosts),true);
			
		}
		
		
		
		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 4;
		// $per_page = $per_page_rec;
		
		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		
		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column 
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array($columns, $hidden, $sortable);
		
		
		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		$this->process_bulk_action();
		
		
		/**
		 * Instead of querying a database, we're going to fetch the example data
		 * property we created for use in this plugin. This makes this example 
		 * package slightly different than one you might build on your own. In 
		 * this example, we'll be using array manipulation to sort and paginate 
		 * our data. In a real-world implementation, you will probably want to 
		 * use sort and pagination data to build a custom query instead, as you'll
		 * be able to use your precisely-queried data immediately.
		 */
			
		$data = $this->example_data;
		// if($keywords!=""){
			$data = $search;
		// }

		// $data = json_decode(json_encode(get_posts(array('post_type'=>'page','posts_per_page'=>-1))),true);
		// echo '<pre>';
		// echo $keywords;
		// print_r($search);
		// echo '</pre>';
		// exit;		
		
		/**
		 * This checks for sorting input and sorts the data in our array accordingly.
		 * 
		 * In a real-world situation involving a database, you would probably want 
		 * to handle sorting by passing the 'orderby' and 'order' values directly 
		 * to a custom query. The returned data will be pre-sorted, and this array
		 * sorting technique would be unnecessary.
		 */
		function usort_reorder( $a, $b ) {

			//If no sort, default to title
			$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'title';
			//If no order, default to asc
			$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
			 //Determine sort order
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			//Send final sort direction to usort
			return ( 'asc' === $order ) ? $result : -$result; 
		}
		usort( $data, 'usort_reorder' );
		
		
		/***********************************************************************
		 * ---------------------------------------------------------------------
		 * vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
		 * 
		 * In a real-world situation, this is where you would place your query.
		 *
		 * For information on making queries in WordPress, see this Codex entry:
		 * http://codex.wordpress.org/Class_Reference/wpdb
		 * 
		 * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
		 * ---------------------------------------------------------------------
		 **********************************************************************/
		
				
		/**
		 * REQUIRED for pagination. Let's figure out what page the user is currently 
		 * looking at. We'll need this later, so you should always include it in 
		 * your own package classes.
		 */
		$current_page = $this->get_pagenum();
		
		/**
		 * REQUIRED for pagination. Let's check how many items are in our data array. 
		 * In real-world use, this would be the total number of items in your database, 
		 * without filtering. We'll need this later, so you should always include it 
		 * in your own package classes.
		 */
		$total_items = count($data);
		
		
		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to 
		 */
		$data = array_slice($data,(($current_page-1)*$per_page),$per_page);
		
		
		
		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where 
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;
		// $this->items = 
		
		
		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(
			array(
				//WE have to calculate the total number of items
				'total_items'	=> $total_items,
				//WE have to determine how many items to show on a page
				'per_page'	=> $per_page,
				//WE have to calculate the total number of pages
				'total_pages'	=> ceil( $total_items / $per_page ),
				// Set ordering values if needed (useful for AJAX)
				'orderby'	=> ! empty( $_REQUEST['orderby'] ) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'title',
				'order'		=> ! empty( $_REQUEST['order'] ) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc'
			)
		);
	}

	/**
	 * Display the table
	 * Adds a Nonce field and calls parent's display method
	 *
	 * @since 3.1.0
	 * @access public
	 */
	function display() {

		wp_nonce_field( 'ajax-custom-list-nonce', '_ajax_custom_list_nonce' );

		echo '<input type="hidden" id="order" name="order" value="' . $this->_pagination_args['order'] . '" />';
		echo '<input type="hidden" id="orderby" name="orderby" value="' . $this->_pagination_args['orderby'] . '" />';

		parent::display();
	}

	/**
	 * Handle an incoming ajax request (called from admin-ajax.php)
	 *
	 * @since 3.1.0
	 * @access public
	 */
	function ajax_response() {

		// check_ajax_referer( 'ajax-custom-list-nonce', '_ajax_custom_list_nonce' );
		$keywords = isset($_REQUEST['keywords']) ? esc_sql( $_REQUEST['keywords'] ) : "";
		$per_page_rec = isset($_REQUEST['per_page']) ? esc_sql( $_REQUEST['per_page'] ) : 6;
		// print_r($per_page_rec);
		$this->prepare_items($keywords,$per_page_rec);

		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) )
			$this->display_rows();
		else
			$this->display_rows_or_placeholder();
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->pagination('top');
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination('bottom');
		$pagination_bottom = ob_get_clean();

		$response = array( 'rows' => $rows );
		$response['pagination']['top'] = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;
		$response['column_headers'] = $headers;

		if ( isset( $total_items ) )
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		die( json_encode( $response ) );
	}


function _ajax_fetch_custom_list_callback() {

	$wp_list_table = new TT_Example_List_Table();
	$wp_list_table->ajax_response();
	
}
add_action('wp_ajax_ajax_fetch_custom_list', '_ajax_fetch_custom_list_callback');