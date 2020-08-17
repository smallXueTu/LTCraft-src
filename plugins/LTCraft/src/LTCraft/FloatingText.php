<?php

namespace LTCraft;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\entity\Item as ItemEntity;

class FloatingText extends Position{
    protected $Ytext;
    protected $text;
    protected $id;
    protected $hasSpawned = [];
    public function __construct(Position $pos, $text = "", $replace=false)
    {
        $this->x = $pos->x;
        $this->y = $pos->y+1;
        $this->z = $pos->z;
        $this->level = $pos->level;
		if($replace){
			$this->Ytext = $text;
			$this->text = $text;
		}else{
			$this->text = $text;
		}
        $this->id = Entity::$entityCount++;
		$this->level->addFloatingText($this);
    }
	
	public function getID(){
		return $this->id;
	}
	
    public function spawnTo(Player $player)
    {
		if(isset($this->hasSpawned[$player->getLoaderId()]) and isset($player->FloatingTexts[$this->getID()]))return;
		$pk = new AddEntityPacket();
		$pk->eid = $this->id;
		$pk->type = 37;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$flags = 0;
		$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->text],
			38 => [7, 0],
			39 => [3, 0]
		];
		$player->dataPacket($pk);
		$this->hasSpawned[$player->getLoaderId()]=$player;
		$player->FloatingTexts[$this->getID()] = $this;
    }

    public function updateAll($info)
    {
		if(is_array($info)){
			$this->text = strtr($this->Ytext, ['@c'=>$info[1], '@t'=>$info[0]]);
		}else{
			$this->text=$info;
		}
		foreach($this->hasSpawned as $player)$this->update($player);
    }
    public function getText()
    {
        return $this->Ytext;
    }
	public function update(Player $player){
		$pk = new SetEntityDataPacket();
		$pk->eid =  $this->id;
		$pk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->text]];
		$player->dataPacket($pk);
	}
	public function spawnToAll(){
		foreach($this->level->getChunkPlayers( $this->x >>4 ,  $this->z >>4) as $player){
			$this->spawnTo($player);
		}
	}
    public function despawnFrom(Player $player)
    {
        $pk = new RemoveEntityPacket();
        $pk->eid = $this->id;
		$player->dataPacket($pk);
		unset($this->hasSpawned[$player->getLoaderId()]);
		unset($player->FloatingTexts[$this->getID()]);
    }
	public function despawnFromAll(){
		foreach($this->hasSpawned as $player)
		$this->despawnFrom($player);
	}
}