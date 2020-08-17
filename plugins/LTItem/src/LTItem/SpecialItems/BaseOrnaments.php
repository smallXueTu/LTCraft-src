<?php
namespace LTItem\SpecialItems;

use LTItem\Ornaments;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\CompoundTag;
use LTItem\LTItem;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\Color;

class BaseOrnaments extends Item implements LTItem, Ornaments {
	private $armorV = 0;
	private $miss = 0;
	private $handMessage = false;
	private $lucky = 0;
	private $tough = 0;
	private $PVPDamage = 0;
	private $PVEDamage = 0;
	private $PVEMedical = 0;
	private $PVPMedical = 0;
	private $groupOfBack = 0;
	private $controlReduce = 0;
	private $RealDamage = 0;
	private $PVPArmour = 0;
	private $PVEArmour = 0;
	private $OrnamentsName;
	private $conf;
	public function __construct(array $conf, int $count, CompoundTag $nbt, $weaponInit=true){
		$idInfo=explode(':',$conf['ID']);
		parent::__construct($idInfo[0], $idInfo[1]??0, $count);
		$this->setCompoundTag($nbt);
		$this->setCustomName($conf['名字']);
		if($weaponInit)$this->initO($conf);
		$this->conf = $conf;
		$this->OrnamentsName = $this->getNamedTag()['ornaments'];
	}
	public function initO($conf){
		try{
			$this->armorV = $conf['护甲'];
			$this->miss = $conf['闪避'];
			$this->PVPDamage = $conf['附PVP攻击力'];
			$this->PVEDamage = $conf['附PVE攻击力'];
			$this->PVEMedical = $conf['PVE攻击医疗'];
			$this->PVPMedical = $conf['PVP攻击医疗'];
			$this->groupOfBack = $conf['群回'];
			$this->lucky = $conf['幸运'];
			$this->RealDamage = $conf['附真实伤害'];
			$this->PVPArmour = $conf['附PVP穿甲'];
			$this->PVEArmour = $conf['附PVE穿甲'];
			$this->handMessage = $conf['手持提示'];
			$this->tough = $conf['坚韧'];
			$this->controlReduce = $conf['控制减少'];
		}catch(\Throwable $e){
			Server::getInstance()->getLogger()->warning($this->getWeaponType().':'.$this->getLTName().'配置文件出错 在'.$e->getLine());
		}
	}
	public function getTypeName() : string {
		return '饰品';
	}
	public function getControlReduce() : int{
		return $this->controlReduce;
	}
	public function getPVPDamage() : int{
		return $this->PVPDamage;
	}
	public function getPVEDamage() : int{
		return $this->PVEDamage;
	}
	public function getPVEMedical() : int{
		return $this->PVEMedical;
	}
	public function getPVPMedical() : int{
		return $this->PVPMedical;
	}
	public function getGroupOfBack() : int{
		return $this->groupOfBack;
	}
	public function getTough() : int{
		return $this->tough;
	}
	public function getLTName() : string {
		return $this->OrnamentsName;
	}
	public function getRealDamage() : int{
		return $this->RealDamage;
	}
	public function getPVPArmour() : int{
		return $this->PVPArmour;
	}
	public function getPVEArmour() : int{
		return $this->PVEArmour;
	}
	public function getArmorV() : int{
		return $this->armorV;
	}
	public function getLucky() : int{
		return $this->lucky;
	}
	public function getMiss() : int{
		return $this->miss;
	}
	public function getHandMessage(Player $player) : string {
		return $this->handMessage;
	}
	public function getAttribute($attribute){
		return $this->conf[$attribute]??false;
	}
	public function getMaxStackSize() : int{
		return 1;
	}
    public function getInfo() {
		return $this->conf['介绍'];
	}
}