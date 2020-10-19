<?php
namespace LTMenu\Inventorys;

use pocketmine\inventory\ContainerInventory;
use pocketmine\inventory\InventoryType;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\FakeBlockMenu;
use pocketmine\entity\Human;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\Player;
use LTMenu\Menus\Menu;
use LTMenu\Main;
use LTMenu\Open;
use LTMenu\MenuItem;
use pocketmine\network\protocol\BlockEventPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use LTItem\Main as LTItem;
use LTItem\SpecialItems\Weapon;
use LTItem\SpecialItems\Armor;
use LTItem\SpecialItems\Material;
use onebone\economyapi\EconomyAPI;
use pocketmine\math\Vector3;
use pocketmine\NBT\tag\{CompoundTag, StringTag};

class MenuInventory extends ContainerInventory{
    /** @var Player $owner */
	protected $owner;
	const MENU = 0;//普通菜单
	const CHEST = 1;//菜单箱子
	const GRABAGE = 2;//垃圾菜单
	const HOME = 3;//家菜单
	const INLAY = 4;//镶嵌菜单
	const PLOT = 5;//地皮菜单
	const SELL = 6;//出售菜单
	const ENDER = 7;//末影箱
	const ORNAMENTS = 8;//饰品
	const EXCHANGE = 9;//交易所
	const VALIDATION = 10;//在线状态验证
	public static $types = [];
	protected $page=0;
	protected $menu;
	public static function init(){
		self::$types[self::MENU]=self::class;
		self::$types[self::CHEST]=ChestInventory::class;
		self::$types[self::GRABAGE]=GarbageInventory::class;
		self::$types[self::HOME]=HomeInventory::class;
		self::$types[self::INLAY]=InlayInventory::class;
		self::$types[self::PLOT]=PlotInventory::class;
		self::$types[self::SELL]=SellInventory::class;
		self::$types[self::ENDER]=EnderInventory::class;
		self::$types[self::ORNAMENTS]=OrnamentsInventory::class;
		self::$types[self::EXCHANGE]=ExchangeInventory::class;
		self::$types[self::VALIDATION]=ValidationInventory::class;
	}
	public static function openAtInventory($player, $MenuType, Menu $menu, Position $pos){
		return new self::$types[$MenuType]($player, $menu, $pos);
	}
	public function getMenu(){
		return $this->menu;
	}
	public function __construct(Human $owner, Menu $menu, Position $pos){
		$this->owner = $owner;
		$this->menu = $menu;
		parent::__construct(new FakeBlockMenu($this, $pos), InventoryType::get(InventoryType::MENU));
		$contens = $this->menu->getItem($this->page);
		// var_dump($contens);
		foreach($contens as $index=>$item){
			if($item instanceof MenuItem and Open::isEligible($item, $owner)==false){
				unset($contens[$index]);
			}
		}
		$this->setContents($contens);
	}
	public function getMaxPage(){
		return $this->menu->getMaxPage();
	}
	public function getOwner(){
		return $this->owner;
	}
	public function setCancelled($event){
		$this->sendContents($this->owner);
		$this->owner->getInventory()->sendContents($this->owner);
		$event->setCancelled();
	}
	public function event($event, $open){
		$this->setCancelled($event);
		if($open->isDisable())return;

		$packet=$event->getPacket();
		if($this->getItem($packet->slot)->getId() == 0)return;
		if(!$this->owner->getInventory()->isNoFull())return $open->invError();
		if($open->getLastClick()!==$packet->slot)return $open->setLastClick($packet->slot);
		if($packet->slot==25){
			$open->setLastClick(null);
			if($this->page>0){
				$this->jumpPage(false);
			}else{
				$open->closeMultiLevel();
			}
			return;
		}elseif($packet->slot==26){
			if($this->page<$this->getMaxPage()-1){
				$this->jumpPage(true);
				$open->setLastClick(null);
			}
			return;
		}
		$item=$this->getItem($packet->slot);
		$events=$item->getEvent();
		if($item->getMultilevel()!==false and $item->getMultilevel() instanceof Menu){
			$open->setLastClick(null);
			$open->openMultiLevel($item->getMultilevel());
			return;
		}
		$contents=$this->owner->getInventory()->getContents();
		if($events['cost_item']!=null){
			if($this->owner->getGamemode()!==0)return $open->error();
			$CostItems=explode('&',$events['cost_item']);
			$items=[];
			foreach($CostItems as $CostItem){
				$i=explode(':',$CostItem);
				if(in_array($i[0],['近战','远程','通用','材料','盔甲','魔法'])){
					$item=$i;
				}elseif($i[0]=='祝福点'){
					$item=$i;
				}else{
					if(isset($i[3]))
						$item=Item::get($i[0],$i[1],$i[2])->setCustomName($i[3]);
					else
						$item=Item::get($i[0],$i[1],$i[2]);
				}
				$items[]=$item;
			}
			if(!Open::CheackItems($this->owner, $items))return $open->error();
			foreach($items as $item){
				if(is_array($item) and $item[0]=='祝福点'){
					$count = LTItem::getInstance()->R->get(strtolower($this->owner->getName()), 0);
					if($count<$item[1]){
						$this->owner->getInventory()->setContents($contents);
						return $open->error();
					}else{
						LTItem::getInstance()->R->set(strtolower($this->owner->getName()), LTItem::getInstance()->R->get(strtolower($this->owner->getName()), 0)-$item[1]);
					}
					continue;
				}
				if(Open::removeItem($this->owner,$item)!==true){
					$this->owner->getInventory()->setContents($contents);
					return $open->error();
				}
			}
		}
		if((int)$events['price']!=0){
			if($events['price']<0){
				EconomyAPI::getInstance()->addMoney($this->owner,abs($events['price']), '菜单获得');
			}else{
				$money=EconomyAPI::getInstance()->myMoney($this->owner);
				if($money>=$events['price']){
					EconomyAPI::getInstance()->reduceMoney($this->owner,abs($events['price']), '菜单消耗');
				}else{
					if($events['cost_item']!=null)$this->owner->getInventory()->setContents($contents);
					return $open->error();
				}
			}
		}
		if($events['give_item']!=null){
			if($this->owner->getGamemode()!==0){
				if((int)$events['price']>0)EconomyAPI::getInstance()->addMoney($this->owner,(int)$events['price'], '菜单获得');
				return $open->error();
			}else{
				$GiveItems=explode('&',$events['give_item']);
				$items=[];
				foreach($GiveItems as $GiveItem){
					$i=explode(':',$GiveItem);
					if(in_array($i[0],['近战','远程','通用','材料','盔甲', '饰品', '魔法', '奖励箱'])){
						if($i[0]=='材料')
							$item=LTItem::getInstance()->createMaterial($i[1]);
						elseif($i[0]=='盔甲')
							$item=LTItem::getInstance()->createArmor($i[1],strtolower($this->owner->getName()));
						elseif($i[0]=='魔法')
							$item=LTItem::getInstance()->createMana($i[1], $this->owner);
						elseif($i[0]=='饰品')
							$item=LTItem::getInstance()->createOrnaments($i[1]);
						else
							$item=LTItem::getInstance()->createWeapon($i[0],$i[1],strtolower($this->owner->getName()));
						if($item===false){
							$this->owner->getInventory()->setContents($contents);
							return $open->error();
						}
						$item->setCount((int)$i[2]);
					}else{
						if(isset($i[3]))
							$item=Item::get($i[0],$i[1]??0,$i[2]??1)->setCustomName($i[3]);
						else
							$item=Item::get($i[0],$i[1]??0,$i[2]??1);
						$item = $item->setUnableConvert(true);
					}
					$items[]=$item;
				}
				foreach($items as $item){
					if(!$this->owner->getInventory()->canAddItem($item)){
						$this->owner->getInventory()->setContents($contents);
						if((int)$events['price']>0)EconomyAPI::getInstance()->addMoney($this->owner,(int)$events['price'], '菜单退款');
						return $open->invError();
					}
					$this->owner->getInventory()->addItem($item);
					$this->owner->getTask()->action('兑换完成',$item);
				}
			}
		}
		if(is_array($events['commands']) and count($events['commands'])!==0){
			foreach($events['commands'] as $command){
				$commands=[];
				if(substr($command,0,7)==='%server'){
					$open->getPlugin()->getServer()->dispatchCommand($open->getPlugin()->getServer()->consoleSender,str_replace('%p',$this->owner->getName(),substr($command,0-(strlen($command)-7))));
				}elseif(substr($command,0,7)==='%player'){
					if(substr($command,0-(strlen($command)-7), 1)=='w' or substr($command,0-(strlen($command)-7), 4)=='warp')$this->owner->getTask()->action('菜单传送');
					$commands[]=substr($command,0-(strlen($command)-7));
				}elseif(substr($command,0,5)==='%open'){
					$needOpen=substr($command,0-(strlen($command)-5));
					if(($menu=$open->getPlugin()->getMenu($needOpen))!==null){
						$open->setLastClick(null);
						$open->openMultiLevel($menu);
						return;
					}else{
						return $open->error();
					}
				}elseif(substr($command,0,9)==='%testopen'){
					$needOpen=substr($command,0-(strlen($command)-9));
					if(($menu=$open->getPlugin()->getMenu($needOpen))!==null and $this->owner->canOpen($needOpen)){
						$open->setLastClick(null);
						$open->openMultiLevel($menu);
						return;
					}else{
						return $open->error();
					}
				}
				if(count($commands)>0){
					$open->setCloseCommands($commands);
					$open->close();
					return;
				}
			}
		}
		if($events['teleport']!=null){
			$v=explode(':',$events['teleport']);
			$level=$open->getPlugin()->getServer()->getLevelByName($v[3]);
			if($level instanceof Level)
				$teleport=new Position($v[0],$v[1],$v[2],$level);
			else
				$teleport=new Vector3($v[0],$v[1],$v[2]);
			$open->setTeleport($teleport);
			$this->owner->getTask()->action('菜单传送');
			$open->close();
		}
		return;
	}

