<?php
class dev_docbuilder_ddmd{
    private $__content = '';
    private $__indexes  = array();
    private $__dbtables = array();
    private $__tables_outline = array();

    public function export()
    {
        foreach($this->get_all_apps() as $app)
        {
            $this->get_dbtables($app);
        }
        $this->__content = $this->gen_index();
        foreach($this->__indexes as $index)
        {
            $this->__content .= $this->gen_table($index['table']);
        }
    }

    public function output(){
        echo $this->__content;
    }

    public function get_all_apps()
    {
        $d = dir(APP_DIR);
        while (false !== ($entry = $d->read())) {
            if ($entry!='.' && $entry!='..') {
                if (is_dir(APP_DIR.'/'.$entry)) {
                    $apps[] = $entry;
                }
            }
        }
        $d->close();
        return $apps;
    }

    public function get_dbtables($app_id)
    {
        foreach(kernel::single('base_application_dbtable')->detect($app_id) as $name=>$item){
            $table_name = $item->real_table_name();
            $table = $item->load();
            $table['table_name'] = $table_name;
            $this->__dbtables[$table_name] = $table;
            $this->__indexes[] = array(
                'table' => $table_name,
                'title' => $table['comment'],
            );
        }
    }

    public function gen_index()
    {
        $render = new base_render(app::get('dev'));
        $indexes = $this->__indexes;
        foreach($indexes as $key=>$val){
            $pos = strpos($val['table'], 'sdb_webpos_');
            if($pos !==false){
                unset($indexes[$key]);
            }
        }
        $render->pagedata['indexes'] = $indexes;
        $render->pagedata['count'] = count($indexes);
        $render->pagedata['version'] = app::get('base')->getConf('product_version');
        $html = $render->display('ddmd_index.html', 'dev', true, false);
        return $html;
    }

    public function gen_table($table_name)
    {
        $render = new base_render(app::get('dev'));
        $dbtable = $this->__dbtables[$table_name];
        $pos = strpos($dbtable['table_name'], 'sdb_webpos_');
        if($pos !==false){
            return '';
        }
        $render->pagedata['dbtable'] = $dbtable;
      //var_dump($dbtable);
        $html = $render->display('ddmd.html', 'dev', true, false);
        return $html;
    }

}
