<?php
class express_print_elements{

    protected $elements = array(
        'logi_no' => '快递单号',
        'ship_name'   => '收货人-姓名',
        'ship_area_0' => '收货人-地区1级',
        'ship_area_1' => '收货人-地区2级',
        'ship_area_2' => '收货人-地区3级',
        'ship_addr'   => '收货人-地址',
        'ship_detailaddr' => '收货人-详细地址',
        'ship_tel'    => '收货人-电话',
        'ship_mobile' => '收货人-手机',
        'ship_zip'    => '收货人-邮编',

        'dly_name'    => '发货人-姓名',
        'dly_area_0'  => '发货人-地区1级',
        'dly_area_1'  => '发货人-地区2级',
        'dly_area_2'  => '发货人-地区3级',
        'dly_address' => '发货人-地址',
        'dly_detailaddr' => '发货人-详细地址',
        'dly_tel'     => '发货人-电话',
        'dly_mobile'  => '发货人-手机',
        'dly_zip'     => '发货人-邮编',

        'date_y'      => '当日日期-年',
        'date_m'      => '当日日期-月',
        'date_d'      => '当日日期-日',
        'date_h'      => '当日日期-时',
        'date_i'      => '当日日期-分',
        'date_s'      => '当日日期-秒',

        'order_bn'    => '订单-订单号',
        'order_count' => '快递单-物品数量',
        'delivery_order_amount' => '快递单-总价',
        'delivery_order_amount_d' => '快递单-总价(大写)',
        'order_memo'  => '订单附言',
        'shop_name'   => '网店名称',
        'bn_spec_num_n' => '商家编码+规格+数量(不换行)',
        'bn_spec_num_y' => '商家编码+规格+数量(换行)',
        'goodsbn_spec_num_n' => '商品编码+规格+数量(不换行)',
        'goodsbn_spec_num_y' => '商品编码+规格+数量(换行)',
        'member_uname' => '会员名',
        'bn_amount_n' => '货号+数量(不换行)',
        'name_amount_n' => '货品名+数量(不换行)',
        'bn_name_amount_n' => '货号+货品名+数量(不换行)',
        'bn_amount' => '货号+数量',
        'name_amount' => '货品名+数量',
        'bn_name_amount' => '货号+货品名+数量',
        'name_spec_amount' => '货品名+规格+数量',
        'bn_name_spec_amount' => '货号+货品名+规格+数量(不换行)',
        'bn_name_spec_amount_y' => '货号+货品名+规格+数量(换行)',
        'new_bn_name_amount' => '{商品名称+数量}不换行',
        'bn_spec_num'=>'货号+规格+数量',
        'total_product_weight_g'=>'货品重量 单位：g',
        'total_product_weight_kg'=>'货品重量 单位：kg',
        'goods_bn'=>'商家编码',
    );

    /**
     * default elements
     * 默认配置列表
     * @return array();
     */
    public function defaultElements(){
        $printTagObj = app::get('express')->model('print_tag');
        $rows = $printTagObj->getList('*');
        foreach($rows as $row){
            if($row['tag_id']>0){
                $key = 'print_tag_'.$row['tag_id'];
                $this->elements[$key] = '大头笔-'.$row['name'];
            }
        }
        return $this->elements;
    }

