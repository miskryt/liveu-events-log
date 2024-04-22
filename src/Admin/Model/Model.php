<?php
namespace LiveuEventsLog\Admin\Model;

use LiveuEventsLog\Admin\Interfaces\IModel;

class Model implements IModel {

	public function get_events_list(array $params, $return_type = OBJECT) : array {
		$table_name = EVENTS_DATABASE_TABLE;
		global $wpdb;

		$start  = $params['start']  ?? 0;
		$length = $params['length'] ?? 10;
		$order_by = $params['order_by'] ?? 'id';
		$asc_desc = $params['order_dir'] ?? 'desc';

		$sql = $wpdb->prepare( "SELECT * FROM $table_name order by $order_by $asc_desc limit %d offset %d", $length, $start );
		return $wpdb->get_results($sql, $return_type);
	}

	public function get_events_count() : int {
		global $wpdb;

		$table_name = EVENTS_DATABASE_TABLE;
		$sql = ("SELECT count(id) as total FROM $table_name");

		return $wpdb->get_results( $sql )[0]->total;
	}



	public function get_event_by_id(int $id): array {
		global $wpdb;

		$sql = sprintf("SELECT * FROM %s WHERE `id`= %d", EVENTS_DATABASE_TABLE, $id);

		$event = $wpdb->get_results($sql, ARRAY_A)[0];
		$contexts = $this->get_event_context_by_event_id($id);

		$r = [];
		foreach ($contexts as $context_item)
		{
			$r[$context_item['key']] = $context_item['value'];
		}

		$event['context'] = $r;

		return $event;
	}

	private function get_event_context_by_event_id(int $event_id): array {
		global $wpdb;

		$sql = sprintf("SELECT * FROM %s WHERE `event_id`= %d", EVENTS_CONTEXT_TABLE, $event_id);

		return $wpdb->get_results($sql, ARRAY_A);
	}
}
