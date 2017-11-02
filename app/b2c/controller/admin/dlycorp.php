<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class b2c_ctl_admin_dlycorp extends desktop_controller{

    var $workground = 'b2c_ctl_admin_system';
    
    public function __construct($app){
        parent::__construct($app);
        $this->ui = new base_component_ui($this);
        $this->app = $app;
        header("cache-control: no-store, no-cache, must-revalidate");
    }

    public function index(){
        $action = array('label'=>app::get('b2c')->_('添加物流公司'),'href'=>'index.php?app=b2c&ctl=admin_dlycorp&act=addnew','target'=>'dialog::{title:\''.app::get('b2c')->_('添加物流公司').'\',width:500,height:400}');
        $this->finder('b2c_mdl_dlycorp',array('title'=>app::get('b2c')->_('物流公司'),'actions'=>array($action),'use_buildin_new_dialog'=>false,'use_buildin_set_tag'=>false,'use_buildin_recycle'=>true,'use_buildin_export'=>false));
    }

    public function addnew()
    {
        if($_POST)
        {
            $this->begin();
            $dlycorp = $this->app->model('dlycorp');
            $arrdlcorp = $dlycorp->dump(array('name' => trim($_POST['name'])));
            if (!$arrdlcorp)
            {
                if (!$_POST['ordernum'])
                    $_POST['ordernum'] = 50;
                
                $_POST['ordernum'] = intval($_POST['ordernum']);
                if ($_POST['corp_code_other'])
                {
                    $_POST['corp_code'] = $_POST['corp_code_copy'];
                    unset($_POST['corp_code_other']);
                    unset($_POST['corp_code_copy']);
                }

                if($_POST['tmpl_type']=='normal'){
                    $_POST['prt_tmpl_id'] = $_POST['normal_tmpl_id'];
                    $_POST['channel_id'] = 0;
                }elseif($_POST['tmpl_type']=='electron'){
                    $_POST['prt_tmpl_id'] = $_POST['electron_tmpl_id'];
                    $_POST['channel_id'] = $_POST['electron_channel_id'];
                }

                $result = $dlycorp->save($_POST);
                $this->end($result, app::get('b2c')->_('物流公司添加成功！'));
            }
            else
            {
                $this->end(false, app::get('b2c')->_('该物流公司已经存在！'));
            }            
        }
        else
        {
            //获取电子面单来源渠道
            $channelObj = app::get("express")->model('channel');
            $channel = $channelObj->getList("*",array('status'=>'true'));
            foreach($channel as $key=>$val) {
                $channelType[$val['channel_id']] = $val['logistics_code'];
                unset($val);
            }
            $this->pagedata['electronchannel'] = $channel;
            $this->pagedata['channelType'] = json_encode($channelType);

            //获取打印模板
            $templateObj = app::get("express")->model('print_tmpl');
            $templates = $templateObj->getList("prt_tmpl_id,prt_tmpl_title,prt_tmpl_type",array('shortcut'=>'true'));
            $normalTmpl = $electronTmpl = array();
            foreach($templates as $val){
                if ($val['prt_tmpl_type']=='normal') {
                    $normalTmpl[] = $val;
                }elseif($val['prt_tmpl_type']=='electron'){
                    $electronTmpl[] = $val;
                }
                unset($val);
            }
            $this->pagedata['normalTmpl'] = $normalTmpl;
            $this->pagedata['electronTmpl'] = $electronTmpl;

            $this->display('admin/delivery/dlycorp_new.html');
        }
    }
    
    public function save()
    {
        if($_POST)
        {
            $this->begin();
            $dlycorp = $this->app->model('dlycorp');
            if (!$_POST['ordernum'])
                $_POST['ordernum'] = 50;
            $_POST['ordernum'] = intval($_POST['ordernum']);
            if ($_POST['corp_code_other'])
            {
                $_POST['corp_code'] = $_POST['corp_code_copy'];
                unset($_POST['corp_code_other']);
                unset($_POST['corp_code_copy']);
            }

            if($_POST['tmpl_type']=='normal'){
                $_POST['prt_tmpl_id'] = $_POST['normal_tmpl_id'];
                $_POST['channel_id'] = 0;
            }elseif($_POST['tmpl_type']=='electron'){
                $_POST['prt_tmpl_id'] = $_POST['electron_tmpl_id'];
                $_POST['channel_id'] = $_POST['electron_channel_id'];
            }

            $result = $dlycorp->save($_POST);
            $this->end($result, app::get('b2c')->_('物流公司修改成功！'));
        }
    }
    
    public function showEdit()
    {
        $dly_corp = $this->app->model('dlycorp');
        $rows = $dly_corp->getList('*', array('corp_id'=>$_GET['corp_id']));
        $row = $rows[0];
        $this->pagedata['dlycrop'] = $row;

        //获取电子面单来源渠道
        $channelObj = app::get("express")->model('channel');
        $channel = $channelObj->getList("*",array('status'=>'true'));
        foreach($channel as $key=>$val) {
            $channelType[$val['channel_id']] = $val['logistics_code'];
            unset($val);
        }
        $this->pagedata['electronchannel'] = $channel;
        $this->pagedata['channelType'] = json_encode($channelType);

        //获取打印模板
        $templateObj = app::get("express")->model('print_tmpl');
        $templates = $templateObj->getList("prt_tmpl_id,prt_tmpl_title,prt_tmpl_type",array('shortcut'=>'true'));
        $normalTmpl = $electronTmpl = array();
        foreach($templates as $val){
            if ($val['prt_tmpl_type']=='normal') {
                $normalTmpl[] = $val;
            }elseif($val['prt_tmpl_type']=='electron'){
                $electronTmpl[] = $val;
            }
            unset($val);
        }
        $this->pagedata['normalTmpl'] = $normalTmpl;
        $this->pagedata['electronTmpl'] = $electronTmpl;

        $this->display('admin/delivery/dlycrop_edit.html');
    }
}
