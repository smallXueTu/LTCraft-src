<?php
namespace LTPet\Pets\WalkingPets;

use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;

class LTCreeper extends WalkingPet{

	const NETWORK_ID = 33;
	public $width = 0.72;
	public $height = 1.8;
	public $name = '苦力怕';
 /*   
	public function attack($damage, EntityDamageEvent $source) {
		if(!($source instanceof EntityDamageByEntityEvent) or (!$source->getDamager() instanceof Player))return;
		if($source->getDamager()!==$this->owner)return;
		$this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_IGNITED, true);
		$this->setDataProperty(self::DATA_FUSE_LENGTH, self::DATA_TYPE_INT, 80);
		$this->owner->sendMessage('设置成功');
		return true;
	}
*/

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
