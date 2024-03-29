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
 
global $cookie, $link;

$module = new PtsSliderLayer();
if(empty($link))
	$link = new Link();
$id_lang = Tools::getValue('id_lang');
$ssl = Tools::usingSecureMode();
if(Tools::getValue('secure_key') != $module->secure_key)
	die('Secure key is invalid.');
$field = Tools::getValue('field');
?>
<html>
	<head>
		<title><?php echo $module->l('Pop up');?></title>
		<link href="<?php echo $pathModule;?>css/typo.css"  type="text/css" rel="stylesheet"/>
		<style>
                        .typo{ position: relative; width: 90%; min-height: 50px; padding:12px 0;}
                        .typo .tp-caption {
                            display: block;
                            left: 0;
                            margin: 0;
                            padding: 0;
                            top: 0;
                        }
                        .note{ font-size: 12px;}
                        .note a { color: #003A88 }
                </style>
		<script src="<?php echo $jqueryLink;?>" type="text/javascript"></script>	
		<script src="<?php echo $pathModule;?>assets/admin/jquery-ui-1.10.3.custom.min.js" type="text/javascript"></script>	
	</head>
	<body>
	<div class="typos">
	<div class="note"> 
		NOTE: <p>These Below Typos are getting in the file:<a href="<?php echo $typoFile ?>" target="_blank"><?php echo $typoFile; ?></a>
		<br>you can open this file and add yours css style and it will be listed in here!!!</p>
		<p>To Select One, You Click The Text Of Each Typo</p>
	</div>
	 
	<div class="typos-wrap">	
  		
		<?php foreach( $captions as $caption ) {  ?>
  		<div class="typo"><div class="tp-caption <?php echo $caption;?>" data-class="<?php echo $caption;?>">Use This Caption Typo</div></div>
  		<?php } ?>	
	 </div>
        </div>  
        <script type="text/javascript">
        $('div.typo').live('click', function() {  
                parent.$('#<?php echo $field; ?>').attr('value',  $("div", this).attr("data-class") );
                parent.$('#dialog').dialog('close');

                parent.$('#dialog').remove();	
        });
        </script>
	</body>
</html>