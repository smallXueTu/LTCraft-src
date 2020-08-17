<?php
namespace LTItem;

use pocketmine\Player;

interface LTItem{
    /**
     * @return mixed
     */
	public function getLTName();

    /**
     * @return mixed
     */
	public function getTypeName();

    /**
     * @return mixed
     */
    public function getInfo();

    /**
     * @param Player $player
     * @return mixed
     */
    public function getHandMessage(Player $player);
}