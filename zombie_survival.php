<?php
// outbreak_survivor.php
// THE OUTBREAK SURVIVOR — a terminal survival RPG written in PHP.
// This single-file project demonstrates OOP design, classes, methods, objects,
// encapsulation, CLI I/O, random events, and state management.

// ============================================================================
// BASIC HELPER FUNCTIONS (UI + INPUT)
// ============================================================================

// Colorize terminal text using ANSI escape codes.
// Helps make the UI readable and visually appealing.
function color($text, $code) {
    return "\e[" . $code . "m" . $text . "\e[0m";
}

// Clears the terminal screen for a clean UI each frame.
function clearScreen() {
    echo "\e[2J\e[H";
}

// Simple separator line for interface readability.
function line() {
    echo str_repeat("-", 70) . PHP_EOL;
}

// Prompts the user and returns their typed response.
function prompt($text = "> ") {
    echo $text;
    return trim(fgets(STDIN));
}

// Pauses execution until user presses enter.
// Great for pacing the game so text isn't spammed instantly.
function waitEnter($msg = "Press ENTER to continue...") {
    echo $msg;
    fgets(STDIN);
}
// Render a health bar
function renderHealthBar($current, $max) {
    $barLength = 20;
    $filledLength = (int)(($current / $max) * $barLength);
    $emptyLength = $barLength - $filledLength;
    $bar = "[" . str_repeat("█", $filledLength) . str_repeat("-", $emptyLength) . "]";
    return "$bar $current/$max";
}

// Render ammo icons
function renderAmmo($ammo) {
    return str_repeat("🔫", $ammo);
}

// ============================================================================
// PLAYER CLASS — Stores all stats, items, and player-specific logic
// ============================================================================
class Player {
    // Stats are private to enforce encapsulation.
    private int $health;
    private int $hunger;
    private int $ammo;
    private array $inventory;
    private string $weapon; // Current equipped weapon

    public function getMaxHealth(): int {
    return 100;
    }

    public function __construct() {
        // Initial starting stats
        $this->health = 100;
        $this->hunger = 0;
        $this->ammo = 6;

        // Basic starting gear
        $this->inventory = ["Knife"];
        $this->weapon = "Knife";
    }

    // ---------------------------
    // Getters — Read-only access
    // ---------------------------
    public function getHealth(): int { return $this->health; }
    public function getHunger(): int { return $this->hunger; }
    public function getAmmo(): int { return $this->ammo; }
    public function getInventory(): array { return $this->inventory; }
    public function getWeapon(): string { return $this->weapon; }

    // ---------------------------
    // HEALTH MANAGEMENT
    // ---------------------------
    public function takeDamage(int $amount): void {
        // Health never drops below zero
        $this->health -= $amount;
        if ($this->health < 0) $this->health = 0;
    }

    public function heal(int $amount): void {
        $this->health += $amount;
        // Health capped at 100
        if ($this->health > 100) $this->health = 100;
    }

    // ---------------------------
    // HUNGER MANAGEMENT
    // ---------------------------
    public function increaseHunger(int $amount): void {
        $this->hunger += $amount;
        if ($this->hunger > 100) $this->hunger = 100;
    }

    public function decreaseHunger(int $amount): void {
        $this->hunger -= $amount;
        if ($this->hunger < 0) $this->hunger = 0;
    }

    // ---------------------------
    // AMMO MANAGEMENT
    // ---------------------------
    public function useAmmo(int $count = 1): bool {
        // Only shoot if enough ammo
        if ($this->ammo >= $count) {
            $this->ammo -= $count;
            return true;
        }
        return false;
    }

    public function addAmmo(int $count): void {
        $this->ammo += $count;
    }

    // ---------------------------
    // INVENTORY MANAGEMENT
    // ---------------------------
    public function addItem(string $item): void {
        $this->inventory[] = $item;
    }

    // Removes one matching item from inventory
    public function removeItem(string $item): bool {
        $idx = array_search($item, $this->inventory);
        if ($idx !== false) {
            array_splice($this->inventory, $idx, 1);
            return true;
        }
        return false;
    }

    public function hasItem(string $item): bool {
        return in_array($item, $this->inventory);
    }

    // ---------------------------
    // EQUIP WEAPON
    // ---------------------------
    public function setWeapon(string $w): void {
        // Can only equip items the player owns (except Knife)
        if ($w === "Knife" || $this->hasItem($w)) {
            $this->weapon = $w;
        }
    }

    // Check if still alive
    public function isAlive(): bool {
        return $this->health > 0;
    }

    // ---------------------------
    // STATUS UI BOX
    // ---------------------------
    public function showStats(): void {
        // Draws a fancy box with stats inside
        echo color("┌" . str_repeat("─", 66) . "┐", "90") . PHP_EOL;

        $status = sprintf(
            "│ ❤️ Health: %3d   🍗 Hunger: %3d   🔫 Ammo: %3d   Weapon: %-12s │",
            $this->health, $this->hunger, $this->ammo, $this->weapon
        );

        echo color($status, "97") . PHP_EOL;

        // Inventory displayed as a single line
        $inv = "│ 🎒 Inventory: " . str_pad(implode(", ", $this->inventory), 52) . "│";
        echo color($inv, "97") . PHP_EOL;

        echo color("└" . str_repeat("─", 66) . "┘", "90") . PHP_EOL;
    }
}

// ============================================================================
// ZOMBIE CLASS — Represents enemies with health and attack logic
// ============================================================================
class Zombie {
    private int $health;
    private int $maxHealth;
    private int $attack;

