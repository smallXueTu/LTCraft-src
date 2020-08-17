<?php
namespace LTMenu;

use LTMenu\Menus\Menu;
use LTMenu\Inventorys\MenuInventory;
use pocketmine\inventory\Inventory;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\network\protocol\BlockEntityDataPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\scheduler\CallbackTask;
use LTItem\Main as LTItem;
use pocketmine\entity\Human;

class Open extends Position{
	private $player;
	private $inventory;
	private $multiLevels=[];
	private $multiMenus=[];
	private $menu;
	/** @var Position $position */
	private $position;
	private $disable = false;
	private $plugin;
	private $closed = false;
	private $died = false;
	private $lastClick;
	private $teleport = null;
	private $closeDataPacket = null;
	private $closeCommand=[];
	private $id;
	private $status = 0;//0正常 1错误 2背包错误

    /**
     * Open constructor.
     * @param Menu $menu
     * @param Player $player
     * @param Position $position
     */
    public function __construct(Menu $menu, Player $player, Position $position){
		$this->position=$position;
		$this->player=$player;
		$this->menu=$menu;
		$this->plugin=Main::getInstance();
		$this->open();
	}
	public function setClosePacket($pk){
		$this->closeDataPacket=$pk;
	}
	public function getClosePacket(){
		return $this->closeDataPacket;
	}
	public function setTeleport(Vector3 $pos){
		$this->teleport=$pos;
	}
	/*
		判断玩家时候符合条件来显示一个功能
	*/
	public static function isEligible(MenuItem $item, Human $player){
		$conditions = $item->getEvent()['unlock']??false;
		if($conditions == false)return true;
		$conditions = explode(':', $conditions);
		switch($conditions[0]){
			case 'GTo':
				return $player->getGTo()>=$conditions[1];
			break;
			case 'plot':
				return $player->getAStatusIsDone('购买地皮');
			break;
			case 'home':
				return $player->getAStatusIsDone('设置家');
			break;
			default:
				return true;
			break;
		}
	}
	public function setDie(){
		$this->died=1;
	}
	public function getDieProgress(){
		if($this->died===false){
			return 0;
		}
		return ++$this->died;
	}
	public function getDie(){
		return $this->died!==false;
	}
	public function getTeleport(): Vector3{
		return $this->teleport;
	}
	public function addMultiLevels($inv){
		$this->multiLevels[]=$inv;
	}
	public function getCloseCommands(){
		return $this->closeCommand;
	}
	public function setCloseCommands($closeCommand){
		$this->closeCommand=$closeCommand;
	}
	public function getMultiLevelCount(){
		return count($this->multiLevels);
	}
	public function openMultiLevel(Menu $menu){
		$this->addMultiLevels($this->getInventory());
		$this->menu=$menu;
		$this->inventory=MenuInventory::openAtInventory($this->player, $this->getMenu()->getIType(), $this->getMenu(), $this->position);
		$this->inventory->StaticOpen($this->player);
		$this->player->setWindow($this->id, $this->inventory);
		$this->inventory->sendContents($this->player);
	}
	public function closeAll(){
		if(!$this->closed){
			$this->closed = true;
			foreach($this->multiLevels as $inv){
				$inv->StaticClose($this->player);
			}
			$this->multiLevels=[];
		}
	}
	public function runCloseEvents(){
		if($this->teleport instanceof Vector3){
			$this->player->teleport($this->teleport);
		}
		if(count($this->getCloseCommands())>0){
			foreach($this->getCloseCommands() as $command){
				$this->getPlugin()->getServer()->dispatchCommand($this->player,$command);
			}
		}
	}
	public function closeMultiLevel(){
		if($this->getMultiLevelCount()<=0)return;
		$inv=$this->pushMultiLevels();
		$this->menu=$inv->getMenu();
		$this->inventory->StaticClose($this->player);
		$this->inventory=MenuInventory::openAtInventory($this->player, $this->getMenu()->getIType(), $this->getMenu(), $this->position);
		$this->inventory->setContents($inv->getContents());
		$this->inventory->StaticOpen($this->player);
		$this->player->setWindow($this->id, $this->inventory);
		$this->inventory->sendContents($this->player);
	}
	public function pushMultiLevels(){
		return array_pop($this->multiLevels);
	}
	public function getMenu(){
		return $this->menu;
	}
	public function setMenu(Menu $menu){
		$this->menu=$menu;
	}
	public function getLastClick(){
		return $this->lastClick;
	}
	public function setLastClick($s){
		$this->lastClick=$s;;
	}
	public function getPlugin(){
		return $this->plugin;
	}
	public function getInventory(){
		return $this->inventory;
	}
	public function getPlayer(){
		return $this->player;
	}
	public function getPage(){
		return $this->inventory->getPage();
	}
	public function getId(){
		return $this->id;
	}
	public function event($event){
		$this->inventory->event($event, $this);
	}
	public function setDisable(){
		$this->disable=true;
	}
	public function setEnable(){
		$this->disable=false;
	}
	public function isDisable(){
		return $this->disable;
	}
	public function recovery(){
		$this->status=0;
		$this->setEnable();
		$this->inventory->sendContents($this->player);
	}
	public function error(){
		$this->setDisable();
		$this->status=1;
		$this->inventory->sendContents($this->player);
		$this->getPlugin()->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"recovery"],[]), 20);
	}
	public function undoError($contents=[]){
		$this->inventory->setContents($contents);
		$this->error();
	}
	public function getStatus(){
		return $this->status;
	}
	public function invError(){
		$this->setDisable();
		$this->status=2;
		$this->inventory->sendContents($this->player);
		$this->getPlugin()->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"recovery"],[]), 20);
	}
	public static function CheackItems(Player $player, Array $items, $inv=null){
		foreach($items as $item){
			if(is_array($item) and $item[0]=='祝福点')continue;
			if(!self::getNumber($player,$item, $inv))return false;
		}
		return true;
	}
	public function close(){
		$this->inventory->onClose($this->player);
	}
	public static function getNumber(Player $player, $item, $inventory=null){
        /** @var Inventory $inventory */
        /** @var Player $player */
		if($inventory==null)$inventory=$player->getInventory();
		$s=0;
		if(!($item instanceof Item)){
			 $needCount=PHP_INT_MAX;
			if($item[0]=='更多要求')$needCount=$item[5];
			elseif(isset($item[2])) $needCount=$item[2];
			foreach($inventory->getContents() as $i){
				if(LTItem::getInstance()->isEquals($i,$item,$player)){
					$s+=$i->getCount();
					if($s>=$needCount){
						return true;
					}
				}
			}
			return $needCount==PHP_INT_MAX?$s:false;
		}else{
			foreach($inventory->getContents() as $i)
				if($i->getId()==$item->getId() AND $i->getDamage()===$item->getDamage() and !LTItem::getInstance()->isThisItem($i))
					$s+=$i->getCount();
					if($s>=$item->getCount())return true;
			return false;
		}
	}
	public static function canOpen(Player $player, $MenuName){
		
	}

    /**
     * @param $p
     * @param $items
     * @param null $inventory
     * @return bool
     */
    public static function removeItems($p, $items, $inventory=null){
	    foreach ($items as $item){
            if (self::removeItem($p, $item, $inventory)!=true){
                return false;
            }
        }
	    return true;
    }
    /**
     * @param $p
     * @param $item
     * @param null $inventory
     * @return bool
     */
	public static function removeItem($p,$item, $inventory=null){
	    /** @var Inventory $inventory */
	    /** @var Player $p */
		if($inventory==null)$inventory=$p->getInventory();
		$s=0;
		if(!($item instanceof Item)){
			if($item[0]=='更多要求')$needCount=$item[5];
			else $needCount=$item[2];
			foreach($inventory->getContents() as $k=>$i){
					if(LTItem::getInstance()->isEquals($i,$item,$p)){
					if($i->getCount()===$needCount-$s){
						$inventory->clear($k);
						return true;
					}elseif($i->getCount()>$needCount-$s){
						$i->setCount($i->getCount()-($needCount-$s));
						$inventory->setItem($k,$i);
						return true;
					}else{
						$s+=$i->getCount();
						$inventory->clear($k);
						if($s===$needCount)return true;
					}
				}
			}
		}else{
			foreach($inventory->getContents() as $k=>$i){
				if($i->getId()===$item->getId() AND $i->getDamage()===$item->getDamage() and !LTItem::getInstance()->isThisItem($i)){
					if($i->getCount()===$item->getCount()-$s){
						$inventory->clear($k);
						return true;
					}elseif($i->getCount()>$item->getCount()-$s){
						$i->setCount($i->getCount()-($item->getCount()-$s));
						$inventory->setItem($k,$i);
						return true;
					}else{
						$s+=$i->getCount();
						$inventory->clear($k);
						if($s===$item->getCount())return true;
					}
				}
			}
		}
		return false;
	}
	public function open(){
		$pk = new UpdateBlockPacket();
		$pk->x = (int)$this->position->x;
		$pk->z = (int)$this->position->z;
		$pk->y = (int)$this->position->y;
		$pk->blockId = 54;
		$pk->blockData = 0;
		$hpk=clone $pk;
		$this->player->dataPacket($pk);
		$be=new BlockEntityDataPacket();
		$be->x=$this->position->x;
		$be->y=$this->position->y;
		$be->z=$this->position->z;
		$be->namedtag=Menu::getSpawnCompound($this->position, $this->menu->getMenuName());
		$this->player->dataPacket($be);
		$hpk->blockId = 0;
		$hpk->blockData = 0;
		$this->inventory=MenuInventory::openAtInventory($this->player, $this->menu->getIType(), $this->menu, $this->position);
		$this->id=$this->player->addWindow($this->inventory);
		$this->setClosePacket($hpk);
	}
}