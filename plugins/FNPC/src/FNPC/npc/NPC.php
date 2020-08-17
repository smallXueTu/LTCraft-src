<?php
namespace FNPC\npc;

/*
Copyright © 2016 FENGberd All right reserved.
GitHub Project:
https://github.com/fengberd/FNPC
*/

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\entity\Entity;

use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\utils\TextFormat;

use pocketmine\math\Vector3;

use FNPC\SystemProvider;
use FNPC\Utils\Converter;

class NPC extends \pocketmine\level\Location
{
	public static $pool=array();
	public static $config=null;
	public static $packet_hash='';
	private static $unknownTypeData=array();
	
	public static function reloadUnknownNPC()
	{
		foreach(NPC::$unknownTypeData as $key=>$val)
		{
			if(($class=\FNPC\Main::getRegisteredNpcClass($val['type']))!==false)
			{
				$npc=new $class($key);
				$npc->reload();
				$npc->save();
				unset(NPC::$unknownTypeData[$key]);
			}
			unset($key,$val,$npc,$class);
		}
	}
	
	public static function init()
	{
		@mkdir(SystemProvider::$plugin->getDataFolder());
		@mkdir(SystemProvider::$plugin->getDataFolder().'skins/');
		@mkdir(SystemProvider::$plugin->getDataFolder().'skins/cache/');
		NPC::$pool=array();
		NPC::$config=new Config(SystemProvider::$plugin->getDataFolder().'NPC.yml',Config::YAML,array());
		SystemProvider::debug('static,config_loaded');
		foreach(NPC::$config->getAll() as $key=>$val)
		{
			if(($class=\FNPC\Main::getRegisteredNpcClass($val['type']))!==false)
			{
				$npc=new $class($key);
				$npc->reload();
				$npc->initPK();
				$npc->save();
			}
			else
			{
				NPC::$unknownTypeData[$key]=$val;
			}
			unset($key,$val,$npc,$class);
		}
	}
	
	public static function spawnAllTo($player)
	{
		foreach(NPC::$pool as $npc)
		{
			$npc->spawnTo($player);
			unset($npc);
		}
		unset($player,$level);
	}
	
	public static function packetReceive($player,$packet)
	{
		if($packet->action == \pocketmine\network\protocol\InteractPacket::ACTION_LEFT_CLICK)
		{
			if(NPC::$packet_hash!=spl_object_hash($packet))
			{
				NPC::$packet_hash=spl_object_hash($packet);
				foreach(NPC::$pool as $npc)
				{
					if($packet->target==$npc->getEID())
					{
						if($npc->needPay() && !$npc->checkPay($player,true,$player))
						{
							break;
						}
						$npc->onTouch($player);
					}
					unset($npc);
				}
			}
		}
		unset($player,$packet);
	}
	
	public static function tick()
	{
		foreach(NPC::$pool as $npc)
		{
			$npc->onTick();
			unset($npc);
		}
	}
	
	public static function playerMove($player)
	{
		foreach(NPC::$pool as $npc)
		{
			if($npc->distance($player)<=10)
			{
				$npc->look($player);
			}
			unset($npc);
		}
		unset($player);
	}
	
/*************************/
	
	public $nametag='';
	public $clientID=0;
	protected $eid=0;
	public $handItem;
	public $skinpath='';
	public $skin='';
	public $skinName='';
	protected $nid='';
	public $level='';
	public $levelO;
	public $uuid='';
	public $pay=0;
	protected $linkEid=0;
	protected $cape=false;
	public $extra='';
	public $spawnPK = null;
	public $armorPK = null;
	public $skinPK = null;
	public $capePK = null;
	
