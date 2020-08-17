<?php
namespace LTCraft;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\utils\Config;
use pocketmine\level\Location;
use pocketmine\level\Level;
use pocketmine\entity\Attribute;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use LTItem\Main as LTItem;

class Tutorial{
	private static $TutorialRecord = [];
	
	private $progress = 0;
    /**
     * @var Player
     */
	private $player;
	private $beforeGamemMode = 0;
    /**
     * @var \pocketmine\Server
     */
	public $server;
    /**
     * @var Main
     */
	public $plugin;
	public static function init(Config $config){
		self::$TutorialRecord = $config->getAll();
		// var_dump(self::$TutorialRecord);
	}

    /**
     * Tutorial constructor.
     * @param Player $player
     */
	public function __construct(Player $player){
		$this->plugin = Main::getInstance();
		$this->player = $player;
		$this->server = $player->getServer();
		$this->beforeGamemMode = $player->getLevel()->getName()=='create'?0:$this->player->getGamemode();
		$this->player->setGamemode(3);
		// $this->player->updateTeleport = false;
		if($this->player->getAPI()!==null){
			$this->player->getAPI()->removeThis();
		}
		$this->player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE, true);
		$location = $this->getLocation();
		$this->player->teleport($location, $location->getYaw(), $location->getPitch(), false);
	}

    /**
     * @return bool|void
     */
	public function progressUpdate(){
		if($this->player->onTeleport())return;
		$location = $this->getLocation();
		if(!($location instanceof Location)){
			return false;
		}
		if($location->getLevel()->getName()!==$this->player->getLevel()->getName()){
			$this->player->teleport($location, $location->getYaw(), $location->getPitch(), false);
			return;
		}
		// $this->player->sendMessage(($location->x .':'. $location->y .':'.$location->z .':'. $location->getYaw() .':'. $location->getPitch()), true);
		$this->player->sendPosition($location, $location->getYaw(), $location->getPitch());
		$this->player->newPosition = $location;
		$this->player->checkChunks();
		// $x = $location->x - $this->player->x;
		// $y = $location->y - $this->player->y;
		// $z = $location->z - $this->player->z;
		// $diff = abs($x) + abs($z) + abs($y);
		// $dx = 0.3 * ($x / $diff);
		// $dz = 0.3 * ($z / $diff);
		// $dy = 0.3 * ($y / $diff);
		// $pk = new SetEntityMotionPacket();
		// $pk->eid = $this->player->getId();
		// $pk->motionX = $dx;
		// $pk->motionY = $dy;
		// $pk->motionZ = $dz;
		// $this->player->dataPacket($pk);
		return true;
	}
	public function getLocation(){
		if(!$this->player->isOnline()){
			$this->plugin->endTutorial($this->player, 'exit');
			return;
		}
		while(true){
			if($this->progress > count(self::$TutorialRecord))break;
			if(!isset(self::$TutorialRecord[$this->progress++])){
				// $this->progress++;
				continue;
			}
			if(strpos(self::$TutorialRecord[$this->progress-1], '::')!==false){
				// var_dump(self::$TutorialRecord[$this->progress-1]);
				$info = explode('::', self::$TutorialRecord[$this->progress-1]);
				// var_dump($info);
				$this->player->addTitle($info[0], $info[1], 50,200,0, true);
				continue;
			}
			$info = explode(':', self::$TutorialRecord[$this->progress-1]);
			if(!($this->server->getLevelByName($info[0]) instanceof Level)){
				// $this->progress++;
				continue;
			}
			// $x = $info[1] - $this->player->x;
			// $y = $info[2] - $this->player->y;
			// $z = $info[3] - $this->player->z;
			// if($x ** 2 + $z ** 2 + $y ** 2 < 1) {
				// $this->progress++;
				// continue;
			// }
			$location = new Location($info[1], $info[2], $info[3], $info[4], $info[5], $this->server->getLevelByName($info[0]));
			break;
		}
		if(!isset($location) or !($location instanceof Location)){
			$this->plugin->endTutorial($this->player, 'end');
		}else{
			return $location;
		}
	}
	public function endTutorial($type = 'exit'){
		if($this->player->isOnline()){
			$this->player->getAPI()->restore();
			// $this->player->updateTeleport = true;
			$this->player->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE, false);
			$this->player->setGamemode($this->beforeGamemMode);
			if($type=='end'){
				$this->player->addAStatus('新手教程');
				$this->player->getInventory()->addItem(LTItem::getInstance()->createWeapon('近战','新手铁剑',strtolower($this->player->getName())));
				$this->player->sendMessage('§l§a太棒了！你阅览完了新手教程，已奖励你一把武器到你的背包！', true);
			}else{
				$this->player->sendMessage('§l§c哼,不好好看新手教程,不给你武器了！哼唧唧。', true);
			}
		}
	}
}