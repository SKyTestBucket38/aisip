<?php

namespace Shop;

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

class Shop extends PluginBase implements Listener{

	function onEnable(){

		$this->getServer()->getPluginManager()->registerEvents($this, $this);

        //$this->api = $this->getServer()->getPluginManager()->getPlugin("Octopus");

        if(!is_dir($this->getDataFolder())) @mkdir($this->getDataFolder());

        $this->saveDefaultConfig();

        $this->config = (new Config($this->getDataFolder()."config.yml", Config::YAML))->getAll();
        $this->number = 0; $this->maxnumber = count($this->config['Товары']) - 1;
        $this->count = 1; $this->maxcount = 64; $this->price = $this->config['Товары'][0]['Цена'];
    }

    public $shop;

    /* FLY TEXT */

    public $idfloating; // $entityfloating = 500000

    function removePreCustomFloating($player, $x, $y, $z){

        if(isset($this->idfloating[$x . $y . $z])){

            $id = $this->idfloating[$x . $y . $z]; $this->removeCustomFloating($player, $id);
        }
    }
    
    function createCustomFloating($player, $x, $y, $z, $text){

        if(isset($this->idfloating[$x . $y . $z])) $id = $this->idfloating[$x . $y . $z]; else $id = Entity::$entityCount++; //$id = $this->entityfloating;

        $pk = new \pocketmine\network\mcpe\protocol\AddPlayerPacket();
        $pk->eid = $id;
        $pk->uuid = \pocketmine\utils\UUID::fromRandom();
        $pk->username = "null";
        $pk->x = $x; $pk->y = $y; $pk->z = $z;
        $pk->item = Item::get(Item::AIR);

        $flags = (
            (1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG) |
            (1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG) |
            (1 << Entity::DATA_FLAG_IMMOBILE)
        );

        $pk->metadata = [
            Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
            Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $text],
            Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0],
        ];

        $player->dataPacket($pk);

        $this->idfloating[$x . $y . $z] = $id; // $this->entityfloating++;
    }

    function removeCustomFloating($player, $id){

        $pk = new \pocketmine\network\mcpe\protocol\RemoveEntityPacket();
        $pk->eid = $id;

        $player->dataPacket($pk);
    }

    /* FLY TEXT */

    function onJoin(PlayerJoinEvent $event){

    	$player = $event->getPlayer();

    	$this->getEntity($player);

    	$this->createCustomFloating($player, 9579 + 0.5, 68, -9656 + 0.5, '§e● §fМагазин §e●§r');
        $this->createCustomFloating($player, 9579 + 0.5, 67, -9656 + 0.5, '§7Для открытия магазина, нажмите на сундук!§r');
    }

    function onMove(PlayerMoveEvent $event){

        $player = $event->getPlayer(); $username = strtolower($player->getName());

        if($player->distance(new Vector3(9579, 68, -9656)) > 5){

            if(isset($this->shop[$username])){

	            $this->removePreCustomFloating($player, 9579 + 0.5, 67 + 0.5, -9656 - 1);
	            $this->removePreCustomFloating($player, 9579 + 0.5, 67, -9656 - 1);

	            $this->removePreCustomFloating($player, 9579 + 0.5, 67 + 0.5, -9655 + 1);
	            $this->removePreCustomFloating($player, 9579 + 0.5, 67, -9655 + 1);

	            //$this->removePreCustomFloating($player, 143 + 0.5, 74, 506 + 0.5);

	            $this->removePreCustomFloating($player, 9579 + 0.5, 68 + 0.5, -9656 + 0.5);
	            $this->removePreCustomFloating($player, 9579 + 0.5, 68, -9656 + 0.5);
                
	            $this->createCustomFloating($player, 9579 + 0.5, 68, -9656 + 0.5, '§e● §fМагазин §e●§r');
                $this->createCustomFloating($player, 9579 + 0.5, 67, -9656 + 0.5, '§7Для открытия магазина, нажмите на сундук!§r');

                foreach($this->getServer()->getOnlinePlayers() as $players){

                    $player->showPlayer($players); // $players->hidePlayer($player);
                }

	            $pkk = new \pocketmine\network\mcpe\protocol\RemoveEntityPacket; 
		        $pkk->eid = $this->eid[$username]; 
		        $player->dataPacket($pkk);

	            $this->getEntity($player); $this->onAnimationChest($player, 9579, 67, -9656);

	            unset($this->shop[$username]);
	        }
        }
    }

    function createNPC($sender, $name, $xyz, $size = 1, $type = 0){

        $npc = new CompoundTag('', [ 

            'Pos' => new ListTag('Pos', [ 
                new DoubleTag('', $xyz[0]),
                new DoubleTag('', $xyz[1] + 0.1),
                new DoubleTag('', $xyz[2]) 
            ]),

            'Motion' => new ListTag('Motion', [ 
                new DoubleTag('', 0),
                new DoubleTag('', 0),
                new DoubleTag('', 0) 
            ]),

            'Rotation' => new ListTag('Rotation', [ 
                new FloatTag('', $sender->getYaw()),
                new FloatTag('', $sender->getPitch()) 
            ]) 
        ]);

        $mob = Entity::createEntity(LavaSlime::NETWORK_ID, $sender->getLevel(), $npc);

        $mob->setMaxHealth(20); $mob->setHealth(20);
	
	    $mob->setNameTag($name); $mob->spawnTo($sender);
		$mob->setNameTagVisible(false); $mob->setNameTagAlwaysVisible(false);	
		$mob->setDataProperty(Entity::DATA_SCALE, Entity::DATA_TYPE_FLOAT, $size);
    }

    function onQuit(PlayerQuitEvent $event){

    	$player = $event->getPlayer(); $username = strtolower($player->getName());

    	if(isset($this->shop[$username])) unset($this->shop[$username]);
    	if(isset($this->eid[$username])) unset($this->eid[$username]);
    }

    function updateNBT($player){

    	$username = strtolower($player->getName());

		/*

        $this->createNPC($player, '+item', [9579 + 0.5, 67 + 0.90, -9656 - 1], 0.2);
		$this->createNPC($player, '-item', [9579 + 0.5, 67 + 0.40, -9656 - 1], 0.2);

		$this->createNPC($player, '+count', [9579 + 0.5, 67 + 0.90, -9655 + 1], 0.2);
		$this->createNPC($player, '-count', [9579 + 0.5, 67 + 0.40, -9655 + 1], 0.2);

		$this->createNPC($player, 'potion_1', [9592 + 0.5, 68 + 0.15, -9671], 0.2);
		$this->createNPC($player, 'potion_2', [9592 + 0.5, 68 + 0.15, -9675 + 1], 0.2);

        */

        $this->createNPC($player, 'rubak_1', [9613 + 0.5, 68 + 0.15, -9653 + 0.5], 0.2);
    }

    function onDamage(EntityDamageEvent $e){
		
        if($e instanceof EntityDamageByEntityEvent){
		   
            $p = $e->getEntity(); $d = $e->getDamager(); $username = strtolower($d->getName());
           
		    if($p instanceof LavaSlime && $d instanceof Player && $p->getNameTag() == "+item"){
			   
                $e->setCancelled(); $this->onTapTrue('Кнопка следующее', $d);

            } elseif($p instanceof LavaSlime && $d instanceof Player && $p->getNameTag() == "-item"){
			   
                $e->setCancelled(); $this->onTapTrue('Кнопка предыдущее', $d);

            } elseif($p instanceof LavaSlime && $d instanceof Player && $p->getNameTag() == "+count"){
			   
                $e->setCancelled(); $this->onTapTrue('Кнопка+', $d);

            } elseif($p instanceof LavaSlime && $d instanceof Player && $p->getNameTag() == "-count"){
			   
                $e->setCancelled(); $this->onTapTrue('Кнопка-', $d);
            }
        }
    }

    function getEntity($player){

    	$username = strtolower($player->getName());
		
		foreach($this->getServer()->getDefaultLevel()->getEntities() as $entity){

		    if($entity instanceof LavaSlime && !$entity instanceof Player) if($entity->getNameTag() === '+item' || $entity->getNameTag() === '-item' || $entity->getNameTag() === '+count' || $entity->getNameTag() === '-count'){

				$entity->despawnFrom($player);
			}
		}
	}

    function onTap(PlayerInteractEvent $e){

    	$player = $e->getPlayer(); $username = strtolower($player->getName()); $block = $e->getBlock();

    	$x = $block->getX(); $y = $block->getY(); $z = $block->getZ();

    	if($x ==  9579 && $y == 67 && $z == -9656){

    		$e->setCancelled();

    		if(isset($this->shop[$username])){

    			$this->onTapTrue('Кнопка купить', $player);

    		} else {

    		    if(!isset($this->shop[$username])){

    		    	$this->onAnimationChest($player, 9579, 67, -9656, 2); $this->eid[$username] = 0;
                
	                $this->updateItem($player); $this->shop[$username] = $player; // $this->updateNBT($player);

                    foreach($this->getServer()->getOnlinePlayers() as $players){

                        $player->hidePlayer($players); //$players->hidePlayer($player);
                    }

	                $this->createCustomFloating($player, 9579 + 0.5, 67 + 0.5, -9656 - 1, '§eСледующий товар§r');
	                $this->createCustomFloating($player, 9579 + 0.5, 67, -9656 - 1, '§eПредыдущий товар§r');

	                $this->createCustomFloating($player, 9579 + 0.5, 67, -9656 + 0.5, '§7Нажмите, чтобы купить!§r');

			        $this->createCustomFloating($player, 9579 + 0.5, 67 + 0.5, -9655 + 1, '§aКоличество +§r');
			        $this->createCustomFloating($player, 9579 + 0.5, 67, -9655 + 1, '§cКоличество -§r');

                    foreach($player->getLevel()->getEntities() as $entity){

                        if($entity instanceof LavaSlime) if(in_array($entity->getNameTag(), ['+item', '-item', '+count', '-count'])){
                            
                            $entity->spawnTo($player);
                            $entity->addEffect(Effect::getEffect(14)->setDuration(999999)->setAmplifier(0)->setVisible(false));
                            $entity->setDataProperty(Entity::DATA_SCALE, Entity::DATA_TYPE_FLOAT, 0.2);
                        }
                    }
	            }	
    		}
    	}
    }

    function onAnimationChest($player, $x, $y, $z, $case = 0){

	    $pk = new BlockEventPacket();
		$pk->x = $x;
		$pk->y = $y;
		$pk->z = $z;
		$pk->case1 = 1;
		$pk->case2 = $case; // 0 - сундук закрыт, либо 2 - cундук открыт.
		$player->dataPacket($pk);
	}

    public $eid;

    function updateItem($player, $count = 1){

    	$username = strtolower($player->getName());

    	$id = $this->config['Товары'][$this->number]['ID']; $damage = $this->config['Товары'][$this->number]['Damage'];
    	$name = $this->config['Товары'][$this->number]['Название'];

    	$this->createCustomFloating($player, 9579 + 0.5, 68 + 0.5, -9656 + 0.5, '§e● §f'. $name .' §e●§r');
        $this->createCustomFloating($player, 9579 + 0.5, 68, -9656 + 0.5, '§fКоличество: §a'. $this->count .' §7- §fСтоимость: §b'. $this->price .'§r');

		$pkk = new \pocketmine\network\mcpe\protocol\RemoveEntityPacket; 
		$pkk->eid = $this->eid[$username]; 
		$player->dataPacket($pkk);

		$item = Item::get($id, $damage, $count);
        
        $id = Entity::$entityCount++;

		$pk = new \pocketmine\network\mcpe\protocol\AddItemEntityPacket;
		$pk->eid = $id; 
		$pk->item = $item; 
		$pk->x = 9579 + 0.5; 
		$pk->y = 68; 
		$pk->z = -9656 + 0.5; 
		$pk->speedX = 0; 
		$pk->speedY = 0; 
		$pk->speedZ = 0; 
		$player->dataPacket($pk);

		$this->eid[$username] = $id; 
    }

    function onButtonCount($player, $check){

    	if($check){

    		if($this->maxcount == $this->count){

    			$this->count = 1;
				$this->price = $this->config['Товары'][$this->number]['Цена'] * $this->count;

    		} else {

    			$this->count++;
				$this->price = $this->config['Товары'][$this->number]['Цена'] * $this->count;
    		}

    		$this->updateItem($player, $this->count);

    		return true;

    	} else {

    		if($this->count == 1){

    			$this->count = 64;
				$this->price = $this->config['Товары'][$this->number]['Цена'] * $this->count;

    		} else {

    			$this->count--;
				$this->price = $this->config['Товары'][$this->number]['Цена'] * $this->count;
    		}

    		$this->updateItem($player, $this->count);

    		return true;
    	}

    	$player->getLevel()->addSound((new \pocketmine\level\sound\ButtonClickSound($player)), [$player]);
    }

    function onButton($player, $check){

    	if($check){

    		if($this->number == $this->maxnumber){

    			$this->number = 0;
				$this->count = 1;
				$this->price = $this->config['Товары'][$this->number]['Цена'] * $this->count;

    		} else {

    			$this->number++;
				$this->count = 1;
				$this->price = $this->config['Товары'][$this->number]['Цена'] * $this->count;
    		}

    		$this->updateItem($player);

    		return true;

    	} else {

    		if($this->number == 0){

    			$this->number = $this->maxnumber;
				$this->count = 1;
				$this->price = $this->config['Товары'][$this->number]['Цена'] * $this->count;

    		} else {

    			$this->number--;
				$this->count = 1;
				$this->price = $this->config['Товары'][$this->number]['Цена'] * $this->count;
    		}

    		$this->updateItem($player);

    		return true;
    	}

    	$player->getLevel()->addSound((new \pocketmine\level\sound\ButtonClickSound($player)), [$player]);
    }

    function onBuy($player){

		$price = $this->price;
		$money = EconomyAPI::getInstance()->myMoney($player);

		if($price <= $money){

			EconomyAPI::getInstance()->reduceMoney($player, $price);

			$player->getInventory()->addItem(Item::get($this->config['Товары'][$this->number]['ID'],$this->config['Товары'][$this->number]['Damage'],$this->count));
			$player->addActionBarMessage($this->config['Сообщения']['Успешно']);

			$player->getLevel()->addSound((new \pocketmine\level\sound\ExpPickupSound($player)), [$player]);

		} else {

			$player->addActionBarMessage($this->config['Сообщения']['Ошибка']);

			$player->getLevel()->addSound((new \pocketmine\level\sound\DoorBumpSound($player)), [$player]);
		}
    }

    function onTapTrue($key, $player){

    	switch ($key){

    		case 'Кнопка+':
    			$this->onButtonCount($player, true);
    		break;

    		case 'Кнопка-':
    			$this->onButtonCount($player, false);
    		break;

    		case 'Кнопка следующее':
    			$this->onButton($player, true);
    		break;

    		case 'Кнопка предыдущее':
    			$this->onButton($player, false);
    		break;

    		case 'Кнопка купить':
    			$this->onBuy($player);
    		break;
    	}
    }
}
?>