    public function __construct(?int $health = null, int $attack = 10) {
        $this->maxHealth = $health ?? rand(20, 40); // random max health if none provided
        $this->health = $this->maxHealth;
        $this->attack = $attack;
    }

    public function getMaxHealth(): int { return $this->maxHealth; }
    public function getHealth(): int { return $this->health; }
    public function getAttack(): int { return $this->attack; }

    public function takeDamage(int $dmg): void {
        $this->health -= $dmg;
    }

    // Dead when HP drops to zero or below
    public function isDead(): bool {
        return $this->health <= 0;
    }

    // Zombie strike with variance adds unpredictability
    public function strike(): int {
        return max(1, $this->attack + rand(-3, 5));
    }
}

// ============================================================================
// LOCATION CLASS — Each node in the world map with ASCII art and danger level
// ============================================================================
class Location {
    public string $name;
    public string $description;
    public string $ascii;
    public int $danger; // Percentage chance of an encounter

    public function __construct(string $name, string $desc, string $ascii = "", int $danger = 30) {
        $this->name = $name;
        $this->description = $desc;
        $this->ascii = $ascii;
        $this->danger = $danger;
    }

    // Display introductory text + ASCII art
    public function showIntro(): void {
        echo color("=== " . $this->name . " ===", "36") . PHP_EOL;

        if (!empty($this->ascii)) {
            // Yellow ASCII art for visibility
            echo color($this->ascii, "33") . PHP_EOL;
        }

        echo $this->description . PHP_EOL;
    }
}

// ============================================================================
// GAME CONTROLLER — where the story, rules, and loop logic live
// ============================================================================
class Game {
    private Player $player;
    private array $map;      // List of Location objects
    private int $pos;        // Player's current index in map
    private bool $running;

    public function __construct() {
        $this->player = new Player();
        $this->map = $this->buildMap();
        $this->pos = 0;          // Start at first location
        $this->running = true;
    }

    // ------------------------------------------------------------------------
    // Build the world map. Each location has unique art + danger level.
    // ------------------------------------------------------------------------
    private function buildMap(): array {
        return [
            new Location("Start Camp", "You wake up among ruined tents...", $this->asciiCamp(), 10),
            new Location("Abandoned House", "Boarded windows; something moves inside.", $this->asciiHouse(), 40),
            new Location("Dark Forest", "Trees close around you...", $this->asciiForest(), 60),
            new Location("Riverside Tunnel", "Echoes and damp stone.", $this->asciiTunnel(), 50),
            new Location("Gas Station", "Broken pumps and scattered crates.", $this->asciiGasStation(), 20),
            new Location("Burnt Town", "Ash and burned cars.", $this->asciiTown(), 65),
            new Location("Highway Overpass", "Long and exposed.", $this->asciiOverpass(), 45),
            new Location("Military Base", "Massive gates. Final hope.", $this->asciiBase(), 80),
        ];
    }

    // ------------------------------------------------------------------------
    // START GAME — Main loop, called once at end of file
    // ------------------------------------------------------------------------
    public function start(): void {
        clearScreen();
        $this->showTitle();
        waitEnter();

        // MAIN LOOP
        while ($this->running && $this->player->isAlive()) {
            clearScreen();
            $this->player->showStats();

            $loc = $this->map[$this->pos];
            $loc->showIntro();

            // Random encounter or loot depending on danger level
            $this->locationEvent($loc);

            // Present main options for every location
            $this->mainChoices($loc);

            // Game over condition — hunger
            if ($this->player->getHunger() >= 100) {
                $this->ending("HUNGER");
                return;
            }

            // Reached last location triggers boss fight
            if ($this->pos === count($this->map) - 1) {
                $this->bossFight();
                return;
            }
        }

        // Death fallback
        if (!$this->player->isAlive()) {
            $this->ending("DEAD");
        }
    }

    // ------------------------------------------------------------------------
    // TITLE SCREEN WITH ASCII ART
    // ------------------------------------------------------------------------
    private function showTitle(): void {
        echo color($this->titleBlock(), "35") . PHP_EOL;
        echo color($this->titleGraffiti(), "31") . PHP_EOL;
        echo color("WELCOME: THE OUTBREAK SURVIVOR", "36") . PHP_EOL;
        line();
        echo "Objective: Reach the military base alive." . PHP_EOL;
    }


    private function titleBlock(): string {
        return "
████████╗██╗  ██╗███████╗     ██████╗ ██╗   ██╗████████╗██████╗ ██████╗ ███████╗ █████╗ ██╗  ██╗
╚══██╔══╝██║  ██║██╔════╝    ██╔═══██╗██║   ██║╚══██╔══╝██╔══██╗██╔══██╗██╔════╝██╔══██╗██║ ██╔╝
   ██║   ███████║█████╗      ██║   ██║██║   ██║   ██║   ██████╔╝██████╔╝█████╗  ███████║█████╔╝ 
   ██║   ██╔══██║██╔══╝      ██║   ██║██║   ██║   ██║   ██╔══██╗██╔══██╗██╔══╝  ██╔══██║██╔═██╗ 
   ██║   ██║  ██║███████╗    ╚██████╔╝╚██████╔╝   ██║   ██████╔╝██║  ██║███████╗██║  ██║██║  ██╗
   ╚═╝   ╚═╝  ╚═╝╚══════╝     ╚═════╝  ╚═════╝    ╚═╝   ╚═════╝ ╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝
                                                                                                                                                                                                                                                   
";
    }
   
