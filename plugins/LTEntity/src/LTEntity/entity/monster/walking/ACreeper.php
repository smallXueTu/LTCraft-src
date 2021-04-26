<?php

namespace LTEntity\entity\monster\walking;

use LTEntity\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\level\Explosion;
use pocketmine\level\sound\TNTPrimeSound;
use pocketmine\scheduler\CallbackTask;

class ACreeper extends WalkingMonster
{
    const NETWORK_ID = 33;

    public $width = 0.72;
    public $height = 1.8;
    public $eyeHeight = 1.6;

    public function getName()
    {
        return "Creeper";
    }

    public function attackEntity(Entity $player)
    {
        if ($this->attackDelay > 20 && $this->distanceSquared($player) < 1) {
            $this->attackDelay = 0;

            $this->level->addSound(new TNTPrimeSound($this));
            $ex = new Explosion($this, 3, $this);
            $ex->booom($this->enConfig["攻击"], $this, true);
            $this->close();
        }
    }

    public function getDrops()
    {
        return [];
    }

}





















