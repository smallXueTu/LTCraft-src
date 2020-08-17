<?php
namespace LTPet\Preview;

use pocketmine\utils\UUID;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\Player;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\PlayerListPacket;
use pocketmine\network\protocol\RemoveEntityPacket;

use LTPet\Main;
use LTPet\Pet;

class NPCSkinPreview extends Vector3{
	private $player;
	private $need;
	private $info;
	public $UUID;
	private $eid;
	public $WaitingConfirm = false;
	private $name;
	private $skins = [];
	private $SkinName = '';
	public function __construct($player){
		$this->player=$player;
		foreach(Main::getInstance()->skins as $SkinName=>$skin){
			$this->skins[]=[$SkinName, $skin];
		}
		$this->UUID = UUID::fromRandom();
		$this->eid = Entity::$entityCount++;
		$this->SkinName = 0;
		$this->updateInfo();
		parent::__construct(742.5, 12.5, 68.5);
		$this->spawnTo();
	}
	public function updateInfo(){
		$this->name=$this->skins[$this->SkinName][0];
		$this->need=Pet::getSkinCount($this->getName());
		$all=Main::getInstance()->PlayerSkins->get(strtolower($this->player->getName()), []);
		if(!is_array($this->need)){
			$info=('§l§d皮肤名字:'.$this->name .(isset($all[$this->name])?'§a已拥有':'§c未拥有'));
			$this->info=$info.PHP_EOL .$this->need;
		}else{
			$info=('§l§d皮肤名字:'.$this->name .(isset($all[$this->name])?'§a已拥有':'§c未拥有 点击购买'));
			$this->info=$info.PHP_EOL .$this->getName().'§e需要：§l§c材料'.$this->getNeed()[0].' '.$this->getNeed()[1].'个';
		}
	}
	public function getEid(){
		return $this->eid;
	}
	public function getNeed(){
		return $this->need;
	}
	public function getName(){
		return $this->name;
	}
	public function updateSkin($next = true){
		if($next){
			if(++$this->SkinName==count($this->skins))$this->SkinName=0;
		}else{
			if(--$this->SkinName<0)$this->SkinName=count($this->skins)-1;
		}
		$this->despawnFrom();
		$this->UUID = UUID::fromRandom();
		$this->updateInfo();
		$this->spawnTo();
	}
	public function despawnFrom(){
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_REMOVE;
		$pk->entries[] = [$this->UUID];
		$this->player->dataPacket($pk);
		$pk=new RemoveEntityPacket();
		$pk->eid=$this->eid;
		$this->player->dataPacket($pk);
	}
	public function remove(){//remove this..
		$this->despawnFrom();
	}
	public function __destruct(){//析构函数
		$this->remove();
	}
	public function spawnTo(){
		$pk = new AddPlayerPacket();
		$pk->uuid = $this->UUID;
		$pk->username = 'Skin';
		$pk->eid = $this->eid;
		$pk->item = Item::get(322);
		$pk->y = $this->getY();
		$pk->x = $this->getX();
		$pk->z = $this->getZ();
		$pk->yaw = 270;
		$pk->pitch = 0;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$flags = 0;
		$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
		$metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_AIR => [Entity::DATA_TYPE_SHORT, 400],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->info],
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
		];  
		$pk->metadata = $metadata;
		$this->player->dataPacket($pk);
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		$pk->entries[] = [$this->UUID, $this->eid, '§d女仆皮肤预览', 'Standard_CustomSlim', $this->skins[$this->SkinName][1]];
		$this->player->dataPacket($pk);
	}
}