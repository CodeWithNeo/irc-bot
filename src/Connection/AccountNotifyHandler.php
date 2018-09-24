<?php
/**
 * Copyright 2018 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Connection;


use WildPHP\Core\ComponentContainer;
use WildPHP\Core\Connection\IRCMessages\ACCOUNT;
use WildPHP\Core\ContainerTrait;
use WildPHP\Core\Database\Database;
use WildPHP\Core\EventEmitter;
use WildPHP\Core\Logger\Logger;
use WildPHP\Core\Modules\BaseModule;
use WildPHP\Core\Users\User;

class AccountNotifyHandler extends BaseModule
{
	use ContainerTrait;

    /**
     * AccountNotifyHandler constructor.
     *
     * @param ComponentContainer $container
     * @throws \Yoshi2889\Container\NotFoundException
     */
	public function __construct(ComponentContainer $container)
	{
		EventEmitter::fromContainer($container)->on('irc.line.in.account', [$this, 'updateUserIrcAccount']);
		$this->setContainer($container);
	}

	/** @noinspection PhpUnusedParameterInspection */
    /**
     * @param ACCOUNT $ircMessage
     * @param Queue $queue
     * @throws \WildPHP\Core\StateException
     * @throws \WildPHP\Core\Users\UserNotFoundException
     * @throws \Yoshi2889\Container\NotFoundException
     */
	public function updateUserIrcAccount(ACCOUNT $ircMessage, Queue $queue)
	{
		$nickname = $ircMessage->getPrefix()->getNickname();
		$db = Database::fromContainer($this->getContainer());

		$user = User::fromDatabase($db, ['nickname' => $nickname]);
		Logger::fromContainer($this->getContainer())->debug('Updated irc account for userid ' . $user->getId());
		$user->setIrcAccount($ircMessage->getAccountName());

		User::toDatabase($db, $user);
	}

	/**
	 * @return string
	 */
	public static function getSupportedVersionConstraint(): string
	{
		return WPHP_VERSION;
	}
}