<?php
namespace LTMenu\Menus;

use LTMenu\MenuItem;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\nbt\NBT;
use pocketmine\tile\Tile;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;


class Menu{
	const MENU=0;//基础
	const GARBAGE=1;//垃圾
	const LNLAY=2;//镶嵌
	const SELL=3;//出售
	const NULLC=4;//null
	const EXCHANGE=5;//交易所
	protected $items=[];
	protected $function=[];
	protected $name;
	protected $MenuName;
	protected $type;
	protected $config;
	public static $menus = [];
	public static function init(){
		self::$menus[self::MENU]=self::class;
		self::$menus[self::GARBAGE]=GarbageMenu::class;
		self::$menus[self::LNLAY]=InlayMenu::class;
		self::$menus[self::SELL]=SellMenu::class;
		self::$menus[self::NULLC]=NullMenu::class;
		self::$menus[self::EXCHANGE]=ExchangeMenu::class;
	}
	public static function getSpawnCompound(Position $pos, $name){
		$c = new CompoundTag("", [
			new StringTag("id", Tile::CHEST),
			new IntTag("x", (int) $pos->x),
			new IntTag("y", (int) $pos->y),
			new IntTag("z", (int) $pos->z)
		]);
		if($name!==null){
			$c->CustomName = new StringTag("CustomName", $name);
		}
		$ben = new NBT(NBT::LITTLE_ENDIAN);
		$ben->setData($c);
		return $ben->write(true);
	}
	public static function getMenu($items, $conf){
		$class=self::$menus[$conf->get('MenuType')]??self::$menus[0];
		return new $class($items, $conf);
	}
	public function __construct($items, $conf){
		foreach($items as $item){
			$this->items[]=MenuItem::getMenuItem($item, $conf);
		}
		$this->name=$conf->get('name');
		$this->MenuName=$conf->get('MenuName');
		$this->config=$conf;
		$this->type=$conf->get('InventoryType');
		$this->initFunction();
	}
	public function getName(){
		return $this->name;
	}
	public function getMenuName(){
		return $this->MenuName;
	}
	public function getIType(){
		return $this->type;
	}
	public function getMaxPage(){
		return ceil(count($this->items)/25);
	}
	public function getUMaxCount(){
		return 27-count($this->functions);
	}
	public function initFunction(){
		$item=Item::get(404,0,1);
		$item->setCustomName('§l§o§d上一页');
		$this->functions[]=$item;
		$item=Item::get(356,0,1);
		$item->setCustomName('§l§o§d下一页');
		$this->functions[]=$item;
	}
	public function getSlot($slot){
		return $this->items[$slot];
	}
	public function getItem($page=0){
		$items=[];
		$min=$page*$this->getUMaxCount();
		$max=$min+$this->getUMaxCount();
		for($i=0;$i<$max;$i++){
			if($i>=$min){
				$items[]=$this->items[$i]??Item::get(0);
			}
		}
		
		return array_merge($items, $this->functions);
	}
	public function getItems(){
		return $this->items;
	}
}