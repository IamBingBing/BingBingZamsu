<?php
namespace bingbing;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use onebone\economyapi\EconomyAPI;
use pocketmine\Player;
use pocketmine\math\Vector3;

use pocketmine\event\Listener;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\enchantment\EnchantmentInstance;

use pocketmine\tile\Sign;

use pocketmine\scheduler\Task;

class zamsu extends PluginBase implements Listener{
    
    public function onEnable(){
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        @mkdir($this->getDataFolder());
        $this->option = new Config($this->getDataFolder()."option.yml",Config::YAML,["item" => "370:0:1", "high" => 6 ,"low" =>3 , "time" => 6 , "block" => 42 ]);
        $this->op = $this->option->getAll();
        $this->shopdb["shop"] = new Config($this->getDataFolder()."shop.yml",Config::YAML,[ "number" => 0]);
        $this->shop["shop"] = $this->shopdb["shop"]->getAll();
        $this->getScheduler()->scheduleRepeatingTask( new task1($this), $this->op["time"]*20 );
        
    }
    public function givemoney(Player $name){
        $money = mt_rand($this->op["low"] , $this->op["high"]);
        EconomyAPI::getInstance()->addMoney($name, $money);
        $name->getPlayer()->sendMessage("§b[§f잠수§b]§f 돈  ".$money."을  받았습니다");
        
    }
    public function giveitem(Player $name){
        $item = explode(":",$this->op["item"]  );
        
        if ($this->rand(10)){
        $name->getPlayer()->getInventory()->addItem(\pocketmine\item\Item::get($item[0] , $item[1] , $item[2]));
        $name->getPlayer()->sendMessage("§b[§f잠수§b]§f 아이템코드  ".$item[0].":".$item[1]." 을 ".$item[2]."개 받았습니다.");
        }
    }
    public function rand( int $int){
        $m = mt_rand(1, 100);
        if ($m < $int) {
            return true;
        }
        else {
            return false;
        }
    }
    public function save(){
        $this->shopdb["shop"]->setAll($this->shop["shop"]);
        $this->shopdb["shop"]->save();
    }
    
