<?php
namespace MyPlot\provider;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;

class EconomySProvider implements EconomyProvider
{
    public function reduceMoney(Player $player, $amount, $info=null) {
        if ($amount == 0) {
            return true;
        } elseif ($amount < 0) {
            $ret = EconomyAPI::getInstance()->addMoney($player, -$amount, $info);
        } else {
            $ret = EconomyAPI::getInstance()->reduceMoney($player, $amount, $info);
        }

        return ($ret === true);
    }
}