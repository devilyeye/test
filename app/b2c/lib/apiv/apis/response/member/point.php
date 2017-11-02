<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 *
 * b2c member_point interactor with center
 * shopex team
 * dev@shopex.cn
 */
class b2c_apiv_apis_response_member_point
{

    //初始化会员积分信息
    public function init_point($params, &$service){
        $members = app::get('b2c')->model('members');
        $member_point = app::get('b2c')->model('member_point');

        //默认分页码为1,分页大小为20
        $params['page_no'] = intval($params['page_no']) ? $params['page_no'] : 1;
        $params['page_size'] = intval($params['page_size']) ? $params['page_size'] : 20;
        $page_no = intval($params['page_no']) - 1;
        $limit  = intval($params['page_size']);
        $offset = $page_no * $limit;

        //返回总数
        $rows = $member_point->count();
        $data['item_total'] = $rows;

        $pointData = $member_point->getList('member_id,point,change_point,type,point_desc,expiretime,related_id,reason,operator,addtime,remark',array(),$offset,$limit);
        $data['list'] = $this->format_point_data($pointsData);
        return $data;
    }


    /**
     * 更新会员积分
     */
    public function update_point($params,&$service){
        $crm_member_id = $params['member_id'];
        $point = $params['point'];
        if(!$crm_member_id){
            return $service->send_user_error('100002','用户id必传');
        }
        if($crm_member_id && is_numeric($point)){
            $b2c_obj = app::get('b2c');
            $member_point_obj = $b2c_obj->model('member_point');
            $member_obj = $b2c_obj->model('members');
            $member = $member_obj->getList('member_lv_id,member_id',array('crm_member_id'=>$crm_member_id),0,-1,null,true);
            $member_id = $member[0]['member_id'];
            $data = array('point'=>$point);
            //开启会员等级按积分的话要更新用户等级
            if($b2c_obj->getConf('site.level_switch')== 0){
                $cur_member_lv_id = $member[0]['member_lv_id'];
                $member_lv_id = $member_point_obj->member_lv_chk($member_id,$cur_member_lv_id,$point);
                $data["member_lv_id"] = $member_lv_id;
            }
            $filter = array('member_id'=>$member_id);
            //这里只需更新point字段 为了防止底层update方法去走扩展字段getList方法走统一打查询接口出去
            if($member_obj->update($data,$filter)){
                //更新成功 记录推送明细
                $sdf_point = array(
                        'member_id'=> $member_id,
                        'point'=> $point,
                        'addtime'=> time(),
                        'reason'=> "响应积分相关更新",
                        'related_id'=> $member_id,
                        'status'=> true,
                );
                $member_point_obj->insert($sdf_point);
                return array("result"=>true,"member_id"=>$crm_member_id);
            }else{
                return array("result"=>false);
            }
        }
        return array("result"=>false);
    }

    private function format_point_data($pointData)
    {
        $userPassport = kernel::single('b2c_user_passport');
        $list = array();
        foreach( (array)$pointData as $k=>$row ){
            //获取到用户名，手机号, 邮箱
            $pam_colunms = $userPassport->userObject->get_pam_data('*',$row['member_id']);

            $member = $this->app->model('member')->getList('crm_member_id',array('member_id'=>$point_data['member_id']));
            $list[$k]['member_id'] = $member[0]['crm_member_id'];
            $list[$k]['point'] = $point_data['point'];
            $list[$k]['change_point'] = $point_data['change_point'];
            $list[$k]['type'] = '1';
            $list[$k]['point_desc'] = $point_data['reason'];
            $list[$k]['starttime'] = $point_data['addtime'];
            $list[$k]['valid'] = ($point_data['expiretime'] > time()) ? 'true':'false';
            $list[$k]['expiretime'] = $point_data['expiretime'];
            $list[$k]['related_id'] = $point_data['related_id'];
            $list[$k]['operator'] = $point_data['operator'];
            $list[$k]['addtime'] = $point_data['addtime'];
            $list[$k]['remark'] = $point_data['remark'];
            $list[$k]['shop_id'] = '';
        }
        return $list;
    }

}