    private function titleGraffiti(): string {
        return "
        ~ Welcome to the apocalypse! ~
   ~ Ash, metal, and the smell of burning oil ~
   ~ Zombies roam the land... survive if you can. ~
";
    }
    // ------------------------------------------------------------------------
    // LOCATION EVENT (random chance based on danger)
    // ------------------------------------------------------------------------
    private function locationEvent(Location $loc): void {
        $chance = rand(1, 100);
        if ($chance <= $loc->danger) {
            $this->randomEvent();
        } else {
            echo color("The area seems quiet... for now.", "33") . PHP_EOL;
        }
    }

    // ------------------------------------------------------------------------
    // MAIN CHOICES EACH LOCATION
    // ------------------------------------------------------------------------
    private function mainChoices(Location $loc): void {
        echo PHP_EOL;
        echo color("What do you do next?", "36") . PHP_EOL;
        echo "A) Search the area\n";
        echo "B) Move forward\n";
        echo "C) Rest / Use item\n";
        echo "D) Check inventory / Equip\n";

        $choice = strtolower(prompt("Choice (A/B/C/D): "));

        switch ($choice) {
            case 'a': $this->searchArea($loc); break;
            case 'b': $this->moveForward(); break;
            case 'c': $this->restAction(); break;
            case 'd': $this->manageInventory(); break;
            default:
                echo color("Invalid choice. Time passes...", "31") . PHP_EOL;
                waitEnter();
                break;
        }
    }

    // ------------------------------------------------------------------------
    // SEARCH AREA — weighted loot + chance of zombies
    // ------------------------------------------------------------------------
    private function searchArea(Location $loc): void {
        echo color("You search the area carefully...", "33") . PHP_EOL;
        $roll = rand(1, 100);

        if ($roll <= 45) {
            // Weighted loot system → demonstrates probability logic
            $items = ["Canned Food", "Ammo Pack", "Medkit", "Pistol", "Shotgun", "Molotov", "Machete"];
            $weights = [25, 25, 18, 18, 6, 8, 10];
            $item = $this->weightedChoice($items, $weights);
            echo color("You found: $item", "32") . PHP_EOL;

            if ($item === "Ammo Pack") {
                $gain = rand(2, 6);
                $this->player->addAmmo($gain);
                echo color("Ammo +$gain", "32") . PHP_EOL;
            } else {
                $this->player->addItem($item);
            }

        } elseif ($roll <= 75) {
            echo color("A zombie lunges out!", "31") . PHP_EOL;

            $z = new Zombie(rand(15, 30), rand(8, 12));

            // BEFORE combat, track player HP
            $beforeHP = $this->player->getHealth();

            $this->zombieEncounter($z);

            // IF PLAYER ESCAPED:
            // Zombie still alive AND player took no melee damage → escape/hide
            if (!$z->isDead() && $this->player->getHealth() === $beforeHP) {
                return; // ← STOP searchArea immediately
            }

            // If player died, stop too
            if (!$this->player->isAlive()) {
                return;
            }
        }
 else {
            echo color("You find nothing but a cold wind.", "37") . PHP_EOL;
        }

        // Searching increases hunger
        $this->player->increaseHunger(6);
        waitEnter();
    }

    // ------------------------------------------------------------------------
    // MOVE FORWARD — increments map position & possible ambush
    // ------------------------------------------------------------------------
    private function moveForward(): void {
        echo color("You proceed to the next area...", "36") . PHP_EOL;

        // 20% ambush chance
        if (rand(1, 100) <= 20) {
            echo color("Ambush! Multiple zombies attack!", "31") . PHP_EOL;
            $count = rand(1, 3);
            for ($i = 0; $i < $count; $i++) {
                $z = new Zombie(rand(10, 25), rand(6, 12));
                $this->zombieEncounter($z);
                if (!$this->player->isAlive()) return;
            }
        }

        // Move to next location unless already at end
        if ($this->pos < count($this->map) - 1) {
            $this->pos++;
            $this->player->increaseHunger(12);
        } else {
            echo color("This is as far as you can go.", "33") . PHP_EOL;
        }
        waitEnter();
    }

    // ------------------------------------------------------------------------
    // RESTING — uses items or restores HP with penalty
    // ------------------------------------------------------------------------
    private function restAction(): void {
        echo color("Resting options:", "36") . PHP_EOL;
        echo "1) Use Medkit\n";
        echo "2) Eat Canned Food\n";
        echo "3) Short rest (health +10, hunger +15)\n";
        $opt = prompt("Choose (1/2/3): ");
        switch ($opt) {
            case '1':
                if ($this->player->hasItem("Medkit")) {
                    $this->player->removeItem("Medkit");
                    $this->player->heal(30);
                    echo color("Used Medkit. Health +30.", "32") . PHP_EOL;
                } else {
                    echo color("No Medkit.", "33") . PHP_EOL;
                }
                break;
            case '2':
                if ($this->player->hasItem("Canned Food")) {
                    $this->player->removeItem("Canned Food");
                    $this->player->decreaseHunger(30);
                    echo color("Ate canned food. Hunger -30.", "32") . PHP_EOL;
                } else {
                    echo color("No canned food.", "33") . PHP_EOL;
                }
                break;
            case '3':
                echo color("Short rest: Health +10, Hunger +15", "32") . PHP_EOL;
                $this->player->heal(10);
                $this->player->increaseHunger(15);
                break;
            default:
                echo color("You do nothing...", "33") . PHP_EOL;
                break;
        }
        waitEnter();
    }

