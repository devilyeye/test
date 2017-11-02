<?php
/* v3 发货响应
 * 20170303
 * created by wangjianjun
 */
class b2c_apiv_apis_response_order_logistics{

    public $app;

    public function __construct($app){
        $this->app = $app;
    }

    //发货单创建及发货流程是和中心的交互
    public function send(&$sdf, $thisObj){
        if (empty($sdf)){
            $thisObj->send_user_error(app::get('b2c')->_('发货单没有收到！'));
        }else{
            //开启数据库事务
            $db = kernel::database();
            $transaction_status = $db->beginTransaction();
            
            $mdl_delivery = $this->app->model('delivery');
            $mdl_delivery_items = $this->app->model('delivery_items');
            $mdl_orders = $this->app->model('orders');
            $mdl_dlycorp = $this->app->model('dlycorp');
            $mdl_order_item = $this->app->model('order_items');
            $mdl_order_delivery = $this->app->model('order_delivery');
            $mdl_order_obj = $this->app->model('order_objects');
            $mdl_dlytype = $this->app->model('dlytype');
            
            $arr_dlycorp = $mdl_dlycorp->dump(array('corp_code' => $sdf['company_code'])); //获取相关快递信息
            $delivery_id = $mdl_delivery->gen_id(); //生成delivery_id
            $arr_order_objs = $mdl_order_obj->getList('*',array('order_id'=>$sdf['order_bn'])); //获取订单object数据
            $arr_order_items = $mdl_order_item->getList('*',array('order_id'=>$sdf['order_bn'])); //获取订单item数据
            $orders_detail = $mdl_orders->getList("*",array('order_id'=>$sdf['order_bn'])); //获取订单主信息
            $arr_dlytype = $mdl_dlytype->dump(array('dt_name' => $orders_detail[0]['shipping'])); //获取配送方式
            //是否保价
            $is_protect = 'false';
            if($orders_detail[0]['is_protect'] === 'true'){
                $is_protect = 'true';
            }
            $arr_data = array(
                'delivery_id' => $delivery_id,
                'order_id' => $sdf['order_bn'],
                'is_protect' => $is_protect,
                'delivery' => $arr_dlytype['dt_id'] ? $arr_dlytype['dt_id'] : 0, 
                'logi_id' => $arr_dlycorp['corp_id'] ? $arr_dlycorp['corp_id'] : 0,
                'logi_no' => $sdf['logistics_no'],
                'logi_name' => $sdf['company_name'],
                'ship_name' => $orders_detail[0]['ship_name'],
                'ship_area' => $orders_detail[0]['ship_area'],
                'ship_addr' => $orders_detail[0]['ship_addr'],
                'ship_zip' => $orders_detail[0]['ship_zip'],
                'ship_tel' => $orders_detail[0]['ship_tel'],
                'ship_mobile' => $orders_detail[0]['ship_mobile'],
                'ship_email' => $orders_detail[0]['ship_email'],
                'memo' => $orders_detail[0]['memo'],
                't_begin' => time(),
                'member_id'=>$orders_detail[0]['member_id'],
                'op_name'=>"admin" //接口响应默认就是admin
            );
            
            if (empty($arr_order_objs) || empty($arr_order_items)){
                $db->rollback();
                $thisObj->send_user_error(app::get('b2c')->_('订单的明细不存在！'), array('tid' => $sdf['order_bn']));
            }
            
//             $order_delivery_item = array(); //订单和发货单关系表保存用
            if ($sdf['is_split']){
                //拆单
                //获取订单相关的未发货的items
                $spe_odr_item_filter = array(
                    'order_id'=>$sdf['order_bn'],
                    'filter_sql'=>"nums > sendnum",
                );
                $arr_order_items = $mdl_order_item->getList('*',$spe_odr_item_filter);
                if (empty($arr_order_items)){
                    $db->rollback();
                    $thisObj->send_user_error(app::get('b2c')->_('没有需要发货的商品明细！'), array('delivery_id' => $delivery_id, 'tid' => $sdf['order_bn']));
                }
                if(!$sdf['oid_list']){
                    $db->rollback();
                    $thisObj->send_user_error(app::get('b2c')->_('子订单数据缺失！'), array('delivery_id' => $delivery_id, 'tid' => $sdf['order_bn']));
                }
                $arr_items = json_decode($sdf['oid_list']);
                if($arr_items){
                    foreach ($arr_items as $jsonarr){
                          $jsonarr = (array)$jsonarr;
                          $iteminfo = $mdl_order_item->getList('*',array('obj_id'=>$jsonarr['oid']));
                          $items = array(
                              'delivery_id' => $delivery_id,
                              'order_item_id' => $iteminfo[0]['item_id'],
                              'item_type' => $iteminfo[0]['item_type'] == 'product' ? 'goods' : $iteminfo['item_type'],
                              'product_id' => $iteminfo[0]['product_id'],
                              'product_bn' => $iteminfo[0]['bn'],
                              'product_name' => $iteminfo[0]['name'],
                              'number' => $jsonarr['nums'],
                          );
//                           $order_delivery_item[] = $items;
                          //发货单明细表保存
                          $mdl_delivery_items->save($items);
                    }
                }
            }else{
                //全单 或是 合单（logistics_no一样 order_id不同）
                $rs_delivery = $mdl_delivery->dump(array('order_id'=>$sdf['order_bn'],'logi_no'=>$sdf['logistics_no']));
                if(!empty($rs_delivery)){
                    $db->rollback();
                    $thisObj->send_user_error(app::get('b2c')->_('发货单已经存在！'), array('tid'=>$sdf['order_bn'],'logi_no'=>$sdf['logistics_no']));
                }
                //发货单明细表保存
                foreach ($arr_order_items as $info){
                    $items = array(
                        'delivery_id' => $delivery_id,
                        'order_item_id' => $info['item_id'],
                        'item_type' => $info['item_type'] == 'product' ? 'goods' : $info['item_type'],
                        'product_id' => $info['product_id'],
                        'product_bn' => $info['bn'],
                        'product_name' => $info['name'],
                        'number' => $info['nums'],
                    );
//                     $order_delivery_item[] = $items;
                    $mdl_delivery_items->save($items);//发货单明细表保存
                }
            }
            //之后 全单 拆单 合单 做相同处理 
            $arr_data['status'] = 'ready';
            $arr_data['money'] = $sdf["delivery_cost_actual"]; //物流费用
            $mdl_delivery->save($arr_data);//发货单主表保存
            //订单和发货单关系表保存
//             $order_delivery_data = array('order_id'=>$sdf['order_bn'],'dly_id'=>$delivery_id,'dlytype'=>'delivery','items'=>($order_delivery_item));
            $order_delivery_data = array('order_id'=>$sdf['order_bn'],'dly_id'=>$delivery_id,'dlytype'=>'delivery');
            $mdl_order_delivery->save($order_delivery_data);
            //发货单更新操作
            $update_result = $this->delivery($orders_detail[0], $delivery_id,$arr_dlycorp,$sdf);
            if ($update_result["result"]){
                $db->commit();
                return array('tid'=>$sdf['order_bn'],"delivery_id"=>$delivery_id);
            }else{
                $db->rollback();
                $thisObj->send_user_error($update_result["msg"],$update_result["arr"]);
            }
        }
    }
    
