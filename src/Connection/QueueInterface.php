<?php

/*
	WildPHP - a modular and easily extendable IRC bot written in PHP
	Copyright (C) 2016 WildPHP

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace WildPHP\Core\Connection;

use WildPHP\Core\Connection\Commands\BaseCommand;

interface QueueInterface
{
    /**
     * @param BaseCommand $command
     * @return void
     */
    public function insertMessage(BaseCommand $command);

    /**
     * @param BaseCommand $command
     * @return void
     */
    public function removeMessage(BaseCommand $command);

    /**
     * @param int $index
     * @return void
     */
    public function removeMessageByIndex(int $index);

    /**
     * @param QueueItem $item
     * @return void
     */
    public function scheduleItem(QueueItem $item);

    /**
     * @return QueueItem[]
     */
    public function flush(): array;

    //public function pass(string $password);

    public function nick(string $nickname);

    public function user(string $username, string $hostname, string $servername, string $realname);

    /**
     * @param string $channel
     * @param string $message
     * @return void
     */
    public function privmsg(string $channel, string $message);

    //public function pong(string $server = '');
}