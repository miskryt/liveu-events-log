<?php
namespace LiveuEventsLog\Admin\Model;

use LiveuEventsLog\Admin\Interfaces\IModel;

class Model implements IModel {

	public function get_events_list(array $params, $return_type = OBJECT) : array {
		global $wpdb;
		$table_name = EVENTS_DATABASE_TABLE;

		$offset = $params['offset']  ?? 0;
		$length = $params['length'] ?? 10;
		$order_by = $params['order_by'] ?? 'id';
		$asc_desc = $params['order_dir'] ?? 'desc';

		$where = '';

		$sql = $wpdb->prepare( "SELECT * FROM $table_name $where order by $order_by $asc_desc limit %d offset %d", $length, $offset );
//var_dump($sql);
		return $wpdb->get_results($sql, $return_type);
	}

	public function get_events_count() : int {
		global $wpdb;
		$table_name = EVENTS_DATABASE_TABLE;

		$sql = ("SELECT count(id) as total FROM $table_name");

		return $wpdb->get_results( $sql )[0]->total;
	}

	public function get_new_events_count() {
		global $wpdb;
		$table_name = EVENTS_DATABASE_TABLE;

		$sql = ("SELECT count(id) as total FROM $table_name WHERE `new` = 1");

		return $wpdb->get_results( $sql )[0]->total;
	}

	public function get_event(int $id, $return_type = OBJECT) {
		global $wpdb;

		$sql = sprintf("SELECT * FROM %s WHERE `id`= %d", EVENTS_DATABASE_TABLE, $id);

		$event = $wpdb->get_results($sql, $return_type)[0];
		$contexts = $this->get_event_context($id, $return_type);

		$r = [];
		foreach ($contexts as $context_item)
		{
			$r[$context_item->key] = $context_item->value;
		}

		$event->context = $r;

		return $event;
	}

	public function get_event_context(int $event_id, $return_type = OBJECT): array {
		global $wpdb;

		$sql = sprintf("SELECT * FROM %s WHERE `event_id`= %d", EVENTS_CONTEXT_TABLE, $event_id);
//var_dump($sql);
		return $wpdb->get_results($sql, $return_type);
	}
}
