<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/3/2
 * Time: 15:41
 */

/**
 * Class Tvm
 * T语言解释器
 * php实现
 */
class Tvm
{
    //脚本魔术
    private $magic = "<TTT>";
    //局部变量前缀
    private $varPrefix = "___";
    //方法区
    private $funcArea;
    //栈区
    private $stackArea;
    //临时变量名计数后缀,保证变量名不重复,解决运算符优先级
    private $varNum = 0;
    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->funcArea = [];
        $this->stackArea = [];
    }
    /**
     * 前端编译器,将源码编译为字节码
     * 验证脚本合法性
     * 语法检测
     * 提取方法放入方法区中
     */
    public function explainer(){
        if ('cli' !== php_sapi_name()) {
            die('必须在命令行模式下运行');
        }
        global $argc, $argv;
        $target = isset($argv[1])?$argv[1]:null;
        if (!$target || !file_exists($target)) {
            die('no target file');
        }
        $targetClasssName = $target.".class";
//        if(file_exists($targetClasssName)){
//            //加载字节码
//            $classs = file_get_contents($targetClasssName);
//            $this->funcArea = json_decode($classs,true);
//            return true;
//        }
        //加载脚本
        $targetFile = fopen($target, "r") or die("Unable to open file!");
        $targetStr = "";
        // 输出单字符直到 end-of-file
        while(!feof($targetFile)) {
            $targetStr.=fgetc($targetFile);
        }
        fclose($targetFile);
        //验证脚本合法性
        if(strpos($targetStr,$this->magic) !== 0){
            die('Illegal script');
        }
        $targetStr = $this->strDel($targetStr,0,strlen($this->magic));
        //提取方法雏形
        $box=[];
        $start = 0;$end = 0;
        while (true){
            $start = strpos($targetStr,"function",$start);
            $end = strpos($targetStr,"}",$start)+1;
            if($start && $end){
                $box[] = substr($targetStr,$start,$end);
                $targetStr = $this->strDel($targetStr,$start,$end);
            }else{
                $box[] = "function main(){
                    $targetStr
                }";
                break;
            }
        }
        //方法提纯
        foreach ($box as $item){
            $functionStrIndex = strpos($item,"function");
            $item = $this->strDel($item,0,$functionStrIndex+8);
            $item = trim($item);
            $leftSIndex = strpos($item,"(");
            $rightSIndex = strpos($item,")");
            $funcName = substr($item,0,$leftSIndex);
            $funcName = trim($funcName);
            $paramStr = substr($item,$leftSIndex+1,$rightSIndex-$leftSIndex-1);
            //保存方法信息到方法区
            $this->funcArea[$funcName]["paramStr"] = !empty($paramStr)?explode(",",$paramStr):[];
            $leftBIndex = strpos($item,"{");
            $rightBIndex = strpos($item,"}");
            $item = substr($item,$leftBIndex+1,$rightBIndex-$leftBIndex-1);
            $item = trim($item);
//            var_dump($item);die;
            //词法,语法分析
            while (true){
                $endIndex = strpos($item,";");
                $temp = substr($item,0,$endIndex);
                $temp = trim($temp);
                if(empty($temp)){
                    break;
                }
                if(strpos($temp,"=")){
                    //赋值操作
                    $temp = explode("=",$temp);
                    $left = trim($temp[0]);
                    $this->lexicalAnalysis($funcName,$temp[1]);
                    $this->funcArea[$funcName]["byteCode"][]="tcode_assign ".$this->varPrefix.$left;
                }elseif(strpos($temp,"dump") === 0){
                    //打印操作
                    $temp = $this->str_replace_once($temp," ","|");
                    $temp = explode("|",$temp);
                    $this->lexicalAnalysis($funcName,$temp[1]);
                    $this->funcArea[$funcName]["byteCode"][]="tcode_dump";
                }elseif(strpos($temp,"go_back") === 0){
                    //返回操作
                    $temp = $this->str_replace_once($temp," ","|");
                    $temp = explode("|",$temp);
                    $this->lexicalAnalysis($funcName,$temp[1]);
                    $this->funcArea[$funcName]["byteCode"][]="tcode_return";
                }else{
                    $this->lexicalAnalysis($funcName,$temp);
                }
                $item = $this->strDel($item,0,$endIndex+1);
                $item = trim($item);
            }
        }
        $tClasss = json_encode($this->funcArea);
        //生成字节码
        file_put_contents($targetClasssName,$tClasss);
