<?php

namespace escurd\core;

/**
 * Class EsBaseModel
 * @mixin EsQuery
 */
abstract class EsBaseModel {
    //模型实例
    private static $instance = null;
    //PHPES客户端
    protected static $esClient = null;
    //ES查询对象
    private static $esQuery = null;
    //模型映射ES索引
    protected static $index = null;
    //模型映射ES类型
    protected static $type = "default";
    //DSL语句调试
    private static $fetchSql = false;


    /**
     * EsBaseModel constructor.
     * @param null $index
     */
    private function __construct()
    {
        self::$esClient = ESClient::getInstance();
        self::$instance = $this;
    }

    public static function getInstance(){
        if(self::$instance === null){
            self::$instance = new static();
        }
        return self::$instance;
    }

    private static function getEsClient(){
        return self::$esClient!==null?self::$esClient:ESClient::getInstance();
    }

    public static function insert($doc){
        $params = [
            'index' => static::$index,
            'type' => static::$type,
            'body' => $doc
        ];
        if(self::$fetchSql){
            return json_encode($params,true);
        }
        $res = self::getEsClient()->index($params);
        return isset($res["_id"])?$res["_id"]:null;
    }
    public static function delete($_id){
        $params = [
            'index' => static::$index,
            'type' => static::$type,
            'id' => $_id
        ];
        if(self::$fetchSql){
            return json_encode($params,true);
        }
        $res = self::getEsClient()->delete($params);
        return isset($res["_id"])?$res["_id"]:null;
    }
    public static function update($_id,$doc){
        $params = [
            'index' => static::$index,
            'type' => static::$type,
            'id' => $_id,
            'body'=>$doc
        ];
        if(self::$fetchSql){
            return json_encode($params,true);
        }
        $res = self::getEsClient()->index($params);
        return isset($res["_id"])?$res["_id"]:null;
    }
    public static function select(){
        $body =  static::getQuery()->getBody();
        if (empty($body["query"])){
            $body["query"] = [
                "match_all"=>new \stdClass()
            ];
        }
        $params = [
            'index' => static::$index,
            'type' => static::$type,
            'body' => $body
        ];
        if(self::$fetchSql){
            return json_encode($params,true);
        }
        $res = self::getEsClient()->search($params);
        self::$esQuery = null;
        return !empty($res["hits"]["hits"])?$res["hits"]["hits"]:[];
    }
    public static function find(){
        $body =  static::getQuery()->getBody();
        if (empty($body)){
            $body = [
                "query"=>[
                    "match_all"=>new \stdClass()
                ]
            ];
        }
        $body["from"] = 0;
        $body["size"] = 1;
        $params = [
            'index' => static::$index,
            'type' => static::$type,
            'body' => $body
        ];
        if(self::$fetchSql){
            return json_encode($params,true);
        }
        $res = self::getEsClient()->search($params);
        self::$esQuery = null;
        return !empty($res["hits"]["hits"][0])?$res["hits"]["hits"][0]:null;
    }

    public static function count(){
        $body =  static::getQuery()->getBody();
        if (empty($body)){
            $body = [
                "query"=>[
                    "match_all"=>new \stdClass()
                ]
            ];
        }
        $params = [
            'index' => static::$index,
            'type' => static::$type,
            'body' => $body
        ];
        $res = self::getEsClient()->count($params);
        return !empty($res["count"])?$res["count"]:0;
    }

    public static function fetchSql($bool=true){
        self::$fetchSql = $bool;
        if(self::$instance === null){
            self::$instance = new static();
        }
        return self::$instance;
    }
    protected static function getQuery(){
        if(self::$esQuery === null){
            self::$esQuery = new EsQuery();
        }
        return self::$esQuery;
    }

    public function __call($method, $args)
    {
        call_user_func_array([self::getQuery(), $method], $args);
        return self::getInstance();
    }
    public static function __callStatic($method, $args)
    {
        call_user_func_array([self::getQuery(), $method], $args);
        return self::getInstance();
    }
}