<?php


namespace LiveuEventsLog;


abstract class EnumActions
{
	const create = 1;
	const publish = 2;
	const update = 3;
	const trash = 4;
	const delete = 5;
	const draft = 6;
	const restore = 7;


	public static function get(int $id) {
		switch ($id){
			case 1:
				return "Created";
			break;
			case 2:
				return "Published";
			break;
			case 3:
				return "Updated";
			break;
			case 4:
				return "Trashed";
			break;
			case 5:
				return "Deleted";
			break;
			case 6:
				return "Drafted";
			break;
			case 7:
				return "Restored";
			break;
			default:
				return "";
		}
	}
}
