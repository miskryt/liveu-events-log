<?php

namespace LiveuEventsLog\Notifiers;


/**
 * Базовый интерфейс Компонента определяет поведение, которое изменяется
 * декораторами.
 */
interface Notification{
	public function send(string $message): void;
}







