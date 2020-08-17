<?php
namespace LTMenu\Inventorys;

use LTMenu\MenuItem;
use MyPlot\MyPlot;

class PlotInventory extends DynamicInventory{
	public function load(){
		$count=count(MyPlot::getInstance()->getProvider()->getPlotsByOwner($this->owner->getName()));
		$index=$this->page*25;
		$ii=0;
		$this->setContents($this->menu->getItem($this->page));
		for($i=1;$i<=$count;$i++){
			if($index++>$this->page*25+25)break;
			$item=MenuItem::getMenuItem(2);
			$item->setEvent(array(
				'commands'=>['%playerp h '.$i],
				'teleport'=>null,
				'cost_item'=>null,
				'give_item'=>null,
				'multilevel'=>false,
				'price'=>0
			));
			$this->setItem($ii++,$item->setCustomName('§l§d我的第'.$i .'块地皮'));
		}
	}
	public function getMaxPage(){
		$count=count(MyPlot::getInstance()->getProvider()->getPlotsByOwner($this->owner->getName()));
		return ceil($count/25);
	}
}