    /*
     * 发货单更新操作
     * $order_detail 订单明细
     * $delivery_id 主发货单主键
     * $arr_dlycorp 物流信息
     */
    private function delivery($order_detail,$delivery_id,$arr_dlycorp){
        $mdl_delivery = $this->app->model('delivery');
        $arr_data = $mdl_delivery->dump(array('delivery_id' => $delivery_id, 'order_id' => $order_detail['order_id']));
        if (!empty($arr_data)){
            $mdl_orders = $this->app->model('orders');
            $mdl_order_items = $this->app->model('order_items');
            $mdl_delivery_item = $this->app->model('delivery_items');
            $mdl_odr_object = $this->app->model('order_objects');
            $mdl_products = $this->app->model('products');
            $mdl_goods = $this->app->model('goods');
            $objMath = kernel::single('ectools_math');
            //原发货单状态为ready 执行发货
            if($arr_data["status"] == "ready"){
                $arr_delivery_items = $mdl_delivery_item->getList('*',array('delivery_id'=>$arr_data['delivery_id']));
                if (!empty($arr_delivery_items)){
                    //获取发货的库存处理方法 是否需要要冻结还是解冻库存
                    $obj_checkorder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
                    $arrStatus = $obj_checkorder->checkOrderFreez('delivery', $order_detail['order_id']);
                    $arr_extends_objs = array();
                    foreach ($arr_delivery_items as $arr_item){
                        //单个item逐一循环处理 更新发货量 库存处理
                        $is_update_store = false;
                        $tmp = $mdl_order_items->getList('*', array('order_id'=>$order_detail['order_id'],'product_id'=>$arr_item['product_id'],'item_id'=>$arr_item['order_item_id']));
                        if (!$tmp){
                            return $this->get_return_error_arr("1", array("delivery_id"=>$delivery_id,"order_id"=>$order_detail['order_id']), "需要发货的商品不在订单的明细中！");
                        }
                        $update_data = array("sendnum"=>$objMath->number_plus(array($tmp[0]['sendnum'], $arr_item['number'])));
                        $tmp_odr_obj = $mdl_odr_object->getList('*',array('obj_id'=>$tmp[0]['obj_id']));
                        if (empty($tmp_odr_obj)){
                            return $this->get_return_error_arr("1", array("delivery_id"=>$delivery_id,"order_id"=>$order_detail['order_id']), "需要发货的商品所在的子订单不存在！");
                        }
                        //处理其他类型子订单的发货
                        if ($tmp_odr_obj[0]['obj_type'] != 'goods' && $tmp_odr_obj[0]['obj_type'] != 'gift'){
                            if (!$arr_extends_objs[$tmp[0]['obj_id']]){
                                $arr_extends_objs[$tmp[0]['obj_id']] = array('obj_type'=>$tmp_odr_obj[0]['obj_type']);
                            }
                            if ($update_data['sendnum'] == $tmp[0]['nums']){
                                $arr_extends_objs[$tmp[0]['obj_id']]['is_finish'] = true;//当前item发货全
                            }else{
                                $arr_extends_objs[$tmp[0]['obj_id']]['is_finish'] = false;//当前item发货不全
                            }
                        }
                        if ($tmp[0]['nums'] >= $update_data['sendnum']){
                            $is_update_store = true;
                        }else{
                            $is_update_store = false;
                        }
                        if (!$is_update_store){
                            continue;
                        }
                        $update_data['item_id'] = $tmp[0]['item_id'];
                        if (!$mdl_order_items->save($update_data)){//更新items的sendnum
                            return $this->get_return_error_arr("1", array("delivery_id"=>$delivery_id,"order_id"=>$order_detail['order_id']), "订单明细发货数量保存失败！");
                        }
                        //当前货品信息 （这里用测试脚本/script/test/apiResponseOrderLogistics.php跑的话查看$mdl_products dump 方法的注释 $this->site_member_lv_ids）
                        $tmp_p = $mdl_products->dump($arr_item['product_id'],'*'); 
                        //是否需要减实际库存 （是否开启无库存销售） 
                        $tmp_g = $mdl_goods->getList('*',array('goods_id'=>$tmp_p['goods_id']));
                        if($tmp_g[0]['nostore_sell']){
                            continue;
                        }
                        if ($arrStatus['store']){
                            //需要减实际库存
                            if (is_null($tmp_p['store']) || $tmp_p['store'] == '' || intval($tmp_p['store']) == 0){
                                return $this->get_return_error_arr("2", array("delivery_id"=>$delivery_id,"o_items"=>$tmp[0],"d_items"=>$arr_item), "需要发货的货品库存不足！");
                            }
                            $update_data_p = array("product_id"=>$tmp_p['product_id']);
                            //扣减当前货品实际库存操作
                            $update_data_p['store'] = $objMath->number_minus(array($tmp_p['store'], $arr_item['number']));
                            $is_updated_store = $mdl_products->save($update_data_p);
                            if ($arrStatus['unfreez']){//需要释放冻结的 当前货品操作
                                $update_data_p['freez'] = $objMath->number_minus(array($tmp_p['freez'], $arr_item['number']));
                                $is_updated_unfreez = $mdl_products->save($update_data_p);
                            }
                            if (!$is_updated_store || !$is_updated_unfreez){
                                $temp_store_msg = "货品库存裁剪出错！";
                                if (!$is_updated_unfreez){
                                    $temp_store_msg = "货品库存冻结释放出错！";
                                }
                                return $this->get_return_error_arr("2", array("delivery_id"=>$delivery_id,"o_items"=>$tmp[0],"d_items"=>$arr_item), $temp_store_msg);
                            }
                            //对应的商品信息
                            $arr_goods = $tmp_g[0];
                            if (is_null($arr_goods['store']) || $arr_goods['store'] == '' || intval($arr_goods['store']) == 0){
                                return $this->get_return_error_arr("2", array("delivery_id"=>$delivery_id,"o_items"=>$tmp[0],"d_items"=>$arr_item), "需要发货的商品品库存不足！");
                            }
                            //扣减商品实际库存
                            $update_data_g = array('store' => $objMath->number_minus(array($arr_goods['store'], $arr_item['number'])));
                            $is_updated_g = $mdl_goods->update($update_data_g, array('goods_id'=>$tmp_p['goods_id']));
                            if (!$is_updated_g){
                                return $this->get_return_error_arr("2", array("delivery_id"=>$delivery_id,"o_items"=>$tmp[0],"d_items"=>$arr_item), "商品库存裁剪出错！");
                            }
                        }
                    }
                    // 处理其他对象类别的发货处理
                    if (!empty($arr_extends_objs)){
                        $arr_extends_objects = array();
                        foreach( kernel::servicelist('b2c.order_store_extends') as $object ) {
                            if (!$object->get_goods_type()){
                                continue;
                            }
                            $arr_extends_objects[$object->get_goods_type()] = array(
                                'obj_type'=>$object->get_goods_type(),
                                'obj'=>$object,
                            );
                        }
                        foreach ($arr_extends_objs as $key=>$arr_extends_item){
                            if ($arr_extends_item['is_finish'] && $arr_extends_objects[$arr_extends_item['obj_type']] && $arr_extends_objects[$arr_extends_item['obj_type']]['obj']){
                                $subsdf = array('order_items'=>array('*',array(':products'=>'*')));
                                $v = $mdl_odr_object->dump($key,'*',$subsdf);
                                $arr_extends_objects[$arr_extends_item['obj_type']]['obj']->store_change($v, 'delivery', 'delivery_finish');
                            }
                        }
                    }
                }else{
                    return array("result"=>false,"msg"=>"发货单明细不存在！");
                }
                //更新发货单状态为succ
                $is_updated_delivery = $mdl_delivery->update(array("status"=>"succ"),array("delivery_id"=>$delivery_id));
                if (!$is_updated_delivery){
                    return $this->get_return_error_arr("1", array("delivery_id"=>$delivery_id,"order_id"=>$order_detail['order_id']), "修改发货单失败！");
                }
                
                /** 获取默认不是部分发货 **/
                $is_part_delivery = false; 
                //获取订单的总的items
                $arr_order_items = $mdl_order_items->getList('*', array('order_id' => $order_detail['order_id']));
                foreach ($arr_order_items as $arr_item){
                    if ($arr_item['sendnum'] != $arr_item['nums']){
                        //当前的已发数量和该发数量不同
                        if ($arr_item['sendnum'] > $arr_item['nums']){ //理论上不会进入这个判断
                            return $this->get_return_error_arr("1", array("delivery_id"=>$delivery_id,"order_id"=>$order_detail['order_id']), "已发货数量将大于需发货的数量！");
                        }
                        $is_part_delivery = true;
                        //部分发货
                        if ($arr_item['sendnum'] > 0){
                            $arr_delivery_items_send[] = array(
                                'number' => $arr_item['sendnum'],
                                'name' => $arr_item['name'],
                            );
                        }
                    }else{
                        //当前的已发数量和该发数量相同 全部发货
                        if ($arr_item['sendnum'] > 0){
                            $arr_delivery_items_send[] = array(
                                'number' => $arr_item['sendnum'], //此时sendnum和nums相同
                                'name' => $arr_item['name'],
                            );
                        }
                    }
                }
                
                /** 更新订单发货状态 **/
                $ship_status = '1';
                if ($is_part_delivery){
                    $ship_status = '2';
                }
                $aUpdate = array(
                    "order_id" => $order_detail['order_id'],
                    "ship_status" => $ship_status,
                );
                $orders = $mdl_orders->getList('order_id,member_id,score_g,pay_status,ship_status',array('order_id'=>$aUpdate['order_id']));
                //当前订单全单发货 本身不是已退货 或者 部分或的情况下 存销售日志b2c_sell_logs
                if($aUpdate['ship_status'] == 1 && $orders[0]['ship_status'] != 4 && $orders[0]['ship_status'] != 3){
                    $mdl_orders->addSellLog($aUpdate);
                }
                //订单积分结算埋点 $policy_stage为2是支付发货 当前订单的发货状态不是已发货且之后需更新是已发货  （如果是拆单的情况下：完全发货后才结算积分）
                $policy_stage = app::get('b2c')->getConf('site.get_policy.stage');
                if($policy_stage == 2 && $orders[0]['pay_status'] == 1 && $orders[0]['ship_status'] != 1 && $aUpdate['ship_status'] == 1){
                    $obj_add_point = kernel::service('b2c_member_point_add');
                    $obj_add_point->change_point($orders[0]['member_id'], intval($orders[0]['score_g']), $msg, 'order_pay_get', 2, $orders[0]['order_id'], '0', 'pay');
                
                }//订单积分处理结束
                //优惠券处理
                $obj_coupon = kernel::single("b2c_coupon_order");
                if($obj_coupon){
                    $obj_coupon->use_c($arr_data['order_id']);
                }
                //更新订单发货状态操作
                $is_updated = $mdl_orders->save($aUpdate);
                if (!$is_updated){
                    return $this->get_return_error_arr("1", array("delivery_id"=>$delivery_id,"order_id"=>$order_detail['order_id']), "订单发货状态修改失败！");
                }
                
                /** 生成订单日志 **/
                $mdl_order_log = $this->app->model('order_log');
                //这里会涉及拆单发货 只做增加不做更新操作 （拆单的情况下 一个订单的发货日志可能是多条） 
                $arr_order_log = $this->get_arr_order_log($arr_data, $arr_delivery_items_send, $arr_dlycorp);
                $mdl_order_log->insert($arr_order_log);
                
                //发货通知到微信
                kernel::single('weixin_transaction')->generate(array('order_id'=>$arr_data['order_id']));
                //成功返回
                return array("result"=>true);
            }else{
                return array("result"=>false,"msg"=>"发货单不是准备中状态！");
            }
        }else{
            return array("result"=>false,"msg"=>"需要修改的发货单不存在！");
        }
    }
    
