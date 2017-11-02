<?php
class express_ctl_admin_channel extends desktop_controller{
    //1.渠道能获取哪些快递单号
    //2.渠道获取到的快递单号哪些店铺能用。
    public function index(){
        $this->finder('express_mdl_channel', array(
            'actions'=>array(
                array('label' => '添加来源', 'href' => 'index.php?app=express&ctl=admin_channel&act=add','target' => 'dialog::{width:620,height:360,title:\'来源添加/编辑\'}'),
                array('label' => '启用', 'submit' => 'index.php?app=express&ctl=admin_channel&act=toStatus&status=true','target'=>'refresh'),
                array('label' => '关闭', 'submit' => 'index.php?app=express&ctl=admin_channel&act=toStatus&status=false','target'=>'refresh'),
            ),
            'title' => '电子面单来源',
            'use_buildin_recycle' => false,
            'use_buildin_setcol' => false,
        ));

        $html = <<<EOF
        <script>
              $$(".show_list").addEvent('click',function(e){
                  var billtype = this.get('billtype');
                  var channel_id = this.get('channel_id');
                  var t_url ='index.php?app=express&ctl=admin_waybill&act=findwaybill&channel_id='+channel_id+'&billtype='+billtype;
                  var url='index.php?app=desktop&act=alertpages&goto='+encodeURIComponent(t_url);
                  Ex_Loader('modedialog',function() {
                      new finderDialog(url,{width:1000,height:660,});
                  });
              });
        </script>
EOF;
        echo $html;exit;
    }

    public function add(){
        $this->_edit();
    }

    public function edit($channel_id){
        $this->_edit($channel_id);
    }
    
    private function _edit($channel_id=NULL){
        
        //银联小跟班申请开通
        $http_base = kernel::single('base_prism');
        $http_base->base_url= 'https://openapi.shopex.cn';
        $http_base->app_key = DEVELOPER_APP_KEY;
        $http_base->app_secret = DEVELOPER_APP_SECRET;
        
        if ($_GET["code"]){
            //根据code 获取token
            $params = array("code"=>$_GET["code"],"grant_type"=>"authorization_code");
            $auther_info_result = $http_base->post('/oauth/token',$params);
            $arr_auther_info_result = json_decode($auther_info_result,true);
            //这里先不管$arr_auther_info_result 跳转至银联小跟班信息提交页
            header('Location:'.GENBANAPPLY.'?from=ECStore');
        }
        
        if($channel_id){
            $channelObj = &$this->app->model("channel");
            $channel = $channelObj->dump($channel_id);
            if ($channel['channel_type'] == 'unionpay') {
                $unionpay_info = explode('|||', $channel['shop_id']);
                $channel['unionpay_uname'] = $unionpay_info[0];
                $channel['unionpay_password'] = $unionpay_info[1];
                $channel['pay_method'] = $unionpay_info[2];
                $channel['unionpay_month_code'] = $unionpay_info[3];
            }
        }

        //来源类型信息
        $funcObj = kernel::single('express_waybill_func');
        $channels = $funcObj->channels();
        $this->pagedata['channels'] = $channels;

        //物流公司信息
        if($channel['channel_type']) {
            $wlbObj = kernel::single('express_waybill_'.$channel['channel_type']);
            $logistics = $wlbObj->logistics();
            $this->pagedata['logistics'] = $logistics;
        }

        if ($channel['pay_method']) {
            $wlbObj = kernel::single('express_waybill_'.$channel['channel_type']);
            $pay_method = $wlbObj->pay_method();
            $this->pagedata['pay_method'] = $pay_method;
        }

        $this->pagedata['channel'] = $channel;
        
        //获取申请开通url
        $http_base->set_redirect_url();
        $http_base->set_oauth_view("onex_login");
        $this->pagedata['request_url'] = $http_base->get_authorize_url();
        
        $this->display("admin/channel/channel.html");
    }

