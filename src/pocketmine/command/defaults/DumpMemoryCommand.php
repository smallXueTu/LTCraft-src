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


class DumpMemoryCommand extends VanillaCommand {

	/**
	 * DumpMemoryCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"Dumps the memory",
			"/$name [path]"
		);
		$this->setPermission("pocketmine.command.dumpmemory");
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
//		if($sender->getName() !== 'Angel_XX' AND $sender instanceof \pocketmine\Player){
//			return $sender->sendMessage('§l§a[提示]§cOP不能使用这个命令哦！');
//		}
		Command::broadcastCommandMessage($sender, "Dumping server memory");
		if($args[0]=='level'){
			foreach($sender->getServer()->getLevels() as $level){
				$max=0;
				$mess='';
				foreach($level as $k=>$v){
					if(is_array($v) and count($v)>$max){
						$max=count($v);
						$mess=$k;
					}
				}
				echo $level->getName().'最大数组为'.$mess.'数量为:'.$max. PHP_EOL;
				return true;
			}
		}elseif($args[0]=='max'){
			echo '正在寻找...'.PHP_EOL;	var_dump($sender->getServer()->getMemoryManager()->getMaxMemoryObject($sender->getServer()));
			return true;
		}
		$sender->getServer()->getMemoryManager()->dumpServerMemory(isset($args[0]) ? $args[0] : $sender->getServer()->getDataPath() . "/memory_dumps/memoryDump_" . date("D_M_j-H.i.s-T_Y", time()), 48, 80);
		return true;
	}

}
