<?php

namespace LiveuEventsLog\Admin;


use LiveuEventsLog\Admin\Api\Api;
use WP_List_Table;

class AdminListTable extends WP_List_Table
{
	private Api $api;
	private $table_data;


	public function __construct (Api $api, $args = array())
	{
		parent::__construct($args);
		$this->api = $api;

		$this->prepare_items();
	}

	function get_sortable_columns ()
	{
		return [
			'user' => ['user_id', true],
			'date' => ['date', true],
			'action' => ['action', true],
			'post_url' => ['post_id', true],
			'post_type' => ['post_type', true]
		];
	}

	function get_hidden_columns() {
		return [
			'new'
		];
	}

	function get_columns(): array
	{
		$columns = array(
			'cb'  => '<input type="checkbox" />',
			'user' => 'User',
			'action' => 'Action',
			'post_url' => 'Post url',
			'date' => 'Date & Time',
			'post_type' => 'Post Type',
			'new' => 'New'
		);

		return $columns;
	}

	public function column_cb ($item)
	{
		return sprintf('<input type="checkbox" name="events[]" value="%s" />', $item['id']);
	}

	function column_default( $item, $column_name ): string
	{

		$cssClass = ($item['new'] === '1') ? 'event-unread' : '';

		switch ( $column_name ) {
			case 'id':
			case 'user_id':
			case 'action':
			case 'post_url':
			case 'date':
			case 'post_type':
			default:
				return '<span class="'.$cssClass.'">'.$item[$column_name].'</span>';
		}
	}

	public function prepare_items() {

		$this->table_data = $this->get_table_data();

		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$primary = 'name';
		$this->_column_headers = array($columns, $hidden, $sortable, $primary);

		$this->items = $this->table_data;
	}

	private function get_table_data(): array
	{
		global $wpdb;

		$paged = 1;
		$per_page = 10;

		$order_by = sanitize_sql_orderby(isset($_REQUEST['orderby']) ? trim($_REQUEST['orderby']) : null);
		$order = sanitize_sql_orderby(isset($_REQUEST['order']) ?  trim($_REQUEST['order']) : 'desc');

		if(isset($_REQUEST['paged']))
			$paged = (int)$_REQUEST['paged'];

		if(empty($order_by))
			$order_by = 'id';

		if(empty($order))
			$order = 'desc';

		$params = [
			'order_by' => $order_by,
			'order_dir' => $order,
			'offset' => ($paged - 1) * $per_page,
			'length' => $per_page,
		];

		$this->set_pagination_args([
			"total_items" => $this->api->get_events_count(),
			"per_page" => $per_page
								   ]);

		return $this->api->get_events_list($params);
	}

	public function get_bulk_actions (): array
	{
		return [
			'set_read' => "Mark as read",
			'delete' => "Delete"
		];
	}

}
