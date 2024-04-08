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
				return "Create";
			break;
			case 2:
				return "Publish";
			break;
			case 3:
				return "Update";
			break;
			case 4:
				return "Trash";
			break;
			case 5:
				return "Delete";
			break;
			case 6:
				return "Draft";
			break;
			case 7:
				return "Restore";
			break;
			default:
				return "";
		}
	}
}
