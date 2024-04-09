<?php
namespace LiveuEventsLog\Admin\Model;

use LiveuEventsLog\Admin\Interfaces\IModel;
use LiveuEventsLog\EnumActions;

class Model implements IModel {

	public function __construct() {

	}

	public function get_events_list () : array {
		$search = $_REQUEST['search'] ?? '';
		$start  = $_REQUEST['start'] ?? 0;
		$length = $_REQUEST['length'] ?? 10;
		$draw   = $_REQUEST['draw'] ?? 1;

		check_ajax_referer( 'myajax-nonce', 'nonce_code' );

		$table_name = EVENTS_DATABASE_TABLE;
		global $wpdb;

		$sql = $wpdb->prepare( "SELECT * FROM $table_name limit %d offset %d", $length, $start );

		$table_data = $wpdb->get_results( $sql );
		$table_count = $this->get_records_count();


		$data = [];
		foreach ($table_data as $td){

			$d = [
				"id" => $td->id,
				"user" => get_user_by('id', $td->user_id)->user_login,
				"action" => EnumActions::get($td->action),
				"post_url" =>
					'<a  href="?page=liveu-events&action=show_diff&diff_id='.$td->id.'">'.
					get_post($td->post_id)->post_title.
					'</a>&nbsp;<a target="_blank" href="'.get_edit_post_link( $td->post_id).'"><i class="levlog-list-share-icon iconoir-open-in-window"></i></a>',

				"datetime" => $td->date,
				"post_type" => $td->post_type,
				"new" => $td->new,
			];

			$data[] = $d;
		}

		$send_data['data'] = $data;
		$send_data['draw'] = $draw;
		$send_data['recordsTotal'] = $table_count;
		$send_data['recordsFiltered'] = $table_count;

		return ($send_data);
	}

	public function get_records_count() : int {
		global $wpdb;

		$table_name = EVENTS_DATABASE_TABLE;
		$sql = ("SELECT count(id) as total FROM $table_name");

		return $wpdb->get_results( $sql )[0]->total;
	}
}
