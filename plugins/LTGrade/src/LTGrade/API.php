<?php
namespace LTGrade;

use pocketmine\entity\Entity;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\BossEventPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\SetEntityDataPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Attribute;
use LTItem\Main as LTItem;

class API{
	const ENTITY = 37;
	const LEVEL = 0;
	const ARMOR = 1;
	const POWER = 2;
	const TASK = 3;
	public $eid;
	public $player;
	public $attributes = [];
	public $attribute;
	public $ShowStatus = false;
	private $removed = false;
	private $packet;
	public static $tab='                                                             ';
	public function setShowHealth($v){
		if($v==$this->ShowStatus)return;
		$this->ShowStatus=$v;
		if($v){
			$this->player->getAttributeMap()->getAttribute(Attribute::HEALTH)->setMaxValue(1)->setValue(1, true);
			$this->setPercentage($this->player->getHealth()/$this->player->getMaxHealth()*100);
		}else{
			$this->player->getAttributeMap()->getAttribute(Attribute::HEALTH)->setMaxValue($this->player->getMaxHealth())->setValue($this->player->getHealth(), true);
			$this->setPercentage(100);
		}
	}
	public function __construct(Player $player, string $title, int $percentage){
		$this->player=$player;
		$this->eid=Entity::$entityCount++;
		$packet = new AddEntityPacket();
		$packet->eid = $this->eid;
		$packet->type = self::ENTITY;
		$packet->x = $player->x;
		$packet->y = $player->y;
		$packet->z = $player->z;
		$packet->metadata = [
			Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 0 ^ 1 << Entity::DATA_FLAG_SILENT ^ 1 << Entity::DATA_FLAG_INVISIBLE ^ 1 << Entity::DATA_FLAG_NO_AI],
			Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title],
			Entity::DATA_BOUNDING_BOX_WIDTH => [Entity::DATA_TYPE_FLOAT, 0],
			Entity::DATA_BOUNDING_BOX_HEIGHT => [Entity::DATA_TYPE_FLOAT, 0]
		];
		$this->packet = $packet;
		$this->attribute= new Attribute(Attribute::HEALTH, "minecraft:health", 0.00, 100.00, $percentage, true);
		$this->update(0);
		$this->update(1);
		$this->update(2);
		$this->update(3);
		if($player->getAStatusIsDone('右边显示') and !$player->getAStatusIsDone('血量格式')){
			$this->removed = true;
			return;
		}
		$this->player->dataPacket($packet);
		$bpk = new BossEventPacket();
		$bpk->eid = $this->eid;
		$bpk->eventType = 1;
		$this->player->dataPacket($bpk);
		$pk = new UpdateAttributesPacket();
		$pk->entries[] = $this->attribute;
		$pk->entityId = $this->eid;
		$this->player->dataPacket($pk);
	}
	public function setTitleAndPercentage(string $title, int $percentage){
		$this->setPercentage($percentage);
		$this->setTitle($title);
	}
	public function setPercentage(int $percentage){
		if($percentage<=0)$percentage = 1;
		$pk = new UpdateAttributesPacket();
		$pk->entries[] = $this->attribute->setValue($percentage);
		$pk->entityId = $this->eid;
		$this->player->dataPacket($pk);
	}
	public function setTitle(string $title){
		$npk = new SetEntityDataPacket();
		$npk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title]];
		$npk->eid = $this->eid;
		$this->player->dataPacket($npk);
	}
	public function update($type=0){
		switch($type){
			case self::ARMOR:
				$armor=$this->player->getArmorV();
				$armorB=round($armor/($armor+300),2)*100;
				if(($k=$this->player->getEffect(11))!==null)
					$armorB=$armorB+$k->getAmplifier()*20;
				$armorB+=intval($this->player->getBuff()->getArmor())/2;
				if($armorB>95)
					if($k!==null and $k->getAmplifier()>=5 and $armorB>100)
						$armorB=100;
					else
						$armorB=95;
				$this->attributes[$type]='§d护甲:'.$armor.' 实际伤害减少('.$armorB.'%%%%)';
			break;
			case self::TASK:
				$this->attributes[$type]=str_replace('\n',PHP_EOL . self::$tab,$this->player->getTask()->getTaskMessage());
			break;
			case self::LEVEL:
				switch($this->player->level->getName()){
					case 'zc':
						$name='主城';
					break;
					case 'zy':
						$name='资源世界';
					break;
					case 'ender':
						$name='末地';
					break;
					case 'nether':
						$name='地狱';
					break;
					case 'dp':
						$name='土豪地皮';
					break;
					case 'land':
						$name='普通地皮';
					break;
					case 'jm':
						$name='新手地皮';
					break;
					case 'pvp':
						$name='PVP世界';
					break;
					case 'create':
						$name='创造世界,返回输入§d/w zc';
					break;
					case 'boss':
						$name='最后一战';
					break;
					case 'login':
						$name='登录岛';
					break;
					case 'f1':
						$name='异化豪宅';
					break;
					case 'pve':
						$name='暗影岛屿';
					break;
					case 'f2':
						$name='骑士圣地';
					break;
					case 'f3':
						$name='沙亡神殿';
					break;
					case 'f4':
						$name='生化废区';
					break;
					case 'f5':
						$name='玄之广场';
					break;
                    case 'f6':
                        $name='神秘密室';
                        break;
					case 'f7':
						$name='死神竞技场';
					break;
					case 't8':
						$name='召唤师峡谷';
					break;
					case 'f9':
						$name='亚特兰蒂斯';
					break;
					case 't1':
						$name='异界之地';
					break;
					case 't2':
						$name='冰雪之地';
					break;
					case 't3':
						$name='元素之城';
					break;
					case 't4':
						$name='古怪决斗场';
					break;
					case 't5':
						$name='龙之城';
					break;
					case 't6':
						$name='符文之城';
					break;
					case 's1':
						$name='地狱城';
					break;
					case 's2':
						$name='法尔之旅';
					break;
					default:
					$name=$this->player->level->getName();
				}
				$this->attributes[$type]='§3世界:'.$name;
			break;
		}
	}
	public function upDateTitleAndPercentage($Count, $Time){
		if($this->removed)return;
		if($this->ShowStatus)$this->setPercentage($this->player->getHealth()/$this->player->getMaxHealth()*100);
		$title='§l'.PHP_EOL .PHP_EOL;
		if($this->player->getAStatusIsDone('右边显示')){
			$this->setTitle($title.'§a你的生命值:'.$this->player->getHealth().'/'.$this->player->getMaxHealth());
			return;
		}
		if($this->ShowStatus)
			$title.='                               §a你的生命值:'.$this->player->getHealth().'/'.$this->player->getMaxHealth();
		// $title.=PHP_EOL .self::$tab. '§d在线人数:'.$Count. PHP_EOL .self::$tab. '§e时间:'.$Time;
		$title.=PHP_EOL .self::$tab. '§e时间:'.$Time;
		foreach($this->attributes as $attribute){
			$title.=PHP_EOL .self::$tab. $attribute;
		}
		$this->setTitle($title);
	}

    /**
     * 删除
     */
	public function removeThis(){
		$pk = new RemoveEntityPacket();
		$pk->eid = $this->eid;
		$this->player->dataPacket($pk);
		$this->removed = true;
	}

    /**
     * 恢复
     */
	public function restore(){
		$this->player->dataPacket($this->packet);
		$bpk = new BossEventPacket();
		$bpk->eid = $this->eid;
		$bpk->eventType = 1;
		$this->player->dataPacket($bpk);
		$pk = new UpdateAttributesPacket();
		$pk->entries[] = $this->attribute;
		$pk->entityId = $this->eid;
		$this->player->dataPacket($pk);
		$this->removed = false;
	}
}