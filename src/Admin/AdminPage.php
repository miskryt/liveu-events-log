<?php

namespace LiveuEventsLog\Admin;

use LiveuEventsLog\Admin\Interfaces\IModel;
use LiveuEventsLog\Admin\Interfaces\IView;

class AdminPage
{
	private static IModel $model;
	private static IView $viewer;

	public function __construct(IModel $model, IView $viewer) {
		self::$model = $model;
		self::$viewer = $viewer;
	}

	public function get_events_list_callback() {
		$result = self::$model->get_events_list();

		echo json_encode($result, JSON_THROW_ON_ERROR);
		wp_die();
	}

	public static function show(): void {
	    $data = [
	    	'title' => 'Admin page',
			'new_count' => self::$model->get_records_count()
		];

	    if(isset($_REQUEST['action']) &&  $_REQUEST['action'] === 'show_diff')
		{
			self::$viewer->render('templates/diff', $data);
		}
		else
			self::$viewer->render('templates/admin', $data);
	}
}
