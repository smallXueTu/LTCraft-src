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

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\TranslationContainer;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use LTVIP\Main as LTVIP;

class TeleportCommand extends VanillaCommand {

	/**
	 * TeleportCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			'%pocketmine.command.tp.description',
			'%pocketmine.command.tp.usage'
		);
		$this->setPermission('pocketmine.command.teleport');
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $currentAlias
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $currentAlias, array $args){
		if(!$sender->isOP() and !in_array($sender->getName() ,['dpp', 'end', '_X_', 'dai_meng', 'xiaoxiaoya','nan_yu'])){
			return false;
		}
		if(count($args) < 1 or count($args) > 6){
			$sender->sendMessage(new TranslationContainer('commands.generic.usage', [$this->usageMessage]));

			return true;
		}
		
		if(($sender instanceof Player and $sender->isVIP()!=false) and !$sender->isOP()){
			$target = $sender->getServer()->getPlayer($args[0]);
			if($target){
				if($sender->teleport($target)){
					$sender->sendMessage(LTVIP::HEAD.'a传送成功！');
				}else{
					$sender->sendMessage(LTVIP::HEAD.'c传送失败！');
				}
			}else{
				$sender->sendMessage(LTVIP::HEAD.'c目标玩家不在线！');
			}
			return true;
		}

		$target = null;
		$origin = $sender;

		if(count($args) === 1 or count($args) === 3  or count($args) === 5){
			if($sender instanceof Player){
				$target = $sender;
			}else{
				$sender->sendMessage(TextFormat::RED . '你不是一个玩家！');

				return true;
			}
			if(count($args) === 1){
				$target = $sender->getServer()->getPlayer($args[0]);
				if($target === null){
					$sender->sendMessage(TextFormat::RED . '找不到玩家' . $args[0]);

					return true;
				}
			}
		}else{
			$target = $sender->getServer()->getPlayer($args[0]);
			if($target === null){
				$sender->sendMessage(TextFormat::RED . '找不到玩家' . $args[0]);

				return true;
			}
			if(count($args) === 2){
				// if(strtolower($sender->getName())==='xiao_hua' or strtolower($sender->getName())==='baobao')return $sender->sendMessage(TextFormat::RED . '滥用此权限 已取消你的这个权限');
				$origin = $target;
				$target = $sender->getServer()->getPlayer($args[1]);
				if($target === null){
					Command::broadcastCommandMessage($sender, new TranslationContainer('commands.tp.failure', []));

					return true;
				}
			}
		}

		if(count($args) < 3){
			if($origin->teleport($target))
				Command::broadcastCommandMessage($sender, new TranslationContainer('commands.tp.success', [$origin->getName(), $target->getName()]));
			else
				$sender->sendMessage(TextFormat::RED . '传送' . $target->getName() .'失败！');
			return true;
		}elseif($target->getLevel() !== null){
			if(count($args) === 4 or count($args) === 6){
				$pos = 1;
			}else{
				$pos = 0;
			}

			$x = $this->getRelativeDouble($target->x, $sender, $args[$pos++]);
			$y = $this->getRelativeDouble($target->y, $sender, $args[$pos++], 0, 256);
			$z = $this->getRelativeDouble($target->z, $sender, $args[$pos++]);
			$yaw = $target->getYaw();
			$pitch = $target->getPitch();

			if(count($args) === 6 or (count($args) === 5 and $pos === 3)){
				$yaw = $args[$pos++];
				$pitch = $args[$pos++];
			}

			if($target->teleport(new Vector3($x, $y, $z), $yaw, $pitch))
				Command::broadcastCommandMessage($sender, new TranslationContainer('commands.tp.success.coordinates', [$target->getName(), round($x, 2), round($y, 2), round($z, 2)]));
			else
				$sender->sendMessage(TextFormat::RED . '传送' . $target->getName() .'失败！');
			return true;
		}

		$sender->sendMessage(new TranslationContainer('commands.generic.usage', [$this->usageMessage]));

		return true;
	}
}
