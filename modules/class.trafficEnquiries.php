<?php

class TrafficEnquiries extends Items_List_Table {
	public $unique_id;
	public $table_name;
	public $table_fields;
	public $items_list_heading;
	public $items_list_desc;
	public $url;
	public $sub_menu_array = array();
	public $csv_headers = array();
	public $option_name = 'traffic_enquires_table';
	public static function init() {
		$class = __CLASS__;
		new $class;
	}
	public function __construct() {
		//construct [Do not remove this it will lead to 500 error]
	}
	public function add_menu() {
		$request_page = $_REQUEST['page'];
		$options_list = $this->getOptionList();
		if( ! empty($options_list) ) {
			foreach($options_list as $key => $name) {
				$slug = $name;
				$name = $this::prepare_human_readable_columns($name);
				$hook = add_submenu_page(TRAFFIC_PLUGIN_SLUG, $name, $name, 'manage_options', $slug, array(
						&$this,
						'start_listing'
					)
				);
				add_action("load-$hook", array(&$this, 'add_options'));
				add_filter('set-screen-option', array(__CLASS__, '_table_set_option'), 10, 3);
			}
			if( isset($request_page) && in_array($request_page, $options_list) ) {
				$name = $this::prepare_human_readable_columns($request_page);
				$this->setup_menu($request_page, $name);
			}
		}
	}
	public function add_options() {
		$option = 'per_page';
		$args   = array(
			'label'   => 'Items',
			'default' => 10,
			'option'  => 'cmi_movies_per_page'
		);
		add_screen_option($option, $args);
	}
	public function _table_set_option($status, $option, $value) {
		if( 'cmi_movies_per_page' == $option ) {
			return $value;
		}

		return $status;
	}
	public function getOptionList() {
		$get_options  = get_option($this->option_name);
		$options_list = ! empty($get_options) ? maybe_unserialize($get_options) : array();

		return $options_list;
	}
	public function setup_menu($slug, $name) {
		$this->table_name         = $slug;
		$this->table_fields       = $this->getFieldsCommaSeprated();
		$this->unique_id          = $this->getPrimaryColumn();
		$this->items_list_heading = $name.' List';
		$this->items_list_desc    = 'This is a list of '.$name.' forms submitted by visitors.';
		$this->url                = 'admin.php?page='.$slug;
		$get_columns              = $this->get_table_columns_objects();
		$setColumns               = array('cb' => '<input type="checkbox" />');
		$setSortableColumns       = array();
		$setCsvHeaders            = array();
		foreach($get_columns as $key => $obj) {
			$toString                          = $this::prepare_human_readable_columns($obj->Field);
			$setColumns[ $obj->Field ]         = $toString;
			$setSortableColumns[ $obj->Field ] = array($obj->Field, ( $obj->Key == 'PRI' ? true : false ));
			if( $obj->Key != 'PRI' ) {
				$setCsvHeaders[ $toString ] = $toString;
			}
		}
		$this->setColumns($setColumns);
		$this->setSortableColumns($setSortableColumns);
		$this->setCsvHeaders($setCsvHeaders);

		return $this;
	}
	public function setColumns($columns = array()) {
		$this->columns = $columns;
	}
	public function getColumns() {
		return $this->columns;
	}
	public function setSortableColumns($sortable_columns = array()) {
		$this->sortable_columns = $sortable_columns;
	}
	public function getSortableColumns() {
		return $this->sortable_columns;
	}
	public function process_bulk_action() {
		global $wpdb;
		$entry_id     = $_REQUEST['item'];
		$request_page = $_REQUEST['page'];
		$options_list = $this->getOptionList();
		if( strpos($request_page, '?page=') !== false ) {
			$page         = explode('?page=', $_REQUEST['page']);
			$request_page = $page[1];
			if( isset($request_page) && in_array($request_page, $options_list) ) {
				$name = $this::prepare_human_readable_columns($request_page);
				$this->setup_menu($request_page, $name);
			}
		}
		if( 'delete' === $this->current_action() ) {
			if( isset($entry_id) && is_array($entry_id) ) {
				foreach($entry_id as $id) {
					$id = absint($id);
					$wpdb->query("DELETE FROM $this->table_name WHERE $this->unique_id = $id");
				}
			}
			else {
				$id = absint($entry_id);
				$wpdb->query("DELETE FROM $this->table_name WHERE $this->unique_id = $id");
			}
			wp_redirect(esc_url_raw($this->url));
			exit;
		}
		else if( 'export' === $this->current_action() ) {
			$this->export_csv($entry_id);
			$url = get_site_url().'/wp-content/plugins'.TRAFFIC_PLUGIN_DIR.'/export/download_csv.php';
			if( empty($this->file_name) ) {
				wp_redirect($_SERVER["REQUEST_URI"]);
				exit;
			}
			wp_redirect($url.'?file='.$this->file_name.'&redirect='.$_SERVER['REQUEST_URI']);
			exit;
		}
		else if( 'export' === $this->current_action() && empty($entry_id) ) {
			wp_redirect(esc_url_raw(add_query_arg()));
			exit;
		}
	}
	public function start_listing() {
		//Set variables for class...
		$this->add_new          = false;
		$this->add_new_file     = $this->url.'&task=new';
		$this->get_bulk_actions = array('delete' => 'Delete', 'export' => 'Export');
		/**************************************************/
		if( $_REQUEST['action'] == 'view' && ! empty($_REQUEST['item']) ) {
			global $wpdb;
			$form_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE {$this->unique_id}=$_REQUEST[item]"), ARRAY_A);
			$this->display_form($form_data);
		}
		else if( $_REQUEST['action'] == 'all-export' ) {
			$this->export_all_view();
		}
		else if( $_REQUEST['action'] != 'view' ) {
			//display listing page
			$this->items_listing_form_page();
		}
	}
	public function display_form($form_data) {
		?>
        <div class="wrap">
            <h3>Form Details</h3>
            <table width="70%" class="wp-list-table widefat fixed">
				<?php foreach($form_data as $key => $value) { ?>
                    <tr>
                        <th width="30%" scope="col">
                            <strong><?php echo ucwords(str_replace('_', ' ', $key)) ?></strong>
                        </th>
                        <td><?php echo $value ?></td>
                    </tr>
				<?php } ?>
            </table>
        </div>
        <div id="response"></div>
        <script type="text/javascript">
            jQuery('#wpadminbar').hide();
            jQuery('.wp-toolbar').css('padding', '0');
        </script>
		<?php
	}
	public function export_all_view() {
		$this->csv_export_all();
		$url = get_site_url().'/wp-content/plugins'.TRAFFIC_PLUGIN_DIR.'/export/download_csv.php';
		$url .= '?file='.$this->file_name.'&redirect='.$_SERVER['REQUEST_URI']
		?>
        <div class="wrap">
            <h3>Export List</h3>
            <p>Data export successfull. <a href="<?php echo $url; ?>">click here to download.</a> <br> OR <a
                        href="/wp-admin?admin.php?page=<?php echo $_REQUEST['page'] ?>">Go back to listing</a></p>
        </div>
		<?php
	}
	public function usort_reorder($a, $b) {
		// If no sort, default to title
		$orderby = ( ! empty($_GET['orderby']) ) ? $_GET['orderby'] : 'booktitle';
		// If no order, default to asc
		$order = ( ! empty($_GET['order']) ) ? $_GET['order'] : 'asc';
		// Determine sort order
		$result = strcmp($a[ $orderby ], $b[ $orderby ]);

		// Send final sort direction to usort
		return ( $order === 'asc' ) ? $result : - $result;
	}
	protected function single_row_columns($item) {
		list($columns, $hidden, $sortable, $primary) = $this->get_column_info();
		foreach($columns as $column_name => $column_display_name) {
			$classes = "$column_name column-$column_name";
			if( $primary === $column_name ) {
				$classes .= ' has-row-actions column-primary';
			}
			if( in_array($column_name, $hidden) ) {
				$classes .= ' hidden';
			}
			// Comments column uses HTML in the display name with screen reader text.
			// Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
			$data       = 'data-colname="'.wp_strip_all_tags($column_display_name).'"';
			$attributes = "class='$classes' $data";
			if( 'cb' === $column_name ) {
				echo '<th scope="row" class="check-column">';
				echo $this->column_cb($item);
				echo '</th>';
			}
			elseif( 'message' == $column_name ) {
				$limit = 50;
				echo "<td $attributes>";
				echo mb_substr($item[ $column_name ], 0, $limit, 'UTF-8');
				if( strlen($item[ $column_name ]) > $limit ) {
					echo '...';
					//echo '<div class="content-one">' . mb_substr( $item[ $column_name ], $limit, strlen( $item[ $column_name ] ), 'UTF-8' ) . '</div>';
				}
				echo "</td>";
			}
			elseif( 'attachment' == strtolower($column_name) ) {
				echo "<td $attributes>";
				echo '<a href="'.$item[ $column_name ].'" target="_blank">View</a>';
				echo "</td>";
			}
			elseif( 'created_date' == strtolower($column_name) ) {
				$created_date = date_i18n('F j, Y g:i a', strtotime($item[ $column_name ]));
				echo "<td $attributes>";
				echo $created_date;
				echo "</td>";
			}
			elseif( method_exists($this, '_column_'.$column_name) ) {
				echo call_user_func(array($this, '_column_'.$column_name), $item, $classes, $data, $primary);
			}
			elseif( method_exists($this, 'column_'.$column_name) ) {
				echo "<td $attributes>";
				echo call_user_func(array($this, 'column_'.$column_name), $item);
				echo $this->handle_row_actions($item, $column_name, $primary);
				echo "</td>";
			}
			else {
				echo "<td $attributes>";
				echo $this->column_default($item, $column_name);
				echo $this->handle_row_actions($item, $column_name, $primary);
				echo "</td>";
			}
		}
	}
	public function enqueue_admin_js($hook_suffix) {
		if( strpos($hook_suffix, TRAFFIC_PLUGIN_SLUG) !== false ) {
			wp_enqueue_style('bootstrapstyle', plugins_url(TRAFFIC_PLUGIN_DIR).'/bootstrap/css/bootstrap.min.css');
			wp_enqueue_style('bootstrapthemestyle', plugins_url(TRAFFIC_PLUGIN_DIR).'/bootstrap/css/bootstrap-theme.min.css');
			wp_enqueue_script('bootstrap-script', plugins_url(TRAFFIC_PLUGIN_DIR).'/bootstrap/js/bootstrap.min.js', array(), true);
			wp_enqueue_script('my-script', WP_PLUGIN_URL.TRAFFIC_PLUGIN_DIR.'/bootstrap/js/_script.js', array(
					'jquery',
					'jquery-ui-core',
					'jquery-ui-tabs'
				)
			);
			wp_localize_script('my-script', 'traffic', array(
					'plugin_url' => plugins_url(TRAFFIC_PLUGIN_DIR),
				)
			);
		}
		if( in_array($_REQUEST['page'], $this->getOptionList()) ) {
			wp_enqueue_style('bootstrapstyle', plugins_url(TRAFFIC_PLUGIN_DIR).'/bootstrap/css/traffic_custom.css');
		}
	}
	public function prepare_table_lists() {
		$tables  = '';
		$results = $this->traffic_get_tables();
		$tables .= '<table class="table table-striped">';
		$tables .= '<tr><thead><th>Tables</th><th>Select</th></thead></tr>';
		$tables .= '<tbody>';
		$options_list = $this->getOptionList();
		foreach($results as $key => $tableName) {
			$checked = '';
			if( array_search($tableName, $options_list) !== false ) {
				$checked = 'checked';
			}
			$tables .= '<tr>';
			$tables .= '<td><label for="'.$tableName.'">'.$tableName.'</label></td>';
			$tables .= '<td><input type="checkbox" '.$checked.' class="table_name" id="'.$tableName.'" value="'.$tableName.'" name="table_name[]"></td>';
			$tables .= '</tr>';
		}
		$tables .= "</tbody>";
		$tables .= "</table>";

		return $tables;
	}
	public function export_csv($id) {
		$page         = explode('?page=', $_REQUEST['page']);
		$request_page = $page[1];
		$name         = $this::prepare_human_readable_columns($request_page);
		$this->setup_menu($request_page, $name);
		if( ! current_user_can('manage_options') || empty($this->csv_headers) ) {
			return;
		}
		global $wpdb;
		$filename         = 'export_csv-'.date('d-M-Y-h-i-s').'.csv';
		$resultGetColumns = $this->getFieldsCommaSeprated(false);
		$sql              = "SELECT {$resultGetColumns} FROM {$this->table_name} WHERE `ID` IN (".implode(',', array_map('intval', $id)).")";
		$results          = $wpdb->get_results($sql);
		$csv_headers      = $this->csv_headers;
		if( count($csv_headers) != count(explode(',', $resultGetColumns)) ) {
			wp_die('csv headers are not equal to fields.');
		}
		$directory = TRAFFIC_PLUGIN_EXPORT_DIR;
		if( ! file_exists($directory) ) {
			mkdir($directory, 0777, true);
		}
		$file_path     = $directory.'/'.$filename;
		$output_handle = fopen($file_path, 'w');
		fputs($output_handle, "\xEF\xBB\xBF");//write utf-8 BOM to file
		fputcsv($output_handle, $csv_headers);
		foreach($results as $result) {
			fputcsv($output_handle, (array) $result);
		}
		fclose($output_handle);
		$this->file_name = $filename;

		return true;
	}
	public function csv_export_all() {
		global $wpdb;
		$request_page = $_REQUEST['page'];
		$name         = $this::prepare_human_readable_columns($request_page);
		$this->setup_menu($request_page, $name);
		$filename         = 'export-all-'.date('d-M-Y-h-i-s').'.csv';
		$resultGetColumns = $this->getFieldsCommaSeprated(false);
		$sql              = "SELECT {$resultGetColumns} FROM {$this->table_name} ORDER BY {$this->unique_id}";
		$results          = $wpdb->get_results($sql);
		$csv_headers      = $this->csv_headers;
		if( count($csv_headers) != count(explode(',', $resultGetColumns)) ) {
			wp_die('csv headers are not equal to fields.');
		}
		$directory = TRAFFIC_PLUGIN_EXPORT_DIR;
		if( ! file_exists($directory) ) {
			mkdir($directory, 0777, true);
		}
		$file_path     = $directory.'/'.$filename;
		$output_handle = fopen($file_path, 'w');
		fputs($output_handle, "\xEF\xBB\xBF");//write utf-8 BOM to file
		fputcsv($output_handle, $csv_headers);
		foreach($results as $result) {
			fputcsv($output_handle, (array) $result);
		}
		fclose($output_handle);
		$this->file_name = $filename;

		return true;
	}
	/**
	 * @return array
	 */
	public function getCsvHeaders() {
		return $this->csv_headers;
	}
	/**
	 * @param array $csv_headers
	 */
	public function setCsvHeaders($csv_headers) {
		$this->csv_headers = $csv_headers;
	}
	private function traffic_get_tables() {
		global $wpdb, $table_prefix;
		$sql                  = "SHOW TABLES LIKE '%'";
		$results              = $wpdb->get_results($sql, ARRAY_A);
		$format_array         = array();
		$default_tables_array = array(
			$table_prefix.'commentmeta',
			$table_prefix.'comments',
			$table_prefix.'links',
			$table_prefix.'options',
			$table_prefix.'postmeta',
			$table_prefix.'posts',
			$table_prefix.'terms',
			$table_prefix.'termmeta',
			$table_prefix.'term_relationships',
			$table_prefix.'term_taxonomy',
			$table_prefix.'usermeta',
			$table_prefix.'users',
			$table_prefix.'icl_cms_nav_cache',
			$table_prefix.'icl_content_status',
			$table_prefix.'icl_core_status',
			$table_prefix.'icl_flags',
			$table_prefix.'icl_languages',
			$table_prefix.'icl_languages_translations',
			$table_prefix.'icl_locale_map',
			$table_prefix.'icl_message_status',
			$table_prefix.'icl_node',
			$table_prefix.'icl_reminders',
			$table_prefix.'icl_string_packages',
			$table_prefix.'icl_string_pages',
			$table_prefix.'icl_string_positions',
			$table_prefix.'icl_string_status',
			$table_prefix.'icl_string_translations',
			$table_prefix.'icl_string_urls',
			$table_prefix.'icl_strings',
			$table_prefix.'icl_translate',
			$table_prefix.'icl_translate_job',
			$table_prefix.'icl_translation_batches',
			$table_prefix.'icl_translation_status',
			$table_prefix.'icl_translations',
			$table_prefix.'itsec_lockouts',
			$table_prefix.'itsec_log',
			$table_prefix.'itsec_temp',
			$table_prefix.'yoast_seo_links',
			$table_prefix.'yoast_seo_meta',
		);
		foreach($results as $index => $value) {
			foreach($value as $tableName) {
				if( ! in_array($tableName, $default_tables_array) ) {
					$format_array[] = $tableName;
				}
			}
		}

		return $format_array;
	}
	public function generate_form() {
		$html = '<div class="traffic_container container">
					<div class="row">
						<div class="col-md-12"><h1>Traffic Form Settings</h1></div>
						<div class="col-md-12 msg"></div>
					</div>
					<div class="row">
					   <div class="col-md-12">
					   		<form  id="traffic_form">
									'.$this->prepare_table_lists().'
							  <button type="submit" class="btn btn-primary">Submit</button>
							</form>
						</div>
					  <div class="col-md-4"></div>
					</div>
				</div>';
		echo $html;
	}
	public static function prepare_human_readable_columns($string) {
		if( strpos($string, '_') !== false ) {
			return ucwords(str_replace('_', ' ', $string));
		}
		else {
			$re = '/(?<=[a-z])(?=[A-Z])/x';
			$a  = preg_split($re, $string);

			return join($a, " ");
		}
	}
}
