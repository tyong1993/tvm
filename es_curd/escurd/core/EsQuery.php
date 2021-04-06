<?php

namespace escurd\core;
class EsQuery
{
    private $body=[];
    const QUERY_TYPE_MUST = "must";
    const QUERY_TYPE_MUST_NOT = "must_not";
    const QUERY_TYPE_SHOULD = "should";
    const QUERY_TYPE_FILTER = "filter";

    /**
     * @param $field
     * @param $operator
     * @param $value
     * @param string $type
     * @param null $accurate
     * $accurate:查询精确度
     * $operator:like,has,eq,in,inf,is_null,is_not_null,gt,lt
     * return EsBaseModel
     */
    public function where($field,$operator,$value=null,$type = EsQuery::QUERY_TYPE_MUST,$accurate = null){
        if(empty($type)){
            $type = EsQuery::QUERY_TYPE_MUST;
        }
        //ES废除了missing查询关键字,用MUST_NOT来实现
        if($operator == "is_null"){
            $type = EsQuery::QUERY_TYPE_MUST_NOT;
        }
        $clause = $this->getClause($field,$operator,$value,$accurate);
        $this->body["query"]["bool"][$type][]=$clause;
        return $this;
    }

    public function order($order=""){
        if(!empty($order)){
            $orderArr = explode(" ",$order);
            if(count($orderArr) != 2){
                throw new Exception("error order clause");
            }
            $this->body["sort"][]=[
                $orderArr[0]=>[
                    "order"=>$orderArr[1]
                ]
            ];
        }
        return $this;
    }

    public function page($page=1,$limit=10){
        $this->body["from"] = ($page-1)*$limit;
        $this->body["size"] = $limit;
        return $this;
    }

    public function field($field){
        if(!empty($field)){
            $this->body["_source"]=explode(",",$field);
        }
        return $this;
    }

    public function highLight($fields=[],$pre_tags="<span style='color: red;'>",$post_tags="</span>"){
        if(!empty($fields)){
            foreach ($fields as $field){
                $temp[$field] = new \stdClass();
            }
            $this->body["highlight"] = [
                "pre_tags"=>$pre_tags,
                "post_tags"=>$post_tags,
                "fields"=>$temp
            ];
        }
        return $this;
    }

    public function getBody(){
        return $this->body;
    }

    /**
     * @param $operator
     * @return string
     * @throws Exception
     * 获取查询子句
     */
    private function getClause($field,$operator,$value,$accurate){
        $esOperator = $this->getOperator($operator);
        //分词查询与完整包含关键词
        if(in_array($esOperator,["match","match_phrase"])){
            $clause =[
                $esOperator=>[
                    $field=>[
                        "query"=>$value
                    ]
                ]
            ];
            if($accurate !== null){
                $clause[$esOperator][$field]["minimum_should_match"] = $accurate;
            }
            return $clause;
        }
        //精确值查询
        if(in_array($esOperator,["term","terms"])){
            if($esOperator == "terms" && !is_array($value)){
                throw new \Exception("in查询 the value param must be array");
            }
            $clause =[
                $esOperator=>[
                    $field=>$value
                ]
            ];
            return $clause;
        }
        //多字段查询
        if(in_array($esOperator,["multi_match"])){
            if($esOperator == "multi_match" && !is_array($field)){
                throw new \Exception("inf查询 the field param must be array");
            }
            $clause =[
                $esOperator=>[
                    "query"=>$value,
                    "fields"=>$field
                ]
            ];
            return $clause;
        }
        //missing,exists
        if(in_array($esOperator,["missing","exists"])){
            $clause =[
                "exists"=>[
                    "field"=>$field
                ]
            ];
            return $clause;
        }
        //范围查询
        if(in_array($esOperator,["gt","lt"])){
            $clause =[
                "range"=>[
                    $field=>[
                        $esOperator=>$value
                    ]
                ]
            ];
            return $clause;
        }
    }

    /**
     * @param $operator
     * @return string
     * @throws Exception
     * 解析操作符
     */
    private function getOperator($operator){
        switch ($operator){
            case "like":return "match";//分词查询
            case "has":return "match_phrase";//完整包含关键词
            case "eq":return "term";//精确值查询
            case "in":return "terms";
            case "inf":return "multi_match";
            case "is_null":return "missing";
            case "is_not_null":return "exists";
            case "gt":return "gt";
            case "lt":return "lt";
            default:throw new \Exception("unknown operator");
        }
    }

}