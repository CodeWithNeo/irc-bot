<?php

/**
 * Copyright 2018 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Connection\Capabilities;

use WildPHP\Core\ComponentContainer;
use WildPHP\Core\Connection\Queue;
use WildPHP\Core\ContainerTrait;
use WildPHP\Core\EventEmitter;
use WildPHP\Core\Logger\Logger;
use WildPHP\Core\Modules\BaseModule;
use WildPHP\Messages\Cap;
use Yoshi2889\Container\ComponentInterface;
use Yoshi2889\Container\ComponentTrait;

class CapabilityHandler extends BaseModule implements ComponentInterface
{
    use ComponentTrait;
    use ContainerTrait;

    /**
     * @var array
     */
    protected $availableCapabilities = [];

    /**
     * @var array
     */
    protected $queuedCapabilities = [];

    /**
     * @var array
     */
    protected $acknowledgedCapabilities = [];

    /**
     * @var array
     */
    protected $notAcknowledgedCapabilities = [];

    /**
     * @var bool
     */
    protected $saslIsComplete = false;

    /**
     * CapabilityHandler constructor.
     *
     * @param ComponentContainer $container
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function __construct(ComponentContainer $container)
    {
        $eventEmitter = EventEmitter::fromContainer($container);
        $eventEmitter->on('irc.line.in.cap', [$this, 'responseRouter']);
        $eventEmitter->on('irc.cap.ls', [$this, 'flushRequestQueue']);
        $eventEmitter->on('irc.cap.acknowledged', [$this, 'tryEndNegotiation']);
        $eventEmitter->on('irc.cap.notAcknowledged', [$this, 'tryEndNegotiation']);
        $eventEmitter->on('irc.sasl.complete', [$this, 'setSaslHasCompleted']);
        $this->setContainer($container);

        $this->requestCapability('extended-join');
        $this->requestCapability('account-notify');
        $this->requestCapability('multi-prefix');

        Logger::fromContainer($this->getContainer())
            ->debug('[CapabilityHandler] Capability negotiation started.');
        Queue::fromContainer($container)
            ->cap('LS');
    }

    /**
     * @param string $capability
     *
     * @return bool
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function requestCapability(string $capability)
    {
        if ($this->isCapabilityAcknowledged($capability) || in_array($capability, $this->queuedCapabilities)) {
            return false;
        }

        Logger::fromContainer($this->getContainer())
            ->debug('Capability queued for request on next flush.', ['capability' => $capability]);

        $this->queuedCapabilities[] = $capability;
        return true;
    }

    /**
     * @param string $capability
     *
     * @return bool
     */
    public function isCapabilityAcknowledged(string $capability): bool
    {
        return in_array($capability, $this->acknowledgedCapabilities);
    }

    /**
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function flushRequestQueue()
    {
        if (empty($this->queuedCapabilities)) {
            return;
        }

        $capabilities = $this->queuedCapabilities;
        foreach ($capabilities as $key => $capability) {
            if (!$this->isCapabilityAvailable($capability)) {
                unset($capabilities[$key]);
            }
        }

        Logger::fromContainer($this->getContainer())
            ->debug('Sending capability request.', ['queuedCapabilities' => $capabilities]);

        Queue::fromContainer($this->getContainer())
            ->cap('REQ', $capabilities);
    }

    /**
     * @param string $capability
     *
     * @return bool
     */
    public function isCapabilityAvailable(string $capability): bool
    {
        return in_array($capability, $this->availableCapabilities);
    }

    /**
     * @param CAP $incomingIrcMessage
     * @param Queue $queue
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function responseRouter(CAP $incomingIrcMessage, Queue $queue)
    {
        $command = $incomingIrcMessage->getCommand();
        $capabilities = $incomingIrcMessage->getCapabilities();

        switch ($command) {
            case 'LS':
                $this->updateAvailableCapabilities($capabilities, $queue);
                break;

            case 'ACK':
                $this->updateAcknowledgedCapabilities($capabilities, $queue);
                break;

            case 'NAK':
                $this->updateNotAcknowledgedCapabilities($capabilities, $queue);
                break;
        }
    }

    /**
     * @param array $capabilities
     * @param Queue $queue
     * @throws \Yoshi2889\Container\NotFoundException
     */
    protected function updateAvailableCapabilities(array $capabilities, Queue $queue)
    {
        $this->availableCapabilities = $capabilities;

        Logger::fromContainer($this->getContainer())
            ->debug('Updated list of available capabilities.',
                [
                    'availableCapabilities' => $capabilities
                ]);

        foreach ($this->queuedCapabilities as $key => $capability) {
            if (in_array($capability, $capabilities)) {
                continue;
            }

            unset($this->queuedCapabilities[$key]);
            Logger::fromContainer($this->getContainer())
                ->debug('Removed requested capability from the queue because server does not support it.',
                    [
                        'capability' => $capability
                    ]);
        }

        EventEmitter::fromContainer($this->getContainer())
            ->emit('irc.cap.ls', [$capabilities, $queue]);
    }

    /**
     * @param string[] $capabilities
     * @param Queue $queue
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function updateAcknowledgedCapabilities(array $capabilities, Queue $queue)
    {
        $ackCapabilities = array_filter(array_unique(array_merge($this->getAcknowledgedCapabilities(), $capabilities)));
        $this->acknowledgedCapabilities = $ackCapabilities;

        foreach ($ackCapabilities as $capability) {
            EventEmitter::fromContainer($this->getContainer())
                ->emit('irc.cap.acknowledged.' . $capability, [$queue]);

            if (in_array($capability, $this->queuedCapabilities)) {
                unset($this->queuedCapabilities[array_search($capability, $this->queuedCapabilities)]);
            }
        }

        EventEmitter::fromContainer($this->getContainer())
            ->emit('irc.cap.acknowledged', [$ackCapabilities, $queue]);
    }

    /**
     * @return array
     */
    public function getAcknowledgedCapabilities(): array
    {
        return $this->acknowledgedCapabilities;
    }

    /**
     * @param string[] $capabilities
     * @param Queue $queue
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function updateNotAcknowledgedCapabilities(array $capabilities, Queue $queue)
    {
        $nakCapabilities = array_filter(array_unique(array_merge($this->getNotAcknowledgedCapabilities(),
            $capabilities)));
        $this->notAcknowledgedCapabilities = $nakCapabilities;

        foreach ($nakCapabilities as $capability) {
            EventEmitter::fromContainer($this->getContainer())
                ->emit('irc.cap.notAcknowledged.' . $capability, [$queue]);

            if (in_array($capability, $this->queuedCapabilities)) {
                unset($this->queuedCapabilities[array_search($capability, $this->queuedCapabilities)]);
            }
        }

        EventEmitter::fromContainer($this->getContainer())
            ->emit('irc.cap.notAcknowledged', [$nakCapabilities, $queue]);
    }

    /**
     * @return array
     */
    public function getNotAcknowledgedCapabilities(): array
    {
        return $this->notAcknowledgedCapabilities;
    }

    /**
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function setSaslHasCompleted()
    {
        $this->saslIsComplete = true;
        $this->tryEndNegotiation();
    }

    /**
     * @return bool
     * @throws \Yoshi2889\Container\NotFoundException
     */
    public function tryEndNegotiation(): bool
    {
        if (!$this->canEndNegotiation()) {
            return false;
        }

        Logger::fromContainer($this->getContainer())
            ->debug('Ending capability negotiation.');
        Queue::fromContainer($this->getContainer())
            ->cap('END');
        EventEmitter::fromContainer($this->getContainer())
            ->emit('irc.cap.end');

        return true;
    }

    /**
     * @return bool
     */
    public function canEndNegotiation(): bool
    {
        return empty($this->queuedCapabilities) && $this->saslIsComplete;
    }

    /**
     * @return array
     */
    public function getAvailableCapabilities(): array
    {
        return $this->availableCapabilities;
    }

    /**
     * @return string
     */
    public static function getSupportedVersionConstraint(): string
    {
        return WPHP_VERSION;
    }
}