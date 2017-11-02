<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
class goodsapi_shopex_spec_update extends goodsapi_goodsapi{

    public function __construct(){
        parent::__construct();
        $this->obj_goods = kernel::single('goodsapi_goods');
        $this->spec_model = app::get('b2c')->model('specification');
        $this->spec_values_model = app::get('b2c')->model('spec_values');
    }

    //更新商品规格列表接口
    function shopex_spec_update (){
        $params = $this->params;
        //api 调用合法性检查
        $this->check($params);

        //检查应用级必填参数
        $must_params = array(
            'spec_name','new_spec_name','spec_type','spec_show_type',
            'spec_values','is_show','disabled','is_force_update','last_modify'
        );
        $this->check_params($must_params);

       //规格值不能为空
        if( empty($params['spec_name'])  || empty($params['spec_values'])){
            $error['code'] = null;
            $error['msg'] = '规格名称或规格值为空';
            $this->send_error($error);
        }

        $filter = array(
            'spec_name' => $params['spec_name'],
        );
        if($params['alias']){
            $filter['alias'] = $params['alias'];
        }

        //获取到要更新的spec_id
        $spec_id = $this->spec_model->getList('*',$filter);
        if( !$spec_id ){
            if( $params['unexist_add'] == 'false'){
                $error['code'] = null;
                $error['msg'] = '更新的规格不存在';
                $this->send_error($error);
            }
        }else{
            $spec_id = $spec_id[0]['spec_id'];
            $save_data['spec_id'] = $spec_id;
        }

        if(empty($params['new_alias'])){
            $alias = $params['alias'];
        }else{
            $alias = $params['new_alias'];
        }

        if(empty($params['new_spec_name']))
            $spec_name = trim($params['spec_name']);
        else
            $spec_name = trim($params['new_spec_name']);

        $arr_spec_value = json_decode($params['spec_values'],true);
//         $spec_index = app::get('b2c')->model('goods_spec_index');
        
        //格式化传入的spec_values  已确认货店通的spec_value和new_spec_value始终保持一致
        $arr_new_spec_values = array();
        foreach ($arr_spec_value['spec_values'] as $var_new_s){
            $current_spec_value = trim($var_new_s['spec_value']);
            //获取到规则值的id todo:在ecstore中 spec_id和spec_values 不能确定唯一
//             $spec_value_id = $this->spec_values_model->getList('spec_value_id',array('spec_id'=>$spec_id,'spec_value'=>$current_spec_value));
            //这里判断此规格是否有关联商品
//             if( $spec_index->dump(array('spec_id'=>$spec_id,'spec_value_id'=>$spec_value_id)) ){
//                 $error['msg'] = '此规格值已有关联商品，不能更新';
//                 $this->send_error($error);
//             }
            $arr_new_spec_values[$current_spec_value] = $var_new_s;
        }
        //由于改成了增量 以原有的数据为准 获取原有规格数据
        $rs_spec_values = $this->spec_values_model->getList('*',array("spec_id"=>$spec_id));
        //先处理原有数据 如和传入数据一样以传入数据为准
        $spec_value = array();
        foreach($rs_spec_values as $var_old_s){
            $current_spec_value_id = $var_old_s["spec_value_id"];
            if($arr_new_spec_values[$var_old_s["spec_value"]]){
                //处理新旧规格值得交集 取新传入的数据
                $image_id = '';
                if($arr_new_spec_values[$var_old_s["spec_value"]]['image_url']){
                    $image_id = $this->obj_goods->get_image_id($arr_new_spec_values[$var_old_s["spec_value"]]['image_url']);
                }
                $spec_value[] = array(
                    'spec_value' => $arr_new_spec_values[$var_old_s["spec_value"]]['spec_value'],
                    'alias' => $arr_new_spec_values[$var_old_s["spec_value"]]['spec_value_alias'],
                    'spec_image' => $image_id,
                    'p_order' => intval($arr_new_spec_values[$var_old_s["spec_value"]]['order_by']),
                    'spec_value_id' => $current_spec_value_id,
                );
                unset($arr_new_spec_values[$var_old_s["spec_value"]]);
            }else{
                //有存在的旧规格 新传入的没有此规格 保留原有规则
                $spec_value[] = array(
                    'spec_value' => $var_old_s['spec_value'],
                    'alias' => $var_old_s["alias"],
                    'spec_image' => $var_old_s["spec_image"],
                    'p_order' => intval($var_old_s["p_order"]),
                    'spec_value_id' => $current_spec_value_id,
                );
            }
        }
        //处理留下的新的规则
        foreach ($arr_new_spec_values as $new_spec_values){
            $image_id = '';
            if($new_spec_values['image_url']){
                $image_id = $this->obj_goods->get_image_id($new_spec_values['image_url']);
            }
            $spec_value[] = array(
                    'spec_value' => $new_spec_values['spec_value'],
                    'alias' => $new_spec_values['spec_value_alias'],
                    'spec_image' => $image_id,
                    'p_order' => intval($new_spec_values['order_by']),
            );
        }
        
        $spec_type = 'image';
        if($params['spec_type'] == '文字'){
            $spec_type = 'text';
        }

        $spec_show_type = 'flat';
        if($params['spec_show_type'] == '下拉'){
            $spec_show_type = 'select';
        }

        $save_data['spec_name'] = $spec_name;
        $save_data['alias'] = $alias;
        $save_data['spec_memo'] = trim($params['memo']);
        $save_data['spec_type'] = $spec_type;
        $save_data['spec_show_type'] = $spec_show_type;
        $save_data['p_order'] = intval($params['order_by']);
        $save_data['spec_value'] = $spec_value;

        $rs = $this->spec_model->save($save_data);
        if( $rs ){
            $data = array('last_modify' =>time());
            $this->send_success($data);
        }else{
            $error['code'] = null;
            $error['msg']  = '更新失败';
            $this->send_error($error);
        }
    }
}
