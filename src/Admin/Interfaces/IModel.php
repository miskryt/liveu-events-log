<?php


namespace LiveuEventsLog\Admin\Interfaces;


interface IModel
{
	public function get_events_list(array $params) : array;
	public function get_events_count() : int;
	public function get_event_by_id(int $id): array;
	public function get_event_context_by_event_id(int $event_id): array;
}
