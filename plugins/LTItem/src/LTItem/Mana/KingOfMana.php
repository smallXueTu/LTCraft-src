<?php


namespace LTItem\Mana;


use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\inventory\BaseInventory;
use pocketmine\Player;

/**
 * 王者圣剑
 * Class KingOfMana
 * @package LTItem\Mana
 */
class KingOfMana extends BaseMana
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

    /**
     * @param Player $player
     * @param int $index
     * @param BaseInventory $inventory
     * @return bool
     */
    public function onTick(Player $player, int $index, BaseInventory $inventory): bool
    {
        if ($player->getItemInHand()->equals($this)){
            $effect = $player->getEffect(1);
            if ($effect==null or ($effect->getAmplifier()<=2 and $effect->getDuration()<=5)){
                $player->addEffect(Effect::getEffect(1)->setAmplifier(2)->setDuration(20));
            }
        }
		parent::onTick($player, $index, $inventory);
        return true;
    }
}