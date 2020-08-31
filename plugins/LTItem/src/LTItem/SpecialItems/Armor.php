<?php
namespace LTItem\SpecialItems;

use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\Color;
use LTItem\LTItem;

class Armor extends Item implements LTItem{
	private $armorV = 0;
	private $miss = 0;
	private $thorns = 0;
	private $effect = '';
	private $handMessage = false;
	private $resistanceFire = false;
	private $speed = 0;
	private $health = 0;
	private $lucky = 0;
	private $tough = 0;
	private $controlReduce = 0;
	private $ArmorName;
	private $binding = '*';
    private array $conf;
	public $colorInfo = [0, 0];
	public function __construct(array $conf, int $count, CompoundTag $nbt, $init=true){
		$idInfo=explode(':',$conf['ID']);
		parent::__construct($idInfo[0], $idInfo[1]??0, $count);
		$this->setCompoundTag($nbt);
		$this->setCustomName($conf['名字']);
		if($init)$this->initW($conf);
		$this->conf = $conf;
		$this->ArmorName = $this->getNamedTag()['armor'][1];
		$this->binding = strtolower($this->getNamedTag()['armor'][0]);
	}

    /**
     * @return array 配置
     */
	public function getConfig(){
	    return $this->conf;
    }

    /**
     * @param $conf array 配置
     */
	public function initW($conf){
		try{
			$nbt=$this->getNamedTag();
			if(!isset($nbt['armor'][13])){
				$nbt['armor'][3]=new StringTag('',$nbt['armor'][3]??0);//护甲
				$nbt['armor'][4]=new StringTag('',$nbt['armor'][4]??0);//血量
				$nbt['armor'][5]=new StringTag('',$nbt['armor'][5]??0);//闪避
				$nbt['armor'][6]=new StringTag('',$nbt['armor'][6]??0);//速度
				$nbt['armor'][7]=new StringTag('',$nbt['armor'][7]??0);//控制减少
				$nbt['armor'][8]=new StringTag('',$nbt['armor'][8]??0);//药水
				$nbt['armor'][9]=new StringTag('',$nbt['armor'][9]??1);//9 等级
				$nbt['armor'][10]=new StringTag('',$nbt['armor'][10]??0);//10 经验
				$nbt['armor'][11]=new StringTag('',$nbt['armor'][11]??1);//11 星级
				$nbt['armor'][12]=new StringTag('',$nbt['armor'][12]??0);//12 反伤
				$nbt['armor'][13]=new StringTag('',$nbt['armor'][13]??0);//13 坚韧
				$nbt['armor'][14]=new StringTag('',$nbt['armor'][14]??0);//14 幸运
			}
			$this->armorV = $conf['护甲']+$nbt['armor'][3];
			$this->thorns = $conf['反伤']+$nbt['armor'][4];
			$this->miss = $conf['闪避']+$nbt['armor'][5];
			$this->lucky = $conf['幸运']??0+$nbt['armor'][14];
			if($nbt['armor'][8]=='')
				$this->effect = $conf['effect'];
			else $this->effect = $conf['effect']==''?'':($conf['effect'].'@'.$nbt['armor'][8]);
			$this->handMessage = $conf['手持提示'];
			$this->resistanceFire = $conf['抗燃烧'];
			$this->speed = 1+$conf['速度']+$nbt['armor'][6];
			$this->health = $conf['附加血量']+$nbt['armor'][2];
			$this->tough = $conf['坚韧'];
			$this->controlReduce = $conf['控制减少']+$nbt['armor'][7];
			if($conf['颜色']!=false){
				$nbt['customColor'] = new IntTag("customColor", Color::getDyeColor($conf["颜色"])->getColorCode());
				$nbt=$this->setNamedTag($nbt);
			}
		}catch(\Throwable $e){
			Server::getInstance()->getLogger()->warning($this->getWeaponType().':'.$this->getLTName().'配置文件出错 在'.$e->getLine());
		}
	}

    /**
     * @param null $conf
     * @return array|bool|mixed
     */
	public function getConf($conf = null){
		if($conf === null){
			return $this->conf;
		}else{
			return $this->conf[$conf]??false;
		}
	}

    /**
     * @return mixed|string类型
     */
	public function getTypeName(){
		return '盔甲';
	}
	public function setBinding($name){
		$nbt=$this->getNamedTag();
		$nbt['armor'][0]=new StringTag('',$name);
		$this->binding = strtolower($name);
		$this->setNamedTag($nbt);
		return $this;
	}
	public function getExp(){
		return $this->getNamedTag()['armor'][10];
	}
	public function setExp($exp){
		$this->getNamedTag()['armor'][10] =  new StringTag('',$exp);
	}
	public function getLevel(){
		return $this->getNamedTag()['armor'][9];
	}
	public function setLevel($level){
		if($level>$this->getLevel())self::upGrade($this->getLTName(), $level);
		$this->getNamedTag()['armor'][9] = new StringTag('',$level);
	}