    // ------------------------------------------------------------------------
    // INVENTORY MANAGEMENT — Equip + Drop Item UI
    // ------------------------------------------------------------------------
    private function manageInventory(): void {
        $inv = $this->player->getInventory();
        if (empty($inv)) {
            echo color("Inventory empty.", "33") . PHP_EOL;
            waitEnter();
            return;
        }
        echo color("Your Inventory:", "36") . PHP_EOL;
        foreach ($inv as $i => $item) {
            echo ($i + 1) . ") $item\n";
        }
        echo "E) Equip weapon from inventory\n";
        echo "D) Drop item\n";
        echo "B) Back\n";
        $choice = strtolower(prompt("Choice: "));
        if ($choice === 'e') {
            $num = (int)prompt("Enter item number to equip (weapon): ");
            $idx = $num - 1;
            if (isset($inv[$idx])) {
                $this->player->setWeapon($inv[$idx]);
            }
        } elseif ($choice === 'd') {
            $num = (int)prompt("Select item number to drop: ");
            $idx = $num - 1;
            if (isset($inv[$idx])) {
                $item = $inv[$idx];
                $this->player->removeItem($item);
                echo color("Dropped: " . $item, "33") . PHP_EOL;
            } else {
                echo color("Invalid selection.", "31") . PHP_EOL;
            }
        } else {
            // back
        }
        waitEnter();
    }

    // ------------------------------------------------------------------------
    // RANDOM EVENTS — small side bonuses/dangers
    // Demonstrates switch-case logic and array_rand()
// ------------------------------------------------------------------------
    private function randomEvent(): void {
        $events = ["food", "ammo", "trap", "medic", "nothing", "weapon"];
        $choice = $events[array_rand($events)];

        switch ($choice) {
            case "food":
                echo color("You find canned food on a shelf.", "32") . PHP_EOL;
                $this->player->addItem("Canned Food");
                break;
            case "ammo":
                $gain = rand(1, 4);
                echo color("You discover ammo. Ammo +$gain", "32") . PHP_EOL;
                $this->player->addAmmo($gain);
                break;
            case "trap":
                $dmg = rand(5, 20);
                echo color("A hidden trap injures you for $dmg damage!", "31") . PHP_EOL; 
                $this->player->takeDamage($dmg);
                break;
            case "medic":
                echo color("A friendly survivor gives you a Medkit.", "32") . PHP_EOL;
                $this->player->addItem("Medkit");
                break;
            case "weapon":
                // Weighted weapon loot
                $weapons = ["Pistol", "Shotgun", "Molotov", "Machete"];
                $weights = [20, 6, 8, 10];
                $found = $this->weightedChoice($weapons, $weights);
                echo color("You find a weapon: $found", "32") . PHP_EOL;
                $this->player->addItem($found);
                break;
            default:
                echo color("Nothing of interest here.", "33") . PHP_EOL;
                break;
        }
        // Random events always increase hunger slightly
        $this->player->increaseHunger(5);
        waitEnter();
    }

    // Weighted random selection helper
    private function weightedChoice(array $items, array $weights) {
        $total = array_sum($weights);
        $roll = rand(1, $total);
        $acc = 0;
        foreach ($items as $i => $it) {
            $acc += $weights[$i];
            if ($roll <= $acc) return $it;
        }
        return $items[0]; // fallback
    }

    // ------------------------------------------------------------------------
    // COMBAT LOOP — handles all zombie fights
    // ------------------------------------------------------------------------
    private function zombieEncounter(Zombie $z): void {
        $actionResult = "continue";
        while (!$z->isDead() && $this->player->isAlive()) {
            // Zombier encounter UI
            echo PHP_EOL;
            echo color("===== ZOMBIE ENCOUNTER =====", "31") . PHP_EOL;
            echo color("🧟 Zombie HP: " . renderHealthBar($z->getHealth(), $z->getMaxHealth()), "31") . PHP_EOL;
            echo color("❤️ Your Health: " . renderHealthBar($this->player->getHealth(), $this->player->getMaxHealth()), "32") . PHP_EOL;
            echo color("🔫 Ammo: {$this->player->getAmmo()}   Weapon: {$this->player->getWeapon()}", "32") . PHP_EOL;
            echo PHP_EOL;

            echo "A) Shoot (uses ammo)\n";
            echo "B) Melee attack (knife/machete)\n";
            echo "C) Try to Push & escape\n";
            echo "D) Hide (chance to avoid)\n";
            echo "E) Use Medkit\n";

            $action = strtolower(prompt("Action (A/B/C/D/E): "));

            // IMPORTANT: initialize before switch
            $actionResult = "continue";

            switch ($action) {

                case 'a':
                case 'shoot':
                    $this->handleShoot($z);
                    break;

                case 'b':
                case 'melee':
                    $this->handleMelee($z);
                    break;

                case 'c':
                case 'push':
                    $actionResult = $this->handlePush($z);
                    break;

                case 'd':
                case 'hide':
                    $actionResult = $this->handleHide($z);
                    break;

                case 'e':
                case 'medkit':
                    $this->handleMedkit();
                    break;

                default:
                    echo color("You hesitate and lose time.", "33") . PHP_EOL;
                    break;
            }    
            // Stop combat if escaped or avoided
            if ($actionResult === "escaped" || $actionResult === "avoided") {
                echo color("You got away from the zombie!", "32") . PHP_EOL;
                return;
            }
            // Zombie counterattack if still alive
            if (!$z->isDead()) {
                $zDmg = $z->strike();
                echo color("The zombie attacks and deals $zDmg damage!", "31") . PHP_EOL;
                $this->player->takeDamage($zDmg);
            } else {
                // Chance for bonus loot
                echo color("Zombie defeated!", "32") . PHP_EOL;
                if (rand(1, 100) <= 35) {
                    echo color("The zombie dropped ammo. Ammo +1", "32") . PHP_EOL;
                    $this->player->addAmmo(1);
                }
            }
            // Combat increases hunger slowly
            $this->player->increaseHunger(5);

            if (!$this->player->isAlive()) {
                echo color("You have been mortally wounded...", "31") . PHP_EOL;
                break;
            }
        }
    }

