<?php

abstract class express_rpc_electron_abstract
{
    protected $channel;
    protected $title;
    protected $timeOut = 10;
    protected $primaryBn = '';
    protected $cacheLimit = 5000;
    protected $directNum = 1;
    protected $everyNum = 100;

    public function setChannel($channel) {
        $this->channel = $channel;
        return $this;
    }

    //电子面单请求统一出口
    final protected function requestCall($method,$params)
    {
        if(!$this->title) {
            $this->title = $this->channel['name'] . '获取电子面单';
        }

        $query_params = $this->get_query_params($method, $params);
        $query_params = array_merge((array) $params, (array) $query_params);

        $this->__caller = app::get('b2c')->remote(2);
        $result = $this->__caller->call($method,$query_params);

        //记录日志
        if($result){
            $params['status'] = 'success';
        }else{
            $params['status'] = 'fail';
        }

        $rpc_response = $this->__caller->rpc_response;
        $result = json_decode($rpc_response,true);
        $params['msg_id'] = $result['msg_id'];
        $this->request_log($method,$params,$rpc_id);

        return $result;
    }

    private function get_query_params($method, $params) {
        $account = explode('|||',$this->channel['shop_id']);
        $query_params = array(
            'app_id' => 'ecos.b2c',
            'method' => $method,
            'date' => date('Y-m-d H:i:s'),
            'format' => 'json',
            'certi_id' => base_certificate::certi_id(),
            'v' => 1,
            'from_node_id' => base_shopnode::node_id('b2c'),
            'member_id' =>  $account[0], #
            'member_pwd'  =>  $account[1],    #
            'month_code'    =>  $account[3],    #
            'pay_type'      =>  $account[2],    #,
            'node_type'     => 'ums',
            'to_node_id'    => '1705101437',
        );

        return $query_params;
    }

    //是否直辖市
    public function isMunicipality($province) {
        $municipality = array('北京市', '上海市', '天津市', '重庆市');
        $status = false;
        foreach ($municipality as $zxs) {
            if (substr($zxs, 0, strlen($province)) == $province) {
                $status = true;
                break;
            }
        }
        return $status;
    }

    #过滤特殊字符
    public function charFilter($str){
        return str_replace(array("<",">","&","'",'"',''),'',$str);
    }

    /**
    * 处理直连返回结果
    *
    */
    public function directDataProcess($data){
        $channel = $this->channel;

        $objWaybill = app::get('express')->model('waybill');
        $waybillExtendModel = app::get('express')->model('waybill_extend');
        foreach ($data as $val){

            if($val['succ']) {
                $logi_no = trim($val['logi_no']);
                $arrWaybill = $objWaybill->dump(array('channel_id' => $channel['channel_id'], 'waybill_number' => $logi_no),'id,status');
                if (!$arrWaybill) {
                    $arrWaybill = array(
                        'waybill_number' => $logi_no,
                        'channel_id'     => $channel['channel_id'],
                        'logistics_code' => $channel['logistics_code'],
                        'status'         => 1,
                        'create_time'    => time(),
                    );
                    $ret = $objWaybill->insert($arrWaybill);
                
                } elseif ($arrWaybill['status'] == '2') {
                    $objWaybill->update(array('status'=>'1'),array('id'=>$arrWaybill['id']));
                } 
                if(!$val['noWayBillExtend']) {
                    $waybillExtend = array(
                        'waybill_id' => $arrWaybill['id'],
                        'mailno_barcode' => $val['mailno_barcode'],
                        'qrcode' => $val['qrcode'],
                        'position' => $val['position'],
                        'position_no' => $val['position_no'],
                        'package_wdjc' => $val['package_wdjc'],
                        'package_wd' => $val['package_wd'],
                        'print_config' => $val['print_config'],
                        'json_packet' => $val['json_packet'],
                    );
                    
                    $filter = array('waybill_id' => $waybillExtend['waybill_id']);
                    if (!$waybillExtendModel->dump($filter)) {
                        $ret = $waybillExtendModel->insert($waybillExtend);
                    } else {
                        $ret = $waybillExtendModel->update($waybillExtend, $filter);
                    }
                }
            }
        }
    }

    //处理取消运单号返回结果
    public function recycleDataProcess($data){
        $objWaybill = app::get('express')->model('waybill');
        $arrWaybill = $objWaybill->dump(array('channel_id' => $this->channel['channel_id'], 'waybill_number' => $data['logi_no']),'id,status');
        if ($arrWaybill['status'] == '1') {
            $objWaybill->update(array('status'=>'2'),array('id'=>$arrWaybill['id']));
        }
    }

    public function request_log($method,$params,$rpc_id){
        $api_mdl = app::get('apiactionlog')->model('apilog');
        $time = time();
        $original_bn = $this->primaryBn;
        if(is_null($rpc_id)){
            $microtime = utils::microtime();
            $rpc_id = str_replace('.','',strval($microtime));
            $randval = uniqid('', true);
            $rpc_id .= strval($randval);
            $rpc_id = md5($rpc_id);
            $data = array(
                'apilog'=>$rpc_id,
                'calltime'=>$time,
                'params'=>$params,
                'api_type'=>'request',
                'msg_id'=>$params['msg_id'],
                'status'=>$params['status'],
                'worker'=>$method,
                'original_bn'=>$original_bn,
                'task_name'=>$this->title,
                'log_type'=>'waybill',
                'createtime'=>$time,
                'last_modified'=>$time,
                'retry'=>$retry?$retry:0,
            );
        }
        return $api_mdl->save($data);
    }

}