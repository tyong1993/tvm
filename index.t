<TTT>

function abc($a,$b){
    $v = abc1(10,10);
    go_back $a+$b*2/10-5+$v;
}
function abc1($a,$b){
    go_back $a+$b*2/10-5;
}
$a = 10;
$b = 5;
$d = abc($a,$b);
dump $d;

