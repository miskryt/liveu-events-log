<?php
namespace LiveuEventsLog\Notifiers;


/**
 * Конкретные Компоненты предоставляют реализации поведения по умолчанию. Может
 * быть несколько вариаций этих классов.
 */
class EmailNotifier implements Notification
{
	public function send(string $message): void
	{
		echo "Email Notifier: " . $message . " <br/>";
	}
}
