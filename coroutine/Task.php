<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021/4/6
 * Time: 18:26
 */

class Task
{
    public $task_id = null;
    public $runner = null;

    public function __construct($task_id)
    {
        $this->runner = $this->codes();
        $this->task_id = $task_id;
    }
    public function codes(){
        yield;
        echo "协程".$this->task_id."第一段代码执行完毕".PHP_EOL;
        $arg = yield;
        echo "协程".$this->task_id."第二段代码执行完毕".PHP_EOL;
        $arg = yield;
        echo "协程".$this->task_id."第三段代码执行完毕".PHP_EOL;
        $arg = yield "die";
    }
}