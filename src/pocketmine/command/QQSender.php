<?php
namespace pocketmine\command;

use pocketmine\utils\TextFormat;
use pocketmine\utils\MainLogger;
use pocketmine\event\TextContainer;
class QQSender extends ConsoleCommandSender{
	public $messages=[];
	public function sendMessage($message ,$show = true){
		if($message instanceof TextContainer){
			$message = $this->getServer()->getLanguage()->translate($message);
		}else{
			$message = $this->getServer()->getLanguage()->translateString($message);
		}
		foreach(explode("\n", trim($message)) as $line){
			$this->messages[]=TextFormat::clean($line);
			if($show)MainLogger::getLogger()->info($line);
		}
	}
	public function getMessages(){
		return $this->messages;
	}
}