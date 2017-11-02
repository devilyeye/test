<?php
class penker_api_goods{

    public $app;
    public function __construct($app)
    {
        $this->app = $app;
    }
    public function get_all(){
        $word = 'goods_cat';
        $penker = $this->app->model('bind');
        $arr_bind = $penker->getList();
        $pack = $_GET['pack'];
        $key = $arr_bind[0]['secret_key'];
        $iv = substr(md5($arr_bind[0]['node_id']),0,16);
        $check_word = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($pack), MCRYPT_MODE_CBC,$iv));
        if($word == $check_word){
            $result['rsp'] = 'succ';
            $goods = app::get('b2c')->model('goods_cat');
            $goods_list = $goods->getList('*',array('disabled'=>'false'));
            $result['res'] = $goods_list;
            print_r(json_encode($result));
        }else{
            $result['rsp'] = 'fail';
            $result['data'] = 'pack is wrong';
            print_r(json_encode($result));
        }
    }
    public function get_list(){
        $word = 'goods_list';
        $penker = $this->app->model('bind');
        $arr_bind = $penker->getList();
        $pack = $_GET['pack'];
        $key = $arr_bind[0]['secret_key'];
        $iv = substr(md5($arr_bind[0]['node_id']),0,16);
        $check_word = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($pack), MCRYPT_MODE_CBC,$iv));
        if($word == $check_word){
            $goods = app::get('b2c')->model('goods');
            $products = app::get('b2c')->model('products');
            $page = empty($_POST['page'])?'1':$_POST['page'];
            $pageLimit = empty($_POST['limit'])?'10':$_POST['limit'];
            $urlFilter = empty($_POST['kw'])?null:'n,'.htmlspecialchars($_POST['kw']);

            $oSearch = app::get('b2c')->model('search');
            $path = array();
            $system = '';
            $tmp_filter = $oSearch->decode($urlFilter,$path,$system);
            if(!empty($_POST['cat_id'])){
                $tmp_filter['cat_id'] = $_POST['cat_id'];
            }
            $tmp_filter['marketable'] = 'true';
            $tmp_filter['is_buildexcerpts'] = 'true';
            $tmp_filter['is_store'] = 'on';
            $goodsData = $goods->getList('goods_id,bn,name,price,image_default_id',$tmp_filter,$pageLimit*($page-1),$pageLimit);
            if($tmp_filter['is_store'] == 'on'){
                foreach($goodsData as $key => $value){
                    $arr_products = $products->getlist('product_id,store,freez',array('goods_id'=>$value['goods_id']));
                    $is_store = false;
                    foreach($arr_products as $value){
                        if($value['store']>$value['freez']){
                            $is_store = ture;
                            break;
                        }
                    }
                    if(!$is_store){
                        unset($goodsData[$key]);
                    }else{
                        $goodsData[$key]['pic'] = base_storager::image_path($goodsData[$key]['image_default_id'], 's');
                        $goodsData[$key]['link'] = 'http://'.$_SERVER['HTTP_HOST'].app::get('wap')->router()->gen_url( array( 'app'=>'b2c','real'=>1,'ctl'=>'wap_product','args'=>array($arr_products[0]['product_id'],'guide_identity')));
                        $goodsData[$key]['product_id'] = $arr_products[0]['product_id'];
                    }
                }
            }
            $total = $goods->count($tmp_filter);
            $result =array(
                    'total' => $total,
                    'page' => $page,
                    'limit' => $pageLimit,
                    'goods' => $goodsData,
                );
            $result['rsp'] = 'succ';
            $result['res'] = $result;
            print_r(json_encode($result));
        }else{
            $result['rsp'] = 'fail';
            $result['data'] = 'pack is wrong';
            print_r(json_encode($result));
        }
    }
}
