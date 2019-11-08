<?php
/**
 * Pts Prestashop Theme Framework for Prestashop 1.6.x
 *
 * @package   ptssliderlayer
 * @version   1.0
 * @author    http://www.prestabrain.com
 * @copyright Copyright (C) October 2013 prestabrain.com <@emai:prestabrain@gmail.com>
 *               <info@prestabrain.com>.All rights reserved.
 * @license   GNU General Public License version 2
 */

class PtsSliderGroup extends ObjectModel
{
        public $title;
	public $active;
	public $hook;
	public $id_shop;
	public $params;

	/**
	 * @see ObjectModel::$definition
	 */
	public static $definition = array(
		'table' => 'ptssliderlayer_groups',
		'primary' => 'id_ptssliderlayer_groups',
		'fields' => array(
                        'title' =>			array('type' => self::TYPE_STRING, 'lang' => false, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 255),
                        'active' =>			array('type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true),
                        'hook' =>                       array('type' => self::TYPE_STRING, 'lang' => false, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 64),
                        'id_shop'=>                     array('type' => self::TYPE_INT, 'validate' => 'isunsignedInt', 'required' => true),
			'params' =>                     array('type' => self::TYPE_HTML, 'lang' => false)
		)
	);

	public	function __construct($id_group = null, $id_shop = null, Context $context = null)
	{
		parent::__construct($id_group, $id_shop);
	}

	public function add($autodate = true, $null_values = false)
	{
		$res = parent::add($autodate, $null_values);
		
		return $res;
	}
        
        
        public static function groupExists($id_group) {
            $req = 'SELECT gr.`id_ptssliderlayer_groups` as id_group
                    FROM `' . _DB_PREFIX_ . 'ptssliderlayer_groups` gr
                            WHERE gr.`id_ptssliderlayer_groups` = ' . (int) $id_group;
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);
            return ($row);
        }

    public function getGroups($active = null,$id_shop)
	{
				
		$this->context = Context::getContext();
		if(!isset($id_shop))
		$id_shop = $this->context->shop->id;
		return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT *
			FROM `'._DB_PREFIX_.'ptssliderlayer'.'_groups` gr
			WHERE (`id_shop` = '.(int)$id_shop.')'.
			($active ? ' AND gr.`active` = 1' : ' '));
	}
        
        public static function deleteAllSlider($id_group){
                $sliders = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT sl.`id_ptssliderlayer_slides` as id
                            FROM `' . _DB_PREFIX_ . 'ptssliderlayer_slides` sl
                            WHERE sl.`id_group` = ' . (int) $id_group);
                
                if($sliders){
                    $res = Db::getInstance()->execute('
                            DELETE FROM `'._DB_PREFIX_.'ptssliderlayer_slides`
                            WHERE `id_group` = '.(int)$id_group
                    );
                    
                    $where = '';
                    foreach ($sliders as $sli){
                        $where .= $where?",".$sli["id"]:$sli["id"];
                    }
                    $res &= Db::getInstance()->execute('
                            DELETE FROM `'._DB_PREFIX_.'ptssliderlayer_slides_lang`
                            WHERE `id_ptssliderlayer_slides` IN ('.$where.')'
                    );
                }
        }
                
	public function delete()
	{
		$res = true;
                
		$res &= Db::getInstance()->execute('
			DELETE FROM `'._DB_PREFIX_.'ptssliderlayer_groups`
			WHERE `id_ptssliderlayer_groups` = '.(int)$this->id
		);
		$this->deleteAllSlider((int)$this->id);
		$res &= parent::delete();
		return $res;
	}
}
