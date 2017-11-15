<?php
ob_start();
ob_clean();
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Items_List_Table extends WP_List_Table {
	var $columns = array(
		'cb'               => '<input type="checkbox" />', //Render a checkbox instead of text
		'link_id'          => 'ID',
		'link_name'        => 'Name',
		'link_url'         => 'Url',
		'link_description' => 'Description',
		'link_visible'     => 'Visible'
	);
	var $sortable_columns = array(
		'link_id'      => array( 'link_id', true ), //true means its already sorted
		'link_name'    => array( 'link_name', false ),
		'link_visible' => array( 'link_visible', false )
	);
	var $get_bulk_actions = array( 'delete' => 'Delete' );
	var $style_desc_box = '';
	var $itemsperpage = 20;
	var $unique_id = 'link_id';
	var $table_name = 'wp_links';
	var $table_fields = 'link_id,link_name,link_url,link_description,link_visible';
	var $items_list_heading = 'Website Links List';
	var $items_list_desc = 'This is a list of some website links.';
	var $add_new = false;
	var $add_new_file = '#';
	var $message = '';
	var $url = '/wp-admin/users.php';
	var $is_search = false;
	var $where = '';
	var $is_additional = array();
	var $file_name = '';

	public function __construct() {
		//Set parent defaults
		parent::__construct( array(
				'singular' => 'item',     //singular name of the listed records
				'plural'   => 'items',    //plural name of the listed records
				'ajax'     => false        //does this table support ajax?
			)
		);
	}

	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', 'item', $item[ $this->unique_id ] );
	}

	public function column_id( $item ) {
		$actions = array(
			'view'   => sprintf( '<a title="Form Details" class="thickbox" href="' . $this->url . '&action=%s&item=%s&?TB_iframe=true&width=600">View</a>', 'view', $item[ $this->unique_id ] ),
			'delete' => sprintf( '<a href="' . $this->url . '&action=%s&item=%s">Delete</a>', 'delete', $item[ $this->unique_id ] ),
		);

		return sprintf( '%1$s %2$s', $item[ $this->unique_id ], $this->row_actions( $actions ) );
	}

	public function get_bulk_actions() {
		return $this->get_bulk_actions;
	}

	public function get_columns() {
		return $this->columns;
	}

	public function get_sortable_columns() {
		return $this->sortable_columns;
	}

	private function record_count() {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM $this->table_name";

		return $wpdb->get_var( $sql );
	}

	public function get_lists( $per_page = 5, $page_number = 1 ) {
		global $wpdb;
		$sql = "SELECT $this->table_fields FROM {$this->table_name}";
		if ( ! empty( $_REQUEST['orderby'] ) ) {
			$sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
			$sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' DESC';
		} else {
			$sql .= ' ORDER BY ID DESC';
		}

		$sql    .= " LIMIT $per_page";
		$sql    .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;
		$result = $wpdb->get_results( $sql, 'ARRAY_A' );

		return $result;
	}

	public function prepare_items( $type = '' ) {
		$hidden                            = array();
		$columns                           = $this->get_columns();
		$sortable                          = $this->get_sortable_columns();
		$_wp_column_headers[ $screen->id ] = $columns;
		$this->_column_headers             = array( $columns, $hidden, $sortable );
		/** Process bulk action */
		$this->process_bulk_action();
		$per_page     = $this->get_items_per_page( 'per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = $this->record_count();
		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $per_page
		] );
		$this->items = $this->get_lists( $per_page, $current_page );
	}

	public function items_listing_form_page( $type = '' ) {
		$this->prepare_items( $type );
		?>
        <div class="wrap">
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php echo $this->items_list_heading; ?>
            </h2>
            <div class="custom_list_header error fade" style="<?php echo $this->style_desc_box ?>">
                <p>
                    <strong><?php echo $this->items_list_desc ?></strong>
                    <span>
                        <input type="button"
                               onclick="window.location='<?php echo $this->url . '&action=all-export'; ?>'"
                               class="button button-primary button-large" value="Export All">
                    </span>
                </p>


            </div>
			<?php echo( $this->message != '' ? "<div class='updated' id='message'><p>$this->message</p></div>" : '' ) ?>
            <div class="custom_listing_table">
                <form id="items-filter" method="post">
                    <input type="hidden" name="page" value="<?php echo $_SERVER['REQUEST_URI'] ?>"/>
					<?php if ( $this->is_search ) {
						$this->search_box( 'Search', 'search_id' );
					}
					$this->display();
					?>
                </form>
            </div>
        </div>
		<?php
	}

	public function display() {
		$singular = $this->_args['singular'];
		$this->bulk_actions( 'top' );
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

	protected function getPrimaryColumn() {
		global $wpdb;
		$cols_sql    = "DESCRIBE $this->table_name";
		$all_objects = $wpdb->get_results( $cols_sql );
		foreach ( $all_objects as $object ) {
			return $object->Key == 'PRI' ? $object->Field : 'ID';
		}
	}

	protected function getFieldsCommaSeprated( $inc_pm_key = true ) {
		global $wpdb;
		$cols_sql         = "DESCRIBE $this->table_name";
		$all_objects      = $wpdb->get_results( $cols_sql );
		$existing_columns = [];
		foreach ( $all_objects as $object ) {
			// Build an array of Field names
			if ( $inc_pm_key == false && $object->Key == 'PRI' ) {
				continue;
			}
			$existing_columns[] = $object->Field;
		}
		$sql = implode( ', ', $existing_columns );

		return $sql;
	}

	public function get_table_columns_objects() {
		global $wpdb;
		$cols_sql    = "DESCRIBE $this->table_name";
		$all_objects = $wpdb->get_results( $cols_sql );

		return $all_objects;
	}
}