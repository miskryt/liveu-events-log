<?php

namespace LiveuEventsLog\Services;


use LiveuEventsLog\Config\Config;

class NotifiersLoader extends Service
{

	public function loaded ()
	{
		$arr_notifiers_to_instantiate = Config::get_instance()->get_notifiers();

		foreach ( $arr_notifiers_to_instantiate as $notifier ) {
			//$stack = new Notifier();
			//$stack = new SlackNotifier($stack);
			//$stack = new AdminWebNotifier($stack);
		}
	}
}
