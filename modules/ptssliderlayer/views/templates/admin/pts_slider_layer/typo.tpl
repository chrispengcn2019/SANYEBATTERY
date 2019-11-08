{*
* Pts Prestashop Theme Framework for Prestashop 1.6.x
*
* @package   ptssliderlayer
* @version   1.0
* @author    http://www.prestabrain.com
* @copyright Copyright (C) October 2013 prestabrain.com <@emai:prestabrain@gmail.com>
*               <info@prestabrain.com>.All rights reserved.
* @license   GNU General Public License version 2
*}
<div class="typos">
    <div class="note"> 
            {l s='NOTE'}: <p>{l s='These Below Typos are getting in the file'}:<a href="{$typoDir}" target="_blank">{$typoDir}</a>
            <br>{l s='you can open this file and add yours css style and it will be listed in here!!!'}</p>
            <p>{l s='To Select One, You Click The Text Of Each Typo'}</p>
    </div>

    <div class="typos-wrap">	
        {foreach $captions as $caption}
            <div class="typo {if $caption=='cus_color'}typo-big{/if}"><div class="tp-caption {$caption}" data-class="{$caption}">{$caption}</div></div>
        {/foreach}
     </div>
</div>  
<script type="text/javascript">
$('div.typo').live('click', function() {  
        if(parent.$('#{$field}').val())
            parent.$('#{$field}').val(parent.$('#{$field}').val()+" "+$("div", this).attr("data-class") );
        else
            parent.$('#{$field}').val($("div", this).attr("data-class") );
        parent.$('#dialog').dialog('close');
        parent.$('#dialog').remove();	
});
</script>