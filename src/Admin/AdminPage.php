<?php

namespace LiveuEventsLog\Admin;

use LiveuEventsLog\Admin\Model\Model;

class AdminPage
{
	private Model $model;
	private IView $viewer;

	public function __construct(Model $model, IView $viewer) {
		$this->model = $model;
		$this->viewer = $viewer;
	}


	public function show() {
	    $data = [
	    	'title' => 'Admin page',
			'new_count' => $this->model->records_count()
		];

	    if(isset($_REQUEST['action']) &&  $_REQUEST['action'] === 'show_diff')
		{
			$this->viewer->render('templates/diff', $data);
		}
		else
			$this->viewer->render('templates/admin', $data);
	}
}
