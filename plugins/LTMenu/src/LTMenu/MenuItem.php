<?php
namespace LTMenu;

use pocketmine\Item\Item;
use LTMenu\Menus\Menu;

class MenuItem extends Item{
	private $event;
	private $Multilevel;
	public function setMultilevel(Menu $Multilevel){
		$this->Multilevel=$Multilevel;
	}
	public function getMultilevel(){
		if($this->event['multilevel']===false)return false;
		return $this->Multilevel;
	}
	
	public static function getMenuItem($conf, $config=null){
		if(is_array($conf)){
			$item=new MenuItem($conf['item'][0], $conf['item'][1], $conf['item'][2]);
			$price=$conf['price']==0?'': ' §d价格'.$conf['price'];
			if($conf['price']!==0){
				if($conf['price']<0)
					$price="\n§l§d>>".'可获得'.abs((int)$conf['price']).'橙币';
				else
					$price=$conf['price']==0?'':"\n§l§d>>".' 需要'.$conf['price'].'橙币';
			}else $price='';
			$item->setCustomName('§a'.str_replace('\n',"\n",$conf['nametag']).$price);
			unset($conf['item']);
			$item->setEvent($conf);
			if($conf['multilevel']??false!==false and $config!==null){
				$item->setMultilevel(Menu::getMenu($conf['multilevel'], $config));
			}
		}else{
			$info=explode(':', $conf);
			$item=new MenuItem($info[0], $info[1]??0, $info[2]??1);
		}
		return $item;
	}
	public function equalsMenuItem(MenuItem $item){
		return $item->getID()==$this->getID() and $item->getDamage()==$this->getDamage() and $item->getCount()==$this->getCount() and $item->getEvent()==$this->getEvent();
	}
	public function getEvent(){
		return $this->event;
	}
	public function setEvent(array $event){
		$this->event=$event;
	}
}