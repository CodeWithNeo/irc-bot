<?php
/**
 * Copyright 2017 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Users;

use WildPHP\Core\Channels\Channel;
use WildPHP\Core\Channels\ChannelCollection;
use WildPHP\Core\Channels\ChannelModes;
use WildPHP\Core\ComponentContainer;
use WildPHP\Core\Configuration\Configuration;
use WildPHP\Core\Connection\IRCMessages\JOIN;
use WildPHP\Core\Connection\IRCMessages\KICK;
use WildPHP\Core\Connection\IRCMessages\MODE;
use WildPHP\Core\Connection\IRCMessages\NICK;
use WildPHP\Core\Connection\IRCMessages\PART;
use WildPHP\Core\Connection\IRCMessages\PRIVMSG;
use WildPHP\Core\Connection\IRCMessages\QUIT;
use WildPHP\Core\Connection\IRCMessages\RPL_ENDOFNAMES;
use WildPHP\Core\Connection\IRCMessages\RPL_NAMREPLY;
use WildPHP\Core\Connection\IRCMessages\RPL_WHOSPCRPL;
use WildPHP\Core\Connection\Queue;
use WildPHP\Core\ContainerTrait;
use WildPHP\Core\EventEmitter;
use WildPHP\Core\Logger\Logger;
use WildPHP\Core\Modules\BaseModule;

class UserStateManager extends BaseModule
{
	use ContainerTrait;

	/**
	 * UserStateManager constructor.
	 *
	 * @param ComponentContainer $container
	 */
	public function __construct(ComponentContainer $container)
	{
		$events = [
			// 366: RPL_ENDOFNAMES
			'irc.line.in.366' => 'sendInitialWhoxMessage',

			// 354: RPL_WHOSPCRPL
			'irc.line.in.354' => 'processWhoxReply',

			// 353: RPL_NAMREPLY
			'irc.line.in.353' => 'processNamesReply',

			'irc.line.in.join' => 'processUserJoin',
			'irc.line.in.quit' => 'processUserQuit',
			'irc.line.in.nick' => 'processUserNicknameChange',
			'irc.line.in.mode' => 'processUserModeChange',
			'irc.line.in.part' => 'processUserPart',
			'irc.line.in.kick' => 'processUserKick'
		];

		foreach ($events as $event => $callback)
		{
			EventEmitter::fromContainer($container)
				->on($event, [$this, $callback]);
		}

		EventEmitter::fromContainer($container)->first('irc.line.in.privmsg', [$this, 'processConversation']);

		$this->setContainer($container);
	}

	/**
	 * @param PART|KICK $ircMessage
	 * @param string $channel
	 * @param string $target
	 */
	public function processUserPartOrKick($ircMessage, string $channel, string $target)
	{
		$ownNickname = Configuration::fromContainer($this->getContainer())['currentNickname'];
		$channel = ChannelCollection::fromContainer($this->getContainer())->findByChannelName($channel);

		if (!$channel)
			return;

		$user = $channel->getUserCollection()->findByNickname($ircMessage->getNickname());

		if (!$user)
			return;

		if ($target == $ownNickname)
		{
			ChannelCollection::fromContainer($this->getContainer())->removeAll($channel);
			return;
		}

		$channel->getUserCollection()->removeAll($user);
		EventEmitter::fromContainer($this->getContainer())->emit('user.left', [$channel, $user, Queue::fromContainer($this->getContainer())]);
	}

	/**
	 * @param PART $ircMessage
	 */
	public function processUserPart(PART $ircMessage)
	{
		$channel = $ircMessage->getChannels()[0];
		$this->processUserPartOrKick($ircMessage, $channel, $ircMessage->getNickname());
	}

	/**
	 * @param KICK $ircMessage
	 */
	public function processUserKick(KICK $ircMessage)
	{
		$channel = $ircMessage->getChannel();
		$this->processUserPartOrKick($ircMessage, $channel, $ircMessage->getTarget());
	}

	/**
	 * @param JOIN $ircMessage
	 * @param Queue $queue
	 */
	public function processUserJoin(JOIN $ircMessage, Queue $queue)
	{
		$availablemodes = Configuration::fromContainer($this->getContainer())['serverConfig']['prefix'];
		$channelName = $ircMessage->getChannels()[0];

		if (!($channel = ChannelCollection::fromContainer($this->getContainer())->findByChannelName($channelName)))
		{
			$channel = new Channel($channelName, new UserCollection(), new ChannelModes($availablemodes));
			ChannelCollection::fromContainer($this->getContainer())
				->append($channel);
		}

		if ($channel->getUserCollection()->findByNickname($ircMessage->getNickname()))
			return;

		$prefix = $ircMessage->getPrefix();
		$userObject = new User($ircMessage->getNickname(), $prefix->getHostname(), $prefix->getUsername(), $ircMessage->getIrcAccount());
		$channel->getUserCollection()->append($userObject);

		EventEmitter::fromContainer($this->getContainer())
			->emit('user.join', [$userObject, $channel, $queue]);

		Logger::fromContainer($this->getContainer())
			->debug('Added user to channel.',
				[
					'reason' => 'join',
					'nickname' => $userObject->getNickname(),
					'channel' => $channel->getName()
				]);
	}

	/**
	 * @param RPL_NAMREPLY $ircMessage
	 */
	public function processNamesReply(RPL_NAMREPLY $ircMessage)
	{
		$channel = ChannelCollection::fromContainer($this->getContainer())->findByChannelName($ircMessage->getChannel());

		if (!$channel)
			return;

		$nicknames = $ircMessage->getNicknames();

		foreach ($nicknames as $nicknameWithMode)
		{
			$nickname = '';
			$modes = $channel->getChannelModes()->extractUserModesFromNickname($nicknameWithMode, $nickname);

			$user = new User($nickname);

			$channel->getChannelModes()->addUserToModes($modes, $user);
			$channel->getUserCollection()->append($user);
		}
	}

	/**
	 * @param RPL_ENDOFNAMES $ircMessage
	 * @param Queue $queue
	 */
	public function sendInitialWhoxMessage(RPL_ENDOFNAMES $ircMessage, Queue $queue)
	{
		$channel = $ircMessage->getChannel();
		$queue->who($channel, '%nuhaf');
	}

	/**
	 * @param RPL_WHOSPCRPL $ircMessage
	 */
	public function processWhoxReply(RPL_WHOSPCRPL $ircMessage)
	{
		$username = $ircMessage->getUsername();
		$hostname = $ircMessage->getHostname();
		$nickname = $ircMessage->getNickname();
		$accountname = $ircMessage->getAccountname();

		/** @var Channel $channel */
		foreach (ChannelCollection::fromContainer($this->getContainer())->getArrayCopy() as $channel)
		{
			if (!($user = $channel->getUserCollection()->findByNickname($nickname)))
				continue;

			$user->setIrcAccount($accountname);
			$user->setHostname($hostname);
			$user->setUsername($username);
		}
	}

	/**
	 * @param QUIT $incomingIrcMessage
	 * @param Queue $queue
	 */
	public function processUserQuit(QUIT $incomingIrcMessage, Queue $queue)
	{
		$nickname = $incomingIrcMessage->getNickname();

		/** @var Channel $channel */
		foreach (ChannelCollection::fromContainer($this->getContainer()) as $channel)
		{
			if (!($user = $channel->getUserCollection()->findByNickname($nickname)))
				continue;

			$channel->getUserCollection()->removeAll($user);

			EventEmitter::fromContainer($this->getContainer())
				->emit('user.quit', [$channel, $user, $queue]);
		}
	}

	/**
	 * @param NICK $incomingIrcMessage
	 * @param Queue $queue
	 */
	public function processUserNicknameChange(NICK $incomingIrcMessage, Queue $queue)
	{
		$oldNickname = $incomingIrcMessage->getNickname();
		$newNickname = $incomingIrcMessage->getNewNickname();

		/** @var Channel $channel */
		foreach (ChannelCollection::fromContainer($this->getContainer()) as $channel)
		{
			if ($channel->getName() == $oldNickname)
			{
				ChannelCollection::fromContainer($this->getContainer())->removeAll($channel);
				continue;
			}

			if (!($user = $channel->getUserCollection()->findByNickname($oldNickname)))
				continue;

			$user->setNickname($newNickname);

			EventEmitter::fromContainer($this->getContainer())
				->emit('user.nick', [$channel, $user, $oldNickname, $newNickname, $queue]);
		}
	}

	/**
	 * @param MODE $ircMessage
	 * @param Queue $queue
	 */
	public function processUserModeChange(MODE $ircMessage, Queue $queue)
	{
		$mode = $ircMessage->getFlags();
		$target = $ircMessage->getArguments()[0] ?? '';

		$channel = ChannelCollection::fromContainer($this->getContainer())->findByChannelName($ircMessage->getTarget());
		if (!$channel)
			return;

		$user = $channel->getUserCollection()->findByNickname($target);
		if (!$user)
			return;

		$modeCollection = $channel->getChannelModes();

		$chars = str_split($mode);
		$add = array_shift($chars) == '+';
		foreach ($chars as $char)
		{
			if ($add)
				$modeCollection->addUserToMode($char, $user);
			else
				$modeCollection->removeUserFromMode($char, $user);

			EventEmitter::fromContainer($this->getContainer())
				->emit('user.mode.channel', [$channel, $add, $mode, $user, $queue]);
		}
	}

	/**
	 * @param PRIVMSG $ircMessage
	 * @param Queue $queue
	 */
	public function processConversation(PRIVMSG $ircMessage, Queue $queue)
	{
		$ownNickname = Configuration::fromContainer($this->getContainer())['currentNickname'];
		if ($ircMessage->getChannel() != $ownNickname)
			return;

		$channelName = $ircMessage->getNickname();

		if (!($channel = ChannelCollection::fromContainer($this->getContainer())->findByChannelName($channelName)))
		{
			$channel = new Channel($channelName, new UserCollection(), new ChannelModes(''));
			ChannelCollection::fromContainer($this->getContainer())
				->append($channel);
		}

		if ($channel->getUserCollection()->findByNickname($ircMessage->getNickname()))
			return;

		$prefix = $ircMessage->getPrefix();
		$userObject = new User($ircMessage->getNickname(), $prefix->getHostname(), $prefix->getUsername());
		$channel->getUserCollection()->append($userObject);

		$queue->who($ircMessage->getNickname(), '%nuhaf');
	}

	/**
	 * @return string
	 */
	public static function getSupportedVersionConstraint(): string
	{
		return WPHP_VERSION;
	}
}