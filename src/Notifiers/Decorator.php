<?php
namespace LiveuEventsLog\Notifiers;


/**
 * Базовый класс Декоратора следует тому же интерфейсу, что и другие компоненты.
 * Основная цель этого класса - определить интерфейс обёртки для всех конкретных
 * декораторов. Реализация кода обёртки по умолчанию может включать в себя поле
 * для хранения завёрнутого компонента и средства его инициализации.
 */
class Decorator implements Notification {

	private $component;

	public function __construct(Notification $component)
	{
		$this->component = $component;
	}

	/**
	 * Декоратор делегирует всю работу обёрнутому компоненту.
	 */
	public function send(string $message): void {
		$this->component->send($message);
	}
}
