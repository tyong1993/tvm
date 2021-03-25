<TTT>

$a = 100;
$b = 1000;

$c = $a + $b;
$c = aaa($c,3);
dump $c;

function aaa($p1,$p2){
    $a = 3+$p1*$p2;
    go_back $a;
}