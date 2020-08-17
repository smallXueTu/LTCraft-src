<?php

namespace onebone\economyapi;

use onebone\economyapi\event\debt\DebtChangedEvent;
use onebone\economyapi\event\money\MoneyChangedEvent;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\scheduler\CallbackTask;
use LTEntity\Main as LTEntity;

class EconomyAPI extends PluginBase implements Listener{
	private static $obj = null;
	private $path;
	private $money;
	public $config;
	private $command;
	private $FloatingText;

    /**
     * @return EconomyAPI
     */
	public static function getInstance(){
		return self::$obj;
	}
	
	public function onLoad(){
		self::$obj = $this;
		$this->path = $this->getDataFolder();
		$this->money = array();
	}
	
	public function onEnable(){
		@mkdir($this->path);
		$this->createConfig();
		$cmds = array(
			"seemoney" => "onebone\\economyapi\\commands\\SeeMoneyCommand",
			"mymoney" => "onebone\\economyapi\\commands\\MyMoneyCommand",
			"pay" => "onebone\\economyapi\\commands\\PayCommand",
			"givemoney" => "onebone\\economyapi\\commands\\GiveMoneyCommand",
			"topmoney" => "onebone\\economyapi\\commands\\TopMoneyCommand",
			"takemoney" => "onebone\\economyapi\\commands\\TakeMoneyCommand",
		);
		$commandMap = $this->getServer()->getCommandMap();
		foreach($cmds as $key => $cmd){
			foreach($this->command->get($key) as $c){
				$commandMap->register("economyapi", new $cmd($this, $c));
			}
		}
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$moneyConfig = new Config($this->path."Money.yml", Config::YAML, array(
		));
		
		$this->money = $moneyConfig->getAll();
		// $this->money = $moneyConfig->get('money');
		$this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new CallbackTask([$this,'save']), 12000, 12000);
		$this->getServer()->getScheduler()->scheduleDelayedRepeatingTask(new CallbackTask([$this,'updateTop']), 1200, 1200);
	}
	
	
	private function createConfig(){
		$this->config = new Config($this->path."economy.properties", Config::PROPERTIES, []);
		$this->command = new Config($this->path."command.yml", Config::YAML, []);
	}
	public function updateTop(){
        LTEntity::getInstance()->updateTop();
		if($this->FloatingText instanceof \LTCraft\FloatingText){
			$moneyData = $this->getAllMoney();
			$banList = $this->getServer()->getNameBans();
			arsort($moneyData);
			$n=1;
			$text='§l§e富豪排行榜'."\n";
			foreach($moneyData as $p => $money){
				if($banList->isBanned($p) or $this->getServer()->isOp(strtolower($p))) continue;
				$text.='§a'.$n .'#玩家名字'.$p .'§d财产:'.$money ."\$\n";
				if(++$n==11)break;
			}
			$this->FloatingText->updateAll($text);
		}
	}
	public function setTopFloatingText(\LTCraft\FloatingText $FloatingText){
		$this->FloatingText=$FloatingText;
		$this->updateTop();
	}
	
	public function getAllMoney(){
		return $this->money;
	}
	
	public function myMoney($player){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);
		
		if(!isset($this->money[$player])){
			return false;
		}
		return $this->money[$player];
	}

	public function addMoney($player, $amount, $info = null){
		if($amount <= 0 or !is_numeric($amount)){
			return 1;
		}
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);
		$amount = round($amount, 2);
		if(isset($this->money[$player])){
			$this->money[$player]+=$amount;
			$this->getServer()->getLogger()->addData($player, [$amount, $info], 'addMoney');
			return true;
		}else{
			return 2;
		}
	}

	public function reduceMoney($player, $amount, $info = null){
		if($amount <= 0 or !is_numeric($amount)){
			return 1;
		}
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);
		$amount = round($amount, 2);
		if($this->money[$player]<$amount)return 3;
		if(isset($this->money[$player])){
			$this->money[$player]-=$amount;
			$this->getServer()->getLogger()->addData($player, [$amount, $info], 'reduceMoney');
			return true;
		}else{
			return 2;
		}
	}

	public function setMoney($player, $money, $info = null){
		if($money <= 0 or !is_numeric($money)){
			return 1;
		}
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);
		$money = round($money, 2);
		if(isset($this->money[$player])){
			$this->money[$player]=$money;
			return true;
		}else{
			return 2;
		}
	}
	
	public function onDisable(){
		$this->save();
	}
	
	public function save(){
		$moneyConfig = new Config($this->path."Money.yml", Config::YAML);
		$moneyConfig->setAll($this->money);
		$moneyConfig->save();
	}

    /**
     * @param Player $player
     * @param int $amount
     */
	public function openAccount(Player $player, int $amount = 5000){
		$username = strtolower($player->getName());
		if(!isset($this->money[$username]))$this->money[$username] = 5000;
	}

    /**
     * @param Player $player
     * @return bool
     */
	public function checkAccount(Player $player):bool {
        $username = strtolower($player->getName());
		return isset($this->money[$username]);
	}
}