<?php

namespace LTGrade;

use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\entity\Item as ItemEntity;
use pocketmine\network\protocol\SetEntityMotionPacket;
class FloatingText
{
	public static $floatingTexts = [];
	public $age = 0;
    protected $pos;
    protected $hieght;
    protected $text;
    protected $entityId;
    protected $level;
    protected $players=[];
	public static function update(){
		foreach(self::$floatingTexts as $eid=>$floatingText){
			if(++$floatingText->age>30){
				$floatingText->respawn();
				unset(self::$floatingTexts[$eid]);
			}
		}
	}
    public function __construct(Position $pos, $text = "", $hieght = 0)
    {
        $this->pos = $pos;
        $this->hieght = $hieght;
        $this->level = $pos->level;
        $this->text = $text;
        $this->entityId = Entity::$entityCount++;
		self::$floatingTexts[$this->entityId]=$this;
		$this->spawn();
    }
    public function spawn()
    {
        $pk = new \pocketmine\network\protocol\AddEntityPacket();
        $pk->eid = $this->entityId;
        $pk->type = ItemEntity::NETWORK_ID;;
        $pk->x = $this->pos->x;
        $pk->y = $this->pos->y + $this->hieght;
        $pk->z = $this->pos->z;
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
			39 => [Entity::DATA_TYPE_FLOAT, 0]
		];
        foreach($this->level->getChunkPlayers($this->pos->x >> 4, $this->pos->z >> 4) as $player){
			if($player->canSelected()){
				$player->dataPacket($pk);
				$this->players[]=$player;
			}
		}
    }
    public function respawn()
    {
        $pk = new RemoveEntityPacket();
        $pk->eid = $this->entityId;
        Server::getInstance()->broadcastPacket($this->players, $pk);
    }
}