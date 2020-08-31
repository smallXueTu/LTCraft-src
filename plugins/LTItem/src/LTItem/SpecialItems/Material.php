<?php
namespace LTItem\SpecialItems;

use LTItem\SpecialItems\Material\BraveManWeaponGiftBag;
use LTItem\SpecialItems\Material\Coolant;
use LTItem\SpecialItems\Material\Drawings;
use LTItem\SpecialItems\Material\EmptyRewardBox;
use LTItem\SpecialItems\Material\MagicStick;
use LTItem\SpecialItems\Material\MysteriousPetPieces;
use LTItem\SpecialItems\Material\RewardBoxGene;
use LTItem\SpecialItems\Material\SeniorWeaponGiftBag;
use LTItem\SpecialItems\Material\SmeltingFurnace;
use LTItem\SpecialItems\Material\SmeltingStone;
use LTItem\SpecialItems\Material\TerraSteelIngot;
use LTItem\SpecialItems\Material\MysteriousArmorGiftBag;
use LTItem\SpecialItems\Material\WakeUpStone;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use LTItem\LTItem;
use pocketmine\Player;

class Material extends Item implements LTItem{
	public $MaterialName;
	private $handMessage = false;

    private static $materials = [];
	/** @var array  */
	private $conf;

    /**
     * @param string $name
     * @param array $conf
     * @param int $count
     * @param CompoundTag $nbt
     * @return Material
     */
	public static function getMaterial(string $name, array $conf, int $count, CompoundTag $nbt) : Material{
        if (isset(self::$materials[$name])){
            return new self::$materials[$name]($conf, $count, $nbt);
        }
        return new Material($conf, $count, $nbt);
    }


    /**
     * 初始化材料
     */
    public static function initMaterial()
    {
        self::$materials['泰拉钢锭'] = TerraSteelIngot::class;
        self::$materials['盖亚魂锭'] = TerraSteelIngot::class;
        self::$materials['神秘宠物碎片'] = MysteriousPetPieces::class;
        self::$materials['勇者武器礼包'] = BraveManWeaponGiftBag::class;
        self::$materials['高级武器礼包'] = SeniorWeaponGiftBag::class;
        self::$materials['神秘盔甲礼包'] = MysteriousArmorGiftBag::class;
        self::$materials['觉醒石'] = WakeUpStone::class;
        self::$materials['史诗武器图纸'] = Drawings::class;
        self::$materials['史诗战靴图纸'] = Drawings::class;
        self::$materials['终极武器图纸'] = Drawings::class;
        self::$materials['史诗护膝图纸'] = Drawings::class;
        self::$materials['史诗头盔图纸'] = Drawings::class;
        self::$materials['史诗胸甲图纸'] = Drawings::class;
        self::$materials['觉醒石模板'] = Drawings::class;
        self::$materials['熔炼冷却液'] = Coolant::class;
        self::$materials['熔炼原石'] = SmeltingStone::class;
        self::$materials['熔炼熔炉'] = SmeltingFurnace::class;
        self::$materials['空奖励箱'] = EmptyRewardBox::class;
        self::$materials['奖励箱-基因'] = RewardBoxGene::class;
        self::$materials['魔法棍'] = MagicStick::class;
    }
    /**
     * Material constructor.
     * @param array $conf 配置文件
     * @param int $count 数量
     * @param CompoundTag $nbt
     */
	public function __construct(array $conf, int $count, CompoundTag $nbt){
		$idInfo=explode(':',$conf['材料ID']);
		parent::__construct($idInfo[0], $idInfo[1]??0, $count);
		$this->setCompoundTag($nbt);
		$this->setCustomName($conf['材料名字']);
		// $this->init($conf);
		$this->conf = $conf;
		$this->MaterialName=$this->getNamedTag()['material'];
		$this->handMessage=$conf['手持提示'];
	}
	public function getLTName(){
		return $this->MaterialName;
	}
	public function getHandMessage(Player $player) : string {
		return $this->handMessage;
	}

    /**
     * @return string
     */
	public function getTypeName(){
		return '材料';
	}

    /**
     * @return bool
     */
	public function canBeActivated(): bool
    {
        return true;
    }

    /**
     * @param Level $level
     * @param Player $player
     * @param Block $block
     * @param Block $target
     * @param $face
     * @param $fx
     * @param $fy
     * @param $fz
     * @return bool
     */
    public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz)
    {
        if ($this->getUseMessage()!=false){
            $player->sendMessage('§e'.$this->getUseMessage());
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getInfo()  {
		return false;
	}

    /**
     * @return mixed
     */
	public function getUseMessage(){
		return $this->conf['使用提示'];
	}

    /**
     * @param Item $item
     * @param bool $checkDamage
     * @param bool $checkCompound
     * @param bool $checkCount
     * @return bool
     */
	public function equals(Item $item, bool $checkDamage = true, bool $checkCompound = true, $checkCount = false) : bool{
		if($this->id === $item->getId() and ($checkDamage === false or $this->getDamage() === $item->getDamage()) and ($checkCount === false or $this->getCount() === $item->getCount())){
			if($checkCompound){
				if($item instanceof Material and $item->getLTName()==$this->getLTName())return true;
					return false;
			}else return false;
		}
		return false;
	}

    /**
     * @return int
     */
	public function getMaxStackSize() : int{
		if(!isset($this->conf['叠加最大']))
			return 64;
		if($this->conf['叠加最大']===false)
			return 64;
		return $this->conf['叠加最大'];
	}
}