	public function getHolder(){
		return $this->holder;
	}

	public function jumpPage($action){
		if($action)
			$this->page++;
		else
			$this->page--;
		$this->setContents($this->menu->getItem($this->page));
	}
	public function getPage(){
		return $this->page;
	}
	public function setPage($page){
		if($page>$this->menu->getMaxPage()-1){
			$page=$this->menu->getMaxPage()-1;
		}
		$this->page = $page;
		$this->setContents($this->menu->getItem($this->page));
	}
	public function sendContents($target){
		if($target instanceof Player){
			$target = [$target];
		}

		$pk = new ContainerSetContentPacket();
		$pk->slots = [];
		/** @var $open Open */
		if(($open=Main::getInstance()->getOpen($this->owner->getName()))===null or $open->getStatus()===0){
			for($i = 0; $i < $this->getSize(); ++$i){
				$pk->slots[$i] = $this->getItem($i);
			}
		}else{
			switch($open->getStatus()){
				case 1:
					$item=Item::get(35,14);
                    if ($open->getMessage() != '')$item->setCustomName($open->getMessage());
					for($i = 0; $i < $this->getSize(); ++$i){
						$pk->slots[$i] = $item;
					}
				break;
				case 2:
					$item=Item::get(35,4);
                    if ($open->getMessage() != '')$item->setCustomName($open->getMessage());
					for($i = 0; $i < $this->getSize(); ++$i){
						$pk->slots[$i] = $item;
					}
				break;
			}
		}
		foreach($target as $player){
			if(($id = $player->getWindowId($this)) === -1 or $player->spawned !== true){
				$this->close($player);
				continue;
			}
			$pk->windowid = $id;
			$pk->targetEid = $player->getId();
			$player->dataPacket($pk);
		}
	}
	
