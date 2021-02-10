<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

namespace pocketmine\entity;

use LTItem\Mana\Mana;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerExperienceChangeEvent;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\FloatingInventory;
use pocketmine\inventory\EnderChestInventory;
use pocketmine\inventory\MenuInventory;
use pocketmine\inventory\OrnamentsInventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\SimpleTransactionQueue;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item as ItemItem;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\math\Math;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\Player;
use pocketmine\utils\UUID;
use LTPopup\Popup;
use pocketmine\level\sound\ExpPickupSound;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\network\protocol\SetEntityLinkPacket;
use LTCraft\Chair;
use LTCraft\Main as LTCraft;

class Human extends Creature implements ProjectileSource, InventoryHolder {

    const DATA_PLAYER_FLAG_SLEEP = 1;
    const DATA_PLAYER_FLAG_DEAD = 2; //TODO: CHECK

    const DATA_PLAYER_FLAGS = 27;

    const DATA_PLAYER_BED_POSITION = 29;

    /** @var PlayerInventory */
    protected $inventory;

    /** @var EnderChestInventory */
    protected $enderChestInventory;

    /** @var MenuInventory */
    protected $menuInventory;

    /** @var OrnamentsInventory */
    protected $ornamentsInventory;

    /** @var FloatingInventory */
    protected $floatingInventory;

    /** @var SimpleTransactionQueue */
    protected $transactionQueue = null;

    /** @var Entity */
    protected $linkedEntity = null;

    /** @var Entity */
    protected $rideEntity = null;

    /** @var UUID */
    protected $uuid;
    protected $rawUUID;

    public $width = 0.6;
    public $length = 0.6;
    public $height = 1.8;
    public $eyeHeight = 1.62;

    protected $skinId;
    protected $skin;
    /** @var [] */
    protected $Homes = [];
    protected $Pets = [];
    protected $CanOpenMenu = [];

    protected $foodTickTimer = 0;
    protected $Love = '未婚';
    protected $Gender = '未选择';
    protected $MainTask = 0;
    protected $MaxDamage = 0;
    protected $Exp = 0;
    protected $Grade = 0;
    protected $FlyTime = 0;
    protected $Guild = '无公会';
    protected $Role = '未选择';
    protected $Prefix = '无称号';
    protected $ForceTP = 1;
    protected $Cape = 1;
    protected $GTo = 0;
    protected $GeNeAwakening = 0;
    // protected $PVP = 0;
    protected $EnderChestCount = 0;
    public $canFly = false;
    public $isVIP=false;

    /**
     * @return mixed
     */
    public function getSkinData(){
        return $this->skin;
    }

    /**
     * @return mixed
     */
    public function getSkinId(){
        return $this->skinId;
    }

