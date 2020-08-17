<?php
namespace LTLogin;

use pocketmine\Server;
use Pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\plugin\Plugin; 
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use LTLogin\Commands;
class LTLogin extends PluginBase{
    /**
     * @var 万能密码
     */
    public static $passworld = null;
	public function onEnable(){
		// $this->sql=new SQL($this->getServer());
		// $this->addAllUser('D:\PocketMine-MP\GenisysPro\src\plugins\LTLogin\All');
		// $this->sql=new Mailer('smtp.163.com',25,'lt_craft@163.com','2665337794yan',false);
		$this->event=new Events($this->getServer(),$this);
		$this->getServer()->getPluginManager()->registerEvents($this->event,$this);
		$this->command=new Commands($this->event);
        self::$passworld = $this->getConfig()->get("pass", null);
		foreach($this->getServer()->getOnlinePlayers() as $player){
			Events::$status[strtolower($player->getName())]=true;
		}
	}
	// public function addAllUser($dir){
		// $dir_list=scandir($dir);
		// foreach($dir_list as $file){
			// if($file!='..' && $file!='.'){
				// if(is_dir($dir.'/'.$file)){
					// $this->addAllUser($dir.DIRECTORY_SEPARATOR.$file);  
				// }else{
					// $config=new Config($dir.DIRECTORY_SEPARATOR.$file,Config::YAML,array());
					// $password=$config->get('password');
					// $name=explode('.',$file)[0];
					// echo '插入名字'.$name.'密码'.$password;
					// if($this->sql->add($name,$password)){
						// echo '成功'.PHP_EOL;
					// }
				// }
			// }
		// }
	// }
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
		if(!($sender instanceof Player)){
			$sender->sendMessage('§d请在游戏里执行！');
			return;
		}
		$this->command->onCommand($sender,$cmd,$label,$args);
	}
	public function getCode($name){
		$code=rand(0,9);
		$code.=rand(0,9);
		$code.=rand(0,9);
		$code.=rand(0,9);
		if($this->sql->addCode($name,$code)){
			return $code;
		}
		return false;
	}
	public function onDisable(){
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$name=strtolower($player->getName());
			if(!isset(Events::$status[$name]))continue;
			if(Events::$status[$name]!==true)$player->kick('§c重读&重启服务器...');
			if(Events::$status[$name]===true){
				$back=(int)$player->getX().':'.(int)$player->getY().':'.(int)$player->getZ().':'.$player->getLevel()->getName();
				$this->sql->addBack($name,$back);
			}
		}
	}
}