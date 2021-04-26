<?php

namespace LTEntity\entity\monster\walking;

use LTEntity\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

class AVillager extends WalkingMonster
{
    const NETWORK_ID = 15;

    public $width = 0.72;
    public $height = 1.8;

    public $eyeHeight = 1.6;


    public function getName()
    {
        return "Villager";
    }

    public function attackEntity(Entity $player)
    {
        if ($this->attackDelay > 10 && ($this->distanceSquared($player) < 1 or ($this->distanceSquaredNoY($player) < 1 and abs($player->y - $this->y) < 1.5))) {
            $this->attackDelay = 0;

            $ev = new EntityDamageByEntityEvent($this, $player, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $this->getDamage());
            $player->attack($ev->getFinalDamage(), $ev);
        }
    }

    public function getDrops()
    {
        return [];
    }

}
