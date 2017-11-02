<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class express_ctl_admin_delivery_printer extends desktop_controller{
    public $workground = 'ectools_ctl_admin_order';

    public function __construct($app)
    {
        parent::__construct($app);
        header("cache-control: no-store, no-cache, must-revalidate");
        define('DPGB_TMP_MODE',1);
        define('DPGB_HOME_MODE',2);
        $this->pagedata['dpi'] = intval(app::get('b2c')->getConf('system.clientdpi'));
        if(!$this->pagedata['dpi']){
            $this->pagedata['dpi'] = 96;
        }
        $this->model = $this->app->model('print_tmpl');
        $this->o = app::get('image')->model('image_attach');
        $this->obj = $this;
    }

    public function index()
    {
        $this->finder('express_mdl_print_tmpl',array(
            'title'=>app::get('express')->_('快递单模板'),
            'actions'=>array(
                            array('label'=>app::get('express')->_('添加普通面单'),'icon'=>'add.gif','target'=>'_blank','href'=>'index.php?app=express&ctl=admin_delivery_printer&act=add_tmpl'),
                            array('label'=>app::get('express')->_('添加单子面单'),'icon'=>'add.gif','target'=>'_blank','href'=>'index.php?app=express&ctl=admin_delivery_printer&act=add_electron_tmpl'),
                            array('label'=>app::get('express')->_('导入模版'),'icon'=>'add.gif','target'=>'dialog::{title:\''.app::get('express')->_('导入模版').'\'}','href'=>'index.php?app=express&ctl=admin_delivery_printer&act=import'),
                        ),'use_buildin_set_tag'=>false,'use_buildin_recycle'=>true,'use_buildin_filter'=>false,
            ));
    }

    public function getCorpInfo(){
        if(empty($_POST['corp_id'])){
            echo json_encode(array('error'=>1));exit;
        }

        $corpObj = app::get('b2c')->model('dlycorp');
        $corpInfo = $corpObj->getList('*',array('corp_id'=>$_POST['corp_id']));

        //获取订单是否已经打印过其他物流公司的电子面单
        $orderBillObj = app::get('express')->model('order_bill');
        $orderBillInfo = $orderBillObj->getList('*',array('order_id'=>$_POST['order_id'],'type'=>1,'status'=>0));
        if($orderBillInfo){
            if($orderBillInfo[0]['logi_id'] != $_POST['corp_id']){
                $corpInfo[0]['old_logi_no'] = $orderBillInfo[0]['logi_no'];
                $corpInfo[0]['old_logi_id'] = $orderBillInfo[0]['logi_id'];

                $oldCorpInfo =$corpObj->dump($orderBillInfo[0]['logi_id'],'name');
                $corpInfo[0]['old_logi_name'] = $oldCorpInfo['name'];
            }
        }

        if($corpInfo){
            echo json_encode($corpInfo[0]);exit;
        }else{
            echo json_encode(array('error'=>1));exit;
        }
    }

    public function do_print(){
        if($_POST['tmpl_type'] == 'normal'){
            $this->do_normal_print();
        }elseif($_POST['tmpl_type'] == 'electron'){
            $this->do_electron_print();
        }
    }

    public function jumpToPrint(){
        $orderObj = app::get('b2c')->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $orderInfo = $orderObj->dump($_POST['order_id'], '*', $subsdf);

        $_POST['order']['order_id'] = $orderInfo['order_id'];
        $_POST['order']['order_count'] = $orderInfo['itemnum'];
        $_POST['order']['ship_time'] = $orderInfo['consignee']['r_time'];
        $_POST['order']['order_price'] = $orderInfo['total_amount'];
        $_POST['order']['order_weight'] = $orderInfo['weight'];
        $_POST['order']['ship_name'] = $orderInfo['consignee']['name'];
        $_POST['order']['ship_area'] = $orderInfo['consignee']['area'];
        $_POST['order']['ship_zip'] = $orderInfo['consignee']['zip'];
        $_POST['order']['ship_addr'] = $orderInfo['consignee']['addr'];
        $_POST['order']['ship_name'] = $orderInfo['consignee']['name'];
        $_POST['order']['ship_mobile'] = $orderInfo['consignee']['mobile'];
        $_POST['order']['ship_tel'] = $orderInfo['consignee']['telephone'];
        $_POST['order']['order_memo'] = $orderInfo['memo'];

        $corpObj = app::get('b2c')->model('dlycorp');
        $corpInfo = $corpObj->getList('channel_id,prt_tmpl_id',array('corp_id'=>$_POST['corp_id']));
        $_POST['dly_tmpl_id'] = $corpInfo[0]['prt_tmpl_id'];
        $_POST['channel_id'] = $corpInfo[0]['channel_id'];

        $this->do_electron_print();
    }

    private function do_normal_print(){
        $this->get_delivery_info($_POST,$data);

        $aData = $this->o->getList('image_id',array('target_id' => $_POST['dly_tmpl_id'],'target_type' => 'print_tmpl'));
        $image_id = $aData[0]['image_id'];
        $this->pagedata['bg_id'] = $image_id;
        $url = $this->show_bg_picture(1,$image_id);

        // addnew
        $data['order_id'] = $_POST['order']['order_id'];
        $data['order_print'] = $data['order_id'];
        $oOrder = app::get('b2c')->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $goodsItems = $oOrder->dump($data['order_id'],'*',$subsdf);
        $this->get_order_info($goodsItems,$data);
        $data['text'] = '自定义的内容';
        $xmltool = kernel::single('site_utility_xml');
        $mydata['item'] = $data;

        $this->pagedata['prt_tmpl'] = $this->model->dump($_POST['dly_tmpl_id']);
        $this->pagedata['templateData'] = json_encode(array(
                'name'=>$this->pagedata['prt_tmpl']['prt_tmpl_title'],
                'enable'=>($this->pagedata['prt_tmpl']['shortcut']=='true')?'1':'0',
                'size'=>array(
                    'width'=>$this->pagedata['prt_tmpl']['prt_tmpl_width'],
                    'height'=>$this->pagedata['prt_tmpl']['prt_tmpl_height'],
                    ),
                'imgUrl'=>$url,
                'dpi'=>96,
                'offset'=>array(
                    'x'=>$this->pagedata['prt_tmpl']['prt_tmpl_offsetx'],
                    'y'=>$this->pagedata['prt_tmpl']['prt_tmpl_offsety'],
                ),
                'ptItem'=>json_decode($this->pagedata['prt_tmpl']['prt_tmpl_data'],true),
            ));

        $this->pagedata['testTemplateData'] = json_encode(array(
            array(
                'label'=>app::get('site')->getConf('site.name'),
                'data'=>'shop_name',
            ),
            array(
                'label'=>'√',
                'data'=>'tick',
            ),
            array(
                'label'=>$data['ship_name'],
                'data'=>'ship_name',
            ),
            array(
                'label'=>$data['ship_addr'],
                'data'=>'ship_addr',
            ),
            array(
                'label'=>$data['ship_tel'],
                'data'=>'ship_tel',
            ),
            array(
                'label'=>$data['ship_mobile'],
                'data'=>'ship_mobile',
            ),
            array(
                'label'=>$goodsItems['consignee']['r_time'],
                'data'=>'ship_time',
            ),
            array(
                'label'=>$data['ship_zip'],
                'data'=>'ship_zip',
            ),
            array(
                'label'=>$data['ship_area_0'],
                'data'=>'ship_area_0',
            ),
            array(
                'label'=>$data['ship_area_1'],
                'data'=>'ship_area_1',
            ),
            array(
                'label'=>$data['ship_area_2'],
                'data'=>'ship_area_2',
            ),
            array(
                'label'=>$data['ship_addr'],
                'data'=>'ship_addr',
            ),
            array(
                'label'=>$data['order_count'],
                'data'=>'order_count',
            ),
            array(
                'label'=>$data['order_memo'],
                'data'=>'order_memo',
            ),
            array(
                'label'=>$data['order_count'],
                'data'=>'order_count',
            ),
            array(
                'label'=>$data['order_weight'],
                'data'=>'order_weight',
            ),
            array(
                'label'=>$data['order_price'],
                'data'=>'order_price',
            ),
            array(
                'label'=>$data['text'],
                'data'=>'text',
            ),
            array(
                'label'=>$data['dly_area_0'],
                'data'=>'dly_area_0',
            ),
            array(
                'label'=>$data['dly_area_1'],
                'data'=>'dly_area_1',
            ),
            array(
                'label'=>$data['dly_area_2'],
                'data'=>'dly_area_2',
            ),
            array(
                'label'=>$data['dly_address'],
                'data'=>'dly_address',
            ),
            array(
                'label'=>$data['dly_tel'],
                'data'=>'dly_tel',
            ),
            array(
                'label'=>$data['dly_mobile'],
                'data'=>'dly_mobile',
            ),
            array(
                'label'=>$data['dly_zip'],
                'data'=>'dly_zip',
            ),
            array(
                'label'=>$data['date_y'],
                'data'=>'date_y',
            ),
            array(
                'label'=>$data['date_m'],
                'data'=>'date_m',
            ),
            array(
                'label'=>$data['date_d'],
                'data'=>'date_d',
            ),
            array(
                'label'=>$data['order_name'],
                'data'=>'order_name',
            ),
            array(
                  'label'=>str_replace('&nbsp;', ' ', $data['order_name_a']),
                'data'=>'order_name_a',
            ),
            array(
                  'label'=>str_replace('&nbsp;', ' ', $data['order_name_as']),
                'data'=>'order_name_as',
            ),
            array(
                  'label'=>str_replace('&nsbsp;', ' ', $data['order_name_ab']),
                'data'=>'order_name_ab',
            ),
            array(
                  'label' => (!empty($data['dly_name']) ? $data['dly_name'] : ' '),
                  'data' => 'dly_name',
                  ),
            array(
                  'label' => $data['order_id'],
                  'data' => 'order_id',
                  ),

        ));

        $this->pagedata['res_url'] = $this->app->res_url;
        $this->singlepage('admin/delivery/center/printer.html');
    }

    private function get_delivery_info($arr_post,&$data)
    {
        $obj_dly_center = $this->app->model('dly_center');
        $dly_center = $obj_dly_center->dump($arr_post['dly_center']);
        $data['dly_name'] = $dly_center['uname'];

        list($pkg,$regions,$region_id) = explode(':',$arr_post['order']['ship_area']);
        foreach(explode('/',$regions) as $i=>$region){
            $data['ship_area_'.$i]= $region;
        }

        if($dly_center['region']){
            list($pkg,$regions,$region_id) = explode(':',$dly_center['region']);
            foreach(explode('/',$regions) as $i=>$region){
                $data['dly_area_'.$i]= $region;
            }
        }

        $data['dly_address']=$dly_center['address'];
        $data['dly_tel']=$dly_center['phone'] ? $dly_center['phone'] : 0;
        $data['dly_mobile']=$dly_center['cellphone'] ? $dly_center['cellphone'] : 0;
        $data['dly_zip']=$dly_center['zip']?$dly_center['zip']:0;

        $t = time()+($GLOBALS['user_timezone']-SERVER_TIMEZONE)*3600;
        $data['date_y']=date('Y',$t);
        $data['date_m']=date('m',$t);
        $data['date_d']=date('d',$t);

        $data['order_memo'] = $_POST['order']['order_memo'];

        unset($data['ship_area']);
    }

    private function get_order_info($arr_order,&$data)
    {
        $num = 0;
        $weight = 0;
        $math = kernel::single('ectools_math');
        if ($arr_order['member_id'])
        {
            $oMember = app::get('b2c')->model('members');
            $aMem = $oMember->dump($arr_order['member_id'],'*',array(':account@pam'=>array('*')));
            if(!$aMem){
                $data['member_name'] = app::get('express')->_('非会员顾客!');
            }
            else{
                $data['member_name'] = $aMem['pam_account']['login_name'];
            }
        }
        else{
            $data['member_name'] = app::get('express')->_('非会员顾客');
        }

        if ($arr_order)
        {
            $oProduct = app::get('b2c')->model('products');
            $order_item = app::get('b2c')->model('order_items');
            $data['ship_name']   = $arr_order['consignee']['name'];
            $data['ship_addr']   = $arr_order['consignee']['addr'];
            $data['ship_tel']    = $arr_order['consignee']['telephone']?$arr_order['consignee']['telephone']:0;
            $data['ship_mobile'] = $arr_order['consignee']['mobile']?$arr_order['consignee']['mobile']:0;
            $data['ship_zip']    = $arr_order['consignee']['zip']?$arr_order['consignee']['zip']:0;
            $data['order_memo'] || ( $data['order_memo']  = $arr_order['memo']?$arr_order['memo']:'订单缺省备注');
            $i=0;
            // 所有的goods type 处理的服务的初始化.
            $arr_service_goods_type_obj = array();
            $arr_service_goods_type = kernel::servicelist('order_goodstype_operation');
            foreach ($arr_service_goods_type as $obj_service_goods_type)
            {
                $goods_types = $obj_service_goods_type->get_goods_type();
                $arr_service_goods_type_obj[$goods_types] = $obj_service_goods_type;
            }
            foreach ($arr_order['order_objects'] as $k=>$item)
            {
                if ($item['obj_type'] != 'goods')
                {
                    if ($item['obj_type'] == 'gift')
                    {
                        foreach ((array)$item['order_items'] as $key=> $val)
                        {
                            if (!$val['products'])
                            {
                                $tmp = $order_item->getList('*', array('item_id'=>$val['item_id']));
                                $val['products']['bn'] = $tmp[0]['bn'];
                                $val['products']['spec_info'] = $tmp[0]['bn'];
                            }

                            $arr_service_goods_type_obj[$item['obj_type']]->get_default_dly_order_info($val,$data);

                        }
                    }
                    else
                    {
                        $arr_service_goods_type_obj[$item['obj_type']]->get_default_dly_order_info($item,$data);
                    }
                }
                else
                {
                    foreach ((array)$item['order_items'] as $key=> $val)
                    {
                        if ($val['item_type'] == "product" || $val['item_type'] == "ajunct")
                        {
                            if ($val['item_type'] == "product")
                                $val['item_type'] = 'goods';

                            if (!$val['products'])
                            {
                                $tmp = $order_item->getList('*', array('item_id'=>$val['item_id']));
                                $val['products']['bn'] = $tmp[0]['bn'];
                                $val['products']['spec_info'] = $tmp[0]['bn'];
                            }

                            $arr_service_goods_type_obj[$val['item_type']]->get_default_dly_order_info($val,$data);

                        }
                        else
                        {
                            if (!$val['products'])
                            {
                                $tmp = $order_item->getList('*', array('item_id'=>$val['item_id']));
                                $val['products']['bn'] = $tmp[0]['bn'];
                                $val['products']['spec_info'] = $tmp[0]['bn'];
                            }

                            $arr_service_goods_type_obj[$val['item_type']]->get_default_dly_order_info($val,$data);
                        }
                        $weight = $math->number_plus(array($weight, $val['weight']));
                        $num = $math->number_plus(array($num, $val['quantity']));
                    }
                }
            }
        }
        $data['order_count'] = $num;
        $data['order_weight'] = $weight;
        $data['order_price'] = $arr_order['cur_amount'];
    }

    public function add_tmpl($image_id=null)
    {
        $this->_fontlist();
        $this->pagedata['tmpl'] = $this->model->dump($tmpl_id);
        $this->pagedata['res_url'] = $this->app->res_url;

        $url = $this->show_bg_picture(1,$image_id);
        $this->pagedata['templateData'] = json_encode(array(
            'name'=>'',
            'enable'=>'1',
            'size'=>array(
                'width'=>'240',
                'height'=>'158',
                ),
            'imgUrl'=>$url,
            'dpi'=>96,
            'offset'=>array(
                'x'=>'0',
                'y'=>'0',
            ),
            'ptItem'=>array(),
        ));

        $this->pagedata['save_action'] = 'add_save';
        $this->singlepage('admin/printer/dly_printer_editor.html');
    }

    /**
     * 添加快递单模版
     * @param null
     * @return null
     */
    public function add_save()
    {
        $o = app::get('image')->model('image_attach');
        $this->begin('javascript:opener.finderGroup["'.$_POST['finder_id'].'"].refresh();window.close();');

        if (!$_POST)
            $this->end(false,app::get('express')->_('需要添加的信息不存在！'));

        $tmpl_data = array();
        $tmpl_data['prt_tmpl_offsety'] = floatval($_POST['offset']['y']);
        $tmpl_data['prt_tmpl_offsetx'] = floatval($_POST['offset']['x']);
        $tmpl_data['shortcut'] = $_POST['enable'];
        $tmpl_data['prt_tmpl_title'] = $_POST['name'];
        $tmpl_data['prt_tmpl_height'] = $_POST['size']['height'];
        $tmpl_data['prt_tmpl_width'] = $_POST['size']['width'];
        $ptItem = $_POST['ptItem'];
        if (!empty($ptItem)){
            foreach($ptItem as $key=>$val){
                $ptItem[$key]['tilt'] = ($val['tilt'] =='false')?false:true;
                $ptItem[$key]['bold'] = ($val['bold'] =='false')?false:true;
            }
        }
        $tmpl_data['prt_tmpl_data'] = json_encode($ptItem);
        $tpl_id = $this->model->insert($tmpl_data);
        if (!$tpl_id)
            $this->end(false, app::get('express')->_('添加快递单模版失败！'));
        if(isset($_POST['prt_tmpl_id']) && $_POST['prt_tmpl_id'] != ''){
            $old_tpl_id = $_POST['prt_tmpl_id'];
            $aData = $o->getList('attach_id,image_id',array('target_id' => $old_tpl_id,'target_type' => 'print_tmpl'));
            $_POST['tmp_bg'] = $aData[0]['image_id'];
        }

        if (isset($_POST['tmp_bg']) && $_POST['tmp_bg'])
        {
            $sdf = array(
                'attach_id' => $attach_id?$attach_id:'',
                'target_id' => $tpl_id,
                'target_type' => 'print_tmpl',
                'image_id' => $_POST['tmp_bg'],
                'last_modified' => time(),
            );
            if (!$o->save($sdf))
                $this->end(false, app::get('express')->_('添加快递单模版背景失败！'));
        }

        $this->end(true,app::get('express')->_('添加快递单模版成功！'));
    }

    /**
     * 修改快递单模版
     * @param null
     * @return null
     */
    public function modify_save()
    {
        $o = app::get('image')->model('image_attach');
        $this->begin('javascript:opener.finderGroup["'.$_POST['finder_id'].'"].refresh();window.close();');

        if (!$_POST['prt_tmpl_id'])
        {
            $this->end(false,app::get('express')->_('要修改的快递单模版不存在！'));
        }
        else
        {
            $tmpl_data = array();
            $tmpl_data['prt_tmpl_id'] = $_POST['prt_tmpl_id'];
            $tmpl_data['prt_tmpl_offsety'] = floatval($_POST['offset']['y']);
            $tmpl_data['prt_tmpl_offsetx'] = floatval($_POST['offset']['x']);
            $tmpl_data['shortcut'] = $_POST['enable'];
            $tmpl_data['prt_tmpl_title'] = $_POST['name'];
            $tmpl_data['prt_tmpl_height'] = $_POST['size']['height'];
            $tmpl_data['prt_tmpl_width'] = $_POST['size']['width'];
            $ptItem = $_POST['ptItem'];
            if(!empty($ptItem)){
                foreach($ptItem as $key=>$val){
                    $ptItem[$key]['tilt'] = ($val['tilt'] =='false')?false:true;
                    $ptItem[$key]['bold'] = ($val['bold'] =='false')?false:true;
                }
            }
            $tmpl_data['prt_tmpl_data'] = json_encode($ptItem);

            if ($this->model->update($tmpl_data,array('prt_tmpl_id'=>$_POST['prt_tmpl_id']))){
                $tpl_id = $_POST['prt_tmpl_id'];
                $aData = $o->getList('attach_id',array('target_id' => $tpl_id,'target_type' => 'print_tmpl'));
                $attach_id = $aData[0]['attach_id'];
            }else{
                $tpl_id = false;
            }
        }

        if (isset($_POST['tmp_bg']) && $_POST['tmp_bg'])
        {
            $sdf = array(
                'attach_id' => $attach_id?$attach_id:'',
                'target_id' => $tpl_id,
                'target_type' => 'print_tmpl',
                'image_id' => $_POST['tmp_bg'],
                'last_modified' => time(),
            );
            if (!$o->save($sdf))
                $this->end(false, app::get('express')->_('修改快递单模版背景失败！'));
        }

        $this->end(true,app::get('express')->_('修改快递单模版成功！'));
    }

    /**
     * 显示编辑快递单模版的页面
     * @param string 模版id
     * @return null
     */
    public function edit_tmpl($tmpl_id)
    {
        $this->pagedata['tmpl'] = $this->model->dump($tmpl_id);
        $this->pagedata['res_url'] = $this->app->res_url;

        if($this->pagedata['tmpl']){
            $aData = $this->o->getList('image_id',array('target_id' => $tmpl_id,'target_type' => 'print_tmpl'));
            $image_id = $aData[0]['image_id'];
            $this->_fontlist();
            $url = $this->show_bg_picture(1,$image_id);
            $this->pagedata['save_action'] = 'modify_save';
            $this->pagedata['templateData'] = json_encode(array(
                'name'=>$this->pagedata['tmpl']['prt_tmpl_title'],
                'enable'=>($this->pagedata['tmpl']['shortcut']=='true')?'1':'0',
                'size'=>array(
                    'width'=>$this->pagedata['tmpl']['prt_tmpl_width'],
                    'height'=>$this->pagedata['tmpl']['prt_tmpl_height'],
                    ),
                'imgUrl'=>$url,
                'dpi'=>96,
                'offset'=>array(
                    'x'=>$this->pagedata['tmpl']['prt_tmpl_offsetx'],
                    'y'=>$this->pagedata['tmpl']['prt_tmpl_offsety'],
                ),
                'ptItem'=>json_decode($this->pagedata['tmpl']['prt_tmpl_data'],true),
            ));
            $this->singlepage('admin/printer/dly_printer_editor.html');
        }else{
            echo "<div class='notice'>ERROR ID</div>";
        }
    }

    public function add_same($tmpl_id)
    {
        $this->pagedata['tmpl'] = $this->model->dump($tmpl_id);
        $this->pagedata['res_url'] = $this->app->res_url;
        $this->pagedata['tmpl_id'] = $tmpl_id;
        if($this->pagedata['tmpl']){
            //unset($this->pagedata['tmpl']['prt_tmpl_id']);
            $aData = $this->o->getList('image_id',array('target_id' => $tmpl_id,'target_type' => 'print_tmpl'));
            $image_id = $aData[0]['image_id'];
            $this->_fontlist();
            if($image_id){
                $this->pagedata['image_id'] = $image_id;
            }
            $url = $this->show_bg_picture(1,$image_id);
            $this->pagedata['tmpl_bg'] = $url;
            $this->pagedata['save_action'] = 'add_save';
            $tmpl = array(
                'name'=>$this->pagedata['tmpl']['prt_tmpl_title'],
                'enable'=>($this->pagedata['tmpl']['shortcut']=='true')?'1':'0',
                'size'=>array(
                    'width'=>$this->pagedata['tmpl']['prt_tmpl_width'],
                    'height'=>$this->pagedata['tmpl']['prt_tmpl_height'],
                ),
                'imgUrl'=>$url,
                'dpi'=>96,
                'offset'=>array(
                    'x'=>$this->pagedata['tmpl']['prt_tmpl_offsetx'],
                    'y'=>$this->pagedata['tmpl']['prt_tmpl_offsety'],
                ),
                'ptItem'=>json_decode($this->pagedata['tmpl']['prt_tmpl_data'],true),
            );
            $this->pagedata['templateData'] = json_encode($tmpl);
            $this->singlepage('admin/printer/dly_printer_editor.html');
        }else{
                 echo "<div class='notice'>ERROR ID</div>";
        }
    }

    function print_test(){
        $this->pagedata['dpi'] = 96;
        $o = app::get('image')->model('image_attach');

        if($_POST['tmp_bg']){
            $this->pagedata['bg_id'] = $_POST['tmp_bg'];
        }else if($_POST['prt_tmpl_id']){
            $tpl_id = $_POST['prt_tmpl_id'];
            $aData = $o->getList('image_id',array('target_id' => $tpl_id,'target_type' => 'print_tmpl'));
            $this->pagedata['bg_id'] = $aData[0]['image_id'];
        }
        $this->pagedata['res_url'] = $this->app->res_url;

        $this->display('admin/printer/dly_print_test.html');
    }

    function import(){
        $this->display('admin/printer/dly_printer_import.html');
    }

    function do_upload_pkg(){
        $this->begin();
        $file = $_FILES['package'];
        $res = kernel::single('express_print_tmpl')->upload_tmpl($file, $err_msg);
        if($res){
            $this->end(true, app::get('express')->_('上传成功！'));
        }else{
            $this->end(false, app::get('express')->_($err_msg));
        }
    }

    public function upload_bg($printer_id=0,$type=1){
        $this->pagedata['dly_printer_id'] = $printer_id;
        $this->pagedata['print_type'] = $type;
        $this->display('admin/printer/dly_printer_uploadbg.html');
    }

    public function do_upload_bg(){
        $url = $this->show_bg_picture(1,$_POST['background']);
        if($_POST['print_type'] == 1){
            echo '<script>
            window.pt.replaceBackground("'.$url.'");
            window.pt.setBgID("'.$_POST['background'].'");
            window.pt.dlg.close();
            </script>';
        }else{
            $pos=strpos($url,'?');
            $url=substr($url,0,$pos);
            list($width, $height) = getimagesize($url);
            $pager_width = intval($width*25.4/96);
            $pager_height = intval($height*25.4/96);
            echo '<script>
            parent.$("template_width").value = "'.$pager_width.'";
            parent.$("template_height").value = "'.$pager_height.'";
            parent.embed1.setStyles({width:'.($width+30) .',height:'.($height+30).',});
            parent.embed1.setbackground("'.$url.'");
            parent.dlg.close();
            </script>';
        }
    }

    function download($tmpl_id){
        $tmpl = $this->model->dump($tmpl_id);
        $tar = kernel::single('base_tar');
        $tar->addFile('info',serialize($tmpl));
        $aData = $this->o->getList('image_id',array('target_id' => $tmpl_id,'target_type' => 'print_tmpl'));
        $image_id = $aData[0]['image_id'];

        if($bg = $this->show_bg_picture(1,$image_id)){
            $tar->addFile('background.jpg',file_get_contents($bg));
        }

        $charset = kernel::single('base_charset');
        $name = $charset->utf2local($tmpl['prt_tmpl_title'],'zh');
        @set_time_limit(0);
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header('Content-type: application/octet-stream');
        header('Content-type: application/force-download');
        header('Content-Disposition: attachment; filename="'.$name.'.dtp"');
        $tar->getTar('output');
    }

    public function done_upload_bg($rs,$file){
        if($rs){
            $url = 'index.php?app=express&ctl=admin_delivery_printer&act=show_bg_picture&p[0]='.$rs.'&p[1]='.$file;
            echo '<script>
                if($("dly_printer_bg")){
                    $("dly_printer_bg").value = "'.$file.'";
        }else{
              new Element("input",{id:"dly_printer_bg",type:"hidden",name:"tmp_bg",value:"__none__"}).inject("dly_printer_form");
        }

        window.printer_editor.dlg.close();
        window.printer_editor.setPicture("'.$url.'");
            </script>';
        }else{
            echo 'Error on upload:'.$file;
        }
    }

    public function show_picture($mode, $image_id)
    {
        readfile($this->show_bg_picture($mode, $image_id));exit;
    }

    public function show_bg_picture($mode,$file){
        $obj_storager = kernel::single("base_storager");
        $str_file = $obj_storager->image_path($file);
        return $str_file;
    }

    function _fontlist(){
        $default_font = array(
            array(
                'label'=>'宋体',
                'data'=>'宋体',
            ),
            array(
                'label'=>'黑体',
                'data'=>'黑体',
            ),
            array(
                'label'=>'Arial',
                'data'=>'Arial',
            ),
            array(
                'label'=>'Tahoma',
                'data'=>'Tahoma',
            ),
            array(
                'label'=>'Times New Roman',
                'data'=>'Times New Roman',
            ),
            array(
                'label'=>'Vrinda',
                'data'=>'Vrinda',
            ),
            array(
                'label'=>'Verdana',
                'data'=>'Verdana',
            ),
            array(
                'label'=>'Serif',
                'data'=>'Serif',
            ),
            array(
                'label'=>'Cursive',
                'data'=>'Cursive',
            ),
            array(
                'label'=>'Fantasy',
                'data'=>'Fantasy',
            ),
            array(
                'label'=>'Sans-Serif',
                'data'=>'Sans-Serif',
            ),
        );
        foreach ($default_font as $ft_item){
            $this->pagedata['printData']['fontItem'][] = $ft_item;
        }
        if(PRINTER_FONTS){
            $font = explode("|",PRINTER_FONTS);
            foreach ($font as $ft_item){
                $this->pagedata['printData']['fontItem'][] = array(
                    'label'=>$ft_item,
                    'data'=>$ft_item
                );
            }
        }
        $elements = $this->model->getElements();
        foreach ((array)$elements as $key=>$ele_item){
            $this->pagedata['printData']['printItem'][] = array(
                'label'=>$ele_item,
                'data'=>$key
            );
        }

        $this->pagedata['printData'] = json_encode($this->pagedata['printData']);
    }

    public function add_electron_tmpl(){
        $this->_edit($template_id);
    }

    public function edit_electron_tmpl($template_id){
        $this->_edit($template_id);
    }

    private function _edit($template_id=NULL){
        $elements = kernel::single('express_print_elements')->defaultElements();
        if($template_id){
            $template = $this->model->dump($template_id);
            $this->pagedata['title'] = '编辑模板';
        } else {
            $template = array(
                'prt_tmpl_width' => 100,
                'prt_tmpl_height' => 150,
                'prt_tmpl_type' => 'electron',
            );
            $this->pagedata['title'] = '新增模板';
        }

        $this->pagedata['tmpl'] = $template;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['dpi'] = 96;
        $this->pagedata['uniqid'] = uniqid();
        $this->pagedata['userAgent'] = $this->getUserAgent();
        $this->pagedata['elements'] = $elements;
        $this->singlepage('admin/printer/electron/template.html');
    }

    function save_electron_tmpl(){
        $rs = kernel::single('express_print_tmpl')->save($_POST);
        if($rs['rs'] == 'succ') {
            echo 'SUCC';
        } else {
            echo $rs['msg'];
        }
    }

    public function copy_electron_tmpl($template_id){
        $elements = kernel::single('express_print_elements')->defaultElements();
        if($template_id){
            $template = $this->model->dump($template_id);
            $template['prt_tmpl_title'] = '复制'.$template['prt_tmpl_title'];
            $this->pagedata['title'] = '复制模板';
        }

        unset($template['prt_tmpl_id']);
        $this->pagedata['tmpl'] = $template;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['dpi'] = 96;
       
        $this->pagedata['uniqid'] = uniqid();
        $this->pagedata['userAgent'] = $this->getUserAgent();

        $this->pagedata['elements'] = $elements;
        $this->singlepage('admin/printer/electron/template.html');
    }

    /**
     * 获得浏览器版本
     */
    public function getUserAgent() {
        $agent = $_SERVER["HTTP_USER_AGENT"];
        $brower = array('brower' => 'Other', 'ver' => '0', 'type' => 2);
    
        if (strpos($agent, "MSIE 10.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '10.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 9.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '9.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 8.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '8.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 7.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '7.0', 'type' => 1);
        }
        elseif (strpos($agent, "MSIE 6.0")) {
            $brower = array('brower' => 'Ie', 'ver' => '6.0', 'type' => 1);
        }
        elseif (strpos($agent, "Trident")) {
            //IE11以后的版本
            $str = substr($agent, strpos($agent, 'rv:') + strlen('rv:'));
            $ver = substr($str, 0, strpos($str, ')'));
            $brower = array('brower' => 'Ie', 'ver' => $ver, 'type' => 1);
        }
        elseif (strpos($agent, "Chrome")) {
            $str = substr($agent, strpos($agent, 'Chrome/') + strlen('Chrome/'));
            $verInfo = explode(" ", $str);
            $brower = array('brower' => 'Chrome', 'ver' => $verInfo[0], 'type' => 2);
        }
        elseif (strpos($agent, "Firefox")) {
            $str = substr($agent, strpos($agent, 'Firefox/') + strlen('Firefox/'));
            $brower = array('brower' => 'Firefox', 'ver' => $str, 'type' => 2);
        }
        return $brower;
    }

    private function do_electron_print(){
        $_err = 'false';
        //原始数据准备
        $this->get_delivery_info($_POST,$print_data);

        $aData = $this->o->getList('image_id',array('target_id' => $_POST['dly_tmpl_id'],'target_type' => 'print_tmpl'));
        $image_id = $aData[0]['image_id'];
        $this->pagedata['bg_id'] = $image_id;
        $url = $this->show_bg_picture(1,$image_id);
        $pos=strpos($url,'?');
        $url=substr($url,0,$pos);

        $print_data['order_id'] = $_POST['order']['order_id'];
        $oOrder = app::get('b2c')->model('orders');
        $subsdf = array('order_objects'=>array('*',array('order_items'=>array('*',array(':products'=>'*')))));
        $goodsItems = $oOrder->dump($print_data['order_id'],'*',$subsdf);
        $this->get_order_info($goodsItems,$print_data);
        $print_data['order_objects'] = $goodsItems['order_objects'];

        $corpObj = app::get('b2c')->model('dlycorp');
        $corpInfo = $corpObj->getList('corp_id,corp_code,name',array('corp_id'=>$_POST['corp_id']));
        $corp = $corpInfo[0];
        $corp['prt_tmpl_id'] = $_POST['dly_tmpl_id'];
        $corp['channel_id'] = $_POST['channel_id'];

        $print_data['logi_id'] = $corp['corp_id'];
        $print_data['corp_type'] = $corp['corp_code'];
        $print_data['corp_name'] = $corp['name'];
        $print_data['dly_center'] = $_POST['dly_center'];

        //获取订单关联的物流单号
        $orderBillObj = app::get('express')->model('order_bill');
        $orderBillInfo = $orderBillObj->dump(array('order_id'=>$print_data['order_id'],'logi_id'=>$corp['corp_id'],'type'=>'1','status'=>'0'),'logi_no');
        if($orderBillInfo){
            $print_data['logi_no'] = $orderBillInfo['logi_no'];
        }

        //获取电子面单
        $expressOrder = array();
        if(!isset($_GET['isdown'])) {//处理电子面单
            $expressOrder = $print_data;
            kernel::single('express_print_electron')->dealElectron($expressOrder, $corp['channel_id'], $this);
        }

        $print_data['dly_tmpl_id'] = $_POST['dly_tmpl_id'];
        $PrintShipLib = kernel::single('express_print_ship');
        $format_data = $PrintShipLib->format($print_data, $sku,$_err);
        
        $this->pagedata = $format_data;
        $express_company_no = strtoupper($corp['corp_code']);
        $objExpress = express_print_tmpl_express::instance($express_company_no, $this);
        if(!$objExpress->getExpressTpl($corp)){
            $msg = $objExpress->msg ? $objExpress->msg : '获取打印模板失败';
            $this->message($msg);
            exit();
        }
        $printField = $objExpress->printField;
        $printTpl = $objExpress->printTpl;
        $this->pagedata['printTmpl'] = $printTpl;

        if ($format_data['order']) {
            //获取快递单打印模板的servivce定义
            $data = array();
            $tmp = kernel::single('express_print_elements')->processElementContent($format_data['order']);
            $data = array_merge($data, $tmp);
            $mydata[] = $data;
        }

        $jsondata = $PrintShipLib->arrayToJson($mydata);

        //组织控件打印数据
        $this->pagedata['jsondata'] = $jsondata;
        $this->pagedata['data'] = addslashes($PrintShipLib->array2xml2($mydata, 'data'));
        $this->pagedata['totalPage'] = count($mydata);

        /* 修改的地方 */
        //if ($this->pagedata['printTmpl']['file_id']) {
        //    $this->pagedata['tmpl_bg'] = 'index.php?app=ome&ctl=admin_delivery_print&act=showPicture&p[0]=' . $this->pagedata['printTmpl']['file_id'];
        //}

        /*
        $aData = $this->o->getList('image_id',array('target_id' => $_POST['dly_tmpl_id'],'target_type' => 'print_tmpl'));
        $image_id = $aData[0]['image_id'];
        $this->pagedata['bg_id'] = $image_id;
        $url = $this->show_bg_picture(1,$image_id);
        */

        //获取有问题的单据号
        $this->pagedata['errBns'] = $print_data['errBns'];
        $this->pagedata['err'] = $_err;

        //$this->pagedata['idents'] = $print_data['identInfo']['items'];
        //$this->pagedata['ident'] = join(',', $print_data['identInfo']['idents']);
        //$this->pagedata['errIds'] = $print_data['errIds'];
        //$this->pagedata['errInfo'] = $print_data['errInfo'];

        $items = array();
        foreach ($format_data['delivery'] as $row) {
            $items[$row['delivery_id']] = $row;
        }

        $this->pagedata['items'] = $items;
        $this->pagedata['sku'] = $sku;//单品 多品标识
        $this->pagedata['dpi'] = 96;
        $this->pagedata['base_dir'] = kernel::base_url();
        $this->pagedata['title'] = '快递单打印';
        $this->pagedata['uniqid'] = uniqid();

        $params = array('order_bn'=>$this->pagedata['o_bn']);
        $objExpress->setParams($params)->getTmpl();
    }

    /**
     * 获取电子面单运单号
     */
    public function getElectronLogiNo($directParam) {
        $urlParams = json_encode($directParam['get']);
        $postIds = json_encode($directParam['ids']);
        $this->pagedata['urlParams'] = $urlParams;
        $this->pagedata['postIds'] = $postIds;
        $this->pagedata['channel'] = $directParam['channel'];
        $this->pagedata['logi_id'] = $directParam['logi_id'];
        $this->pagedata['dly_center'] = $directParam['dly_center'];
        $this->pagedata['directNum'] = $directParam['directNum'];
        $this->singlepage('admin/delivery/directwaybill/getelectronlogino.html');exit;
        exit();
    }

    /**
     *运单号异步页面
     */
    public function async_logino_page() {
        $channel_id = $_GET['channel_id'];
        $this->pagedata['channel_id'] = $channel_id;
        $this->pagedata['logi_id'] = $_GET['logi_id'];
        $this->pagedata['dly_center'] = $_GET['dly_center'];
        $this->pagedata['MaxProcessOrderNum'] = intval($_GET['directNum']);

        $ids = explode(',', urldecode($_GET['itemIds']));
        $this->pagedata['postIds'] = json_encode($ids);

        $count = count($ids);
        $this->pagedata['count'] = $count;
        $this->display('admin/delivery/directwaybill/async_logino_page.html');
    }

    public function getWaybillLogiNo() {
        $channel_id = $_POST['channel_id'];
        $logi_id = $_POST['logi_id'];
        $dly_center = $_POST['dly_center'];
        $order_id = $_POST['id'];
        
        $result = kernel::single('express_rpc_electron')->directGetWaybill($order_id, $channel_id, $logi_id, $dly_center);
        if($result['dealResult']) {
            echo json_encode($result);
            exit();
        }
    }

    public function cancelWaybill(){
        $order_id = $_POST['order_id'];
        $logi_id = $_POST['old_logi_id'];
        $logi_no = $_POST['old_logi_no'];

        $corpObj = app::get('b2c')->model('dlycorp');
        $corpInfo = $corpObj->getList('channel_id',array('corp_id'=>$logi_id));
        $channel_id = $corpInfo[0]['channel_id'];

        $result = kernel::single('express_rpc_electron')->cancelWaybill($order_id,array('logi_id'=>$logi_id,'logi_no'=>$logi_no),$channel_id);
        if($result){
            $tmp = array('status'=>'succ','msg'=>'');
        }else{
            $tmp = array('status'=>'error','msg'=>'取消失败');
        }

        echo json_encode($tmp);
        exit;
    }

    /**
     * 下载控件
     */
    public function diagLoadPrintSite() {
        $this->page('admin/delivery/directwaybill/diag_load_print_site.html');
    }

    public function downloadPrintSite() {
        $product_type = isset($_GET['product_type']) ? trim($_GET['product_type']) : 'tp';
        $url = 'http://update.tg.taoex.com/tg.php';
        $http = kernel::single('base_httpclient');
        $secrect = '67C70BDFAF354401D9D2192377D09DC0';
        $params = array(
            'app_key' => 'taoguan',
            'product_type' => $product_type,
            'timestamp' => time(),
            'format' => 'json'
        );
        $sign = strtoupper(md5($this->assemble($params).$secrect));
        $params['sign'] = $sign;
        $result = $http->post($url, $params);
        echo $result;exit;
    }

    protected function assemble($params) {
        if(!is_array($params)) {
            return null;
        }
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params as $pk => $pv) {
            if (is_null($pv)) {
                continue;
            }
            if (is_bool($pv)) {
                $pv = ($pv) ? 1 : 0;
            }
            $sign .= $pk . (is_array($pv) ? $this->assemble($pv) : $pv);
        }
        return $sign;
    }
}
