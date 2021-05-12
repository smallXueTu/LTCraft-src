<?php
namespace LTMenu;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\scheduler\CallbackTask;
use pocketmine\network\protocol\UpdateBlockPacket;

use LTMenu\Menus\Menu;
use LTMenu\Inventorys\SellInventory;
use LTItem\LTItem;
use LTMenu\Inventorys\MenuInventory;
use LTItem\Main as LTMain;
use LTCraft\Main as LTCraft;
use LTItem\SpecialItems\Material;
class Main extends PluginBase{
	public $opens=[];
	public $openEnderChests=[];
	private $menus=[];
	/** @var Exchange */
	private $Exchange;
	public static $eventListener=null;
	public static $instance=null;

    /**
     * @return Main
     */
	public static function getInstance(){
		return self::$instance;
	}
	public function onDisable(){
		// $this->Data->save(false);
		$this->Exchange->save();
	}

    /**
     * @return Exchange
     */
	public function getExchange(){
		return $this->Exchange;
	}
	public function onEnable(){
		self::$instance=$this;
		self::$eventListener=new EventListener($this);
		$this->getServer()->getPluginManager()->registerEvents(self::$eventListener,$this);
		Menu::init();
		MenuInventory::init();
		$this->initMenus();
		$this->Exchange = new Exchange(new Config($this->getDataFolder().'Exchange.yml',Config::YAML,array()));
	}
	public function getMenu($name){
		return $this->menus[$name]??null;
	}
	public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
	    /** @var Player $sender */
		if(!isset($args[0]))return true;
		switch($args[0]){
			case 'reload':
				$this->initMenus();
				$sender->sendMessage('§e重载完成！');
			break;
			case 'get':
				$inv=$sender->getInventory();
				if($inv->isNoFull()){
					$inv->addItem(LTMain::getInstance()->createMaterial('§a点击地面打开菜单'));
					$sender->sendMessage('§l§e收到菜单快捷方式！');
				}else $sender->sendMessage('§l§e无法接收菜单快捷物品,请检查背包！');
			break;
			case 'shelves':
				LTCraft::getInstance()->status[$sender->getName()]='shelves';
				return $sender->sendMessage('§d你已经进入交易状态，请手拿你要出售的物品。'.PHP_EOL .'在聊天栏输入这个物品需要条件！'.PHP_EOL .'§3如果你想要橙币，直接输入橙币价格即可'.PHP_EOL .'如果你想要其他物品,输入§e类型:名字:数量'.PHP_EOL .'§3例:§e材料:武器精髓:10 §3多个用&区分 例:§e材料:武器精髓:10&材料:盔甲精髓:10&5000'.PHP_EOL .'§3以上则需要武器精髓×10 和盔甲精髓×10 和5000橙币'.PHP_EOL .'§a当前可用类型:[材料 饰品]'.PHP_EOL .'退出输入:exit');
			// case 'test':
				// $conf=new Config($this->getDataFolder().'Menus'.'/MaterialShop.yml',Config::YAML,array());
				// foreach($conf->get('items') as $info){
					// foreach($info['multilevel'] as $minfo){
						// $dropItem=explode(':', $minfo['give_item']);
						// if($dropItem[0]=='材料')
							// $item = LTItem::getInstance()->createMaterial($dropItem[1]);
						// elseif(in_array($dropItem[0], ['近战', '远程', '通用']))
							// $item = LTItem::getInstance()->createWeapon($dropItem[0], $dropItem[1]);
						// elseif($dropItem[0]=='盔甲')
							// $item = LTItem::getInstance()->createArmor($dropItem[1]);
						// if(!($item instanceof LTItem)){
							// echo $minfo['give_item'].'不存在！'.PHP_EOL;
						// }
					// }
				// }
			// break;
			default:
				if(isset($this->menus[$args[0]])){
					$open=$this->openMenu($sender,$args[0]);
					if($open and isset($args[1])){
						$item=$open->getInventory()->getItem($args[1]);
						if($item->getMultilevel()!==false){
							$open->openMultiLevel($item->getMultilevel());
						}
					}
				}
			break;
		}
	}
	public function getOpen($player){
		if($player instanceof Player){
			$player=$player->getName();
		}
		return $this->opens[$player]??null;
	}
	public function openMenu(Player $player,$name){
		if($player->getGamemode()!=0)return false;
//		if (isset($this->opens[$player->getName()]))return false;
		$block=$player->getLevel()->getBlock($player);
		if(!($block instanceof Air)){
			$block=$player->getLevel()->getBlock(new Vector3($player->x,$player->y+1,$player->z));
			if(!($block instanceof Air))$block=$this->getAir($player);
		}
		if(!($block instanceof Block))return $player->sendMessage('§c附近找不到空位置！');
		$menu=$this->menus[$name];
		$this->opens[$player->getName()]=new Open($menu, $player, $block);
		return $this->opens[$player->getName()];
	}
	public function initMenus(){
		$this->menus=[];
		$path=$this->getDataFolder().'Menus';
		foreach(scandir($path) as $afile){
		 $fname=explode('.',$afile);
		  if($afile=='.' or $afile=='..' or is_dir($path.'/'.$afile) or end($fname)!=='yml')continue;
			$name = explode('.', $afile);
			unset($name[count($name)-1]);
			$name = implode('.', $name);
			$conf=new Config($path.'/'.$afile,Config::YAML,array());
			$this->menus[$name]=Menu::getMenu($conf->get('items', []), $conf);
		}
	}
	public function getMoney($item, $priceMenu){
		if($item instanceof Material){
			if($item->getLTName()!=='§a点击地面打开菜单'){
				return 100*$item->getCount();
			}else return 0;
		}
		foreach($priceMenu->getItems() as $i){
			if($i->equals($item,false,false)){
				return $i->getEvent()['money']*$item->getCount();
			}
		}
		return 0;
	}
	public function openSet(Player $player,$name){//TODO 打开设置界面
		
	}
	public function getMenuName($menu){
		$conf=$this->menuInfo->get($menu);
		return $conf['name'];
	}
	public function getAir(Player $player){
		$x=(int)$player->getX()+3;
		$y=(int)$player->getY()+3;
		$z=(int)$player->getZ()+3;
		$level=$player->getLevel();
		for($fx=$x - 6;$fx<=$x;$fx++){
			for($fy=$y - 6;$fy<=$y;$fy++){
				for($fz=$z - 6;$fz<=$z;$fz++){
					$block=$level->getBlock(new Vector3($fx,$fy,$fz));
					if($block instanceof Air)return $block;
				}
			}
		}
		return false;
	}
}