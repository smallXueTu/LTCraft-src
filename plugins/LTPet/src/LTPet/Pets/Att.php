<?php
namespace LTPet\Pets;

use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\level\particle\HeartParticle;
use LTPet\Pets\Pets;
use LTPet\Main;
use LTPet\Pets\WalkingPets\LTNPC;

class Att{
	private $owner;
	private $config;
	private $pet;
	private $remind = 0;
	private $info;
	private $plugin;
	public function __construct(Player $owner, Pets $pet, $info){
		$this->owner=$owner;
		$this->pet=$pet;
		$this->info=$info;
		$this->plugin=Main::getInstance();
		if(!isset($this->info['hunger'])){
			$this->info['hunger']=100;
		}
		if(!isset($this->info['love'])){
			$this->info['love']=0;
		}
	}
	public function getInfo(){
		return $this->info;
	}
	public function setInfo($info){
		$this->info = $info;
	}
	public function getLove(){
		return $this->info['love'];
	}
	public function addHunger($v){
		$this->info['hunger'] += $v;
		$a=0;
        if($this->info['hunger'] > 10000){
			$a=$this->info['hunger']-10000;
            $this->info['hunger'] = 10000;
		}
        $this->owner->sendMessage('§l§a喂养成功，增加了'.(($v-$a) / 100) .'%'.'饥饿度');
		if($this->info['hunger'] > 3000 and $this->remind!==0) {//提醒5
			$this->remind=0;
		}elseif($this->info['hunger'] > 3000) {//提醒4
			$this->remind=1;
		}elseif($this->info['hunger'] > 1500) {//提醒3
			$this->remind=2;
		}elseif($this->info['hunger'] > 500) {//提醒2
			$this->remind=3;
		}elseif($this->info['hunger'] > 300) {//提醒1
			$this->remind=4;
		}
		$vector3=new Vector3($this->pet->x, $this->pet->y, $this->pet->z);
        $this->pet->level->addParticle(new HeartParticle($vector3));
        $this->pet->level->addParticle(new HeartParticle($vector3->add(0, 0.3, 0)));
        $this->pet->level->addParticle(new HeartParticle($vector3->add(0, 0.3, 0)));
        $this->pet->level->addParticle(new HeartParticle($vector3->add(0, 0.3, 0)));
		if(!mt_rand(0,9)){
			$this->info['love']++;
		}
		$this->updateNameTag();
	}
	public function setHunger($v){
		$this->info['hunger'] = $v;
	}
	public function getHunger(){
		return $this->info['hunger'];
	}
	public function updateHunger(){
		if(!mt_rand(0, 1)){
			if($this->pet instanceof MountPet)
			$this->info['hunger']-=1+$this->pet->getRideObject()->getCount();
			else 
			$this->info['hunger']--;
			if($this->info['hunger'] <= 3000 and $this->remind<=0) {//提醒1
				$this->owner->sendMessage('§l§a['.$this->pet->getPetName().'§r§a§l]主人我饿了，你有什么食物吗？');
				if($this->info['love']>0)$this->info['love']--;
				$this->remind=1;
			}elseif($this->info['hunger'] <= 1500 and $this->remind<=1) {//提醒2
				$this->owner->sendMessage('§l§a['.$this->pet->getPetName().'§r§a§l]主人我闻到你口袋的食物了，能给我点吗？');
				if($this->info['love']>0)$this->info['love']-=2;
				$this->remind=2;
			}elseif($this->info['hunger'] <= 500 and $this->remind<=2) {//提醒3
				$this->owner->sendMessage('§l§a['.$this->pet->getPetName().'§r§a§l]主人你再不给我吃的，我就把你吃了！');
				if($this->info['love']>0)$this->info['love']-=3;
				$this->remind=3;
			}elseif($this->info['hunger'] <= 100 and $this->remind<=3) {//提醒4
				$this->owner->sendMessage('§l§a['.$this->pet->getPetName().'§r§a§l]主人小心点 我吃你了！');
				if($this->info['love']>0)$this->info['love']-=4;
				$this->remind=4;
			}elseif($this->info['hunger'] <= 0 and $this->remind<=4) {//提醒5
				$this->pet->teleport($this->owner);
				if($this->plugin->eAPI->myMoney($this->owner)>=100000){
					$this->info['hunger']=$this->plugin->eAPI->myMoney($this->owner)/10;
					$this->owner->sendMessage('§l§a你被你的宠物吃掉了 并且拿走了你身上'.$this->plugin->eAPI->myMoney($this->owner).'橙币！！');
					$this->plugin->eAPI->reduceMoney($this->owner, $this->plugin->eAPI->myMoney($this->owner), '宠物打劫');
				}else{
					$this->owner->sendMessage('§l§a你被你的宠物吃掉了 并且拿走了你身上100000橙币！！');
					$this->plugin->eAPI->reduceMoney($this->owner, 100000, '宠物打劫');
					$this->info['hunger']=10000;
				}
				$this->owner->setLastDamageCause(new EntityDamageByEntityEvent($this->pet, $this->owner, EntityDamageEvent::CAUSE_EAT, 1000));
				$this->owner->setHealth(0);
				$this->remind=0;
				if($this->info['love']>0)$this->info['love']=0;
			}
			if($this->info['love']<0){
				$this->info['love']=0;
			}
		}
		$this->updateNameTag();
	}
	public function updateNameTag(){
		$this->pet->setNameTag(('§a'.$this->owner->getName().'的'.(($this->pet instanceof LTNPC) ? '女仆' : '宠物').':§d'.$this->info['name']."§r\n §c饥饿度:".$this->info['hunger'] / 100 .'% §a好感度:'.$this->info['love']));
	}
	public function save(){
		$this->owner->setPet(Main::getCleanName($this->info['name']), $this->info);
	}
}