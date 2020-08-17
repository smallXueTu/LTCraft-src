<?php
namespace LTEntity\entity\projectile;

use LTEntity\entity\ProjectileEntity;
use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;
use LTEntity\entity\projectile\ABlazeFireball;

class ADragonFireBall extends ABlazeFireball
{
    const NETWORK_ID = 79;
    public function onUpdate($currentTick)
    {
        $hasUpdate = parent::onUpdate($currentTick);
        if($this->age > 60 and $this->isAlive()) {
            $this->kill();
            $hasUpdate = true;
        }
        return $hasUpdate;
    }
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
        ProjectileEntity::spawnTo($player);
    }
}