//        var_dump($this->funcArea);die;
    }
    //词法分析
    private function lexicalAnalysis($funcName,$right){
        $rightOperator = [];
        //处理右边的语法,按顺序提取运算符为数组,并将运算符替换为"|"
        while (true){
            $index = null;
            $temp = strpos($right,"+");
            if($temp && ($index === null || $temp<$index)){$index = $temp;}
            $temp = strpos($right,"-");
            if($temp && ($index === null || $temp<$index)){$index = $temp;}
            $temp = strpos($right,"*");
            if($temp && ($index === null || $temp<$index)){$index = $temp;}
            $temp = strpos($right,"/");
            if($temp && ($index === null || $temp<$index)){$index = $temp;}
            if(strpos($right,"+") === $index){
                $rightOperator[] = "+";
                $right = $this->str_replace_once($right,"+","|");
                continue;
            }
            if (strpos($right,"-") === $index){
                $rightOperator[] = "-";
                $right = $this->str_replace_once($right,"-","|");
                continue;
            }
            if (strpos($right,"*") === $index){
                $rightOperator[] = "*";
                $right = $this->str_replace_once($right,"*","|");
                continue;
            }
            if (strpos($right,"/") === $index){
                $rightOperator[] = "/";
                $right = $this->str_replace_once($right,"/","|");
                continue;
            }
            break;
        }

        $rightArray = explode("|",$right);
        //先处理乘除法
        if(count($rightOperator) > 1){
            if(in_array("*",$rightOperator) || in_array("/",$rightOperator)){
                if(in_array("+",$rightOperator) || in_array("-",$rightOperator)){
                    while (true){
                        $out = 1;
                        //先执行乘除法,结果用临时变量保存,再参与加减法运算
                        foreach ($rightOperator as $key=>$val){
                            if(in_array($val,["*","/"])){
                                $key1 = $key;
                                $key2 = $key+1;
                                $items = [trim($rightArray[$key1]),trim($rightArray[$key2])];
                                foreach ($items as $item){
                                    if(strpos($item,"$") === 0){
                                        $this->funcArea[$funcName]["byteCode"][]="tcode_push_var ".$this->varPrefix.$item;
                                    }elseif (strpos($item,"(")){
                                        $this->lexicalAnalysisFunc($item,$funcName);
                                    }else{
                                        $this->funcArea[$funcName]["byteCode"][]="tcode_push_num ".$item;
                                    }
                                }
                                switch ($val){
                                    case "*":
                                        $this->funcArea[$funcName]["byteCode"][]="tcode_mul";break;
                                    case "/":
                                        $this->funcArea[$funcName]["byteCode"][]="tcode_div";break;
                                }
                                $tempVarName = "$"."temp".$this->varNum++;
                                $this->funcArea[$funcName]["byteCode"][]="tcode_assign ".$this->varPrefix.$tempVarName;
                                unset($rightOperator[$key1]);
                                $rightOperator = array_values($rightOperator);
                                $rightArray[$key1] = $tempVarName;
                                unset($rightArray[$key2]);
                                $rightArray = array_values($rightArray);
                                $out = 0;

                                break;
                            }
                        }
                        if($out){
                            break;
                        }
                    }
                }
            }
        }
        //后处理加减法
        foreach ($rightArray as $key=>$item){
            $item = trim($item);
            if(strpos($item,"$") === 0){
                $this->funcArea[$funcName]["byteCode"][]="tcode_push_var ".$this->varPrefix.$item;
            }elseif (strpos($item,"(")){
                $this->lexicalAnalysisFunc($item,$funcName);
            }else{
                $this->funcArea[$funcName]["byteCode"][]="tcode_push_num ".$item;
            }
            if($key>0){
                switch ($rightOperator[$key-1]){
                    case "+":
                        $this->funcArea[$funcName]["byteCode"][]="tcode_add";break;
                    case "-":
                        $this->funcArea[$funcName]["byteCode"][]="tcode_sub";break;
                    case "*":
                        $this->funcArea[$funcName]["byteCode"][]="tcode_mul";break;
                    case "/":
                        $this->funcArea[$funcName]["byteCode"][]="tcode_div";break;
                }
            }
        }
        return true;
    }
    //函数调用分析
    private function lexicalAnalysisFunc($temp,$funcName){
        $leftSIndex = strpos($temp,"(");
        $rightSIndex = strpos($temp,")");
        $funcNameTo = substr($temp,0,$leftSIndex);
        $funcNameTo = trim($funcNameTo);
        $paramStr = substr($temp,$leftSIndex+1,$rightSIndex-$leftSIndex-1);
        //参数入栈
        $paramArray = !empty($paramStr)?explode(",",$paramStr):[];
        foreach ($paramArray as $val){
            $val = trim($val);
            if(strpos($val,"$") === 0){
                $this->funcArea[$funcName]["byteCode"][]="tcode_push_var ".$this->varPrefix.$val;
            }else{
                $this->funcArea[$funcName]["byteCode"][]="tcode_push_num ".$val;
            }
        }
        $this->funcArea[$funcName]["byteCode"][]="tcode_goin ".$funcNameTo."|".count($paramArray);
    }
    /**
     * 编译源代码并解释执行
     */
    public function run(){
        $this->explainer();
        $this->executeEngineRun();
    }
    /**
     * 执行引擎
     */
    private function executeEngineRun(){
        //当前代码段
        $codeSegment=[];
        //当前栈帧
        $stackFrame=[];
        while (true){
            if(!empty($stackFrame)){
                if(count($codeSegment) <= $stackFrame["counter"]){
                    break;
                }
                $byteCode = $codeSegment[$stackFrame["counter"]];
                $stackFrame["counter"]++;
            }else{
                $byteCode = "tcode_goin main|0";
            }
            //分割字节码
            $code = explode(" ",$byteCode);
            //指令
            $instruct = $code[0];
            //操作数
            $operand = isset($code[1])?$code[1]:null;

            //执行指令
            switch ($instruct){
                //操作数入栈
                case "tcode_push_num":
                    array_push($stackFrame["num_stack"],$operand);
                    break;
                //局部变量入栈
                case "tcode_push_var":
                    array_push($stackFrame["num_stack"],$stackFrame["var_table"][$operand]);
                    break;
                //局部变量赋值
                case "tcode_assign":
                    $temp1 =array_pop($stackFrame["num_stack"]);
                    $stackFrame["var_table"][$operand] = $temp1;
                    break;
                //操作数相加
                case "tcode_add":
                    $temp1 =array_pop($stackFrame["num_stack"]);
                    $temp2 =array_pop($stackFrame["num_stack"]);
                    array_push($stackFrame["num_stack"],$temp1+$temp2);
                    break;
                //操作数相减
                case "tcode_sub":
                    $temp1 =array_pop($stackFrame["num_stack"]);
                    $temp2 =array_pop($stackFrame["num_stack"]);
                    array_push($stackFrame["num_stack"],$temp2-$temp1);
                    break;
                //操作数相乘
                case "tcode_mul":
                    $temp1 =array_pop($stackFrame["num_stack"]);
                    $temp2 =array_pop($stackFrame["num_stack"]);
                    array_push($stackFrame["num_stack"],$temp1*$temp2);
                    break;
                //操作数相除
                case "tcode_div":
                    $temp1 =array_pop($stackFrame["num_stack"]);
                    $temp2 =array_pop($stackFrame["num_stack"]);
                    array_push($stackFrame["num_stack"],$temp1/$temp2);
                    break;
                //打印输出
                case "tcode_dump":
                    $temp1 =array_pop($stackFrame["num_stack"]);
                    array_push($stackFrame["num_stack"],$temp1);
                    echo $temp1;
                    break;
                //函数调用
                case "tcode_goin":
                    //参数
                    $operandArray = explode("|",$operand);
                    $operand = $operandArray[0];
                    $paramNum = $operandArray[1];
                    $param = [];
                    while ($paramNum){
                        $temp =array_pop($stackFrame["num_stack"]);
                        array_unshift($param,$temp);
                        $paramNum--;
                    }
                    //方法入栈
                    array_push($this->stackArea,$stackFrame);
                    //当前代码段
                    $codeSegment = $this->funcArea[$operand]["byteCode"];
//                    var_dump($codeSegment);die;
                    //创建栈帧
                    $temp = [
                        //当前方法名称
                        "funcName"=>$operand,
                        //局部变量表
                        "var_table"=>[],
                        //操作数栈
                        "num_stack"=>[],
                        //方法返回地址
                        "return_addr"=>!empty($stackFrame)?$stackFrame["funcName"]:null,
                        //程序计数器
                        "counter"=>0
                    ];
                    //参数入局部变量表
                    foreach ($this->funcArea[$operand]["paramStr"] as $key=>$val){
                        $temp["var_table"][$this->varPrefix.$val] = $param[$key];
                    }
                    $stackFrame = $temp;
                    break;
                //函数返回
                case "tcode_return":
                    $temp1 =array_pop($stackFrame["num_stack"]);
                    //方法出栈
                    $stackFrame = array_pop($this->stackArea);
                    array_push($stackFrame["num_stack"],$temp1);
                    //当前代码段
                    $codeSegment = $this->funcArea[$stackFrame["funcName"]]["byteCode"];
                    break;
            }

        }

    }
    //删除字符串指定部分
    private function strDel($str,$start,$end){
        $res1 = substr($str,0,$start);
        $res2 = substr($str,$end);
        return $res1.$res2;
    }
    //字符串替换一次
    private  function str_replace_once($str, $needle, $replace) {
        $pos = strpos($str, $needle);
        if ($pos === false) {
            return $str;
        }
        return substr_replace($str, $replace, $pos, strlen($needle));
    }
}
$tvm = new  Tvm();
$tvm->run();