    // SHOOT — Enforces ranged weapons + ammo usage
    private function handleShoot(Zombie $z): void {
        $weapon = $this->player->getWeapon();
        $ranged = ["Pistol", "Shotgun"];
        if (!in_array($weapon, $ranged)) {
            echo color("You can't shoot with that $weapon! Please equip a Gun.", "33") . PHP_EOL;
            return;
        }
        $cost = ($weapon === "Shotgun") ? 2 : 1;
        if (!$this->player->useAmmo($cost)) {
            echo color("Not enough ammo for $weapon.", "33") . PHP_EOL;
            return;
        }

        $this->player->useAmmo($cost);
        $dmg = $this->weaponDamage($weapon, true);
        echo color("You fire your $weapon and deal $dmg damage.", "32") . PHP_EOL;
        $z->takeDamage($dmg);
    }

    // MELEE — handles close-range attacks
    private function handleMelee(Zombie $z): void {
        $weapon = $this->player->getWeapon();
        $dmg = $this->weaponDamage($weapon, false);
        echo color("You attack with $weapon and deal $dmg damage.", "32") . PHP_EOL;
        $z->takeDamage($dmg);
    }

    // PUSH/ESCAPE — 50% escape chance
    private function handlePush(Zombie $z): string {
        $chance = rand(1, 100);

        if ($chance <= 50) {
            echo color("You shove the zombie and break free!", "32") . PHP_EOL;
            return "escaped";
        }

        echo color("Your push fails! The zombie grabs you!", "31") . PHP_EOL;
        return "continue";
    }


    // HIDE — 40% avoid chance
    private function handleHide(Zombie $z): string {
        $chance = rand(1, 100);

        if ($chance <= 40) { 
            echo color("You hide behind debris... the zombie loses sight of you!", "32") . PHP_EOL;
            return "avoided";
        }

        echo color("You fail to hide! The zombie spots you!", "31") . PHP_EOL;
        return "continue";
    }


    // MEDKIT
    private function handleMedkit(): void {
        if ($this->player->hasItem("Medkit")) {
            $this->player->removeItem("Medkit");
            $this->player->heal(30);
            echo color("You used a Medkit. Health +30.", "32") . PHP_EOL;
        } else {
            echo color("No Medkit available.", "33") . PHP_EOL;
        }
    }

    // DAMAGE TABLE — defines weapon behavior
    private function weaponDamage(string $weapon, bool $ranged = false): int {
        switch ($weapon) {
            case "Shotgun":
                return $ranged ? rand(30, 45) : rand(20, 30);
            case "Pistol":
                return $ranged ? rand(16, 26) : rand(8, 14);
            case "Machete":
                return rand(18, 28);
            case "Molotov":
                // Molotov is single-use
                $this->player->removeItem("Molotov");
                return rand(35, 50);
            case "Knife":
            default:
                return rand(8, 15);
        }
    }
    // ------------------------------------------------------------------------
    // FINAL BOSS FIGHT — scripted battle with special options
    // ------------------------------------------------------------------------
    private function bossFight(): void {
        clearScreen();
        echo color($this->asciiBoss(), "31") . PHP_EOL;
        echo color("THE TITAN ZOMBIE APPROACHES!", "31") . PHP_EOL;
        $bossHealth = 180;
        $bossAttack = 20;
        // Boss loop
        while ($bossHealth > 0 && $this->player->isAlive()) {
            $this->player->showStats();
            echo color("Boss HP: $bossHealth", "35") . PHP_EOL;
            echo "1) Aim for the head (high damage, low accuracy)\n";
            echo "2) Aim for the legs (medium damage, reduce boss attack)\n";
            echo "3) Molotov (if available)\n";
            echo "4) Barricade (reduce incoming damage)\n";
            $opt = prompt("Choice (1-4): ");
            $reduced = false;
            switch ($opt) {
                case '1':
                    if (rand(1, 100) <= 45) {
                        $dmg = rand(28, 50);
                        echo color("Headshot! Boss takes $dmg damage.", "32") . PHP_EOL;
                        $bossHealth -= $dmg;
                    } else {
                        echo color("You miss the head!", "33") . PHP_EOL;
                    }
                    break;
                case '2':
                    $dmg = rand(14, 26);
                    echo color("You strike the legs for $dmg damage and slow the boss.", "32") . PHP_EOL;
                    $bossHealth -= $dmg;
                    $reduced = true;
                    break;
                case '3':
                    if ($this->player->hasItem("Molotov")) {
                        $this->player->removeItem("Molotov");
                        $dmg = rand(40, 60);
                        echo color("Molotov hits! Boss takes $dmg damage.", "32") . PHP_EOL;
                        $bossHealth -= $dmg;
                    } else {
                        echo color("No Molotov found!", "33") . PHP_EOL;
                    }
                    break;
                case '4':
                    echo color("You barricade — incoming damage reduced this turn.", "33") . PHP_EOL;
                    $reduced = true;
                    break;
                default:
                    echo color("Indecision costs you.", "33") . PHP_EOL;
                    break;
            }
            // Boss attacks back
            if ($bossHealth > 0) {
                $bossHit = rand($bossAttack - 6, $bossAttack + 8);
                if ($reduced) $bossHit = (int)($bossHit / 2);
                echo color("The Titan slams you for $bossHit damage!", "31") . PHP_EOL;
                $this->player->takeDamage($bossHit);
            } else {
                echo color("The Titan collapses — you've beaten it!", "32") . PHP_EOL;
                $this->ending("SURVIVE");
                return;
            }

            $this->player->increaseHunger(8);

            if (!$this->player->isAlive()) {
                $this->ending("DEAD");
                return;
            }
            waitEnter();
        }
    }

