<?php

namespace LTEntity\entity\monster\walking;

use LTEntity\entity\monster\WalkingMonster;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\network\protocol\MobEquipmentPacket;

class ARabbit extends WalkingMonster
{
    const NETWORK_ID = 18;

    public $width = 0.4;
    public $height = 0.75;

    public $eyeHeight = 0.6;

    public function getName()
    {
        return "Rabbit";
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
