<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class wap_view_helper
{

    public function function_wapheader($params, &$smarty)
    {

        $app_exclusion = app::get('base')->getConf('system.main_app');
        $app_exclusion = 'ecos.'.$app_exclusion['app_id'];

        $title = $this->_deleteHtml(!empty($smarty->pagedata['title'])?$smarty->pagedata['title']:($smarty->title ? $smarty->title : app::get('wap')->getConf('wap.shopname')));
        $keywords = $this->_deleteHtml(!empty($smarty->pagedata['keywords'])?$smarty->pagedata['keywords']:$smarty->keywords);
        $description = $this->_deleteHtml(!empty($smarty->pagedata['description'])?$smarty->pagedata['description']:$smarty->description);
        $nofollow = $smarty->pagedata['nofollow']!=""?$smarty->pagedata['nofollow']:$smarty->nofollow;
        $noindex = $smarty->pagedata['noindex']!=""?$smarty->pagedata['noindex']:$smarty->noindex;

        // 苹果桌面
        if($appleDesktop = app::get('wap')->getConf('wap.apple.desktop')) {
            if($appleDesktop = base_storager::image_path($appleDesktop,'m')) {
                $appleDesktop = $appleDesktop;
            }else{
                $appleDesktop = $imgDefaultUrl;
            }
        }

        $_outputHeader = array(
                "<meta charset='UTF-8' />",
                "<meta name='generator' content='$app_exclusion' />",
                "<title>$title</title>",
                "<meta name='keywords' content='$keywords' />",
                "<meta name='description' content='$description' />",
                "<meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no'/>",
                "<link type='image/x-icon' href='$appleDesktop' rel='apple-touch-icon-precomposed'>"
            );
        if($nofollow == '是'){
            $_outputHeader[]="<meta name='nofollow' content='nofollow' />";
        }
        if($noindex == '是'){
            $_outputHeader[]="<meta name='noindex' content='noindex' />";
        }

        foreach($_outputHeader AS $oh){
                $html .= $oh . "\n";
        }

        $headers = $smarty->pagedata['headers'];
        if(is_array($headers)){
            foreach($headers AS $header){
                $html .= $header . "\n";
            }
        }else{
            $html .= $headers . "\n";
        }

        $html .= $smarty->fetch('header.html', app::get('wap')->app_id);
        return $html . '<link id="wap_widgets_style" rel="stylesheet" href="<%wap_widgets_css%>" type="text/css"/><script type="text/javascript">(function(){var widgets_style = document.getElementById(\'wap_widgets_style\');var head = document.getElementsByTagName(\'head\')[0];head.appendChild(widgets_style);})();</script>';
    }//End Function

    public function function_wapfooter($params, &$smarty)
    {
        $footers = $smarty->pagedata['footers'];
        if(is_array($footers)){
            foreach($footers AS $footer){
                $html .= $footer;
            }
        }else{
            $html .= $footers;
        }

        $html .= app::get('wap')->getConf('wap.foot_edit');

        $obj = kernel::service('site_footer_copyright');
        if(is_object($obj) && method_exists($obj, 'get')){
            $html .= $obj->get();
        }else{
            if(!defined('WITHOUT_POWERED') || !constant('WITHOUT_POWERED')){
                $html .= ecos_cactus('wap','wapcopyr',$html);
            }
        }

        if (isset($_COOKIE['wap']['preview'])&&$_COOKIE['wap']['preview']=='true'){
            $base_dir = kernel::base_url();
            $remove_cookie= "$.fn.cookie('wap[preview]','',{path:'".$base_dir."/'});$(document.body).removeClass('set-margin-body');";
            $set_window = '$("body").addClass("set-margin-body");moveTo(0,0);resizeTo(screen.availWidth,screen.availHeight);';
            $html .='<style>body.set-margin-body{margin-top:36px;}#_theme_preview_tip_ {width:100%; position: absolute; left: 0; top: 0; background: #FCE2BC; height: 25px; line-height: 25px; padding: 5px 0; border-bottom: 1px solid #FF9900;box-shadow: 0 2px 5px #CCCCCC; }#_theme_preview_tip_ span.msg { float: left; _display: inline;zoom:1;line-height: 25px;margin-left:10px;padding:0; }#_theme_preview_tip_ a.btn {vertical-align:middle; color:#333;float: right; margin:0 10px; }</style><div id="_theme_preview_tip_"><span class="msg">'.app::get('site')->_('目前正在预览模式').'</span><a href="javascript:void(0);" class="btn" onclick="'.$remove_cookie.'location.reload();"><span><span>'.app::get('site')->_('退出预览').'</span></span></a></div>';
            $html .='<script>'.$set_window.'$(window).on("unload",function(){'.$remove_cookie.'});</script>';
        }
        $html .= $smarty->fetch('footer.html', app::get('wap')->app_id);
        $html .= $this->set_refer();
        $icp = app::get('site')->getConf('system.site_icp');
        if( $icp )
            $html .= '<div style="text-align: center;">'.$icp.'</div>';

        return $html;
    }//End Function

    //设置FIRST_REFER NOW_REFER cookie值用于推广链接  同/app/b2c/view/site/common/footer.html
    private function set_refer(){
        $cookie_url =  kernel::base_url().'/';
        $hasfirstR = "2"; //无
        if (isset($_COOKIE['S']['FIRST_REFER'])){
            $firstR = json_decode(stripslashes($_COOKIE['S']['FIRST_REFER']),true);
            $first_refer_id = $firstR['ID'];
            $first_refer_time = $firstR['DATE'];
            $first_refer_url = $firstR['REFER'];
            if ($first_refer_id && $first_refer_time){
                $hasfirstR = "1"; //有
            }
        }
        $html = '<script>
                    (function(){
                        var serverTime = '.time().';
                        var __time_out = 1000;
                        var nowDate = serverTime*1000;
                        var current_url = window.location.href;
                        var doc_ref = document.referrer?document.referrer:"";
                        var pos = current_url.indexOf("#r-");
                        if(pos == -1){
                            return;
                        }
                        var current_url_uid = current_url.substr(pos+4);
                        if(!current_url_uid){
                            return;
                        }
                        var uid_n = current_url_uid;    
                        var refer_n = doc_ref;   
                        var date_n = nowDate;
                        var fdata_n={"ID":uid_n,"REFER":refer_n,"DATE":date_n};
                        if('.$hasfirstR.' == "1"){
                            var uid_f = "'.$first_refer_id.'";
                            var refer_f = "'.$first_refer_url.'";
                            var date_f = "'.$first_refer_time.'";
                            fdata_f={"ID":uid_f,"REFER":refer_f,"DATE":date_f};
                            $.fn.cookie("S[FIRST_REFER]",JSON.stringify(fdata_f),{ path: "'.$cookie_url.'" });
                        }else{
                            $.fn.cookie("S[FIRST_REFER]",JSON.stringify(fdata_n),{ path: "'.$cookie_url.'" });
                        }
                        $.fn.cookie("S[NOW_REFER]",JSON.stringify(fdata_n),{ path: "'.$cookie_url.'" });
                    })();
                </script>';
        return $html;
    }
    
    // public function function_template_filter($params, &$smarty)
    // {

    //     if($params['type']){
    //         $render = kernel::single('base_render');
    //         $theme = kernel::single('wap_theme_base')->get_default();
    //         $obj = kernel::single('wap_theme_tmpl');
    //         $theme_list = $obj->get_edit_list($theme);
    //         $render->pagedata['list'] = $theme_list[$params['type']];
    //         unset($params['type']);
    //         $render->pagedata['selected'] = $params['selected'];
    //         unset($params['selected']);
    //         if(is_array($params)){
    //             foreach($params AS $k=>$v){
    //                 $ext .= sprintf(' %s="%s"', $k, $v);
    //             }
    //         }
    //         $render->pagedata['ext'] = $ext;
    //         return $render->fetch('admin/theme/tmpl/template_filter.html', app::get('wap')->app_id);
    //     }else{
    //         return '';
    //     }
    // }//End Function


    private  function _deleteHtml($str)
    {
        $str = trim($str);
        $str = strip_tags($str,"");
        $str = str_replace("\t","",$str);
        $str = str_replace("\r\n","",$str);
        $str = str_replace("\r","",$str);
        $str = str_replace("\n","",$str);
        $str = str_replace(" "," ",$str);
        return trim($str);
    }//End Function

    function function_wap_pagers($params, &$smarty){
        if(!$params['data']['current'])$params['data']['current'] = 1;
        if(!$params['data']['total'])$params['data']['total'] = 1;
        if($params['data']['total']<2){
            return '';
        }

        $c = $params['data']['current'];
        $t = $params['data']['total'];
        $l=$params['data']['link'];
        $p=$params['data']['token'];

        $html = '<div class="pageview">';
        if($c > 1 ){
            $html .= '<a href="'.str_replace($p,$c-1,$l).'" class="flip prev">上一页</a>';
        }else{
            $html .= '';
        }

        $ajaxhref = $params['ajax'] ? '' : ' onChange="location.replace(this.options[this.selectedIndex].value)" ';
        $html .= '<select '.$ajaxhref.'>';
        for($i=1; $i<=$t ;$i++ ){
            $selected = ($i==$c) ? ' selected="selected" ' : '';
            $pages = $i.'/'.$t;
            $html.='<option class="flip" '.$selected.' page='.$i.' value="'.str_replace($p,$i,$l).'">'.$pages.'</option>';
        }
        $html .= '</select>';

        if($c < $t){
            $html .= '<a href="'.str_replace($p,$c+1,$l).'" class="flip next">下一页</a>';
        }else{
            $html .= '';
        }

        return $html.'</div>';
    }

}//End Class
