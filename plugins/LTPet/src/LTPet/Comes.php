<?php
namespace LTPet;

use pocketmine\Player;


class Comes{
	private $player;
	private $pets = [];
	public function __construct(Player $player){
		$this->player=$player;
	}
	public function addPet($name, $pet){
		$this->pets[$name]=$pet;
	}
	public function removePet($name){
		unset($this->pets[$name]);
	}
	public function getPets(){
		return $this->pets;
	}
	public function getPet($name){
		return $this->pets[$name]??null;
	}
	public function getCount(){
		return count($this->pets);
	}
	public function closePets(){
		foreach($this->pets as $pet){
			$pet->close();
		}
	}
	public function closePet($name){
		if(isset($this->pets[$name])){
			$this->pets[$name]->close();
			unset($this->pets[$name]);
			return true;
		}else{
			return true;
		}
	}
}