    //获取错误是的返回数组
    private function get_return_error_arr($type,$arr_info,$msg){
        $return_arr = array("result"=>false,"msg"=>$msg);
        if ($type == "1"){
            $return_arr["arr"] = array(
                "delivery_id" => $arr_info["delivery_id"],
                "tid" => $arr_info["order_id"]
            );
        }
        if ($type == "2"){
            $return_arr["arr"] = array(
                    "delivery_id" => $arr_info["delivery_id"],
                    "order_item_id" => $arr_info["o_items"]["item_id"],
                    "item_type" => $arr_info["o_items"]["item_type"] == 'product' ? 'goods' : 'gift',
                    'product_id' => $arr_info['d_items']["product_id"],
                    'product_bn' => $arr_info['d_items']["product_bn"],
                    'product_name' => $arr_info['d_items']["product_name"],
                    'number' => $arr_info['d_items']["number"],
            );
        }
        return $return_arr;
    }
    
    /*
     * 获取订单日志需要更新或者新增的arr_order_log并返回
     * $arr_data 主发货单信息
     * $arr_delivery_items_send 发货单明细发货信息
     * $arr_dlycorp 物流信息
     * $type 默认insert
     */
    private function get_arr_order_log($arr_data,$arr_delivery_items_send,$arr_dlycorp,$type="insert"){
        $log_text = array();
        if($type == "insert"){
            $log_text[] = array (
                    'txt_key' => app::get ( 'b2c' )->_ ( '订单' ) . '<a href="javascript:void(0)" onclick=\'show_delivery_item(this,"%s",%s)\' title="' . app::get ( 'b2c' )->_ ( '点击查看详细' ) . '" style="color: rgb(0, 51, 102); font-weight: bolder; text-decoration: underline;">' . app::get ( 'b2c' )->_ ( '全部商品' ) . '</a>' . app::get ( 'b2c' )->_ ( '已发货' ),
                    'data' => array( 0 => $arr_data['delivery_id'], 1 => htmlentities ( json_encode ( $arr_delivery_items_send ), ENT_QUOTES ) )
            );
            if (!empty($arr_dlycorp)) {
                $log_text[] = array (
                        'txt_key' => app::get ( 'b2c' )->_ ( '物流公司' ) . ',:<a class="lnk" target="_blank" title="%s" href="%s">%s</a>（' . app::get ( 'b2c' )->_ ( '可点击进入物流公司网站跟踪配送' ) . ')',
                        'data' => array ( 0 => $arr_dlycorp['name'], 1 => $arr_dlycorp['request_url'] )
                );
            }
            if ($arr_data["logi_no"]) {
                $log_text[] = array (
                        'txt_key' => app::get ( 'b2c' )->_ ( '物流单号' ) . ":%s",
                        'data' => array ( 0 => $arr_data["logi_no"] )
                );
                $log_addon['logi_no'] = $arr_data["logi_no"];
                $log_addon = serialize($log_addon);
            }
            $log_text = serialize ($log_text);
            $arr_order_log = array (
                    'rel_id' => $arr_data['order_id'],
                    'op_id' => '1',
                    'op_name' => 'admin',
                    'alttime' => time(),
                    'bill_type' => 'order',
                    'behavior' => 'delivery',
                    'result' => 'SUCCESS',
                    'log_text' => $log_text,
                    'addon' => $log_addon
            );
            return $arr_order_log;
        }
        /** 已弃用了 update **/
        if($type == "update"){
            $log_text[] = array (
                    'txt_key' => app::get ( 'b2c' )->_ ( '订单' ) . '<a href="javascript:void(0)" onclick=\'show_delivery_item(this,"%s",%s)\' title="' . app::get ( 'b2c' )->_ ( '点击查看详细' ) . '" style="color: rgb(0, 51, 102); font-weight: bolder; text-decoration: underline;">' . app::get ( 'b2c' )->_ ( '全部商品' ) . '</a>' . app::get ( 'b2c' )->_ ( '已发货' ),
                    'data' => array ( 0 => $arr_data['delivery_id'], 1 => htmlentities ( json_encode ( $arr_delivery_items_send ), ENT_QUOTES ) )
            );
            if (!empty($arr_dlycorp)) {
                $log_text[] = array (
                        'txt_key' => ',' . app::get ( 'b2c' )->_ ( '物流公司' ) . ':<a class="lnk" target="_blank" title="%s" href="%s">%s</a>(' . app::get ( 'b2c' )->_ ( '可点击进入物流公司网站跟踪配送' ) . ')',
                        'data' => array ( 0 => $arr_dlycorp['name'], 1 => $arr_dlycorp['request_url'])
                );
            }
            if ($arr_data["logi_no"]) {
                $log_text[] = array (
                        'txt_key' => app::get ( 'b2c' )->_ ( '物流单号' ) . ":%s",
                        'data' => array( 0 => $arr_data["logi_no"] )
                );
                $log_addon['logi_no'] = $arr_data["logi_no"];
                $log_addon = serialize($log_addon);
            }
            $log_text = serialize($log_text);
            $arr_order_log = array( 'log_text' => $log_text );
            return $arr_order_log;
        }
    }
    
}