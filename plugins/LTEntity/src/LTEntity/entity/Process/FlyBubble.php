<?php


namespace LTEntity\entity\Process;


use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class FlyBubble extends Entity
{
    const NETWORK_ID = 69;
    /** @var Position */
    public $target = null;
    /**
     * @return Position
     */
    public function getTarget(): Position
    {
        return $this->target;
    }
    /**
     * @param Position $target
     */
    public function setTarget(Position $target): void
    {
        $this->target = $target;
    }
    public function onUpdate($currentTick)
    {
        $this->age++;
        $x=$this->target->x - $this->x;
        $y=$this->target->y - $this->y;
        $z=$this->target->z - $this->z;
        $diff = abs($x) + abs($z);
        if($diff==0){
            $this->close();
            return false;
        }else{
            $Mx = 0.5 * ($x / $diff);
            $My = 0.5 * ($y / $diff);
            $Mz = 0.5 * ($z / $diff);
        }
        $this->move($Mx, $My, $Mz);
        if ($this->target->distance($this)<=1 or $this->age > 200){
            $this->close();
            return false;
        }
    }
    public function saveNBT()
    {

    }

    public function move($dx, $dy, $dz): bool
    {
        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
        $this->updateMovement();
        return true;
    }

    public function updateMovement()
    {
        $this->level->addEntityMovement($this->chunk->getX(), $this->chunk->getZ(), $this->id, $this->x, $this->y, $this->z, 0, 0, 0);
    }

    /**
     * @param Player $player
     */
    public function spawnTo(Player $player){
        $pk = new AddEntityPacket();
        $pk->type = self::NETWORK_ID;
        $pk->eid = $this->getId();
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->speedX = $this->motionX;
        $pk->speedY = $this->motionY;
        $pk->speedZ = $this->motionZ;
        $pk->metadata = $this->dataProperties;
        $player->dataPacket($pk);

        parent::spawnTo($player);
    }
}