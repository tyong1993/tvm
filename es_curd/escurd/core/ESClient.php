<?php

namespace escurd\core;

use Elasticsearch\ClientBuilder;
class ESClient
{
    private static $instance = null;

    public static function getInstance(){
        require_once 'libs/libraries/vendor/autoload.php';
        $hosts = [
            ES_HOST.':'.ES_PORT,
        ];
        if(self::$instance === null){
            $client = ClientBuilder::create()->setHosts($hosts)->build();
        }else{
            $client = self::$instance;
        }
        return $client;
    }

    /**
     * @return array
     * 创建索引示例
     */
    public static function createIndex(){
        $params = [
            'index' => 'test_index',
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 1,
                    "index.analysis.analyzer.default.type"=> "ik_max_word"
                ],
                'mappings' => [
                    'default' => [
                        '_source' => [
                            'enabled' => true
                        ],
                        'properties' => [
                            'id' => [
                                'type' => 'integer',
                            ],
                            'tag' =>[
                                'type' => 'text',
                                "fields"=> [//允许给一个字段设置多个类型
                                    "raw"=> [
                                        "type"=> "keyword",
                                        "ignore_above"=>30//限制插入数据长度,超出不报错但是不存储
                                    ]
                                ]
                            ],
                            'title' => [
                                'type' => 'text',
                                'boost' => 1 //设置相关性权重,默认为1
                            ],
                            'desc' => [
                                'type' => 'text',
                            ],
                            'content' => [
                                'type' => 'text',
                                'index' => false
                            ],
                        ]
                    ]
                ]
            ]
        ];
        return self::getInstance()->indices()->create($params);
    }

    /**
     * 清空索引
     */
    public static function clearIndexs(){
        self::getInstance()->indices()->delete(["index"=>"_all"]);
    }

}