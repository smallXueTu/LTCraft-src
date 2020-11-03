<?php


namespace LTItem\SpecialItems\Weapon;

/**
 * 类似于拔刀剑的武器接口
 * Interface DrawingKnife
 * @package LTItem\SpecialItems\Weapon
 */
interface DrawingKnife
{
    /**
     * 设置耐久值
     * @param $durable int
     * @return DrawingKnife
     */
    public function setDurable(int $durable): DrawingKnife;

    /**
     * 获取耐久值
     * @return int
     */
    public function getDurable() : int;
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