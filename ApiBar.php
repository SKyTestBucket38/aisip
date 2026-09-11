<?php

namespace Efremov;

use pocketmine\Player;

use pocketmine\entity\{Entity};

use pocketmine\network\mcpe\protocol\{AddEntityPacket, BossEventPacket, RemoveEntityPacket, SetEntityDataPacket, UpdateAttributesPacket};

use pocketmine\event\Listener;

class ApiBar implements Listener {

	public $bossbar = [];
	
	function __construct(\Efremov\bar $plugin){

        $this->pg = $plugin;
    }

	function getIdBossBar($player){

		$username = strtolower($player->getName());

		if(isset($this->bossbar[$username])){

		    $id = $this->bossbar[$username]; 

		} else {

			$id = Entity::$entityCount++; $this->bossbar[$username] = $id;
		}

		return $id;
	}

	function getText($player){

        $items = array('   §e§lТекущий сервер§f Survival', '   §a§lТЕЛЕПОРТАЦИЯ В СЛУЧАЙНОЕ МЕСТО §7- §e/rtp', '    §a§lРАБОТЫ §7- §e/job', '     §a§lКУПИТЬ ДОНАТ §7- §eshop.breadixpe.ru', '     §b§lГРУППА ВК §7- §evk.com/breadixpe');
        return $items[array_rand($items)];
	}

	function sendBossBar($player){

		$id = $this->getIdBossBar($player);
		$text = $this->getText($player);

		$packet = new AddEntityPacket();
		$packet->type = 37;
		$packet->eid = $id;

		$packet->x = (float) $player->getFloorX();
	    $packet->y = (float) $player->getFloorY() + 5;
	    $packet->z = (float) $player->getFloorZ();

		$packet->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $text], Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 0 ^ 1 << Entity::DATA_FLAG_SILENT ^ 1 << Entity::DATA_FLAG_INVISIBLE ^ 1 << Entity::DATA_FLAG_NO_AI]];

		$player->dataPacket($packet);

		$packet = new BossEventPacket();
		$packet->eid = $id;

		$player->dataPacket($packet);
	}

	function setTitle($player){

		$id = $this->getIdBossBar($player);
		$text = $this->getText($player);

		$packet = new SetEntityDataPacket();
		$packet->eid = $id;

		$packet->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $text]];

		$player->dataPacket($packet);
	}

	function setProgress($player, $time = 100){

		$id = $this->getIdBossBar($player);

		$packet = new UpdateAttributesPacket();
		$packet->entityId = $id;
		$packet->entries[] = new BossBarValues(1, 600, max(1, min([$time, 100])) / 100 * 600, 'minecraft:health');
		$player->dataPacket($packet);
	}

	function removeBossBar($player){

		$username = strtolower($player->getName()); $id = $this->getIdBossBar($player);

		$pk = new RemoveEntityPacket();
		$pk->eid = $id;

		$player->dataPacket($packet);

		unset($this->bossbar[$username]);
	}
}
?>