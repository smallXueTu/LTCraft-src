<?php
namespace LTPet\Pets\WalkingPets;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\Player;

class LTSilverfish extends WalkingPet{

	const NETWORK_ID = 39;
	public $name = '蠢虫';
	public $width = 0.2;
	public $height = 0.25;
	public $length = 0.2;

	/**
	 * @param Player $player
	 */
	public function spawnTo(Player $player){
		$pk = new AddEntityPacket();
		$pk->eid = $this->getId();
		$pk->type = self::NETWORK_ID;
		$pk->x = $this->x;
		$pk->y = $this->y;
		$pk->z = $this->z;
		$pk->speedX = $this->motionX;
		$pk->speedY = $this->motionY;
		$pk->speedZ = $this->motionZ;
		$pk->yaw = $this->yaw;
		$pk->pitch = $this->pitch;
		$pk->metadata = $this->dataProperties;
		$player->dataPacket($pk);
		parent::spawnTo($player);
	}
}