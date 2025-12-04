<?php
class Game {
    private $playerturn = 0;//p1 gets first turn
    private $board = [
        [" ", " ", " "],
        [" ", " ", " "],
        [" ", " ", " "]
    ];//board to start
    
    public function resetBoard(){
        $this->board = [
        [" ", " ", " "],
        [" ", " ", " "],
        [" ", " ", " "]
    ];
    }//resets board after a game

    public function switchPlayer(){
        $this->playerturn = !$this->playerturn;
    }//switches from p1 to p2 or vice versa

    public function getPlayer(){
        return $this->playerturn;
    }//gets current player

    public function setPlayer($num){
        $this->playerturn = $num;
    }//sets current player

    public function printBoard(){
        echo "  | 0 | 1 | 2 |\n";
        echo "  |___________|\n";
        for($i = 0; $i < 3; $i++){
            echo $i . " | " . $this->board[$i][0] . " | " . $this->board[$i][1] . " | " . $this->board[$i][2] . " | \n";

            if($i < 2){
                echo "--|---+---+---|\n";
            }
        }
    }//prints the board's current status

    public function checkDraw(){
        for($i = 0; $i < 3; $i++){
            for($j = 0; $j < 3; $j++){
                if($this->board[$i][$j] == " "){
                return false;
            }
            }
        }
        return true;
    }

    public function checkWinner(){
        $player;
        $playermark;
        if($this->playerturn == 0){
            $playermark = "X";//checking for p1 win
            $player = "Player 1";
        }else{
            $playermark = "O";//checking for p2 win
            $player = "Player 2";
        }

        for($i = 0; $i < 3; $i++){
            if($this->board[$i][0] == $playermark && $this->board[$i][0] == $this->board[$i][1] && $this->board[$i][1] == $this->board[$i][2]){
                echo "That's a win for " . $player . "\n";
                return true;
            }//checks for horizontal win
        }

         for($j = 0; $j < 3; $j++){
            if($this->board[0][$j] == $playermark && $this->board[0][$j] == $this->board[1][$j] && $this->board[1][$j] == $this->board[2][$j]){
                echo "That's a win for " . $player . "\n";
                return true;
            }//checks for vertical win
        }

        if($this->board[0][0] == $playermark && $this->board[0][0] == $this->board[1][1] && $this->board[1][1] == $this->board[2][2]){
            echo "That's a win for " . $player . "\n";
            return true;
        }//checks for diagonal win

        if($this->board[0][2] == $playermark && $this->board[0][2] == $this->board[1][1] && $this->board[1][1] == $this->board[2][0]){
            echo "That's a win for " . $player . "\n";
            return true;
        }//checks for diagonal win

        echo "Keep playing " . $player . "\n";//if there's no win
        return false;
    }

    public function pickspot($input, $row, $col){
        if($this->board[$row][$col] == " "){
            $this->board[$row][$col] = $input;//puts the player's mark
        }else{
            echo "Spot not available\n";
        }//fails if spot is taken
    }
}

function playgame($game){
    $row;
    $col;
    $player;
    $playername;
    $mark;
    $draw = false;

    $ingame = true;

    echo "\nResetting board..\n";
    $game->resetBoard();

    $game->setPlayer(0);//resets so player 1 always starts first

    while($ingame){
        $draw = $game->checkDraw();
        if($draw){
            echo "Game ends in a draw\n";
            echo "\n";
            $ingame = false;
            break;
        }
        $player = $game->getPlayer();
        if($player == 0){
            $playername = "Player 1";
        }else{
            $playername = "Player 2";
        }
        echo "\n" . $playername . "'s turn\n";
        if($player == 0){
            $mark = "X";
        }else{
            $mark = "O";
        }


        $game->printBoard();
        echo "Choose a row: ";
        $row = readline();
        echo "Choose a col: ";
        $col = readline();
        $game->pickspot($mark, $row, $col);

        if($game->checkWinner()){
            echo "\n";
            $ingame = false;
        }
        $game->switchPlayer();
    }
}

function playcpugame($game){
    $row;
    $col;
    $player;
    $playername;
    $mark;
    $draw = false;//automatically reset to false

    $ingame = true;

    echo "\nResetting board..\n";
    $game->resetBoard();//clears board each game

    $game->setPlayer(0);//resets so player 1 always starts first

    while($ingame){
        $draw = $game->checkDraw();
        if($draw){
            echo "Game ends in a draw\n";
            echo "\n";
            $ingame = false;
            break;//makes sure game ends if board is full
        }
        $player = $game->getPlayer();
        if($player == 0){
            $playername = "Player 1";
        }else{
            $playername = "Player 2";//sets up player name for messages
        }
        echo "\n" . $playername . "'s turn\n";
        if($player == 0){
            $mark = "X";
        }else{
            $mark = "O";//sets up the mark for each player for easy readability
        }


        $game->printBoard();
        echo "Choose a row: ";
        if($player == 1){
            $row = random_int(0, 2);//cpu chooses random num
        }else{
            $row = readline();//player 1 manually inputs num
        }
        echo "Choose a col: ";
        if($player == 1){
            $col = random_int(0, 2);
        }else{
             $col = readline();
        }
        $game->pickspot($mark, $row, $col);//adds to the chosen spot

        if($game->checkWinner()){
            echo "\n";
            $ingame = false;//checks if someone won
        }
        $game->switchPlayer();//switches player turns
    }
}

function main(){
        echo "____________________________________________________________________________________________\n";
        echo "_______   _______   _______     _______     /\       _______     _______   _______   _______\n";
        echo "   |         |      |              |       /  \      |              |      |     |   |      \n";
        echo "   |         |      |              |      /____\     |              |      |     |   |______\n";
        echo "   |         |      |              |     |      |    |              |      |     |   |      \n";
        echo "   |      ___|___   |______        |     |      |    |______        |      |_____|   |______\n";
        echo "____________________________________________________________________________________________\n";
        echo "\n";

        $game = new Game();
    $playing = true;
    while($playing == true){
        echo "Choose an option: \n";
        echo "1. Play a solo game\n";
        echo "2. Play a duo game\n";
        echo "3. Exit\n";
        switch(readline()){
            case 1:
                playcpugame($game);
                break;//plays solo game
            case 2:
                playgame($game);
                break;//plays duo game
            case 3:
                echo "Bye bye\n";
                $playing = false;
                break;//ends program
        }
    }
}

main();
?>