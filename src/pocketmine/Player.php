<?php

/*
 *
 *  _____            _               _____
 * / ____|          (_)             |  __ \
 *| |  __  ___ _ __  _ ___ _   _ ___| |__) | __ ___
 *| | |_ |/ _ \ '_ \| / __| | | / __|  ___/ '__/ _ \
 *| |__| |  __/ | | | \__ \ |_| \__ \ |   | | | (_) |
 * \_____|\___|_| |_|_|___/\__, |___/_|   |_|  \___/
 *                         __/ |
 *                        |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author GenisysPro
 * @link https://github.com/GenisysPro/GenisysPro
 *
 *
*/

namespace pocketmine;

use LTCraft\Main;
use LTGrade\API;
use LTGrade\PlayerTask;
use LTItem\Buff;
use LTItem\Mana\Mana;
use LTItem\Mana\ManaFood;
use LTItem\SpecialItems\Armor;
use pocketmine\event\block\ItemFrameDropItemEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\CraftingManager;
use pocketmine\inventory\FloatingInventory;
use pocketmine\level\sound\AnvilBreakSound;
use pocketmine\network\protocol\LevelEventPacket;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\Stair;
use pocketmine\block\Fire;
use pocketmine\block\EndGateway;
use pocketmine\block\EndPortal;
use onebone\economyapi\EconomyAPI;
use pocketmine\block\PressurePlate;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Animal;
use pocketmine\entity\Arrow;
use pocketmine\entity\Attribute;
use pocketmine\entity\Boat;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\entity\Item as DroppedItem;
use pocketmine\entity\Living;
use pocketmine\entity\Minecart;
use pocketmine\entity\Projectile;
use pocketmine\event\entity\EntityCombustByEntityEvent;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\player\cheat\PlayerIllegalMoveEvent;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerBedLeaveEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerTextPreSendEvent;
use pocketmine\event\player\PlayerToggleFlightEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\player\PlayerToggleSprintEvent;
use pocketmine\event\player\PlayerToggleGlideEvent;
use pocketmine\event\player\PlayerUseFishingRodEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\TextContainer;
use pocketmine\event\Timings;
use pocketmine\event\TranslationContainer;
use pocketmine\inventory\AnvilInventory;
use pocketmine\inventory\BaseTransaction;
use pocketmine\inventory\BigShapedRecipe;
use pocketmine\inventory\BigShapelessRecipe;
use pocketmine\inventory\DropItemTransaction;
use pocketmine\inventory\EnchantInventory;
use pocketmine\inventory\FurnaceInventory;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\InventoryHolder;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\Food;
use pocketmine\level\ChunkLoader;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\Position;
use pocketmine\level\sound\LaunchSound;
use pocketmine\level\WeakPosition;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\metadata\MetadataValue;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\protocol\AdventureSettingsPacket;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\AvailableCommandsPacket;
use pocketmine\network\protocol\BatchPacket;
use pocketmine\network\protocol\ChangeDimensionPacket;
use pocketmine\network\protocol\ChunkRadiusUpdatedPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\DisconnectPacket;
use pocketmine\network\protocol\EntityEventPacket;
use pocketmine\network\protocol\FullChunkDataPacket;
use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\PlayerActionPacket;
use pocketmine\network\protocol\PlayStatusPacket;
use pocketmine\network\protocol\ResourcePackChunkDataPacket;
use pocketmine\network\protocol\ResourcePackClientResponsePacket;
use pocketmine\network\protocol\ResourcePackDataInfoPacket;
use pocketmine\network\protocol\PlaySoundPacket;
use pocketmine\network\protocol\ResourcePacksInfoPacket;
use pocketmine\network\protocol\ResourcePackStackPacket;
use pocketmine\network\protocol\RespawnPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\network\protocol\SetPlayerGameTypePacket;
use pocketmine\network\protocol\SetSpawnPositionPacket;
use pocketmine\network\protocol\SetTimePacket;
use pocketmine\network\protocol\SetTitlePacket;
use pocketmine\network\protocol\StartGamePacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\TakeItemEntityPacket;
use pocketmine\network\protocol\TextPacket;
use pocketmine\network\protocol\UpdateAttributesPacket;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\network\protocol\TransferPacket;
use pocketmine\network\SourceInterface;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;
use pocketmine\plugin\Plugin;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\tile\ItemFrame;
use pocketmine\tile\Spawnable;
use pocketmine\utils\TextFormat;
use pocketmine\utils\UUID;
use pocketmine\scheduler\CallbackTask;
use LTPet\Pets\MountPet;
use LTPet\Pets\WalkingPets\LTNPC;
use LTPet\Pets\Pets;
use LTLogin\Events;
use LTVIP\Main as LTVIP;
use LTGrade\EventListener;
use LTItem\LTItem;
/**
 * Main class that handles networking, recovery, and packet sending to the server part
 */
class Player extends Human implements CommandSender, InventoryHolder, ChunkLoader, IPlayer {

    const SURVIVAL = 0;
    const CREATIVE = 1;
    const ADVENTURE = 2;
    const SPECTATOR = 3;
    const VIEW = Player::SPECTATOR;

    const CRAFTING_SMALL = 0;
    const CRAFTING_BIG = 1;
    const CRAFTING_ANVIL = 2;
    const CRAFTING_ENCHANT = 3;

    /** @var SourceInterface */
    public $interface;
    /** @var Position  */
    public $waitingTeleportTask=null;
    /** @var bool */
    public $playedBefore = false;
    /** @var bool */
    public $teleportTask = false;
    public $spawned = false;
    public $loggedIn = false;
    /** @var bool $forceFlying 强制飞行 */
    public bool $forceFlying = false;
    public $gamemode;
    public $moveCheck = false;
    public $lastBreak;
    public $updateTeleport = true;
    public $lastDie=null;
    public $lastClicken=null;
    /*啪啪事件*/
    public $PleasureEvent=null;
    public $isVIP=false;
    protected $windowCnt = 2;
    /** @var \SplObjectStorage<Inventory> */
    protected $windows;
    /** @var Inventory[] */
    protected $windowIndex = [];

    protected $messageCounter = 2;

    private $clientSecret;

    /** @var Vector3 */
    public $speed = null;
    public $lastTeleportTick = null;

    // public $achievements = [];

    public $craftingType = self::CRAFTING_SMALL; //0 = 2x2 crafting, 1 = 3x3 crafting, 2 = anvil, 3 = enchanting

    public $creationTime = 0;
    public $dieMessage = [];

    protected $randomClientId;

    protected $protocol;

    /** @var Vector3 */
    protected $forceMovement = null;
    /** @var Vector3 */
    protected $teleportPosition = null;
    protected $connected = true;
    protected $ip;
    protected $removeFormat = false;
    public $isDie = false;
    protected $port;
    protected $username;
    protected $iusername;
    protected $displayName;
    protected $startAction = -1;
    /** @var Vector3 */
    protected $sleeping = null;
    protected $clientID = null;

    protected $deviceModel;
    protected $deviceOS;

    private $loaderId = null;

    protected $stepHeight = 0.6;

    public $usedChunks = [];
    public $WaitingSendEntity = [];
    public $WaitingSendFloatingText = [];
    public $WaitingSendNPC = [];
    // public $lastMoveTick = 0;
    protected $chunkLoadCount = 0;
    protected $loadQueue = [];
    protected $nextChunkOrderRun = 5;
    protected $dimension = 0;

    /** @var Player[] */
    protected $hiddenPlayers = [];

    /** @var Vector3 */
    public $newPosition;

    protected $viewDistance = -1;
    protected $chunksPerTick;
    protected $spawnThreshold;
    /** @var null|WeakPosition */
    private $spawnPosition = null;

    protected $inAirTicks = 0;
    protected $startAirTicks = 5;

    //TODO: Abilities
    protected $autoJump = true;
    protected $allowFlight = false;
    protected $flying = false;
    protected $allowMovementCheats = false;
    protected $allowInstaBreak = false;

    private $needACK = [];

    private $batchedPackets = [];

    /** @var PermissibleBase */
    private $perm = null;

    public $weatherData = [0, 0, 0];

    /** @var Vector3 */
    public $fromPos = null;
    private $portalTime = 0;
    protected $shouldSendStatus = false;
    /** @var  Position */
    private $shouldResPos;
    public $lastMove = 0;
    /** @var bool  */
    public $canFly = false;
    /** @var bool  */
    public $attentionSend = false;
    /** @var bool  */
    public $isLogin = false;
    public $lastAttackMob = 0;
    public $PassiveCooling = 0;
    /** @var bool  */
    public $lastDieTime = 0;
    /** @var API */
    public $API = null;
    /** @var PlayerTask */
    public ?PlayerTask $Task = null;
    /** @var Buff */
    public $Buff = null;
    public $NPCs = [];
    public $FloatingTexts = [];

    /** @var Position[] */
    public $selectedPos = [];
    /** @var Level[] */
    public $selectedLev = [];

    /** @var Item[] */
    protected $personalCreativeItems = [];

    /** @var int */
    protected $lastEnderPearlUse = 0;
    public $phone;
    public function setAPI(\LTGrade\API $api){
        $this->API=$api;
    }
    public function removeAPI(){
        $this->API=null;
    }
    public function setlastAttackMob($time){
        $this->lastAttackMob = $time;
    }
    public function getlastAttackMob(){
        return $this->lastAttackMob;
    }

    /**
     * @return API
     */
    public function getAPI(){
        return $this->API;
    }
    public function setPleasureEvent($PleasureEvent){
        $this->PleasureEvent=$PleasureEvent;
    }
    public function getPleasureEvent(){
        return $this->PleasureEvent;
    }
    public function setTask(\LTGrade\PlayerTask $Task){
        $this->Task=$Task;
    }

    /**
     * @return PlayerTask
     */
    public function getTask(){
        return $this->Task;
    }
    public function setBuff(\LTItem\Buff $Buff){
        $this->Buff=$Buff;
    }

    /**
     * @return Buff
     */
    public function getBuff(){
        return $this->Buff;
    }
    public function getLastDie(){
        if($this->lastDie==null)
            return false;
        else
            return $this->lastDie;
    }

    /**
     * @return mixed
     */
    public function getDeviceModel(){
        return $this->deviceModel;
    }

    /**
     * @return mixed
     */
    public function getDeviceOS(){
        return $this->deviceOS;
    }

    /**
     * @return Item
     */
    public function getItemInHand(){
        return $this->inventory->getItemInHand();
    }

    /**
     * @return TranslationContainer
     */
    public function getLeaveMessage(){
        return new TranslationContainer(TextFormat::YELLOW . '%multiplayer.player.left', [
            $this->getDisplayName()
        ]);
    }

    /**
     * This might disappear in the future.
     * Please use getUniqueId() instead (IP + clientId + name combo, in the future it'll change to real UUID for online
     * auth)
     */
    public function getClientId(){
        return $this->randomClientId;
    }

    /**
     * @return mixed
     */
    public function getClientSecret(){
        return $this->clientSecret;
    }

    /**
     * @return bool
     */
    public function isBanned(){
        return $this->server->getNameBans()->isBanned(strtolower($this->getName()));
    }

    /**
     * @param bool $value
     */
    public function setBanned($value){
        if($value === true){
            $this->server->getNameBans()->addBan($this->getName(), null, null, null);
            $this->kick(TextFormat::RED . '你被服务器加入黑名单！');
        }else{
            $this->server->getNameBans()->remove($this->getName());
        }
    }

    /**
     * @return bool
     */
    public function isWhitelisted() : bool{
        return $this->server->isWhitelisted(strtolower($this->getName()));
    }

    /**
     * @param bool $value
     */
    public function setWhitelisted($value){
        if($value === true){
            $this->server->addWhitelist(strtolower($this->getName()));
        }else{
            $this->server->removeWhitelist(strtolower($this->getName()));
        }
    }

    /**
     * @return $this
     */
    public function getPlayer(){
        return $this;
    }
    /**
     * @return null
     */
    public function getFirstPlayed(){
        return $this->namedtag instanceof CompoundTag ? $this->namedtag['firstPlayed'] : null;
    }

    /**
     * @return null
     */
    public function getLastPlayed(){
        return $this->namedtag instanceof CompoundTag ? $this->namedtag['lastPlayed'] : null;
    }

    /**
     * @return bool
     */
    public function hasPlayedBefore(){
        return $this->playedBefore;
    }

    /**
     * @param $value
     * @param bool $force
     */
    public function setAllowFlight($value, $force = false){
        if($value!==false and in_array($this->level->getName(), ['pvp', 'boss', 'pve']) and $force===false and !$this->isOp())
            $value = false;
        elseif(($value!==true and ($this->level->getName()==='zc' or $this->isOp()) and $force===false) or \LTCraft\Main::getInstance()->getMode()==1)
            $value = true;
        $this->allowFlight = (bool) $value;
        $this->sendSettings();
    }

    /**
     * 获取飞行权限
     * @return bool
     */
    public function getAllowFlight() : bool{
        return $this->allowFlight;
    }

    /**
     * @param bool $value
     */
    public function setFlying(bool $value){
        $this->flying = $value;
        $this->sendSettings();
    }

    /**
     * @return bool
     */
    public function isFlying() : bool{
        return $this->flying;
    }

    /**
     * @param $value
     */
    public function setAutoJump($value){
        $this->autoJump = $value;
        $this->sendSettings();
    }

    /**
     * @return bool
     */
    public function hasAutoJump() : bool{
        return $this->autoJump;
    }

    /**
     * @return bool
     */
    public function allowMovementCheats() : bool{
        return $this->allowMovementCheats;
    }

    /**
     * @param bool $value
     */
    public function setAllowMovementCheats(bool $value = false){
        $this->allowMovementCheats = $value;
    }

    /**
     * @return bool
     */
    public function allowInstaBreak() : bool{
        return $this->allowInstaBreak;
    }

    /**
     * @param bool $value
     */
    public function setAllowInstaBreak(bool $value = false){
        $this->allowInstaBreak = $value;
    }

    /**
     * @param Player $player
     */
    public function spawnTo(Player $player){
        if($this->spawned and $player->spawned and $this->isAlive() and $player->isAlive() and $player->getLevel() === $this->level and $player->canSee($this) and !$this->isSpectator()){
            parent::spawnTo($player);
        }
    }

    /**
     * @return Server
     */
    public function getServer(){
        return $this->server;
    }

    /**
     * @return bool
     */
    public function getRemoveFormat(){
        return $this->removeFormat;
    }

    /**
     * @param bool $remove
     */
    public function setRemoveFormat($remove = true){
        $this->removeFormat = (bool) $remove;
    }

    /**
     * @param Player $player
     *
     * @return bool
     */
    public function canSee(Player $player) : bool{
        return !isset($this->hiddenPlayers[$player->getRawUniqueId()]);
    }

    /**
     * @param Player $player
     */
    public function hidePlayer(Player $player){
        if($player === $this){
            return;
        }
        $this->hiddenPlayers[$player->getRawUniqueId()] = $player;
        $player->despawnFrom($this);
    }

    /**
     * @param Player $player
     */
    public function showPlayer(Player $player){
        if($player === $this){
            return;
        }
        unset($this->hiddenPlayers[$player->getRawUniqueId()]);
        if($player->isOnline()){
            $player->spawnTo($this);
        }
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function canCollideWith(Entity $entity) : bool{
        return false;
    }

    public function resetFallDistance(){
        parent::resetFallDistance();
        if($this->inAirTicks !== 0){
            $this->startAirTicks = 5;
        }
        $this->inAirTicks = 0;
    }

    /**
     * @return int
     */
    public function getViewDistance() : int{
        return $this->viewDistance;
    }

    /**
     * @param int $distance
     */
    public function setViewDistance(int $distance){
        $this->viewDistance = $this->server->getAllowedViewDistance($distance, $this);

        $this->spawnThreshold = (int) (min($this->viewDistance, $this->server->getProperty('chunk-sending.spawn-radius', 4)) ** 2 * M_PI);

        $pk = new ChunkRadiusUpdatedPacket();
        $pk->radius = $this->viewDistance;
//var_dump($pk->radius);
//$pk->radius = 4;
        $this->dataPacket($pk);
    }

    /**
     * @return bool
     */
    public function isOnline() : bool{
        return $this->connected === true and $this->loggedIn === true;
    }

    /**
     * @return bool
     */
    public function isOp() : bool{
        return $this->server->isOp($this->getName());
    }

    /**
     * @param bool $value
     */
    public function setOp($value){
        if($value === $this->isOp()){
            return;
        }

        if($value === true){
            $this->server->addOp($this->getName());
        }else{
            $this->server->removeOp($this->getName());
        }

        $this->recalculatePermissions();
        $this->sendSettings();
    }

    /**
     * @param permission\Permission|string $name
     *
     * @return bool
     */
    public function isPermissionSet($name){
        return $this->perm->isPermissionSet($name);
    }

    /**
     * @param permission\Permission|string $name
     *
     * @return bool
     *
     * @throws \InvalidStateException if the player is closed
     */
    public function hasPermission($name){
        if($this->closed){
            throw new \InvalidStateException('Trying to get permissions of closed player');
        }
        return $this->perm->hasPermission($name);
    }

    /**
     * @param Plugin $plugin
     * @param string $name
     * @param bool   $value
     *
     * @return permission\PermissionAttachment|null
     */
    public function addAttachment(Plugin $plugin, $name = null, $value = null){
        if($this->perm == null) return null;
        return $this->perm->addAttachment($plugin, $name, $value);
    }


    /**
     * @param PermissionAttachment $attachment
     *
     * @return bool
     */
    public function removeAttachment(PermissionAttachment $attachment){
        if($this->perm == null){
            return false;
        }
        $this->perm->removeAttachment($attachment);
        return true;
    }

    public function recalculatePermissions(){
        $this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
        $this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

        if($this->perm === null){
            return;
        }

        $this->perm->recalculatePermissions();

        if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
            $this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
        }
        if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
            $this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
        }

