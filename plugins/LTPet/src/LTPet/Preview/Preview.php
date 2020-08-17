<?php
namespace LTPet\Preview;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\utils\UUID;
use pocketmine\math\Vector3;
class Preview{
	private $current;
	private $target;
	public function __construct($player){//构造方法
		$this->target=$player;
		$this->current=PreviewPet::get(0);
		$this->current->spawnTo($player);
	}
	public function getEid(){
		return $this->current->getEid();
	}
	public function getCurrent(){
		return $this->current;
	}
	public function getNeed(){
		return $this->current->getNeed();
	}
	public function updateSeeTarget(){//更新实体看的方向
		$this->current->updateSee($this->target);
	}
	public function updateType($action){//更新实体类型
		$this->current->despawnFrom($this->target);
		if($action){//下一个
			$id=$this->current->getID()+1;
			if($id>PreviewPet::getMax())$id=0;
		}else{
			$id=$this->current->getID()-1;
			if($id<0)$id=PreviewPet::getMax();
		}
		$entity=PreviewPet::get($id);
		if($entity==null){
			$id=$action?0:PreviewPet::getMax();
			$entity=PreviewPet::get(0);
		}
		$this->current=$entity;
		$this->current->spawnTo($this->target);
	}
	public function remove(){//remove this..
		$this->current->despawnFrom($this->target);
	}
	public function __destruct(){//析构函数
		$this->remove();
	}
}