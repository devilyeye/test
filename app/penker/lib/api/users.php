<?php
class penker_api_users{

    public $app;
    public function __construct($app)
    {
        $this->app = $app;
    }

    public function login(){
        kernel::single('base_session')->start();
        $penker = $this->app->model('bind');
        $arr_bind = $penker->getList();
        $pack = $_GET['pack'];

        $key = $arr_bind[0]['secret_key'];
        $iv = substr(md5($arr_bind[0]['node_id']),0,16);
        $params = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($pack), MCRYPT_MODE_CBC,$iv));
        $params = json_decode($params,true);

        $msg = 'fail!';
        if( is_array($params) ){
            if( isset($params['userinfo']) &&is_array($params['userinfo']) && !empty($params['userinfo']['openid']) ){
                $user_login = kernel::single('penker_service_user');

                $openid = $params['userinfo']['openid'];
                $_SESSION['weixin_u_nickname'] = $params['userinfo']['nickname'];
                $_SESSION['weixin_u_openid'] = $openid;
                $bindTagData = app::get('pam')->model('bind_tag')->getRow('tag_name,member_id',array('open_id'=>$openid));
                if( $bindTagData ){
                    $msg = $user_login->login($bindTagData['member_id']);
                }else{
                    $msg = $user_login->create($params['userinfo']);
                }
            }else{
                $msg = 'succ';
            }
        }else{
            $msg = 'params format error！';
            print_r($msg);
            exit();
        }

        if($msg == 'succ'){
            if( isset($params['product_id']) && !empty($params['product_id']) ){
                $product_id = $params['product_id'];
            }else{
                $msg = 'product_id is null';
            }

            if( isset($params['guide_identity']) && !empty($params['guide_identity']) ){
                $guide_identity = $params['guide_identity'];
            }else{
                $msg = 'guide_identity is null';
            }

            if( $msg != 'succ' ){
                print_r($msg);
                exit();
            }

            $this->gen_cookie($guide_identity);
            $url = app::get('wap')->router()->gen_url( array( 'app'=>'b2c','real'=>1,'ctl'=>'wap_product','args'=>array($product_id,'penker',$guide_identity)));
            header('Location: '.$url);
        }
        print_r($msg);
        exit();
    }
    private function gen_cookie($guide_identity){
        $path = kernel::base_url().'/';
        if( !$_COOKIE['penker'] ){
            setcookie('penker','true',0,$path);
        }

        if( !$_COOKIE['guide_identity'] ){
            setcookie('guide_identity',$guide_identity,0,$path);
        }
    }
}
