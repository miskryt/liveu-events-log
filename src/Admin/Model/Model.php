<?php
namespace LiveuEventsLog\Admin\Model;

use LiveuEventsLog\Admin\Interfaces\IModel;

class Model implements IModel {

	public function get_events_list(array $params) : array {
		$table_name = EVENTS_DATABASE_TABLE;
		global $wpdb;

		$start  = $params['start']  ?? 0;
		$length = $params['length'] ?? 10;

		$sql = $wpdb->prepare( "SELECT * FROM $table_name limit %d offset %d", $length, $start );
		return $wpdb->get_results($sql);
	}

	public function get_events_count() : int {
		global $wpdb;

		$table_name = EVENTS_DATABASE_TABLE;
		$sql = ("SELECT count(id) as total FROM $table_name");

		return $wpdb->get_results( $sql )[0]->total;
	}

	public function get_event_data_by_id(int $id) {
		$event = $this->get_event_by_id($id);
		$event_context = $this->get_event_context_by_event_id($id);

		return [

		];

	}

	private function get_event_by_id(int $id) {
		global $wpdb;

		$table_name = EVENTS_DATABASE_TABLE;
		$sql = sprintf("SELECT * FROM %s WHERE `id`= %d", $table_name, $id);

		return $wpdb->get_results($sql);
	}

	private function get_event_context_by_event_id(int $event_id) {
		global $wpdb;

		$table_name = EVENTS_CONTEXT_TABLE;
		$sql = sprintf("SELECT * FROM %s WHERE `event_id`= %d", $table_name, $event_id);

		return $wpdb->get_results($sql);
	}
}
