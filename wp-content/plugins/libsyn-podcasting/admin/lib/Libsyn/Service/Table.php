<?php
namespace Libsyn\Service;

/*
	This class is used to create a WP admin table for displaying options.
*/
//Our class extends the WP_List_Table class, so we need to make sure that it's there
if(!class_exists('WP_List_Table')){
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
class Table extends \WP_List_Table {
	
	public $item_headers;
	
	/**
	* Constructor, we override the parent to pass our own arguments
	* We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
	*/
	public function __construct($args, $items) {
		global $status, $page;
		parent::__construct( $args);
		$this->items = $items;
	}
	
	
	/**
	 * Add extra markup in the toolbars before or after the list
	 * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
	 */
	public function extra_tablenav( $which ) {
		if ( $which == "top" ){
			//The code that goes before the table is here
			// echo "Hello, I'm before the table";
		}
			if ( $which == "bottom" ){
			//The code that goes after the table is there
			// echo "Hi, I'm after the table";
		}
	}
	
	/**
	 * Define the columns that are going to be used in the table
	 * @return array $columns, the array of columns to use with the table
	 */
	public function get_columns() {
		
		
/* 	function get_columns() {
		return $columns = array(
			'cb'		=> '<input type="checkbox" />', //Render a checkbox instead of text
			'title'		=> 'Title',
			'rating'	=> 'Rating',
			'director'	=> 'Director'
		);
	} */
		$columns = array();
		foreach ($this->item_headers as $col_name => $col_display_name){
			$columns[0][$col_name] = __($col_display_name);
			/* array(
				'col_link_id' => __('ID')
				,'col_link_name '=> __('Name')
				,'col_link_url' => __('Url')
				,'col_link_description' => __('Description')
				,'col_link_visible' => __('Visible')
			); */
			
		}
		return array_shift($columns);
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
		// parent::display();
		$singular = $this->_args['singular'];
		$this->display_tablenav( 'top' );
		$this->screen->render_screen_reader_content( 'heading_list' );
		?>
		<table class="wp-list-table <?php echo implode( ' ', $this->get_table_classes() ); ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tbody id="the-list"<?php
				if ( $singular ) {
					echo " data-wp-lists='list:$singular'";
				} ?>>
				<?php $this->display_rows_or_placeholder(); ?>
			</tbody>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>

		</table>
		<?php
		$this->display_tablenav( 'bottom' );
	}
	
	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			// wp_nonce_field( 'bulk-' . $this->_args['plural'] ); //this breaks the post form
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ): ?>
			<div class="alignleft actions bulkactions">
				<?php $this->bulk_actions( $which ); ?>
			</div>
			<?php endif;
			$this->extra_tablenav( $which );
			$this->pagination( $which );
		?>

				<br class="clear" />
			</div>
		<?php
	}
	
	/**
	 * Handle an incoming ajax request (called from admin-ajax.php)
	 *
	 * @since 3.1.0
	 * @access public
	 */
	function ajax_response() {
		check_ajax_referer( 'ajax-custom-list-nonce', '_ajax_custom_list_nonce' );
		$this->prepare_items();
		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );
		
		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) )
			$this->display_rows();
		else
			$this->display_rows_or_placeholder();
		$rows = ob_get_clean();;

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();
		
		ob_start();
		$this->pagination('top');
		$pagination_top = ob_get_clean();
		
		ob_start();
		$this->pagination('bottom');
		$pagination_bottom = ob_get_clean();
		
		$response = array();
		$response['rows'] = $rows;
		$response['pagination']['top'] = $pagination_top;
		$response['pagination']['bottom'] = $pagination_bottom;
		$response['column_headers'] = $headers;
		
		if ( isset( $total_items ) )
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );
		
		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;
			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}
		
		die( json_encode( $response, JSON_PRETTY_PRINT ) );
	}	
	
	
	/**
	 * Decide which columns to activate the sorting functionality on
	 * @param array $items, list of items to display in key => val format for the table
	 * @return array $sortable, the array of columns that can be sorted by the user
	 */
	public function get_sortable_columns() {
/* 	function get_sortable_columns() {
		return $sortable_columns = array(
			'title'	 	=> array( 'title', false ),	//true means it's already sorted
			'rating'	=> array( 'rating', false ),
			'director'	=> array( 'director', false )
		);
	} */
		$sortable = array();
		foreach($this->item_headers as $col_name => $col_display_name) {
			$sortable[0][$col_name] = $col_display_name;
			 /* array(
				'col_link_id' => 'link_id'
				,'col_link_name' => 'link_name'
				,'col_link_visible' => 'link_visible'
			); */
		}
		return $sortable;
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
			'edit'		=> sprintf( '<a href="?page=%s&action=%s&movie=%s">Edit</a>', $_REQUEST['page'], 'edit', $item['ID'] ),
			// 'delete'	=> sprintf( '<a href="?page=%s&action=%s&movie=%s">Delete</a>', $_REQUEST['page'], 'delete', $item['ID'] ),
		);

		//Return the title contents
		return sprintf('%1$s <span style="color:silver">(id:%2$s)</span>%3$s',
			/*$1%s*/ $item['title'],
			/*$2%s*/ $item['ID'],
			/*$3%s*/ $this->row_actions( $actions )
		);
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
	public function prepare_items() {
		global $wpdb;
		
		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = 10;		
		
		/* -- Register the Columns -- */
		$columns = $this->get_columns();
		foreach($columns as $key => $val) if($key === 'id') unset($columns[$key]); //unset id row
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		foreach($sortable as $key => $val) if($key === 'id') unset($sortable[$key]); //unset id row
		$this->_column_headers = array($columns, $hidden, $sortable);
		// global $wpdb, $_wp_column_headers;
		// $screen = get_current_screen();
		
		/**
		 * Optional. You can handle your bulk actions however you see fit. In this
		 * case, we'll handle them within our package just to keep things clean.
		 */
		// $this->process_bulk_action();
		
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
		$total_items = count((array)$this->items);
		
		/**
		 * The WP_List_Table class does not handle pagination for us, so we need
		 * to ensure that the data is trimmed to only the current page. We can use
		 * array_slice() to 
		 */
		$data = array_slice((array) $this->items,(($current_page-1)*$per_page),$per_page);

		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where 
		 * it can be used by the rest of the class.
		 */
		$this->items = $data;
		
		/**
		 * This checks for sorting input and sorts the data in our array accordingly.
		 * 
		 * In a real-world situation involving a database, you would probably want 
		 * to handle sorting by passing the 'orderby' and 'order' values directly 
		 * to a custom query. The returned data will be pre-sorted, and this array
		 * sorting technique would be unnecessary.
		 */
/* 		function usort_reorder( $a, $b ) {
			//If no sort, default to title
			$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? $_REQUEST['orderby'] : 'title';
			//If no order, default to asc
			$order = ( ! empty( $_REQUEST['order'] ) ) ? $_REQUEST['order'] : 'asc';
			 //Determine sort order
			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
			//Send final sort direction to usort
			return ( 'asc' === $order ) ? $result : -$result; 
		}
		usort( $data, 'usort_reorder' ); */
		
		/* -- Preparing your query -- */
		// $query = "SELECT * FROM $wpdb->posts";

		/* -- Ordering parameters -- */
		//Parameters that are going to be used to order the result
		// $orderby = !empty($_GET["orderby"]) ? $wpdb->_real_escape($_GET["orderby"]) : 'ASC';
		// $order = !empty($_GET["order"]) ? $wpdb->_real_escape($_GET["order"]) : 'title';
		// if(!empty($orderby) & !empty($order)){ $query.=' ORDER BY '.$orderby.' '.$order; }

		/* -- Pagination parameters -- */
		//Number of elements in your table?
		// $total_items = $wpdb->query($query); //return the total number of affected rows
		// $total_items = count((array)$this->items);
		
		
		//Which page is this?
		// $paged = !empty($_GET["paged"]) ? $wpdb->_real_escape($_GET["paged"]) : '';
		
		//Page Number
		// if(empty($paged) || !is_numeric($paged) || $paged<=0 ){ $paged=1; }
		
		//How many pages do we have in total?
		// $total_pages = ceil($total_items/$per_page);
		
		//adjust the query to take pagination into account
		// if(!empty($paged) && !empty($per_page)){
			// $offset=($paged-1)*$per_page;
			// $query.=' LIMIT '.(int)$offset.','.(int)$per_page;
		// }

		/* -- Register the pagination -- */
		// $this->set_pagination_args( array(
			// "total_items" => $total_items,
			// "total_pages" => $total_pages,
			// "per_page" => $per_page,
		// ) );
		//The pagination links are automatically built according to those parameters
		/**
		 * REQUIRED. We also have to register our pagination options & calculations.
		 */
		$this->set_pagination_args(
			array(
				'total_items'	=> $total_items
				,'per_page'	=> $per_page
				,'total_pages'	=> ceil( $total_items / $per_page )
				// ,'orderby'	=> $orderby
				,'orderby'	=> ! empty( $_REQUEST['orderby'] ) && '' != $_REQUEST['orderby'] ? $_REQUEST['orderby'] : 'title'
				// ,'order'		=> $order
				,'order'		=> ! empty( $_REQUEST['order'] ) && '' != $_REQUEST['order'] ? $_REQUEST['order'] : 'asc'
			)
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
			'<input type="checkbox" name="%1$s[]" value="%2$s" id="%3$s">'
			,/*$1%s*/ $this->_args['singular']  	//Let's simply repurpose the table's singular label ("movie")
			,/*$2%s*/ $item['ID']			//The value of the checkbox should be the record's id
			,/*$3%s*/ "libsyn-table-checkbox-".$item['ID']	//The value of the checkbox should be the record's id
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
			// 'delete'	=> 'Delete'
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
	 * Display the rows of records in the table
	 * @return string, echo the markup of the rows
	 */
	public function display_rows() {

		//Get the records registered in the prepare_items method
		$records = (array) $this->items;

		//Get the columns registered in the get_columns and get_sortable_columns methods
		// list( $columns, $hidden ) = $this->item_headers;
		// list( $columns, $hidden ) = $this->get_column_info();
		//Loop for each record
		if(!empty($records)){
			foreach($records as $rec){
				//Open the line
				echo '<tr id="record_'.$rec->{$this->item_headers['id']}.'">';
				foreach ( $this->item_headers as $column_name => $column_display_name ) {
					//Style attributes for each col
					$class = "class='$column_name column-$column_name'";
					$style = "";
					if ( is_array($hidden) && in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
					$attributes = $class . $style;
					
					//edit link
					$editlink  = '/wp-admin/link.php?action=edit&link_id='.(int)$rec->{$this->item_headers['id']};
					//Display the cell
					// echo '< td '.$attributes.'>'.$rec->{$this->headers['visible']}.'< /td>';
					if($column_name !== 'id') echo '<td '.$attributes.'>'.stripslashes($rec->{$column_name}).'</td>';
/* 					switch ( $column_name ) {
						case ($column_name !== 'id'):  
							echo '<td '.$attributes.'>'.stripslashes($rec->{$column_name}).'</td>';
							break;
					} */
					
				}
				//Close the line
				echo'</tr>';
			}
		}
	}
	
}

?>