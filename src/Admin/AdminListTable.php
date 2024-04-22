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



	function get_columns() {
		$columns = array(
			'cb'  => '<input type="checkbox" />',
			'user' => 'User',
			'action' => 'Action',
			'post_url' => 'Post url',
			'datetime' => 'Date & Time',
			'post_type' => 'Post Type'
		);

		return $columns;
	}

	function column_default( $item, $column_name ) {


		switch ( $column_name ) {
			case 'id':
			case 'user':
			case 'action':
			case 'post_url':
			case 'datetime':
			case 'post_type':
			default:
				return $item[ $column_name ];
		}
	}

	function prepare_items() {

		$this->table_data = $this->get_table_data();

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$primary = 'name';
		$this->_column_headers = array($columns, $hidden, $sortable, $primary);

		$this->items = $this->table_data;
	}

	private function get_table_data() {
		global $wpdb;

		return $this->api->get_events_list();
	}

}
