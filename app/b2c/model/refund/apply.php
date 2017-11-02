<?php
/**
 * ShopEx licence
 * create 20170214 by wangjianjun
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class b2c_mdl_refund_apply extends dbeav_model{
    
    /**
     * 通过会员的编号得到refund申请退款标准数据格式
     * @params string $member_id
     * @params string $nPage
     * @params array $limit
     */
    public function fetchByMember($member_id,$nPage=1,$limit=10){
        if(!$limit){
            $limit = 10;
        }
        $limitStart = ($nPage-1) * $limit;
        $filter = array('member_id' => $member_id);
        $sdf_refund = $this->getList('*', $filter, $limitStart, $limit, 'apply_id DESC');
        // 生成分页组建
        $countRd = $this->count($filter);
        $total = ceil($countRd/$limit);
        $current = $nPage;
        $token = '';
        $arrPager = array(
            'current' => $current,
            'total' => $total,
            'token' => $token,
        );
        $arrdata['data'] = $sdf_refund;
        $arrdata['pager'] = $arrPager;
        return $arrdata;
    }
    
    /*
     * 记录退款申请相关日志
     * $refund_apply_bn 退款申请单号
     * $log_text 日志描述
     * $is_backend 是否是后台
     */
    public function saveOrderLog($refund_apply_bn,$log_text,$is_backend = false){
        $orderLog = $this->app->model("order_log");
        if($is_backend){
            //后台
            $default_op_name = "管理员";
            $obj_user = kernel::single('desktop_user');
            $op_id = $obj_user->user_data['user_id'];
            $op_name = $obj_user->user_data['name'];
        }else{
            //前台
            $default_op_name = "顾客";
            $arrMember = kernel::single('b2c_user_object')->get_current_member();
            $op_id = $arrMember['member_id'];
            //获取登录用户名
            if ($op_id){
                $op_name = $arrMember['uname'];
            }
        }
        $sdf_order_log = array(
                'rel_id' => $refund_apply_bn,
                'op_id' => $op_id,
                'op_name' => (!$op_id) ? $default_op_name : $op_name,
                'alttime' => time(),
                'bill_type' => 'refund_apply',
                'behavior' => 'refunds',
                'result' => 'SUCCESS',
                'log_text' => $log_text,
        );
        $orderLog->insert($sdf_order_log);
    }
    
    /*
     * 后台：操作拒绝或者退款相应的操作更新及日志的记录
     * $refund_apply_bn 退款申请单号
     * $update_status 更新状态
     * $msg 日志描述
     */
    public function update_refund_apply($refund_apply_bn,$update_status,$msg){
        $result = $this->update(array("status"=>$update_status),array("refund_apply_bn"=>$refund_apply_bn));
        //记录操作日志
        if ($result){
            $msg.="成功";
        }else{
            $msg.="失败";
        }
        $this->saveOrderLog($refund_apply_bn,$msg,true);
        return $result;
    }
    
    //获取refund_apply的refunds_reason字段的信息
    public function get_field_refunds_reason(){
        $current_schema = $this->get_schema();
        return $current_schema["columns"]["refunds_reason"]["type"];
    }
    
    /*
     * 获取refunds_reason统一的文字描述
     * $refunds_reason type数字值
     */
    public function get_refunds_reason_text($refunds_reason){
        $current_schema_refunds_reason = $this->get_field_refunds_reason();
        return $current_schema_refunds_reason[$refunds_reason];
    }
    
}
