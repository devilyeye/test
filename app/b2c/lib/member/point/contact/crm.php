<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class b2c_member_point_contact_crm
{
    /**
     * 公开构造方法
     * @params app object
     * @return null
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->rpc_obj = kernel::single("b2c_apiv_exchanges_request_member_point");
    }

    /**
     * 查询积分
     * @param string member id 当前用户id
     * @param $type
     *  1.不直接调用查询接口，如有缓存积分则拿本地数据库的值 
     *  2.直接调用接口获取会员最新的积分  不成功的话拿本地的point字段值
     *  3.直接调用接口获取会员最新的积分  不成功的话直接返回api_get_point_fail
     */
    public function getPoint($member_id=0,$type=1)
    {
        $real_point = 0;

        $members = app::get('b2c')->model('members');

        if( $member_id ){
            $member_data = $members->getRow('point,member_lv_id',array('member_id'=>$member_id));
            $current_point = $member_data['point'];
            $current_member_lv = $member_data['member_lv_id'];

            if( !$this->apiStatus($type,$member_id) ){
                $real_point = $current_point;
            }else{
                if( $this->rpc_obj ){
                    $point_data = $this->rpc_obj->getActive($member_id);
                    $real_point = $point_data['total'];
                    //如果接口调用成功，则记录接口调用时间
                    if( $real_point !== null){
//                         $_SESSION['getPoint']['addtime'] = time();

                        //缓存member接口返回的积分值 时间为5分钟
                        cachemgr::co_start();
                        $expires_time = time()+300;
                        cachemgr::set('current_member_apipoint_'.$member_id, $real_point, array("expires"=>$expires_time));

                        if($real_point != $current_point){
                            $members->update(array('point'=>$real_point),array('member_id'=>$member_id));
                            $obj_member_point = app::get('b2c')->model('member_point');
                            $member_lv_id = $obj_member_point->member_lv_chk($member_id,$current_member_lv,$real_point);
                            if( $member_lv_id != $current_member_lv){
                                $members->update(array('member_lv_id'=>$member_lv_id),array('member_id'=>$member_id));
                            }
                        }
                    }else{
                        $real_point = $current_point;
                        if($type == 3){
                            $real_point = "api_get_point_fail";
                        }
                    }
                }
            }
        }

        return $real_point;
    }

    /**
     * 查询积分日志
     * @param arr data 接口请求参数 array('member_id'=>'','page'=>1,'page_size'=10)
     * @param arr pointlog 积分日志
     */
    public function getPointLog($data)
    {
        $pointlog = array();

        if( $this->rpc_obj ){
            $pointlog = $this->rpc_obj->getlogActive($data);
        }

        return $pointlog;
    }

    /**
     * 同步积分日志
     * @param point_id 积分日志的id
     *
     */

    public function pointChange($point_id){
        if($point_id){
            if($this->rpc_obj){
                $rpc_result = $this->rpc_obj->changeActive($point_id);
                return $rpc_result;
            }
        }
        return false;
    }
    
    /**
     * @param 
     * $type 参考function get_point方法
     * $member_id 当前用户id
     */
    private function apiStatus($type=1,$member_id){
//         if( !isset($_SESSION['getPoint']) ) return true;
//         $addtime = $_SESSION['getPoint']['addtime'];
//         if( $addtime + 60*5 < time() ) return true;
        
        if( $type == 2 || $type == 3 ) return true;
        
        //判断如果不存在当前member缓存的积分值 打接口获取积分
        cachemgr::get('current_member_apipoint_'.$member_id, $current_member_apipoint);
        if(!is_numeric($current_member_apipoint)){
            return true;
        }

        return false;
    }
}
