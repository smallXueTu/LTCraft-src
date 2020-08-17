<?php
namespace LTMenu;

use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\Player;
use LTItem\Main as LTItem;

class Exchange{
	private $Config;
	private $Goods = [];
	private $NextID;
	public function __construct(Config $Config){
		$this->Config=$Config;
		$this->NextID=$Config->get('NextID', 0);
		$this->load();
	}
	public function load(){
		foreach($this->Config->getAll() as $id=>$info){
			if($id==='NextID'){
				continue;
			}
			$this->Goods[$id]=MenuItem::getMenuItem($info);
		}
	}
	public function getGoods(){
		return $this->Goods;
	}
	public function removeGood($id){
		unset($this->Goods[$id]);
	}
	public function addGood(Item $item, Player $player, $info){
		$conf=[
			'item'=>[
				$item->getID(),
				$item->getDamage(),
				$item->getCount(),
			],
			'nametag'=>'§a卖家§3'.$player->getName().PHP_EOL .'§e§l>>§a售卖物品:§3'.Item::getItemString($item, '×'),
			'time'=>time()+60*60*24,
			'player'=>$player->getName(),
			'ID'=>$this->NextID,
			'price'=>0,
			'give_item'=>Item::getItemString($item),
			'cost_item'=>null,
		];
		$needItems=explode('&', $info);
		foreach($needItems as $info){
			if(is_numeric($info)){
				if($info<=0)return $info.'格式不正确。';
				$conf['price']+=$info;
			}else{
				$needs=explode(':', $info);
				if(in_array($needs[0], ['材料', '饰品']) and count($needs)==3){
					if(!is_numeric($needs[2]))return $info.'格式不正确。';
					if($needs[2]<=0)return $info.'格式不正确。';
					if(LTItem::getInstance()->existsLTItem($needs[0], $needs[1])){
						if($conf['cost_item']==null){
							$conf['cost_item']=$info;
							$conf['nametag'].="\n§e§l>>需要:".$needs[0].':'.$needs[1].'×'.$needs[2];
						}else{
							$conf['cost_item'].='&'.$info;
							$conf['nametag'].="\n§e§l>>需要:".$needs[0].':'.$needs[1].'×'.$needs[2];
						}
					}else{
						return $info.'不存在。';
					}
				}else{
					return $info.'格式不正确。';
				}
			}
		}
		$this->Goods[$this->NextID++]=MenuItem::getMenuItem($conf);
		return true;
	}
	public function save(){
		$this->Config->setAll([]);
		$this->Config->set('NextID', $this->NextID);
		foreach($this->Goods as $ID=>$item){
			$this->Config->set($ID, array_merge(['item'=>[$item->getID(), $item->getDamage(), $item->getCount()]], $item->getEvent()));
		}
		$this->Config->save(false);
	}
}