        $this->sendCommandData();
    }

    /**
     * @return permission\PermissionAttachmentInfo[]
     */
    public function getEffectivePermissions(){
        return $this->perm->getEffectivePermissions();
    }

    public function sendCommandData(){
        $data = new \stdClass();
        $count = 0;
        foreach($this->server->getCommandMap()->getCommands() as $command){
            if(($cmdData = $command->generateCustomCommandData($this)) !== null){
                ++$count;
                $data->{$command->getName()} = new \stdClass();
                $data->{$command->getName()}->versions = [];
                $data->{$command->getName()}->versions[0] = $cmdData;
            }
        }

        if($count > 0){
            //TODO: structure checking
            $pk = new AvailableCommandsPacket();
            $pk->commands = json_encode($data);
            $this->dataPacket($pk);
        }
    }

    /**
     * @param SourceInterface $interface
     * @param null            $clientID
     * @param string          $ip
     * @param int             $port
     */
    public function __construct(SourceInterface $interface, $clientID, $ip, $port){
        $this->interface = $interface;
        $this->windows = new \SplObjectStorage();
        $this->perm = new PermissibleBase($this);
        $this->namedtag = new CompoundTag();
        $this->server = Server::getInstance();
        $this->lastBreak = PHP_INT_MAX;
        $this->ip = $ip;
        $this->port = $port;
        $this->clientID = $clientID;
        $this->loaderId = Level::generateChunkLoaderId($this);
        $this->chunksPerTick = (int) $this->server->getProperty('chunk-sending.per-tick', 4);
        $this->spawnThreshold = (int) (($this->server->getProperty('chunk-sending.spawn-radius', 4) ** 2) * M_PI);
        $this->spawnPosition = null;
        $this->gamemode = $this->server->getGamemode();
        //$this->setLevel($this->server->getLevelByName('login'));
        $this->newPosition = new Vector3(0, 0, 0);
        $this->boundingBox = new AxisAlignedBB(0, 0, 0, 0, 0, 0);

        $this->uuid = null;
        $this->rawUUID = null;

        $this->creationTime = microtime(true);

        $this->allowMovementCheats = (bool) $this->server->getProperty('player.anti-cheat.allow-movement-cheats', false);
        $this->allowInstaBreak = (bool) $this->server->getProperty('player.anti-cheat.allow-instabreak', false);
    }

    /**
     * @return bool
     */
    public function isConnected() : bool{
        return $this->connected === true;
    }

    /**
     * Gets the 'friendly' name to display of this player to use in the chat.
     *
     * @return string
     */
    public function getDisplayName(){
        return $this->displayName;
    }

    /**
     * @param string $name
     */
    public function setDisplayName($name){
        $this->displayName = $name;
        if($this->spawned){
            $this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $this->getSkinId(), $this->getSkinData());
        }
    }

    /**
     * @param string $str
     * @param string $skinId
     */
    public function setSkin($str, $skinId){
        parent::setSkin($str, $skinId);
        if($this->spawned){
            $this->server->updatePlayerListData($this->getUniqueId(), $this->getId(), $this->getDisplayName(), $skinId, $str);
        }
    }

    /**
     * Gets the player IP address
     *
     * @return string
     */
    public function getAddress() : string{
        return $this->ip;
    }

    /**
     * @return int
     */
    public function getPort() : int{
        return $this->port;
    }

    /**
     * @return Position
     */
    public function getNextPosition(){
        return $this->newPosition !== null ? new Position($this->newPosition->x, $this->newPosition->y, $this->newPosition->z, $this->level) : $this->getPosition();
    }

    /**
     * @return bool
     */
    public function isSleeping() : bool{
        return $this->sleeping !== null;
    }

    /**
     * @return int
     */
    public function getInAirTicks(){
        return $this->inAirTicks;
    }

    /**
     * @param Level $targetLevel
     *
     * @return bool|void
     */
    protected function switchLevel(Level $targetLevel){
        $oldLevel = $this->level;
        if(parent::switchLevel($targetLevel)){
            foreach($this->usedChunks as $index => $d){
                Level::getXZ($index, $X, $Z);
                $this->unloadChunk($X, $Z, $oldLevel);
            }
            if ($oldLevel->getDimension() != $targetLevel->getDimension() or $this->teleportTask){
                $pk = new ChangeDimensionPacket();
                $pk->dimension = $targetLevel->getDimension();
                $pk->x = $this->getX();
                $pk->y = $this->getY();
                $pk->z = $this->getZ();
                $this->dataPacket($pk);
                if ($targetLevel->getDimension()==Level::DIMENSION_NETHER){
                    $this->newProgress('我们需要更深入些');
                }elseif($targetLevel->getDimension()==Level::DIMENSION_END){
                    $this->newProgress('结束了？');
                }
                $this->dimension = $targetLevel->getDimension();
                if($oldLevel->getDimension() != $targetLevel->getDimension())$this->server->getPluginManager()->callEvent($ev = new EntityLevelChangeEvent($this, $oldLevel, $targetLevel));
            }
            $this->usedChunks = [];
            $pk = new SetTimePacket();
            $pk->time = $this->level->getTime();
            $pk->started = $this->level->stopTime == false;
            $this->dataPacket($pk);
            $targetLevel->getWeather()->sendWeather($this);
            foreach($this->NPCs as $NPC){
                $NPC->despawnFrom($this);
            }
            if($this->spawned){
                $this->spawnToAll();
            }
        }
        $this->sendSettings();
    }

    /**
     * @param            $x
     * @param            $z
     * @param Level|null $level
     */
    private function unloadChunk($x, $z, Level $level = null){
        $level = $level === null ? $this->level : $level;
        $index = Level::chunkHash($x, $z);
        if(isset($this->usedChunks[$index])){
            foreach($level->getChunkEntities($x, $z) as $entity){
                if($entity !== $this){
                    $entity->despawnFrom($this);
                }
            }
            foreach($level->getFloatingTexts($x, $z) as $FloatingText){
                $FloatingText->despawnFrom($this);
            }
            foreach($this->level->getNPCs($x, $z) as $NPC){
                $NPC->despawnFrom($this);
            }
            unset($this->usedChunks[$index]);
        }
        $level->unregisterChunkLoader($this, $x, $z);
        unset($this->loadQueue[$index]);
    }

    /**
     * @return Position
     */
    public function getSpawn(){
        if($this->hasValidSpawnPosition()){
            return $this->spawnPosition;
        }else{
            $level = $this->server->getDefaultLevel();

            return $level->getSafeSpawn();
        }
    }

    /**
     * @return bool
     */
    public function hasValidSpawnPosition() : bool{
        return $this->spawnPosition instanceof WeakPosition and $this->spawnPosition->isValid();
    }

    /**
     * @param $x
     * @param $z
     * @param $payload
     */
    public function sendChunk($x, $z, $payload){
        if($this->connected === false){
            return;
        }

        $this->usedChunks[Level::chunkHash($x, $z)] = true;
        $this->chunkLoadCount++;
// if(!isset($this->testChunk))$this->testChunk = [];
        if($payload instanceof DataPacket){
            $this->dataPacket($payload);
            // $this->testChunk[]=[0, $payload];
        }else{
            $pk = new FullChunkDataPacket();
            $pk->chunkX = $x;
            $pk->chunkZ = $z;
            $pk->data = $payload;
            $this->batchDataPacket($pk);
            // $this->testChunk[]=[1, $pk];
        }

        if($this->spawned and $this->waitingTeleportTask===null){
            foreach($this->level->getChunkEntities($x, $z) as $entity){
                if($entity !== $this and !$entity->closed and $entity->isAlive()){
                    if($this->lastTeleportTick===0){
                        $this->WaitingSendEntity[]=$entity;
                        continue;
                    }
                    $entity->spawnTo($this);
                }
            }
            foreach($this->level->getFloatingTexts($x, $z) as $FloatingText){
                if($this->lastTeleportTick===0){
                    $this->WaitingSendFloatingText[]=$FloatingText;
                    continue;
                }
                $FloatingText->spawnTo($this);
            }
            foreach($this->level->getNPCs($x, $z) as $NPC){
                if($this->lastTeleportTick===0){
                    $this->WaitingSendNPC[]=$NPC;
                    continue;
                }
                $NPC->spawnTo($this);
            }
        }
        $sendComplete=count($this->usedChunks) >=5*16;
        foreach($this->usedChunks as $v){
            if($v===false)$sendComplete=false;
        }
        if($sendComplete and ($this->waitingTeleportTask!==null or $this->lastTeleportTick===0)){
            if($this->waitingTeleportTask!==null) {
                if (!$this->teleportTask) {
                    $this->teleportTask = true;
                    $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function () {
                        if ($this->waitingTeleportTask != null and $this->isOnline()) {
                            $this->teleport($this->waitingTeleportTask, null, null, false);
                            $this->lastTeleportTick = 0;
                            $this->teleportTask = false;
                        }
                    }, []), 5);
                }
            }elseif($this->lastTeleportTick===0){
                $this->server->getPluginManager()->callEvent($ev = new EntityLevelChangeEvent($this, $this->lastLevel, $this->getLevel()));
                $this->lastTeleportTick = $this->getServer()->getTick();
                $this->server->getScheduler()->scheduleDelayedTask(new CallbackTask(function () {
                    if(!$this->isOnline())return;
                    if(count($this->WaitingSendEntity)>0){
                        foreach($this->WaitingSendEntity as $entity){
                            if($entity !== $this and !$entity->closed and $entity->isAlive()){
                                $entity->spawnTo($this);
                            }
                        }
                        $this->WaitingSendEntity = [];
                    }
                    if(count($this->WaitingSendFloatingText)>0){
                        foreach($this->WaitingSendFloatingText as $FloatingText){
                            $FloatingText->spawnTo($this);
                        }
                        $this->WaitingSendFloatingText = [];
                    }
                    if(count($this->WaitingSendNPC)>0){
                        foreach($this->WaitingSendNPC as $NPC){
                            $NPC->spawnTo($this);
                        }
                        $this->WaitingSendNPC = [];
                    }
                }, []), 20);
            }
        }
    }
    public function onTutorial(){
        return \LTCraft\Main::getInstance()->hasTutorial($this);
    }
    public function sendNextChunk(){
        if($this->connected === false){
            return;
        }

        Timings::$playerChunkSendTimer->startTiming();

        $count = 0;
        if ($this->onTeleport())
            $chunksPerTick=128;
        else
            $chunksPerTick = $this->chunksPerTick;
        foreach($this->loadQueue as $index => $distance){//距离
            if($count >= $chunksPerTick){
                break;
            }

            $X = null;
            $Z = null;
            Level::getXZ($index, $X, $Z);

            ++$count;

            $this->usedChunks[$index] = false;
            $this->level->registerChunkLoader($this, $X, $Z, false);

            if(!$this->level->populateChunk($X, $Z)){
                continue;
            }

            unset($this->loadQueue[$index]);
            if ($this->waitingTeleportTask!=null){//玩家刷新地形 发送空块以节省性能..
                $chunk = new Chunk($X, $Z);
                $this->sendChunk($X, $Z, $chunk->networkSerialize());
            }else{
                $this->level->requestChunk($X, $Z, $this);
            }
        }
        // var_dump($this->chunkLoadCount);
        // var_dump($this->spawnThreshold);
        if($this->chunkLoadCount >= $this->spawnThreshold and $this->spawned === false and $this->teleportPosition === null){
            $this->doFirstSpawn();
        }

        Timings::$playerChunkSendTimer->stopTiming();
    }

    protected function doFirstSpawn(){
        $this->spawned = true;

        $this->sendPotionEffects($this);
        $this->sendData($this);

        $pk = new SetTimePacket();
        $pk->time = $this->level->getTime();
        $pk->started = $this->level->stopTime == false;
        $this->dataPacket($pk);

        $pos = $this->level->getSafeSpawn($this);

        $pk = new PlayStatusPacket();
        $pk->status = PlayStatusPacket::PLAYER_SPAWN;
        $this->dataPacket($pk);

        $this->noDamageTicks = 60;

        foreach($this->usedChunks as $index => $c){
            Level::getXZ($index, $chunkX, $chunkZ);
            foreach($this->level->getChunkEntities($chunkX, $chunkZ) as $entity){
                if($entity !== $this and !$entity->closed and $entity->isAlive()){
                    $entity->spawnTo($this);
                }
            }
            foreach($this->level->getFloatingTexts($chunkX, $chunkZ) as $FloatingText){
                $FloatingText->spawnTo($this);
            }
            foreach($this->level->getNPCs($chunkX, $chunkZ) as $NPC){
                $NPC->spawnTo($this);
            }
        }
        if(($time=$this->getFlyTime())!==0){
            if(time()<$time){
                $this->sendMessage('§l§a[提示]§e你的飞行权限截止到'.date("Y年m月d日H时i分s秒",$time),true);
                $this->canFly = true;
            }else{
                $this->setFlyTime(0);
                $this->sendMessage('§l§a[提示]§c你的飞行权限已到期',true);
            }
        }
        // $this->canFly = ((MultiSign::getInstance()->checkFly($this->username)) or (LTVIP::getInstance()->isVIP($this->username)!==false));
        $this->allowFlight = (($this->gamemode == 3) or ($this->gamemode == 1) or $this->canFly);
        $this->setHealth($this->getHealth());
        $this->setTask(new \LTGrade\PlayerTask($this));
        $this->setAPI(new \LTGrade\API($this,'',100));
        $this->getAPI()->setShowHealth($this->getAStatusIsDone('血量格式'));
        $this->server->getPluginManager()->callEvent($ev = new PlayerJoinEvent($this, 'test'));

        $this->sendSettings();
        $this->server->getLogger()->addData($this->username, [$this->phone, $this->ip, $this->clientID], 'join');

        $this->server->onPlayerLogin($this);
        $this->spawnToAll();

        $this->level->getWeather()->sendWeather($this);

        if($this->server->dserverConfig['enable'] and $this->server->dserverConfig['queryAutoUpdate']){
            $this->server->updateQuery();
        }
    }

    /**
     * @return bool
     */
    protected function orderChunks(){
        if($this->connected === false or $this->viewDistance === -1){
            return false;
        }

        Timings::$playerChunkOrderTimer->startTiming();

        $this->nextChunkOrderRun = 200;

        $radius = $this->server->getAllowedViewDistance($this->viewDistance, $this);
        $radiusSquared = $radius ** 2;
        $newOrder = [];
        $unloadChunks = $this->usedChunks;

        $centerX = $this->x >> 4;
        $centerZ = $this->z >> 4;
        for($x = 0; $x < $radius; ++$x){
            for($z = 0; $z <= $x; ++$z){
                if(($x ** 2 + $z ** 2) > $radiusSquared){
                    break; //skip to next band
                }

                //If the chunk is in the radius, others at the same offsets in different quadrants are also guaranteed to be.

                /* Top right quadrant */
                if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $x, $centerZ + $z)]) or $this->usedChunks[$index] === false){
                    $newOrder[$index] = true;
                }
                unset($unloadChunks[$index]);

                /* Top left quadrant */
                if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $x - 1, $centerZ + $z)]) or $this->usedChunks[$index] === false){
                    $newOrder[$index] = true;
                }
                unset($unloadChunks[$index]);

                /* Bottom right quadrant */
                if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $x, $centerZ - $z - 1)]) or $this->usedChunks[$index] === false){
                    $newOrder[$index] = true;
                }
                unset($unloadChunks[$index]);


                /* Bottom left quadrant */
                if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $x - 1, $centerZ - $z - 1)]) or $this->usedChunks[$index] === false){
                    $newOrder[$index] = true;
                }
                unset($unloadChunks[$index]);

                if($x !== $z){
                    /* Top right quadrant mirror */
                    if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $z, $centerZ + $x)]) or $this->usedChunks[$index] === false){
                        $newOrder[$index] = true;
                    }
                    unset($unloadChunks[$index]);

                    /* Top left quadrant mirror */
                    if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $z - 1, $centerZ + $x)]) or $this->usedChunks[$index] === false){
                        $newOrder[$index] = true;
                    }
                    unset($unloadChunks[$index]);

                    /* Bottom right quadrant mirror */
                    if(!isset($this->usedChunks[$index = Level::chunkHash($centerX + $z, $centerZ - $x - 1)]) or $this->usedChunks[$index] === false){
                        $newOrder[$index] = true;
                    }
                    unset($unloadChunks[$index]);

                    /* Bottom left quadrant mirror */
                    if(!isset($this->usedChunks[$index = Level::chunkHash($centerX - $z - 1, $centerZ - $x - 1)]) or $this->usedChunks[$index] === false){
                        $newOrder[$index] = true;
                    }
                    unset($unloadChunks[$index]);
                }
            }
        }

        foreach($unloadChunks as $index => $bool){
            Level::getXZ($index, $X, $Z);
            $this->unloadChunk($X, $Z);
        }

        $this->loadQueue = $newOrder;


        Timings::$playerChunkOrderTimer->stopTiming();

        return true;
    }

    /**
     * Batch a Data packet into the channel list to send at the end of the tick
     *
     * @param DataPacket $packet
     *
     * @return bool
     */
    public function batchDataPacket(DataPacket $packet){
        if($this->connected === false){
            return false;
        }

        $timings = Timings::getSendDataPacketTimings($packet);
        $timings->startTiming();
        $this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
        if($ev->isCancelled()){
            $timings->stopTiming();
            return false;
        }

        if(!isset($this->batchedPackets)){
            $this->batchedPackets = [];
        }

        $this->batchedPackets[] = clone $packet;
        $timings->stopTiming();
        return true;
    }

    /**
     * Sends an ordered DataPacket to the send buffer
     *
     * @param DataPacket $packet
     * @param bool       $needACK
     *
     * @return int|bool
     */
    public function dataPacket(DataPacket $packet, $needACK = false){
        if(!$this->connected){
            return false;
        }
        $timings = Timings::getSendDataPacketTimings($packet);
        $timings->startTiming();
        $this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
        if($ev->isCancelled()){
            $timings->stopTiming();
            return false;
        }
        // if(!($packet instanceof BatchPacket))var_dump(get_class($packet));
        // if(isset($this->aaa) and !($packet instanceof MovePlayerPacket or $packet instanceof BatchPacket)){
        // return false;
        // }
        $identifier = $this->interface->putPacket($this, $packet, $needACK, false);

        if($needACK and $identifier !== null){
            $this->needACK[$identifier] = false;
            $timings->stopTiming();
            return $identifier;
        }
        $timings->stopTiming();
        return true;
    }

    /**
     * @param DataPacket $packet
     * @param bool       $needACK
     *
     * @return bool|int
     */
    public function directDataPacket(DataPacket $packet, $needACK = false){
        if($this->connected === false){
            return false;
        }

        $timings = Timings::getSendDataPacketTimings($packet);
        $timings->startTiming();
        $this->server->getPluginManager()->callEvent($ev = new DataPacketSendEvent($this, $packet));
        if($ev->isCancelled()){
            $timings->stopTiming();
            return false;
        }

        $identifier = $this->interface->putPacket($this, $packet, $needACK, true);

        if($needACK and $identifier !== null){
            $this->needACK[$identifier] = false;

            $timings->stopTiming();
            return $identifier;
        }

        $timings->stopTiming();
        return true;
    }

    /**
     * @param Vector3 $pos
     *
     * @return boolean
     */
    public function sleepOn(Vector3 $pos){
        if(!$this->isOnline()){
            return false;
        }

        foreach($this->level->getNearbyEntities($this->boundingBox->grow(2, 1, 2), $this) as $p){
            if($p instanceof Player){
                if($p->sleeping !== null and $pos->distance($p->sleeping) <= 0.1){
                    return false;
                }
            }
        }
        $this->newProgress('甜美的梦');
        // $this->server->getPluginManager()->callEvent($ev = new PlayerBedEnterEvent($this, $this->level->getBlock($pos)));
        // if($ev->isCancelled()){
        // return false;
        // }

        $this->sleeping = clone $pos;

        $this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [$pos->x, $pos->y, $pos->z]);
        $this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, true, self::DATA_TYPE_BYTE);

        $this->setSpawn($pos);

        $this->level->sleepTicks = 60;


        return true;
    }

    /**
     * Sets the spawnpoint of the player (and the compass direction) to a Vector3, or set it on another world with a
     * Position object
     *
     * @param Vector3|Position $pos
     */
    public function setSpawn(Vector3 $pos){
        if(!($pos instanceof Position)){
            $level = $this->level;
        }else{
            $level = $pos->getLevel();
        }
        $this->spawnPosition = new WeakPosition($pos->x, $pos->y, $pos->z, $level);
        $pk = new SetSpawnPositionPacket();
        $pk->x = (int) $this->spawnPosition->x;
        $pk->y = (int) $this->spawnPosition->y;
        $pk->z = (int) $this->spawnPosition->z;
        $this->dataPacket($pk);
    }

    public function stopSleep(){
        if($this->sleeping instanceof Vector3){
            // $this->server->getPluginManager()->callEvent($ev = new PlayerBedLeaveEvent($this, $this->level->getBlock($this->sleeping)));

            $this->sleeping = null;
            $this->setDataProperty(self::DATA_PLAYER_BED_POSITION, self::DATA_TYPE_POS, [0, 0, 0]);
            $this->setDataFlag(self::DATA_PLAYER_FLAGS, self::DATA_PLAYER_FLAG_SLEEP, false, self::DATA_TYPE_BYTE);


            $this->level->sleepTicks = 0;

            $pk = new AnimatePacket();
            $pk->eid = $this->id;
            $pk->action = PlayerAnimationEvent::WAKE_UP;
            $this->dataPacket($pk);
        }

    }


    /**
     * @return int
     */
    public function getGamemode() : int{
        return $this->gamemode;
    }

    /**
     * @internal
     *
     * Returns a client-friendly gamemode of the specified real gamemode
     * This function takes care of handling gamemodes known to MCPE (as of 1.1.0.3, that includes Survival, Creative and Adventure)
     *
     * TODO: remove this when Spectator Mode gets added properly to MCPE
     *
     * @param int $gamemode
     *
     * @return int
     */
    public static function getClientFriendlyGamemode(int $gamemode) : int{
        $gamemode &= 0x03;
        if($gamemode === Player::SPECTATOR){
            return Player::CREATIVE;
        }

        return $gamemode;
    }
    /**
     * Sets the gamemode, and if needed, kicks the Player.
     *
     * @param int  $gm
     * @param bool $client if the client made this change in their GUI
     *
     * @return bool
     */
    public function setGamemode(int $gm, bool $client = false, $force = false){
        if(($this->level->getName()=='create' or $this->level->getName()=='pvp') and $force==false)return;
        if($gm < 0 or $gm > 3 or $this->gamemode === $gm or $gm === 2 or ($this->isDie!==false and !$force)){
            return false;
        }
        if($gm == 1 and !$this->isOp())return false;
        /* 现在不必要
		$this->server->getPluginManager()->callEvent($ev = new PlayerGameModeChangeEvent($this, $gm));
		if($ev->isCancelled()){
			if($client){ //gamemode change by client in the GUI
				$this->sendGamemode();
			}
			return false;
		}
		*/
        $jgm=$this->gamemode;
        if($this->gamemode===0 and $this->isDie===false){
            $this->namedtag->SurvivalInventory = new ListTag('SurvivalInventory', []);
            $this->namedtag->SurvivalInventory->setTagType(NBT::TAG_Compound);
            if($this->inventory !== null){
                //Hotbar
                for($slot = 0; $slot < $this->inventory->getHotbarSize(); ++$slot){
                    $inventorySlotIndex = $this->inventory->getHotbarSlotIndex($slot);
                    $item = $this->inventory->getItem($inventorySlotIndex);
                    $tag = $item->nbtSerialize($slot);
                    $tag->TrueSlot = new ByteTag('TrueSlot', $inventorySlotIndex);
                    $this->namedtag->SurvivalInventory[$slot] = $tag;
                }

                //Normal inventory
                $slotCount = $this->inventory->getSize() + $this->inventory->getHotbarSize();
                for($slot = $this->inventory->getHotbarSize(); $slot < $slotCount; ++$slot){
                    $item = $this->inventory->getItem($slot - $this->inventory->getHotbarSize());
                    //As NBT, real inventory slots are slots 9-44, NOT 0-35
                    $this->namedtag->SurvivalInventory[$slot] = $item->nbtSerialize($slot);
                }

                //Armour
                for($slot = 100; $slot < 104; ++$slot){
                    $item = $this->inventory->getItem($this->inventory->getSize() + $slot - 100);
                    if($item instanceof Item and $item->getId() !== Item::AIR){
                        $this->namedtag->SurvivalInventory[$slot] = $item->nbtSerialize($slot);
                    }
                }
            }
            $this->inventory->clearAll();
        }elseif($gm===0 and $this->isDie===false){
            $this->removeAllEffects();
            $this->inventory->clearAll();
            $inventoryContents = ($this->namedtag->SurvivalInventory ?? null);
            $this->inventory->setInv($inventoryContents);
        }
        $this->gamemode = $gm;

        $this->setAllowFlight($this->isCreative() or $this->isSpectator());
        if($this->isSpectator()){
            $this->flying = true;
            $this->despawnFromAll();

            // Client automatically turns off flight controls when on the ground.
            // A combination of this hack and a new AdventureSettings flag FINALLY
            // fixes spectator flight controls. Thank @robske110 for this hack.
            $this->teleport($this->temporalVector->setComponents($this->x, $this->y + 0.1, $this->z),$this->getYaw(),$this->getPitch(),false, true);
        }else{
            if($this->isSurvival()){
                if($this->canFly and !in_array($this->level->getName(), ['boss', 'pvp', 'pve']))
                    $this->setAllowFlight(true);
                else{
                    $this->setAllowFlight(false);
                    $this->setFlying(false);
                }
            }
            $this->spawnToAll();
        }

        $this->resetFallDistance();

        $this->namedtag->playerGameType = new IntTag('playerGameType', $this->gamemode);

        if(!$client){ //Gamemode changed by server, do not send for client changes
            $this->sendGamemode();
        }else{
            Command::broadcastCommandMessage($this, new TranslationContainer('commands.gamemode.success.self', [Server::getGamemodeString($gm)]));
        }

        if($this->gamemode === Player::SPECTATOR){
            $pk = new ContainerSetContentPacket();
            $pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
            $this->dataPacket($pk);
        }else{
            $pk = new ContainerSetContentPacket();
            $pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
            $pk->slots = array_merge(Item::getCreativeItems(), $this->personalCreativeItems);
            $this->dataPacket($pk);
        }

        $this->sendSettings();
        if($gm === 3 and $jgm === 0 and $this->isDie!==false){
            $this->inventory->sendNullContents($this);
        }else{
            $this->inventory->sendContents($this);
            $this->inventory->sendContents($this->getViewers());
            $this->inventory->sendHeldItem($this->getViewers());
        }
        return true;
    }

    /**
     * @internal
     * Sends the player's gamemode to the client.
     */
    public function sendGamemode(){
        $pk = new SetPlayerGameTypePacket();
        $pk->gamemode = Player::getClientFriendlyGamemode($this->gamemode);
        $this->dataPacket($pk);
    }

    /**
     * Sends all the option flags
     */
    public function sendSettings(){
        $pk = new AdventureSettingsPacket();
        $pk->flags = 0;
        $pk->worldImmutable = $this->isAdventure();
        $pk->autoJump = $this->autoJump;
        $pk->allowFlight = $this->getAllowFlight();
        $pk->noClip = $this->isSpectator();
        $pk->worldBuilder = !($this->isAdventure());
        $pk->isFlying = $this->flying;
        $pk->userPermission = ($this->isOp() ? AdventureSettingsPacket::PERMISSION_OPERATOR : AdventureSettingsPacket::PERMISSION_NORMAL);
        $this->dataPacket($pk);
    }

    /**
     * @return bool
     */
    public function isSurvival() : bool{
        return ($this->gamemode & 0x01) === 0;
    }

    /**
     * @return bool
     */
    public function isCreative() : bool{//
        return ($this->gamemode & 0x01) > 0;
    }

    /**
     * @return bool
     */
    public function isSpectator() : bool{
        return $this->gamemode === 3;
    }

    /**
     * @return bool
     */
    public function isAdventure() : bool{//冒险。
        return ($this->gamemode & 0x02) > 0;
    }

    /**
     * @return bool
     */
    public function isFireProof() : bool{
        return $this->isCreative();
    }

    /**
     * @return array
     */
    public function getDrops(){
        if(!$this->isCreative()){
            return parent::getDrops();
        }

        return [];
    }

    /**
     * @param int   $id
     * @param int   $type
     * @param mixed $value
     *
     * @return bool
     */
    public function setDataProperty($id, $type, $value){
        if(parent::setDataProperty($id, $type, $value)){
            $this->sendData($this, [$id => $this->dataProperties[$id]]);
            return true;
        }

        return false;
    }

    /**
     * @param $movX
     * @param $movY
     * @param $movZ
     * @param $dx
     * @param $dy
     * @param $dz
     */
    protected function checkGroundState($movX, $movY, $movZ, $dx, $dy, $dz){
        if(!$this->onGround or $movY != 0){
            $bb = clone $this->boundingBox;
            $bb->maxY = $bb->minY + 0.5;
            $bb->minY -= 1;
            if(count($this->level->getCollisionBlocks($bb, true)) > 0){
                $this->onGround = true;
                $this->knockBack = false;
            }else{
                $this->onGround = false;
            }
        }
        $this->isCollided = $this->onGround;
    }

    protected function checkBlockCollision(){
        foreach($blocksaround = $this->getBlocksAround() as $block){
            $block->onEntityCollide($this);
            if($this->getServer()->redstoneEnabled){
                if($block instanceof PressurePlate){
                    $this->activatedPressurePlates[Level::blockHash($block->x, $block->y, $block->z)] = $block;
                }
            }
        }

        if($this->getServer()->redstoneEnabled){
            /** @var \pocketmine\block\PressurePlate $block * */
            foreach($this->activatedPressurePlates as $key => $block){
                if(!isset($blocksaround[$key])) $block->checkActivation();
            }
        }
    }

    /**
     * @param $tickDiff
     */
    protected function checkNearEntities($tickDiff){
        foreach($this->level->getNearbyEntities($this->boundingBox->grow(0.5, 0.5, 0.5), $this) as $entity){
            $entity->scheduleUpdate();

            if(!$entity->isAlive()){
                continue;
            }

            if($entity instanceof Arrow and $entity->hadCollision){
                $item = Item::get(Item::ARROW, $entity->getPotionId(), 1);

                $add = false;
                if(!$this->server->allowInventoryCheats and !$this->isCreative()){
                    if(!$this->getFloatingInventory()->canAddItem($item) or !$this->inventory->canAddItem($item)){
                        //The item is added to the floating inventory to allow client to handle the pickup
                        //We have to also check if it can be added to the real inventory before sending packets.
                        continue;
                    }
                    $add = true;
                }

                // $this->server->getPluginManager()->callEvent($ev = new InventoryPickupArrowEvent($this->inventory, $entity));
                // if($ev->isCancelled()){
                // continue;
                // }

                $pk = new TakeItemEntityPacket();
                $pk->eid = $this->id;
                $pk->target = $entity->getId();
                $this->server->broadcastPacket($entity->getViewers(), $pk);

                if($add){
                    $this->getFloatingInventory()->addItem(clone $item);
                }
                $entity->kill();
            }elseif($entity instanceof DroppedItem){
                if($entity->getPickupDelay() <= 0){
                    $item = $entity->getItem();
                    if(($entity->getOwner()!==null and $entity->getOwner()!==strtolower($this->username) and $entity->getAge()<200) or (isset($entity->isOpItem) and !$this->isop()))continue;
                    if($item instanceof Item){
                        $add = false;
                        if(!$this->server->allowInventoryCheats and !$this->isCreative()){
                            if(!$this->getFloatingInventory()->canAddItem($item) or !$this->inventory->canAddItem($item)){
                                continue;
                            }
                            $add = true;
                        }

                        // $this->server->getPluginManager()->callEvent($ev = new InventoryPickupItemEvent($this->inventory, $entity));
                        // if($ev->isCancelled()){
                        // continue;
                        // }

                        $pk = new TakeItemEntityPacket();
                        $pk->eid = $this->id;
                        $pk->target = $entity->getId();
                        $this->server->broadcastPacket($entity->getViewers(), $pk);

                        if($add){
                            $this->getFloatingInventory()->addItem(clone $item);
                            $this->server->getLogger()->addData($this, $entity, 'PickUp');
                        }
                        $entity->kill();
                    }
                }
            }
        }
    }
    public function isPortal(){
        $blocks = $this->getBlocksAround();
        // var_dump($blocks);
        foreach($blocks as $block){
            if($block instanceof EndGateway or $block instanceof EndPortal){
                return true;
            }
        }
    }

    /**
     * @param $tickDiff
     */
    protected function processMovement($tickDiff){
        if($this->getlinkedEntity() instanceof \LTCraft\Chair and $this->newPosition === null){
            $this->newPosition = $this->asPosition();
        }
        if(!$this->isAlive() or !$this->spawned or $this->newPosition === null or $this->teleportPosition !== null or $this->isSleeping()){
            return;
        }
        /*
		var_dump($this->pitch);
		var_dump($this->yaw);
		*/
        $newPos = $this->newPosition;
        $distanceSquared = $newPos->distanceSquaredNoY($this);
        $distanceSquared2 = $newPos->distanceSquared($this);
        $revert = false;
        $n = PHP_INT_MAX;
        if($this->moveCheck and $this->level->getName()!='zc' and $this->getlinkedEntity()===null){
            $s=0.9;
            if($this->hasEffect(Effect::JUMP))$s+=$this->getEffect(Effect::JUMP)->getAmplifier()*1.65;
            $s+=microtime(true)>($this->getServer()->nextTick+0.05)?PHP_INT_MAX:0;
            if($newPos->y>$this->y)//在跳
                if(pow($newPos->y - $this->y, 2)>$s and $this->knockBack===false)
                    $revert = true;
            $n=$tickDiff*1;
            if($this->isSprinting())$n+=0.3;
            if($this->isSneaking())$n/=3;
            if($this->isFlying())$n=10*$tickDiff;
            elseif(!$this->onGround)$n=20*$tickDiff;
            $n+=microtime(true)>($this->getServer()->nextTick+0.05)?PHP_INT_MAX:0;
            if($this->hasEffect(Effect::SPEED))$n+=$this->getEffect(Effect::SPEED)->getAmplifier()*1.65*$tickDiff;
        }
        if(($this->moveCheck and $this->level->getName()!='zc' and ($distanceSquared / ($tickDiff ** 2)) > $n and $this->knockBack===false) or $revert===true)
            $revert = true;
        else{
            if($this->chunk === null or !$this->chunk->isGenerated()){

                $chunk = $this->level->getChunk($newPos->x >> 4, $newPos->z >> 4, false);
                if($chunk === null or !$chunk->isGenerated()){
                    $revert = true;
                    $this->nextChunkOrderRun = 0;
                }else{
                    if($this->chunk !== null){
                        $this->chunk->removeEntity($this);
                    }
                    $this->chunk = $chunk;
                }
            }
        }

        if(!$revert and $distanceSquared2 != 0){
            $dx = $newPos->x - $this->x;
            $dy = $newPos->y - $this->y;
            $dz = $newPos->z - $this->z;

            $this->move($dx, $dy, $dz);

            $diffX = round($this->x - $newPos->x,2);

            $diffY = round($this->y - $newPos->y,2);
            $diffZ = round($this->z - $newPos->z,2);

            $diff = round(($diffX ** 2 + $diffY ** 2 + $diffZ ** 2) / ($tickDiff ** 2),2);
            /*
			if($this->isSurvival() and !$revert and $diff > $m){
				$ev = new PlayerIllegalMoveEvent($this, $newPos);
				$ev->setCancelled($this->allowMovementCheats);

				$this->server->getPluginManager()->callEvent($ev);

				if(!$ev->isCancelled()){
					// $revert = true;
					 //$this->server->getLogger()->warning($this->getServer()->getLanguage()->translateString('pocketmine.player.invalidMove', [$this->getName()]));
				}
			}*/

            if($diff > 0){
                $this->x = $newPos->x;
                $this->y = $newPos->y;
                $this->z = $newPos->z;
                $radius = $this->width / 2;
                $this->boundingBox->setBounds($this->x - $radius, $this->y, $this->z - $radius, $this->x + $radius, $this->y + $this->height, $this->z + $radius);
            }
        }

        $from = new Location($this->lastX, $this->lastY, $this->lastZ, $this->lastYaw, $this->lastPitch, $this->level);
        $to = $this->getLocation();

        $delta = pow($this->lastX - $to->x, 2) + pow($this->lastY - $to->y, 2) + pow($this->lastZ - $to->z, 2);
        $deltaAngle = abs($this->lastYaw - $to->yaw) + abs($this->lastPitch - $to->pitch);

        if(!$revert and ($delta > 0.0001 or $deltaAngle > 1.0)){
            // if(pow($this->lastX - $to->x, 2) +  pow($this->lastZ - $to->z, 2)>0.0001)$this->lastMoveTick=$this->server->getTick();
            $isFirst = ($this->lastX === null or $this->lastY === null or $this->lastZ === null);

            $this->lastX = $to->x;
            $this->lastY = $to->y;
            $this->lastZ = $to->z;

            $this->lastYaw = $to->yaw;
            $this->lastPitch = $to->pitch;
            // echo $this->getYaw() .':'. $this->getPitch() . PHP_EOL;
            if(!$isFirst){
                $ev = new PlayerMoveEvent($this, $from, $to);
                $this->setMoving(true);
                if($this->vertigoTime>0)$ev->setCancelled();
                else
                    $this->server->getPluginManager()->callEvent($ev);
                if(!($revert = $ev->isCancelled())){ //Yes, this is intended
                    if($this->isInsideOfPortal()){
                        if($this->portalTime == 0){
                            $this->portalTime = $this->server->getTick();
                        }
                    }else{
                        $this->portalTime = 0;
                    }
                    if($this->isPortal()){
                        if(\LTCraft\Main::getInstance()->triggerTeleport($this)===false){
                            $this->teleport($this->level->getSafeSpawn(),null,null,false,true);
                        }
                    }

                    if($to->distanceSquared($ev->getTo()) > 0.01){ //If plugins modify the destination
                        $this->teleport($ev->getTo());
                    }else{
                        $this->level->addEntityMovement($this->x >> 4, $this->z >> 4, $this->getId(), $this->x, $this->y + $this->getEyeHeight(), $this->z, $this->yaw, $this->pitch, $this->yaw);
                    }
                    // $this->lastMove = $this->server->getTick();
                }
            }

            if(!$this->isSpectator()){
                $this->checkNearEntities($tickDiff);
            }

            $this->speed = ($to->subtract($from))->divide($tickDiff);
        }elseif($distanceSquared == 0){
            $this->speed = new Vector3(0, 0, 0);
            $this->setMoving(false);
        }

        if($revert){

            $this->lastX = $from->x;
            $this->lastY = $from->y;
            $this->lastZ = $from->z;

            $this->lastYaw = $from->yaw;
            $this->lastPitch = $from->pitch;

            $this->sendPosition($from, $from->yaw, $from->pitch, MovePlayerPacket::MODE_RESET);
            $this->forceMovement = new Vector3($from->x, $from->y, $from->z);
        }else{
            $this->forceMovement = null;
            if($distanceSquared != 0 and $this->nextChunkOrderRun > 20){
                $this->nextChunkOrderRun = 20;
            }
        }

        $this->newPosition = null;
    }


    /**
     * @param Vector3 $mot
     *
     * @return bool
     */
    public function setMotion(Vector3 $mot){
        if(parent::setMotion($mot)){
            if($this->chunk !== null){
                $this->level->addEntityMotion($this->chunk->getX(), $this->chunk->getZ(), $this->getId(), $this->motionX, $this->motionY, $this->motionZ);
                $pk = new SetEntityMotionPacket();
                $pk->eid = $this->id;
                $pk->motionX = $mot->x;
                $pk->motionY = $mot->y;
                $pk->motionZ = $mot->z;
                $this->dataPacket($pk);
            }

            if($this->motionY > 0){
                $this->startAirTicks = (-(log($this->gravity / ($this->gravity + $this->drag * $this->motionY))) / $this->drag) * 2 + 5;
            }

            return true;
        }
        return false;
    }


    protected function updateMovement(){

    }

    public $foodTick = 0;

    public $starvationTick = 0;

    public $foodUsageTime = 0;

    protected $moving = false;

    /**
     * @param $moving
     */
    public function setMoving($moving){
        $this->moving = $moving;
    }
    /**
     * @param bool $sendAll
     */
    public function sendAttributes(bool $sendAll = false){
        $entries = $sendAll ? $this->attributeMap->getAll() : $this->attributeMap->needSend();
        if(count($entries) > 0){
            $pk = new UpdateAttributesPacket();
            $pk->entityId = $this->id;
            $pk->entries = $entries;
            $this->dataPacket($pk);
            foreach($entries as $entry){
                $entry->markSynchronized();
            }
        }
    }

    /**
     * tick背包物品 唉
     */
    public  function tickInventory(){
        foreach ($this->inventory->getContents() as $index => $item){
            if ($item instanceof Mana){
                /** @var Mana $item */
                $item->onTick($this, $index,  $this->inventory);
            }
        }
        foreach ($this->ornamentsInventory->getContents() as $index => $item){
            if ($item instanceof Mana){
                /** @var Mana $item */
                $item->onTick($this, $index,  $this->ornamentsInventory);
            }
        }
    }

    /**
     * @param $currentTick
     *
     * @return bool
     */
    public function onUpdate($currentTick){
        if(!$this->loggedIn){
            return false;
        }

        $tickDiff = $currentTick - $this->lastUpdate;

        if($tickDiff <= 0){
            return true;
        }

        $this->messageCounter = 2;

        $this->lastUpdate = $currentTick;

        $this->sendAttributes();

        if(!$this->isAlive() and $this->spawned){
            ++$this->deadTicks;
            if($this->deadTicks >= 10){
                $this->despawnFromAll();
            }
            return true;
        }

        $this->timings->startTiming();
        if($this->spawned){
            if((($this->isCreative() or $this->isSurvival()) and ($this->server->getTick() - $this->portalTime >= 80)) and $this->portalTime > 0){
                if(\LTCraft\Main::getInstance()->triggerTeleport($this)===false){
                    $this->teleport($this->level->getSafeSpawn(),null,null,false,true);
                }
                $this->portalTime = 0;
            }

            $this->processMovement($tickDiff);//移动监测
            $this->entityBaseTick($tickDiff);//实体刷新
            if($this->freezeTime>0)
                if(--$this->freezeTime<=0)
                    $this->setDataFlag(self::DATA_FLAGS,self::DATA_FLAG_IMMOBILE, false);
            if($this->injuredTime>0)
                --$this->injuredTime;
            if($this->vertigoTime>0){
                --$this->vertigoTime;
                $this->yaw+=60;
                $this->pitch=0;
                $this->forceUpdateMovement();
                if($this->freezeTime===0)
                    $this->setDataFlag(self::DATA_FLAGS,self::DATA_FLAG_IMMOBILE, false);
            }
            $this->tickInventory();
            // $this->setVertigo(1);
            // $this->yaw+=60;
            // $this->pitch=0;
            // $this->forceUpdateMovement();
            if(($open=\LTMenu\Main::getInstance()->getOpen($this))!==null and $open->getDieProgress()>5){
                $this->isDie=60;
                $this->setGamemode(3, false, true);
                $this->addTitle('§l§c你死了！','§l§d3秒后复活',50,100,50);
                unset(\LTMenu\Main::getInstance()->opens[$this->username]);
            }
            if($this->isDie!==false){//复活
                if(--$this->isDie==40){
                    $this->addTitle('§l§c你死了！','§l§d2秒后复活',0,100,50);
                }elseif($this->isDie==20){
                    $this->addTitle('§l§c你死了！','§l§d1秒后复活',0,100,50);
                }elseif($this->isDie==0){
                    if($this->getLevel()->getName()=='boss'){
                        if(--$this->getLevel()->GamePlayers[strtolower($this->username)]>=0){
                            $this->teleport($this->level->getSafeSpawn(),null,null,false,true);
                            $this->sendMessage('§l§a[提示]§a成功复活,你还有'.$this->getLevel()->GamePlayers[strtolower($this->username)].'次复活机会！');
                            $this->addTitle('§l§a成功复活','§l§d继续加油吧！',50,100,50);
                            $this->setAllowFlight(false);
                            $this->setFlying(false);
                        }else{
                            $pos=$this->server->getDefaultLevel()->getSafeSpawn();
                            $this->teleport(Location::fromObject($pos, $pos->level, 177, 0),null,null,false);//747:4:16:zc
                        }
                    }elseif($this->getLevel()->getName()=='pvp'){
                        $this->teleport($this->level->getSafeSpawn(),null,null,false,true);
                        $this->addTitle('§l§a成功复活','§l§d继续加油吧！',50,100,50);
                        $this->setAllowFlight(false);
                        $this->setFlying(false);
                    }elseif(in_array($this->getLevel()->getName(), ['zy', 'nether', 'ender', 'land', 'dp', 'jm', 'create'])){
                        $this->sendMessage('§l§a[提示]§a输入/back，返回死亡点');
                        $pos=$this->server->getDefaultLevel()->getSafeSpawn();
                        $this->teleport(Location::fromObject($pos, $pos->level, 177, 0),null,null,false);//747:4:16:zc
                    }else{
                        $cause = $this->getLastDamageCause();
                        $this->sendMessage('§l§a[提示]§a输入/back，返回死亡点');
                        if($cause instanceof EntityDamageByEntityEvent and $cause->getDamager()  instanceof Player){
                            $pos=$this->server->getDefaultLevel()->getSafeSpawn();
                            $this->teleport(Location::fromObject($pos, $pos->level, 177, 0),null,null,false);//747:4:16:zc
                        }else{
                            $this->teleport($this->level->getSafeSpawn(),null,null,false,true);
                            $this->setFlying(false);
                            $this->addTitle('§l§a成功复活','§l§d愿光明女神与您同在！',50,100,50);
                        }
                    }
                    $this->setGamemode($this->dieMessage[0], false, true);
                    $this->isDie=false;
                    $this->extinguish();
                    $this->setHealth($this->getMaxHealth());
                    $this->setFood(20);
                    $this->getBuff()->runEffect();
                    $this->server->removePlayerListData($this->dieMessage[1], $this->dieMessage[3]);
                    $pk = new RemoveEntityPacket();
                    $pk->eid = $this->dieMessage[2];
                    $this->server->broadcastPacket($this->dieMessage[3],$pk);
                }
            }
            if($this->isOnFire() or $this->lastUpdate % 10 == 0){
                if($this->isCreative() and !$this->isInsideOfFire()){
                    $this->extinguish();
                }elseif($this->getLevel()->getWeather()->isRainy()){
                    if($this->getLevel()->canBlockSeeSky($this)){
                        $this->extinguish();
                    }
                }
            }

            if(!$this->isSpectator() and $this->speed !== null){
                // if($this->hasEffect(Effect::LEVITATION)){
                // $this->inAirTicks = 0;
                // }
                if($this->onGround){
                    if($this->inAirTicks !== 0){
                        $this->startAirTicks = 5;
                    }
                    $this->inAirTicks = 0;
                }else{
                    if($this->getInventory()->getItem($this->getInventory()->getSize() + 1)->getId() == '444'){
                        #enable use of elytra. todo: check if it is open
                        $this->inAirTicks = 0;
                    }
                    if(!$this->getAllowFlight() and $this->inAirTicks > 10 and !$this->isSleeping() and !$this->isImmobile()){
                        $expectedVelocity = (-$this->gravity) / $this->drag - ((-$this->gravity) / $this->drag) * exp(-$this->drag * ($this->inAirTicks - $this->startAirTicks));
                        $diff = ($this->speed->y - $expectedVelocity) ** 2;

                        if(!$this->hasEffect(Effect::JUMP) and !$this->hasEffect(Effect::FLOATING) and $diff > 0.6 and $expectedVelocity < $this->speed->y){
                            // if($this->inAirTicks < 100){
                            $this->setMotion(new Vector3(0, $expectedVelocity, 0));
                            // }elseif($this->kick('服务器是不允许非法飞行的！',false)){
                            // $this->timings->stopTiming();
                            // return false;
                            // }
                        }
                    }

                    ++$this->inAirTicks;
                }
            }

            if($this->getTransactionQueue() !== null){
                $this->getTransactionQueue()->execute();
            }
        }

        $this->checkTeleportPosition();

        $this->timings->stopTiming();

        if(count($this->messageQueue) > 0){//排队
            $pk = new TextPacket();
            $pk->type = TextPacket::TYPE_RAW;
            $pk->message = implode("§r\n", $this->messageQueue);
            $this->dataPacket($pk);
            $this->messageQueue = [];
        }

        return true;
    }

    public function checkNetwork(){
        if(!$this->isOnline()){
            return;
        }
        if($this->nextChunkOrderRun-- <= 0 or $this->chunk === null){
            $this->orderChunks();
        }

        if(count($this->loadQueue) > 0 or !$this->spawned){
            $this->sendNextChunk();
        }

        if(count($this->batchedPackets) > 0){
            $this->server->batchPackets([$this], $this->batchedPackets, false);
            $this->batchedPackets = [];
        }

    }

    /**
     * @param Vector3 $pos
     * @param         $maxDistance
     * @param float   $maxDiff
     *
     * @return bool
     */
    public function canInteract(Vector3 $pos, $maxDistance, $maxDiff = 1){
        $eyePos = $this->getPosition()->add(0, $this->getEyeHeight(), 0);
        if($eyePos->distanceSquared($pos) > $maxDistance ** 2){
            return false;
        }

        $dV = $this->getDirectionPlane();
        $dot = $dV->dot(new Vector2($eyePos->x, $eyePos->z));
        $dot1 = $dV->dot(new Vector2($pos->x, $pos->z));
        return ($dot1 - $dot) >= -$maxDiff;
    }

    public function onPlayerPreLogin(){
        $pk = new PlayStatusPacket();
        $pk->status = PlayStatusPacket::LOGIN_SUCCESS;
        $this->dataPacket($pk);

        $this->processLogin();
    }

    public function clearCreativeItems(){
        $this->personalCreativeItems = [];
    }

    /**
     * @return array
     */
    public function getCreativeItems() : array{
        return $this->personalCreativeItems;
    }

    /**
     * @param Item $item
     */
    public function addCreativeItem(Item $item){
        $this->personalCreativeItems[] = Item::get($item->getId(), $item->getDamage());
    }

    /**
     * @param Item $item
     */
    public function removeCreativeItem(Item $item){
        $index = $this->getCreativeItemIndex($item);
        if($index !== -1){
            unset($this->personalCreativeItems[$index]);
        }
    }

    /**
     * @param Item $item
     *
     * @return int
     */
    public function getCreativeItemIndex(Item $item) : int{
        foreach($this->personalCreativeItems as $i => $d){
            if($item->equals($d, !$item->isTool())){
                return $i;
            }
        }

        return -1;
    }

    protected function processLogin(){
        if(!$this->server->isWhitelisted(strtolower($this->getName()))){
            $this->close($this->getLeaveMessage(), '服务器白名单限制', true, true);

            return;
        }elseif($this->server->getNameBans()->isBanned(strtolower($this->getName())) or $this->server->getIPBans()->isBanned($this->getAddress()) or $this->server->getCIDBans()->isBanned($this->randomClientId)){
            $this->close($this->getLeaveMessage(), TextFormat::RED . 'LTCraft: 你已被服务器断开连接', true, true);
            $this->close($this->getLeaveMessage(), TextFormat::RED . '我们检测到您有违规行为，出于对服务器安全的考虑，已将您永久禁封', true, true);
            $this->close($this->getLeaveMessage(), TextFormat::RED . '详情请加QQ群：862859409 您也可以加服主QQ:2665337794', true, true);

            return;
        }

        if($this->hasPermission(Server::BROADCAST_CHANNEL_USERS)){
            $this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_USERS, $this);
        }
        if($this->hasPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE)){
            $this->server->getPluginManager()->subscribeToPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);
        }

        foreach($this->server->getOnlinePlayers() as $p){
            if($p !== $this and strtolower($p->getName()) === strtolower($this->getName())){
                if($p->kick('从另一个地方登陆') === false){
                    $this->close($this->getLeaveMessage(), '从另一个地方登陆', true, true);
                    return;
                }
            }elseif($p->loggedIn and $this->getUniqueId()->equals($p->getUniqueId())){
                if($p->kick('从另一个地方登陆') === false){
                    $this->close($this->getLeaveMessage(), '从另一个地方登陆', true, true);
                    return;
                }
            }
        }
        $this->setNameTag($this->getDisplayName());
        /*$sql='select moveCheck,name from user where name=''.strtolower($this->username).'' LIMIT 1';
		$this->getServer()->dataBase->pushService('2'.chr(4).$sql);
		*/
        $nbt = $this->server->getOfflinePlayerData($this->username);
        if(!($nbt instanceof CompoundTag)){
            $this->close($this->getLeaveMessage(), '数据损坏！', true, true);
            return;
        }
        $this->playedBefore = ($nbt['lastPlayed'] - $nbt['firstPlayed']) > 1;
        if(!isset($nbt->NameTag)){
            $nbt->NameTag = new StringTag('NameTag', $this->username);
        }else{
            $nbt['NameTag'] = $this->username;
        }

        $this->gamemode = $nbt['playerGameType'] & 0x03;
        // if($this->server->getForceGamemode()){
        // $this->gamemode = $this->server->getGamemode();
        // $nbt->playerGameType = new IntTag('playerGameType', $this->gamemode);
        // }
        if(!isset($nbt['Level'])){
            \LTLogin\Events::$status[strtolower($this->username)]='notDataS';//无数据等待同步
            $this->setLevel($this->server->getDefaultLevel());
            $nbt['Level']=new StringTag('Level', 'zc');
            $nbt['Level'] = new StringTag('Level', 'zc');
            $nbt['Pos']=new ListTag('Pos', [
                new DoubleTag(0, $this->level->getSafeSpawn()->x),
                new DoubleTag(1, $this->level->getSafeSpawn()->y),
                new DoubleTag(2, $this->level->getSafeSpawn()->z)
            ]);
        }else{
            \LTLogin\Events::$status[strtolower($this->username)]='hasDataS';//有数据等待同步
            $pos=$this->server->getLevelByName('login')->getSafeSpawn();
            $this->setLevel($this->server->getLevelByName('login'));
            $nbt['Level'] = new StringTag('Level', 'login');
            $nbt['Pos'][0] = $pos->x;
            $nbt['Pos'][1] = $pos->y;
            $nbt['Pos'][2] = $pos->z;
        }
        $nbt['MaxHealth']=20+$nbt['Grade']*2;
        if($nbt['Role']=='战士')$nbt['MaxHealth']+=(int)($nbt['Grade']/2);
        $nbt['MaxHealth']+=$nbt['AdditionalHealth'];
        $this->setMaxHealth($nbt['MaxHealth']);
        $this->setHealth((($nbt['Health']>$nbt['MaxHealth'] or $nbt['Health']<=0)?$nbt['MaxHealth']:$nbt['Health']));
        $nbt->lastPlayed = new LongTag('lastPlayed', floor(microtime(true) * 1000));

        if(isset(\LTItem\Main::getInstance()->Buff[strtolower($this->username)]) and \LTItem\Main::getInstance()->Buff[strtolower($this->username)]->get('启用', true))
            $this->setBuff(new \LTItem\Buff($this, \LTItem\Main::getInstance()->Buff[strtolower($this->username)]->getAll()));
        else
            $this->setBuff(new \LTItem\Buff($this));
        parent::__construct($this->level, $nbt);
        $this->loggedIn = true;
        $this->server->addOnlinePlayer($this);

        if(!$this->isConnected()){
            return;
        }

        $this->dataPacket(new ResourcePacksInfoPacket());

        if(!$this->hasValidSpawnPosition() and isset($this->namedtag->SpawnLevel) and ($level = $this->server->getLevelByName('zc')) instanceof Level){
            $spos=$this->server->getDefaultLevel()->getSafeSpawn();
            $this->spawnPosition = new WeakPosition($spos->getX(),$spos->getY(), $spos->getZ(), $level);
        }
        $spawnPosition = $this->getSpawn();

        $pk = new StartGamePacket();
        $pk->entityUniqueId = $this->id;
        $pk->entityRuntimeId = $this->id;
        $pk->playerGamemode = Player::getClientFriendlyGamemode($this->gamemode);
        $pk->x = $this->x;
        $pk->y = $this->y;
        $pk->z = $this->z;
        $pk->pitch = $this->pitch;
        $pk->yaw = $this->yaw;
        $pk->seed = -1;
        $pk->dimension = $this->level->getDimension();
        $pk->worldGamemode = Player::getClientFriendlyGamemode($this->server->getGamemode());
        $pk->difficulty = $this->server->getDifficulty();
        $pk->spawnX = $spawnPosition->getFloorX();
        $pk->spawnY = $spawnPosition->getFloorY();
        $pk->spawnZ = $spawnPosition->getFloorZ();
        $pk->hasAchievementsDisabled = 1;
        $pk->dayCycleStopTime = -1; //TODO: implement this properly
        $pk->eduMode = 0;
        $pk->rainLevel = 0; //TODO: implement these properly
        $pk->lightningLevel = 0;
        $pk->commandsEnabled = 1;
        $pk->levelId = '';
        $pk->worldName = $this->server->getMotd();
        $this->dataPacket($pk);

        $this->server->getPluginManager()->callEvent($ev = new PlayerLoginEvent($this, 'Plugin reason'));
        if($ev->isCancelled()){
            $this->close($this->getLeaveMessage(), $ev->getKickMessage());
            return;
        }

        $pk = new SetTimePacket();
        $pk->time = $this->level->getTime();
        $pk->started = $this->level->stopTime == false;
        $this->dataPacket($pk);

        $this->sendAttributes(true);
        $this->setNameTagVisible(true);
        $this->setNameTagAlwaysVisible(true);
        $this->setCanClimb(true);
        switch($this->deviceOS){
            case 1:
                $os = 'Android';
                break;
            case 7:
                $os = 'Windows10';
                break;
            default:
                $os = 'IOS';
        }
        $this->server->getLogger()->info($this->getServer()->getLanguage()->translateString('pocketmine.player.logIn', [
            TextFormat::AQUA . $this->username . TextFormat::WHITE,
            $this->ip,
            $this->id,
            TextFormat::LIGHT_PURPLE .$this->phone,
            TextFormat::LIGHT_PURPLE .$os,
        ]));
        /*if($this->isOp()){
			$this->setRemoveFormat(false);
		}*/
        if($this->gamemode === Player::SPECTATOR){
            $pk = new ContainerSetContentPacket();
            $pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
            $this->dataPacket($pk);
        }else{
            $pk = new ContainerSetContentPacket();
            $pk->windowid = ContainerSetContentPacket::SPECIAL_CREATIVE;
            $pk->slots = array_merge(Item::getCreativeItems(), $this->personalCreativeItems);
            $this->dataPacket($pk);
        }

        $this->sendCommandData();

        $this->level->getWeather()->sendWeather($this);
        $this->forceMovement = $this->teleportPosition = $this->getPosition();
    }

    /**
     * 重新计算护甲值和生命值
     */
    public function recalculateHealthAndArmorV(){
        $this->recalculateHealth();
        $this->recalculateArmorV();
    }
    /**
     * 重新计算生命值
     */
    public function recalculateHealth(){
        $max=20+$this->getGrade()*2;
        if($this->getRole()=='战士')$max+=(int)($this->getGrade()/2);
        $max+=$this->getAdditionalHealth();
        $this->setMaxHealth($max);
        $this->setHealth((($this->getHealth()>$max)?$max:$this->getHealth()));
    }

    /**
     * 重新计算护甲值
     */
    public function recalculateArmorV(){
        $armors = $this->inventory->getArmorContents();
        $armorV = 0;
        foreach ($armors as $armor){
            if ($armor instanceof Armor){
                $armorV += $armor->getArmorV();
            }
        }
        $armorV += (int)$this->getGrade()/2;
        $this->armorV = $armorV;
        $this->getAPI()->update(API::ARMOR);
    }
    /**
     * @return mixed
     */
    public function getProtocol(){
        return $this->protocol;
    }

    /**
     * Handles a Minecraft packet
     * TODO: Separate all of this in handlers
     *
     * WARNING: Do not use this, it's only for internal use.
     * Changes to this function won't be recorded on the version.
     *
     * @param DataPacket $packet
     */
    public function handleDataPacket(DataPacket $packet){
        if($this->connected === false){
            return;
        }
        //if(!($packet instanceof InteractPacket) and !($packet instanceof BatchPacket))echo get_class($packet).PHP_EOL;
        if($packet::NETWORK_ID === 0xfe){
            /** @var BatchPacket $packet */
            $this->server->getNetwork()->processBatch($packet, $this);
            return;
        }

        $timings = Timings::getReceiveDataPacketTimings($packet);

        $timings->startTiming();

        $this->server->getPluginManager()->callEvent($ev = new DataPacketReceiveEvent($this, $packet));
        if($ev->isCancelled()){
            $timings->stopTiming();
            return;
        }
        switch($packet::NETWORK_ID){
            case ProtocolInfo::LEVEL_SOUND_EVENT_PACKET:
                $this->level->addChunkPacket($packet->x >> 4, $packet->z >> 4, $packet);
                break;
            case ProtocolInfo::PLAYER_INPUT_PACKET:
                break;
            case ProtocolInfo::LOGIN_PACKET:
                if($this->loggedIn){
                    break;
                }
                $pk = new PlayStatusPacket();
                $pk->status = PlayStatusPacket::LOGIN_SUCCESS;
                $this->dataPacket($pk);

                $this->username = TextFormat::clean($packet->username);
                $this->phone = $packet->deviceModel;
                $this->displayName = $this->username;
                $this->setNameTag($this->username);
                $this->iusername = strtolower($this->username);
                $this->protocol = $packet->protocol;
                $this->deviceModel = $packet->deviceModel;
                $this->deviceOS = $packet->deviceOS;//基岩版 7
                if(isset($packet->clientData['GuiScale']) and $packet->clientData['GuiScale']==0){
                    $this->sendMessage('§l§a[LTcraft注意]§c你当前GUI缩放为：最大 推荐设置:中'.PHP_EOL .'可在设置-视频-GUI缩放 调整', true);
                }
                /*
					if($this->server->getConfigBoolean('online-mode', false) && $packet->identityPublicKey === null){
						$this->kick('disconnectionScreen.notAuthenticated', false);
						break;
					}

					if(count($this->server->getOnlinePlayers()) >= $this->server->getMaxPlayers() and $this->kick('disconnectionScreen.serverFull', false)){
						break;
					}
				*/
                if(!in_array($packet->protocol, ProtocolInfo::ACCEPTED_PROTOCOLS)){
                    if($packet->protocol < ProtocolInfo::CURRENT_PROTOCOL){
                        $message = 'disconnectionScreen.outdatedClient';

                        $pk = new PlayStatusPacket();
                        $pk->status = PlayStatusPacket::LOGIN_FAILED_CLIENT;
                        $this->directDataPacket($pk);
                    }else{
                        $message = 'disconnectionScreen.outdatedServer';

                        $pk = new PlayStatusPacket();
                        $pk->status = PlayStatusPacket::LOGIN_FAILED_SERVER;
                        $this->directDataPacket($pk);
                    }
                    $this->close('', $message, false);

                    break;
                }

                $this->randomClientId = $packet->clientId;

                $this->uuid = UUID::fromString($packet->clientUUID);
                $this->rawUUID = $this->uuid->toBinary();

                $valid = true;
                $len = strlen($packet->username);
                if(preg_match("/[\x7f-\xff]/", $packet->username)){
                    $valid = '用户名不可包含中文！';
                }
                if($len > 16 or $len < 3 and $valid===true){
                    $valid = '用户名长度请大于等于3和小于等于16';
                }
                for($i = 0; $i < $len and $valid===true; ++$i){
                    $c = ord($packet->username[$i]);
                    if(($c >= ord('a') and $c <= ord('z')) or ($c >= ord('A') and $c <= ord('Z')) or ($c >= ord('0') and $c <= ord('9')) or $c === ord('_')){
                        continue;
                    }
                    // if($c === ord('|') or $c === ord('$')){
                    // $this->close('', '无效名字！！');
                    $valid = '请使用英文数字和_组合名字';
                    // }

                    break;
                }
                if($valid===true and ($this->iusername === 'rcon' or $this->iusername === 'console')){
                    $valid='用户名不符合规范'.PHP_EOL .'不可使用: console rcon';//用户名不符合规范
                }
                if($valid!==true){
                    $this->close('', $valid);
                    // $this->ConfError=true;
                    break;
                }
                /*
				if((strlen($packet->skin) != 64 * 64 * 4) and (strlen($packet->skin) != 64 * 32 * 4)){
					// $this->ConfError=true;
					$this->close('', '无效皮肤');//无效皮肤
					break;
				}
				*/
                $this->setSkin($packet->skin, $packet->skinId);
                // var_dump($packet->skin);
                // var_dump($packet->skinId);
                // file_put_contents('F:\sink.txt', $packet->skin,FILE_APPEND);

                $this->server->getPluginManager()->callEvent($ev = new PlayerPreLoginEvent($this, 'Plugin reason'));
                if($ev->isCancelled()){
                    $this->close('', $ev->getKickMessage(),true,true);
                    break;
                }

                $pk = new PlayStatusPacket();
                $pk->status = PlayStatusPacket::LOGIN_SUCCESS;
                $this->directDataPacket($pk);

                $infoPacket = new ResourcePacksInfoPacket();
                $infoPacket->resourcePackEntries = $this->server->getResourcePackManager()->getResourceStack();
                $infoPacket->mustAccept = $this->server->getResourcePackManager()->resourcePacksRequired();
                $this->directDataPacket($infoPacket);

                /*if($this->isConnected()){
					$this->processLogin();
				}*/
                break;
            case ProtocolInfo::RESOURCE_PACK_CLIENT_RESPONSE_PACKET:
                switch($packet->status){
                    case ResourcePackClientResponsePacket::STATUS_REFUSED:
                        //Client refused to download the required resource pack
                        $this->close('', $this->server->getLanguage()->translateString('disconnectionScreen.refusedResourcePack'), true);
                        break;
                    case ResourcePackClientResponsePacket::STATUS_SEND_PACKS:
                        $manager = $this->server->getResourcePackManager();
                        foreach($packet->packIds as $uuid){
                            $pack = $manager->getPackById($uuid);
                            if(!($pack instanceof ResourcePack)){
                                //Client requested a resource pack but we don't have it available on the server
                                $this->close('', $this->server->getLanguage()->translateString('disconnectionScreen.unavailableResourcePack'), true);
                                break;
                            }

                            $pk = new ResourcePackDataInfoPacket();
                            $pk->packId = $pack->getPackId();
                            $pk->maxChunkSize = 1048576; //1MB
                            $pk->chunkCount = $pack->getPackSize() / $pk->maxChunkSize;
                            $pk->compressedPackSize = $pack->getPackSize();
                            $pk->sha256 = $pack->getSha256();
                            $this->dataPacket($pk);
                        }
                        break;
                    case ResourcePackClientResponsePacket::STATUS_HAVE_ALL_PACKS:
                        $pk = new ResourcePackStackPacket();
                        $manager = $this->server->getResourcePackManager();
                        $pk->resourcePackStack = $manager->getResourceStack();
                        $pk->mustAccept = $manager->resourcePacksRequired();
                        $this->dataPacket($pk);
                        break;
                    case ResourcePackClientResponsePacket::STATUS_COMPLETED:
                        $this->processLogin();
                        break;
                }
                break;
            case ProtocolInfo::RESOURCE_PACK_CHUNK_REQUEST_PACKET:
                $manager = $this->server->getResourcePackManager();
                $pack = $manager->getPackById($packet->packId);
                if(!($pack instanceof ResourcePack)){
                    $this->close('', 'disconnectionScreen.resourcePack', true);
                    return true;
                }

                $pk = new ResourcePackChunkDataPacket();
                $pk->packId = $pack->getPackId();
                $pk->chunkIndex = $packet->chunkIndex;
                $pk->data = $pack->getPackChunk(1048576 * $packet->chunkIndex, 1048576);
                $pk->progress = (1048576 * $packet->chunkIndex);
                $this->dataPacket($pk);
                break;
            case ProtocolInfo::MOVE_PLAYER_PACKET:
                if($this->linkedEntity instanceof Entity){
                    $entity = $this->linkedEntity;
                    if($entity instanceof Boat){//船
                        $entity->setPosition($this->temporalVector->setComponents($packet->x, $packet->y - 0.3, $packet->z));
                    }
                }

                $newPos = new Vector3($packet->x, $packet->y - $this->getEyeHeight(), $packet->z);

                if($newPos->distanceSquared($this) == 0 and ($packet->yaw % 360) === $this->yaw and ($packet->pitch % 360) === $this->pitch){ //player hasn't moved, just client spamming packets
                    break;
                }

                $revert = false;
                if(!$this->isAlive() or $this->spawned !== true){
                    $revert = true;
                    $this->forceMovement = new Vector3($this->x, $this->y, $this->z);
                }

                if($this->teleportPosition !== null or ($this->forceMovement instanceof Vector3 and ($newPos->distanceSquared($this->forceMovement) > 0.1 or $revert))){
                    $this->sendPosition($this->forceMovement, $packet->yaw, $packet->pitch, MovePlayerPacket::MODE_RESET);
                }else{
                    $packet->yaw %= 360;
                    $packet->pitch %= 360;

                    if($packet->yaw < 0){
                        $packet->yaw += 360;
                    }

                    $this->setRotation($packet->yaw, $packet->pitch);
                    $this->newPosition = $newPos;
                    $this->forceMovement = null;
                }

                break;
            case ProtocolInfo::ADVENTURE_SETTINGS_PACKET:
                //TODO: player abilities, check for other changes
                $isCheater = ($this->getAllowFlight() === false && ($packet->flags >> 9) & 0x01 === 1) || (!$this->isSpectator() && ($packet->flags >> 7) & 0x01 === 1);
                if(($packet->isFlying and !$this->getAllowFlight() and !$this->server->getAllowFlight()) or $isCheater){
                    $this->kick('服务器是不允许非法飞行的！', false);
                    break;
                }else{
                    $this->server->getPluginManager()->callEvent($ev = new PlayerToggleFlightEvent($this, $packet->isFlying));
                    if($ev->isCancelled()){
                        $this->sendSettings();
                    }else{
                        $this->flying = $ev->isFlying();
                    }
                    break;
                }
                break;
            case ProtocolInfo::MOB_EQUIPMENT_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }
                foreach($this->level->getPlayers() as $player)$player->dataPacket($packet);
                /**
                 * Handle hotbar slot remapping
                 * This is the only time and place when hotbar mapping should ever be changed.
                 * Changing hotbar slot mapping at will has been deprecated because it causes far too many
                 * issues with Windows 10 Edition Beta.
                 */
                if($this->isSpectator())break;
                $this->inventory->setHeldItemIndex($packet->selectedSlot, false, $packet->slot);

                $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
                break;
            case ProtocolInfo::USE_ITEM_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }

                $blockVector = new Vector3($packet->x, $packet->y, $packet->z);

                $this->craftingType = self::CRAFTING_SMALL;

                if($packet->face >= 0 and $packet->face <= 5){ //Use Block, place
                    $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);

                    if(!$this->canInteract($blockVector->add(0.5, 0.5, 0.5), 13) or $this->isSpectator()){

                    }elseif($this->isCreative()){
                        $item = $this->inventory->getItemInHand();
                        if($this->level->useItemOn($blockVector, $item, $packet->face, $packet->fx, $packet->fy, $packet->fz, $this) === true){
                            break;
                        }
                    }elseif(!$this->inventory->getItemInHand()->equals($packet->item)){
                        $this->inventory->sendHeldItem($this);
                    }else{
                        $item = $this->inventory->getItemInHand();
                        $oldItem = clone $item;
                        if($this->level->useItemOn($blockVector, $item, $packet->face, $packet->fx, $packet->fy, $packet->fz, $this)){
                            if(!$item->equals($oldItem) or $item->getCount() !== $oldItem->getCount()){
                                $this->inventory->setItemInHand($item);
                                $this->inventory->sendHeldItem($this->hasSpawned);
                            }
                            break;
                        }
                    }

                    $this->inventory->sendHeldItem($this);

                    if($blockVector->distanceSquared($this) > 10000){
                        break;
                    }
                    $target = $this->level->getBlock($blockVector);
                    $block = $target->getSide($packet->face);

                    $this->level->sendBlocks([$this], [$target, $block], UpdateBlockPacket::FLAG_ALL_PRIORITY);
                    break;
                }elseif($packet->face === -1){//点击的空气
                    if($this->isSpectator())break;
                    $aimPos = (new Vector3($packet->x / 32768, $packet->y / 32768, $packet->z / 32768))->normalize();

                    if($this->isCreative()){
                        $item = $this->inventory->getItemInHand();
                    }elseif(!$this->inventory->getItemInHand()->equals($packet->item)){
                        $this->inventory->sendHeldItem($this);
                        break;
                    }else{
                        $item = $this->inventory->getItemInHand();
                    }

                    $ev = new PlayerInteractEvent($this, $item, $aimPos, $packet->face, PlayerInteractEvent::RIGHT_CLICK_AIR);

                    $this->server->getPluginManager()->callEvent($ev);

                    if($ev->isCancelled()){
                        $this->inventory->sendHeldItem($this);
                        break;
                    }

                    $nbt = new CompoundTag('', [
                        'Pos' => new ListTag('Pos', [
                            new DoubleTag('', $this->x),
                            new DoubleTag('', $this->y + $this->getEyeHeight()),
                            new DoubleTag('', $this->z),
                        ]),
                        'Motion' => new ListTag('Motion', [
                            //TODO: remove this because of a broken client
                            new DoubleTag('', -sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)),
                            new DoubleTag('', -sin($this->pitch / 180 * M_PI)),
                            new DoubleTag('', cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)),
                        ]),
                        'Rotation' => new ListTag('Rotation', [
                            new FloatTag('', $this->yaw),
                            new FloatTag('', $this->pitch),
                        ]),
                    ]);

                    $entity = null;
                    $reduceCount = true;

                    switch($item->getId()){
                        case Item::SNOWBALL:
                            $f = 1.5;
                            $entity = Entity::createEntity('Snowball', $this->getLevel(), $nbt, $this);
                            $entity->setMotion($entity->getMotion()->multiply($f));
                            // $this->server->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($entity));
                            // if($ev->isCancelled()){
                            // $entity->kill();
                            // }
                            break;
                        // case Item::EGG:
                        // $f = 1.5;
                        // $entity = Entity::createEntity('Egg', $this->getLevel(), $nbt, $this);
                        // $entity->setMotion($entity->getMotion()->multiply($f));
                        // $this->server->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($entity));
                        // if($ev->isCancelled()){
                        // $entity->kill();
                        // }
                        // break;
                        case Item::ENCHANTING_BOTTLE:
                            $f = 1.1;
                            $entity = Entity::createEntity('ThrownExpBottle', $this->getLevel(), $nbt, $this);
                            $entity->setMotion($entity->getMotion()->multiply($f));
                            // $this->server->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($entity));
                            // if($ev->isCancelled()){
                            // $entity->kill();
                            // }
                            break;
                        case Item::SPLASH_POTION:
                            if($this->isSurvival()){
                                $f = 1.1;
                                $nbt['PotionId'] = new ShortTag('PotionId', $item->getDamage());
                                $entity = Entity::createEntity('ThrownPotion', $this->getLevel(), $nbt, $this);
                                $entity->setMotion($entity->getMotion()->multiply($f));
                                /*
								$this->server->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($entity));
								if($ev->isCancelled()){
									$entity->kill();
								}
								*/
                            }
                            break;
                        case Item::ENDER_PEARL:
                            if(floor(($time = microtime(true)) - $this->lastEnderPearlUse) >= 1){
                                $f = 1.1;
                                $entity = Entity::createEntity('EnderPearl', $this->getLevel(), $nbt, $this);
                                $entity->setMotion($entity->getMotion()->multiply($f));
                                // $this->server->getPluginManager()->callEvent($ev = new ProjectileLaunchEvent($entity));
                                // if($ev->isCancelled()){
                                // $entity->kill();
                                // }else{
                                $this->lastEnderPearlUse = $time;
                                // }
                            }
                            break;
                    }

                    if($entity instanceof Projectile and $entity->isAlive()){
                        if($reduceCount and $this->isSurvival()){
                            $item->setCount($item->getCount() - 1);
                            $this->inventory->setItemInHand($item->getCount() > 0 ? $item : Item::get(Item::AIR));
                        }
                        $entity->spawnToAll();
                        $this->level->addSound(new LaunchSound($this), $this->getViewers());
                    }

                    $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, true);
                    $this->startAction = $this->server->getTick();
                }
                break;
            case ProtocolInfo::PLAYER_ACTION_PACKET:
                if($this->spawned === false or (!$this->isAlive() and $packet->action !== PlayerActionPacket::ACTION_SPAWN_SAME_DIMENSION and $packet->action !== PlayerActionPacket::ACTION_SPAWN_OVERWORLD and $packet->action !== PlayerActionPacket::ACTION_SPAWN_NETHER)){
                    break;
                }
                $pos = new Vector3($packet->x, $packet->y, $packet->z);

                switch($packet->action){
                    case PlayerActionPacket::ACTION_START_BREAK:
                        if($this->lastBreak !== PHP_INT_MAX or $pos->distanceSquared($this) > 10000){
                            break;
                        }
                        $target = $this->level->getBlock($pos);
                        $ev = new PlayerInteractEvent($this, $this->inventory->getItemInHand(), $target, $packet->face, $target->getId() === 0 ? PlayerInteractEvent::LEFT_CLICK_AIR : PlayerInteractEvent::LEFT_CLICK_BLOCK);
                        $this->getServer()->getPluginManager()->callEvent($ev);
                        if(!$ev->isCancelled()){
                            $side = $target->getSide($packet->face);
                            if($side instanceof Fire){
                                $side->getLevel()->setBlock($side, new Air());
                                break;
                            }
                            $this->lastBreak = microtime(true);
                        }else{
                            $this->inventory->sendHeldItem($this);
                        }
                        break;
                    case PlayerActionPacket::ACTION_ABORT_BREAK:
                        $this->lastBreak = PHP_INT_MAX;
                        break;
                    case PlayerActionPacket::ACTION_STOP_BREAK:
                        break;
                    case PlayerActionPacket::ACTION_RELEASE_ITEM:
                        if($this->startAction > -1 and $this->getDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION)){
                            if($this->inventory->getItemInHand()->getId() === Item::BOW){
                                $bow = $this->inventory->getItemInHand();
                                if($this->isSurvival() and !$this->inventory->contains(Item::get(Item::ARROW, 0))){
                                    $this->inventory->sendContents($this);
                                    break;
                                }
                                $arrow = null;

                                $index = $this->inventory->first(Item::get(Item::ARROW, 0));

                                if($index !== -1){
                                    $arrow = $this->inventory->getItem($index);
                                    $arrow->setCount(1);
                                }elseif($this->isCreative()){
                                    $arrow = Item::get(Item::ARROW, 0, 1);
                                }else{
                                    $this->inventory->sendContents($this);
                                    break;
                                }
                                $nbt = new CompoundTag('', [
                                    'Pos' => new ListTag('Pos', [
                                        new DoubleTag('', $this->x),
                                        new DoubleTag('', $this->y + $this->getEyeHeight()),
                                        new DoubleTag('', $this->z)
                                    ]),
                                    'Motion' => new ListTag('Motion', [
                                        new DoubleTag('', -sin($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI)),
                                        new DoubleTag('', -sin($this->pitch / 180 * M_PI)),
                                        new DoubleTag('', cos($this->yaw / 180 * M_PI) * cos($this->pitch / 180 * M_PI))
                                    ]),
                                    'Rotation' => new ListTag('Rotation', [
                                        new FloatTag('', $this->yaw),
                                        new FloatTag('', $this->pitch)
                                    ]),
                                    'Fire' => new ShortTag('Fire', $this->isOnFire() ? 45 * 60 : 0),
                                    'Potion' => new ShortTag('Potion', $arrow->getDamage())
                                ]);
                                $diff = ($this->server->getTick() - $this->startAction);
                                $p = $diff / 20;
                                $f = min((($p ** 2) + $p * 2) / 3, 1) * 2;
                                if(!$bow->getEnchantmentLevel(Enchantment::TYPE_BOW_INFINITY)!==false)
                                    $ev = new EntityShootBowEvent($this, $bow, Entity::createEntity('Arrow', $this->getLevel(), $nbt, $this, $f == 2 ? true : false), $f);
                                else
                                    $ev = new EntityShootBowEvent($this, $bow, Entity::createEntity('falseArrow', $this->getLevel(), $nbt, $this, $f == 2 ? true : false), $f);
                                if($f < 0.1 or $diff < 5){
                                    $ev->setCancelled();
                                }

                                $this->server->getPluginManager()->callEvent($ev);

                                if($ev->isCancelled()){
                                    $ev->getProjectile()->kill();
                                    $this->inventory->sendContents($this);
                                }else{
                                    $ev->getProjectile()->setMotion($ev->getProjectile()->getMotion()->multiply($ev->getForce()));
                                    if($this->isSurvival()){
                                        if(!$bow->getEnchantmentLevel(Enchantment::TYPE_BOW_INFINITY)!==false)$this->inventory->removeItem($arrow);
                                        if(!$bow->isUnbreakable()){
                                            $bow->setDamage($bow->getDamage() + 1);
                                            if($bow->getDamage() >= 385){
                                                $this->inventory->setItemInHand(Item::get(Item::AIR, 0, 0));
                                            }else{
                                                $this->inventory->setItemInHand($bow);
                                            }
                                        }
                                    }
                                    if($ev->getProjectile() instanceof Projectile){
                                        // $this->server->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($ev->getProjectile()));
                                        // if($projectileEv->isCancelled()){
                                        // $ev->getProjectile()->kill();
                                        // }else{
                                        $ev->getProjectile()->spawnToAll();
                                        $this->level->addSound(new LaunchSound($this), $this->getViewers());
                                        // }
                                    }else{
                                        $ev->getProjectile()->spawnToAll();
                                    }
                                }
                            }
                        }elseif($this->inventory->getItemInHand()->getId() === Item::BUCKET and $this->inventory->getItemInHand()->getDamage() === 1){ //Milk!
                            /*$this->server->getPluginManager()->callEvent($ev = new PlayerItemConsumeEvent($this, $this->inventory->getItemInHand()));
							if($ev->isCancelled()){
								$this->inventory->sendContents($this);
								break;
							}*/

                            $pk = new EntityEventPacket();
                            $pk->eid = $this->getId();
                            $pk->event = EntityEventPacket::USE_ITEM;
                            //$pk;
                            $this->dataPacket($pk);
                            $this->server->broadcastPacket($this->getViewers(), $pk);

                            if($this->isSurvival()){
                                $slot = $this->inventory->getItemInHand();
                                --$slot->count;
                                $this->inventory->setItemInHand($slot);
                                $this->inventory->addItem(Item::get(Item::BUCKET, 0, 1));
                            }

                            $this->removeBadEffect();
                        }else{
                            $this->inventory->sendContents($this);
                        }
                        break;
                    case PlayerActionPacket::ACTION_STOP_SLEEPING:
                        $this->stopSleep();
                        break;
                    case PlayerActionPacket::ACTION_SPAWN_NETHER:
                        break;
                    case PlayerActionPacket::ACTION_SPAWN_SAME_DIMENSION:
                    case PlayerActionPacket::ACTION_SPAWN_OVERWORLD:
                        if($this->isAlive() or !$this->isOnline()){
                            break;
                        }

                        if($this->server->isHardcore()){
                            $this->setBanned(true);
                            break;
                        }

                        $this->craftingType = self::CRAFTING_SMALL;

                        if($this->server->netherEnabled){
                            if($this->level === $this->server->getLevelByName($this->server->netherName)){
                                $this->teleport($pos = $this->server->getDefaultLevel()->getSafeSpawn());
                            }
                        }

                        $this->server->getPluginManager()->callEvent($ev = new PlayerRespawnEvent($this, $this->getSpawn()));

                        $this->teleport($ev->getRespawnPosition());

                        $this->setSprinting(false);
                        $this->setSneaking(false);

                        $this->extinguish();
                        $this->setDataProperty(self::DATA_AIR, self::DATA_TYPE_SHORT, 400);
                        $this->deadTicks = 0;
                        $this->noDamageTicks = 60;

                        $this->removeAllEffects();
                        $this->setHealth($this->getMaxHealth());
                        $this->setFood(20);
                        $this->starvationTick = 0;
                        $this->foodTick = 0;
                        $this->foodUsageTime = 0;

                        $this->sendData($this);

                        $this->sendSettings();
                        $this->inventory->sendContents($this);
                        $this->inventory->sendArmorContents($this);

                        $this->spawnToAll();
                        $this->scheduleUpdate();
                        break;
                    case PlayerActionPacket::ACTION_JUMP:
                        break 2;
                    case PlayerActionPacket::ACTION_START_SPRINT:
                        // $ev = new PlayerToggleSprintEvent($this, true);
                        // $this->server->getPluginManager()->callEvent($ev);
                        // if($ev->isCancelled()){
                        // $this->sendData($this);
                        // }else{
                        $this->setSprinting(true);
                        // }
                        break 2;
                    case PlayerActionPacket::ACTION_STOP_SPRINT:
                        // $ev = new PlayerToggleSprintEvent($this, false);
                        // $this->server->getPluginManager()->callEvent($ev);
                        // if($ev->isCancelled()){
                        // $this->sendData($this);
                        // }else{
                        $this->setSprinting(false);
                        // }
                        break 2;
                    case PlayerActionPacket::ACTION_START_SNEAK:
                        // $ev = new PlayerToggleSneakEvent($this, true);
                        // $this->server->getPluginManager()->callEvent($ev);
                        // if($ev->isCancelled()){
                        // $this->sendData($this);
                        // }else{
                        $this->setSneaking(true);
                        if($this->getRideEntity() instanceof Player){
                            LTVIP::down($this);
                        }
                        // }
                        break 2;
                    case PlayerActionPacket::ACTION_STOP_SNEAK:
                        // $ev = new PlayerToggleSneakEvent($this, false);
                        // $this->server->getPluginManager()->callEvent($ev);
                        // if($ev->isCancelled()){
                        // $this->sendData($this);
                        // }else{
                        $this->setSneaking(false);
                        // }
                        break 2;
                    case PlayerActionPacket::ACTION_START_GLIDE:
                        // $ev = new PlayerToggleGlideEvent($this, true);
                        // $this->server->getPluginManager()->callEvent($ev);
                        // if($ev->isCancelled()){
                        // $this->sendData($this);
                        // }else{
                        $this->setGliding(true);
                        // }
                        break 2;
                    case PlayerActionPacket::ACTION_STOP_GLIDE:
                        // $ev = new PlayerToggleGlideEvent($this, false);
                        // $this->server->getPluginManager()->callEvent($ev);
                        // if($ev->isCancelled()){
                        // $this->sendData($this);
                        // }else{
                        $this->setGliding(false);
                        // }
                        break 2;
                    case PlayerActionPacket::ACTION_CONTINUE_BREAK:
                        $block = $this->level->getBlock($pos);
                        $this->level->broadcastLevelEvent($pos, LevelEventPacket::EVENT_PARTICLE_PUNCH_BLOCK, $block->getId() | ($block->getDamage() << 8) | ($packet->face << 16));
                        break;
                    default:
                        assert(false, 'Unhandled player action ' . $packet->action . ' from ' . $this->getName());
                }

                $this->startAction = -1;
                $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
                break;

            case ProtocolInfo::REMOVE_BLOCK_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }
                $this->craftingType = self::CRAFTING_SMALL;

                $vector = new Vector3($packet->x, $packet->y, $packet->z);

                $item = $this->inventory->getItemInHand();
                $oldItem = clone $item;
                if($this->canInteract($vector->add(0.5, 0.5, 0.5), $this->isCreative() ? 13 : 6) and $this->level->useBreakOn($vector, $item, $this, $this->server->destroyBlockParticle)){
                    if($this->isSurvival()){
                        if(!$item->equals($oldItem) or $item->getCount() !== $oldItem->getCount()){
                            $this->inventory->setItemInHand($item);
                            $this->inventory->sendHeldItem($this);
                        }

                        $this->exhaust(0.025, PlayerExhaustEvent::CAUSE_MINING);
                    }
                    break;
                }

                $this->inventory->sendContents($this);
                $target = $this->level->getBlock($vector);
                $tile = $this->level->getTile($vector);

                $this->level->sendBlocks([$this], [$target], UpdateBlockPacket::FLAG_ALL_PRIORITY);

                $this->inventory->sendHeldItem($this);

                if($tile instanceof Spawnable){
                    $tile->spawnTo($this);
                }
                break;

            case ProtocolInfo::MOB_ARMOR_EQUIPMENT_PACKET:
                //This packet is ignored. Armour changes are also sent by ContainerSetSlotPackets, and are handled there instead.
                break;

            case ProtocolInfo::INTERACT_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }

                $this->craftingType = self::CRAFTING_SMALL;

                $target = $this->level->getEntity($packet->target);