    public function place(player $name){
        
        if ($name->getLevel()->getBlock(new Vector3($name->getFloorX() , $name->getFloorY()-1 , $name->getFloorZ() ))->getId() == $this->op["block"]){
                return "true";
            }
            else {
                return "false";
            }
        }
        public function touch(PlayerInteractEvent$event){
            $x = $event->getBlock()->getFloorX();
            $y = $event->getBlock()->getFloorY();
            $z = $event->getBlock()->getFloorZ();
            $level = $event->getBlock()->getLevel();
            $tem = explode(":", $this->op["item"]);
            if (isset($this->shop["shop"][$x.":".$y.":".$z.":".$level->getName()] )){
                $sign = $level->getTile(new Vector3($x ,$y,$z));
                if ($sign instanceof Sign){
                    if ($event->getPlayer()->getInventory()->contains(Item::get($tem[0] , $tem[1], $this->shop["shop"][$x.":".$y.":".$z.":".$level->getFolderName()][2] ))){
                        if ($this->shop["shop"][$x.":".$y.":".$z.":".$level->getFolderName()][0] == "아이템"){
                            $id = $this->shop["shop"][$x.":".$y.":".$z.":".$level->getFolderName()][1];
                                            $item = new Item($id[0],$id[1],$sign->getLine(1));
                                            $item->setCount($id[2]);
                                            if (isset ($id[3]) && isset($id[4])){
                                                $item->addEnchantment(new EnchantmentInstance(Enchantment::init($id[3]), $id[4]));
                                            }
                                            $event->getPlayer()->getInventory()->addItem($item);
                                            $event->getPlayer()->getInventory()->removeItem(Item::get($this->op["item"][0] , $this->op["item"][1], $this->shop["shop"][$x.":".$y.":".$z.":".$level->getFolderName()][2]) );
                                            $event->getPlayer()->sendMessage("§f[ §b점수상점  §f] ".$sign->getLine(1)."을/를 구매하였습니다");
                                        
                                        
                                    }
                                    else {
                                        if ($event->getPlayer()->getInventory()->contains(Item::get($tem[0] , $tem[1], $this->shop["shop"][$x.":".$y.":".$z.":".$level->getFolderName()][2] ))){
                                            EconomyAPI::getInstance()->addMoney($event->getPlayer(),explode(":",$sign->getLine(2))[1]);
                                            $event->getPlayer()->getInventory()->removeItem(Item::get($this->op["money"][0] , $this->op["money"][1], $this->shop["shop"][$x.":".$y.":".$z.":".$level->getFolderName()][2]));
                                            $event->getPlayer()->sendMessage("§f[ §b점수상점  §f] ".$sign->getLine(2)."을/를 구매하였습니다");
                                        }
                                        else {
                                            $event->getPlayer()->sendMessage("§f[ §b점수상점  §f] 코인이 부족합니다");
                                        }
                                    }
                                }
                                else {
                                    $event->getPlayer()->sendMessage("§f[ §b점수상점  §f] 코인이 부족합니다");
                                }
                }
            }
             
        }
        public function break (BlockBreakEvent $event){
            $block = $event->getBlock();
            $x = $event->getBlock()->getFloorX();
            $y = $event->getBlock()->getFloorY();
            $z = $event->getBlock()->getFloorZ();
            $level = $event->getBlock()->getLevel()->getFolderName();
            
            if ($event->getPlayer()->isOp() && isset($this->shop["shop"][$x.":".$y.":".$z.":".$level])){
                if ($block->getId() == "63"  or $block->getId() == "68" or $block->getId() == "323"&& isset($this->shop["shop"][$x.":".$y.":".$z.":".$level])){
               
                unset($this->shop["shop"][$x.":".$y.":".$z.":".$level]);
                                    $event->getPlayer()->sendMessage("삭제완료");
                                    $this->save();
                                }
            }
            else if(isset($this->shop["shop"][$x.":".$y.":".$z.":".$level])){
                $event->setCancelled(true);
            }
            
        }
        public function change(SignChangeEvent$event){
            $l0 = $event->getLine(0);
            $l1 = $event->getLine(1);
            $l2 = $event->getLine(2);
            $l3 = $event->getLine(3);
            $x = $event->getBlock()->getFloorX();
            $y = $event->getBlock()->getFloorY();
            $z = $event->getBlock()->getFloorZ();
            $level = $event->getBlock()->getLevel()->getFolderName();
            
            
            if ($l0 == "잠수상점" && $event->getPlayer()->isOp()){
                switch ($l1){
                    case "돈":
                        if (is_numeric($l2) && is_numeric($l3)){
                            $event->setLine(0, "§f[ §b점수상점  §f]");
                            $event->setLine(1, "§e금액: §f".$l2."원");
                            $event->setLine(2, "§b 잠수코인: §f".$l3."개");
                            $event->setLine(3, "");
                            $this->shop["shop"][$x.":".$y.":".$z.":".$level] = ["돈" , $l2 , $l3];
                            $this->shop["shop"]["number"] = $this->shop["shop"]["number"]+1;
                            $this->save();
                            break;
                        }
                        else{
                            
                        }
                    default:
                        $cm = explode(":", $l1);
                        if ($cm[0] == "아이템"){
                        
                            $id = explode(":", $l2);
                                $event->setLine(0, "§f[ §b점수상점  §f]");
                                $event->setLine(1, $cm[1]);
                                $event->setLine(2, "§b 잠수코인: §f".$l3."개");
                                $event->setLine(3, "");
                                $this->shop["shop"][$x.":".$y.":".$z.":".$level] = ["아이템" , $id ,$l3];
                                $this->save();
                                $this->shop["shop"]["number"] = $this->shop["shop"]["number"]+1;
                                break;
                            }
                            
                        
                }
            }
        }
        public function onDisable(){
            $this->save();
        }
    
}
class task1 extends Task{
    public $plugin;
    public function __construct($plugin){
        $this->plugin = $plugin;
    }
    public function getOwner(){
        return $this->plugin;
        
    }
    public function onRun(int $currentTick){
        foreach($this->getOwner()->getServer()->getOnlinePlayers() as $player){
            if ($this->getOwner()->place($player) == "true"){ 
                $this->getOwner()->giveitem($player);
                $this->getOwner()->givemoney($player);
            }
        }
    }
}