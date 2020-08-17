<?php
namespace LTPet\Pets\WalkingPets;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\Player;

class LTSheep extends WalkingPet{
	const NETWORK_ID = 13;

	public $width = 0.75;
	public $length = 1.3;
	public $height = 0.9;
	public $name = '羊';
	protected $RideVector3 = [[0, 2.1, 0]];

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
		$attrebute = new UpdateAttributesPacket();
		$attrebute->entityId = $this->getId();
		$attrebute->entries = $this->attributeMap->getAll();
		$player->dataPacket($attrebute);
		parent::spawnTo($player);
	}
}