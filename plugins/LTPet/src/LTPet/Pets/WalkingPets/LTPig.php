<?php
namespace LTPet\Pets\WalkingPets;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\Player;
use LTPet\Pets\MountPet;

class LTPig extends WalkingPet implements MountPet{

	const NETWORK_ID = 12;
    public $width = 1.45;
	public $length = 1.22;
    public $height = 1.12;
	public $name = '猪';
	protected $RideVector3 = [[0, 2, 0]];

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