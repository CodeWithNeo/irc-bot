<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Channels;


use WildPHP\Core\ComponentContainer;
use WildPHP\Core\Configuration\Configuration;
use WildPHP\Core\Connection\IRCMessages\RPL_TOPIC;
use WildPHP\Core\Connection\IRCMessages\RPL_WELCOME;
use WildPHP\Core\Connection\Queue;
use WildPHP\Core\ContainerTrait;
use WildPHP\Core\EventEmitter;
use WildPHP\Core\Logger\Logger;
use WildPHP\Core\Modules\BaseModule;

class ChannelStateManager extends BaseModule
{
	use ContainerTrait;

	/**
	 * ChannelStateManager constructor.
	 *
	 * @param ComponentContainer $container
	 */
	public function __construct(ComponentContainer $container)
	{
		$events = [
			// 001: RPL_WELCOME
			'irc.line.in.001' => 'joinInitialChannels',

			// 332: RPL_TOPIC
			'irc.line.in.332' => 'processChannelTopicChange',
		];

		foreach ($events as $event => $callback)
		{
			EventEmitter::fromContainer($container)
				->on($event, [$this, $callback]);
		}

		$this->setContainer($container);
	}

	/**
	 * @param RPL_WELCOME $incomingIrcMessage
	 * @param Queue $queue
	 */
	public function joinInitialChannels(RPL_WELCOME $incomingIrcMessage, Queue $queue)
	{
		$channels = Configuration::fromContainer($this->getContainer())['channels'];

		if (empty($channels))
			return;

		$chunks = array_chunk($channels, 3);
		$queue->setFloodControl(true);

		foreach ($chunks as $chunk)
		{
			$queue->join($chunk);
		}

		Logger::fromContainer($this->getContainer())
			->debug('Queued initial channel join.',
				[
					'count' => count($channels),
					'channels' => $channels
				]);
	}

	/**
	 * @param RPL_TOPIC $ircMessage
	 */
	public function processChannelTopicChange(RPL_TOPIC $ircMessage)
	{
		$channel = $ircMessage->getChannel();

		$channelObject = ChannelCollection::fromContainer($this->getContainer())
			->findByChannelName($channel);

		if (!$channelObject)
			return;

		$channelObject->setTopic($ircMessage->getMessage());
		EventEmitter::fromContainer($this->getContainer())
			->emit('channel.topic', [$channelObject, $ircMessage->getMessage()]);
	}

	/**
	 * @return string
	 */
	public static function getSupportedVersionConstraint(): string
	{
		return WPHP_VERSION;
	}
}