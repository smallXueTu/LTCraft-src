<?php
namespace LTMenu\Inventorys;

use LTMenu\MenuItem;

class HomeInventory extends DynamicInventory{
	public function load(){
		$homes=$this->owner->getHomes();
		$this->setContents($this->menu->getItem($this->page));
		$index=$this->page*25;
		$i=0;
		$c=0;
		// for($ii=0;$ii<100;$ii++){
			// $homes[]=$ii;
		// }
		foreach(array_keys($homes) as $homeName){
		// foreach($homes as $homeName){
			if($c++<$index)continue;
			if($index++>$this->page*25+25)break;
			$item=MenuItem::getMenuItem(355);
			$item->setEvent(array(
				'commands'=>['%playerhome '.$homeName],
				'teleport'=>null,'cost_item'=>null,
				'give_item'=>null,
				'multilevel'=>false,
				'price'=>0
			));
			$this->setItem($i++,$item->setCustomName('§l§d家:'.$homeName));
			if($i==25)break;
		}
	}
	public function getMaxPage(){
		$homes=$this->owner->getHomes();
		return ceil(count($homes)/25);
	}
}