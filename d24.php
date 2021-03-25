<?php
/**
 * 三八二十四计算器
 */
$target = [1,3,9,10];
$ysf = ["+","-","*","/"];
$box=[];
foreach ($target as $key=>$val){
    $temp[0] = $target[$key];

    $target1 = $target;
    unset($target1[$key]);
    sort($target1);

    foreach ($target1 as $key1=>$val1){
        $temp[1] = $target1[$key1];

        $target2 = $target1;
        unset($target2[$key1]);
        sort($target2);

        foreach ($target2 as $key2=>$val2){
            $temp[2] = $target2[$key2];

            $target3 = $target2;
            unset($target3[$key2]);
            sort($target3);

            $temp[3] = $target3[0];

            $box[]=$temp;
        }

    }

}

$box1=[];
foreach ($ysf as $key=>$val){
    $temp1[0] = $ysf[$key];
    foreach ($ysf as $key1=>$val){
        $temp1[1] = $ysf[$key1];
        foreach ($ysf as $key2=>$val){
            $temp1[2] = $ysf[$key2];
            $box1[] = $temp1;
        }
    }
}

foreach ($box as $data){
    foreach ($box1 as $action){
        $code1 = "($data[0] $action[0] $data[1]) $action[1] ($data[2] $action[2] $data[3])";
        $code2 = "(($data[0] $action[0] $data[1]) $action[1] $data[2]) $action[2] $data[3]";
        $code3 = "($data[0] $action[0] ($data[1] $action[1] $data[2])) $action[2] $data[3]";
        $code4 = "$data[0] $action[0] (($data[1] $action[1] $data[2]) $action[2] $data[3])";
        $code5 = "$data[0] $action[0] ($data[1] $action[1] ($data[2] $action[2] $data[3]))";
        $codes = [$code1,$code2,$code3,$code4,$code5];
        foreach ($codes as $code){
            $code = "return $code;";
            $res = @eval($code);
            if($res == 24){
                echo $code.PHP_EOL;
            }
        }

    }
}