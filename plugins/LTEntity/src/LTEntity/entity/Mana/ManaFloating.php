<?php
namespace LTEntity\entity\Mana;

use pocketmine\entity\Arrow;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\item\Potion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\GenericParticle;
use pocketmine\level\Position;
use pocketmine\math\Vector3;

class ManaFloating extends Entity
{
    /** @var Position  */
    public $target = null;
    /** @var Position  */
    public $starting  = null;
    /** @var int  */
    public $pid  = 22;

    /**
     * @param Position $target
     */
    public function setTarget(Position $target): void
    {
        $this->target = $target;
    }

    /**
     * @param Position $starting
     */
    public function setStarting(Position $starting): void
    {
        $this->starting = $starting;
    }
    public function onUpdate($currentTick)
    {
        if($this->closed){
            return false;
        }
        if ($this->target == null){
            $this->close();
            return false;
        }
        $this->age++;
        $x=$this->target->x - $this->x;
        $y=$this->target->y - $this->y;
        $z=$this->target->z - $this->z;
        $diff = abs($x) + abs($z);
        if($diff==0){
            $this->close();
            return false;
        }else{
            $Mx = 1 * 0.15 * ($x / $diff);
            $My = 1 * 0.15 * ($y / $diff);
            $Mz = 1 * 0.15 * ($z / $diff);
        }
        $this->move($Mx, $My, $Mz);
        $this->getLevel()->addParticle(new GenericParticle($this->asVector3(), $this->pid));
        if ($this->target->distance($this)<=1 or $this->age > 200){
            $this->close();
            return false;
        }
        return true;
    }
    public function move($dx, $dy, $dz)
    {
        $this->setComponents($this->x + $dx, $this->y + $dy, $this->z + $dz);
    }

    /**
     * @param \pocketmine\Player $player
     * @param bool $send
     */
    public function despawnFrom(\pocketmine\Player $player, bool $send = true)
    {

    }

    /**
     * @param \pocketmine\Player $player
     */
    public function spawnTo(\pocketmine\Player $player)
    {

    }

    public function saveNBT()
    {

    }
}