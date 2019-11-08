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

if (!defined('_PS_VERSION_'))
    exit;

include_once(_PS_MODULE_DIR_ . 'ptssliderlayer/grouplayer.php');
include_once(_PS_MODULE_DIR_ . 'ptssliderlayer/sliderlayer.php');

class PtsSliderLayer extends Module {

    private $_html = '';
    private $_currentGroup = array('id_group' => 0, 'title' => '');
    public $groupData = array(
        'id_ptssliderlayer_groups' => '',
        'title' => '',
        'id_shop' => '',
        'hook' => '',
        'active' => '',
        'auto_play' => '1',
        'delay' => '9000',

        'width' => '960',
        'height' => '350',
        'md_width' => '12',
        'sm_width' => '12',
        'xs_width' => '12',
        'stop_on_hover' => '1',
        'image_cropping' => '0',
        'shadow_type' => '2',
        'show_time_line' => '1',
        'time_line_position' => 'top',
        'background_color' => '#d9d9d9',
        'margin' => '0px 0px 18px',
        'padding' => '5px 5px',
        'background_image' => '0',
        'background_url' => '',
        'navigator_type' => '0',
        'navigator_arrows' => '1',
        'navigation_style' => 'round',
        'offset_horizontal' => '0',
        'offset_vertical' => '20',
        'show_navigator' => '0',
        'hide_navigator_after' => '200',
        'thumbnail_width' => '100',
        'thumbnail_height' => '50',
        'group_class' => ''
    );
    private $_hookSupport = array("displaySlideshow", "displayTop", "displayTopColumn", "displayHome");
    private $_currentSlider = array();
    private $_sliderData = array(
        'transition' => 'random',
        'easing' => 'easeInQuad',
        'pausetime' => 1500,
    );
    public $themeName;
    public $img_path;
    public $img_url;

    public $_error_text = '';
    public function __construct() {
        $this->name = 'ptssliderlayer';
        $this->tab = 'prestabrain';
        $this->version = '1.5';
        $this->author = 'PrestaBrain';
        $this->need_instance = 0;
        $this->secure_key = Tools::encrypt($this->name);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Pts Slider Layer for your homepage.');
        $this->description = $this->l('Adds image or text slider to your homepage.');
        
        $this->themeName = Context::getContext()->shop->getTheme();
        $this->img_path = _PS_MODULE_DIR_.$this->name.'/images/';
        $this->img_url = _MODULE_DIR_ . $this->name. '/images/';
    }

    /**
     * @see Module::install()
     */
    public function install() {
		$hookspos = array(
            'displayTop',
            'displayHeaderRight',
            'displaySlideshow',
            'topNavigation',
            'displayPromoteTop',
            'displayRightColumn',
            'displayLeftColumn',
            'displayHome',
            'displayFooter',
            'displayBottom',
            'displayContentBottom',
            'displayFootNav',
            'displayFooterTop',
			'displayMapLocal',
            'displayFooterBottom'
        );
        
        foreach( $hookspos as $hook ){
            if( Hook::getIdByName($hook) ){
                
            } else {
                $new_hook = new Hook();
                $new_hook->name = pSQL($hook);
                $new_hook->title = pSQL($hook);
                $new_hook->add();
            }
        } 
        // Prepare tab
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = "AdminPtsSliderLayer";
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang)
                $tab->name[$lang['id_lang']] = 'PtsSliderLayer';
        $tab->id_parent = -1;
        $tab->module = $this->name;
        
