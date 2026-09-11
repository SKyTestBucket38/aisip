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

use pocketmine\level\particle\FloatingTextParticle;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;

use pocketmine\entity\{Human, Creature, LavaSlime};
use pocketmine\Player;
use pocketmine\entity\{Entity, Effect};

use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\{CompoundTag, ListTag, DoubleTag, FloatTag, StringTag};

use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent, EntityInventoryChangeEvent};

use pocketmine\network\mcpe\protocol\{AddEntityPacket, RemoveEntityPacket, BlockEventPacket, AddItemEntityPacket};

class lvl extends PluginBase implements Listener {

	function onEnable(){

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->economy = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
        $this->shop = $this->getServer()->getPluginManager()->getPlugin('Shop');

		$this->db = new \SQLite3($this->getDataFolder() .'lvl.db');
        $this->db->query("CREATE TABLE IF NOT EXISTS `lvl` (`username` TEXT NOT NULL, `lvl` INTEGER NOT NULL, `exp` INTEGER NOT NULL);");
    }

    function getL($player, $entry = 'lvl'){

        $username = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);

        $result = $this->db->query("SELECT `$entry` FROM `lvl` WHERE `username` = '$username'")->fetchArray(SQLITE3_ASSOC);

        if(isset($result[$entry])) return $result[$entry]; else if($entry == 'lvl') return 1; else return 0;
    }
}
?>