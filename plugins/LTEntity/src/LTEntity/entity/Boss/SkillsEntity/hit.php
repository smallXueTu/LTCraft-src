<?php


namespace LTEntity\entity\Boss\SkillsEntity;


use pocketmine\entity\Entity;

interface hit
{
    /**
     * @param Entity $entity
     * @param array $args 参数
     * @return mixed
     */
    public function hit(Entity $entity, array $args = []);
}