<?php
class express_rpc_electron_unionpay extends express_rpc_electron_abstract
{

    public function directRequest($sdf){
        $this->primaryBn = $sdf['primary_bn'];
        $order = $sdf['order'];
        $shopInfo = $sdf['shop'];

        //解析省市区县
        $area = explode(':',$order['ship_area']);
        $area = explode('/',$area[1]);
        $to_address = $order['ship_addr'] ? $area['0'] . $area['1'] . $area['2'] . $order['ship_addr'] : '_SYSTEM';

        $receiver = array(
            'company'   =>  $this->charFilter($order['ship_name']),
            'name'      =>  $this->charFilter($order['ship_name']),
            'tel'       =>  $order['ship_tel'],
            'mobile'    =>  $order['ship_mobile'],
            'post_code' =>  $order['ship_zip'],
            'province'  =>  $area['0'],
            'city'      =>  $area['1'],
            'area'      =>  $area['2'],
            'address'   =>  $this->charFilter($to_address),
        );
        unset($area);

        $area = explode(':',$shopInfo['region']);
        $area = explode('/',$area[1]);
        $sender = array(
            'company'   =>  $shopInfo['name'] ? $shopInfo['name'] : '_SYSTEM',
            'name'      =>  $shopInfo['name'] ? $shopInfo['name'] : '_SYSTEM',
            'tel'       =>  $shopInfo['cellphone'],
            'mobile'    =>  $shopInfo['phone'],
            'post_code' =>  $shopInfo['zip'],
            'province'  =>  $area[0],
            'city'      =>  $area[1],
            'area'      =>  $area[2],
            'address'   =>  $this->charFilter($shopInfo['address']),
        );

        $params = array(
            'send_site'     =>  "",  # 收件网点标识
            'shipper_code'  =>  $this->channel['logistics_code'],  # 物流公司编码
            'logistic_code' =>  '',  # 运单号
            'tid'           =>  $order['order_id'],  # 订单号
            'cost'          =>  '',
            'other_cost'    =>  '',
            'receiver'      =>  json_encode($receiver),
            'sender'        =>  json_encode($sender),
            'start_date'    =>  '',#上门取货时间段
            'end_date'      =>  '',
            'weight'        =>  '',
            'volume'        =>  '',
            'remark'        =>  '',
            'qty'           =>  '',
            'service_list'  =>  '',#增值服务
            'commoditys'     =>  json_encode($this->format_order_item($sdf['order_item'])),#货品明细信息
            'device_type' =>'pc',//pos android ios wp qt pc
            'is_auto_bill'  =>'1',
            'exp_type'=>'1',
        );
        $back =   $this->requestCall('store.trade.pub', $params);

        $data = empty($back['data']) ? '' : json_decode($back['data'], true);
        $data = empty($data['data']) ? '' :  json_decode($data['data'], true);
        if(empty($data)) {
            return false;
        }

        $msg = '';
        #以下这种情况，说明银联做过特殊处理了，需要在打印页面提示客户
        if($data['UmsbillNo'] && (($data['Success'] == true && empty($data['Order'] ['LogisticCode'])) && empty($data['Reason']))){
            $msg = '单据已推送到仓储';
        }elseif(!empty($data['Reason'])){
            $msg = $data['Reason'];
        }

        $result = array();
        $result[] = array(
            'succ' => $data['Order'] ['LogisticCode']? true : false,
            'msg' => $msg,
            'order_id' => $order['order_id'],
            'logi_no' => $data['Order'] ['LogisticCode'],
            'package_wdjc' => $data['Order'] ['DestinatioName'],
            'package_wd' => $data['Order'] ['DestinatioCode'],
        );
        $this->directDataProcess($result);

        return $result;
    }
    #获取货物名称
    public function format_order_item($orderItems = null) {
        $items = array();
        foreach($orderItems as $key=>$item){
            $items[$key]['code']    = $item['bn'];
            $items[$key]['name']    = $item['name'];
            $items[$key]['num']     = $item['nums'];
            $items[$key]['price']   = $item['price'];
            $items[$key]['weight']  = $item['weight'];
            $items[$key]['desc']    = $item['desc'];
            $items[$key]['GoodsSku'] = $item['barcode'];
            $items[$key]['vol']     = $item['vol'];
        }
        return $items;
    }

    public function recycleWaybill($sdf) {
        $this->title = $this->channel['name'] . '取消电子面单';
        $this->primaryBn = $sdf['logi_no'];
        $params = array(
            'order_id' => $sdf['logi_no'],
            'shipper_code'=>  $this->channel['logistics_code'],
             'is_auto_bill'  =>'1',
            'device_type' =>'pc',
            'exp_type'=>'1',
        );
        $back = $this->requestCall('store.trade.cancel', $params);

        //本地waybill表中运单号变更成作废，不管接口取消有无成功
        $this->recycleDataProcess($sdf);
        return $result;
    }

}