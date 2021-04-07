<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/4/6
 * Time: 18:26
 */

class Main
{
    public static $tasks = [];
    public static $taskUniqueId = 1;

    public static function start(){
        while (true){
            if(!empty(self::$tasks)){
                foreach (self::$tasks as $task){
                    $task->runner->next();
                    $res = $task->runner->current();
                    if($res == "die"){
                        unset(self::$tasks[$task->task_id]);
                    }
                }
            }else{
                echo "暂无任务可执行".PHP_EOL;
                sleep(3);
                Main::addTask(2);
                echo "有两个新的任务到来了,三秒后开始执行...".PHP_EOL;
                sleep(3);
            }
        }
    }

    public static function addTask($num){
        for ($i=0;$i<$num;$i++){
            self::$tasks[self::$taskUniqueId]=new Task(self::$taskUniqueId);
            self::$taskUniqueId++;
        }
    }

}
include_once "Task.php";
Main::addTask(3);
Main::start();