    // ------------------------------------------------------------------------
    // GAME ENDINGS — Based on how the player dies or survives
    // ------------------------------------------------------------------------
    private function ending(string $type): void {
        clearScreen();
        line();
        switch ($type) {
            case "HUNGER":
                echo color("☠️  YOU STARVED. Your journey ends here.", "31") . PHP_EOL;
                break;
            case "DEAD":
                echo color("🧟 YOU WERE KILLED BY THE UNDEAD. Game Over.", "31") . PHP_EOL;
                break;
            case "SURVIVE":
                echo color("🚁 RESCUE! You reached the Military Base and are airlifted to safety.", "32") . PHP_EOL;
                echo color("🏆 Ending: THE OUTBREAK SURVIVOR", "32") . PHP_EOL;
                break;
            default:
                echo color("The world fades...", "31") . PHP_EOL;
        }
        line();
        waitEnter("Press ENTER to exit...");
        exit;
    }
    
    // ---------------------------
    // Polished ASCII for locations
    // ---------------------------
    private function asciiCamp(): string {
        return "
                 ⠀⠀⠀⠀⠀⣠⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⢸⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⢸⡇⠠⣤⣤⣤⣴⣶⣶⣾⣿⣿⣿⣇⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⣼⣿⡀⢻⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣦⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⣼⣿⢹⣷⡈⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⡄⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⣰⣿⡿⠸⣿⣷⡀⢻⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣦⠀⠀⠀⠀
⠀⠀⠀⠀⠀⣰⣿⣿⡇⠀⣿⣿⣷⡄⠹⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⣄⠀⠀
⠀⠀⠀⠀⣰⣿⣿⣿⠃⠀⢿⣿⣿⣿⣦⡘⢿⣿⣿⣿⣿⣿⡻⣿⣿⣿⡿⠛⠀⠀
⠀⠀⠀⣴⣿⣿⣿⡿⠀⠀⢸⣿⣿⣿⣿⣷⣄⠙⣿⣿⣿⣿⣿⣤⡙⠻⠀⠀⠀⠀
⠀⢀⣾⣿⣿⣿⣿⠇⠀⠀⠀⣿⣿⣿⣿⣿⣿⣦⠈⢿⠟⠋⠉⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠉⠉⠉⠉⠉⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
        [ START CAMP ]
        ";
    }

    private function asciiHouse(): string {
        return "
        
                            +&-
                          _.-^-._    .--.
                       .-'   _   '-. |__|
                      /     |_|     \|  |
                     /               \  |
                    /|     _____     |\ |
                     |    |==|==|    |  |
 |---|---|---|---|---|    |--|--|    |  |
 |---|---|---|---|---|    |==|==|    |  |
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
                  [House]
        ";
    }

