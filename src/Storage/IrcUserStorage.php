<?php
declare(strict_types=1);
/**
 * Copyright 2019 The WildPHP Team
 *
 * You should have received a copy of the MIT license with the project.
 * See the LICENSE file for more information.
 */

namespace WildPHP\Core\Storage;


use WildPHP\Core\Entities\IrcUser;
use WildPHP\Core\Storage\Providers\StorageProviderInterface;

class IrcUserStorage implements IrcUserStorageInterface
{
    /**
     * @var StorageProviderInterface
     */
    private $storageProvider;

    /**
     * @var string
     */
    private $database;

    public function __construct(StorageProviderInterface $storageProvider, string $database = 'users')
    {
        $this->storageProvider = $storageProvider;
        $this->database = $database;
    }

    /**
     * @param IrcUser $user
     */
    public function store(IrcUser $user): void
    {
        if (empty($user->getId())) {
            $this->giveId($user);
        }

        $this->storageProvider->store($this->database, IrcUserStorageAdapter::convertToStoredEntity($user));
    }

    /**
     * @param IrcUser $user
     * @throws StorageException
     */
    public function delete(IrcUser $user): void
    {
        if (empty($user->getId()) || !$this->has($user->getId())) {
            throw new StorageException('Cannot delete user without ID or channel which is not stored');
        }

        $this->storageProvider->delete($this->database, ['id' => $user->getId()]);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function has(int $id): bool
    {
        return $this->storageProvider->has($this->database, ['id' => $id]);
    }

    /**
     * @param IrcUser $user
     * @return bool
     */
    public function contains(IrcUser $user): bool
    {
        return $this->storageProvider->has($this->database, $user->toArray());
    }

    /**
     * @param int $id
     * @return null|IrcUser
     */
    public function getOne(int $id): ?IrcUser
    {
        $entity = $this->storageProvider->retrieve($this->database, ['id' => $id]);

        if ($entity === null) {
            return null;
        }

        return IrcUserStorageAdapter::convertToIrcUser($entity);
    }

    /**
     * @param string $nickname
     * @return null|IrcUser
     */
    public function getOneByNickname(string $nickname): ?IrcUser
    {
        $entity = $this->storageProvider->retrieve($this->database, ['nickname' => $nickname]);

        if ($entity === null) {
            return null;
        }

        return IrcUserStorageAdapter::convertToIrcUser($entity);
    }

    /**
     * @param string $nickname
     * @return IrcUser
     */
    public function getOrCreateOneByNickname(string $nickname): IrcUser
    {
        $entity = $this->storageProvider->retrieve($this->database, ['nickname' => $nickname]);

        if ($entity === null) {
            $ircUser = new IrcUser($nickname);
            $this->store($ircUser);
            return $ircUser;
        }

        return IrcUserStorageAdapter::convertToIrcUser($entity);
    }

    /**
     * @param string $hostname
     * @return null|IrcUser
     */
    public function getOneByHostname(string $hostname): ?IrcUser
    {
        $entity = $this->storageProvider->retrieve($this->database, ['hostname' => $hostname]);

        if ($entity === null) {
            return null;
        }

        return IrcUserStorageAdapter::convertToIrcUser($entity);
    }

    /**
     * @param string $username
     * @return null|IrcUser
     */
    public function getOneByUsername(string $username): ?IrcUser
    {
        $entity = $this->storageProvider->retrieve($this->database, ['username' => $username]);

        if ($entity === null) {
            return null;
        }

        return IrcUserStorageAdapter::convertToIrcUser($entity);
    }

    /**
     * @param string $ircAccount
     * @return null|IrcUser
     */
    public function getOneByIrcAccount(string $ircAccount): ?IrcUser
    {
        $entity = $this->storageProvider->retrieve($this->database, ['irc_account' => $ircAccount]);

        if ($entity === null) {
            return null;
        }

        return IrcUserStorageAdapter::convertToIrcUser($entity);
    }

    /**
     * @return IrcUser[]
     */
    public function getAll(): array
    {
        $entities = $this->storageProvider->retrieveAll($this->database);

        if ($entities === null) {
            return [];
        }

        $users = [];
        foreach ($entities as $entity) {
            $users[$entity->getId()] = IrcUserStorageAdapter::convertToIrcUser($entity);
        }

        return $users;
    }

    /**
     * @param IrcUser $user
     */
    protected function giveId(IrcUser $user): void
    {
        if (!empty($user->getId())) {
            return;
        }

        $user->setId((int) @max(array_keys($this->getAll())) + 1);
    }
}