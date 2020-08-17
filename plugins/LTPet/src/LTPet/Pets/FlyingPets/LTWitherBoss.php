<?php
namespace LTPet\Pets\FlyingPets;

use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;

class LTWitherBoss extends FlyingPet{
	const NETWORK_ID = 52;

	public $width = 0.72;
	public $length = 6;
	public $height = 2;
	public $name = '凋零';
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