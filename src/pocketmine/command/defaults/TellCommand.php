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

class TellCommand extends VanillaCommand {

	/**
	 * TellCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"%pocketmine.command.tell.description",
			"%pocketmine.command.tell.usage",
			["w", "whisper", "msg", "m"]
		);
		$this->setPermission("pocketmine.command.tell");
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

		if(count($args) < 2){
			$sender->sendMessage(new TranslationContainer("commands.generic.usage", [$this->usageMessage]));

			return false;
		}
		$sm=Popup::getSayManager($sender);
		if($sm!==false and ($r=$sm->checkCanChat())!==true){
			if($r===false)
				return $sender->sendMessage('§l§a[LT温馨提示]§c亲 你有刷屏行为哦,请注意发言速度,否者会被禁言噢！');
			else
				return $sender->sendMessage('§l§a[LT温馨提示]§c你已被禁言,'.$r.'秒后解除！');
		}
		$name = strtolower(array_shift($args));

		$player = $sender->getServer()->getPlayer($name);

		if($player === $sender){
			$sender->sendMessage(new TranslationContainer(TextFormat::RED . "%commands.message.sameTarget"));
			return true;
		}

		if($player instanceof Player){
			$sender->sendMessage("[" . $sender->getName() . " -> " . $player->getName() . "] " . implode(" ", $args));
			$player->sendMessage("[" . $sender->getName() . " -> " . $player->getName() . "] " . implode(" ", $args));
			$sender->getServer()->getLogger()->privacy("[" . $sender->getName() . " -> " . $player->getName() . "] " . implode(" ", $args));
		}else{
			$sender->sendMessage(new TranslationContainer("commands.generic.player.notFound"));
		}

		return true;
	}
}
