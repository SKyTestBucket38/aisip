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

class q extends PluginBase implements Listener {

	function onEnable(){

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->economy = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
        $this->shop = $this->getServer()->getPluginManager()->getPlugin('Shop');

		$this->db = new \SQLite3($this->getDataFolder() .'q.db');
        $this->db->query("CREATE TABLE IF NOT EXISTS `q` (`username` TEXT NOT NULL, `id` INTEGER NOT NULL, `count` INTEGER NOT NULL);");
    }

    function getQ($player, $entry = 'id'){

        $username = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);

        $result = $this->db->query("SELECT `$entry` FROM `q` WHERE `username` = '$username'")->fetchArray(SQLITE3_ASSOC);

        if(isset($result[$entry])) return $result[$entry]; else return 0;
    }

    function flytext($p){

    	$x=9578+0.5; $y=69; $z=-9679+0.5;

        $username = $p instanceof Player ? strtolower($p->getName()) : strtolower($p);

    	if($this->db->query("SELECT * FROM `q` WHERE `username` = '$username'")->fetchArray(SQLITE3_ASSOC)){

    		$id = $this->getQ($p, 'id');
    		$count = $this->getQ($p, 'count');

            $this->shop->createCustomFloating($p, $x, $y + 0.80, $z, '§fКвесты');

	        if($id == 17) $text = 'Принеси '. $count .' древесины'; else $text = 'Принеси '. $count .' булыжника'; 
            $this->shop->createCustomFloating($p, $x, $y + 0.40, $z, '§f'. $text);

	        $text = '§eУДАРЬ ЧТОБЫ ВЫПОЛНИТЬ КВЕСТ§r';
            $this->shop->createCustomFloating($p, $x, $y, $z, $text);

	    } else {

            $this->shop->removePreCustomFloating($p, $x, $y + 0.80, $z);

            $this->shop->createCustomFloating($p, $x, $y + 0.40, $z, '§fКвесты');
            
            $text = '§eУДАРЬ ЧТОБЫ ПОЛУЧИТЬ КВЕСТ§r';
            $this->shop->createCustomFloating($p, $x, $y, $z, $text);
	    }
    }

    function accessQ($p){

		$username = $p instanceof Player ? strtolower($p->getName()) : strtolower($p);

    	if($this->db->query("SELECT * FROM `q` WHERE `username` = '$username'")->fetchArray(SQLITE3_ASSOC)){

    		$id = $this->getQ($p, 'id');
    		$count = $this->getQ($p, 'count');

            if($p->getInventory()->contains(Item::get($id, 0, $count))){
                
                if($id == 17) $money = rand(25, 235); else $money = rand(50, 120);

                $this->economy->myMoney($p, $money);
                $this->db->query("DELETE FROM `q` WHERE `username` = '". $username ."'");
                $p->sendMessage("§eВы успешно выполнили квест и получили§a ". $money ." монет§e!");

                $this->flytext($p);

                $p->getInventory()->removeItem(Item::get($id, 0, $count));

            } else {

            	if($id == 17) $text = 'У тебя в инвентаре должно быть '. $count .' древесины'; else $text = 'У тебя в инвентаре должно быть '. $count .' булыжника';

                $p->sendMessage("§c". $text);   
            }
    	}
    }
        
    function getQuest($p){

    	$rand = rand(1, 2);
        $username = strtolower($p->getName());

    	if($rand == 1){

            $q = [17, 32];

    	} elseif($rand == 2){

            $q = [4, 14];
    	}

    	if($rand == 1) $message = 'Принеси '. $q[1] .' древесины дуба'; else $message = 'Принеси '. $q[1] .' булыжников';

    	$p->sendMessage($message);

    	if(!$this->db->query("SELECT * FROM `q` WHERE `username` = '$username'")->fetchArray(SQLITE3_ASSOC)){

            $this->db->query("INSERT INTO `q` (`username`, `id`, `count`) VALUES ('$username', '$q[0]', '$q[1]');");
        }

        $this->flytext($p);
    }
}
?>