    /**
     * @return UUID|null
     */
    public function getUniqueId(){
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getRawUniqueId(){
        return $this->rawUUID;
    }

    /**
     * @param string $str
     * @param string $skinId
     */
    public function setSkin($str, $skinId){
        $this->skin = $str;
        $this->skinId = $skinId;
    }

    /**
     * @return float
     */
    public function getFood() : float{
        return $this->attributeMap->getAttribute(Attribute::HUNGER)->getValue();
    }

    /**
     * WARNING: This method does not check if full and may throw an exception if out of bounds.
     * Use {@link Human::addFood()} for this purpose
     *
     * @param float $new
     *
     * @throws \InvalidArgumentException
     */
    public function setFood(float $new){
		// var_dump($new);
        $attr = $this->attributeMap->getAttribute(Attribute::HUNGER);
        $old = $attr->getValue();
        $attr->setValue($new);
        // ranges: 18-20 (regen), 7-17 (none), 1-6 (no sprint), 0 (health depletion)
        foreach([17, 6] as $bound){
            if(($old > $bound) !== ($new > $bound)){
                $reset = true;
            }
        }
        if(isset($reset)){
            $this->foodTickTimer = 0;
        }

    }

    /**
     * @return float
     */
    public function getMaxFood() : float{
        return $this->attributeMap->getAttribute(Attribute::HUNGER)->getMaxValue();
    }

    /**
     * @param float $amount
     */
    public function addFood(float $amount){
        $attr = $this->attributeMap->getAttribute(Attribute::HUNGER);
        $amount += $attr->getValue();
        $amount = max(min($amount, $attr->getMaxValue()), $attr->getMinValue());
        $this->setFood($amount);
    }

    /**
     * @return float
     */
    public function getSaturation() : float{
        return $this->attributeMap->getAttribute(Attribute::SATURATION)->getValue();
    }

    /**
     * WARNING: This method does not check if saturated and may throw an exception if out of bounds.
     * Use {@link Human::addSaturation()} for this purpose
     *
     * @param float $saturation
     *
     * @throws \InvalidArgumentException
     */
    public function setSaturation(float $saturation){
        $this->attributeMap->getAttribute(Attribute::SATURATION)->setValue($saturation);
    }

    /**
     * @param float $amount
     */
    public function addSaturation(float $amount){
        $attr = $this->attributeMap->getAttribute(Attribute::SATURATION);
        $attr->setValue($attr->getValue() + $amount, true);
    }

    /**
     * @return float
     */
    public function getExhaustion() : float{
        return $this->attributeMap->getAttribute(Attribute::EXHAUSTION)->getValue();
    }

    /**
     * WARNING: This method does not check if exhausted and does not consume saturation/food.
     * Use {@link Human::exhaust()} for this purpose.
     *
     * @param float $exhaustion
     */
    public function setExhaustion(float $exhaustion){
        $this->attributeMap->getAttribute(Attribute::EXHAUSTION)->setValue($exhaustion);
    }

    public function setXpLevel(int $level) : bool{
        $this->attributeMap->getAttribute(Attribute::EXPERIENCE_LEVEL)->setValue($level);
        return true;
    }
    /**
     * Increases a human's exhaustion level.
     *
     * @param float $amount
     * @param int   $cause
     *
     * @return float the amount of exhaustion level increased
     */
    public function exhaust(float $amount, int $cause = PlayerExhaustEvent::CAUSE_CUSTOM) : float{
        /*
        $this->server->getPluginManager()->callEvent($ev = new PlayerExhaustEvent($this, $amount, $cause));
        if($ev->isCancelled()){
            return 0.0;
        }
        */

        $exhaustion = $this->getExhaustion();
		// var_dump($exhaustion);
        $exhaustion += $amount;
		// var_dump($exhaustion);

        while($exhaustion >= 4.0){
            $exhaustion -= 4.0;

            $saturation = $this->getSaturation();
            if($saturation > 0){
                $saturation = max(0, $saturation - 1.0);
                $this->setSaturation($saturation);
                $this->getTask()->action('消耗饥饿', 1);
            }else{
                $food = $this->getFood();
                if($food > 0){
                    $food--;
                    $this->getTask()->action('消耗饥饿', 1);
					// var_dump($food);
                    $this->setFood($food);
                }
            }
        }
        $this->setExhaustion($exhaustion);

        return $amount;
    }

    public function sendLinkedData($player){
        if($this->linkedEntity instanceof Entity){
            $pk = new SetEntityLinkPacket();
            $pk->from = $this->linkedEntity->getId();
            $pk->to = $this->getId();
            $pk->type = 1;
            $player->dataPacket($pk);
        }elseif($this->linkedEntity instanceof Chair){
            $this->linkedEntity->sendLinkedData($player);
        }
    }
    /**
     * @return Entity
     */
    public function getLinkedEntity(){
        return $this->linkedEntity;
    }


    /**
     * @param Entity $entity
     */
    public function setLinkedEntity($entity){
        $this->linkedEntity = $entity;
    }

    /**
     * @return Entity
     */
    public function getRideEntity(){
        return $this->rideEntity;
    }


    /**
     * @param Entity $entity
     */
    public function setRideEntity($entity){
        $this->rideEntity = $entity;
    }

    public function getEnderChestCount(){
        return $this->EnderChestCount;
    }
    public function setGeNeAwakening($v){
        $this->GeNeAwakening = $v;
    }

    public function getGeNeAwakening(){
        return $this->GeNeAwakening;
    }
    public function setEnderChestCount($v){
        $this->EnderChestCount = $v;
    }
    // public function getPVPStatus(){
    // return $this->PVP;
    // }
    // public function setPVPStatus($v){
    // $this->PVP = $v;
    // }
    public function getMaxDamage(){
        return $this->MaxDamage;
    }
    public function setMaxDamage($MaxDamage){
        $this->MaxDamage = $MaxDamage;
    }

    /**
     * @return int
     */
    public function getGrade(){
        return $this->Grade;
    }

    /**
     * @param $Grade
     */
    public function setGrade($Grade){
        $this->Grade = $Grade;
        $this->setXpLevel($Grade);
    }

    /**
     * @return bool
     */
    public function isVIP(){
        return $this->isVIP;
    }

    /**
     * 这个应该加到Player类..
     * @param int $v
     */
    public function setVIP($v){
        $this->isVIP=$v;
        if($this->getBuff() instanceof \LTItem\Buff){
            $this->getBuff()->addLucky($v*10);
        }
        $this->checkFly();
    }

    /**
     * @return int|null
     */
    public function getAdditionalHealth(){
        return $this->namedtag['AdditionalHealth'];
    }
    public function setAdditionalHealth($v){
        $this->namedtag['AdditionalHealth'] = new ShortTag('AdditionalHealth', $v);
    }
    public function getExitPos(){
        $level = $this->server->getLevelByName($this->namedtag['ExitPos'][3]);
        if(!($level instanceof Level)){
            return $this->server->getDefaultLevel()->getSafeSpawn();
        }
        return new Position($this->namedtag['ExitPos'][0], $this->namedtag['ExitPos'][1], $this->namedtag['ExitPos'][2], $level);
    }
    public function setExitPos($pos){
        $this->namedtag['ExitPos'] = new ListTag("ExitPos", [
            new StringTag(0, $pos->getX()),
            new StringTag(1, $pos->getY()),
            new StringTag(2, $pos->getZ()),
            new StringTag(3, $pos->getLevel()->getName())
        ]);
        $this->namedtag->ExitPos->setTagType(NBT::TAG_String);
    }
    public function getGTo(){
        return $this->GTo;
    }
    public function setGTo($GTo){
        $this->GTo=$GTo;
    }
    public function getFlyTime(){
        return $this->FlyTime;
    }
    public function checkFly(){
        if($this->getFlyTime()>time() or $this->isVIP()!==false)
            $this->canFly=true;
        else
            $this->canFly=false;
        $this->setAllowFlight($this->canFly);
    }
    public function setFlyTime($FlyTime){
        $this->FlyTime = $FlyTime;
        $this->checkFly();
    }
    public function getPrefix(){
        return str_replace("\n", '', str_replace(' ', '', $this->Prefix));
    }
    public function setPrefix($Prefix){
        $this->Prefix = $Prefix;
    }
    public function getCape(){
        return $this->Cape;
    }
    public function setCape($Cape){
        $this->Cape = $Cape;
    }
    public function getForceTP(){
        return $this->ForceTP;
    }
    public function setForceTP($ForceTP){
        $this->ForceTP = $ForceTP;
    }
    /*
    *获取玩家性别 男|女|未选择
    */
    public function getGender(){
        return $this->Gender;
    }
    public function setGender($gender){
        if($gender==='男' or $gender==1)
            $this->Gender = '男';
        elseif($gender==='女' or $gender==0)
            $this->Gender = '女';
        else
            $this->Gender = '未选择';
    }
    public function getLove(){
        return $this->Love;
    }
    public function setLove($Love){
        $this->Love = $Love;
    }
    public function getExp(){
        return $this->Exp;
    }
    public function setExp($exp){
        $this->Exp = $exp;
        $this->setXpProgress(round($exp / \LTGrade\Main::getUpExp($this->getGrade()),2));
        $this->getLevel()->addSound(new ExpPickupSound($this, mt_rand(0, 1000)));
    }
    public function addExp($exp){
        if($this->getGrade()<300){
            $ylevel=$this->getGrade();
             $b=2;
//             if(time() < 1569859200 or time() > 1570118400){
//             $b=2;
//             }else
				 if(time() > 1613044800 and time() < 1613145599)
             $b=3;
             $exp*=$b;
            if($this->getExp()+$exp>=\LTGrade\Main::getUpExp($this->getGrade())){
                $redundantExp=$exp-(\LTGrade\Main::getUpExp($this->getGrade())-$this->getExp());
                $addLevel=1;
                $nextLvel=$this->getGrade();
                while($redundantExp>=\LTGrade\Main::getUpExp(++$nextLvel)){
                    $redundantExp-=\LTGrade\Main::getUpExp($nextLvel-1);
                    $addLevel++;
                    if($nextLvel>300)break;
                }
                $this->addArmorV(((int)(($this->getGrade()+$addLevel)/2))-((int)$this->getGrade()/2));
                $this->setGrade($this->getGrade()+$addLevel);
                $this->setExp($redundantExp);
                // $this->setXpLevel($this->getGrade());
                $i=$ylevel;
                while($i<$this->getGrade()){
                    $this->getTask()->action('升级',++$i);
                }
                if($ylevel<30 and $this->getGrade()>=30){
                    $this->sendMessage('§a§l恭喜你达到30级,§e注意:§c如果你死亡将会扣除你橙币余额的0.01%的橙币！');
                    $this->sendMessage('§l§d生命值已经很高了，如果你觉得遮挡视线可以尝试输入/UI设置 血量格式切换');
                    $this->sendMessage('§l§3恭喜你,你解锁了新地图:创造世界！');
                    LTCraft::PlayerUpdateGradeTo30($this->getName());
                }elseif($ylevel<50 and $this->getGrade()>=50){
                    $this->sendMessage('§l§3恭喜你,你解锁了新地图:PVP！');
                }elseif($ylevel<300 and $this->getGrade()>=300){
                    $this->sendMessage('§l§3恭喜你,达到了最高等级限制！');
                }
                Popup::getInstance()->updateNameTag($this);
                $this->getLevel()->addSound(new EndermanTeleportSound($this));
                $yHealth=$this->getMaxHealth();
                if($this->getRole()==='战士')
                    $this->setMaxHealth($this->getYMaxHealth()+((int)$this->getGrade()*2.5-$ylevel*2.5));
                else
                    $this->setMaxHealth($this->getYMaxHealth()+($this->getGrade()*2-$ylevel*2));
                $this->sendTitle('§a恭喜你升级至'.$this->getGrade().'级!',('§a生命值+'.($this->getMaxHealth()-$yHealth)));
                // $this->getAPI()->update(2);
                $this->setHealth($this->getHealth());
            }else $this->setExp($this->getExp()+$exp);
        }else
            $this->newProgress('世界顶端', '达到300级', 'challenge');
    }
    public function setXpProgress(float $progress) : bool{
        $this->attributeMap->getAttribute(Attribute::EXPERIENCE)->setValue($progress);
        return true;
    }
    public function getMainTask(){
        return $this->MainTask;
    }
    public function setMainTask($task){
        $this->MainTask = $task;
    }
    public function addAStatus($statusName){
        $this->namedtag['AStatus'][$statusName] = new StringTag('', $statusName);
    }
    public function getAStatusIsDone($statusName){//哎呀,又不是团队开发,在乎什么命名呢~
	if(!isset($this->namedtag['AStatus']))return false;
        foreach($this->namedtag['AStatus'] as $index=>$tag){
            if($tag->getValue()==$statusName)return true;
        }
        return false;
    }
    public function removeAStatus($statusName){
        foreach($this->namedtag['AStatus'] as $index=>$tag){
            if($tag->getValue()==$statusName){
                unset($this->namedtag['AStatus'][$index]);
                return true;
            }
        }
        return false;
    }
    public function getGuild(){
        return $this->Guild;
    }
    public function setGuild($guild){
        return $this->Guild = $guild;
    }
    public function setRole($RoleName){
        $this->Role = $RoleName;
    }
    public function getRole(){
        return $this->Role;
    }
    public function addMenu($menu, $time){
        $this->CanOpenMenu[$menu] = $time;
    }
    public function canOpen($menu){
        if(isset($this->CanOpenMenu[$menu])){
            if($this->CanOpenMenu[$menu]!=0 and $this->CanOpenMenu[$menu]<time()){
                unset($this->CanOpenMenu[$menu]);
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }
    }
    public function loadMenus(){
        if(!isset($this->namedtag['CanOpenMenu']) or !($this->namedtag['CanOpenMenu'] instanceof ListTag)){
            $this->CanOpenMenu = [];
            return;
        }
        foreach($this->namedtag['CanOpenMenu'] as $CanOpenMenu){
            $this->CanOpenMenu[$CanOpenMenu->name->getValue()]=$CanOpenMenu->time->getValue();
        }
    }
    public function saveMenus(){
        $this->namedtag->CanOpenMenu = new ListTag('CanOpenMenu', []);
        $this->namedtag->CanOpenMenu->setTagType(NBT::TAG_Compound);
        foreach($this->CanOpenMenu as $name=>$info){
            $this->namedtag['CanOpenMenu'][$name] = new CompoundTag('', [
                'name'=>new StringTag('name', $name),
                'time'=>new LongTag('time', $info),
            ]);
        }
    }
    public function loadPets(){
        if(!isset($this->namedtag['Pets'])){
            $this->Pets = [];
            return;
        }
        foreach($this->namedtag['Pets'] as $petC){
            $this->Pets[preg_replace('#§.#','',strtolower($petC->name->getValue()))]=[
                'name'=>$petC->petName->getValue(),
                'type'=>$petC->type->getValue(),
                'skin'=>$petC->skin->getValue(),
                'hunger'=>$petC->hunger->getValue()
            ];
        }
    }
    public function savePets(){
        $this->namedtag->Pets = new ListTag('Pets', []);
        $this->namedtag->Pets->setTagType(NBT::TAG_Compound);
        foreach($this->Pets as $name=>$petC){
            $this->namedtag['Pets'][$name] = new CompoundTag('', [
                'name' => new StringTag('name', $name),
                'petName' => new StringTag('petName', $petC['name']),
                'type' => new StringTag('type', $petC['type']),
                'skin' => new StringTag('skin', $petC['skin']??''),
                'hunger' => new IntTag('hunger', $petC['hunger'])
            ]);
        }
    }
    public function getPets(){
        return $this->Pets;
    }
    public function setPet($name, $arr){
        $this->Pets[$name]=$arr;
    }
    public function setAllPet($arr){
        $this->Pets=$arr;
    }
    public function removePet($name){
        if(!isset($this->Pets[$name]))return false;
        unset($this->Pets[$name]);
    }
    public function getPet($name){
        return $this->Pets[$name]??false;
    }
    /**
     * @return PlayerInventory
     */
    public function getInventory(){
        return $this->inventory;
    }

    /**
     * @return EnderChestInventory
     */
    public function getMenuInventory(){
        return $this->menuInventory;
    }

    /**
     * @return OrnamentsInventory
     */
    public function getOrnamentsInventory(){
        return $this->ornamentsInventory;
    }

    /**
     * @return EnderChestInventory
     */
    public function getEnderChestInventory(){
        return $this->enderChestInventory;
    }

    /**
     * @return FloatingInventory
     */
    public function getFloatingInventory(){
        return $this->floatingInventory;
    }

    /**
     * @return SimpleTransactionQueue
     */
    public function getTransactionQueue(){
        //Is creating the transaction queue ondemand a good idea? I think only if it's destroyed afterwards. hmm...
        if($this->transactionQueue === null){
            //Potential for crashes here if a plugin attempts to use this, say for an NPC plugin or something...
            $this->transactionQueue = new SimpleTransactionQueue($this);
        }
        return $this->transactionQueue;
    }

    protected function initEntity(){
        try{
            $this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, false, self::DATA_TYPE_BYTE);
            $this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [0, 0, 0], false);
            $this->loadHomes();
            $this->loadPets();
            $this->loadMenus();

            parent::initEntity();

            $inventoryContents = ($this->namedtag->Inventory ?? null);
            $this->inventory = new PlayerInventory($this, null);
            $this->inventory->setSize($this->namedtag['InventorySize']??36);
            $this->inventory->setInv($inventoryContents);
            $this->enderChestInventory = new EnderChestInventory($this, ($this->namedtag->EnderChestInventory ?? null));
            $this->menuInventory = new MenuInventory($this, ($this->namedtag->MenuInventory ?? null));
            $this->ornamentsInventory = new OrnamentsInventory($this, ($this->namedtag->OrnamentsInventory ?? null));

            //Virtual inventory for desktop GUI crafting and anti-cheat transaction processing
            $this->floatingInventory = new FloatingInventory($this);

            if($this instanceof Player){
                $this->addWindow($this->inventory, 0);
            }else{
                if(isset($this->namedtag->NameTag)){
                    $this->setNameTag($this->namedtag['NameTag']);
                }

                if(isset($this->namedtag->Skin) and $this->namedtag->Skin instanceof CompoundTag){
                    $this->setSkin($this->namedtag->Skin['Data'], $this->namedtag->Skin['Name']);
                }

                $this->uuid = UUID::fromData($this->getId(), $this->getSkinData(), $this->getNameTag());
            }

            if(!isset($this->namedtag->foodLevel) or !($this->namedtag->foodLevel instanceof IntTag)){
                $this->namedtag->foodLevel = new IntTag('foodLevel', $this->getFood());
            }else{
                $this->setFood($this->namedtag['foodLevel']);
            }

            if(!isset($this->namedtag->Role) or !($this->namedtag->Role instanceof StringTag))
                $this->namedtag->Role = new StringTag('Role', '未选择');
            else
                $this->setRole($this->namedtag['Role']);
            if(!isset($this->namedtag->Guild) or !($this->namedtag->Guild instanceof StringTag))
                $this->namedtag->Guild = new StringTag('Guild', '无公会');
            else
                $this->setGuild($this->namedtag['Guild']);
            if(!isset($this->namedtag->GeNeAwakening) or !($this->namedtag->GeNeAwakening instanceof ShortTag))
                $this->namedtag->GeNeAwakening = new StringTag('GeNeAwakening', 0);
            else
                $this->setGeNeAwakening($this->namedtag['GeNeAwakening']);
            if(!isset($this->namedtag->ExitPos) or !($this->namedtag->ExitPos instanceof ListTag) or $this->namedtag->ExitPos->getTagType()!==NBT::TAG_String){
                $pos = $this->server->getDefaultLevel()->getSafeSpawn();
                $this->namedtag->ExitPos = new ListTag("ExitPos", [
                    new StringTag(0, $pos->getX()),
                    new StringTag(1, $pos->getY()),
                    new StringTag(2, $pos->getZ()),
                    new StringTag(3, $pos->getLevel()->getName())
                ]);
                $this->namedtag->ExitPos->setTagType(NBT::TAG_String);
            }
            if(!isset($this->namedtag->Grade) or !($this->namedtag->Grade instanceof ShortTag))
                $this->namedtag->Grade = new ShortTag('Grade', 0);
            else{
                $this->setGrade($this->namedtag['Grade']);
                $this->addArmorV((int)$this->namedtag['Grade']/2);
            }
            if(!isset($this->namedtag->Exp) or !($this->namedtag->Exp instanceof ShortTag))
                $this->namedtag->Exp = new ShortTag('Exp', 0);
            else
                $this->setExp($this->namedtag['Exp']);
            if(!isset($this->namedtag->MainTask) or !($this->namedtag->MainTask instanceof ShortTag))
                $this->namedtag->MainTask = new ShortTag('MainTask', 0);
            else
                $this->setMainTask($this->namedtag['MainTask']);
            if(!isset($this->namedtag->Gender) or !($this->namedtag->Gender instanceof ByteTag))
                $this->namedtag->Gender = new ByteTag('Gender', 2);
            else
                $this->setGender($this->namedtag['Gender']);
            if(!isset($this->namedtag->ForceTP) or !($this->namedtag->ForceTP instanceof ByteTag))
                $this->namedtag->ForceTP = new ByteTag('ForceTP', 1);
            else
                $this->setForceTP($this->namedtag['ForceTP']);
            if(!isset($this->namedtag->EnderChestCount) or !($this->namedtag->EnderChestCount instanceof LongTag))
                $this->namedtag->EnderChestCount = new ByteTag('EnderChestCount', 1);
            else
                $this->setEnderChestCount($this->namedtag['EnderChestCount']);
            if(!isset($this->namedtag->Cape) or !($this->namedtag->Cape instanceof ByteTag))
                $this->namedtag->Cape = new ByteTag('Cape', 1);
            else
                $this->setCape($this->namedtag['Cape']);
            // if(!isset($this->namedtag->PVP) or !($this->namedtag->PVP instanceof ByteTag))
            // $this->namedtag->PVP = new ByteTag('PVP', 0);
            // else
            // $this->setPVPStatus($this->namedtag['PVP']);
            if(!isset($this->namedtag->GTo) or !($this->namedtag->GTo instanceof ShortTag))
                $this->namedtag->GTo = new ShortTag('GTo', 0);
            else
                $this->setGTo($this->namedtag['GTo']);
            if(!isset($this->namedtag->Prefix) or !($this->namedtag->Prefix instanceof StringTag))
                $this->namedtag->Prefix = new StringTag('Prefix', '无称号');
            else
                $this->setPrefix($this->namedtag['Prefix']);
            if(!isset($this->namedtag->Love) or !($this->namedtag->Love instanceof StringTag))
                $this->namedtag->Love = new StringTag('Love', '未婚');
            else{
                if($this->namedtag['Love']=='已离婚'){
                    $this->sendMessage('§c你的伴侣已经和你离婚了！',true);
                    $this->setLove('未婚');
                }else $this->setLove($this->namedtag['Love']);
            }
            if(!isset($this->namedtag->FlyTime) or !($this->namedtag->FlyTime instanceof LongTag))
                $this->namedtag->FlyTime = new LongTag('FlyTime', 0);
            else
                $this->setFlyTime(0);
                // $this->setFlyTime($this->namedtag['FlyTime']);
            if(!isset($this->namedtag->MaxDamage) or !($this->namedtag->MaxDamage instanceof LongTag))
                $this->namedtag->MaxDamage = new LongTag('MaxDamage', 0);
            else
                $this->setMaxDamage($this->namedtag['MaxDamage']);
            if(!isset($this->namedtag->AStatus) or !($this->namedtag->AStatus instanceof ListTag) or $this->namedtag->AStatus->getTagType()!==NBT::TAG_String){
                $this->namedtag->AStatus = new ListTag('AStatus', []);
                $this->namedtag->AStatus->setTagType(NBT::TAG_String);
            }
            /* TODO
            if(!isset($this->namedtag->OtherTasks) or !($this->namedtag->OtherTasks instanceof ListTag))
                $this->namedtag->OtherTasks = new ListTag('OtherTasks', []);
            if(!isset($this->namedtag->Reward) or !($this->namedtag->Reward instanceof ListTag))
                $this->namedtag->Reward = new ListTag('Reward', []);
            */

            if(!isset($this->namedtag->foodSaturationLevel) or !($this->namedtag->foodSaturationLevel instanceof IntTag)){
                $this->namedtag->foodSaturationLevel = new FloatTag('foodSaturationLevel', $this->getSaturation());
            }else{
                $this->setSaturation($this->namedtag['foodSaturationLevel']);
            }
        }catch(\Throwable $e){
            echo $e->getMessage().PHP_EOL;
        }
    }

