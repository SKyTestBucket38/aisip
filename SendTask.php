<?php

namespace Efremov;

use pocketmine\Player;

use pocketmine\item\Item;
use pocketmine\block\Block;

use pocketmine\entity\{Entity, Human};

use pocketmine\math\Vector3;
use pocketmine\plugin\Plugin;

use pocketmine\scheduler\PluginTask;

use pocketmine\nbt\tag\{CompoundTag, DoubleTag, FloatTag, ListTag, LongTag, ShortTag, StringTag};

class SendTask extends PluginTask {

	public $time = 0;

	function __construct(Plugin $owner){

		$this->space = str_repeat(' ', 85);

		parent::__construct($owner);
	}

	function onRun($currentTick){

		foreach($this->getOwner()->getServer()->getOnlinePlayers() as $players){

			if($this->time > 10){

				$this->getOwner()->bossbar->setTitle($players);

				$this->time = 0;
			}

			$ping = $players->getPing();
			$money = $this->getOwner()->economy->myMoney($players);

			$players->sendTip($this->space .'   §l§eSurvival§r §7('. $ping .'§7ms)§r'. PHP_EOL . PHP_EOL . $this->space .'§fБаланс: §a'. $money .'§r монет'. PHP_EOL . PHP_EOL . $this->space .'§eshop.breadixpe.ru' . PHP_EOL . $this->space .'§bvk.com/breadixpe'. PHP_EOL . PHP_EOL);
        }

        $this->time++;
	}

	function cancel(){

		$this->getHandler()->cancel();
	}
}
?>