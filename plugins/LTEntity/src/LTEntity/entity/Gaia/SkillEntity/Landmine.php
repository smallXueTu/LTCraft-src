<?php


namespace LTEntity\entity\Gaia\SkillEntity;

use LTEntity\entity\Gaia\GaiaGuardiansIII;
use mysql_xdevapi\CollectionModify;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;

class Landmine extends Position
{
    /** @var int */
    public int $size;
    /** @var int */
    public int $age;
    /**
     * @var GaiaGuardiansIII
     */
    public GaiaGuardiansIII $owner;
    /** @var ?AxisAlignedBB */
    public ?AxisAlignedBB $axisAlignedBB = null;
    public function __construct(Position $position, $size, GaiaGuardiansIII $gaiaGuardiansIII)
    {
        parent::__construct($position->getX(), $position->getY(), $position->getZ(), $position->getLevel());
        $this->owner = $gaiaGuardiansIII;
        $this->size = $size;
        $this->axisAlignedBB = new AxisAlignedBB($position->getX()-$size, $this->getY(), $position->getZ()-$size,$position->getX()+$size, $this->getY()+5, $position->getZ()+$size);
    }

    /**
     * @return false
     */
    public function onUpdate():void{
        foreach ($this->getLevel()->getCollidingEntities($this->axisAlignedBB) as $entity){
            if ($entity instanceof Player and $entity->canSelected()){
                $entity->setLastDamageCause(new EntityDamageByEntityEvent($this->owner, $entity, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $entity->getHealth(), 0));
                $entity->setHealth(0);
                $this->close();
                return;
            }
        }
        if($this->age % 10 == 0 or $this->age == 0)$this->spawnParticle();
        $this->age++;

        if ($this->age > 20 * 10){
            $this->close();
        }
    }
    public function close(){
        $this->owner->removeLandmine($this);
    }
    public function spawnParticle(){
        for($x=-$this->size*2;$x<=$this->size*2;$x++){
            for($z=-$this->size*2;$z<=$this->size*2;$z++){
                for ($y = 0; $y < 2; $y++){
                    $this->getLevel()->addParticle(new RedstoneParticle($this->add($x/2, 0.1 + $y / 2, $z/2)));
                }
            }
        }
    }

    /**
     * @return GaiaGuardiansIII
     */
    public function getOwner(): GaiaGuardiansIII
    {
        return $this->owner;
    }

    /**
     * @param GaiaGuardiansIII $owner
     */
    public function setOwner(GaiaGuardiansIII $owner): void
    {
        $this->owner = $owner;
    }

}