	public function openAt(Position $pos){
		$this->getHolder()->setComponents($pos->x, $pos->y, $pos->z);
		$this->getHolder()->setLevel($pos->getLevel());
		$this->owner->addWindow($this);
	}
	public function StaticOpen(Player $who){
		BaseInventory::onOpen($who);
	}
	public function StaticClose(Player $who){
		BaseInventory::onClose($who);
		$this->setContents([]);
	}
	public function close(Player $who){
		if(isset($this->viewers[spl_object_hash($who)])){
			if(($open=Main::getInstance()->getOpen($this->owner->getName()))!==null){
				$open->closeAll();
			}
			$this->onClose($who);
		}
		$this->setContents([]);
	}
	public function onOpen(Player $who){
		parent::onOpen($who);
		$pk = new BlockEventPacket();
		$pk->x = $this->getHolder()->getX();
		$pk->y = $this->getHolder()->getY();
		$pk->z = $this->getHolder()->getZ();
		$pk->case1 = 1;
		$pk->case2 = 2;
		$this->owner->dataPacket($pk);
	}

	public function onClose(Player $who){
		$pk = new BlockEventPacket();
		$pk->x = $this->getHolder()->getX();
		$pk->y = $this->getHolder()->getY();
		$pk->z = $this->getHolder()->getZ();
		$pk->case1 = 1;
		$pk->case2 = 0;
		$this->owner->dataPacket($pk);
		parent::onClose($who);
		$this->setContents([]);
	}
}