    /**
     * process default print content
     * 处理快递单打印项的对应内容
     * @param array $value_list
     * @return string
     */
    public function processElementContent($value_list)
    {
        $data['delivery_order_amount'] = number_format($value_list['order_price'], 2, '.', ' ');
        $data['delivery_order_amount_d'] = $this->NumToFinanceNum(number_format($value_list['order_price'], 2, '.', ''), true, false);
        $data['bn_spec_num_y'] = $data['bn_spec_num_n'] = '';
        $noFirst = false;
        if ($value_list['order_objects']) {
            $totalNum = 0;
            $total_product_weight = 0;
            $i = 0;
            foreach ($value_list['order_objects'] as $obj){
                foreach ($obj['order_items'] as $item){
                    if ($item['addon']) {
                        $addon = sprintf(' %s', $item['addon']);
                    } else {
                        $addon = '';
                    }
                    $totalNum = $totalNum + $item['quantity'];

                    //商家编码+规格+数量+换行
                    $goods_bn = $obj['bn'];
                    $bn = $item['bn'];

                    $noFirst && $data['bn_spec_num_n'] .= ' , ';
                    $noFirst && $data['bn_spec_num_y'] .= "\r\n";
                    $noFirst && $data['bn_name_spec_amount'] .= ' , ';
                    $noFirst && $data['bn_name_spec_amount_y'] .= "\r\n";

                    if(empty($bn) && empty($item['addon'])) {
                        $data['bn_spec_num_n'] .= '';
                        $data['bn_spec_num_y'] .= '';
                    } else {
                        $data['bn_spec_num_n'].= $bn."  ". $item['addon'] . " x " . $item['quantity'];
                        $data['bn_spec_num_y'].= $bn."  ". $item['addon'] . " x " . $item['quantity'];
                    }

                    $total_product_weight+= ($item['weight']*$item['quantity']);

                    //货号+数量
                    $data['bn_amount_n'].= $item['bn']." x ".$item['quantity']." , ";
                    //货品名+数量
                    $data['name_amount_n'].= $item['name']. $addon ." x ".$item['quantity']." , ";
                    //货号+货品名+数量
                    $data['bn_name_amount_n'].= $item['bn']." ：".$item['name']. $addon ." x ".$item['quantity']." , ";

                    $data['bn_amount'].= "货号：".$item['bn']." 数量：".$item['quantity']."\n";
                    //货品名+数量
                    $data['name_amount'].= "货品名：".$item['name']. $addon ." 数量：".$item['quantity']."\n";
                    //货号+货品名+数量
                    $data['bn_name_amount'].= "货号：".$item['bn']." 货品名：".$item['name']. $addon ." 数量：".$item['quantity']."\n";
                    //货品名+规格+数量
                    $data['name_spec_amount']    .= $item['name']."  ". $item['addon'] . " x " . $item['quantity'];
                    //货号+货品名+规格+数量
                    $data['bn_name_spec_amount'] .=  $item['bn']."：".$item['name']."  ". $item['addon'] . " x " . $item['quantity'];
                    $data['bn_name_spec_amount_y'] .=  $item['bn']."：".$item['name']."  ". $item['addon'] . " x " . $item['quantity'];

                    $data['new_bn_name_amount'] .="【".$item['name']." x ".$item['quantity']." 】 ";
                    $data['bn_spec_num'].= $item['bn']."  ". $item['addon'] . " x " . $item['quantity'];

                    $data['goodsbn_spec_num_n'] .= $goods_bn."  ". $item['addon'] . " x " . $item['quantity'];
                    $data['goodsbn_spec_num_y'] .= $goods_bn."  ". $item['addon'] . " x " . $item['quantity']."\n";

                    $data['goods_bn'].= $goods_bn."\n";

                    $noFirst = true;
                    $i++;
                }
            }

            $data['bn_amount_n'] = preg_replace('/, $/is', '', $data['bn_amount_n']) . sprintf(' 共 %d 件', $totalNum);
            $data['name_amount_n'] = preg_replace('/, $/is', '', $data['name_amount_n']) . sprintf(' 共 %d 件', $totalNum);
            $data['bn_name_amount_n'] = preg_replace('/, $/is', '', $data['bn_name_amount_n']) . sprintf(' 共 %d 件', $totalNum);
            $data['total_product_weight_g'] = $total_product_weight.'g';//商品重量累加
            $data['total_product_weight_kg'] = ($total_product_weight/1000).'kg';//商品重量累加
        }
        
        //会员名
        $data['member_uname'] = $value_list['member_uname'];

        //收货人信息
        $data['ship_name']   = $value_list['ship_name'];
        $data['ship_addr']   = $value_list['ship_addr'];
        $data['ship_tel']    = (string)$value_list['ship_tel'];
        $data['ship_mobile'] = (string)$value_list['ship_mobile'];
        $data['ship_zip']    = (string)$value_list['ship_zip'];
        $data['ship_area_0'] = trim($value_list['ship_area_0']);
        $data['ship_area_1'] = trim($value_list['ship_area_1']);
        $data['ship_area_2'] = trim($value_list['ship_area_2']);
        $data['ship_detailaddr'] = $value_list['ship_area_0'] . $value_list['ship_area_1'] . $value_list['ship_area_2']. $value_list['ship_addr'];

        $data['order_bn']    = (string)$value_list['order_id'];
        $data['order_count'] = (string)$value_list['order_count'];
        $data['order_memo']  = (string)$value_list['order_memo'];
        $data['logi_no'] = (string)$value_list['logi_no'];

        //时间定义
        if(isset($GLOBALS['user_timezone'])){
             $t = time()+($GLOBALS['user_timezone']-SERVER_TIMEZONE)*3600;
        }else{
             $t = time();
        }

        $data['date_y'] = (string)date('Y',$t);
        $data['date_m'] = (string)date('m',$t);
        $data['date_d'] = (string)date('d',$t);
        $data['date_ymd'] = date('Y-m-d',$t);
        $data['date_h'] = date('H',$t);
        $data['date_i'] = date('i',$t);
        $data['date_s'] = date('s',$t);

        //发货人信息
        $data['dly_area_0']     = $value_list['dly_area_0'];
        $data['dly_area_1']     = $value_list['dly_area_1'];
        $data['dly_area_2']     = $value_list['dly_area_2'];
        $data['dly_address']    = $value_list['dly_address'];
        $data['dly_detailaddr'] = $value_list['dly_area_0']. $value_list['dly_area_1'] . $value_list['dly_area_2'] . $value_list['dly_address'];
        $data['dly_tel']        = (string)$value_list['dly_tel'];
        $data['dly_mobile']     = (string)$value_list['dly_mobile'];
        $data['dly_zip']        = (string)$value_list['dly_zip'];
        $data['dly_name']       = $value_list['dly_name'];
        $data['shop_name']      = app::get('site')->getConf('site.name');

        //根据自定义获取大头笔信息
        $this->getPrintTag($data);

        foreach($data as $k=>$v){
            $data[$k] = addslashes($v);
            unset($k,$v);
        }
        return $data;
    }

