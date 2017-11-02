<?php
class b2c_service_vcode{

    public function __construct($app){
        $this->app = $app;
    }

    public function status($username=""){
        //modified by zengxinwen 2016-1-21 加入 $username 判断
        if($username){
            if(app::get('b2c')->getConf('site.login_valide') == 'false'){
                if($_SESSION['error_count']['b2c'][$username]['count'] >= 3){
                    return 1;
                }else{
                    return 0;
                }
            }
        }else{
            if(app::get('b2c')->getConf('site.login_valide') == 'false'){
                if($_SESSION['error_count']['b2c']['count'] >= 3){
                    return 1;
                }else{
                    return 0;
                }
            }

        }
        return app::get('b2c')->getConf('site.login_valide') == 'true' ? 1 : 0;
    }

    public function set_error_count($username=""){
        //modified by zengxinwen 2016-1-21 加入 $username 判断
        if($username){
            if(isset($_SESSION['error_count']['b2c']['time']) && (time() - $_SESSION['error_count']['b2c']['time']<3600) ){
                $_SESSION['error_count']['b2c'][$username]['count'] += 1;
            }else{
                $_SESSION['error_count']['b2c']['time'] = time();
                $_SESSION['error_count']['b2c'][$username]['count'] = 1;
            }
        }else{
            if(isset($_SESSION['error_count']['b2c']['time']) && (time() - $_SESSION['error_count']['b2c']['time']<3600) ){
                $_SESSION['error_count']['b2c']['count'] += 1;
            }else{
                $_SESSION['error_count']['b2c']['time'] = time();
                $_SESSION['error_count']['b2c']['count'] = 1;
            }
        }

    }
}
?>
