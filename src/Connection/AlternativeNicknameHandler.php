<?php
/**
 * Copyright 2018 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Connection;


use Evenement\EventEmitterInterface;
use Psr\Log\LoggerInterface;
use WildPHP\Core\Configuration\Configuration;

class AlternativeNicknameHandler
{

    protected $nicknames = [];
    protected $tryNicknames = [];

    /**
     * @var EventEmitterInterface
     */
    private $eventEmitter;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var IrcConnectionInterface
     */
    private $ircConnection;
    /**
     * @var QueueInterface
     */
    private $queue;

    /**
     * NicknameHandler constructor.
     *
     * @param EventEmitterInterface $eventEmitter
     * @param Configuration $configuration
     * @param LoggerInterface $logger
     * @param IrcConnectionInterface $ircConnection
     * @param QueueInterface $queue
     */
    public function __construct(
        EventEmitterInterface $eventEmitter,
        Configuration $configuration,
        LoggerInterface $logger,
        IrcConnectionInterface $ircConnection,
        QueueInterface $queue
    ) {
        if (empty($configuration['alternativeNicknames'])) {
            return;
        }

        $this->nicknames = $configuration['alternativeNicknames'];

        // 001: RPL_WELCOME
        $eventEmitter->on('irc.line.in.001', [$this, 'deregisterListeners']);

        // 431: ERR_NONICKNAMEGIVEN
        // 432: ERR_ERRONEUSNICKNAME
        // 433: ERR_NICKNAMEINUSE
        // 436: ERR_NICKCOLLISION
        $eventEmitter->on('irc.line.in.431', [$this, 'chooseAlternateNickname']);
        $eventEmitter->on('irc.line.in.432', [$this, 'chooseAlternateNickname']);
        $eventEmitter->on('irc.line.in.433', [$this, 'chooseAlternateNickname']);
        $eventEmitter->on('irc.line.in.436', [$this, 'chooseAlternateNickname']);

        $this->eventEmitter = $eventEmitter;
        $this->logger = $logger;
        $this->ircConnection = $ircConnection;
        $this->queue = $queue;
    }

    public function deregisterListeners()
    {
        $this->eventEmitter->removeListener('irc.line.in.431', [$this, 'chooseAlternateNickname']);
        $this->eventEmitter->removeListener('irc.line.in.432', [$this, 'chooseAlternateNickname']);
        $this->eventEmitter->removeListener('irc.line.in.433', [$this, 'chooseAlternateNickname']);
        $this->eventEmitter->removeListener('irc.line.in.436', [$this, 'chooseAlternateNickname']);
    }

    /** @noinspection PhpUnusedParameterInspection */

    /**
     * @return void
     */
    public function chooseAlternateNickname()
    {
        if (empty($this->tryNicknames)) {
            $this->tryNicknames = $this->nicknames;
        }

        if (empty($this->nicknames)) {
            $this->logger->warning('Out of nicknames to try; giving up.');
            $this->ircConnection->close();
            return;
        }

        $nickname = array_shift($this->nicknames);
        $this->queue->nick($nickname);
    }

    /**
     * @return string
     */
    public static function getSupportedVersionConstraint(): string
    {
        return WPHP_VERSION;
    }
}