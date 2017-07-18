<?php
namespace Api\Model;
use Think\Model;

class UserModel extends \Think\Model\RelationModel
{
    protected $_link = [
        'card' => [
            'mapping_type' => self::HAS_MANY ,
            'class_name' => 'Wishcard',
            'foreign_key' => 'set_user',
        ],

        'help' => [
            'mapping_type' => self::HAS_MANY ,
            'class_name' => 'Wishhelp',
            'foreign_key' => 'post_uid',
        ],

        'wall' => [
            'mapping_type' => self::HAS_MANY ,
            'class_name' => 'Wishwall',
            'foreign_key' => 'set_user',
            'condition'   =>'chk_status = 1'
        ],

    ];

}

?>