        /* Adds Module */
        if ($tab->add() && parent::install() 
            && $this->registerHook('displaySlideshow') && $this->registerHook('displayHome') 
			&& $this->registerHook('displayTop') && $this->registerHook('displayTopColumn') 
            && $this->registerHook('actionShopDataDuplication') 
            && $this->registerHook('header') 
			&& Configuration::updateValue('PTSSLIDERLAYER_GROUP_DE', '1')) {
            $res = true;
            /* Sets up configuration */

            /* Creates tables */
            $res &= $this->createTables();

           return (bool)$res;
        }
        return false;
    }

    /**
     * @see Module::uninstall()
     */
    public function uninstall() {
        $id_tab = (int)Tab::getIdFromClassName('AdminPtsSliderLayer');
        if ($id_tab)
        {
            $tab = new Tab($id_tab);
            $tab->delete();
        }
                
        /* Deletes Module */
        if (parent::uninstall()) {
            /* Deletes tables */
            $res = $this->deleteTables();
            return $res;
        }
        return false;
    }

    /**
     * Creates tables
     */
    protected function createTables() {
        /* Group */
        $res = (bool) Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $this->name . '_groups` (
                `id_' . $this->name . '_groups` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,    
                `id_shop` int(10) unsigned NOT NULL,
                `hook` varchar(64) NOT NULL,
                `active` tinyint(1) unsigned NOT NULL DEFAULT \'1\',
                `params` text NOT NULL,
                PRIMARY KEY (`id_' . $this->name . '_groups`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        /* Slides configuration */
        $res &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $this->name . '_slides` (
                `id_' . $this->name . '_slides` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `id_group` int(11) NOT NULL,
                `position` int(10) unsigned NOT NULL DEFAULT \'0\',
                `active` tinyint(1) unsigned NOT NULL DEFAULT \'1\',
                `parent_id` int(11) NOT NULL,
                `params` text NOT NULL,
              PRIMARY KEY (`id_' . $this->name . '_slides`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');

        /* Slides lang configuration */
        $res &= Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . $this->name . '_slides_lang` (
              `id_' . $this->name . '_slides` int(10) unsigned NOT NULL,
              `id_lang` int(10) unsigned NOT NULL,
              `title` varchar(255) NOT NULL,
              `link` varchar(255) NOT NULL,
              `image` varchar(255) NOT NULL,
              `thumbnail` varchar(255) NOT NULL,
              `video` text,
              `layersparams` text,
              PRIMARY KEY (`id_' . $this->name . '_slides`,`id_lang`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=UTF8;
        ');
        return $res;
    }
    

    /**
     * deletes tables
     */
    protected function deleteTables() {
        return true;
        return Db::getInstance()->execute('
            DROP TABLE IF EXISTS `' . _DB_PREFIX_ . $this->name . '_groups`, `' . _DB_PREFIX_ . $this->name . '_slides`, `' . _DB_PREFIX_ . $this->name . '_slides_lang`;
        ');
    }

    public function getContent() {
        $this->_html .= $this->headerHTML();
        if(Tools::isSubmit("importGroup")){
            $this->importGroup();
        }
        if(Tools::getIsset("leoajax") && Tools::getValue("leoajax") == 1){
            $this->processAJax();
        }
        
        if(Tools::getIsset('correctGroup') && Tools::getValue('correctGroup')){
            $this->correctDataGroup();
        }
        
        if(Tools::getIsset('copylang') && Tools::getValue('copylang')){
            $this->copyLang();
        }

        //action for group
        if (Tools::isSubmit("editgroup") || Tools::isSubmit("submitGroup") || Tools::isSubmit("deletegroup") || Tools::isSubmit("addNewGroup") || Tools::isSubmit("exportgroup") || Tools::isSubmit("changeGStatus")) {
            if (Tools::isSubmit("submitGroup") || Tools::isSubmit("deletegroup") || Tools::isSubmit("changeGStatus")) {
                if ($this->postValidation()) {
                    $this->_postProcess();
                }
            }
            //save group id in config to edit in next time when open module
            if (Tools::isSubmit("submitGroup") || Tools::isSubmit("editgroup") || Tools::isSubmit("changeGStatus"))
                Configuration::updateValue('PTSSLIDERLAYER_GROUP_DE', (int) Tools::getValue('id_group'));
            //if (!Tools::isSubmit("addNewGroup"))
            //    $this->genGroupData();
            $this->_html .= $this->renderGroupList();
            $this->_html .= $this->renderGroupConfig();
        }
        elseif (Tools::isSubmit("showsliders") || Tools::isSubmit("submitSlider") || Tools::isSubmit("editSlider") || Tools::isSubmit("deleteSlider") || Tools::isSubmit("addNewSlider") || Tools::isSubmit("changeStatus")) {
            
            if (Tools::isSubmit("submitSlider") || Tools::isSubmit("deleteSlider") || Tools::isSubmit("changeStatus") || Tools::getValue("duplicateSlider")){
                if ($this->postValidation()) {
                    $this->_postProcess();
                }
            }
            $this->_html .= $this->renderList();
            $this->_html .= $this->renderConfig();
            $this->_html .= $this->renderSliderForm();
        }
        //action for slideshow
        else {
            //$this->genGroupData();
            $this->_html .= $this->renderGroupList();
            $this->_html .= $this->renderGroupConfig();
        }

        return $this->_html;
    }
    
    /*
     * this function is only for developer of prestabrain.com
     * to correct data for group + slider
     */
    public function correctDataGroup(){
        $id_group = Tools::getValue('id_group');
        if($id_group){
            $group = new PtsSliderGroup($id_group);
            
            if(Validate::isLoadedObject($group)){
               //correct group data
               $params = Tools::unSerialize($group->params);
               if($params){
                   $group->params = base64_encode(Tools::jsonEncode($params));
                   $group->save();
               }
               
               //correct slider
               $sliders = $this->getSlides($group->id);
               foreach ($sliders as $slider){
                   $sliderObj = new SliderLayer($slider["id_slide"]);
                   if(Validate::isLoadedObject($sliderObj)){
                       $tmp = Tools::unSerialize($sliderObj->params);
                       if($tmp) $sliderObj->params = base64_encode(Tools::jsonEncode($tmp));
                       
                       $tmpObj = array();
                       foreach($sliderObj->video as $key=>$value){
                           $tmp = Tools::unSerialize($value);
                           if($tmp)
                            $tmpObj[$key] = base64_encode(Tools::jsonEncode($tmp));
                       }
                       if($tmpObj)
                        $sliderObj->video = $tmpObj;
                       
                       $tmpObj = array();
                       foreach($sliderObj->layersparams as $key=>$value){
                           $tmp = Tools::unSerialize($value);
                           if($tmp){
                               $tmpObj[$key] = base64_encode(Tools::jsonEncode($tmp));
                           }
                       }
                       if($tmpObj)
                        $sliderObj->layersparams = $tmpObj;
                       //print_r($sliderObj);die;
                       $sliderObj->save();
                   }
               }
               
            }
        }
        
    }

    public function copyLang(){
        $id_group = Tools::getValue('id_group');
        if($id_group){
            $sliders = $this->getSlides($id_group);
            $sliderObj = new SliderLayer();
            $defined = $sliderObj->getDefinition($sliderObj);
            $defined = $defined['fields'];
            
            foreach ($sliders as $slider){
                $sliderObj = new SliderLayer($slider["id_slide"]);
                if($sliderObj->id){
                    $languages = Language::getLanguages(false);
                    $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

                    $tmp = array();
                    foreach ($languages as $language) {
                        if($language['id_lang'] == $default_lang){
                            foreach($defined as $key=>$val){
                                if(isset($val['lang']) && $val['lang']==1){
                                    $tmp[$key] = $sliderObj->{$key}[$default_lang];
                                }
                            }
                            break;
                        }
                    }
                    
                    foreach ($languages as $language) {
                        if($language['id_lang'] != $default_lang){
                            foreach($tmp as $key=>$val){
                                if($key=="layersparams"){
                                    $layersParams = Tools::jsonDecode(base64_decode($val), true);
                                    foreach($layersParams as &$layer){
                                        $layer['layer_id'] =  str_replace($default_lang."_",$language['id_lang']."_", $layer['layer_id']);
                                    }
                                    //echo "<pre>";print_r($layersParams);die;
                                    $sliderObj->layersparams[$language['id_lang']] = base64_encode(Tools::jsonEncode($layersParams));
                                }else{
                                    $sliderObj->{$key}[$language['id_lang']] = $val;
                                }

                            }
                        }
                    }
                    $sliderObj->update();
                }
            }
        }
    }

    public function importGroup(){
        include_once(_PS_MODULE_DIR_ . 'ptssliderlayer/controllers/admin/AdminPtsSliderLayer.php');
        $sliderController = new AdminPtsSliderLayerController();
        $res = $sliderController->importGroup();
        if (!$res)
            $this->_html .= $this->displayError('Could not import');
        else
            $this->_html .= $this->displayConfirmation($this->l('Importing was successful'));
    }
    
    /*
     * add new
     * delete
     * duplicate
     * changestatus
     */
    public function processAJax() {
        $result = array();
        if (Tools::getValue('action') && Tools::getValue('action') == 'submitslider') {
            if (Tools::getValue('id_slide')) {
                $slide = new SliderLayer((int) Tools::getValue('id_slide'));
                if (!Validate::isLoadedObject($slide)) {
                    $this->l('Invalid id_slide');
                    return;
                }
            }
            else
                $slide = new SliderLayer();

            $slide->position = (int) Tools::getValue('position');
            $slide->id_group = (int) Tools::getValue('id_group');
            /* Sets active */
            $slide->active = (int) Tools::getValue('active_slide');
            $slide->params = base64_encode(Tools::jsonEncode(Tools::getValue("slider")));

            $languages = Language::getLanguages(false);
            $tmpData = array();
            $tmpBackColor = "";

            foreach ($languages as $language) {
                $slide->title[$language['id_lang']] = Tools::getValue('title_' . $language['id_lang']);
                //get data default
                $slide->link[$language['id_lang']] = Tools::getValue('link_' . $language['id_lang']);
                $slide->image[$language['id_lang']] = Tools::getValue('image_' . $language['id_lang']);
                $slide->thumbnail[$language['id_lang']] = Tools::getValue('thumbnail_' . $language['id_lang']);

                $slide->video[$language['id_lang']] = base64_encode(Tools::jsonEncode($video));
                $layersparams = new stdClass();
                $layersparams->layers = array();

                if (Tools::getIsset('layers_' . $language['id_lang'])) {
                    $times = Tools::getValue('layer_time');
                    $layers = Tools::getValue('layers_' . $language['id_lang']);

                    //echo "<pre>";print_r($times);

                    foreach ($layers as $key => $value) {
                        $value['time_start'] = $times[$value['layer_id']];
                        //fix for php 5.2 and 5.3
                        $value['layer_caption'] = utf8_encode(str_replace(array("\'", '\"'), array("'", '"'), $value['layer_caption']));
                        $times[$value['layer_id']] = $value;
                    }
                    // echo "<pre>".$language['id_lang'];print_r($times);
                    $k = 0;
                    //echo "<pre>";print_r($times);
                    foreach ($times as $key => $value) {
                        if (is_array($times) && $key == @$value['layer_id']) {
                            $value['layer_id'] = $language['id_lang'] . '_' . ($k + 1);
                            $layersparams->layers[$k] = $value;
                            $k++;
                        }
                    }
                    $slide->layersparams[$language['id_lang']] = base64_encode(Tools::jsonEncode($layersparams->layers));
                } else {
                    //when add new create sample data for other language
                    if (!Tools::getValue('id_slide') && isset($tmpData["layersparams"]) && $tmpData["layersparams"]) {
                        //set id again
                        foreach ($tmpData["layersparams"] as &$tmpLayer) {
                            foreach ($tmpLayer as $key => &$value) {
                                if ($key == "layer_id") {
                                    $valu = explode("_", $value);
                                    $value = str_replace($valu[0] . "_", $language['id_lang'] . "_", $value);
                                }
                            }
                        }
                        //print_r($tmpData["layersparams"]);
                        $slide->layersparams[$language['id_lang']] = base64_encode(Tools::jsonEncode($tmpData["layersparams"]));
                    }
                    else
                        $slide->layersparams[$language['id_lang']] = "";
                }


                //get data default if add new
                if (!Tools::getValue('id_slide') && $slide->title && empty($tmpData)) {
                    $tmpData["title"] = $slide->title[$language['id_lang']];
                    $tmpData["link"] = $slide->link[$language['id_lang']];
                    $tmpData["video"] = $slide->video[$language['id_lang']];
                    $tmpData["image"] = $slide->image[$language['id_lang']];
                    $tmpData["thumbnail"] = $slide->image[$language['id_lang']];
                    $tmpData["id_lang"] = $language['id_lang'];
                    $tmpData["image"] = $slide->image[$language['id_lang']];
                }
                if (!Tools::getValue('id_slide') && !isset($tmpData["layersparams"])) {
                    $tmpData["layersparams"] = $layersparams->layers;
                }
            }
            /* Processes if no errors  */

            /* Adds */
            if (!Tools::getValue('id_slide')) {
                //add default image
                foreach ($slide->title as &$value)
                    if ($value == "")
                        $value = $tmpData["title"];

                foreach ($slide->link as &$value)
                    if ($value == "")
                        $value = $tmpData["link"];

                foreach ($slide->image as &$value)
                    if ($value == "")
                        $value = $tmpData["image"];

                foreach ($slide->video as &$value)
                    if ($value == "")
                        $value = $tmpData["video"];
                
                if (!$slide->add()) {
                    $result = array("error" => 1, "text" => $this->l('The slide could not be added.'));
                }
                //add slider
            }
            /* Update */ elseif (!$slide->update()) {
                $result = array("error" => 1, "text" => $this->l('The slide could not be updated.'));
            }
            $myLink = '&configure=ptssliderlayer&editSlider=1&id_slide=' . $slide->id . '&id_group=' . $slide->id_group;
            $result = array("error" => 0, "text" => $myLink);

            $this->clearCache();
            die(Tools::jsonEncode($result));
        }
        
        
        if (Tools::getValue('action') && Tools::getValue('action') == 'updateSlidesPosition' && Tools::getValue('slides')) {
            $slides = Tools::getValue('slides');

            foreach ($slides as $position => $id_slide) {
                $result = Db::getInstance()->execute('
			UPDATE `' . _DB_PREFIX_ . 'ptssliderlayer_slides` SET `position` = ' . (int) $position . '
			WHERE `id_ptssliderlayer_slides` = ' . (int) $id_slide
                );
            }
            die(Tools::jsonEncode($result));
            $this->clearCache();
        }
        
        if ( Tools::getValue('action') && Tools::getValue('action') == 'deleteSlider'){
            $id_slide = Tools::getValue('id_slide');
            $slide = new SliderLayer((int)$id_slide);
            if (!$slide->delete()){
                $result= array("error"=>1,"text"=>$this->l('The slide could not be delete.'));
             }
            $this->clearCache();
            die(Tools::jsonEncode($result));
        }
        
        if ( Tools::getValue('action') && Tools::getValue('action') == 'duplicateSlider'){
            $slide = new SliderLayer((int) Tools::getValue('id_slide'));
            $sliderNew = new SliderLayer();
            
            $defined = $sliderNew->getDefinition("SliderLayer");
            
            foreach ($defined["fields"] as $ke => $val) {
                if ($ke == "id") continue;
                
                if ($ke=="title"){
                    $tmp = array();
                    foreach($slide->title as $kt=>$vt){
                        $tmp[$kt] = $this->l("Duplicate of")." ".$vt;
                    }
                    $sliderNew->{$ke} = $tmp;
                }else
                    $sliderNew->{$ke} = $slide->{$ke};
            }
            if(!$sliderNew->add()){
                $result= array("error"=>1,"text"=>$this->l('The slide could not be duplicate.'));
            }
            
            $this->clearCache();
            die(Tools::jsonEncode($result));
        }
        
    }

    public function renderList() {
        //get curent slider data
        if (Tools::isSubmit('id_slide') && $this->slideExists((int) Tools::getValue('id_slide'))) {
            $this->_currentSlider = new SliderLayer((int) Tools::getValue('id_slide'));
        } else {
            $this->_currentSlider = new SliderLayer();
        }

        $slides = $this->getSlides(Tools::getValue("id_group"));
        foreach ($slides as $key => $slide)
            $slides[$key]['status'] = $this->displayStatus($slide['id_slide'], $slide['active'], $slide['id_group']);

        $groupObj = new PtsSliderGroup((int) Tools::getValue('id_group'));
        $id_shop = $this->context->shop->id;
        
        if($id_shop != $groupObj->id_shop) 
            Tools::redirectAdmin( AdminController::$currentIndex .'&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'));
        $jsonData = Tools::jsonDecode(base64_decode($groupObj->params), true);
        if(!is_array($jsonData))
            $jsonData = array();
        $this->groupData = array_merge($this->groupData, $jsonData);
        $this->groupData['current_language'] = $this->context->language->id;
        $arrayParam['secure_key'] = $this->secure_key;
        
        $this->context->smarty->assign(array(
            'link' => $this->context->link,
            'slides' => $slides,
            'id_group' => Tools::getValue("id_group"),
            'group_title' => $groupObj->title,
            'languages' => $this->context->controller->getLanguages(),
            'previewLink' => Context::getContext()->link->getModuleLink($this->name, 'preview', array('secure_key'=>$this->secure_key, 'id_slide' => (int) Tools::getValue('id_slide') )),
            'msecure_key'  => $this->secure_key,
            'currentSliderID' => $this->_currentSlider->id
        ));

        return $this->display(__FILE__, 'list.tpl');
    }

    /*
     * return group config form
     */

    public function renderConfig() {
        $description = $this->l('Add New Slider');

        $transition = array(
            array('id' => 'strip-down-right', 'name' => $this->l('strip-down-right')),
            array('id' => 'strip-down-left', 'name' => $this->l('strip-down-left')),
            array('id' => 'strip-up-right', 'name' => $this->l('strip-up-right')),
            array('id' => 'strip-up-left', 'name' => $this->l('strip-up-left')),
            array('id' => 'strip-up-down', 'name' => $this->l('strip-up-down')),
            array('id' => 'strip-up-down-left', 'name' => $this->l('strip-up-down-left')),
            array('id' => 'strip-left-right', 'name' => $this->l('strip-left-right')),
            array('id' => 'strip-left-right-down', 'name' => $this->l('strip-left-right-down')),
            array('id' => 'slide-in-right', 'name' => $this->l('slide-in-right')),
            array('id' => 'slide-in-left', 'name' => $this->l('slide-in-left')),
            array('id' => 'slide-in-up', 'name' => $this->l('slide-in-up')),
            array('id' => 'slide-in-down', 'name' => $this->l('slide-in-down')),
            array('id' => 'left-curtain', 'name' => $this->l('left-curtain')),
            array('id' => 'right-curtain', 'name' => $this->l('right-curtain')),
            array('id' => 'top-curtain', 'name' => $this->l('top-curtain')),
            array('id' => 'bottom-curtain', 'name' => $this->l('bottom-curtain')),
            array('id' => 'fade', 'name' => $this->l('fade')),
            array('id' => 'block-random', 'name' => $this->l('block-random')),
            array('id' => 'block-fade', 'name' => $this->l('block-fade')),
            array('id' => 'block-fade-reverse', 'name' => $this->l('block-fade-reverse')),
            array('id' => 'block-expand', 'name' => $this->l('block-expand')),
            array('id' => 'block-expand-reverse', 'name' => $this->l('block-expand-reverse')),
            array('id' => 'block-expand-random', 'name' => $this->l('block-expand-random')),
            array('id' => 'block-drop-random', 'name' => $this->l('block-drop-random')),
            array('id' => 'zigzag-top', 'name' => $this->l('zigzag-top')),
            array('id' => 'zigzag-bottom', 'name' => $this->l('zigzag-bottom')),
            array('id' => 'zigzag-grow-top', 'name' => $this->l('zigzag-grow-top')),
            array('id' => 'zigzag-grow-bottom', 'name' => $this->l('zigzag-grow-bottom')),
            array('id' => 'zigzag-drop-top', 'name' => $this->l('zigzag-drop-top')),
            array('id' => 'zigzag-drop-bottom', 'name' => $this->l('zigzag-drop-bottom')),
            array('id' => 'strip-left-fade', 'name' => $this->l('strip-left-fade')),
            array('id' => 'strip-right-fade', 'name' => $this->l('strip-right-fade')),
            array('id' => 'strip-top-fade', 'name' => $this->l('strip-top-fade')),
            array('id' => 'strip-bottom-fade', 'name' => $this->l('strip-bottom-fade')),

        );
        $easing = array(
            array('id' => 'easeOutBack', 'name' => $this->l('easeOutBack')),
            array('id' => 'easeInQuad', 'name' => $this->l('easeInQuad')),
            array('id' => 'easeOutQuad', 'name' => $this->l('easeOutQuad')),
            array('id' => 'easeInOutQuad', 'name' => $this->l('easeInOutQuad')),
            array('id' => 'easeInCubic', 'name' => $this->l('easeInCubic')),
            array('id' => 'easeOutCubic', 'name' => $this->l('easeOutCubic')),
            array('id' => 'easeInOutCubic', 'name' => $this->l('easeInOutCubic')),
            array('id' => 'easeInQuart', 'name' => $this->l('easeInQuart')),
            array('id' => 'easeOutQuart', 'name' => $this->l('easeOutQuart')),
            array('id' => 'easeInOutQuart', 'name' => $this->l('easeInOutQuart')),
            array('id' => 'easeInQuint', 'name' => $this->l('easeInQuint')),
            array('id' => 'easeOutQuint', 'name' => $this->l('easeOutQuint')),
            array('id' => 'easeInOutQuint', 'name' => $this->l('easeInOutQuint')),
            array('id' => 'easeInSine', 'name' => $this->l('easeInSine')),
            array('id' => 'easeOutSine', 'name' => $this->l('easeOutSine')),
            array('id' => 'easeInOutSine', 'name' => $this->l('easeInOutSine')),
            array('id' => 'easeInExpo', 'name' => $this->l('easeInExpo')),
            array('id' => 'easeOutExpo', 'name' => $this->l('easeOutExpo')),
            array('id' => 'easeInOutExpo', 'name' => $this->l('easeInOutExpo')),
            array('id' => 'easeInCirc', 'name' => $this->l('easeInCirc')),
            array('id' => 'easeOutCirc', 'name' => $this->l('easeOutCirc')),
            array('id' => 'easeInOutCirc', 'name' => $this->l('easeInOutCirc')),
            array('id' => 'easeInElastic', 'name' => $this->l('easeInElastic')),
            array('id' => 'easeOutElastic', 'name' => $this->l('easeOutElastic')),
            array('id' => 'easeInOutElastic', 'name' => $this->l('easeInOutElastic')),
            array('id' => 'easeInBack', 'name' => $this->l('easeInBack')),
            array('id' => 'easeOutBack', 'name' => $this->l('easeOutBack')),
            array('id' => 'easeInOutBack', 'name' => $this->l('easeInOutBack')),
            array('id' => 'easeInBounce', 'name' => $this->l('easeInBounce')),
            array('id' => 'easeOutBounce', 'name' => $this->l('easeOutBounce')),
            array('id' => 'easeInOutBounce', 'name' => $this->l('easeInOutBounce')),
        );

        if (!Tools::isSubmit("deleteSlider") && !Tools::isSubmit("addNewSlider") && !Tools::isSubmit("showsliders")) {

            $description = $this->l('You are editting slider:') . " " . $this->_currentSlider->title[$this->context->language->id];
        }

        //general config
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $description,
                    'icon' => 'icon-cogs'
                ),
                //'description' =>$description,
                'input' => array(
                    array(
                        'type' => 'slider_button',
                        'name' => 'slider_button',
                        'lang' => FALSE,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Slider Title:'),
                        'name' => 'title',
                        'class' => 'slider-title',
                        'required' => 1,
                        'lang' => true,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Active:'),
                        'name' => 'active_slide',
                        'is_bool' => true,
                        'values' => $this->getSwitchValue('active'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Transition:'),
                        'name' => 'slider[transition]',
                        'options' => array(
                            'query' => $transition,
                            'id' => 'id',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Easing:'),
                        'name' => 'slider[easing]',
                        'options' => array(
                            'query' => $easing,
                            'id' => 'id',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Pause Time:'),
                        'name' => 'slider[pausetime]',
                        'lang' => false,
                        'default' => 800,
                        'desc' => $this->l('The milliseconds between the end of the sliding effect and the start of the next one'),
                    ),
                    //thumb + main image
                    array(
                        'type' => 'file_lang',
                        'label' => $this->l('Thumbnail:'),
                        'name' => 'thumbnail',
                        'lang' => true,
                    ),
                    array(
                        'type' => 'hidden',
                        'label' => $this->l('Current language:'),
                        'name' => 'current_language',
                        'lang' => false
                    ),
                )
            ),
        );

        if (Tools::getValue('id_slide') && $this->slideExists((int) Tools::getValue('id_slide'))) {
            $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_slide');
        }
 
        $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_group');


        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->name_controller = 'sliderlayer';
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSlider';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $this->groupData['current_language'] = $this->context->language->id;
        $helper->tpl_vars = array(
            'fields_value' => $this->getSliderFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'current_language' => $this->context->language->id,
            'sliderGroup' => $this->groupData,
            'sliderTransition' => $transition,
            'psBaseModuleUri' => $this->img_url
        );

        return $helper->generateForm(array($fields_form));
    }

    /*
     * generate data
     */

    public function getSliderFieldsValues() {
        $fields = array();
        $slide = $this->_currentSlider;

        if (isset($this->_currentSlider->id) && $this->_currentSlider->id) {
            $fields['id_slide'] = (int) $this->_currentSlider->id;
            $slide = $this->_currentSlider;
            //gendata for config
            $jsonData = Tools::jsonDecode(base64_decode($slide->params), true);
            if(!is_array($jsonData))
                $jsonData = array();
            $this->_sliderData = array_merge($this->_sliderData, $jsonData);
        }

        $fields['active_slide'] = Tools::getValue('active_slide', $slide->active);
        $fields['has_picture'] = true;
        $fields['id_group'] = Tools::getValue('id_group', $slide->id_group);

        $languages = Language::getLanguages(false);

        
        foreach ($languages as $lang) {
            $fields['image'][$lang['id_lang']] = Tools::getValue('image_' . (int) $lang['id_lang'], $slide->image[$lang['id_lang']]);
            $fields['thumbnail'][$lang['id_lang']] = Tools::getValue('thumbnail_' . (int) $lang['id_lang'], $slide->thumbnail[$lang['id_lang']]);
            $fields['title'][$lang['id_lang']] = Tools::getValue('title_' . (int) $lang['id_lang'], $slide->title[$lang['id_lang']]);
            $fields['link'][$lang['id_lang']] = Tools::getValue('link_' . (int) $lang['id_lang'], $slide->link[$lang['id_lang']]);
            
        }
        //slider no lang
        foreach ($this->_sliderData as $key => $value) {
            $fields["slider[" . $key . "]"] = Tools::getValue("slider[" . $key . "]", $value);
        }
        $fields["current_language"] = $this->context->language->id;
        //slider with lang
        
        return $fields;
    }

    /*
     * slider Editor
     */

    public function renderSliderForm() {
        $layerAnimation = array(
            array('id' => 'fade', 'name' => $this->l('Fade')), 
            array('id' => 'wipeLeft', 'name' => $this->l('wipeLeft')),
            array('id' => 'wipeRight', 'name' => $this->l('wipeRight')),
            array('id' => 'wipeTop', 'name' => $this->l('wipeTop')),
            array('id' => 'wipeBottom', 'name' => $this->l('wipeBottom')),
            array('id' => 'expandLeft', 'name' => $this->l('expandLeft')),
            array('id' => 'expandRight', 'name' => $this->l('expandRight')),
            array('id' => 'expandTop', 'name' => $this->l('expandTop')),
            array('id' => 'expandBottom', 'name' => $this->l('expandBottom')),
            );
        $layers = array();
        if ($this->_currentSlider->layersparams) {
            $layers = array();
            
            foreach ($this->_currentSlider->layersparams as $key => $val) {
                $layer = Tools::jsonDecode(base64_decode($val), true);
                if ($layer)
                    foreach ($layer as $k => &$l) {
                        if (isset($l['layer_caption']))
                            $l['layer_caption'] = addslashes(str_replace("'", '&apos;', html_entity_decode(str_replace(array("\n", "\r", "\t"), '', utf8_decode($l['layer_caption'])), ENT_QUOTES, 'UTF-8')));
                    }
                 
                $layers[] = array("langID" => $key, "content" => json_encode($layer));
            }
        }
        $slideImg = $this->_currentSlider->image;
        $sliderBack = array();
        if($this->_currentSlider->video)
        foreach ($this->_currentSlider->video as $key => $val) {
            $video = Tools::jsonDecode(base64_decode($val), true);
            $sliderBack[$key] = "";
            if(isset($video["background_color"]))
                $sliderBack[$key] = $video["background_color"];
        }
        $this->groupData['current_language'] = $this->context->language->id;
        $this->context->smarty->assign(array(
            'link' => $this->context->link,
            'slideImg' => $slideImg,
            'sliderBack' => $sliderBack,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'layerAnimation' => $layerAnimation,
            'sliderGroup' => $this->groupData,
            'layers' => $layers,
            'ajaxfilelink' => Context::getContext()->link->getAdminLink('AdminPtsSliderLayer'),
            'formLink' => _MODULE_DIR_.$this->name.'/ajax_' . $this->name . '.php?secure_key=' . $this->secure_key . '&action=submitslider',
            'psBaseModuleUri' => $this->img_url,
            'previewLink' => Context::getContext()->link->getModuleLink($this->name, 'preview', array('secure_key'=>$this->secure_key)),
            'msecure_key'  => $this->secure_key,
            'id_group' => Tools::getValue("id_group"),
            'id_slide' => $this->_currentSlider->id
        ));

        return $this->display(__FILE__, 'slider_editor.tpl');
    }
    
    public function checkExistAnyGroup(){
        $this->context = Context::getContext();
        $id_shop = $this->context->shop->id;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'ptssliderlayer_groups gr WHERE gr.id_shop = ' . (int) $id_shop);
    }
    /*
     * get group via hookname
     */
    public function getSliderGroupByHook($hookName = '', $active = 1) {
        $this->context = Context::getContext();
        $id_shop = $this->context->shop->id;
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                    SELECT *
                    FROM ' . _DB_PREFIX_ . 'ptssliderlayer_groups gr
                    WHERE gr.id_shop = ' . (int) $id_shop . '
                    AND gr.hook = "' . $hookName . '"' .
                        ($active ? ' AND gr.`active` = 1' : ' ') . '
                    ORDER BY gr.id_ptssliderlayer_groups');
    }

    /*
     * get all slider data
     */
    public function getSlides($id_group, $active = null) {
        $this->context = Context::getContext();
        $id_lang = $this->context->language->id;

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
                    SELECT lsl.`id_ptssliderlayer_slides` as id_slide,
                                      lsl.*,lsll.*
                    FROM ' . _DB_PREFIX_ . 'ptssliderlayer_slides lsl
                    LEFT JOIN ' . _DB_PREFIX_ . 'ptssliderlayer_slides_lang lsll ON (lsl.id_ptssliderlayer_slides = lsll.id_ptssliderlayer_slides)
                    WHERE lsl.id_group = ' . (int) $id_group . '
                    AND lsll.id_lang = ' . (int) $id_lang .
                        ($active ? ' AND lsl.`active` = 1' : ' ') . '
                    ORDER BY lsl.position');
    }

    /*
     * return list group
     */
    public function renderGroupList() {
        $obj = new PtsSliderGroup();
		$id_shop = $this->context->shop->id;
        $groups = $obj->getGroups(null,$id_shop);
        foreach ($groups as $key => $group) {
            if ($group['id_ptssliderlayer_groups'] == Tools::getValue("id_group") || (!Tools::getValue("id_group") && !Tools::isSubmit("addNewGroup") &&$group['id_ptssliderlayer_groups'] == Configuration::get('PTSSLIDERLAYER_GROUP_DE'))){
                $this->_currentGroup["id_group"] = $group['id_ptssliderlayer_groups'];
                $this->_currentGroup["title"] = $group['title'];
                
                $params = Tools::jsonDecode(base64_decode($group["params"]), true);
                if ($params)
                    foreach ($params as $k => $v) {
                        $groupResult[$k] = $v;
                    }
                $groupResult["title"] = $group["title"];
                $groupResult["id_ptssliderlayer_groups"] = $group["id_ptssliderlayer_groups"];
                $groupResult["id_shop"] = $group["id_shop"];
                $groupResult["hook"] = $group["hook"];
                $groupResult["active"] = $group["active"];

                if ($groupResult)
                    $this->groupData = array_merge($this->groupData, $groupResult);
            }

            $groups[$key]['status'] = $this->displayGStatus($group['id_ptssliderlayer_groups'], $group['active']);
        }
        $this->context->smarty->assign(array(
            'link' => $this->context->link,
            'groups' => $groups,
            'curentGroup' => $this->_currentGroup["id_group"],
            'languages' => $this->context->controller->getLanguages(),
            'exportLink' => Context::getContext()->link->getAdminLink('AdminPtsSliderLayer')."&ajax=1&exportGroup=1",
            'previewLink' => Context::getContext()->link->getModuleLink($this->name, 'preview', array('secure_key'=>$this->secure_key)),
            'msecure_key'  => $this->secure_key
        ));

        return $this->display(__FILE__, 'grouplist.tpl');
    }

    /*
     * return group config form
     */

    public function renderGroupConfig() {
        $description = $this->l('Add New Group');
        if (!Tools::isSubmit("deletegroup") && !Tools::isSubmit("addNewGroup") && $this->_currentGroup["id_group"]) {
            $description = $this->l('You are editting group:') . " " . $this->_currentGroup["title"];
        }
        foreach ($this->_hookSupport as $value) {
            $selectHook[] = array("id" => $value, "name" => $value);
        }

        $shadowType = array(array("id" => 0, "name" => $this->l("No Shadown")), array("id" => "1", "name" => 1),
            array("id" => "2", "name" => 2), array("id" => "3", "name" => 3));

        $timeLinerPosition = array(array("id" => "bottom", "name" => $this->l("Bottom")), array("id" => "top", "name" => $this->l("Top")));
        
        $arrayCol = array('12','10','9-6','9','8','7-2','6','4-8','4','3','2-4','2');
        
        $navigatorType = array(array("id" => "0", "name" => $this->l("None")), 
            array("id" => "bullet", "name" => $this->l("Bullet")),
            array("id" => "thumb", "name" => $this->l("Thumbnail")),
            array("id" => "custom", "name" => $this->l("Custom"))
        );
        $navigatorArrows = array(array("id" => "0", "name" => $this->l("No")), 
            array("id" => "1", "name" => $this->l("Yes"))
            );
        
        $navigationStyle = array(array("id" => "round", "name" => $this->l("Round")), array("id" => "navbar", "name" => $this->l("Navbar")),
            array("id" => "round-old", "name" => $this->l("Round Old")), array("id" => "square-old", "name" => $this->l("Square Old")), array("id" => "navbar-old", "name" => $this->l("Navbar Old")));
        
        $hiden_phone = array(array("id" => "", "name" => $this->l("None")), array("id" => "hidden-xs", "name" => $this->l("hidden in phone")),
            array("id" => "hidden-sm", "name" => $this->l("Hidden in tablet")));
        
        $hidden_config = array('hidden-lg'=>$this->l('Hidden in Large devices'),'hidden-md'=>$this->l('Hidden in Medium devices'),
            'hidden-sm'=>$this->l('Hidden in Small devices'),'hidden-xs'=>$this->l('Hidden in Extra small devices'),'hidden-sp'=>$this->l('Hidden in Smart Phone'));
        
        $show_time_line = array(
            array("id" => "0", "name" => $this->l("No")),
            array("id" => "Pie", "name" => $this->l("Pie")),
            array("id" => "360Bar", "name" => $this->l("360Bar")),
            array("id" => "Bar", "name" => $this->l("Bar")),
        );
        //general config
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $description,
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'group_button',
                        'id_group' => $this->_currentGroup["id_group"],
                        'name' => 'group_button',
                        'lang' => FALSE,
                    ),
                    array(
                        'type' => 'sperator_form',
                        'text' => $this->l('General Setting'),
                        'name' => 'sperator_form',
                        'show_button' => 1,
                        'lang' => FALSE,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Group Title:'),
                        'name' => 'title_group',
                        'lang' => FALSE,
                        'required' => 1
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Active:'),
                        'name' => 'active_group',
                        'is_bool' => true,
                        'values' => $this->getSwitchValue('active'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Show in Hook:'),
                        'name' => 'hook_group',
                        'options' => array(
                            'query' => $selectHook,
                            'id' => 'id',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Auto Play:'),
                        'name' => 'group[auto_play]',
                        'is_bool' => true,
                        'values' => $this->getSwitchValue('active'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Delay:'),
                        'name' => 'group[delay]',
                        'lang' => FALSE,
                    ),
                    array(
                        'type'  => 'col_width',
                        'label' => $this->l('Medium and Large Desktops Width:'),
                        'name'  => 'group[md_width]',
                        'class' => 'mode-width mode-',
                        'lang' => FALSE
                    ),
                    array(
                        'type'  => 'col_width',
                        'label' => $this->l('Small devices Tablets Width:'),
                        'name'  => 'group[sm_width]',
                        'class' => 'mode-width mode-',
                        'arrayVal' => $arrayCol,
                        'lang' => FALSE
                    ),
                    array(
                        'type'  => 'col_width',
                        'label' => $this->l('Extra small devices Phones:'),
                        'name'  => 'group[xs_width]',
                        'class' => 'mode-width mode-',
                        'arrayVal' => $arrayCol,
                        'lang' => FALSE
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Stop On Hover'),
                        'name' => 'group[stop_on_hover]',
                        'is_bool' => true,
                        'values' => $this->getSwitchValue('stop_on_hover'),
                    ),
                    
                    array(
                        'type' => 'sperator_form',
                        'text' => $this->l('Css Setting'),
                        'name' => 'sperator_form',
                        'lang' => FALSE,
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Show Time Line :'),
                        'name' => 'group[show_time_line]',
                        'options' => array(
                            'query' => $show_time_line,
                            'id' => 'id',
                            'name' => 'name',
                        )
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Margin:'),
                        'name' => 'group[margin]',
                        'lang' => FALSE,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Padding(border):'),
                        'name' => 'group[padding]',
                        'lang' => FALSE,
                    ),
                    array(
                        'type' => 'color',
                        'label' => $this->l('Background Color:'),
                        'name' => 'group[background_color]',
                        'lang' => FALSE,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Show Background Image:'),
                        'name' => 'group[background_image]',
                        'is_bool' => true,
                        'values' => $this->getSwitchValue('background_image'),
                        'desc' => $this->l("This configuration will override back-ground color config"),
                    ),
                    array(
                        'type' => 'group_background',
                        'label' => $this->l('Background URL:'),
                        'name' => 'group[background_url]',
                        'id'   => 'background_url',
                        'lang' => FALSE
                    ),
                    array(
                        'type' => 'group_class',
                        'label' => $this->l('Group Class:'),
                        'name' => 'group[group_class]'
                    ),
                    array(
                        'type' => 'sperator_form',
                        'text' => $this->l('Navigator'),
                        'name' => 'sperator_form',
                        'lang' => FALSE,
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Navigator Type:'),
                        'name' => 'group[navigator_type]',
                        'options' => array(
                            'query' => $navigatorType,
                            'id' => 'id',
                            'name' => 'name',
                        ),
                        'desc' => $this->l('Thumbnail   ** In Fullwidth version thumbs wont be displayed  if navigation offset set to shwop thumbs outside of the container ! Thumbs must be showen in the container!')
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Arrows:'),
                        'name' => 'group[navigator_arrows]',
                        'options' => array(
                            'query' => $navigatorArrows,
                            'id' => 'id',
                            'name' => 'name',
                        ),
                        'desc'=>$this->l('Next to Bullets only apply for Navigator Type: Bullets')
                    ),
                    array(
                        'type' => 'sperator_form',
                        'text' => $this->l('Image Setting'),
                        'name' => 'sperator_form',
                        'lang' => FALSE,
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Image Cropping:'),
                        'name' => 'group[image_cropping]',
                        'is_bool' => true,
                        'desc' => $this->l('Auto turn off is you use mode fullscreen for slideshow'),
                        'values' => $this->getSwitchValue('image_cropping'),
                    ),
                    array(
                        'type'  => 'text',
                        'label' => $this->l('Image Width:'),
                        'name'  => 'group[width]',
                        'lang' => FALSE,
                        'desc' => $this->l('Use for resize image and Max-Height')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Image Height:'),
                        'name' => 'group[height]',
                        'lang' => FALSE,
                        'desc' => $this->l('Use for resize image and Max-Height')
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Thumbnail Width:'),
                        'name' => 'group[thumbnail_width]',
                        'lang' => FALSE,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Thumbnail Height:'),
                        'name' => 'group[thumbnail_height]',
                        'lang' => FALSE,
                    )
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default')
            ),
        );
        
        if (Tools::isSubmit('id_group') && PtsSliderGroup::groupExists((int) Tools::getValue('id_group'))) {
            //$slide = new PtsSliderGroup((int)Tools::getValue('id_group'));
            $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_group');
        }  else if($this->_currentGroup["id_group"] && PtsSliderGroup::groupExists($this->_currentGroup["id_group"])){
            $fields_form['form']['input'][] = array('type' => 'hidden', 'name' => 'id_group');
        }


        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->name_controller = 'sliderlayer';
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $this->fields_form = array();

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGroup';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getGroupFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'exportLink' => Context::getContext()->link->getAdminLink('AdminPtsSliderLayer')."&exportGroup=1",
            'psBaseModuleUri' => $this->img_url,
            'ajaxfilelink' => Context::getContext()->link->getAdminLink('AdminPtsSliderLayer'),
            'leo_width' => $arrayCol,
            'hidden_config' => $hidden_config
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getSwitchValue($id) {
        return array(array('id' => $id . '_on', 'value' => 1, 'label' => $this->l('Yes')),
            array('id' => $id . '_off', 'value' => 0, 'label' => $this->l('No')));
    }

    public function getGroupFieldsValues() {
        $field = array("id_ptssliderlayer_groups", "title", "id_shop", "hook", "active");
        $this->groupData['current_language'] = $this->context->language->id;
        foreach ($this->groupData as $key => $value) {
            if (in_array($key, $field)) {
                if ($key == "id_ptssliderlayer_groups")
                    $group["id_group"] = $value;
                else
                    $group[$key . "_group"] = $value;
                continue;
            }
            $group["group[" . $key . "]"] = $value;
        }
        return $group;
    }

    public function postValidation() {
        $errors = array();
        
        if (Tools::isSubmit('submitGroup')){
            if (Tools::isSubmit('id_group'))
            {
                if (!Validate::isInt(Tools::getValue('id_group')) && !PtsSliderGroup::groupExists(Tools::getValue('id_group')))
                        $errors[] = $this->l('Invalid id_group');
            }
            $groupValue = Tools::getValue("group");
            /* Checks state (active) */
			if (!Tools::getValue('title_group'))
					$errors[] = $this->l('Invalid title group');
            if (!Validate::isInt(Tools::getValue('active_group')) || (Tools::getValue('active_group') != 0 && Tools::getValue('active_group') != 1))
                    $errors[] = $this->l('Invalid group state');
            
            if (!Validate::isInt($groupValue['stop_on_hover']) || ($groupValue['stop_on_hover'] != 0 && $groupValue['stop_on_hover'] != 1))
                    $errors[] = $this->l('Invalid stop on hover state');

            if (!Validate::isInt($groupValue['image_cropping']) || ($groupValue['image_cropping'] != 0 && $groupValue['image_cropping'] != 1))
                    $errors[] = $this->l('Invalid Image Cropping state');

            if (!Validate::isInt($groupValue['background_image']) || ($groupValue['background_image'] != 0 && $groupValue['background_image'] != 1))
                    $errors[] = $this->l('Invalid Show Background Image state');
            
            //check interger isUnsignedInt
            $intArray = array("delay"=>$this->l('Invalid Delay value'), "width"=>$this->l('Invalid Width value'), "height"=>$this->l('Invalid Height value'),
                "offset_horizontal"=>$this->l('Invalid Offset Horizontal value'), "offset_vertical"=>$this->l('Invalid Offset Vertical value'), "hide_navigator_after"=>$this->l('Invalid Hide Navigator After value'), 
                "thumbnail_width"=>$this->l('Invalid Thumbnail Width value'), "thumbnail_height"=>$this->l('Invalid Thumbnail Height value'));
            
            foreach($intArray as $key=>$value){
                if (isset($groupValue[$key]) && !Validate::isInt($groupValue[$key]) && $groupValue[$key]!="")
                    $errors[] = $value;
            }
            if (!Validate::isColor(Tools::getValue("background_color")))
                $errors[] = $this->l('Invalid Background color value');
        }
        
        /* Display errors if needed */
        if (count($errors))
        {
                $this->_error_text .=  implode('<br>', $errors);
                $this->_html .= $this->displayError(implode('<br />', $errors));
                return false;
        }

        /* Returns if validation is ok */
        return true;
    }
    
    public function getErrorLog(){
        return $this->_error_text;
    }

    private function _postProcess() {
        $errors = array();
        /* Processes Slider */
        if (Tools::isSubmit('submitGroup')) {/* Sets ID if needed */
            if (Tools::getValue('id_group')) {
                $group = new PtsSliderGroup((int) Tools::getValue('id_group'));
                if (!Validate::isLoadedObject($group)) {
                    $this->_html .= $this->displayError($this->l('Invalid id_group'));
                    return;
                }
            }
            else
                $group = new PtsSliderGroup();

            /* Sets position */
            $group->title = Tools::getValue('title_group');
            /* Sets active */
            $group->active = (int) Tools::getValue('active_group');
            $context = Context::getContext();
            $group->id_shop = $context->shop->id;
            $group->hook = Tools::getValue('hook_group');

            $params = Tools::getValue("group");
            $group->params = base64_encode(Tools::jsonEncode($params));


            /* Adds */
            if (!Tools::getValue('id_group')) {
                if (!$group->add())
                    $errors[] = $this->displayError($this->l('The group could not be added.'));
				else{
                    $this->clearCache();
					Tools::redirectAdmin( AdminController::$currentIndex .'&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules').'&editgroup=1&id_group='.$group->id );
                }
            }
            /* Update */
            else{
				if (!$group->update())
                    $errors[] = $this->displayError($this->l('The group could not be updated.'));
				else {
                    $this->clearCache();
					Tools::redirectAdmin( AdminController::$currentIndex .'&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules').'&editgroup=1&id_group='.$group->id );
                }
			}	
            //save in config to edit next time
            $this->clearCache();
        } /* Process Slide status */
        elseif (Tools::isSubmit('changeGStatus') && Tools::isSubmit('id_group')) {
            $group = new PtsSliderGroup((int) Tools::getValue('id_group'));
            if ($group->active == 0)
                $group->active = 1;
            else
                $group->active = 0;
            $res = $group->update();
            $this->clearCache();
            $this->_html .= ($res ? $this->displayConfirmation($this->l('Change status of group was successful')) : $this->displayError($this->l('The configuration could not be updated.')));
        }elseif (Tools::isSubmit('deletegroup')) {
            $group = new PtsSliderGroup((int) Tools::getValue('id_group'));
            //delete slider of group
            $slider = $this->getSlides((int) Tools::getValue('id_group'));

            foreach ($slider as $value) {
                $sliderObj = new SliderLayer($value["id_ptssliderlayer_slides"]);
                $sliderObj->delete();
            }

            $res = $group->delete();


            $this->clearCache();
            if (!$res)
                $this->_html .= $this->displayError('Could not delete');
            else
                $this->_html .= $this->displayConfirmation($this->l('Group deleted'));
				
			Tools::redirectAdmin( 'index.php?controller=AdminModules&token=' . Tools::getAdminTokenLite('AdminModules').'&configure=ptssliderlayer&tab_module=prestabrain&module_name=ptssliderlayer&conf=4'); 	
        }else if (Tools::isSubmit("changeStatus")) {
            $slide = new SliderLayer((int) Tools::getValue('sslider'));
            if ($slide->active == 0)
                $slide->active = 1;
            else
                $slide->active = 0;
            $res = $slide->update();
            $this->clearCache();
            $this->_html .= ($res ? $this->displayConfirmation($this->l('Change status of slide was successful')) : $this->displayError($this->l('The configuration could not be updated.')));
        }

        /* Display errors if needed */
        if (count($errors))
            $this->_html .= $this->displayError(implode('<br />', $errors));
        elseif (Tools::isSubmit('submitGroup'))
            $this->_html .= $this->displayConfirmation($this->l('Slide added'));
        elseif (Tools::isSubmit('submitGroup'))
            $this->_html .= $this->displayConfirmation($this->l('Slide added'));
    }

    private function _prepareHook($hookName, $group = null) {
        if (!$this->isCached($this->name.'.tpl', $this->getCacheId())) {

            if (!is_dir(_PS_ROOT_DIR_ . '/cache/' . $this->name)) {
                mkdir(_PS_ROOT_DIR_ . '/cache/' . $this->name, 0755);
            }

            //get slider via hookname
            if(!$group){
				$group = $this->getSliderGroupByHook($hookName);
			}
            if (!$group)
                return false;
            $sliders = $this->getSlides($group["id_ptssliderlayer_groups"], 1);
            if (!$sliders)
                return false;


            $sliderParams = Tools::jsonDecode(base64_decode($group["params"]), true);
			
			if(!is_array($sliderParams))
				return '';
			$sliderParams = array_merge($this->groupData, $sliderParams);
            
            $sliderParams['hide_navigator_after'] = $sliderParams['show_navigator'] ? 0 : $sliderParams['hide_navigator_after'];
            $sliderParams['slider_class'] = trim(isset($sliderParams['fullwidth']) && !empty($sliderParams['fullwidth']) ? $sliderParams['fullwidth'] : 'boxed');
            $sliderFullwidth = $sliderParams['slider_class'] == "boxed" ? "off" : "on";

            //generate back-ground
            if($sliderParams["background_image"])
               $sliderParams["background"] = 'background: url('.__PS_BASE_URI__ . "modules/" . $this->name . "/images/".$sliderParams["background_url"].') no-repeat scroll left 0 '.$sliderParams["background_color"].';';
            else
               $sliderParams["background"] = 'background-color:'.$sliderParams["background_color"];
           
            //include library genimage
            if (!class_exists('PhpThumbFactory')) {
                require_once _PS_MODULE_DIR_ . 'ptssliderlayer/libs/phpthumb/ThumbLib.inc.php';
            }

            //process slider
            foreach ($sliders as $key => $slider) {
                $slider["layers"] = array();
                $slider['params'] = Tools::jsonDecode(base64_decode($slider["params"]), true);
                $slider['layersparams'] = Tools::jsonDecode(base64_decode($slider["layersparams"]), true);
                
                if($slider['image'] == '') $slider['image'] = "blank.gif";
                if ($sliderParams['image_cropping'] && 1 == 0) {
                    //gender main_image
                    if ($slider['image'] && file_exists($this->img_path. $slider['image'])) {
                        $slider['main_image'] = $this->renderThumb($slider['image'], $sliderParams['width'], $sliderParams['height']);
                    }
                    else
                        $slider['main_image'] = '';

                    if ($slider['thumbnail'] && file_exists($this->img_path . $slider['thumbnail'])) {
                        $slider['thumbnail'] = $this->renderThumb($slider['thumbnail'], $sliderParams['thumbnail_width'], $sliderParams['thumbnail_height']);
                    } else if ($slider['image'] && file_exists($this->img_path . $slider['image'])) {
                        $slider['thumbnail'] = $this->renderThumb($slider['image'], $sliderParams['thumbnail_width'], $sliderParams['thumbnail_height']);
                    } else {
                        $slider['thumbnail'] = '';
                    }
                } else {
                    $slider['main_image'] = '';

                    if ($slider['image'] && file_exists($this->img_path . $slider['image'])) {
                        $slider['main_image'] = $this->img_url . $slider['image'];
                    }

                    if ($slider['thumbnail'] && file_exists($this->img_path. $slider['thumbnail'])) {
                        $slider['thumbnail'] = $this->img_url . $slider['thumbnail'];
                    }else if ($slider['image'] && file_exists($this->img_path. $slider['image'])) {
                        $slider['thumbnail'] = $slider['main_image'];
                    }else {
                        $slider['thumbnail'] = '';
                    }
                }
                
                if($slider['layersparams'])
                        foreach($slider['layersparams'] as &$layerCss){
                                $layerCssVal = '';
                                if(isset($layerCss['layer_font_size']) && $layerCss['layer_font_size']) $layerCssVal = 'font-size:'.$layerCss['layer_font_size'];
                                if(isset($layerCss['layer_background_color']) && $layerCss['layer_background_color']) $layerCssVal .= ($layerCssVal!=''?';':'').'background-color:'.$layerCss['layer_background_color'];
                                if(isset($layerCss['layer_color']) && $layerCss['layer_color']) $layerCssVal.= ($layerCssVal!=''?';':'').'color:'.$layerCss['layer_color'];
                                $layerCss['css'] = $layerCssVal;
                                if(!isset($layerCss['layer_link'])) 
                                        $layerCss['layer_link'] = str_replace("_ASM_","&",$slider['link']);
                                if(isset($layerCss['layer_caption']) && $layerCss['layer_caption'])
                                    $layerCss['layer_caption'] = utf8_decode ($layerCss['layer_caption']);
                                
                        }

                $sliders[$key] = $slider;
            }

            $this->smarty->assign(array(
                'sliderParams' => $sliderParams,
                'sliders' => $sliders,
                'sliderIDRand' => rand(20, rand()),
                'sliderFullwidth' => $sliderFullwidth,
                'sliderImgUrl' => $this->img_url
            ));
        }

        return true;
    }

    /*
     * 
     */

    public function renderThumb($src_file, $width, $height) {
        $subFolder = '/';
        if (!$src_file)
            return '';
        if (strpos($src_file, "/") !== false) {
            $path = @pathinfo($src_file);
            if (strpos($path['dirname'], "/") !== -1) {
                $subFolder = $path['dirname'] . '/';
                $folderList = explode("/", $path['dirname']);
                $tmpPFolder = '/';
                foreach ($folderList as $value) {
                    if ($value) {
                        if (!is_dir(_PS_ROOT_DIR_ . '/cache/' . $this->name . $tmpPFolder . $value)) {
                            mkdir(_PS_ROOT_DIR_ . '/cache/' . $this->name . $tmpPFolder . $value, 0755);
                        }
                        $tmpPFolder .= $value . '/';
                    }
                }
            }
            $imageName = $path['basename'];
        }
        else
            $imageName = $src_file;

        $path = '';
        if (file_exists($this->img_path . $src_file)) {
            //return image url
            $path = __PS_BASE_URI__ . 'cache/' . $this->name . $subFolder . $width . "_" . $height . "_" . $imageName;
            $savePath = _PS_ROOT_DIR_ . '/cache/' . $this->name . $subFolder . $width . "_" . $height . "_" . $imageName;
            if (!file_exists($savePath)) {
                $thumb = PhpThumbFactory::create($this->img_path . $src_file);
                $thumb->adaptiveResize($width, $height);
                $thumb->save($savePath);
            }
        }

        return $path;
    }
	
	public function hookHeader(){

        $this->context->controller->addCSS(($this->_path) . 'css/typo.css', 'all');
        $this->context->controller->addCSS(($this->_path) . 'css/style.css', 'all');

        $this->context->controller->addJqueryPlugin('easing');
		$this->context->controller->addJS($this->_path . 'js/raphael-min.js');
	    $this->context->controller->addJS($this->_path . 'js/iview.js');
		
	}
	
    public function processHook($hookName, $group = null) {
        if (!$this->_prepareHook($hookName, $group)) return false;
        
        return $this->display(__FILE__, '' . $this->name . '.tpl', $this->getCacheId($hookName.'_'.$this->name.'.tpl'));
    }

    public function hookDisplayTop() {
        return $this->processHook("displayTop");
    }

    function hookDisplaySlideshow() {
        return $this->processHook("displaySlideshow");
    }

    function hookTopNavigation() {
        return $this->processHook("topNavigation");
    }

    function hookDisplayPromoteTop() {
        return $this->processHook("displayPromoteTop");
    }

    function hookDisplayBottom() {
        return $this->processHook("displayBottom");
    }

    function hookDisplayContentBottom() {
        return $this->processHook("displayContentBottom");
    }

    public function hookdisplayTopColumn()
    {
        return $this->processHook("displayTopColumn");
    }
    
    public function hookDisplayHome()
    {
        return $this->processHook("displayHome");
    }

    // public function getCacheId($name = null) {
    //     if ($name === null && isset($this->context->smarty->tpl_vars['page_name']))
    //         return parent::getCacheId($this->context->smarty->tpl_vars['page_name']->value);
    //     return parent::getCacheId($name);
    // }

    public function clearCache() {
        $this->_clearCache('' . $this->name . '.tpl');
    }

    public function hookActionShopDataDuplication($params) {
        $groupList = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT gr.*
                            FROM `' . _DB_PREFIX_ . 'ptssliderlayer_groups` gr
                            WHERE gr.`id_shop` = ' . (int)$params['old_id_shop']);
        foreach($groupList as $list){
            $group = new PtsSliderGroup();
            foreach($list as $key=>$value){
                if($key != "id" && $key != "id_shop")
                $group->{$key} = $value;
            }
            $group->id_shop = (int)$params['new_id_shop'];
            $group->add();
            
            //import slider
            $sliderList = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('SELECT sl.id_ptssliderlayer_slides as id
                            FROM `' . _DB_PREFIX_ . 'ptssliderlayer_slides` sl
                            WHERE sl.`id_group` = ' . (int)$list["id_ptssliderlayer_groups"]);
            
            $fields = array("active","image","thumbnail","video","title","layersparams","title","position","link","params");
            foreach($sliderList as $key=>$value){
                $sliderOld = new SliderLayer($value["id"]);
                //print_r($sliderOld);die;
                $sliderNew = new SliderLayer();
                $sliderNew->id_group =  $group->id;
                foreach ($fields as $field){
                    $sliderNew->{$field} =  $sliderOld->{$field};
                }
                $sliderNew->add();
            }
        }

        $this->clearCache();
    }

    public function headerHTML() {
        if (Tools::getValue('controller') != 'AdminModules' && Tools::getValue('configure') != $this->name)
            return;
        $this->context->controller->addCSS($this->_path . 'assets/admin/style.css');
        if (file_exists(_PS_THEME_DIR_ . 'css/modules/ptssliderlayer/css/typo.css')) {
            $this->context->controller->addCSS(_THEME_CSS_DIR_ . 'modules/ptssliderlayer/css/typo.css');
        } else {
            $this->context->controller->addCSS($this->_path . 'css/typo.css');
        }
        $this->context->controller->addJS(_PS_JS_DIR_.'jquery/plugins/jquery.colorpicker.js');
        $this->context->controller->addJS($this->_path . 'assets/admin/script.js');
        $this->context->controller->addJqueryUI('ui.core');
        $this->context->controller->addJqueryUI('ui.widget');
        $this->context->controller->addJqueryUI('ui.mouse');
        $this->context->controller->addJqueryUI('ui.draggable');
        $this->context->controller->addJqueryUI('ui.resizable');
        $this->context->controller->addJqueryUI('ui.sortable');
        
        //$this->context->controller->addJS($this->_path . 'assets/admin/jquery-ui-1.10.3.custom.min.js');
        
        $this->context->controller->addCSS(_PS_JS_DIR_ . 'jquery/ui/themes/ui-lightness/jquery-ui-1.8.16.custom.css');

        $this->context->controller->addJqueryUI('ui.dialog');
        $this->context->controller->addJqueryPlugin('cooki-plugin');
        /* Style & js for fieldset 'slides configuration' */
        $html = '
        <style>
        #slides li {
            list-style: none;
            margin: 0 0 4px 0;
            padding: 10px;
            background-color: #F4E6C9;
            border: #CCCCCC solid 1px;
            color:#000;
        }
        </style>
        
        <script type="text/javascript">
                        $(function() {
                var $mySlides = $("#slides");
                $mySlides.sortable({
                    opacity: 0.6,
                    cursor: "move",
                    update: function() {
                        var order = $(this).sortable("serialize") + "&action=updateSlidesPosition";
                        $.post("' . AdminController::$currentIndex .'&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&leoajax=1'.'" , order);
                        }
                    });
                $mySlides.hover(function() {
                    $(this).css("cursor","move");
                    },
                    function() {
                    $(this).css("cursor","auto");
                });
            });
        </script>';

        return $html;
    }

    public function getNextPosition() {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                SELECT MAX(hss.`position`) AS `next_position`
                FROM `' . _DB_PREFIX_ . '' . $this->name . '_slides` hss, `' . _DB_PREFIX_ . '' . $this->name . '` hs
                WHERE hss.`id_' . $this->name . '_slides` = hs.`id_' . $this->name . '_slides` AND hs.`id_shop` = ' . (int) $this->context->shop->id
        );

        return (++$row['next_position']);
    }

    public function displayGStatus($id_group, $active) {
        $title = ((int) $active == 0 ? $this->l('Disabled') : $this->l('Enabled'));
        $img = ((int) $active == 0 ? 'disabled.gif' : 'enabled.gif');
        $html = '<a href="' . AdminController::$currentIndex .'&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&changeGStatus&id_group=' . (int) $id_group . '" title="' . $title . '"><img src="' . _PS_ADMIN_IMG_ . '' . $img . '" alt="" /></a>';
        return $html;
    }

    public function displayStatus($id_slide, $active, $group_id) {
        $title = ((int) $active == 0 ? $this->l('Disabled') : $this->l('Enabled'));
        $img = ((int) $active == 0 ? 'disabled.gif' : 'enabled.gif');
        $html = '<a href="' . AdminController::$currentIndex .'&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules') . '&changeStatus&sslider=' . (int) $id_slide . '&showsliders=1&id_group=' . (int) $group_id . '" title="' . $title . '"><img src="' . _PS_ADMIN_IMG_ . '' . $img . '" alt="" /></a>';
        return $html;
    }


    public function slideExists($id_slide) {
        $req = 'SELECT `id_' . $this->name . '_slides`
                FROM `' . _DB_PREFIX_ . '' . $this->name . '_slides`
                WHERE `id_' . $this->name . '_slides` = ' . (int) $id_slide;
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($req);
        return ($row);
    }

    protected function getCacheId($name = null, $hook = '') {
        $cache_array = array(
            $name !== null ? $name : $this->name,
            $hook,
            date('Ymd'),
            (int) Tools::usingSecureMode(),
            (int) $this->context->shop->id,
            (int) Group::getCurrent()->id,
            (int) $this->context->language->id,
            (int) $this->context->currency->id,
            (int) $this->context->country->id,
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443)
        );
        return implode('|', $cache_array);
    }
}