    private function asciiForest(): string {
        return "
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠓⠒⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣀⣀⠀⠀⠀⠀⠀⢠⢤⣤⣤⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⡠⠔⠒⠒⠲⠎⠀⠀⢹⡃⢀⣀⠀⠑⠃⠀⠈⢀⠔⠒⢢⠀⠀⠀⡖⠉⠉⠉⠒⢤⡀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⠔⠚⠙⠒⠒⠒⠤⡎⠀⠀⠀⠀⢀⣠⣴⣦⠀⠈⠘⣦⠑⠢⡀⠀⢰⠁⠀⠀⠀⠑⠰⠋⠁⠀⠀⠀⠀⠀⠈⢦⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⣸⠁⠀⠀⠀⠀⠀⠀⢰⠃⠀⣀⣀⡠⣞⣉⡀⡜⡟⣷⢟⠟⡀⣀⡸⠀⡎⠀⠀⠀⠀⠀⡇⠀⠀⠀⠀⠀⠀⠀⠀⣻⠀⠀⠀⠀
⢰⠂⠀⠀⠀⠀⠀⠀⠀⣗⠀⠀⢀⣀⣀⣀⣀⣀⣓⡞⢽⡚⣑⣛⡇⢸⣷⠓⢻⣟⡿⠻⣝⢢⠀⢇⣀⡀⠀⠀⠀⢈⠗⠒⢶⣶⣶⡾⠋⠉⠀⠀⠀⠀⠀
⠈⠉⠀⠀⠀⠀⠀⢀⠀⠈⠒⠊⠻⣷⣿⣚⡽⠃⠉⠀⠀⠙⠿⣌⠳⣼⡇⠀⣸⣟⡑⢄⠘⢸⢀⣾⠾⠥⣀⠤⠖⠁⠀⠀⠀⢸⡇⠀⠀⠀⠀⠀⢀⠀⠀
⠀⠀⠀⢰⢆⠀⢀⠏⡇⠀⡀⠀⠀⠀⣿⠉⠀⠀⠀⠀⠀⠀⠀⠈⢧⣸⡇⢐⡟⠀⠙⢎⢣⣿⣾⡷⠊⠉⠙⠢⠀⠀⠀⠀⠀⢸⡇⢀⠀⠀⠀⠀⠈⠣⡀
⠀⠀⠀⠘⡌⢣⣸⠀⣧⢺⢃⡤⢶⠆⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠙⣟⠋⢀⠔⣒⣚⡋⠉⣡⠔⠋⠉⢰⡤⣇⠀⠀⠀⠀⢸⡇⡇⠀⠀⠀⠀⠀⠀⠸
⠀⠀⠀⠀⠑⢄⢹⡆⠁⠛⣁⠔⠁⠀⣿⠀⠀⠀⠀⢸⡇⠀⠀⠀⠀⠀⣿⢠⡷⠋⠁⠀⠈⣿⡇⠀⠀⠀⠈⡇⠉⠀⠀⠀⠀⢸⡇⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠑⣦⡔⠋⠁⠀⠀⠀⣿⠀⠀⢠⡀⢰⣼⡇⠀⡀⠀⠀⣿⠀⠁⠀⠀⠀⠀⣿⣷⠀⠀⠀⠀⡇⠀⠀⢴⣤⠀⢸⡇⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⢰⣿⡇⠀⠀⠀⠀⠀⣿⡀⠀⢨⣧⡿⠋⠀⠘⠛⠀⠀⣿⠀⠀⢀⠀⠀⠀⣿⣿⠀⠀⠀⠀⢲⠀⠀⠀⠀⠀⢸⡇⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⢸⣿⡇⠀⠀⠀⠀⠀⢸⡧⡄⠀⠹⣇⡆⠀⠀⠀⠀⠀⣿⠀⢰⣏⠀⣿⣸⣿⣿⠀⠀⠀⠀⣼⠀⠀⠰⠗⠀⢸⡇⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⢸⣿⡇⠀⠀⠀⠀⠀⢸⡇⣷⣛⣦⣿⢀⠈⠑⠀⢠⡆⣿⠐⢠⣟⠁⢸⠸⣿⣿⢱⣤⢀⠀⣼⠀⠀⢀⠀⠀⢸⡇⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⢸⣿⡇⠀⢀⠀⠀⠀⢸⡇⠘⠫⣟⡇⠊⣣⠘⠛⣾⡆⢿⠀⠙⣿⢀⣘⡃⣿⣿⡏⠉⠒⠂⡿⠀⠰⣾⡄⠀⢸⡟⣽⣀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠸⣿⡇⠀⠘⣾⠀⠀⢸⡇⢸⣇⡙⠣⠀⣹⣇⠀⠈⠧⢀⣀⣀⡏⣸⣿⣇⢹⣿⡇⢴⣴⣄⣀⡀⢰⣿⡇⠀⢸⣇⢿⡿⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠓⠁⠈⠻⢷⠾⠦⠤⠬⣅⣹⣿⣖⣶⣲⣈⡥⠤⠶⡖⠛⠒⠛⠁⠉⠛⠮⠐⢛⡓⠒⢛⠚⠒⠒⠒⠛⣚⣫⡼⠿⠿⣯⠛⠤⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠉⠉⠉⠉⠉⠉⡉⠉⠁⠀⠀⠘⠓⠀⠀⠀⠀⠀⣀⣞⡿⡉⠉⠉⠉⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠹⣶⠏⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠉⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
                            [ DARK FOREST ]
        ";
    }

    private function asciiTunnel(): string {
        return "
        ⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣀⣀⣀⣀⡀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⣀⣤⣴⣶⣶⣶⣦⣤⣀⠀⠀⠀⣠⣴⣿⣿⣿⣿⣿⣿⣿⣿⣶⣄⡀⠀⠀
⠀⣴⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠇⣠⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣦⠀
⠀⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠏⢰⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⠀
⠀⣿⣿⠿⠿⠿⠿⠟⠛⠛⠛⠀⠛⠛⠛⠛⠛⠛⠛⠻⠿⠿⠿⠿⠿⠿⠿⠿⠿⠀
⠀⠀⠀⠀⠀⠀⠀⢠⣤⣶⣶⣶⠋⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠙⠻⢿⣿⣿⣶⣶⣦⣤⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⣀⣀⣀⣤⣤⣤⣽⣿⣿⣿⣿⡿⠇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⣤⣶⣿⣿⣿⣿⣿⣿⣿⡿⠛⠛⠋⠉⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠙⠛⠿⢿⣯⣭⣝⡛⠻⢿⣿⣿⣷⣶⣶⣦⣤⣤⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠉⠙⠛⠿⢶⣾⣿⣿⣿⣿⣿⣿⣿⡿⣿⣷⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣀⣤⣾⣿⣿⢿⣿⣿⢟⣡⣼⣿⠟⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⣠⣶⣿⣿⣿⠟⣡⣾⣿⣿⣿⣿⡿⠋⠁⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⢰⣿⣿⠹⣿⣿⣄⠻⣿⣿⣿⠻⣿⣿⣦⣄⣀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠘⠛⠛⠓⠈⠛⠛⠛⠊⠛⠛⠓⠀⠙⠛⠛⠛⠛⠓⠒⠀⠀⠀⠀
        [ RIVERSIDE TUNNEL ]
        ";
    }

