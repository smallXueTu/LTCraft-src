<?php


namespace LTEntity\entity\Gaia\SkillEntity;

use LTEntity\entity\Gaia\GaiaGuardiansIII;
use pocketmine\entity\Entity;
use pocketmine\entity\Projectile;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;

class Bomb extends Projectile
{
    /** @var GaiaGuardiansIII $owner */
    private GaiaGuardiansIII $owner;
    public function __construct(Level $level, CompoundTag $nbt, GaiaGuardiansIII $gaiaGuardiansIII)
    {
        $this->owner = $gaiaGuardiansIII;
        parent::__construct($level, $nbt);
    }

    public function onUpdate($currentTick)
    {
        parent::onUpdate($currentTick);
    }
    public function saveNBT()
    {

    }

}