    //根据收货地区得到大头笔内容
    function getPrintTag(&$data) {
        $zhixiashi = array('北京','上海','天津','重庆');
        $areaGAT = array('香港','澳门','台湾');
        $area2Str = substr($data['ship_area_2'],-3);
        $printTagObj = app::get('express')->model('print_tag');
        $rows = $printTagObj->getList('*');
        foreach($rows as $row){
            if($row['tag_id']>0){
                $key = 'print_tag_'.$row['tag_id'];
                $tagConfig= unserialize($row['config']);
                if ($data['ship_area_0'] && in_array($data['ship_area_0'],$zhixiashi)) {
                    if($tagConfig['zhixiashi'] == '1'){
                        $data[$key] = $data['ship_area_2'];
                    }else{
                        $data[$key] = $data['ship_area_1'].$data['ship_area_2'];
                    }
                } elseif($data['ship_area_0'] && in_array($data['ship_area_0'],$areaGAT)) {
                    if($tagConfig['areaGAT'] == '1'){
                        $data[$key] = $data['ship_area_2'];
                    }else{
                        $data[$key] = $data['ship_area_1'].$data['ship_area_2'];
                    }
                } else {
                    $data[$key] = '';
                    if($tagConfig['province'] == '1'){
                        $data[$key] .= $data['ship_area_0'];
                    }

                    if ($area2Str=='区') {
                        if($tagConfig['district'] == '1'){
                            $data[$key] .= $data['ship_area_1'];
                        }else{
                            $data[$key] .= $data['ship_area_1'].$data['ship_area_2'];
                        }
                    } elseif ($area2Str=='市') {
                        if($tagConfig['city'] == '1'){
                            $data[$key] .= $data['ship_area_1'].$data['ship_area_2'];
                        }else{
                            $data[$key] .= $data['ship_area_2'] ? $data['ship_area_2'] : $data['ship_area_1'];
                        }
                    } else {
                        if($tagConfig['county'] == '1'){
                            $data[$key] .= $data['ship_area_2'] ? $data['ship_area_2'] : $data['ship_area_1'];
                        }else{
                            $data[$key] .= $data['ship_area_1'].$data['ship_area_2'];
                        }
                    }
                }
            }
        }
    }

    function NumToFinanceNum($num,$mode = true,$sim = true){
        if(!is_numeric($num)) return '含有非数字非小数点字符！';
        $char    = $sim ? array('零','一','二','三','四','五','六','七','八','九')
        : array('零','壹','贰','叁','肆','伍','陆','柒','捌','玖');
        $unit    = $sim ? array('','十','百','千','','万','亿','兆')
        : array('','拾','佰','仟','','萬','億','兆');
        $retval = $mode ? '元' : '点';
        if(strpos($num, '.')){
            list($num,$dec) = explode('.', $num);
            $dec = strval(round($dec,2));
            if($mode){
                $retval .= $dec['0'] ? "{$char[$dec['0']]}角" : '';
                $retval .= $dec['1'] ? "{$char[$dec['1']]}分" : '';
            }else{
                for($i = 0,$c = strlen($dec);$i < $c;$i++) {
                    $retval .= $char[$dec[$i]];
                }
            }
        }

        $prev_num ='';
        $str = $mode ? strrev(intval($num)) : strrev($num);
        for($i = 0,$c = strlen($str);$i < $c;$i++) {
            if(($str[$i] == 0 && $i == 0) || ($prev_num == 0 && $str[$i] == 0 && $i > 0) || ($i == 4 && $str[$i] == 0)){
                $out[$i] = '';
            }else{
                $out[$i] = $char[$str[$i]];
            }

            $prev_num = $str[$i];

            if($mode){
                $out[$i] .= $str[$i] != '0'? $unit[$i%4] : '';

                if($i%4 == 0){
                    $out[$i] .= $unit[4+floor($i/4)];
                }
            }
        }

        $retval = join('',array_reverse($out)).$retval;
        return $retval;
    }

    /**
     * 获取面单信息
     * @param Array 参数信息
     */
    function getMainnoInfo($params) {
        $logi_id = $params['logi_id'];
        $dlyCorpModel = app::get('ome')->model('dly_corp');
        $filter = array('corp_id' => $logi_id);
        $dlyData = $dlyCorpModel->getList('channel_id,tmpl_type', $filter);
        $mailnoExtend = array();
        if ($dlyData && $dlyData[0]['tmpl_type'] == 'electron') {
            $params['channel_id'] = $dlyData[0]['channel_id'];
            $mailnoExtend = kernel::single('logisticsmanager_service_waybill')->getWayBillExtend($params);
        }
        return $mailnoExtend;
    }    
}
