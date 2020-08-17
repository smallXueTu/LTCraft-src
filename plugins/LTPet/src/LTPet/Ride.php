<?php
namespace LTPet;

use LTPet\Pets\FlyingPets\LTEnderDragon;
use pocketmine\Player;

class Ride{
	private $pet;
	private $RideMax = 1;
	private $players = [];
	public function __construct($pet){
		$this->pet=$pet;
		$this->RideMax=$pet->getRedeMaxPlayer();
	}
	public function getSeat(){
		if(count($this->players)==$this->RideMax){
			return false;
		}
		for($i=0;$i<$this->getRideMax();$i++){
			if(!isset($this->players[$i]))return $i;
		}
		return false;
	}
	public function getCount(){
		return count($this->players);
	}
	public function getPlayerSeat($p){
		foreach($this->players as $i=>$player){
			if($player===$p){
				return $i;
			}
		}
		return false;
	}
	public function addRide($seat, Player $player){
	    if($this->pet instanceof LTEnderDragon){
            $player->newProgress('驯龙高手','骑上一只末影龙翱翔于天空吧~');
        }
		$this->players[$seat]=$player;
	}
	public function removeRide($seat){
		unset($this->players[$seat]);
	}
	public function getSeatVector3($s){
		return $this->pet->getRedeVector3()[$s]??null;
	}
	public function getRideMax(){
		return $this->RideMax;
	}
	public function getPlayers(){
		return $this->players;
	}
	public function unlinkAll(){
		foreach($this->players as $player){
			$this->pet->cancelLinkEntity($player);
		}
	}
}