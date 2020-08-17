<?php
namespace LTEntity\entity\Gaia\SkillEntity;

use pocketmine\entity\Effect;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;

class DieArea extends Position
{
    /** @var int */
    public $size;
    /** @var int */
    public $age;
    /** @var array */
    public $ctime;
    /** @var AxisAlignedBB */
    public $axisAlignedBB = null;
    public function __construct(Position $position, $size)
    {
        parent::__construct($position->getX(), $position->getY(), $position->getZ(), $position->getLevel());
        $this->size = $size;
        $this->axisAlignedBB = new AxisAlignedBB($position->getX()-$size, $this->getY(), $position->getZ()-$size,$position->getX()+$size, $this->getY()+5, $position->getZ()+$size);
    }

    public function onUpdate()
    {
        foreach ($this->getLevel()->getCollidingEntities($this->axisAlignedBB) as $entity){
            if ($entity instanceof Player and $entity->canSelected()){
				$effect = $entity->getEffect(Effect::WITHER);
				if($effect!==null and $effect->getSpecial())continue;
                if($this->age >= 20){
                    if(!isset($this->ctime[$entity->getName()])){
                        $this->ctime[$entity->getName()] = 0;
                    }
                    if($this->ctime[$entity->getName()]++>10){
                        $entity->addEffect(Effect::getEffect(Effect::WITHER)->setAmplifier(3)->setDuration(3*20)->setSpecial(true));
                    }
                }
            }
        }
        if($this->age % 10 == 0 or $this->age == 0)$this->spawnParticle();
        $this->age++;
    }
    public function spawnParticle(){
        for($x=-$this->size*2;$x<=$this->size*2;$x++){
            for($z=-$this->size*2;$z<=$this->size*2;$z++){
                $this->getLevel()->addParticle(new InkParticle($this->add($x/2, 0.1, $z/2)));
            }
        }
    }
}