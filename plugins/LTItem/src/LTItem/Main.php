<?php
namespace LTItem;
use LTItem\Mana\BaseMana;
use LTItem\Mana\Mana;
use LTItem\Mana\ManaSystem;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\CallbackTask;
use pocketmine\item\Item;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\utils\Color;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\entity\Effect;
use pocketmine\Player;

use LTItem\SpecialItems\Weapon;
use LTItem\SpecialItems\Armor;
use LTItem\SpecialItems\BaseOrnaments;
use LTItem\SpecialItems\Material;
use LTItem\LTItem;

use LTGrade\EventListener as LTGradeEventListener;

class Main extends PluginBase{
	public static $instance=null;
	public $EventListener=null;
	public $command=null;
	public $JZ=[];
	public $YC=[];
	public $TY=[];
	public $KJ=[];
	public $Buff=[];
	public $MANA=[];
	public $CL=[];
	public $SQ=[];

    /**
     * @return Main
     */
	public static function getInstance(){
		return self::$instance;
	}
	public function onDisable(){
		if(isset($this->R))$this->R->save(false);
	}
	public function onEnable(){
		self::$instance=$this;
		$this->EventListener=new EventListener($this,$this->getServer());
		$this->getServer()->getPluginManager()->registerEvents($this->EventListener,$this);
		$this->command=new Commands($this,$this->getServer());
		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder());
			mkdir($this->getDataFolder().'Items');
			mkdir($this->getDataFolder().'Items/近战');
			mkdir($this->getDataFolder().'Items/远程');
			mkdir($this->getDataFolder().'Items/通用');
			mkdir($this->getDataFolder().'Items/Buff');
			mkdir($this->getDataFolder().'Items/盔甲');
			mkdir($this->getDataFolder().'Items/材料');
			mkdir($this->getDataFolder().'Items/饰品');
			mkdir($this->getDataFolder().'Items/魔法');
		}else{
			if(!file_exists($this->getDataFolder().'Items')){
				mkdir($this->getDataFolder().'Items');
				mkdir($this->getDataFolder().'Items/近战');
				mkdir($this->getDataFolder().'Items/远程');
				mkdir($this->getDataFolder().'Items/通用');
				mkdir($this->getDataFolder().'Items/Buff');
				mkdir($this->getDataFolder().'Items/盔甲');
				mkdir($this->getDataFolder().'Items/材料');
				mkdir($this->getDataFolder().'Items/饰品');
				mkdir($this->getDataFolder().'Items/魔法');
			}else{
				@mkdir($this->getDataFolder().'Items/近战');
				@mkdir($this->getDataFolder().'Items/远程');
				@mkdir($this->getDataFolder().'Items/通用');
				@mkdir($this->getDataFolder().'Items/Buff');
				@mkdir($this->getDataFolder().'Items/盔甲');
				@mkdir($this->getDataFolder().'Items/材料');
				@mkdir($this->getDataFolder().'Items/饰品');
				@mkdir($this->getDataFolder().'Items/魔法');
			}
		}
		$this->config=new Config($this->getDataFolder().'Config.yml',Config::YAML,array(
			'爆炸'=>[],
			'远程'=>[]
		));
		$this->R=new Config($this->getDataFolder().'R.yml',Config::YAML,array());
		$this->getAllItemss();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,'runEffect']), 1200);
		ManaSystem::initMana();
		Material::initMaterial();
		Weapon::initWeapons();
		Item::$LTIteminitd=true;
	}
	public static function updateArmorColor(){
		$server = Server::getInstance();
		foreach($server->getOnlinePlayers() as $player){
			$send = false;
			$Armors = [];
			foreach($player->getInventory()->getArmorContents() as $index => $i){
				if($i instanceof Armor){
					// var_dump($i->getConf('颜色'));
					if($i->getConf('颜色')==='*'){
						$send = true;
						$nbt=$i->getNamedTag();
						if(++$i->colorInfo[1]>15){
							$i->colorInfo[1] = 0;
							if(++$i->colorInfo[0]>15){
								$i->colorInfo[0] = 0;
							}
						}
						$nbt['customColor'] = new IntTag("customColor", Color::averageColor(Color::getDyeColor($i->colorInfo[0]), Color::getDyeColor($i->colorInfo[1]))->getColorCode());
						// var_dump(Color::averageColor(Color::getDyeColor($i->colorInfo[0]), Color::getDyeColor($i->colorInfo[1]))->getColorCode());
						$nbt=$i->setNamedTag($nbt);
					}
				}
				$Armors[$index] = $i;
			}
			if($send)$player->getInventory()->setArmorContents($Armors);
		}
	}
	public function runEffect(){
		$this->R->save();
		foreach($this->getServer()->getOnlinePlayers() as $p){
			$p->getBuff()->runEffect();
		}
	}
	public function getConf($type, $name){
		switch($type){
		case '近战':
			if(isset($this->JZ[$name]))return $this->JZ[$name]->getAll();else return false;
		case '远程':
			if(isset($this->YC[$name]))return $this->YC[$name]->getAll();else return false;
		case '通用':
			if(isset($this->TY[$name]))return $this->TY[$name]->getAll();else return false;
		case '盔甲':
			if(isset($this->KJ[$name]))return $this->KJ[$name]->getAll();else return false;
		case '材料':
			if(isset($this->CL[$name]))return $this->CL[$name]->getAll();else return false;
		case '饰品':
			if(isset($this->SQ[$name]))return $this->SQ[$name]->getAll();else return false;
		case '魔法':
			if(isset($this->MANA[$name]))return $this->MANA[$name]->getAll();else return false;
		default:
			return false;
		}
	}
	public function CArmor(Player $player, $target=false){
		$inventory=$player->getInventory();
		switch($target){
			case 'health':
				$helmet=$inventory->getHelmet();
				$chestplate=$inventory->getChestplate();
				$lenggings=$inventory->getLeggings();
				$boots=$inventory->getBoots();
				$health = 0;
				if($helmet instanceof Armor){
					$health +=$helmet->getHealth();
				}
				if($chestplate instanceof Armor){
					$health +=$chestplate->getHealth();
				}
				if($lenggings instanceof Armor){
					$health +=$lenggings->getHealth();
				}
				if($boots instanceof Armor){
					$health +=$boots->getHealth();
				}
			return $health;
			break;
		}
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args){
		$this->command->onCommand($sender,$command,$label,$args);
	}
	public function getAllItemss(){//错就错了...
		$this->getItemss($this->getDataFolder().'Items/近战','JZ');
		$this->getItemss($this->getDataFolder().'Items/远程','YC');
		$this->getItemss($this->getDataFolder().'Items/通用','TY');
		$this->getItemss($this->getDataFolder().'Items/盔甲','KJ');
		$this->getItemss($this->getDataFolder().'Items/Buff','Buff');
		$this->getItemss($this->getDataFolder().'Items/材料','CL');
		$this->getItemss($this->getDataFolder().'Items/饰品','SQ');
		$this->getItemss($this->getDataFolder().'Items/魔法','MANA');
	}
	public function getItemss($path,$type){
	 foreach(scandir($path) as $afile){
		 $fname=explode('.',$afile);
		  if($afile=='.' or $afile=='..' or is_dir($path.'/'.$afile) or end($fname)!=='yml')continue;
			$name = explode('.', $afile);
			unset($name[count($name)-1]);
			$name = implode('.', $name);
			$this->$type[$name]=new Config($path.'/'.$afile,Config::YAML,array());
			// if($this->$type[$name]->get('等级')=='定制'){
				// unlink($path.'/'.$afile);
				// unset($this->$type[$name]);
			// }
		}
	}
	public function ThereAre($type,$name){
		switch($type){
		case '近战':
			if(isset($this->JZ[$name]))return true;else return false;
		case '远程':
			if(isset($this->YC[$name]))return true;else return false;
		case '通用':
			if(isset($this->TY[$name]))return true;else return false;
		case '盔甲':
			if(isset($this->KJ[$name]))return true;else return false;
		case '材料':
			if(isset($this->CL[$name]))return true;else return false;
		case '饰品':
			if(isset($this->SQ[$name]))return true;else return false;
		case '魔法':
			if(isset($this->MAN[$name]))return true;else return false;
		default:
			return false;
		}
	}
	public function exists($name){
		return file_exists($this->getDataFolder().'Weapon/Buff/'.strtolower($name).'.yml');
	}
	public static function isThisItem(Item $item){
		if($item instanceof Weapon or $item instanceof Armor or $item instanceof Material or $item instanceof BaseOrnaments or $item instanceof Mana)return true;
		return false;
	}
	public static function isThisItemForNBT(CompoundTag $nbt){
		if(isset($nbt['attribute'][2]) or isset($nbt['material']) or isset($nbt['armor'][1]) or isset($nbt['ornaments']) or isset($nbt['mana']))return true;
		else return false;
	}
	public static function getTypeAndNameForNBT(CompoundTag $nbt){
		if(isset($nbt['attribute'][2])){
			return [$nbt['attribute'][1], $nbt['attribute'][2]];
		}elseif(isset($nbt['material'])){
			return ['材料', $nbt['material']];
		}elseif(isset($nbt['armor'])){
			return ['盔甲', $nbt['armor'][1]];
		}elseif(isset($nbt['ornaments'])){
			return ['饰品', $nbt['ornaments']];
		}elseif(isset($nbt['mana'])){
			return ['魔法', $nbt['mana'][1]];
		}else return false;
	}

    /**
     * @param $type
     * @param $name
     * @param string $player
     * @return bool|Weapon
     */
	public function createWeapon($type,$name,$player=''){
		if($player instanceof Player)$player=strtolower($player->getName());
		switch($type){
		case '近战':
			if(isset($this->JZ[$name]))$conf=$this->JZ[$name]->getAll();else return false;
			break;
		case '远程':
			if(isset($this->YC[$name]))$conf=$this->YC[$name]->getAll();else return false;
			break;
		case '通用':
			if(isset($this->TY[$name]))$conf=$this->TY[$name]->getAll();else return false;
			break;
		default:
			return false;
		}
		$item=Weapon::getWeapon($name, $conf, 1, new CompoundTag('',[
			'Unbreakable'=>new ByteTag('Unbreakable',1),
			'attribute'=>new ListTag('attribute',[
				new StringTag('',$conf['全员可用']==true?'*':$player),//0 绑定
				new StringTag('',$type),//1 武器类型
				new StringTag('',$name),//2 武器名字
				new StringTag('',0),//3 附加攻击力
				new StringTag('',0.0),//4 附加吸血
				new StringTag('',0),//5 附加雷击伤害
				new StringTag('',0),//6 经验
				new StringTag('',1),//7 武器星级
				new StringTag('',0),//8 附加真实伤害
				new StringTag('',1),//9 技能持续时间
				new StringTag('',0),//10 技能冷却缩短
				new StringTag('',0),//11 技能效果附加
				new StringTag('',0),//12 群回量
				new StringTag('',1),//13 武器等级
				new StringTag('',''),//14 自身药水附加
				new StringTag('',''),//15 对方药水附加
				new StringTag('',0),//16 群回范围
				new StringTag('',0),//17 穿甲百分比
				new StringTag('',0),//18 击飞
				new StringTag('',0),//19 击退
				new StringTag('',''),//20 武器技能名
				new StringTag('',0),//21 觉醒层
				new StringTag('',''),//22 武器基因
				new StringTag('',1),//23 基因等级
				new StringTag('',1),//24 基因等级
				]),
				'display' => new CompoundTag("display", [
					"Name" => new StringTag("Name", $conf['武器名'])
				])
			]));
		return $item;
	}
	
	public function isEquals($item, $arr, $player=null){
		switch($arr[0]){
			case '近战':
			case '通用':
			case '远程':
				if($item instanceof Weapon and $item->getWeaponType()===$arr[0] and $item->getLTName()===$arr[1] and ($player===null or $item->canUse($player))){
					return true;
				}
				return false;
			break;
			case '材料':
				if($item instanceof Material and $item->getLTName()===$arr[1]){
					return true;
				}
				return false;
			break;
			case '饰品':
				if($item instanceof Ornaments and $item->getLTName()===$arr[1]){
					return true;
				}
				return false;
			break;
			case '盔甲':
				if($item instanceof Armor and  $item->getLTName()===$arr[1] and ($player===null or $item->canUse($player))){
					return true;
				}
				return false;
			break;
			case '魔法':
				if($item instanceof Mana and  $item->getLTName()===$arr[1] and ($player===null or $item->canUse($player))){
					return true;
				}
				return false;
			break;
			case '更多要求':
				switch($arr[1]){
					case '近战':
					case '通用':
					case '远程':
						if($item instanceof Weapon and $item->getWeaponType()===$arr[1] and ($arr[2]=='*' or $item->getLTName()==$arr[2]) and ($player===null or $item->canUse($player)) and ($arr[3]=='*' or $item->getWlevel()==$arr[3]) and ($arr[4]=='*' or $item->getLevel()>=$arr[4]))return true;
						return false;
					break;
					case '盔甲':
						if($item instanceof Armor and ($arr[2]=='*' or $item->getLTName()==$arr[2]) and ($player===null or $item->canUse($player)) and ($arr[3]=='*' or $item->getAttribute('等级')==$arr[3]) and ($arr[4]=='*' or $item->getLevel()>=$arr[4]))return true;
						return true;
					break;
				}
			break;
		}
		return false;
	}

    /**
     * @param $name
     * @return Item
     */
	public function createMaterial($name){
		if(!isset($this->CL[$name]))return Item::get(0);
		$conf=$this->CL[$name]->getAll();
		$nbt=new CompoundTag('',[
			'material'=>new StringTag('material',$name),
			'display' => new CompoundTag("display", [
			"Name" => new StringTag("Name", $conf['材料名字'])
			])
		]);
        return Material::getMaterial($name, $conf, 1, $nbt);
	}

    /**
     * @param $name
     * @param null $player
     * @return Item
     */
	public function createMana($name, $player=null){
        if(isset($this->MANA[$name]))$conf=$this->MANA[$name]->getAll();else return Item::get(0);
        if($player instanceof Player)$player=strtolower($player->getName());
        $item=ManaSystem::getManaItem($name, $conf, 1, new CompoundTag('',[
            'Unbreakable'=>new ByteTag('Unbreakable',1),
            'mana'=>new ListTag('mana',[
                new StringTag('',$conf['全员可用']==true?'*':$player),//0 绑定
                new StringTag('',$name),//1 物品名字
                new StringTag('',$name),//2 Mana
            ]),
            'display' => new CompoundTag("display", [
                "Name" => new StringTag("Name", $conf['名字'])
            ])
        ]));
        return $item;
    }

    /**
     * @param $name
     * @return BaseOrnaments|Item
     */
	public function createOrnaments($name){
		if(!isset($this->SQ[$name]))return Item::get(0);
		$conf=$this->SQ[$name]->getAll();
		$item=new BaseOrnaments($conf, 1, new CompoundTag('',[
			'ornaments'=>new StringTag('ornaments',$name),
			'display' => new CompoundTag("display", [
			"Name" => new StringTag("Name", $conf['名字'])
			])
		]));
		return $item;
	}
	public static function createUnbreakableItem($ids){//这个方法可以创建无限耐久的物品 参数 ID:特殊值
		$ids=explode(':',$ids);
		$item=Item::get($ids[0],$ids[1]??0,1);
		$item->setNamedTag(new CompoundTag("", [
			'Unbreakable'=>new ByteTag('Unbreakable',1),
		]));
		return $item;
	}

    /**
     * @param $name
     * @param null $player
     * @return Armor|Item
     */
    public function createArmor($name, $player=null){
        if(isset($this->KJ[$name]))$conf=$this->KJ[$name]->getAll();else return Item::get(0);
		if($player instanceof Player)$player=strtolower($player->getName());
		$item=new Armor($conf, 1, new CompoundTag('',[
				'Unbreakable'=>new ByteTag('Unbreakable',1),
				'armor'=>new ListTag('armor',[
					new StringTag('',$conf['全员可用']==true?'*':$player),//0 绑定
					new StringTag('',$name),//1 装备名字
					new StringTag('', 0),//2 附加生命值
					new StringTag('', 0),//3 附加护甲
					new StringTag('', 0),//4 附加反伤
					new StringTag('', 0),//5 附加闪避
					new StringTag('', 0),//6 附加速度
					new StringTag('', 0),//7 减少控制百分比
					new StringTag('', ''),//8 药水
					new StringTag('', 1),//9 等级
					new StringTag('', 0),//10 经验
					new StringTag('', 1),//11 星级
					new StringTag('', 0),//12 反伤
					new StringTag('', 0),//13 坚韧
					new StringTag('', 0),//14 幸运
				]),
				'display' => new CompoundTag("display", [
					"Name" => new StringTag("Name", $conf['名字'])
				])
			]));
		return $item;
    }
	public function existsLTItem($type,$name){
		switch($type){
			case '近战':
				return isset($this->JZ[$name]);
			break;
			case '远程':
				return isset($this->YC[$name]);
			break;
			case '通用':
				return isset($this->TY[$name]);
			break;
			case '盔甲':
				return isset($this->KJ[$name]);
			break;
			case '材料':
				return isset($this->CL[$name]);
			break;
			case '魔法':
				return isset($this->MANA[$name]);
			break;
			case '饰品':
				return isset($this->SQ[$name]);
			break;
			default:
			return false;
		}
	}
	public function createLTItem($type,$name,$player=''){
		switch($type){
			case '近战':
			case '远程':
			case '通用':
				return $this->createWeapon($type, $name, $player);
			break;
			case '盔甲':
				return $this->createArmor($name, $player);
			break;
			case '材料':
				return $this->createMaterial($name);
			break;
			case '魔法':
				return $this->createMana($name, $player);
			break;
			case '饰品':
				return $this->createOrnaments($name);
			break;
			default:
			return false;
		}
	}
	public static function createSendToInvItem($ids){
		$ids=explode(':',$ids);
		$item=Item::get($ids[0],$ids[1]??0,1);
		$item->setNamedTag(new CompoundTag('',[
			'Unbreakable'=>new ByteTag('Unbreakable',1),
			'isSendToInv'=>new ByteTag('isSendToInv',1),
		]));
		return $item;
	}
	public static function createAutoSellItem($ids){
		$ids=explode(':',$ids);
		$item=Item::get($ids[0],$ids[1]??0,1);
		$item->setNamedTag(new CompoundTag('',[
			'Unbreakable'=>new ByteTag('Unbreakable',1),
			'isAutoSellItem'=>new ByteTag('isAutoSellItem',1),
		]));
		return $item;
	}
	public static function isAutoSellItem($item){
		if(isset($item->getNamedTag()['isAutoSellItem']))
			return true;
		else 
			return false;
	}
	public static function isSendToInvItem($item){
		if(isset($item->getNamedTag()['isSendToInv']))
			return true;
		else 
			return false;
	}
	public function getTip($player){
		$hand=$player->getItemInHand();
		if($hand instanceof LTItem){
			if($hand instanceof Weapon){
				if(!$hand->canUse($player))return '你无权使用这把武器！';
				switch($hand->getSkillName()){
					case '凝冻雪球':
					case '风暴之力':
					case '致盲':
					case '时空穿梭':
						if(isset(Cooling::$weapon[$player->getName()][$hand->getLTName()]) and Cooling::$weapon[$player->getName()][$hand->getLTName()]>time())
							$mess=$hand->getHandMessage($player)."\n".'§c技能剩余时间:'.ceil(Cooling::$weapon[$player->getName()][$hand->getLTName()]-time()).'秒';
						else
							$mess=$hand->getHandMessage($player)."\n".'§d长按可释放技能';
						return $mess;
					break;
				}
				return $hand->getHandMessage($player);
			}
			return $hand->getHandMessage($player);
		}
	}
}