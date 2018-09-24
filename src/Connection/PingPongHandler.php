<?php

/**
 * Copyright 2018 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Connection;

use WildPHP\Core\ComponentContainer;
use WildPHP\Core\Configuration\Configuration;
use WildPHP\Core\Connection\IRCMessages\PING;
use WildPHP\Core\ContainerTrait;
use WildPHP\Core\EventEmitter;
use WildPHP\Core\Logger\Logger;
use WildPHP\Core\Modules\BaseModule;

class PingPongHandler extends BaseModule
{
    use ContainerTrait;

    /**
     * @var int
     */
    protected $lastMessageReceived = 0;

    /**
     * The amount of seconds per time the checking loop is run.
     * Do not set this too high or the ping handler won't be effective.
     * @var int
     */
    protected $loopInterval = 2;

    /**
     * In seconds.
     * @var int
     */
    protected $pingInterval = 180;

    /**
     * In seconds.
     * @var int
     */
    protected $disconnectInterval = 120;

    /**
     * @var bool
     */
    protected $hasSentPing = false;

    /**
     * PingPongHandler constructor.
     *
     * @param ComponentContainer $container
     * @throws \Yoshi2889\Container\NotFoundException
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function __construct(ComponentContainer $container)
    {
        EventEmitter::fromContainer($container)
            ->on('irc.line.in', [$this, 'updateLastMessageReceived']);

        EventEmitter::fromContainer($container)
            ->on('irc.line.in.ping', [$this, 'sendPong']);

        $this->updateLastMessageReceived();
        $this->setContainer($container);

        $this->registerPingLoop();
    }

    /**
     * @param PING $pingMessage
     * @param Queue $queue
     */
    public function sendPong(PING $pingMessage, Queue $queue)
    {
        $queue->pong($pingMessage->getServer1(), $pingMessage->getServer2());
    }

    public function updateLastMessageReceived()
    {
        $this->lastMessageReceived = time();
        $this->hasSentPing = false;
    }

    protected function registerPingLoop()
    {
        $this->getContainer()->getLoop()->addPeriodicTimer($this->loopInterval,
            function () {
                $currentTime = time();

                $disconnectTime = $this->lastMessageReceived + $this->pingInterval + $this->disconnectInterval;
                $shouldDisconnect = $currentTime >= $disconnectTime;

                if ($shouldDisconnect) {
                    return $this->forceDisconnect();
                }

                $scheduledPingTime = $this->lastMessageReceived + $this->pingInterval;
                $shouldSendPing = $currentTime >= $scheduledPingTime && !$this->hasSentPing;

                if ($shouldSendPing) {
                    return $this->sendPing();
                }

                return true;
            });
    }

    /**
     * @return bool
     * @throws \Yoshi2889\Container\NotFoundException
     * @throws \Yoshi2889\Container\NotFoundException
     * @throws \Yoshi2889\Container\NotFoundException
     */
    protected function sendPing()
    {
        Logger::fromContainer($this->getContainer())
            ->debug('No message received from the server in the last ' . $this->pingInterval . ' seconds. Sending PING.');

        $server = Configuration::fromContainer($this->getContainer())['serverConfig']['hostname'];

        Queue::fromContainer($this->getContainer())
            ->ping($server);

        $this->hasSentPing = true;

        return true;
    }

    /**
     * @return bool
     * @throws \Yoshi2889\Container\NotFoundException
     * @throws \Yoshi2889\Container\NotFoundException
     * @throws \Yoshi2889\Container\NotFoundException
     */
    protected function forceDisconnect()
    {
        Logger::fromContainer($this->getContainer())
            ->warning('The server has not responded to the last PING command. Is the network down? Closing link.');

        Queue::fromContainer($this->getContainer())
            ->quit('No vital signs detected, closing link...');

        EventEmitter::fromContainer($this->getContainer())
            ->emit('irc.force.close');

        return true;
    }

    /**
     * @return int
     */
    public function getLastMessageReceivedTime(): int
    {
        return $this->lastMessageReceived;
    }

    /**
     * @return string
     */
    public static function getSupportedVersionConstraint(): string
    {
        return WPHP_VERSION;
    }
}