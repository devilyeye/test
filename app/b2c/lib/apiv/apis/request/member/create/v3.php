<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class b2c_apiv_apis_request_member_create_v3 extends b2c_apiv_extends_request
{
    var $method = 'store.user.add';
    var $callback = array();
    var $title = '添加用户接口 (同步)';
    var $timeout = 10;
    var $async = false;

    public function get_params($member_id)
    {
        $userObject = kernel::single('b2c_user_object');
        $data = $userObject->get_members_data(array('account'=>'*','members'=>'*'),$member_id);

        if($data['members']['area']){
            $arrArea = explode(':',$data['members']['area']);
            $area  = explode('/',$arrArea[1]);
        }

        if( isset($data['account']['local']) ){
            $login_name = $data['account']['local'];
        }elseif(isset($data['account']['email'])){
            $login_name = $data['account']['email'];
        }else{
            $login_name = $data['account']['mobile'];
        }
        $params['uid']       = $data['members']['member_id'];
        $params['user_name'] = $login_name;
        $params['nick_name'] = $data['members']['name'];
        $params['sex']       = $this->gender($data['members']['sex']);

        $params['location']  = array(
            'zip' => $data['members']['zip'],
            'address' => $data['members']['addr'],
            'city' => strval($area[1]),
            'state' => strval($area[0]),
            'district' => strval($area[2]),
        );
        $params['location'] = json_encode($params['location']);

        if($data['members']['b_year'] && $data['members']['b_month'] && $data['members']['b_day']){
            $birthday = $data['members']['b_year'].'-'.$data['members']['b_month'].'-'.$data['members']['b_day'];
        }
        $params['created'] = date('Y-m-d H:m:s',$data['members']['regtime']);
        $params['last_visit'] = date('Y-m-d H:m:s');
        $params['birthday'] = strval($birthday);
        $params['email'] = strval($data['account']['email']);
        $params['mobile'] = strval($data['account']['mobile']);

        $obj_policy = kernel::service("referrals.member_policy");

        if(is_object($obj_policy)){
            $referrals_members_info = $obj_policy->referrals_members_info($member_id);
            if(!empty( $referrals_members_info['referrals_code']))
            {
               $params['parent_code'] = $referrals_members_info['referrals_code'];
            }
        }
        
        //当符合条件注册赠送积分加入register_point、register_memo两个字段
        if(app::get('b2c')->getConf('site.get_policy.method') == 1){
            //不使用积分
        }else{
            $reg_active_setting = kernel::single('proregister_member_create')->registerActiveCheck();
            if($reg_active_setting && $reg_active_setting['getscore']){
                $params["register_point"] = $reg_active_setting['getscore'];
                $params["register_memo"] = "注册赠送积分";
            }
        }
        
        $params['tid'] = $member_id;//单据号

        return $params;
    }

    public function gender($gender){
        if($gender == '0'){//ecstore 0代表性别女
            return '2';//crm 2代表性别女
        }elseif($gender == '2'){//ecstore 性别为-
            return '1';//crm 默认性别为男
        }else{
            return $gender;
        }
    }
}
