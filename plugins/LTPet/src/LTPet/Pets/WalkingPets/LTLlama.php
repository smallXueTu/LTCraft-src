<?php
namespace LTPet\Pets\WalkingPets;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\Player;

class LTLlama extends WalkingPet{

	const NETWORK_ID = 29;

	public $name = '羊驼';
	public $width = 0.6;
	public $length = 2;
	public $height = 2;
	protected $RideVector3 = [[0, 2.3, 0]];
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