    private function asciiGasStation(): string {
        return "
                                   /\
                              /\  //\\
                       /\    //\\///\\\        /\
                      //\\  ///\////\\\\  /\  //\\
         /\          /  ^ \/^ ^/^  ^  ^ \/^ \/  ^ \
        / ^\    /\  / ^   /  ^/ ^ ^ ^   ^\ ^/  ^^  \
       /^   \  / ^\/ ^ ^   ^ / ^  ^    ^  \/ ^   ^  \       *
      /  ^ ^ \/^  ^\ ^ ^ ^   ^  ^   ^   ____  ^   ^  \     /|\
     / ^ ^  ^ \ ^  _\___________________|  |_____^ ^  \   /||o\
    / ^^  ^ ^ ^\  /______________________________\ ^ ^ \ /|o|||\
   /  ^  ^^ ^ ^  /________________________________\  ^  /|||||o|\
  /^ ^  ^ ^^  ^    ||___|___||||||||||||___|__|||      /||o||||||\
 / ^   ^   ^    ^  ||___|___||||||||||||___|__|||          | |
/ ^ ^ ^  ^  ^  ^   ||||||||||||||||||||||||||||||oooooooooo| |ooooooo
ooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooooo
                            [ GAS STATION ]
      ";
    }

    private function asciiTown(): string {
        return "
                                    +             
        *                          / \
 _____        _____     __________/ o \/\_________      _________
|o o o|_______|    |___|               | | # # #  |____|o o o o  | /\
|o o o|  * * *|: ::|. .|               |o| # # #  |. . |o o o o  |//\\
|o o o|* * *  |::  |. .| []  []  []  []|o| # # #  |. . |o o o o  |((|))
|o o o|**  ** |:  :|. .| []  []  []    |o| # # #  |. . |o o o o  |((|))
|_[]__|__[]___|_||_|__<|____________;;_|_|___/\___|_.|_|____[]___|  |   |____
                     [ BURNT TOWN ]
        ";
    }

    private function asciiOverpass(): string {
        return "
                             ___....___
   ^^                __..-:'':__:..:__:'':-..__
                 _.-:__:.-:'':  :  :  :'':-.:__:-._
               .':.-:  :  :  :  :  :  :  :  :  :._:'.
            _ :.':  :  :  :  :  :  :  :  :  :  :  :'.: _
           [ ]:  :  :  :  :  :  :  :  :  :  :  :  :  :[ ]
           [ ]:  :  :  :  :  :  :  :  :  :  :  :  :  :[ ]
  :::::::::[ ]:__:__:__:__:__:__:__:__:__:__:__:__:__:[ ]:::::::::::
  !!!!!!!!![ ]!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!![ ]!!!!!!!!!!!
  ^^^^^^^^^[ ]^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^[ ]^^^^^^^^^^^
           [ ]                                        [ ]
           [ ]                                        [ ]
     jgs   [ ]                                        [ ]
   ~~^_~^~/   \~^-~^~ _~^-~_^~-^~_^~~-^~_~^~-~_~-^~_^/   \~^ ~~_ ^
                        [ HIGHWAY OVERPASS ]
   ";
    }

    private function asciiBase(): string {
        return "
                                               /\      /\
                                               ||______||
                                               || ^  ^ ||
                                               \| |  | |/
                                                |______|
              __                                |  __  |
             /  \       ________________________|_/  \_|__
            / ^^ \     /=========================/ ^^ \===|
           /  []  \   /=========================/  []  \==|
          /________\ /=========================/________\=|
       *  |        |/==========================|        |=|
      *** | ^^  ^^ |---------------------------| ^^  ^^ |--
     *****| []  [] |           _____           | []  [] | |
    *******        |          /_____\          |      * | |
   *********^^  ^^ |  ^^  ^^  |  |  |  ^^  ^^  |     ***| |
  ***********]  [] |  []  []  |  |  |  []  []  | ===***** |
 *************     |         @|__|__|@         |/ |*******|
***************   ***********--=====--**********| *********
***************___*********** |=====| **********|***********
 *************     ********* /=======\ ******** | *********
    ***********       ******* /=========\\*******   *******
                        [ MILITARY BASE ]   
        ";
    }

    private function asciiBoss(): string {
        return "
                            ,-.                               
       ___,---.__          /'|`\          __,---,___          
    ,-'    \`    `-.____,-'  |  `-.____,-'    //    `-.       
  ,'        |           ~'\     /`~           |        `.      
 /      ___//              `. ,'          ,  , \___      \    
|    ,-'   `-.__   _         |        ,    __,-'   `-.    |    
|   /          /\_  `   .    |    ,      _/\          \   |   
\  |           \ \`-.___ \   |   / ___,-'/ /           |  /  
 \  \           | `._   `\\  |  //'   _,' |           /  /      
  `-.\         /'  _ `---'' , . ``---' _  `\         /,-'     
     ``       /     \    ,='/ \`=.    /     \       ''          
             |__   /|\_,--.,-.--,--._/|\   __|                  
             /  `./  \\`\ |  |  | /,//' \,'  \                  
            /   /     ||--+--|--+-/-|     \   \                 
           |   |     /'\_\_\ | /_/_/`\     |   |                
            \   \__, \_     `~'     _/ .__/   /            
             `-._,-'   `-._______,-'   `-._,-'
                    [ TITAN ZOMBIE ]
        ";
    }
}

// ---------------------------
// Run the game
// ---------------------------
$game = new Game();
$game->start();

?>
