<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use LTPopup\Popup;

class MeCommand extends VanillaCommand {

	/**
	 * MeCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.me.description",
			"%pocketmine.command.me.usage"
		);
		$this->setPermission("pocketmine.command.me");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $currentAlias
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $currentAlias, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) === 0){
			$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));

			return false;
		}
		$sm=Popup::getSayManager($sender);
		if($sm===false){
			$sender->getServer()->broadcastMessage('* '.$sender->getName().':' . implode(" ", $args));
			return;
		}
		if(($r=$sm->checkCanChat())!==true){
			if($r===false)
				return $sender->sendMessage('§l§a[LT温馨提示]§c亲 你有刷屏行为哦,请注意发言速度,否者会被禁言噢！');
			else
				return $sender->sendMessage('§l§a[LT温馨提示]§c你已被禁言,'.$r.'秒后解除！');
		}
		$sender->getServer()->broadcastMessage('* '.$sender->getName().':' . implode(" ", $args));

		return true;
	}
}