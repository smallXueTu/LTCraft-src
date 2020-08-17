<?php
namespace LTCraft;

use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Item;
use pocketmine\Player;
use pocketmine\network\protocol\{AddEntityPacket, SetEntityLinkPacket, RemoveEntityPacket};

class Chair extends Vector3{
	private $player;
	private $eid;
	public function __construct(Block $block, Player $player){
		parent::__construct($block->getX(), $block->getY(), $block->getZ());
		$player->setDataProperty(57, 8, [0, 0, 0]);
		$this->eid = Entity::$entityCount ++;
		$this->player = $player;
		$this->sendLinkedData();
	}
	public function sendEntity($player = null){
		$pk = new AddEntityPacket();
		$pk->eid = $this->eid;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$pk->pitch = 0;
		$pk->yaw = 0;
		$pk->item = 0;
		$pk->meta = 0;
		$pk->x = $this->x + 0.5;
		$pk->y = $this->y + 1.5;
		$pk->z = $this->z + 0.5;
		$pk->type = Item::NETWORK_ID;
		$flags = 0;
		$flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
		$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, ""],
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1]
		];
		$this->player->getServer()->broadcastPacket($player===null?$this->player->getAllViewer():[$player], $pk);
	}
	public function sendLinkedData($player = null){
		$this->sendEntity($player);
		if($player===null)$this->player->teleport($this, null, null, false);
		$pk = new SetEntityLinkPacket();
		$pk->from = $this->eid;
		$pk->to = $this->player->getId();
		$pk->type = 1;
		$this->player->getServer()->broadcastPacket($player===null?$this->player->getAllViewer():[$player], $pk);
		if($player!==null)return;
		// $pk->to = 0;
		$this->player->setLinkedEntity($this);
		// $this->player->dataPacket($pk);
	}
	public function removeEntity($player=null){
		if($player===null){
			$player=$this->player->getAllViewer();
		}else{
			$player=[$player];
		}
		$pk = new RemoveEntityPacket();
		$pk->eid = $this->eid;
		$this->player->getServer()->broadcastPacket($player, $pk);
	}
	public function unlinkPlayer(){
		$this->removeEntity();
		$this->player->setLinkedEntity(null);
	}
}