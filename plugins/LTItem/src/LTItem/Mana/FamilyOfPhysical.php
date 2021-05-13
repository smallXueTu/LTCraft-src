<?php


namespace LTItem\Mana;


use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\OrnamentsInventory;
use pocketmine\Player;

class FamilyOfPhysical extends ManaOrnaments
{
    public int $lastConsumption = 0;
    public int $energy = 30;

    /**
     * @param Player $player
     * @param int $index
     * @param BaseInventory $inventory
     * @return bool
     */
    public function onTick(Player $player, int $index, BaseInventory $inventory): bool
    {
        if ($player->isVIP()===false and $player->getFlyTime()<time() and !$player->forceFlying and $inventory instanceof OrnamentsInventory and !$inventory->onUse and $player->getServer()->getTick() - $this->lastConsumption >= 10){
            $install = $player->getBuff()->checkOrnamentsInstall("天翼族之眼");
            $this->lastConsumption = $player->getServer()->getTick();
			if($player->getBuff()->getMana()>=($install?80:50)){
				if(!$player->getAllowFlight())$player->setAllowFlight(true);
			}
            if ($player->isFlying() and $player->getLevel()->getName()!=='zc' and $player->isSurvival()){
                if(!$player->getBuff()->consumptionMana($install?30:20)){
                    $player->setFlying(false);//没魔力
                }
                if ($this->energy-- <= 0){
                    if (!$install)$player->setFlying(false);//没能量
                    $this->energy = 0;
                }
            }else{
                if ($this->energy < 30)$this->energy++;
            }
            $inventory->setItem($index, $this);
        }
		parent::onTick($player, $index, $inventory);
        return true;
    }
}