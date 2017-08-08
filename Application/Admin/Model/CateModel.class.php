<?php
namespace Admin\Model;
use Think\Model;

/**
* 
*/
class CateModel extends Model{
	
	public function getTree($id = 0,$field = true){
		# code...
        // if($id){
        //     $info = $this->info($id);
        //     $id   = $info['id'];
        // }

        $list = $this->field($field)->select();
        $list = list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_', $root = $id);
        
        if(isset($info)){ 
            $info['_'] = $list;
        } else { 
            $info = '-'.$list;
        }

        return $info;
	}
}

?>