//echo '点击了:'.get_class($target).PHP_EOL;
                $cancelled = false;

                /*if($target instanceof Player and $this->server->getConfigBoolean('pvp', true) === false){
					$cancelled = true;
				}*/

                if(in_array($packet->action, [InteractPacket::ACTION_RIGHT_CLICK, InteractPacket::ACTION_LEAVE_VEHICLE])){
                    if($target instanceof Pets){
                        if($packet->action === InteractPacket::ACTION_RIGHT_CLICK){
                            $item=$this->inventory->getItemInHand();
                            if($item instanceof Food){
                                $item->setCount($item->getCount()-1);
                                $this->inventory->setItemInHand($item);
                                $target->addFood($item->getFoodRestore()*100);
                            }elseif($target instanceof MountPet or $target instanceof LTNPC){
                                $target->linkEntity($this);
                            }
                        }elseif($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
                            $target->cancelLinkEntity($this);
                        }
                    }elseif($target instanceof Player){
                        if($packet->action === InteractPacket::ACTION_RIGHT_CLICK and $this->level->getName()!='boss'){
                            LTVIP::on($target, $this);
                        }elseif($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
                            LTVIP::down($this);
                        }
                    }elseif($this->getLinkedEntity() instanceof LTEntity\entity\monster\flying\AEnderDragon) {
                        $this->getLinkedEntity()->setStatus(null);
                        $this->getLinkedEntity()->setTarget(null);
                    }
                    break;
                }

                if($packet->action === InteractPacket::ACTION_RIGHT_CLICK){
                    if($target instanceof Animal){
                        //TODO add Feed
                    }
                    break;
                }elseif($packet->action === InteractPacket::ACTION_MOUSEOVER){
                    break;
                }
                if($target instanceof Entity and $this->getGamemode() !== Player::VIEW and $this->isAlive() and $target->isAlive()){
                    if($target instanceof DroppedItem or $target instanceof Arrow){
                        $this->sendMessage(TextFormat::RED.'您尝试攻击了一个无效实体！！！');
                        $this->server->getLogger()->warning($this->getServer()->getLanguage()->translateString('pocketmine.player.invalidEntity', [$this->getName()]));
                        break;
                    }

                    // var_dump($target);
                    // if($this->server->getTick()-$this->lastMoveTick>600){
                    // $this->sendCenterTip('§l§c你有30秒没移动了,为了防止挂机,请移动一下,再发起攻击吧.');
                    // break;
                    // }
                    $item = $this->inventory->getItemInHand();
                    if($item instanceof \LTItem\SpecialItems\Weapon){
                        if($item->canUse($this)){
                            $damage = [
                                EntityDamageEvent::MODIFIER_BASE => $item->getModifyAttackDamage($target),
                            ];
                        }else{
                            $damage = [
                                EntityDamageEvent::MODIFIER_BASE => 0,
                            ];
                        }
                    }else{
                        $damage = [
                            EntityDamageEvent::MODIFIER_BASE => $item->getModifyAttackDamage($target),
                        ];
                    }

                    if(!$this->canInteract($target, 8)){
                        $cancelled = true;
                    }elseif($target instanceof Player){
                        if(($target->getGamemode() & 0x01) > 0){
                            break;
                        }
                        // elseif($this->server->getTick()-$this->lastMove>100){
                        // $cancelled = true;
                        // }

                        /*if($this->server->getConfigBoolean('pvp') !== true or $this->server->getDifficulty() === 0){
							$cancelled = true;
						}*/
                    }
                    $damage[EntityDamageEvent::MODIFIER_BASE]+=$this->getBuff()->getDamage($target);
                    $ev = new EntityDamageByEntityEvent($this, $target, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $damage, 0.4 + $item->getEnchantmentLevel(Enchantment::TYPE_WEAPON_KNOCKBACK) * 0.15);
                    if($cancelled)$ev->setCancelled();
//echo get_class($target);
                    if($target->attack($ev->getDamage(), $ev) === true){
                        $ev->useArmors();
                    }

                    if($ev->isCancelled()){
                        if($item->isTool() and $this->isSurvival()){
                            $this->inventory->sendContents($this);
                        }
                        break;
                    }

                    if($this->isSurvival()){
                        if($item->isTool()){
                            if($item->useOn($target) and $item->getDamage() >= $item->getMaxDurability()){
                                $this->inventory->setItemInHand(Item::get(Item::AIR, 0, 1));
                            }else{
                                $this->inventory->setItemInHand($item);
                            }
                        }

                        $this->exhaust(0.03, PlayerExhaustEvent::CAUSE_ATTACK);
                    }
                }


                break;
            case ProtocolInfo::ANIMATE_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }

                $this->server->getPluginManager()->callEvent($ev = new PlayerAnimationEvent($this, $packet->action));

                $pk = new AnimatePacket();
                $pk->eid = $this->getId();
                $pk->action = $ev->getAnimationType();
                $this->server->broadcastPacket($this->getViewers(), $pk);
                break;
            case ProtocolInfo::SET_HEALTH_PACKET: //Not used
                break;
            case ProtocolInfo::ENTITY_EVENT_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }
                $this->craftingType = self::CRAFTING_SMALL;

                $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false); //TODO: check if this should be true
                //$this->sendMessage('id:'.$packet->event);
                switch($packet->event){
                    case EntityEventPacket::USE_ITEM: //Eating
                        $slot = $this->inventory->getItemInHand();
                        if($slot->canBeConsumed() and $slot->canBeConsumedBy($this)){
                            // $ev = new PlayerItemConsumeEvent($this, $slot);
                            // if(!$slot->canBeConsumedBy($this)){
                            // $ev->setCancelled();
                            // }
                            // $this->server->getPluginManager()->callEvent($ev);
                            // if(!$ev->isCancelled()){
                            $slot->onConsume($this);
                            // }else{
                            // $this->inventory->sendContents($this);
                            // }
                        }
                        break;
                    case EntityEventPacket::EATING:
                        $this->dataPacket($packet);
                        $this->getServer()->broadcastPacket($this->getViewers(), $packet);
                        $slot = $this->inventory->getItemInHand();
                        if ($slot instanceof ManaFood){
                            $slot->eatIng($this);
                        }
                        break;
                }
                break;
            case ProtocolInfo::DROP_ITEM_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }
                if($packet->item->getId() === Item::AIR){
                    /**
                     * This is so stupid it's unreal.
                     * Windows 10 Edition Beta drops the contents of the crafting grid when the inventory closes - including air.
                     */
                    break;
                }

                if($this->isCreative()){
                    break;
                }

                $this->getTransactionQueue()->addTransaction(new DropItemTransaction($packet->item));
                break;
            case ProtocolInfo::COMMAND_STEP_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }
                $this->craftingType = 0;
                $commandText = $packet->command;
                if($packet->inputJson !== null){
                    foreach($packet->inputJson as $arg){ //command ordering will be an issue
                        if(!is_object($arg))
                            $commandText .= ' ' . $arg;
                    }
                }
                $this->server->getPluginManager()->callEvent($ev = new PlayerCommandPreprocessEvent($this, '/' . $commandText));
                if($ev->isCancelled()){
                    break;
                }

                Timings::$playerCommandTimer->startTiming();
                $this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
                Timings::$playerCommandTimer->stopTiming();
                break;
            case ProtocolInfo::TEXT_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }
                $this->craftingType = self::CRAFTING_SMALL;
                if($packet->type === TextPacket::TYPE_CHAT){
                    $packet->message = TextFormat::clean($packet->message, $this->removeFormat);
                    foreach(explode('\n', $packet->message) as $message){
                        if(trim($message) != '' and strlen($message) <= 255 and $this->messageCounter-- > 0){
                            if(substr($message, 0, 2) === './'){ //Command (./ = fast hack for old plugins post 0.16)
                                $message = substr($message, 1);
                            }

                            $ev = new PlayerCommandPreprocessEvent($this, $message);

                            if(mb_strlen($ev->getMessage(), 'UTF-8') > 320){
                                $ev->setCancelled();
                            }
                            $this->server->getPluginManager()->callEvent($ev);

                            if($ev->isCancelled()){
                                break;
                            }

                            if(substr($ev->getMessage(), 0, 1) === '/'){
                                Timings::$playerCommandTimer->startTiming();
                                $this->server->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
                                Timings::$playerCommandTimer->stopTiming();
                            }else{
                                $this->server->getPluginManager()->callEvent($ev = new PlayerChatEvent($this, str_replace("\n", ' ', $ev->getMessage())));
                            }
                        }
                    }
                }
                break;
            case ProtocolInfo::CONTAINER_CLOSE_PACKET:
                if($this->spawned === false or $packet->windowid === 0){
                    break;
                }
                $this->craftingType = self::CRAFTING_SMALL;
                if(isset($this->windowIndex[$packet->windowid])){
                    $this->server->getPluginManager()->callEvent(new InventoryCloseEvent($this->windowIndex[$packet->windowid], $this));
                    if(isset($this->windowIndex[$packet->windowid])){
                        $this->removeWindow($this->windowIndex[$packet->windowid]);
                    }
                }

                /**
                 * Drop anything still left in the crafting inventory
                 * This will usually never be needed since Windows 10 clients will send DropItemPackets
                 * which will cause this to happen anyway, but this is here for when transactions
                 * fail and items end up stuck in the crafting inventory.
                 */
                foreach($this->getFloatingInventory()->getContents() as $item){
                    // var_dump($item);
                    $this->getFloatingInventory()->removeItem($item);
                    $this->getInventory()->addItem($item);
                }
                break;

            case ProtocolInfo::CRAFTING_EVENT_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }

                /**
                 * For some annoying reason, anvils send window ID 255 when crafting with them instead of the _actual_ anvil window ID
                 * The result of this is anvils immediately closing when used. This is highly unusual, especially since the
                 * container set slot packets send the correct window ID, but... eh
                 */
                /*elseif(!isset($this->windowIndex[$packet->windowId])){
					$this->inventory->sendContents($this);
					$pk = new ContainerClosePacket();
					$pk->windowid = $packet->windowId;
					$this->dataPacket($pk);
					break;
				}*/
                $recipe = $this->server->getCraftingManager()->getRecipe($packet->id);

                if($this->craftingType === self::CRAFTING_ANVIL){
                    $anvilInventory = $this->windowIndex[$packet->windowId] ?? null;
                    if($anvilInventory === null){
                        foreach($this->windowIndex as $window){
                            if($window instanceof AnvilInventory){
                                $anvilInventory = $window;
                                break;
                            }
                        }
                        if($anvilInventory === null){ //If it's _still_ null, then the player doesn't have a valid anvil window, cannot proceed.
                            $this->getServer()->getLogger()->debug('Couldnt find an anvil window for ' . $this->getName() . ', exiting');
                            $this->inventory->sendContents($this);
                            break;
                        }
                    }
                    if($recipe === null){
                        if($packet->output[0]->getId() > 0 && $packet->output[1] === 0){ //物品重命名
                            $anvilInventory->onRename($this, $packet->output[0]);
                        }elseif($packet->output[0]->getId() > 0 && $packet->output[1] > 0){ //附魔书
                            $anvilInventory->process($this, $packet->output[0], $packet->output[1]);
                        }
                    }
                    break;
                }elseif(($recipe instanceof BigShapelessRecipe or $recipe instanceof BigShapedRecipe) and $this->craftingType === 0){
                    $this->server->getLogger()->debug('Received big crafting recipe from ' . $this->getName() . ' with no crafting table open');
                    $this->inventory->sendContents($this);
                    break;
                }elseif($recipe === null){
                    $this->server->getLogger()->debug('Null (unknown) crafting recipe received from ' . $this->getName() . ' for ' . $packet->output[0]);
                    $this->inventory->sendContents($this);
                    break;
                }

                $canCraft = true;

                if(count($packet->input) === 0){
                    /* If the packet 'input' field is empty this needs to be handled differently.
					 * 'input' is used to tell the server what items to remove from the client's inventory
					 * Because crafting takes the materials in the crafting grid, nothing needs to be taken from the inventory
					 * Instead, we take the materials from the crafting inventory
					 * To know what materials we need to take, we have to guess the crafting recipe used based on the
					 * output item and the materials stored in the crafting items
					 * The reason we have to guess is because Win10 sometimes sends a different recipe UUID
					 * say, if you put the wood for a door in the right hand side of the crafting grid instead of the left
					 * it will send the recipe UUID for a wooden pressure plate. Unknown currently whether this is a client
					 * bug or if there is something wrong with the way the server handles recipes.
					 * TODO: Remove recipe correction and fix desktop crafting recipes properly.
					 * In fact, TODO: Rewrite crafting entirely.
					 */
                    $possibleRecipes = $this->server->getCraftingManager()->getRecipesByResult($packet->output[0]);
                    if(!$packet->output[0]->equals($recipe->getResult())){
                        $this->server->getLogger()->debug('Mismatched desktop recipe received from player ' . $this->getName() . ', expected ' . $recipe->getResult() . ', got ' . $packet->output[0]);
                    }
                    $recipe = null;
                    foreach($possibleRecipes as $r){
                        /* Check the ingredient list and see if it matches the ingredients we've put into the crafting grid
						 * As soon as we find a recipe that we have all the ingredients for, take it and run with it. */

                        //Make a copy of the floating inventory that we can make changes to.
                        $floatingInventory = clone $this->floatingInventory;
                        $ingredients = $r->getIngredientList();

                        //Check we have all the necessary ingredients.
                        foreach($ingredients as $ingredient){
                            if(!$floatingInventory->contains($ingredient)){
                                //We're short on ingredients, try the next recipe
                                $canCraft = false;
                                break;
                            }
                            //This will only be reached if we have the item to take away.
                            $floatingInventory->removeItem($ingredient);
                        }
                        if($canCraft){
                            //Found a recipe that works, take it and run with it.
                            $recipe = $r;
                            break;
                        }
                    }

                    if($recipe !== null){
// $ev = new CraftItemEvent($this, $ingredients, $recipe);
                        /*	$this->server->getPluginManager()->callEvent($ev = new CraftItemEvent($this, $ingredients, $recipe));

						if($ev->isCancelled()){
							$this->inventory->sendContents($this);
							break;
						}现在不必要*/

                        $this->floatingInventory = $floatingInventory; //Set player crafting inv to the idea one created in this process
                        $this->floatingInventory->addItem(clone $recipe->getResult()); //Add the result to our picture of the crafting inventory
                    }else{
                        $this->server->getLogger()->debug('Unmatched desktop crafting recipe ' . $packet->id . ' from player ' . $this->getName());
                        $this->inventory->sendContents($this);
                        break;
                    }
                }else{
                    if($recipe instanceof ShapedRecipe){
                        for($x = 0; $x < 3 and $canCraft; ++$x){
                            for($y = 0; $y < 3; ++$y){
                                $item = $packet->input[$y * 3 + $x];
                                $ingredient = $recipe->getIngredient($x, $y);
                                if($item->getCount() > 0 and $item->getId() > 0){
                                    if($ingredient == null){
                                        $canCraft = false;
                                        break;
                                    }
                                    if($ingredient->getId() != 0 and !$ingredient->equals($item, !$ingredient->hasAnyDamageValue(), $ingredient->hasCompoundTag())){
                                        $canCraft = false;
                                        break;
                                    }

                                }elseif($ingredient !== null and $item->getId() !== 0){
                                    $canCraft = false;
                                    break;
                                }
                            }
                        }
                    }elseif($recipe instanceof ShapelessRecipe){
                        $needed = $recipe->getIngredientList();

                        for($x = 0; $x < 3 and $canCraft; ++$x){
                            for($y = 0; $y < 3; ++$y){
                                $item = clone $packet->input[$y * 3 + $x];

                                foreach($needed as $k => $n){
                                    if($n->equals($item, !$n->hasAnyDamageValue(), $n->hasCompoundTag())){
                                        $remove = min($n->getCount(), $item->getCount());
                                        $n->setCount($n->getCount() - $remove);
                                        $item->setCount($item->getCount() - $remove);

                                        if($n->getCount() === 0){
                                            unset($needed[$k]);
                                        }
                                    }
                                }
                                if($item->getCount() > 0){
                                    $canCraft = false;
                                    break;
                                }
                            }
                        }
                        if(count($needed) > 0){
                            $canCraft = false;
                        }
                    }else{
                        $canCraft = false;
                    }

                    /** @var Item[] $ingredients */
                    $ingredients = $packet->input;
                    $result = $packet->output[0];

                    if(!$canCraft or !$recipe->getResult()->equals($result)){
                        $this->server->getLogger()->debug('Unmatched recipe ' . $recipe->getId() . ' from player ' . $this->getName() . ': expected ' . $recipe->getResult() . ', got ' . $result . ', using: ' . implode(', ', $ingredients));
                        $this->inventory->sendContents($this);
                        break;
                    }

                    $used = array_fill(0, $this->inventory->getSize(), 0);

                    foreach($ingredients as $ingredient){
                        $slot = -1;
                        foreach($this->inventory->getContents() as $index => $item){
                            if($ingredient->getId() !== 0 and $ingredient->equals($item, !$ingredient->hasAnyDamageValue(), $ingredient->hasCompoundTag()) and ($item->getCount() - $used[$index]) >= 1){
                                $slot = $index;
                                $used[$index]++;
                                break;
                            }
                        }

                        if($ingredient->getId() !== 0 and $slot === -1){
                            $canCraft = false;
                            break;
                        }
                    }

                    if(!$canCraft){
                        $this->server->getLogger()->debug('Unmatched recipe ' . $recipe->getId() . ' from player ' . $this->getName() . ': client does not have enough items, using: ' . implode(', ', $ingredients));
                        $this->inventory->sendContents($this);
                        break;
                    }
                    /*现在不必要
					$this->server->getPluginManager()->callEvent($ev = new CraftItemEvent($this, $ingredients, $recipe));

					if($ev->isCancelled()){
						$this->inventory->sendContents($this);
						break;
					}
					*/

                    foreach($used as $slot => $count){
                        if($count === 0){
                            continue;
                        }

                        $item = $this->inventory->getItem($slot);

                        if($item->getCount() > $count){
                            $newItem = clone $item;
                            $newItem->setCount($item->getCount() - $count);
                        }else{
                            $newItem = Item::get(Item::AIR, 0, 0);
                        }

                        $this->inventory->setItem($slot, $newItem);
                    }

                    $extraItem = $this->inventory->addItem($recipe->getResult());
                    if(count($extraItem) > 0 and !$this->isCreative()){ //Could not add all the items to our inventory (not enough space)
                        foreach($extraItem as $item){
                            if(!$this->isOp())$this->level->dropItem($this, $item);
                        }
                    }
                }

                break;

            case ProtocolInfo::CONTAINER_SET_SLOT_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }

                if($packet->slot < 0){
                    break;
                }
                if($packet->windowid === 0){ //Our inventory
                    if($packet->slot >= $this->inventory->getSize()){
                        break;
                    }
                    if ($this->getInventory()->getItem($packet->slot) instanceof LTItem and $this->getInventory()->getItem($packet->slot) ->getId()==383 and $this->attentionSend){
                        $this->getInventory()->sendContents($this);
                        break;
                    }
                    $transaction = new BaseTransaction($this->inventory, $packet->slot, $packet->item);
                }elseif($packet->windowid === ContainerSetContentPacket::SPECIAL_ARMOR){ //Our armor
                    if($packet->slot >= 4){
                        break;
                    }
                    // var_dump($packet);
                    $transaction = new BaseTransaction($this->inventory, $packet->slot + $this->inventory->getSize(), $packet->item);
                }elseif(isset($this->windowIndex[$packet->windowid])){
                    //Transaction for non-player-inventory window, such as anvil, chest, etc.
                    $inv = $this->windowIndex[$packet->windowid];
                    $achievements = [];

                    if($inv instanceof FurnaceInventory and $inv->getItem($packet->slot)->getId() === Item::IRON_INGOT and $packet->slot === FurnaceInventory::RESULT){
                        $achievements[] = 'acquireIron';

                    }elseif($inv instanceof EnchantInventory and $packet->item->hasEnchantments()){
                        $inv->onEnchant($this, $inv->getItem($packet->slot), $packet->item);
                    }
                    //var_dump($packet->item);
                    $transaction = new BaseTransaction($inv, $packet->slot, $packet->item, $achievements);
                }else{
                    //Client sent a transaction for a window which the server doesn't think they have open
                    break;
                }

                $this->getTransactionQueue()->addTransaction($transaction);

                break;
            case ProtocolInfo::BLOCK_ENTITY_DATA_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }
                $this->craftingType = self::CRAFTING_SMALL;

                $pos = new Vector3($packet->x, $packet->y, $packet->z);
                if($pos->distanceSquared($this) > 10000){
                    break;
                }

                $t = $this->level->getTile($pos);
                if($t instanceof Spawnable){
                    $nbt = new NBT(NBT::LITTLE_ENDIAN);
                    $nbt->read($packet->namedtag, false, true);
                    $nbt = $nbt->getData();
                    if(!$t->updateCompoundTag($nbt, $this)){
                        $t->spawnTo($this);
                    }
                }
                break;
            case ProtocolInfo::REQUEST_CHUNK_RADIUS_PACKET:
                $this->setViewDistance($packet->radius);
                break;
            case ProtocolInfo::SET_PLAYER_GAME_TYPE_PACKET:
                if($packet->gamemode !== $this->gamemode){
                    //Set this back to default. TODO: handle this properly
                    $this->sendGamemode();
                    $this->sendSettings();
                }
                break;
            case ProtocolInfo::ITEM_FRAME_DROP_ITEM_PACKET:
                if($this->spawned === false or !$this->isAlive()){
                    break;
                }

                $tile = $this->level->getTile($this->temporalVector->setComponents($packet->x, $packet->y, $packet->z));
                if($tile instanceof ItemFrame){
                    $this->server->getPluginManager()->callEvent($ev = new ItemFrameDropItemEvent($this, $tile->getBlock(), $tile, $tile->getItem()));
                    if($this->isSpectator() or $ev->isCancelled()){
                        $tile->spawnTo($this);
                        break;
                    }
                    if(lcg_value() <= $tile->getItemDropChance()){
                        $this->level->dropItem($tile->getBlock(), $tile->getItem());
                    }
                    $tile->setItem(null);
                    $tile->setItemRotation(0);
                }

                break;
            default:
                break;
        }

        $timings->stopTiming();
    }

    /**
     * Kicks a player from the server
     *
     * @param string $reason
     * @param bool   $isAdmin
     *
     * @return bool
     */
    public function kick($reason = '', $isAdmin = true){
        $this->server->getPluginManager()->callEvent($ev = new PlayerKickEvent($this, $reason, $this->getLeaveMessage()));
        if(!$ev->isCancelled()){
            if($isAdmin){
                $message = '你被管理员请出服务器.' . ($reason !== '' ? ' 原因: ' . $reason : '被管理员踢出服务器，没有注明原因！');
            }else{
                if($reason === '' or $reason === ' '){
                    $message = 'disconnectionScreen.noReason';
                }else{
                    $message = $reason;
                }
            }
            $this->close($ev->getQuitMessage(), $message);

            return true;
        }

        return false;
    }

    /** @var string[] */
    private $messageQueue = [];

    /**
     * @param Item $item
     *
     * Drops the specified item in front of the player.
     */
    public function dropItem(Item $item){
        if($this->spawned === false or !$this->isAlive()){
            return;
        }

        if($this->isCreative() or $this->isSpectator()){
            //Ignore for limited creative
            return;
        }

        if($item->getId() === Item::AIR or $item->getCount() < 1){
            //Ignore dropping air or items with bad counts
            return;
        }

        $ev = new PlayerDropItemEvent($this, $item);
        $this->server->getPluginManager()->callEvent($ev);
        if($ev->isCancelled()){
            $this->getFloatingInventory()->removeItem($item);
            $this->getInventory()->addItem($item);
            return;
        }

        $motion = $this->getDirectionVector()->multiply(0.4);
        $entity=$this->level->dropItem($this->add(0, 1.3, 0), $item, $motion, 40);

        $entity->setDropPlayer($this->username);
        $this->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_ACTION, false);
    }

    /**
     * Adds a title text to the user's screen, with an optional subtitle.
     *
     * @param string $title
     * @param string $subtitle
     * @param int    $fadeIn  Duration in ticks for fade-in. If -1 is given, client-sided defaults will be used.
     * @param int    $stay    Duration in ticks to stay on screen for
     * @param int    $fadeOut Duration in ticks for fade-out.
     */
    public function sendActionBar(string $title, string $subtitle = '', int $fadeIn = -1, int $stay = -1, int $fadeOut = -1){
        $this->setTitleDuration($fadeIn, $stay, $fadeOut);
        if($subtitle !== ''){
            $this->sendTitleText($subtitle, SetTitlePacket::TYPE_SUB_TITLE);
        }
        $this->sendTitleText($title, SetTitlePacket::TYPE_TITLE);
    }

    /**
     * @param string $title
     * @param string $subtitle
     * @param int    $fadeIn
     * @param int    $stay
     * @param int    $fadeOut
     */
    public function addTitle(string $title, string $subtitle = '', int $fadeIn = -1, int $stay = -1, int $fadeOut = -1, $force = false){//
        if($this->onTutorial() and $force===false)return;
        $this->setTitleDuration($fadeIn, $stay, $fadeOut);
        if($subtitle !== ''){
            $this->sendTitleText($subtitle, SetTitlePacket::TYPE_SUB_TITLE);
        }
        $this->sendTitleText($title, SetTitlePacket::TYPE_TITLE);
    }

    /**
     * Adds small text to the user's screen.
     *
     * @param string $message
     */
    public function addActionBarMessage(string $message){
        $this->sendTitleText($message, SetTitlePacket::TYPE_ACTION_BAR);
    }

    /**
     * Removes the title from the client's screen.
     */
    public function removeTitles(){
        $pk = new SetTitlePacket();
        $pk->type = SetTitlePacket::TYPE_CLEAR;
        $this->dataPacket($pk);
    }

    /**
     * Sets the title duration.
     *
     * @param int $fadeIn  Title fade-in time in ticks.
     * @param int $stay    Title stay time in ticks.
     * @param int $fadeOut Title fade-out time in ticks.
     */
    public function setTitleDuration(int $fadeIn, int $stay, int $fadeOut){
        if($fadeIn >= 0 and $stay >= 0 and $fadeOut >= 0){
            $pk = new SetTitlePacket();
            $pk->type = SetTitlePacket::TYPE_TIMES;
            $pk->fadeInDuration = $fadeIn;
            $pk->duration = $stay;
            $pk->fadeOutDuration = $fadeOut;
            $this->dataPacket($pk);
        }
    }

    /**
     * Internal function used for sending titles.
     *
     * @param string $title
     * @param int    $type
     */
    protected function sendTitleText(string $title, int $type){
        $pk = new SetTitlePacket();
        $pk->type = $type;
        $pk->title = $title;
        $this->dataPacket($pk);
    }

    /**
     * @param string $address
     * @param        $port
     */
    public function transfer(string $address, $port){
        $pk = new TransferPacket();
        $pk->address = $address;
        $pk->port = $port;
        $this->dataPacket($pk);
        // $this->close('','');
    }

    /**
     * Sends a direct chat message to a player
     *
     * @param string|TextContainer $message
     *
     * @return bool
     */
    public function sendMessage($message, $force=false){
        if(($this->onTutorial() or $this->isLogin===false) and $force===false)return;
        if($message instanceof TextContainer){
            if($message instanceof TranslationContainer){
                $this->sendTranslation($message->getText(), $message->getParameters());
                return false;
            }

            $message = $message->getText();
        }

        //TODO: Remove this workaround (broken client MCPE 1.0.0)
        $this->messageQueue[] = $this->server->getLanguage()->translateString($message);
        /*
		$pk = new TextPacket();
		$pk->type = TextPacket::TYPE_RAW;
		$pk->message = $this->server->getLanguage()->translateString($message);
		$this->dataPacket($pk);
		*/
    }

    /**
     * @param       $message
     * @param array $parameters
     *
     * @return bool
     */
    public function sendTranslation($message, array $parameters = []){
        $pk = new TextPacket();
        if(!$this->server->isLanguageForced()){
            $pk->type = TextPacket::TYPE_TRANSLATION;
            $pk->message = $this->server->getLanguage()->translateString($message, $parameters, 'pocketmine.');
            foreach($parameters as $i => $p){
                $parameters[$i] = $this->server->getLanguage()->translateString($p, $parameters, 'pocketmine.');
            }
            $pk->parameters = $parameters;
        }else{
            $pk->type = TextPacket::TYPE_RAW;
            $pk->message = $this->server->getLanguage()->translateString($message, $parameters);
        }

        // $ev = new PlayerTextPreSendEvent($this, $pk->message, PlayerTextPreSendEvent::TRANSLATED_MESSAGE);
        // $this->server->getPluginManager()->callEvent($ev);
        // if(!$ev->isCancelled()){
        $this->dataPacket($pk);
        return true;
        // }
        // return false;
    }

    /**
     * @param        $message
     * @param string $subtitle
     *
     * @return bool
     */
    public function sendPopup($message, $subtitle = ''){
        /*$ev = new PlayerTextPreSendEvent($this, $message, PlayerTextPreSendEvent::POPUP);
		$this->server->getPluginManager()->callEvent($ev);
		if(!$ev->isCancelled()){*/
        $pk = new TextPacket();
        $pk->type = TextPacket::TYPE_POPUP;
        $pk->source = $message;
        $pk->message = $subtitle;
        $this->dataPacket($pk);
        return true;
        // }
        // return false;
    }

    /**
     * @param $message
     *
     * @return bool
     */
    public function sendTip($message){
        /*$ev = new PlayerTextPreSendEvent($this, $message, PlayerTextPreSendEvent::TIP);
		$this->server->getPluginManager()->callEvent($ev);
		if(!$ev->isCancelled()){*/
        $pk = new TextPacket();
        $pk->type = TextPacket::TYPE_TIP;
        $pk->message = $message;
        $this->dataPacket($pk);
        return true;
        // }
        // return false;
    }

    /**
     * 发送屏幕中间Tip
     * @param $message
     * @return bool
     */
    public function sendCenterTip($message){
        return $this->sendTip($message."\n\n\n\n\n\n\n\n\n\n\n\n\n");
    }

    /**
     * 发送屏幕顶部Tip
     * @param $message
     * @return bool
     */
    public function sendTopTip($message){
        return $this->sendTip($message."\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n\n");
    }

    /**
     * 获取全部观众 包括 [@link Player]$this
     * @return array
     */
    public function getAllViewer(){
        return array_merge($this->getViewers(), [$this]);
    }
    /**
     * Send a title text or/and with/without a sub title text to a player
     *
     * @param        $title
     * @param string $subtitle
     * @param int    $fadein
     * @param int    $fadeout
     * @param int    $duration
     *
     * @return bool
     */
    public function sendTitle($title, $subtitle = '', $fadein = 20, $fadeout = 20, $duration = 5){
        return $this->addTitle($title, $subtitle, $fadein, $duration, $fadeout);
    }

    /**
     * Note for plugin developers: use kick() with the isAdmin
     * flag set to kick without the 'Kicked by admin' part instead of this method.
     *
     * @param string $message Message to be broadcasted
     * @param string $reason  Reason showed in console
     * @param bool   $notify
     */
    public final function close($message = '', $reason = '断开连接', $notify = true,$onLogin=false){
        if($this->isDie!==false and !$this->closed){
            $this->setGamemode($this->dieMessage[0], false, true);
            $this->isDie=false;
            $this->extinguish();
            $this->setHealth($this->getMaxHealth());
            $this->setFood(20);
            $this->server->removePlayerListData($this->dieMessage[1], $this->dieMessage[3]);
            $pk = new RemoveEntityPacket();
            $pk->eid = $this->dieMessage[2];
            $this->server->broadcastPacket($this->dieMessage[3],$pk);
        }
        if($this->connected and !$this->closed){
            if($notify and strlen((string) $reason) > 0){//理由
                $pk = new DisconnectPacket();
                $pk->message = $reason;
                $this->directDataPacket($pk);
            }

            $this->removeEffect(Effect::HEALTH_BOOST);

            if($this->getFloatingInventory() instanceof FloatingInventory) {
                foreach ($this->getFloatingInventory()->getContents() as $index=>$item) {
                    if ($item instanceof LTItem) {
                        $this->inventory->addItem($item);
                        $this->getFloatingInventory()->setItem($index, Item::get(0));
                    }
                }
            }
            if(strlen($this->getName()) > 0 AND $onLogin===false){
                $this->server->getPluginManager()->callEvent($ev = new PlayerQuitEvent($this, 'test', true));
                if($this->loggedIn === true){
                    if(\LTLogin\Events::$status[strtolower($this->username)]!==true and in_array(\LTLogin\Events::$status[strtolower($this->username)], ['steve', 'register', 'more']))
                        @unlink($this->server->getDataPath().'players'. DIRECTORY_SEPARATOR .strtolower($this->username).'.dat');
                    else
                        $this->save();
                    unset(\LTLogin\Events::$status[strtolower($this->username)]);
                }
            }
            $this->connected = false;
            foreach($this->server->getOnlinePlayers() as $player){
                if(!$player->canSee($this)){
                    $player->showPlayer($this);
                }
            }
            $this->hiddenPlayers = [];

            foreach($this->windowIndex as $window){
                $this->removeWindow($window);
            }

            foreach($this->usedChunks as $index => $d){
                Level::getXZ($index, $chunkX, $chunkZ);
                $this->level->unregisterChunkLoader($this, $chunkX, $chunkZ);
                foreach($this->level->getChunkEntities($chunkX, $chunkZ) as $entity){
                    $entity->despawnFrom($this, false);
                }
                foreach($this->level->getFloatingTexts($chunkX, $chunkZ) as $FloatingText){
                    $FloatingText->despawnFrom($this);
                }
                foreach($this->level->getNPCs($chunkX, $chunkZ) as $NPC){
                    $NPC->despawnFrom($this);
                }
                unset($this->usedChunks[$index]);
            }

            $this->interface->close($this, $notify ? $reason : '');

            if($this->loggedIn){
                $this->server->removeOnlinePlayer($this);
            }

            $this->loggedIn = false;
            $this->API = null;
            $this->Task = null;
            $this->Buff = null;
            $this->WaitingSendEntity = [];
            $this->WaitingSendFloatingText = [];
            $this->FloatingTexts = [];
            $this->NPCs = [];

            $this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_USERS, $this);
            $this->server->getPluginManager()->unsubscribeFromPermission(Server::BROADCAST_CHANNEL_ADMINISTRATIVE, $this);

            if(isset($ev) and $this->username != '' and $this->spawned !== false and $ev->getQuitMessage() != ''){
                // if($this->server->playerMsgType === Server::PLAYER_MSG_TYPE_MESSAGE) $this->server->broadcastMessage($ev->getQuitMessage());
                $this->server->broadcastTip(str_replace('@player', $this->getName(), $this->server->playerLogoutMsg),null,2);
                // elseif($this->server->playerMsgType === Server::PLAYER_MSG_TYPE_POPUP) $this->server->broadcastPopup(str_replace('@player', $this->getName(), $this->server->playerLogoutMsg));
            }

            $this->spawned = false;
            $this->server->getLogger()->info($this->getServer()->getLanguage()->translateString('pocketmine.player.logOut', [
                TextFormat::AQUA . $this->getName() . TextFormat::WHITE,
                $this->ip,
                $this->getServer()->getLanguage()->translateString($reason)
            ]));
            $this->windows = new \SplObjectStorage();
            $this->windowIndex = [];
            $this->usedChunks = [];
            $this->loadQueue = [];
            $this->spawnPosition = null;

            if($this->server->dserverConfig['enable'] and $this->server->dserverConfig['queryAutoUpdate']) $this->server->updateQuery();

            if($this->perm !== null){
                $this->perm->clearPermissions();
                $this->perm = null;
            }

            $this->inventory = null;
            // $this->floatingInventory = null;
            $this->enderChestInventory = null;
            $this->transactionQueue = null;

            $this->chunk = null;

            $this->server->removePlayer($this);

            parent::close();
        }
    }

    /**
     * @return array
     */
    public function __debugInfo(){
        return [];
    }

    /**
     * Handles player data saving
     *
     * @param bool $async
     */
    public function save($async = false){
        if($this->closed){
            throw new \InvalidStateException('Tried to save closed player');
        }
        parent::saveNBT();
        if($this->level instanceof Level){
            $this->namedtag->Level = new StringTag('Level', $this->level->getName());


            $this->namedtag['playerGameType'] = $this->gamemode;
            $this->namedtag['lastPlayed'] = new LongTag('lastPlayed', floor(microtime(true) * 1000));
            $this->namedtag['Health'] = new ShortTag('Health', ($this->getHealth()>$this->getYMaxHealth()?$this->getYMaxHealth():$this->getHealth()));

            if($this->username != '' and $this->namedtag instanceof CompoundTag){
                $this->server->saveOfflinePlayerData($this->username, $this->namedtag, $async);
            }
        }
    }

    /**
     * Gets the username
     *
     * @return string
     */
    public function getName(){
        return $this->username;
    }

    public function kill(){
        if(!$this->spawned){
            return;
        }

        $message = 'death.attack.generic';

        $params = [
            $this->getDisplayName()
        ];

        $cause = $this->getLastDamageCause();

        switch($cause === null ? EntityDamageEvent::CAUSE_CUSTOM : $cause->getCause()){
            case EntityDamageEvent::CAUSE_ENTITY_ATTACK:
                if($cause instanceof EntityDamageByEntityEvent){
                    $e = $cause->getDamager();
                    if($e instanceof Player){
                        $message = 'death.attack.player';
                        $params[] = $e->getDisplayName();
                        break;
                    }elseif($e instanceof Living){
                        $message = 'death.attack.mob';
                        $params[] = $e->getNormalName();
                        break;
                    }else{
                        $params[] = 'Unknown';
                    }
                }
                break;
            case EntityDamageEvent::CAUSE_PROJECTILE:
                if($cause instanceof EntityDamageByEntityEvent){
                    $e = $cause->getDamager();
                    if($e instanceof Player){
                        $message = 'death.attack.arrow';
                        $params[] = $e->getDisplayName();
                    }elseif($e instanceof Living){
                        $message = 'death.attack.arrow';
                        $params[] = $e->getNormalName();
                        break;
                    }else{
                        $params[] = 'Unknown';
                    }
                }
                break;
            case EntityDamageEvent::CAUSE_STARVATION:
                $message = 'death.attack.starvation';
                break;
            case EntityDamageEvent::CAUSE_SUICIDE:
                $message = 'death.attack.generic';
                break;
            case EntityDamageEvent::CAUSE_VOID:
                $message = 'death.attack.outOfWorld';
                break;
            case EntityDamageEvent::CAUSE_FALL:
                if($cause instanceof EntityDamageEvent){
                    if($cause->getFinalDamage() > 2){
                        $message = 'death.fell.accident.generic';
                        break;
                    }
                }
                $message = 'death.attack.fall';
                break;

            case EntityDamageEvent::CAUSE_SUFFOCATION:
                $message = 'death.attack.inWall';
                break;

            case EntityDamageEvent::CAUSE_LAVA:
                $message = 'death.attack.lava';
                break;

            case EntityDamageEvent::CAUSE_FIRE:
                $message = 'death.attack.onFire';
                break;

            case EntityDamageEvent::CAUSE_LIGHTNING:
                $target=$cause->getDamager();
                if($target->getOwner()!==null){
                    $message = 'death.attack.player.lightning';
                    if($target->getOwner() instanceof Player)
                        $params[] = $target->getOwner()->getDisplayName();
                    else
                        $params[] = $target->getOwner()->getNormalName();
                }else{
                    $message = 'death.attack.lightning';
                }
                break;
            case EntityDamageEvent::CAUSE_FIRE_TICK:
                $message = 'death.attack.inFire';
                break;
            case EntityDamageEvent::CAUSE_HT:
                $message = 'death.attack.ht';
                break;
            case EntityDamageEvent::CAUSE_DROWNING:
                $message = 'death.attack.drown';
                break;
            case EntityDamageEvent::CAUSE_THORNS:
                $message = 'death.attack.thorns';
                $params[] = $cause->getDamager()->getDisplayName();
                break;
            case EntityDamageEvent::CAUSE_CONTACT:
                if($cause instanceof EntityDamageByBlockEvent){
                    if($cause->getDamager()->getId() === Block::CACTUS){
                        $message = 'death.attack.cactus';
                    }
                }
                break;

            case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
            case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
                if($cause instanceof EntityDamageByEntityEvent){
                    $e = $cause->getDamager();
                    if($e instanceof Player){
                        $message = 'death.attack.explosion.player';
                        $params[] = $e->getDisplayName();
                    }elseif($e instanceof Living){
                        $message = 'death.attack.explosion.player';
                        $params[] = $e->getNormalName();
                        break;
                    }else{
                        $message = 'death.attack.explosion';
                        break;
                    }
                }else{
                    $message = 'death.attack.explosion';
                }
                break;

            case EntityDamageEvent::CAUSE_MAGIC:
                $message = 'death.attack.magic';
                break;

            case EntityDamageEvent::CAUSE_CUSTOM:
                break;
            case EntityDamageEvent::CAUSE_SECONDS_KILL:
                $message = 'death.attack.secondsKill';
                $e = $cause->getDamager();
                if($e instanceof Player){
                    $params[] = $e->getDisplayName();
                }elseif($e instanceof Living){
                    $params[] = $e->getNormalName();
                    break;
                }
                break;

            case EntityDamageEvent::CAUSE_EAT:
                $message = 'death.attack.eat';
                $e = $cause->getDamager();
                if($e instanceof Player){
                    $params[] = $e->getDisplayName();
                }elseif($e instanceof Living){
                    $params[] = $e->getNormalName();
                    break;
                }
                break;

            case EntityDamageEvent::CAUSE_DIDI:
                $message = 'death.attack.didi';
                $e = $cause->getDamager();
                if($e instanceof Player){
                    $params[] = $e->getDisplayName();
                }elseif($e instanceof Living){
                    $params[] = $e->getNormalName();
                    break;
                }
                break;

            case EntityDamageEvent::CAUSE_PUNISHMENT:
                $message = 'death.attack.punishment';
                break;
            default:

        }

        // Entity::killPlayer($this);

        $ev = new PlayerDeathEvent($this, $this->getDrops(), new TranslationContainer($message, $params));
        $ev->setKeepInventory($this->server->keepInventory);
        $ev->setKeepExperience($this->server->keepExperience);
        $this->server->getPluginManager()->callEvent($ev);
        $this->server->BroadCastMessage($ev->getDeathMessage());
        // return $ev->getDeathMessage();
    }

    /**
     * @return bool
     */
    public function isA(){
        return $this->isDie===false;
    }
    public function newProgress($name, $info = '', $type = 'achievement'){
        if ($type=='achievement'){
            if (!$this->getAStatusIsDone($name)){
                $this->addAStatus($name);
                $add = '';
                if ($info!='')$add = '§e('.$info.')';
                $this->getServer()->broadcastMessage(($this->username.'取得了进度§a['.$name.']'.$add));
            }
        }elseif($type=='challenge'){
            if (!$this->getAStatusIsDone($name)){
                $this->addAStatus($name);
                $add = '';
                if ($info!='')$add = '§e('.$info.')';
                $this->getServer()->broadcastMessage(($this->username.'完成了挑战§d['.$name.']'.$add));
                $this->getLevel()->addSound(new AnvilBreakSound($this), [$this]);
            }
        }
    }
    /**
     * @param int $amount
     */
    public function reduceMoney($amount, $info){
        EconomyAPI::getInstance()->reduceMoney($this, $amount, $info);
    }
    public function addMoney($amount, $info){
        EconomyAPI::getInstance()->addMoney($this, $amount, $info);
    }
    public function getMoney(){
        return EconomyAPI::getInstance()->MyMoney($this);
    }
    public function setHealth($amount){
        if($this->getGamemode()===3 or $this->closed or (($this->level->getName()==='zc' or $this->level->getName()==='create') and $this->getHealth()>$amount))return;
        if($amount<=0){
            if (!$this->isA())return;
            if(isset($this->tickAttackTask)){
                $this->server->getScheduler()->cancelTask($this->tickAttackTask->getTaskId());
                unset($this->tickAttackTask);
                unset($this->lastPosition);
            }
            $this->kill();
            if($this->freezeTime>0)
                $this->freezeTime=0;
            $this->setDataFlag(self::DATA_FLAGS,self::DATA_FLAG_IMMOBILE, false);
            if($this->vertigoTime>0){
                $this->vertigoTime=0;
            }
            if($this->level->getName()=='boss' or $this->level->getName()=='pvp')
                $this->lastDie=null;
            else
                $this->lastDie=$this->asPosition();
            if(isset($this->diePos)){
                $this->lastDie=$this->diePos;
                unset($this->diePos);
            }
            $this->removeAllEffects();
            /*$this->getBuff()->runEffect();*/
            $this->dieMessage=[];
            $this->dieMessage[]=$this->getGamemode();
            $pk = new AddPlayerPacket();
            $pk->uuid = $uid=UUID::fromRandom();
            $pk->username = $this->getName();
            $pk->eid = $eid=Entity::$entityCount++;
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->speedX = $this->motionX;
            $pk->speedY = $this->motionY;
            $pk->speedZ = $this->motionZ;
            $pk->yaw = $this->yaw;
            $pk->pitch = $this->pitch;
            $hand=$this->getInventory()->getItemInHand();
            if($hand instanceof LTItem){
                if(isset($hand->getNamedTag()['ench'])){
                    $hand=Item::get($hand->getId(),$hand->getDamage(),1);
                    $hand->setNamedTag(new CompoundTag('',[
                        'ench'=>new ListTag('ench', [])
                    ]));
                }else $hand=Item::get($hand->getId(),$hand->getDamage(),1);
            }
            $pk->item = $hand;
            $pk->metadata = $this->dataProperties;
            $this->dieMessage[]=$uid;
            $this->dieMessage[]=$eid;
            $this->server->broadcastPacket($this->level->getPlayers(),$pk);
            $this->server->updatePlayerListData($uid, $eid, $this->getName().'_死亡实体', $this->skinId, $this->skin, $this->level->getPlayers());
            $pk = new MobArmorEquipmentPacket();
            $pk->eid = $eid;
            $pk->slots = $this->inventory->getArmorContents();
            $this->server->broadcastPacket($this->level->getPlayers(),$pk);
            $pk=new EntityEventPacket();
            $pk->eid=$eid;
            $pk->event=EntityEventPacket::DEATH_ANIMATION;
            $this->server->broadcastPacket($this->level->getPlayers(),$pk);
            $this->dieMessage[]=$this->level->getPlayers();
            if(\LTMenu\Main::getInstance()->getOpen($this)!==null){
                \LTMenu\Main::getInstance()->getOpen($this)->setDie();
                \LTMenu\Main::getInstance()->getOpen($this)->close();
                return;
            }
            $this->isDie=60;
            $this->lastDieTime=time();
            if($this->getFloatingInventory() instanceof FloatingInventory) {
                foreach ($this->getFloatingInventory()->getContents() as $index=>$item) {
                    if ($item instanceof LTItem) {
                        $this->inventory->addItem($item);
                        $this->getFloatingInventory()->setItem($index, Item::get(0));
                    }
                }
            }
            $this->setGamemode(3, false, true);
            $this->addTitle('§l§c你死了！','§l§d3秒后复活',50,100,50);
            return;
        }
        parent::setHealth($amount);
        if($this->getAStatusIsDone('血量格式')){
            if($this->spawned === true)$this->foodTick = 0;
            return;
        }
        if($this->spawned === true){
            $this->foodTick = 0;
            $this->getAttributeMap()->getAttribute(Attribute::HEALTH)->setMaxValue($this->getMaxHealth())->setValue($amount, true);
        }
    }
    /**
     * @param $amount
     */
    public function setMovementSpeed($amount){
        if($this->spawned === true){
            $this->getAttributeMap()->getAttribute(Attribute::MOVEMENT_SPEED)->setValue($amount, true);
        }
    }

    /**
     * @return bool
     */
    public function canSelected(): bool{
        return $this->waitingTeleportTask==null and $this->lastTeleportTick!==0 and $this->getServer()->getTick() - $this->lastTeleportTick > 40;
    }

    /**
     * @return bool
     */
    public function onTeleport(): bool{
        return $this->waitingTeleportTask!==null or $this->lastTeleportTick===0;
    }
    /**
     * @param float             $damage
     * @param EntityDamageEvent $source
     *
     * @return bool
     */
    public function attack($damage, EntityDamageEvent $source){
        if(!$this->isA() or !$this->canSelected()){
            return false;
        }

        if($this->isCreative()
            and $source->getCause() !== EntityDamageEvent::CAUSE_MAGIC
            and $source->getCause() !== EntityDamageEvent::CAUSE_SUICIDE
            and $source->getCause() !== EntityDamageEvent::CAUSE_VOID
        ){
            $source->setCancelled();
        }elseif($this->getAllowFlight() and $source->getCause() === EntityDamageEvent::CAUSE_FALL){
            $source->setCancelled();
        }

        parent::attack($damage, $source);

        if($source->isCancelled()){
            return false;
        }elseif($this->getLastDamageCause() === $source and $this->spawned){
            $pk = new EntityEventPacket();
            $pk->eid = $this->id;
            $pk->event = EntityEventPacket::HURT_ANIMATION;
            $this->dataPacket($pk);

            if($this->isSurvival()){
                $this->exhaust(0.03, PlayerExhaustEvent::CAUSE_DAMAGE);
            }
        }
        return true;
    }

    /**
     * @param Vector3    $pos
     * @param null       $yaw
     * @param null       $pitch
     * @param int        $mode
     * @param array|null $targets
     */
    public function sendPosition(Vector3 $pos, $yaw = null, $pitch = null, $mode = MovePlayerPacket::MODE_NORMAL, array $targets = null){
        $yaw = $yaw === null ? $this->yaw : $yaw;
        $pitch = $pitch === null ? $this->pitch : $pitch;

        $pk = new MovePlayerPacket();
        $pk->eid = $this->getId();
        $pk->x = $pos->x;
        $pk->y = $pos->y + $this->getEyeHeight();
        $pk->z = $pos->z;
        $pk->bodyYaw = $yaw;
        $pk->pitch = $pitch;
        $pk->pitch = $pitch;
        $pk->yaw = $yaw;
        $pk->mode = $mode;

        if($targets !== null){
            $this->server->broadcastPacket($targets, $pk);
        }else{
            $this->dataPacket($pk);
        }

        $this->newPosition = null;
    }

    public function checkChunks(){
        if($this->chunk === null or ($this->chunk->getX() !== ($this->x >> 4) or $this->chunk->getZ() !== ($this->z >> 4))){
            if($this->chunk !== null){
                $this->chunk->removeEntity($this);
            }
            $this->chunk = $this->level->getChunk($this->x >> 4, $this->z >> 4, true);

            if(!$this->justCreated){
                $newChunk = $this->level->getChunkPlayers($this->x >> 4, $this->z >> 4);
                unset($newChunk[$this->getLoaderId()]);

                /** @var Player[] $reload */
                $reload = [];
                foreach($this->hasSpawned as $player){
                    if(!isset($newChunk[$player->getLoaderId()])){
                        $this->despawnFrom($player);
                    }else{
                        unset($newChunk[$player->getLoaderId()]);
                        $reload[] = $player;
                    }
                }

                foreach($newChunk as $player){
                    $this->spawnTo($player);
                }
            }

            if($this->chunk === null){
                return;
            }

            $this->chunk->addEntity($this);
        }
    }

    /**
     * @return bool
     */
    protected function checkTeleportPosition(){
        if($this->teleportPosition !== null){
            $chunkX = $this->teleportPosition->x >> 4;
            $chunkZ = $this->teleportPosition->z >> 4;

            for($X = -1; $X <= 1; ++$X){
                for($Z = -1; $Z <= 1; ++$Z){
                    if(!isset($this->usedChunks[$index = Level::chunkHash($chunkX + $X, $chunkZ + $Z)]) or $this->usedChunks[$index] === false){
                        return false;
                    }
                }
            }

            $this->sendPosition($this, null, null, MovePlayerPacket::MODE_RESET);
            $this->spawnToAll();
            $this->forceMovement = $this->teleportPosition;
            $this->teleportPosition = null;

            return true;
        }

        return true;
    }

    /**
     * @param Vector3 $pos
     * @return bool
     */
    public function setPosition(Vector3 $pos)
    {

        if ($this->dimension!==$this->getLevel()->getDimension()){
            $this->dimension = $this->getLevel()->getDimension();
            $pk = new ChangeDimensionPacket();
            $pk->dimension = $this->dimension;
            $pk->x = $this->getX();
            $pk->y = $this->getY();
            $pk->z = $this->getZ();
            $this->dataPacket($pk);
        }
        return parent::setPosition($pos); // TODO: Change the autogenerated stub
    }

    /**
     * @param Vector3 $pos
     * @param null $yaw
     * @param null $pitch
     * @param bool $crucial
     * @param bool $force
     * @param bool $refresh
     * @return bool
     */
    public function teleport(Vector3 $pos, $yaw = null, $pitch = null, $crucial=true, $force = false, $refresh = false){
        if(!$this->isOnline()){
            return false;
        }
        if (!($pos instanceof Position)){
            $pos = new Position($pos->getX(), $pos->getY(), $pos->getZ(), $this->getLevel());
        }
        if($pos instanceof Position and $pos->level!==$this->level and isset($this->tickAttackTask))return false;
        if($this->level->getName()=='boss' and !$force and (!($pos instanceof Position) or $pos->level===$this->level)){
            $this->sendMessage('§l§a[提示]§c这个世界不能传送!');
            return false;
        }
        if($pos instanceof Position and $pos->getLevel()->getName()==='login' and !$this->isOp()){
            $this->sendMessage('§l§a[提示]§c这个世界不能通往');
            return false;
        }
        if($this->waitingTeleportTask==null){
            $this->server->getPluginManager()->callEvent($ev = new EntityTeleportEvent($this, $this, $pos));
            if($ev->isCancelled()){
                return false;
            }
            if($crucial!==false){//不重要的传送事件
                $this->lastPos=$this->asLocation();
                $this->sendMessage('§l§a[提示]§e输入/bt 回到传送前的位置！');
            }
            if (($pos instanceof Position and $pos->getLevel()->getName()!=$this->getLevel()->getName() and $pos->getLevel()->getDimension()==$this->getLevel()->getDimension()) or $refresh === true) {
                $pk = new ChangeDimensionPacket();
                $pk->dimension = $this->getLevel()->getDimension()==0?1:0;
                $pk->x = $this->getX();
                $pk->y = $this->getY();
                $pk->z = $this->getZ();
                $this->dataPacket($pk);
                $this->dimension = $pk->dimension;
                foreach ($this->usedChunks as $index=>$v){
                    Level::getXZ($index, $X, $Z);
                    $this->unloadChunk($X, $Z);
                }
                $this->waitingTeleportTask = $pos;
                $this->loadQueue = [];
                $this->nextChunkOrderRun = 0;
                $this->newPosition = null;
                $this->lastLevel = $this->getLevel();
                $this->teleportPosition = new Vector3($pos->x, $pos->y, $pos->z);
                return true;
            }
        }
        $oldPos = $this->getPosition();
        if(parent::teleport($pos, $yaw, $pitch, $crucial, $force)){

            foreach($this->windowIndex as $window){
                if($window === $this->inventory){
                    continue;
                }
                $this->removeWindow($window);
            }

            $this->teleportPosition = new Vector3($this->x, $this->y, $this->z);

            if(!$this->checkTeleportPosition()){
                $this->forceMovement = $oldPos;
            }else{
                $this->spawnToAll();
            }


            $this->resetFallDistance();
            $this->nextChunkOrderRun = 0;
            $this->newPosition = null;
            $this->stopSleep();
            $this->waitingTeleportTask = null;
            $this->WaitingSendEntity = [];
            $this->WaitingSendFloatingText = [];
            $this->WaitingSendNPC = [];
            if($this->getFloatingInventory() instanceof FloatingInventory) {
                foreach ($this->getFloatingInventory()->getContents() as $index=>$item) {
                    if ($item instanceof LTItem) {
                        $this->inventory->addItem($item);
                        $this->getFloatingInventory()->setItem($index, Item::get(0));
                    }
                }
            }
            return true;
        }
        return false;
    }
    //传送的时候是否需要刷新
    public function updateTeleport(){
        return $this->updateTeleport;
    }
    /**
     * This method may not be reliable. Clients don't like to be moved into unloaded chunks.
     * Use teleport() for a delayed teleport after chunks have been sent.
     *
     * @param Vector3 $pos
     * @param float   $yaw
     * @param float   $pitch
     */
    public function teleportImmediate(Vector3 $pos, $yaw = null, $pitch = null){
        if(parent::teleport($pos, $yaw, $pitch)){

            foreach($this->windowIndex as $window){
                if($window === $this->inventory){
                    continue;
                }
                $this->removeWindow($window);
            }

            $this->forceMovement = new Vector3($this->x, $this->y, $this->z);
            $this->sendPosition($this, $this->yaw, $this->pitch, MovePlayerPacket::MODE_RESET);


            $this->resetFallDistance();
            $this->orderChunks();
            $this->nextChunkOrderRun = 0;
            $this->newPosition = null;
        }
    }


    /**
     * @param Inventory $inventory
     *
     * @return int
     */
    public function getWindowId(Inventory $inventory) : int{
        if($this->windows->contains($inventory)){
            return $this->windows[$inventory];
        }

        return -1;
    }
    public function setWindow($id, Inventory $inventory){
        $this->windows->detach($this->windowIndex[$id]);
        unset($this->windowIndex[$id]);
        $this->windowIndex[$id] = $inventory;
        $this->windows->attach($inventory, $id);
    }
    /**
     * Returns the created/existing window id
     *
     * @param Inventory $inventory
     * @param int       $forceId
     *
     * @return int
     */
    public function addWindow(Inventory $inventory, $forceId = null) : int{
        if($this->windows->contains($inventory)){
            return $this->windows[$inventory];
        }

        if($forceId === null){
            $this->windowCnt = $cnt = max(2, ++$this->windowCnt % 99);
        }else{
            $cnt = (int) $forceId;
        }
        $this->windowIndex[$cnt] = $inventory;
        $this->windows->attach($inventory, $cnt);
        if($inventory->open($this)){
            return $cnt;
        }else{
            $this->removeWindow($inventory);

            return -1;
        }
    }

    /**
     * @param Inventory $inventory
     */
    public function removeWindow(Inventory $inventory){
        $inventory->close($this);
        if($this->windows->contains($inventory)){
            $id = $this->windows[$inventory];
            $this->windows->detach($this->windowIndex[$id]);
            unset($this->windowIndex[$id]);
        }
    }

    /**
     * @param string        $metadataKey
     * @param MetadataValue $metadataValue
     */
    public function setMetadata($metadataKey, MetadataValue $metadataValue){
        $this->server->getPlayerMetadata()->setMetadata($this, $metadataKey, $metadataValue);
    }

    /**
     * @param string $metadataKey
     *
     * @return MetadataValue[]
     */
    public function getMetadata($metadataKey){
        return $this->server->getPlayerMetadata()->getMetadata($this, $metadataKey);
    }

    /**
     * @param string $metadataKey
     *
     * @return bool
     */
    public function hasMetadata($metadataKey){
        return $this->server->getPlayerMetadata()->hasMetadata($this, $metadataKey);
    }

    /**
     * @param string $metadataKey
     * @param Plugin $plugin
     */
    public function removeMetadata($metadataKey, Plugin $plugin){
        $this->server->getPlayerMetadata()->removeMetadata($this, $metadataKey, $plugin);
    }

    /**
     * @param Chunk $chunk
     */
    public function onChunkChanged(Chunk $chunk){
        if(isset($this->usedChunks[$hash = Level::chunkHash($chunk->getX(), $chunk->getZ())])){
            $this->usedChunks[$hash] = false;
        }
        if(!$this->spawned){
            $this->nextChunkOrderRun = 0;
        }
    }

    /**
     * @param Chunk $chunk
     */
    public function onChunkLoaded(Chunk $chunk){

    }

    /**
     * @param Chunk $chunk
     */
    public function onChunkPopulated(Chunk $chunk){

    }

    /**
     * @param Chunk $chunk
     */
    public function onChunkUnloaded(Chunk $chunk){

    }

    /**
     * @param Vector3 $block
     */
    public function onBlockChanged(Vector3 $block){

    }

    /**
     *
     */
    public function removeBadEffect(){
        foreach($this->effects as $effect){
            if($effect->isBad())$this->removeEffect($effect->getId());
        }
    }
    /**
     * @return int|null
     */
    public function getLoaderId(){
        return $this->loaderId;
    }

    /**
     * @return bool
     */
    public function isLoaderActive(){
        return $this->isConnected();
    }

    /**
     * @param Effect $effect
     *
     * @return bool|void
     * @internal param $Effect
     */
    public function removeEffect($effectId){
        parent::removeEffect($effectId);
        if($this->getAPI()!==null)
            if($effectId==11)
                $this->getAPI()->update(1);
        // elseif($effectId==5)
        // $this->getAPI()->update(2);
    }
    public function addEffect(Effect $effect){//Overwrite
        if($effect->isBad() && $this->isCreative()){
            return false;
        }
        // System.out.println($effect.getId());
        if(in_array($effect->getId(), [9, 2, 15, 24]))
            $effect->setDuration((int)($effect->getDuration())*(100-$this->getBuff()->getControlReduce())/100);
        parent::addEffect($effect);
        if($this->getAPI()!==null)
            if($effect->getID()==11)
                $this->getAPI()->update(1);
        // elseif($effect->getID()==5)
        // $this->getAPI()->update(2);
        return true;
    }
}