	public function __construct($nid,$nametag='',$x=0,$y=0,$z=0,$handItem=false,$clientID=false)
	{
		$this->nid=$nid;
		SystemProvider::debug('NPC:'.$this->nid.',construct_start');
		$this->uuid=\pocketmine\utils\UUID::fromRandom();
		$this->x=$x;
		$this->y=$y;
		$this->z=$z;
		$this->nametag=$nametag;
		$this->cape=NPC::$config->get($this->getId())['cape'];
		$this->levelO=Server::getInstance()->getLevelByName(NPC::$config->get($this->getId())['level']);
		if($clientID===false)
		{
			$clientID=mt_rand(1000000,9999999);
		}
		$this->clientID=$clientID;
		$this->eid=Entity::$entityCount++;
		if($handItem===false)
		{
			$handItem=\pocketmine\item\Item::get(0);
		}
		$this->handItem=$handItem;
		if(isset(NPC::$pool[$this->nid]))
		{
			SystemProvider::$plugin->getLogger()->warning('警告:尝试创建ID重复NPC:'.$this->nid.',请检查是否出现逻辑错误');
			NPC::$pool[$this->nid]->close();
		}
		NPC::$pool[$this->nid]=$this;
		SystemProvider::debug('NPC:'.$this->nid.',construct_success');
		unset($nametag,$x,$y,$z,$handItem,$clientID);
	}
	
	public function look($player)
	{
		if(!$player instanceof Player)
		{
			unset($player);
			return false;
		}
		$x=$this->x-$player->x;
		$y=$this->y-$player->y;
		$z=$this->z-$player->z;
		if($x==0 and $z==0){
			$yaw = 0;
			$pitch = $y>0?-90:90;
			if($y==0)$pitch=0;
		}else{
			$yaw=asin($x/sqrt($x*$x+$z*$z))/3.14*180;
			$pitch=round(asin($y/sqrt($x*$x+$z*$z+$y*$y))/3.14*180);
		}
		if($z>0)
		{
			$yaw=-$yaw+180;
		}
		$pk=new \pocketmine\network\protocol\MovePlayerPacket();
		$pk->eid=$this->getEID();
		$pk->x=$this->x;
		$pk->y=$this->y+1.62;
		$pk->z=$this->z;
		$pk->bodyYaw=$yaw;
		$pk->pitch=$pitch;
		$pk->yaw=$yaw;
		$pk->mode=0;
		$player->dataPacket($pk);
		unset($x,$y,$z,$yaw,$pitch,$player,$pk);
		return true;
	}
	
