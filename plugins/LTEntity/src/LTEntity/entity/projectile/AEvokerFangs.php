<?php
namespace LTEntity\entity\projectile;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;

class AEvokerFangs extends Entity{
    const NETWORK_ID = 103;
	public $shootingEntity;
	const DATA_SHOOTER_ID = 17;
    public $damage = 0;
    public $Warmup = 20;
	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null){
		$this->shootingEntity = $shootingEntity;
		if($shootingEntity !== null){
			$this->setDataProperty(self::DATA_SHOOTER_ID, self::DATA_TYPE_LONG, $shootingEntity->getId());
		}
		$this->setDataProperty(78, self::DATA_TYPE_INT, -8);
		parent::__construct($level, $nbt);
	}
	public function onUpdate($currentTick){
		if(--$this->Warmup==-40){
			$this->close();
			return false;
		}
		// $this->setDataProperty(78, self::DATA_TYPE_INT, $this->Warmup);
		return true;
	}
	// public function attack($damage, EntityDamageEvent $source){
	// }
	public function spawnTo(Player $player)
    {
        $pk = new AddEntityPacket();
        $pk->eid = $this->getId();
        $pk->type = self::NETWORK_ID;
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = 0;
        $pk->speedY = 0;
        $pk->speedZ = 0;
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);

        parent::spawnTo($player);
    }
}