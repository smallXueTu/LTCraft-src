<?php


namespace pocketmine\item;


use pocketmine\entity\Entity;
use pocketmine\Player;

interface Bow
{
    public function spawnArrow(Player $player): ?Entity;
    public function deductResources(Player $player): bool;
}