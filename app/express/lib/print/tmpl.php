<?php
class express_print_tmpl {
    
    /**
     * 上传快递单模板
     * 
     * @param object $file 待上传的快递单模板文件
     * @param string $err_msg 上传模板失败描述
     * @return boolean true/false
     */
    function upload_tmpl($file, &$err_msg){
        $printTmplObj = app::get('express')->model('print_tmpl');
        $imageAttachObj = app::get('image')->model('image_attach');

        $extname = strtolower($this->extName($file['name']));
        $tar = kernel::single('base_tar');
        $target = DATA_DIR . '/tmp';
        if($extname=='.dtp')
        {
            if($tar->openTAR($file['tmp_name'],$target) && $tar->containsFile('info'))
            {
                if(!($info = unserialize($tar->getContents($tar->getFile('info')))))
                {
                    $err_msg = app::get('express')->_('无法读取结构信息,模板包可能已损坏！');
                    return false;
                }
                $info['prt_tmpl_id']='';

                if($tpl_id=$printTmplObj->insert($info))
                {
                    if($tar->containsFile('background.jpg'))
                    { //包含背景图
                        $image = app::get('image')->model('image');
                        $image_id = $image->gen_id();
                        $pic = ($tar->getContents($tar->getFile('background.jpg')));
                        file_put_contents(DATA_DIR.'/'.$tpl_id.'.jpg',$tar->getContents($tar->getFile('background.jpg')));
                        $Image_id = $image->store(DATA_DIR.'/'.$tpl_id.'.jpg',$Image_id);
                        unlink(DATA_DIR.'/'.$tpl_id.'.jpg');
                        $sdf = array(
                            'target_id' => $tpl_id,
                            'target_type' => 'print_tmpl',
                            'image_id' => $Image_id,
                            'last_modified' => time(),
                        );

                        if(!($imageAttachObj->save($sdf)))
                        {
                            $err_msg = app::get('express')->_('模板包中图片有误！');
                            return false;
                        }
                    }
                }
            }
            else
            {
                $err_msg = app::get('express')->_('无法解压缩,模板包可能已损坏！');
                return false;
            }
        }
        else
        {
            $err_msg = app::get('express')->_('必须是shopex快递单模板包(.dtp)');
            return false;
        }

        return true;
    }

    /*
     * 提取扩展名
     */
    function extName($file){
        return substr($file,strrpos($file,'.'));
    }

    public function save($params) {

        $data = array(
            'prt_tmpl_title' => $params['template_name'],
            'prt_tmpl_type' => 'electron',
            'shortcut' => $params['status'] ? $params['status'] : 'true',
            'prt_tmpl_width' => $params['template_width'],
            'prt_tmpl_height' => $params['template_height'],
            //'file_id' => $params['file_id'] ? $params['file_id'] : 0,
            //'is_logo' => $params['is_logo'] ? $params['is_logo'] : 'true',
            //'template_select' => $params['template_select'] ? serialize($params['template_select']) : null,
            'prt_tmpl_data' => $params['template_data'],
        );

        if ($data['prt_tmpl_title'] == ''){
            $title = '请输入快递单名称';
            return array('rs'=>'fail', 'msg'=>$title);
        }

        /*
        if((!$data['template_width'] || !$data['template_height']) && $data['file_id']>0){
            $bgUrl = $this->getImgUrl($data['file_id']);
            list($width, $height) = getimagesize($bgUrl);
            if($width && $height){
                $data['template_width'] = intval($width*25.4/96);
                $data['template_height'] = intval($height*25.4/96);
            }
        }
        */

        $templateObj = app::get('express')->model('print_tmpl');
        if ($params['template_id']){
            $filter = array('prt_tmpl_id' => $params['template_id']);
            $re = $templateObj->update($data,$filter);
            $data['prt_tmpl_id'] = $params['template_id'];
        }else {
            $re = $templateObj->insert($data);
        }
        return $re ? array('rs'=>"succ", 'data'=>$data) : array('rs'=>'fail', 'msg'=>'保存失败');
    }
}