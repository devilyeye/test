<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_finder_refund_apply{

    var $detail_basic = '基本信息';
    var $column_editbutton = '操作';

    public function detail_basic($apply_id){
        $render = app::get('b2c')->render();
        //获取退款申请信息
        $mdl_b2c_refund_apply = app::get('b2c')->model('refund_apply');
        $render->pagedata["refund_apply_info"] = $mdl_b2c_refund_apply->dump(array("apply_id"=>$apply_id));
        //获取refunds_reason_text
        $render->pagedata["refund_apply_info"]["refunds_reason"] = $mdl_b2c_refund_apply->get_refunds_reason_text($render->pagedata["refund_apply_info"]["refunds_reason"]);
        $refund_apply_bn = $render->pagedata["refund_apply_info"]["refund_apply_bn"];
        $status = $render->pagedata["refund_apply_info"]["status"];
        $order_id = $render->pagedata["refund_apply_info"]["order_id"];
        //获取退款申请日志
        $mdl_b2c_order_log = app::get('b2c')->model('order_log');
        $render->pagedata["refund_apply_log"] = $mdl_b2c_order_log->getList("*",array("rel_id"=>$refund_apply_bn,"bill_type"=>"refund_apply","behavior"=>"refunds"),0,-1,'log_id asc');
        //是否有绑定
        $mdl_b2c_shop = app::get('b2c')->model('shop');
        $node_type = array('ecos.ome','ecos.ocs');
        $cnt = $mdl_b2c_shop->count(array('status'=>'bind','node_type|in'=>$node_type));
        if($cnt>0){
            $render->pagedata['showBtn'] = false;
        }else{
            if (intval($status) > 0){
                //已做过已退款或者已拒绝的操作
                $render->pagedata['showBtn'] = false;
            }else{
                $render->pagedata['showBtn'] = true;
                $render->pagedata["refuse_url"] = "index.php?app=b2c&ctl=admin_refund_apply&act=dorefuse&p[0]=".$refund_apply_bn."&p[1]=".$order_id;
            }
        }
        return $render->fetch('admin/refund/apply/detail.html');
    }
    
    var $addon_cols='member_id,status,refund_apply_bn,order_id';
    
    public $column_op_name = '申请人';
    public $column_op_name_order = 16;
    public function column_op_name($row){
        $member_id = $row[$this->col_prefix.'member_id'];
        $mdl_pam_members = app::get('pam')->model('members');
        $rs_login_account = $mdl_pam_members->dump(array("member_id"=>$member_id),"login_account");
        return $rs_login_account["login_account"];
    }

    public $column_editbutton_order = '1';
    public $column_editbutton_width = 110;
    public function column_editbutton($row){
        $refund_apply_bn = $row[$this->col_prefix.'refund_apply_bn'];
        $order_id = $row[$this->col_prefix.'order_id'];
        
        //判断是否有成功绑定的店铺
        $mdl_b2c_shop = app::get('b2c')->model('shop');
        $node_type=array('ecos.ome','ecos.ocs');
        $cnt = $mdl_b2c_shop->count(array('status'=>'bind','node_type|in'=>$node_type));
        if($cnt>0) return '';
        
        $render = app::get('b2c')->render();
        $render->pagedata['is_active'] = true;
        $status = $row[$this->col_prefix.'status'];
        if ($status > 0){
            //已做拒绝或者退款处理
            $render->pagedata['is_active'] = false;
            return '';
        }
        
        if ($status == 0){
            //待处理的可以进行退款或者拒绝处理
            $render->pagedata['handle_title'] = "处理退款申请";
            //退款
            $refund = array(
                    "href" => "javascript:void(0);",
                    "submit" => "index.php?app=b2c&ctl=admin_refund_apply&act=gorefund&p[0]=".$refund_apply_bn."&p[1]=".$order_id,
                    "label" => "退款",
                    "target" => "dialog::{title:'退款',width:800,height:420}",
                    "disable" => false,
                    "confirm" => null,
            );
            //拒绝
            $refuse = array(
                    'href'=>"javascript:void(0);",
                    'submit'=>"index.php?app=b2c&ctl=admin_refund_apply&act=dorefuse&p[0]=".$refund_apply_bn."&p[1]=".$order_id,
                    'label'=>"拒绝",
                    'target'=>'confirm',
                    'disable'=>false,
                    'confirm'=>'拒绝此退款申请，确认要执行吗?',
            );
            $render->pagedata['arr_link']["refund_apply"]["refund"] = $refund;
            $render->pagedata['arr_link']["refund_apply"]["refuse"] = $refuse;
            return $render->fetch('admin/actions.html');
        }
    }
    
}
