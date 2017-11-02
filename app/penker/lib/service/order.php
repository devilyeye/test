<?php 
class penker_service_order{
    function __construct($app){
        $this->app = $app;
    }
    function create($params){
        $goods = app::get('b2c')->model('goods');
        $goods_arr =array();
        foreach($params['order_objects'] as $key => $val){
            $addon_arr = unserialize($val['order_items'][0]['addon']);
            if(is_array($addon_arr['product_attr'])){
                $addon = null;
                foreach($addon_arr['product_attr'] as $value){
                    $addon.= $value['label'].':'.$value['value'].'|';
                }
            }
            $goodsData = $goods->getrow('image_default_id',array('goods_id'=>$val['goods_id']));
            $goods_arr[$key]['goods_id'] = $val['goods_id'];
            $goods_arr[$key]['bn'] = $val['order_items'][0]['products']['product_id'];
            $goods_arr[$key]['product_id'] = $val['order_items'][0]['products']['product_id'];
            $goods_arr[$key]['name'] = $val['name'];
            $goods_arr[$key]['thumbnail_pic'] = base_storager::image_path($goodsData['image_default_id'], 's');
            $goods_arr[$key]['small_pic'] = base_storager::image_path($goodsData['image_default_id'], 's');
            $goods_arr[$key]['spec'] = !empty($addon)?$addon:null;
            $goods_arr[$key]['quantity'] = $val['quantity'];
            $goods_arr[$key]['price'] = $val['price'];
            $goods_arr[$key]['link'] = 'http://'.$_SERVER['HTTP_HOST'].app::get('wap')->router()->gen_url( array( 'app'=>'b2c','real'=>1,'ctl'=>'wap_product','args'=>array($val['order_items'][0]['products']['product_id'])));
            $goods_arr[$key]['other'] = '';
        }
        $goods_json = json_encode(array_values($goods_arr));

        $arr_user = app::get('pam')->model('bind_tag')->getRow('open_id',array('member_id'=>$params['member_id']));

        $buyer_info = array(
            'buyer_name' => $params['consignee']['name'],
            'buyer_mobile' => $params['consignee']['mobile'],
            'buyer_addr' => $params['consignee']['addr'],
            'other' => '',
            );
        $buyer_json = json_encode($buyer_info);
        if($params['pay_status'] == 1){
            $status = '已支付';
        }else{
            $status = '未支付';
        }
        $order_arr = array(
            'source' => 'ECSTORE',
            'orderno' => $params['order_id'],
            'nodeId' => base_shopnode::node_id('b2c'),
            'guide_identity' =>$params['guide_identity'],
            'goods' => $goods_json,
            'cost_goods' => $params['cost_item'],
            'cost_freight' => $params['shipping']['cost_shipping'],
            'amount' => $params['total_amount'],
            'payed' => $params['payinfo']['cost_payment'],
            'buyer' => $arr_user['open_id'],
            'buyer_info' => $buyer_json,
            'express_info' => '',
            'mark' => $params['memo'],
            'create_time' => $params['createtime'],
            'status' => $status,
            'status_desc' => '未支付,未发货',
            );
        $penker = $this->app->model('bind');
        $arr_bind = $penker->getList();
        $key = $arr_bind[0]['secret_key'];
        $order_arr['sig'] = $this->make_sig($order_arr,$key);
        $url = 'http://penkr.shopex.cn/index.php?ctl=order&act=accept';
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->post($url,$order_arr);
        logger::info('order_arr:'.var_export($order_arr,1));
        logger::info('penker:'.$response);
        return $response;
    }
    function update($params){
        $order = app::get('b2c')->model('orders');
        $order_item = $order->getrow('pay_status,ship_status',array('order_id' =>$params['order_id']));
        if(isset($params['pay_status'])){
            $order_item['pay_status'] = $params['pay_status'];
        }
        switch ($order_item['pay_status']) {
            case '0':$status_desc = '未支付';$status='未支付';
               break;
           case '1':$status_desc = '已支付';$status='已支付';
                break;
            case '2':$status_desc = '已付款至到担保方';$status='未支付';
                break;
             case '3':$status_desc = '部分付款';$status='未支付';
                break;
            case '4':$status_desc = '部分退款';$status='未支付';
                break;
            case '5':$status_desc = '全额退款';$status='未支付';
                break;
            default:
                 break;
        }
        if(isset($params['ship_status'])){
            $order_item['ship_status'] = $params['ship_status'];
        }
        switch ($order_item['ship_status']) {
            case '0':$status_desc.=',未发货';
                break;
            case '1':$status_desc.=',已发货';
                break;
            case '2':$status_desc.=',部分发货';
                break;
            case '3':$status_desc.=',部分退货';
                break;
             case '4':$status_desc.=',已退货';
                break;
             default:
                break;
         }

        if(isset($params['express_info'])){
            $express_info = $params['express_info'];
        }
        $order_arr = array(
            'source' => 'ECSTORE',
            'nodeId' => base_shopnode::node_id('b2c'),
            'orderno' => $params['order_id'],
            'express_info' => !empty($express_info)?$express_info:'',
            'update_time' => time(),
            'status' => !empty($status)?$status:'',
            'status_desc' => $status_desc,
        );
        if(!empty($params['payed'])){
            $order_arr['payed'] = $params['payed'];
        }
        $penker = $this->app->model('bind');
        $arr_bind = $penker->getList();
        $key = $arr_bind[0]['secret_key'];
        $order_arr['sig'] = $this->make_sig($order_arr,$key);
        $url = 'http://penkr.shopex.cn/index.php?ctl=order&act=receipt';
        $core_http = kernel::single('base_httpclient');
        $response = $core_http->post($url,$order_arr);
        logger::info('order_arr:'.var_export($order_arr,1));
        logger::info('penker:'.$response);
        return $response;
    }
    function make_sig($post_params,$secrent_key){
        ksort($post_params);
        $str = '';
        foreach( $post_params as $key => $value )
        {
            if( $key != 'sig' )
            {
                $str .= $value;
            }
        }
        return strtoupper(md5($str. $secrent_key));
    }
}
