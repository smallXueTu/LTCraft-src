<?php


namespace LTItem\SpecialItems\Weapon;


interface DrawingKnife
{
    /**
     * 增加荣耀值
     * @param int $number
     * @return mixed
     */
    public function addGlory(int $number);

    /**
     * 获取荣耀值
     * @return int
     */
    public function getGlory() : int;

    /**
     * 获取杀敌数
     * @return int
     */
    public function getKills() : int;

    /**
     * 增加杀敌数
     * @param int $number
     * @return mixed
     */
    public function addKills(int $number);
}