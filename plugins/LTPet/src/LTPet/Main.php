<?php
namespace LTPet;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use LTPet\Commands\Commands;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\scheduler\CallbackTask;
use onebone\economyapi\EconomyAPI;
use pocketmine\item\Item;
use LTPet\Preview\PreviewPet;

use LTPet\Utils\Converter;

class Main extends PluginBase{
	public $server;
	public $comes;
	public $eAPI;
	public $skins;
	public $PlayerSkins;
	public $event;
	public static $instance=null;
	public static function getInstance(){
		return self::$instance;
	}
	public function onEnable(){
		$this->eAPI=EconomyAPI::getInstance();
		self::$instance=$this;
		$this->server=$this->getServer();
		$this->event=new Events($this->server ,$this ,$this->getLogger());
		$this->PlayerSkins=new Config($this->getDataFolder()."PlayerSkins.yml",Config::YAML,array());
		$this->server->getPluginManager()->registerEvents($this->event ,$this);
		$this->Commands=new Commands($this->server ,$this);
		@mkdir($this->getDataFolder().'/skins');
		$this->loadSkins();
		Pet::init();
		PreviewPet::init();
	}
	public function onDisable(){
		$this->PlayerSkins->save(false);
	}
	public function loadSkins(){
		$path=$this->getDataFolder().'/skins';
		foreach(scandir($path) as $afile){
			$fname=explode('.',$afile);
			if($afile=='.' or $afile=='..' or is_dir($path.'/'.$afile) or end($fname)!=='png')continue;
			$name = explode('.', $afile);
			unset($name[count($name)-1]);
			$name = implode('.', $name);
			$this->skins[$name]=Converter::getPngSkin($path.'/'.$afile, true);
		}
	}
	public static function getCleanName($name){
		return preg_replace('#§.#', '', strtolower($name));
	}
	public function addPet($player,$type,$name){
		$conf=['type'=>$type, 'hunger'=>10000, 'love'=>0, 'name'=>$name, 'skin'=>''];
		if($player instanceof Player){
			$player->setPet(self::getCleanName($name),$conf);
            $player->newProgress('永恒的伙伴');
			$player->sendMessage('§l§a[LT宠物系统]§a有人给了你一个宠物，输入§d/宠物 列表§a查看吧');
			Pet::Come($player,$conf);
		}
	}
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
		if(isset($args[0])){
			if($args[0]!=='管理' and !($sender instanceof Player))return $sender->sendMessage('§d请在游戏里执行！');
			$this->Commands->onCommand($sender,$cmd,$label,$args);
		}else $sender->sendMessage('§l§a[LT宠物系统]§e输入§d/宠物 帮助§e查看帮助§r');
	}
}