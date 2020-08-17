<?php
namespace LTPet\Pets\FlyingPets;

use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use LTPet\Pets\MountPet;

class LTEnderDragon extends FlyingPet implements MountPet{

	const NETWORK_ID = 53;
	public $width = 5;
	public $length = 8;
	public $name = '末影龙';
	public $height = 3;
	protected $RideVector3 = [[0, 4.1, -2.5], [0, 4.1, -1.5], [0, 4.1, 0.5], [0, 4.1, 1.5], [0, 4.1, 2.5], [0, 4.1, 3.5]];
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