	public function reload()
	{
		if(NPC::$config->exists($this->getId()))
		{
			SystemProvider::debug('NPC:'.$this->nid.',reload_start');
			$cfg=NPC::$config->get($this->getId());
			$this->x=$this->get($cfg,'x');
			$this->y=$this->get($cfg,'y');
			$this->z=$this->get($cfg,'z');
			$this->level=$this->get($cfg,'level');
			$this->yaw=$this->get($cfg,'yaw');
			$this->pitch=$this->get($cfg,'pitch');
			$this->clientID=$this->get($cfg,'clientID');
			$this->nametag=$this->get($cfg,'nametag');
			$this->cape=$this->get($cfg,'cape');
			$this->skinName=$this->get($cfg,'skinName');
			$this->skinName=$this->skinName==''?'Standard_Custom':$this->skinName;
			$this->pay=$this->get($cfg,'pay');
			$this->extra=$this->get($cfg,'extra');
			
			SystemProvider::debug('NPC:'.$this->nid.',reload_item');
			$this->handItem=\pocketmine\item\Item::get($cfg['handItem']['id'],$cfg['handItem']['data']);
			SystemProvider::debug('NPC:'.$this->nid.',reload_skin_start');
			if(is_file(SystemProvider::$plugin->getDataFolder().'skins/'.$this->get($cfg,'skin')))
			{
				$this->skin=Converter::getPngSkin(SystemProvider::$plugin->getDataFolder().'skins/'.$this->get($cfg,'skin'));
				SystemProvider::debug('NPC:'.$this->nid.',reload_skin_converted');
				if($this->skin===false)
				{
					$this->skin='';
				}
				else
				{
					SystemProvider::debug('NPC:'.$this->nid.',reload_skin_success');
					$this->skinpath=$this->get($cfg,'skin');
				}
			}
			if($this->levelO!==null){
				$this->levelO->addNPC($this);
			}
			return $cfg;
		}
		return false;
	}
	public function initPK(){
		$cfg=NPC::$config->get($this->getId());
		$this->spawnPK=new \pocketmine\network\protocol\AddPlayerPacket();
		$this->spawnPK->uuid=$this->uuid;
		$this->spawnPK->username=$this->nametag;
		$this->spawnPK->eid=$this->getEID();
		$this->spawnPK->x=$this->x;
		$this->spawnPK->y=$this->y;
		$this->spawnPK->z=$this->z;
		$this->spawnPK->speedX=0;
		$this->spawnPK->speedY=0;
		$this->spawnPK->speedZ=0;
		$this->spawnPK->yaw=$this->yaw;
		$this->spawnPK->pitch=$this->pitch;
		$this->spawnPK->item=$this->handItem;
 
		$flags = 0;
		$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
		$this->spawnPK->metadata = [
		 Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
		 Entity::DATA_AIR => [Entity::DATA_TYPE_SHORT, 400],
		 Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->nametag],
		 Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1],
		 57, 8, [0, 0, 0],
		];
		if($cfg['头']??false!==false or $cfg['胸']??false!==false or $cfg['腿']??false!==false or $cfg['脚']??false!==false){
			$this->armorPK=new \pocketmine\network\protocol\MobArmorEquipmentPacket();
			$this->armorPK->eid = $this->getEID();
			if($cfg['头']??false!==false){
				$ids=explode(':', $this->get($cfg,'头'));
				$item=Item::get($ids[0], $ids[1]);
				if($ids[2]=="true")self::addEnchant($item);
				$this->armorPK->slots[]=$item;
			}else $this->armorPK->slots[] = Item::get(0);
			if($cfg['胸']??false!==false){
				$ids=explode(':', $this->get($cfg,'胸'));
				$item=Item::get($ids[0], $ids[1]);
				if($ids[2]=="true")self::addEnchant($item);
				$this->armorPK->slots[]=$item;
			}else $this->armorPK->slots[] = Item::get(0);
			if($cfg['腿']??false!==false){
				$ids=explode(':', $this->get($cfg,'腿'));
				$item=Item::get($ids[0], $ids[1]);
				if($ids[2]=="true")self::addEnchant($item);
				$this->armorPK->slots[]=$item;
			}else $this->armorPK->slots[] = Item::get(0);
			if($cfg['脚']??false!==false){
				$ids=explode(':', $this->get($cfg,'脚'));
				$item=Item::get($ids[0], $ids[1]);
				if($ids[2]=="true"){
					self::addEnchant($item);
				}
				$this->armorPK->slots[]=$item;
			}else $this->armorPK->slots[] = Item::get(0);
		}
		$this->skinPK=new \pocketmine\network\protocol\PlayerListPacket();
		$this->skinPK->type = \pocketmine\network\protocol\PlayerListPacket::TYPE_ADD;
		$this->skinPK->entries[] = [$this->uuid, $this->getEID(), $this->nametag, $this->skinName, $this->skin];
		$skin = ['Minecon_MineconSteveCape2011', 'Minecon_MineconSteveCape2012', 'Minecon_MineconSteveCape2013', 'Minecon_MineconSteveCape2015', 'Minecon_MineconSteveCape2016',];
		$this->capePK=new \pocketmine\network\protocol\PlayerListPacket();
		$this->capePK->type = \pocketmine\network\protocol\PlayerListPacket::TYPE_ADD;
		$this->capePK->entries[] = [$this->uuid, $this->getEID(), $this->nametag, $skin[mt_rand(0, 4)], $this->skin];
	}
	public static function addEnchant(&$item){
		$item->setNamedTag(new \pocketmine\nbt\tag\CompoundTag('',[
			'ench'=>new \pocketmine\nbt\tag\ListTag('ench', [])
		]));
	}
	
	protected function get($cfg,$name)
	{
		return isset($cfg[$name])?$cfg[$name]:'';
	}
	
	public function setName($name)
	{
		$this->nametag=str_replace('\n',"\n",$name);
		$this->save();
		$this->spawnToAll();
		return true;
	}
	
	public function setPay($pay)
	{
		$this->pay=$pay;
		$this->save();
	}
	
	public function needPay()
	{
		return $this->pay!=0;
	}
	
	public function checkPay($player,$pay=true,Player $realPlayer=null)
	{
		if(!$this->needPay())
		{
			unset($player,$pay,$realPlayer);
			return true;
		}
		if($player instanceof Player)
		{
			$player=$player->getName();
		}
		$player=strtolower($player);
		if(Economy::getMoney($player)>=$this->pay)
		{
			if($pay)
			{
				if($realPlayer instanceof Player)
				{
					$realPlayer->sendMessage('[System] '.TextFormat::GREEN.'您花费了 '.$this->pay.' '.Economy::$moneyName);
				}
				return Economy::takeMoney($player,$this->pay);
			}
			unset($player,$pay,$realPlayer);
			return true;
		}
		if($realPlayer instanceof Player)
		{
			$realPlayer->sendMessage('[System] '.TextFormat::RED.'抱歉 ,您没有足够的'.Economy::$moneyName.'来使用NPC');
		}
		unset($player,$pay,$realPlayer);
		return false;
	}
	
	public function setPNGSkin($path,$useCache=true)
	{
		$this->skin=Converter::getPngSkin(SystemProvider::$plugin->getDataFolder().'skins/'.$path,$useCache);
		if($this->skin===-1)
		{
			$this->skin='';
			return -1;
		}
		else if($this->skin===-2)
		{
			$this->skin='';
			return -2;
		}
		else if($this->skin===-3)
		{
			$this->skin='';
			return -3;
		}
		$this->skinpath=$path;
		$this->save();
		$this->spawnToAll();
		return 0;
	}
	
	public function setHandItem($item)
	{
		$this->handItem=$item;
		$this->save();
		$this->spawnToAll();
		unset($item);
	}
	
	public function close($removeData=true)
	{
		$this->despawnFromAll();
		$this->levelO->removeNPC($this);
		if($removeData)
		{
			NPC::$config->remove($this->getId());
			NPC::$config->save(false);
		}
		unset(NPC::$pool[$this->getId()]);
	}
	
	public function getEID()
	{
		return $this->eid;
	}
	
	public function getSkin()
	{
		return $this->skin;
	}
	
	public function getSkinPath()
	{
		return $this->skinpath;
	}
	
	public function getLevel()
	{
		return $this->level;
	}
	
	public function getId()
	{
		return $this->nid;
	}
	
	public function onTick()
	{
		
	}
	
	public function onTouch($player)
	{
		
	}
	
	public function teleport(Vector3 $pos)
	{
		$this->x=$pos->x;
		$this->y=$pos->y;
		$this->z=$pos->z;
		if($pos instanceof \pocketmine\level\Position)
		{
			$this->level=$pos->getLevel()->getFolderName();
			$this->spawnToAll();
		}
		else
		{
			$this->sendPosition();
		}
	}
	
	public function save(array $extra=array('type'=>'normal'))
	{
		NPC::$config->set($this->getId(),array_merge(array(
			'x'=>$this->x,
			'y'=>$this->y,
			'z'=>$this->z,
			'level'=>$this->level,
			'yaw'=>$this->yaw,
			'pitch'=>$this->pitch,
			'skin'=>$this->skinpath,
			'nametag'=>$this->nametag,
			'clientID'=>$this->clientID,
			'skinName'=>$this->skinName,
			'pay'=>$this->pay,
			'cape'=>$this->cape,
			'头'=>NPC::$config->get($this->getId())['头']??false,
			'胸'=>NPC::$config->get($this->getId())['胸']??false,
			'腿'=>NPC::$config->get($this->getId())['腿']??false,
			'脚'=>NPC::$config->get($this->getId())['脚']??false,
			'extra'=>$this->extra,
			'handItem'=>array(
				'id'=>$this->handItem->getId(),
				'data'=>$this->handItem->getDamage())),$extra));
		NPC::$config->save(false);
	}
	
	public function despawnFromAll()
	{
		if(($level=SystemProvider::$server->getLevelByName($this->level)) instanceof \pocketmine\level\Level)
		{
			$players=$level->getPlayers();
		}
		else
		{
			$players=SystemProvider::$plugin->getServer()->getOnlinePlayers();
		}
		foreach($players as $p)
		{
			$this->despawnFrom($p);
			unset($p);
		}
		unset($level,$players);
	}
	
	public function despawnFrom($player)
	{

		if($this->nid=='info' and false){
			$pk = new \pocketmine\network\protocol\RemoveEntityPacket();
			$pk->eid = $this->linkEid;
			$player->dataPacket($pk);
		}
		$pk = new \pocketmine\network\protocol\RemoveEntityPacket();
		$pk->eid = $this->getEID();
		$player->dataPacket($pk);
		Server::getInstance()->removePlayerListData($this->uuid,array($player));
		unset($player->NPCs[$this->getEID()]);
		unset($player,$pk);

	}
	
	public function spawnToAll()
	{
		if(($level=SystemProvider::$server->getLevelByName($this->level)) instanceof \pocketmine\level\Level)
		{
			$players=$level->getChunkPlayers($this->x >> 4, $this->z >> 4);
			foreach($players as $p)
			{
				$this->spawnTo($p);
				unset($p);
			}
		}
		unset($player,$level);
	}
	
	public function spawnTo($player)
	{
		if($player->getLevel()->getName()===$this->level){
			$player->dataPacket($this->spawnPK);
			if($this->nid=='info' and false){
				$pk = new \pocketmine\network\protocol\AddEntityPacket();
				$pk->eid = $this->linkEid = Entity::$entityCount++;
				$pk->speedX = 0;
				$pk->speedY = 0;
				$pk->speedZ = 0;
				$pk->pitch = 0;
				$pk->yaw = 0;
				$pk->x = 714.5;
				$pk->y = 4.5;
				$pk->z = 20.5;
				$pk->type = \pocketmine\entity\Item::NETWORK_ID;
				$flags = 0;
				$flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
				$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
				$pk->metadata = [
					Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
					Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, ""],
					Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1]
				];
				$link = new \pocketmine\network\protocol\SetEntityLinkPacket ();
				$link->from = $this->linkEid;
				$link->to = $this->getEID();
				$link->type = true;
				$player->dataPacket($pk);
				$player->dataPacket($link);
			}
			if($this->skinPK!==null){
				$player->dataPacket($this->skinPK);
			}
			if($this->capePK!==null){
				$player->dataPacket($this->capePK);
			}
			if($this->armorPK!==null){
				$player->dataPacket($this->armorPK);
			}
			$player->NPCs[$this->getEID()] = $this;
			return true;
		}else{
			return false;
		}
	}
	
	public function sendPosition()
	{
		$pk=new \pocketmine\network\protocol\MovePlayerPacket();
		$pk->eid=$this->getEID();
		$pk->x=$this->x;
		$pk->y=$this->y+1.62;
		$pk->z=$this->z;
		$pk->bodyYaw=$this->yaw;
		$pk->pitch=$this->pitch;
		$pk->yaw=$this->yaw;
		$pk->mode=0;
		foreach(SystemProvider::$plugin->getServer()->getOnlinePlayers() as $p)
		{
			$p->dataPacket($pk);
			unset($p);
		}
		unset($pk);
	}
}
?>
