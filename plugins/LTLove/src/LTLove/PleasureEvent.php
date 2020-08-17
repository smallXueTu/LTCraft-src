<?php
namespace LTLove;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\math\Vector3;
use pocketmine\network\protocol\{EntityEventPacket,PlayerActionPacket};
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\level\particle\HeartParticle;
use pocketmine\scheduler\CallbackTask;
use LTPopup\Popup as LTPopup;

class PleasureEvent{
	private $Initiate;
	private $Accept = null;
	private $NumberOf = 200;
	private $task;
	public function __construct(Player $Initiate, Player $Accept = null){
		$this->task=new CallbackTask([$this, 'update'], []);
		$Initiate->getServer()->getScheduler()->scheduleRepeatingTask($this->task, 2, 2);
		$Initiate->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_IMMOBILE, true);//禁止移动
		if($Accept === null){//自慰
			$this->Initiate = $Initiate;
			$Initiate->sleepOn(new Vector3($Initiate->x,$Initiate->y,$Initiate->z));//躺下
			$this->runBuff();
			return;
		}
		if($Initiate->getGender()!==$Accept->getGender()){
			if($Initiate->getGender()=='男'){//发起者是男
				$this->Initiate=$Accept;
				$this->Accept=$Initiate;
			}else{//发起者是女
				$this->Initiate=$Initiate;
				$this->Accept=$Accept;
			}
		}else{//性别一样 发起者是受
			$this->Initiate=$Initiate;
			$this->Accept=$Accept;
		}
		$this->runBuff();
		$this->Initiate->sleepOn(new Vector3($Initiate->x,$Initiate->y,$Initiate->z));//躺下
		$this->Accept->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_IMMOBILE, true);//禁止移动
	}
	public function Exception($mess='发生了一个未知问题导致性行为结束!'){
		if($this->Accept){
			$this->Accept->sendMessage(Main::HEAD.'c'.$mess);
		}
		$this->Initiate->sendMessage(Main::HEAD.'c'.$mess);
		$this->stop();
	}
	public function stop(){
		$this->Initiate->removeEffect(10);
		$this->Initiate->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_IMMOBILE, false);//允许移动
		$this->Initiate->setPleasureEvent(null);

		$this->Initiate->getServer()->getScheduler()->cancelTask($this->task->getTaskId());
		if($this->Accept===null){
			$this->Initiate->stopSleep();
			$this->Initiate->removeEffect(9);
            $this->Initiate->newProgress('这感觉，有点...');
			return;
		}
		$this->Accept->setDataFlag(Entity::DATA_FLAGS,Entity::DATA_FLAG_IMMOBILE, false);//允许移动
		$this->Accept->setPleasureEvent(null);
		$this->Accept->removeEffect(10);
		$this->Accept->removeEffect(9);
        $this->Initiate->newProgress('人生高潮');
        $this->Accept->newProgress('人生高潮');
	}
	public function update(){
		$this->runSpecialEffect();
		if($this->Accept){
			if(!$this->Initiate->isSleeping()){
				$this->Initiate->sendMessage(Main::HEAD.'d你结束了啪啪啪!');
				$this->Accept->sendMessage(Main::HEAD.'c受结束了啪啪啪!');
				$this->stop();
				return;
			}
		}else{
			if(!$this->Initiate->isSleeping()){
				$this->stop();
				return;
			}
		}
		if(--$this->NumberOf===0){
			$this->stop();
		}
	}
	public function runSpecialEffect(){
		$this->Initiate->getLevel()->addParticle(new HeartParticle($this->Initiate));
		$pk = new EntityEventPacket();
		$pk->eid = $this->Initiate->getId();
		$pk->event = EntityEventPacket::HURT_ANIMATION;
		if($this->Accept!==null){
			$this->Accept->setSneaking(true);
			foreach(array_merge($this->Initiate->getViewers(),[$this->Initiate, $this->Accept]) as $player)$player->dataPacket($pk);
			if(!mt_rand(0,9)){
				$say=new PlayerChatEvent($this->Initiate, self::CallBed(), 'chat.type.text', array_merge($this->Initiate->getViewers(),[$this->Initiate]), false);
				LTPopup::getInstance()->onPlayerChat($say);
			}
		}else{
			$this->Initiate->setSneaking(true);
			foreach(array_merge($this->Initiate->getViewers(),[$this->Initiate]) as $player)$player->dataPacket($pk);
		}
	}
	public function runBuff(){
		if($this->Accept!==null){
			$this->Accept->addEffect(Effect::getEffect(10)->setDuration(20*20)->setAmplifier(4));
		}
		$this->Initiate->addEffect(Effect::getEffect(9)->setDuration(20*20)->setAmplifier(5));
		$this->Initiate->addEffect(Effect::getEffect(10)->setDuration(20*20)->setAmplifier(4));
	}
	public static function CallBed(){
	    /**
         * 我没怎么不要脸
         */
//		$message=[
//			'啊~',
//			'不要',
//			' ~~停',
//			'不要 ~~ 停',
//			'啊~ 爽~~',
//			'老 ~公 ',
//			'~ ~ 啊 ~哎呀~',
//			'用~ ~力~',
//			'老公 ~ 爱你哟~'
//		];
//		return $message[array_rand($message)];
	}
}