    /**
     * @return float|int 最大等级
     */
	public function getMaxLevel(){
		return $this->getNamedTag()['armor'][11]*10;
	}
	public function getX(){
		return $this->getNamedTag()['armor'][11];
	}
	public function getMaxX(){
		return $this->conf['最大星级'];
	}

    /**
     * @param $exp
     * @return float|int 升级经验
     */
	public function addExp($exp){
		$nbt=$this->getNamedTag();
		$yexp=$nbt['armor'][9];
		if($this->getLevel()>=$this->getX()*10)return $exp;	
		if($yexp+$exp>=self::getUpExp($this->getLevel())){
			$redundantExp=$exp-(self::getUpExp($this->getLevel())-$yexp);
			$this->setLevel($this->getLevel()+1);
			$nextLvel=$this->getLevel();
			while($redundantExp>=self::getUpExp($nextLvel)){
				if($nextLvel>=$this->getX()*10){
					$this->setExp(0);
					return $redundantExp;
				}
				$redundantExp-=self::getUpExp($nextLvel++);
				$this->setLevel($this->getLevel()+1);
			}
			$this->setExp($redundantExp);
		}else $this->setExp($this->getExp()+$exp);
		$this->setNamedTag($nbt);
		return 0;
	}
	public function getControlReduce(){
		return $this->controlReduce;
	}
	public function getBinding(){
		return $this->binding;
	}
	public function canUse(Player $player){
		if($this->binding==='*' or $this->binding===strtolower($player->getName()))return true;
		return false;
	}
	public function getTough(){
		return $this->tough;
	}
	public function getLTName(){
		return $this->ArmorName;
	}
	public function getHealth(){
		return $this->health;
	}
	public function getArmorV(){
		return $this->armorV;
	}
	public function getLucky(){
		return $this->lucky;
	}
	public function getThorns(){
		return $this->thorns;
	}
	public function getMiss(){
		return $this->miss;
	}
	public function getEffects(){
		return $this->effect;
	}
	public function getHandMessage(Player $player):string {
		if($this->canUse($player)){
			return strtr($this->handMessage,['@h'=>$this->getArmorV(),'%'=>'%%%%','@x'=>$this->getHealth(),'@f'=>$this->getThorns(),'@s'=>$this->getMiss(),'@j'=>$this->getTough(),'@sp'=>$this->getSpeed(),'@k'=>$this->getControlReduce()]);
		}else return '你不是这个盔甲的拥有者！';
	}
	public function isResistanceFire(){
		return $this->resistanceFire;
	}
	public function getSpeed(){
		return $this->speed;
	}	
	public static function getUpExp($level){
		return $level*2;
	}
	public function getAttribute($attribute){
		return $this->conf[$attribute]??false;
	}
	public static function upGrade(Armor $armor, $level){
		switch($armor->getLTName()){
			/*
			case '紫金胸甲':
				$nbt=$armor->getNamedTag();
				$nbt['armor'][3] = new StringTag('',(int)$level);
				$nbt['armor'][4] = new StringTag('',(int)$level/2);
				$armor->setNamedTag($nbt);
			break;
			case '紫金战靴':
				$nbt=$armor->getNamedTag();
				$nbt['armor'][3] = new StringTag('',(int)$level);
				$nbt['armor'][4] = new StringTag('',(int)$level/2);
				$armor->setNamedTag($nbt);
			break;
			case '紫金护膝':
				$nbt=$armor->getNamedTag();
				$nbt['armor'][3] = new StringTag('',(int)$level);
				$nbt['armor'][4] = new StringTag('',(int)$level/2);
				$armor->setNamedTag($nbt);
			break;
			case '紫金头盔':
				$nbt=$armor->getNamedTag();
				$nbt['armor'][3] = new StringTag('',(int)$level);
				$nbt['armor'][4] = new StringTag('',(int)$level/2);
				$armor->setNamedTag($nbt);
			break;
			*/
			case '敖龙银靴':
			case '敖龙银膝':
			case '敖龙银甲':
			case '敖龙银帽':
			case '赤金之靴':
			case '赤金之膝':
			case '赤金之甲':
			case '赤金之帽':
			case '虎头皂金靴':
			case '虎头皂金膝':
			case '虎头皂金甲':
			case '虎头皂金帽':
				$nbt=$armor->getNamedTag();
				$nbt['armor'][3] = new StringTag('',(int)$level);
				$nbt['armor'][4] = new StringTag('',(int)$level);
				$armor->setNamedTag($nbt);
			break;
		}
		$armor->initW($armor->getConfig());
	}
	public function getMaxStackSize() : int{
		return 1;
	}
	public function getInfo() {
		return $this->conf['介绍'];
	}
}