    public function do_save(){
        $data = array();
        $data['name'] = $_POST['name'];
        $data['channel_type'] = $_POST['channel_type'];
        $channelObj = &$this->app->model('channel');

        if($data['channel_type'] == 'unionpay'){
            if ($_POST['channel_id']) {
                $channel = $channelObj->dump(array('channel_id' => $_POST['channel_id']));
                $sfinfo = explode('|||', $channel['shop_id']);
                $_POST['pay_method'] = $sfinfo[2];
                
            }
            $_POST['shop_id'] =  $_POST['unionpay_uname'].'|||'. $_POST['unionpay_password'] . '|||' . $_POST['pay_method'] . '|||' . $_POST['unionpay_month_code'];
            $data['shop_id'] = $_POST['shop_id'];

            $is_bind_unionpay = false;
            #检测是否已绑定银联
            base_kvstore::instance('b2c/bind/unionpay')->fetch('b2c_bind_unionpay', $is_bind_unionpay);
            if (!$is_bind_unionpay) {
                $bind_status = kernel::single('express_rpc_unionpay')->bind();
                if ( $bind_status == true) {
                    $data['bind_status'] = 'true';
                }
            }
        }

        if($_POST['channel_id']){
            //更新渠道
            $channelObj->update($data,array('channel_id'=>$_POST['channel_id']));
            $data['channel_id'] = $_POST['channel_id'];
        }else{
            if(!$_POST['shop_id']) {
                echo '请选择主店铺!';
                exit;
            }

            if(!$_POST['logistics_code']) {
                echo '请选择物流公司!';
                exit;
            }

            $filter = array(
                'shop_id' => $_POST['shop_id'],
                'logistics_code' => $_POST['logistics_code'],
            );

            if($data['channel_type'] == 'unionpay'){
                $filter['channel_type'] = 'unionpay';
                $filter['logistics_code'] = $_POST['logistics_code'];
                unset($filter['shop_id']);
            }
            $channel = $channelObj->dump($filter,'channel_id');
            if($channel) {
                echo '已经添加过相同来源，无需重复添加!';
                exit;
            }

            //添加渠道
            $data['shop_id'] = $_POST['shop_id']; //不允许更新
            $data['logistics_code'] = $_POST['logistics_code']; //不允许更新
            $data['create_time'] = time();
            $channelObj->insert($data);
        }

        echo "SUCC";
    }

    public function toStatus(){
        $this->begin('index.php?app=express&ctl=admin_channel&act=index');
        if($_GET['status'] && $_GET['status']=='true'){
            $data['status'] = 'true';
        }else{
            $data['status'] = 'false';
        }

        if($_POST['channel_id'] && is_array($_POST['channel_id'])){
            $filter = array('channel_id'=>$_POST['channel_id']);
        }elseif($_POST['isSelectedAll'] && $_POST['isSelectedAll'] == '_ALL_'){
            $filter = array();
        }else{
            $this->end(false, '操作失败。');
        }
        
        $channelObj = app::get('express')->model('channel');

        //关闭时判断是否有绑定的物流公司 如有则不能关闭
        if ($data['status'] == 'false'){
            $mdl_b2c_dlycorp = app::get('b2c')->model('dlycorp');
            //获取需要关闭的channel_ids数组
            $rs_close_channel = $channelObj->getList("*",$filter);
            $arr_channel_ids = array();
            $arr_rl_channel_id_name = array();
            foreach ($rs_close_channel as $var_c_c){
                $arr_channel_ids[] = $var_c_c["channel_id"];
                $arr_rl_channel_id_name[$var_c_c["channel_id"]] = $var_c_c["name"];
            }
            $rs_using = $mdl_b2c_dlycorp->getList("channel_id",array("channel_id"=>$arr_channel_ids,"tmpl_type"=>"electron","disabled"=>"false"));
            if (!empty($rs_using)){
                $arr_using_name = array();
                foreach ($rs_using as $var_u){
                    $arr_using_name[] = $arr_rl_channel_id_name[$var_u["channel_id"]];
                }
                $false_str = implode("、", $arr_using_name). "，已被物流公司绑定，不得关闭。";
                $this->end(false, $false_str);
            }
        }
        
        $channelObj->update($data,$filter);
        $this->end(true, '操作成功。');
    }

    public function getLogistics() {
        $type = $_POST['type'];
        $wlbObj = kernel::single('express_waybill_'.$type);
        $logistics = $wlbObj->logistics();
        $result = $logistics ? json_encode($logistics) : '';
        echo $result;
    }

    public function getPayMethod() {
        $type = $_POST['type'];
        $wlbObj = kernel::single('express_waybill_'.$type);
        $payMethondList = $wlbObj->pay_method();
        $result = $payMethondList ? json_encode($payMethondList) : '';
        echo $result;
    }

}