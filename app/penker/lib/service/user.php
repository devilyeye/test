<?php 
class penker_service_user{
    function __construct($app){
        $this->app = $app;
    }

    /*
     * 模拟登陆
     * @params member_id int 会员id
     * @return succ/fail
     */
    function login($member_id){
        $pam_member_mdl = app::get('pam')->model('members');
        $member_msg = $pam_member_mdl->getrow('*',array('member_id' => $member_id));
        $userObject = kernel::single('b2c_user_object');
        if( empty($member_msg) ){
            return 'fail!';
        }else{
            $userObject->set_member_session($member_id);
            $this->bind_member($member_id);
            return 'succ';
        }
    }
    function create($params){
        if( !is_array($params) || empty($params['openid']) ) return false;

        $pam_member_mdl = app::get('pam')->model('members');
        $frontpage = kernel::single('wap_frontpage');
        $userPassport = kernel::single('b2c_user_passport');
        $userObject = kernel::single('b2c_user_object');

        if( !$params['nickname'] ){
            $params['nickname'] = 'customer_'.time();
        }
        $login_name = $params['nickname'];
        //验证会员名是否可用
        $flag = $userPassport->check_signup_account( trim($login_name),$msg );

        if( !$flag ){
            $login_name .='_'.time();
        }
        $member_data = array(
            'login_name'=>$login_name,
            'login_password'=>'penker123',
            'psw_confirm'=>'penker123',
        );
        $saveData = array(
            'pam_account'=>$member_data,
            'license'=>'on',
            'response_json'=>ture,
        );
        $saveData = $userPassport->pre_signup_process($saveData);
        $saveData['b2c_members']['source'] = 'penker';

        if( $member_id = $userPassport->save_members($saveData) ){
            //设置session和cookie,做登录操作
            $userObject->set_member_session($member_id);
            $this->bind_member($member_id);

            foreach(kernel::servicelist('b2c_save_post_om') as $object) {
                $object->set_arr($member_id, 'member');
                $refer_url = $object->get_arr($member_id, 'member');
            }

            if( $member_rpc_object = kernel::service("b2c_member_rpc_sync") ) {
                $member_rpc_object->createActive($member_id);
            }

            foreach(kernel::servicelist('b2c_register_after') as $object) {
                $object->registerActive($member_id);
            }

            if(!empty($_SESSION['referrals_code'])){
                $obj_policy = kernel::service("referrals.member_policy");
                if(is_object($obj_policy))
                {
                    $obj_policy ->referrals_member($_SESSION['referrals_code'],$member_id);
                }
            }

            //保存微信绑定信息
            if( isset($params['openid']) ){
                $bindWeixinData = array(
                    'member_id' => $member_id,
                    'open_id' => $params['openid'],
                    'tag_name' => $params['nickname'],
                    'createtime' => time()
                );
                $flag = app::get('pam')->model('bind_tag')->save($bindWeixinData);
            }

            $data['member_id'] = $member_id;
            $data['uname'] = $saveData['pam_account']['login_account'];
            $data['passwd'] = $_POST['pam_account']['psw_confirm'];
            $data['email'] = $_POST['contact']['email'];
            $data['refer_url'] = $refer_url ? $refer_url : '';
            $data['is_frontend'] = true;
            $obj_account=app::get('b2c')->model('member_account');
            $obj_account->fireEvent('register',$data,$member_id);

            if( $flag ){
                return 'succ';
            }
        }
        return 'fail!';
    }

    function bind_member($member_id){
        if( empty($member_id) ) return false;

        $frontpage = kernel::single('wap_frontpage');
        $frontpage->bind_member($member_id);
    }
}
