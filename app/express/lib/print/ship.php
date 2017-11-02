<?php

class express_print_ship{

    public function format($print_data, $sku,&$_err){
        $ids[] = $print_data['order_id'];
        $o_bn[] = $print_data['order_id'];
         if ($ids){
            $name = implode(',', $ids);
         }

         $idd = array(
             'order_id' => $print_data['order_id'],
             'consignee' => array('name'=>$print_data['ship_name']),
         );

         $logiId = $print_data['logi_id'];

        if (!$express_company_no) {
            $express_company_no = strtoupper($print_data['type']);
            $logi_name = $print_data['corp_name'];
        }

        return array(
            'allItems' => $print_data,
            'print_logi_id' => $logiId,
            'order' => $print_data,
            'dly_tmpl_id' => $print_data['dly_tmpl_id'],
            'order_number' => 1,
            'vid' => $name,
            'hasOnePrint' => 0,
            'hasPrintStr' => '',
            'ids' => $ids,
            'idd' => $idd,
            'logid' => $logiId,
            'logi_name' => $logi_name,
            'count' => sizeof($ids),
            'express_company_no' => $express_company_no,
            'o_bn' => $o_bn,
        );
    }

    public function arrayToJson($deliverys) {
        $jsondata = '';
        if ($deliverys) {
            $this->formatField($deliverys);
            $jsondata = json_encode($deliverys);
            $jsondata = str_replace(array('&quot;'), array('”'), $jsondata);
        }
        return $jsondata;
    }

    public function formatField(&$oriRowData) {
        foreach($oriRowData as $k => &$val) {
            if(is_array($val)) {
                foreach($val as &$data) {
                    $data = $this->printSingleFormat($data);
                }
            } else {
                $val = $this->printSingleFormat($val);
            }
        }
    }

    public function printSingleFormat($single) {
        if($single === null) {
            return '';
        } elseif (is_bool($single)) {
            return $single === false ? 'false' : 'true';
        }
        $str = strval($single);
        $str = trim($str);
        $str = str_replace(array('&#34;','"','&quot;','&quot'),array('“','“','“'), $str);
        $str = str_replace(array('&quot;','&quot'), array('”','”'), $str);

        return $str;
    }

    function array2xml2($data,$root='root'){
        $xml='<'.$root.'>';
        $this->_array2xml($data,$xml);
        $xml.='</'.$root.'>';
        return $xml;
    }

    function _array2xml(&$data,&$xml){
        if(is_array($data)){
            foreach($data as $k=>$v){
                if(is_numeric($k)){
                    $xml.='<item>';
                    $xml.=$this->_array2xml($v,$xml);
                    $xml.='</item>';
                }else{
                    $xml.='<'.$k.'>';
                    $xml.=$this->_array2xml($v,$xml);
                    $xml.='</'.$k.'>';
                }
            }
        }elseif(is_numeric($data)){
            $xml.=$data;
        }elseif(is_string($data)){
            $xml.='<![CDATA['.$data.']]>';
        }
    }
}