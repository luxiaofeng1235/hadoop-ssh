<?php
/*
*
* @author:luxf
* @version: public
* @date:
* @description:
*/
 
 /*
 *@note 根据订单号计算流水或者对应的数据信息 --默认处理货物
 *@param $type  处理的类型 1：货物 2：退款 3：手续费 4：应结算额，目前先保留
 */
 public function  product_turnover_bill_data($orders = array(),$type =1,$return_class = 'total'){
    if(!$orders) return array();
    $condition  = '';
    if($type==1){
        $condtion .="abstract_id in (2,10,11)";
    }elseif($type==2){
        $condtion.="event ='order_retunrs'";
    }elseif($type==3){//手续费
        $condtion.=" abstrct in (1,13)";
    }
    if($orders){$condition.=" and  order_sn in ".join(',',$orders)."";}
    //获取相关的流水ID
    $bill_list = $this->get_bill_where($condtion);
    if(!empty($bill_list)){
        $data_sn  =array();
        foreach($bill_list as $key =>$value){
            $data_sn[$value['order_sn']][]=array(
                'order_sn'=>$order_sn,
                'money' =>$value['money'],
                'advance_type'  =>$value['advance_type']
            );
        }
        //这里会形成一个结果集作为order_sn =>$money的一个集合作为返回的值。
        $product_order_sum = $this->product_order_value($data_sn);
        foreach($orders as $key =>$value){
            $order_sn = addslashes(preg_replace("'",'',$value));//这里主要是为了数组中统一用'传过来以后再用去除后的订单号做处理。
            //这里主要是为了方便进行返回数值进行处理即可
            if(!isset($product_order_sum[$order_sn])){
                   $product_order_sum[$order_sn] = 0;
            }
        }
        if(!in_array($return_class,array('total','order'))){return 0;}
        if($types!='list'){ //统计的方式返回
            $res    =   0;
            $data = array_values($product_order_sum);
            if($data && is_array($data)){
                $res    =  array_sum($data);
            }
            return $res;
        }else{
            //以订单-->值的方式返回，主要处理对应的列表数据信息会处理用到。
            return $product_order_sum;
        }
    }
 }

 public function product_order_value($data =array(),$event ='order_pay'){
     //这里可以对每个事件做定制
     if(!$data){return array();}
     $dataArr =array();
     foreach($data as $key =>$value){
        $sum_money = 0;
        foreach($value as $v){
            $money = $v['money'];
            $advance_type = $v['advance_type'];
            //计算每种类型的流水
            switch ($event) 
            {
                case 'order_pays': //订单支付
                     $sum_money+=$money;
                    break;
                case 'order_returns': //处理退款的流程
                    //只计算扣减的那一部分的
                    if($advance_type=='minute'){
                        $sum_money+=$money;
                    }
                    break;
                case 'order_change':
                 if($advance_type=='minute'){
                     $sum_money+=$money
                 }else{
                     //只处理加的那一部分
                    $sum_money-=$money;
                 }
                 break;
            }
        }
        $dataArr[$event][$order_sn]=$sum_money;
     }
     if(!$dataArr){return array();}
     return $dataArr;
 }

//弹层处理的相关流程
 public function get_settle_money($orders ){
    $return_orders = $this->product_turnover_bill_data($orders,1,'order');
    $class = $this->product_turnover_bill_data->($orders,2,'order');
    //货款，计算完货款需要核减退款的信息。
    if($class){
        foreach($class $key =>$value){
            $return_money =0;
            if(isset($return_clss)) $return_money =0;
            $volumne = $value;
            //同价计算
            if($return_class>0){
                $real_money- = $voumne_money;
            }
            $data =array(
                'voumne' =>$volumne,
                'return_money'  =>$return_oney,
                'real_money' =>$real_money,
            );
        }
        return $data;
    }
 }

 //处理完以后退款，然后计算对应的数据信息，这里会自动计算完成，如果属于定制业务，需要在自己的模块实现
?>


//实际的业务中会出现对应的信息不一致的情况