    /**
     * @return int
     */
    public function getAbsorption() : int{
        return $this->attributeMap->getAttribute(Attribute::ABSORPTION)->getValue();
    }

    /**
     * @param int $absorption
     */
    public function setAbsorption(int $absorption){
        $this->attributeMap->getAttribute(Attribute::ABSORPTION)->setValue($absorption);
    }

    protected function addAttributes(){
        parent::addAttributes();

        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::SATURATION));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::EXHAUSTION));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::HUNGER));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::EXPERIENCE_LEVEL));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::EXPERIENCE));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::HEALTH));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::MOVEMENT_SPEED));
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::ABSORPTION));
    }

    /**
     * @param int $tickDiff
     * @param int $EnchantL
     *
     * @return bool
     */
    public function entityBaseTick($tickDiff = 1, $EnchantL = 0){
        if($this->getInventory() instanceof PlayerInventory){
            $EnchantL = $this->getInventory()->getHelmet()->getEnchantmentLevel(Enchantment::TYPE_WATER_BREATHING);
        }
        $hasUpdate = parent::entityBaseTick($tickDiff, $EnchantL);

        if($this->isAlive()){
            $food = $this->getFood();
			// var_dump($food);
            if($food >= 15){
                $this->foodTickTimer++;
                if($this->foodTickTimer >= 40 and $this->getHealth() < $this->getMaxHealth()){
                    $this->heal(1, new EntityRegainHealthEvent($this, 1, EntityRegainHealthEvent::CAUSE_SATURATION));
                    $this->exhaust(3.0, PlayerExhaustEvent::CAUSE_HEALTH_REGEN);
                    $this->foodTickTimer = 0;
                }
            }elseif($food <= 2){
                $this->foodTickTimer++;
				// var_dump( $this->foodTickTimer);
                if($this->foodTickTimer >= 80){
                    $this->attack(ceil($this->getMaxHealth()*0.1), new EntityDamageEvent($this, EntityDamageEvent::CAUSE_STARVATION, $this->getMaxHealth()*0.1, true));
                    $this->foodTickTimer = 0;
                }
            }
            if($food <= 6){
                if($this->isSprinting()){
                    $this->setSprinting(false);
                }
            }
        }

        return $hasUpdate;
    }

    /**
     * @return string
     */
    public function getName(){
        return $this->getNameTag();
    }

    /**
     * @return array
     */
    public function getDrops(){
        $drops = [];
        if($this->inventory !== null){
            foreach($this->inventory->getContents() as $item){
                $drops[] = $item;
            }
        }

        return $drops;
    }
    public function loadHomes(){
        foreach($this->server->getLevels() as $level){
            $levelHomeList=$level->getProvider()->getPlayerHomes($this);
            foreach($levelHomeList as $home){
                $this->addHome($home->name->getValue(),new Position($home->x->getValue(),$home->y->getValue(),$home->z->getValue(),$level));
            }
        }
    }
    public function loadLevelHomes(Level $level){
        $levelHomeList=$level->getProvider()->getPlayerHomes($this);
        foreach($this->Homes as $name=>$pos){
            if($pos->level->isClosed() and $pos->level->getName()==$level->getName()){
                $this->delHome($name);
            }
        }
        foreach($levelHomeList as $home){
            $this->addHome($home->name->getValue(),new Position($home->x->getValue(),$home->y->getValue(),$home->z->getValue(),$level));
        }
    }
    public function addHome($name,$pos){
        if(!isset($this->Homes[$name])){
            $this->Homes[$name]=$pos;
            return true;
        }
        return false;
    }
    public function delHome($name){
        if(isset($this->Homes[$name])){
            unset($this->Homes[$name]);
            return true;
        }
        return false;
    }
    public function getHome($name){
        if(isset($this->Homes[$name])){
            return $this->Homes[$name];
        }
        return false;
    }
    public function getHomes(){
        return $this->Homes;
    }
    public function levelCloseTrigger(Level $level){
        $list=new ListTag(strtolower($this->username), []);
        $update=false;
        foreach($this->Homes as $name=>$pos){
            if($pos->getLevel() instanceof Level and !$pos->getLevel()->isClosed() and $pos->getLevel()->getName()===$level->getName()){
                $list[$name] = new CompoundTag('', [
                    'x' => new FloatTag('x', $pos->x),
                    'y' => new FloatTag('y', $pos->y),
                    'z' => new FloatTag('z', $pos->z),
                    'name' => new StringTag('name', $name)
                ]);
                $update=true;
            }
        }
        if($update)$level->getProvider()->setPlayerHomes($this, $list);
    }
    public function saveHomes(){
        foreach($this->server->getLevels() as $level){
            $list=new ListTag(strtolower($this->username), []);
            $update=false;
            foreach($this->Homes as $name=>$pos){
                if($pos->getLevel() instanceof Level and !$pos->getLevel()->isClosed() and $pos->getLevel()->getName()===$level->getName()){
                    $list[$name] = new CompoundTag('', [
                        'x' => new FloatTag('x', $pos->x),
                        'y' => new FloatTag('y', $pos->y),
                        'z' => new FloatTag('z', $pos->z),
                        'name' => new StringTag('name', $name)
                    ]);
                    $update=true;
                }
            }
            if($update)$level->getProvider()->setPlayerHomes($this, $list);
        }
    }
    public function saveNBT(){
        Entity::saveNBT();
        $this->saveHomes();
        $this->savePets();
        $this->saveMenus();
        $this->namedtag->InventorySize=new IntTag('InventorySize', $this->getInventory()->getSize());
        $this->namedtag->Inventory = new ListTag('Inventory', []);
        // $this->namedtag->InventorySize=$this->Inventory->getSize();
        $this->namedtag->Inventory->setTagType(NBT::TAG_Compound);
        if($this->inventory !== null){

            //Hotbar
            for($slot = 0; $slot < $this->inventory->getHotbarSize(); ++$slot){
                $inventorySlotIndex = $this->inventory->getHotbarSlotIndex($slot);
                $item = $this->inventory->getItem($inventorySlotIndex);
                $tag = $item->nbtSerialize($slot);
                $tag->TrueSlot = new ByteTag('TrueSlot', $inventorySlotIndex);
                $this->namedtag->Inventory[$slot] = $tag;
            }

            //Normal inventory
            $slotCount = $this->inventory->getSize() + $this->inventory->getHotbarSize();
            for($slot = $this->inventory->getHotbarSize(); $slot < $slotCount; ++$slot){
                $item = $this->inventory->getItem($slot - $this->inventory->getHotbarSize());
                //As NBT, real inventory slots are slots 9-44, NOT 0-35
                $this->namedtag->Inventory[$slot] = $item->nbtSerialize($slot);
            }

            //Armour
            for($slot = 100; $slot < 104; ++$slot){
                $item = $this->inventory->getItem($this->inventory->getSize() + $slot - 100);
                if($item instanceof ItemItem and $item->getId() !== ItemItem::AIR){
                    $this->namedtag->Inventory[$slot] = $item->nbtSerialize($slot);
                }
            }
        }

        $this->namedtag->EnderChestInventory = new ListTag('EnderChestInventory', []);
        $this->namedtag->EnderChestInventory->setTagType(NBT::TAG_Compound);
        if($this->enderChestInventory !== null){
            for($slot = 0; $slot < $this->enderChestInventory->getSize(); $slot++){
                if(($item = $this->enderChestInventory->getItem($slot)) instanceof ItemItem){
                    $this->namedtag->EnderChestInventory[$slot] = $item->nbtSerialize($slot);
                }
            }
        }

        $this->namedtag->MenuInventory = new ListTag('MenuInventory', []);
        $this->namedtag->MenuInventory->setTagType(NBT::TAG_Compound);
        if($this->menuInventory !== null){
            for($slot = 0; $slot < $this->menuInventory->getSize(); $slot++){
                if(($item = $this->menuInventory->getItem($slot)) instanceof ItemItem){
                    $this->namedtag->MenuInventory[$slot] = $item->nbtSerialize($slot);
                }
            }
        }

        $this->namedtag->OrnamentsInventory = new ListTag('OrnamentsInventory', []);
        $this->namedtag->OrnamentsInventory->setTagType(NBT::TAG_Compound);
        if($this->ornamentsInventory !== null){
            for($slot = 0; $slot < $this->ornamentsInventory->getSize(); $slot++){
                if(($item = $this->ornamentsInventory->getItem($slot)) instanceof ItemItem){
                    $this->namedtag->OrnamentsInventory[$slot] = $item->nbtSerialize($slot);
                }
            }
        }
        // var_dump($this->namedtag->MenuInventory);
        $this->namedtag->Role = new StringTag('Role', $this->getRole());
        $this->namedtag->Love = new StringTag('Love', $this->getLove());
        $this->namedtag->Prefix = new StringTag('Prefix', $this->getPrefix());
        $this->namedtag->Guild = new StringTag('Guild', $this->getGuild());
        $this->namedtag->GeNeAwakening = new ShortTag('GeNeAwakening', $this->getGeNeAwakening());
        $this->namedtag->Grade = new ShortTag('Grade', (int)$this->getGrade());
        $this->namedtag->Exp = new ShortTag('Exp', (int)$this->getExp());
        $this->namedtag->MaxDamage = new LongTag('MaxDamage', $this->getMaxDamage());
        $this->namedtag->FlyTime = new LongTag('FlyTime', (int)$this->getFlyTime());
        $this->namedtag->MainTask = new ShortTag('MainTask', (int)$this->getMainTask());
        // $this->namedtag->PVP = new ByteTag('PVP', (int)$this->getPVPStatus());
        $this->namedtag->GTo = new ShortTag('GTo', (int)$this->getGTo());
        $this->namedtag->EnderChestCount = new LongTag('EnderChestCount', (int)$this->getEnderChestCount());
        switch($this->getGender()){
            case '男':
                $this->namedtag->Gender = new ByteTag('Gender', 1);
                break;
            case '女':
                $this->namedtag->Gender = new ByteTag('Gender', 0);
                break;
            default:
                $this->namedtag->Gender = new ByteTag('Gender', 2);
                break;
        }
        $this->namedtag->ForceTP = new ByteTag('ForceTP', (int)$this->getForceTP());
        $this->namedtag->Cape = new ByteTag('Cape', (int)$this->getCape());
        //Food
        $this->namedtag->foodLevel = new IntTag('foodLevel', $this->getFood());
        $this->namedtag->foodExhaustionLevel = new FloatTag('foodExhaustionLevel', $this->getExhaustion());
        $this->namedtag->foodSaturationLevel = new FloatTag('foodSaturationLevel', $this->getSaturation());
    }
    public function despawnFrom(Player $player, bool $send = true){
        parent::despawnFrom($player, $send);
        if($this->linkedEntity instanceof Chair){
            $this->linkedEntity->removeEntity($player);
        }
    }
    /**
     * @param Player $player
     */
    public function spawnTo(Player $player){
        if($this->level->getName()==='login')return true;
        if(strlen($this->skin) < 64 * 32 * 4){
            $e = new \InvalidStateException((new \ReflectionClass($this))->getShortName() . ' must have a valid skin set');
            $this->server->getLogger()->logException($e);
            $this->close();
        }elseif($player !== $this and !isset($this->hasSpawned[$player->getLoaderId()])){
            $this->hasSpawned[$player->getLoaderId()] = $player;

            if(!($this instanceof Player)){
                $this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getName(), $this->skinId, $this->skin, [$player]);
            }

            $pk = new AddPlayerPacket();
            $pk->uuid = $this->getUniqueId();
            $pk->username = $this->getName();
            $pk->eid = $this->getId();
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->speedX = $this->motionX;
            $pk->speedY = $this->motionY;
            $pk->speedZ = $this->motionZ;
            $pk->yaw = $this->yaw;
            $pk->pitch = $this->pitch;
            $pk->item = $this->getInventory()->getItemInHand();
            $pk->metadata = $this->dataProperties;
            $player->dataPacket($pk);

            $this->sendLinkedData($player);

            $this->inventory->sendArmorContents($player);

            if(!($this instanceof Player)){
                $this->server->removePlayerListData($this->getUniqueId(), [$player]);
            }
        }
    }

    public function close(){
        if(!$this->closed){
            $this->Homes=[];
            $this->Pets=[];
            if($this->getFloatingInventory() instanceof FloatingInventory){
                foreach($this->getFloatingInventory()->getContents() as $item){
                    $this->server->getLogger()->addData($this, $item, 'floating');
                }
                $this->getFloatingInventory()->setContents([]);
                //TODO 玩家退出游戏保存悬浮物品有什么
				$this->floatingInventory = null;
            }else{
                $this->server->getLogger()->debug('Attempted to drop a null crafting inventory\n');
            }
            if(!($this instanceof Player) or $this->loggedIn){
                foreach($this->inventory->getViewers() as $viewer){
                    $viewer->removeWindow($this->inventory);
                }
            }
            parent::close();
        }
    }
}
