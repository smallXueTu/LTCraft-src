<?php

/*
 *
 *  _____            _               _____           
 * / ____|          (_)             |  __ \          
 *| |  __  ___ _ __  _ ___ _   _ ___| |__) | __ ___  
 *| | |_ |/ _ \ '_ \| / __| | | / __|  ___/ '__/ _ \ 
 *| |__| |  __/ | | | \__ \ |_| \__ \ |   | | | (_) |
 * \_____|\___|_| |_|_|___/\__, |___/_|   |_|  \___/ 
 *                         __/ |                    
 *                        |___/                     
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author GenisysPro
 * @link https://github.com/GenisysPro/GenisysPro
 *
 *
*/

namespace pocketmine\command\defaults;

use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class LoadPluginCommand extends VanillaCommand {

	/**
	 * LoadPluginCommand constructor.
	 *
	 * @param $name
	 */
	public function __construct($name){
		parent::__construct(
			$name,
			"Load a plugin",
			"/loadplugin <file name or folder name>"
		);
		$this->setPermission("pocketmine.command.loadplugin");
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return false;
		}
//		if($sender->getName() !== 'Angel_XX' AND $sender instanceof \pocketmine\Player){
//			return $sender->sendMessage('§l§a[提示]§cOP不能用这个命令！');
//		}

		if(count($args) === 0){
			$sender->sendMessage(TextFormat::RED . "Usage: " . $this->usageMessage);
			return true;
		}

		if(!isset($args[0])) return false;

		$plugin = $sender->getServer()->getPluginManager()->loadPlugin($sender->getServer()->getPluginPath() . DIRECTORY_SEPARATOR . $args[0]);
		if($plugin != null){
		$sender->getServer()->getPluginManager()->enablePlugin($plugin);
		$sender->sendMessage(TextFormat::GREEN.'成功加载插件：'.$plugin->getName());
		foreach($sender->getServer()->getOnlinePlayers() as $player)$player->sendCommandData();
			return true;
		}
		return false;
	}
}