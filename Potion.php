<?php

use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\Server;
use pocketmine\plugin\Plugin;
use pocketmine\math\Vector3;
use pocketmine\tile\Sign;
use pocketmine\tile\ItemFrame;
use pocketmine\item\Item;
use pocketmine\event\player\{PlayerInteractEvent, PlayerMoveEvent, PlayerJoinEvent, PlayerQuitEvent};
use pocketmine\tile\Tile;
use pocketmine\utils\TextFormat as F;
use onebone\economyapi\EconomyAPI;
use pocketmine\inventory\PlayerInventory;

use pocketmine\entity\{Human, Creature, LavaSlime};
use pocketmine\Player;
use pocketmine\entity\{Entity, Effect};

use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\{CompoundTag, ListTag, DoubleTag, FloatTag, StringTag};

use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent, EntityInventoryChangeEvent};

use pocketmine\network\mcpe\protocol\{AddEntityPacket, RemoveEntityPacket, BlockEventPacket, AddItemEntityPacket};

class Potion extends PluginBase implements Listener{

	function onEnable(){

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    function onJoin(PlayerJoinEvent $event){

    	$player = $event->getPlayer();

    	$this->getEntity($player);
    }

    function getEntity($player){

    	$username = strtolower($player->getName());
		
		foreach($this->getServer()->getDefaultLevel()->getEntities() as $entity){

		    if($entity instanceof LavaSlime && !$entity instanceof Player) if($entity->getNameTag() === 'potion_1' || $entity->getNameTag() === 'potion_2' || $entity->getNameTag() === 'rubak_1'){

				$entity->despawnFrom($player);
			}
		}
	}
}
?>