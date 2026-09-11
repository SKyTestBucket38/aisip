<?php

namespace buykey;

use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;

use pocketmine\event\player\{PlayerJoinEvent, PlayerInteractEvent};

use pocketmine\command\{Command, CommandSender};

class buykey extends PluginBase {

    function onEnable(){
        
        /* TODO */

        $this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");

        $this->db = new \SQLite3($this->getDataFolder() .'case.db');
        $this->db->query("CREATE TABLE IF NOT EXISTS `case` (`username` TEXT NOT NULL, `key` INTEGER NOT NULL);");
    }

    function onLogin(PlayerJoinEvent $event){

        $player = $event->getPlayer();
        $username = strtolower($player->getName());

        if(!$this->db->query("SELECT * FROM `case` WHERE `username` = '$username'")->fetchArray(SQLITE3_ASSOC)){

            $this->db->query("INSERT INTO `case` (`username`, `key`) VALUES ('$username', 0);");
        }
    }

    function getkey($player, $entry = 'key'){

        $username = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);

        $result = $this->db->query("SELECT `$entry` FROM `case` WHERE `username` = '$username'")->fetchArray(SQLITE3_ASSOC);

        if(isset($result[$entry])) return $result[$entry]; else return 0;
    }

    public $caseclick = array();

    function getRoll($player){

    	$rand = rand(1, 7);

    	if($rand == 1) $item = Item::get(297, 0, rand(1, 64));
    	if($rand == 2) $item = Item::get(288, 0, rand(1, 32));
    	if($rand == 3) $item = Item::get(218, 0, 1);
    	if($rand == 4) $item = Item::get(444, 0, 1);
    	if($rand == 5) $item = Item::get(373, 0, 1); // увеличение роста
    	if($rand == 6) $item = Item::get(373, 0, 1); // уменьшение роста
    	if($rand == 7) $item = Item::get(450, 0, 1); // totem

    	if($rand == 5) $item->setCustomName('§eЗелье увеличения роста§r§r'); else if($rand == 6) $item->setCustomName('§eЗелье уменьшения роста§r§r');

    	$player->getInventory()->addItem($item);
        $player->sendMessage('§eВам выпал предмет из колодца предметов, посмотрите в инвентарь!');
        $player->sendPopup('§aДобавлен новый предмет в инвентарь!');

		$username = $player instanceof Player ? strtolower($player->getName()) : strtolower($player);
        $this->db->query("UPDATE `case` SET `key` = `key` - 1 WHERE `username` = '$username'");
    }

    function onCommand(CommandSender $sender, Command $command, $label, array $args){

		if($command->getName() == "buykey"){

			$username = $sender instanceof Player ? strtolower($sender->getName()) : strtolower($sender);

            if($this->economy->myMoney($sender) >= 5000){

            	/* UPDATE SQLITE */

				if(!$this->db->query("SELECT * FROM `case` WHERE `username` = '$username'")->fetchArray(SQLITE3_ASSOC)){

		            $this->db->query("INSERT INTO `case` (`username`, `key`) VALUES ('$username', 0);");
		        }

		        /* UPDATE SQLITE */

                $this->db->query("UPDATE `case` SET `key` = `key` + 1 WHERE `username` = '$username'");
            	$this->economy->reduceMoney($sender, 5000);
                
            	$m = $this->economy->myMoney($sender);
                $sender->sendMessage('§eВы успешно купили 1 ключ, на вашем счету осталось§a '. $m .' §eмонет.');

            } else {

            	$m = 5000 - $this->economy->myMoney($sender);
                $sender->sendMessage('§cУ вас не хватает на счету§a '. $m .' монет§c, чтобы купить ключ(ей) (колодец предметов).');
            }
		}

		if($command->getName() == "addkey" && $sender->isOP()){

			if(!isset($args[0])) return false;

			$username = strtolower($args[0]);

			/* UPDATE SQLITE */

			if(!$this->db->query("SELECT * FROM `case` WHERE `username` = '$username'")->fetchArray(SQLITE3_ASSOC)){

	            $this->db->query("INSERT INTO `case` (`username`, `key`) VALUES ('$username', 0);");
	        }

	        /* UPDATE SQLITE */

            $this->db->query("UPDATE `case` SET `key` = `key` + 1 WHERE `username` = '$username'");
            $sender->sendMessage('§eВы выдали ключ игроку '. $username .'.');
		}

		if($command->getName() == "key"){

            $key = $this->getkey($sender);
            $sender->sendMessage('§eУ вас доступно:§b '. $key .' ключ(ей)§e.');
		}
    }
}
?>