<?php
namespace LiveuEventsLog\Notifiers;

/**
 * Конкретные Декораторы вызывают обёрнутый объект и изменяют его результат
 * некоторым образом.
 */
class SlackNotifier extends Decorator {

	/**
	 * Декораторы могут вызывать родительскую реализацию операции, вместо того,
	 * чтобы вызвать обёрнутый объект напрямую. Такой подход упрощает расширение
	 * классов декораторов.
	 */
	public function send(string $message): void {
		parent::send($message);
		$this->send_to_slack($message);
	}

	public function send_to_slack($message) {
		echo "Slack sending: ".$message;
	}
}
