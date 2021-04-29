<?php
namespace LTItem\Mana;

use LTEntity\entity\Mana\ManaArrow;
use pocketmine\entity\Arrow;
use pocketmine\entity\Entity;
use pocketmine\item\Bow;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\Player;

class HundredMiddleBow extends BaseMana implements Bow
{
    /**
     * @param Entity $entity
     * @return float|int
     */
    public function getModifyAttackDamage(Entity $entity)
    {
        if ($entity instanceof Player){
            return $entity->getMaxHealth() * 0.2;
        }
        return 18;
    }

    public function spawnArrow(Player $player): ?Entity
    {
        if ($player->getBuff()->getMana() < 20)return null;
        $nbt = new CompoundTag('', [
            'Pos' => new ListTag('Pos', [
                new DoubleTag('', $player->x),
                new DoubleTag('', $player->y + $player->getEyeHeight()),
                new DoubleTag('', $player->z)
            ]),
            'Motion' => new ListTag('Motion', [
                new DoubleTag('', -sin($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI)),
                new DoubleTag('', -sin($player->pitch / 180 * M_PI)),
                new DoubleTag('', cos($player->yaw / 180 * M_PI) * cos($player->pitch / 180 * M_PI))
            ]),
            'Rotation' => new ListTag('Rotation', [
                new FloatTag('', $player->yaw),
                new FloatTag('', $player->pitch)
            ]),
            'Fire' => new ShortTag('Fire', $player->isOnFire() ? 45 * 60 : 0)
        ]);
        $diff = ($player->getServer()->getTick() - $player->startAction);
        $p = $diff / 20;
        $f = min((($p ** 2) + $p * 2) / 3, 1) * 2;
        $entity = new ManaArrow($player->getLevel(), $nbt, $player, 2 == $f);
        $entity->f = $f;
        $entity->diff = $diff;
        return $entity;
    }

    public function deductResources(Player $player): bool
    {
        $player->getBuff()->consumptionMana(20);
        $player->getInventory()->sendContents($player);
    }
}