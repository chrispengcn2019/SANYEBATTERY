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
{if $sliders}
<div id="ptssliderlayer-container{$sliderIDRand}" class="ptssliderlayer{if $sliderParams.group_class} {$sliderParams.group_class}{/if} {if $sliderParams.md_width} col-md-{$sliderParams.md_width}{/if}{if $sliderParams.sm_width} col-sm-{$sliderParams.sm_width}{/if}{if $sliderParams.sm_width} col-xs-{$sliderParams.xs_width}{/if}" style="padding: {$sliderParams.padding};margin: {$sliderParams.margin};{$sliderParams.background}">

    <div id="ptssliderlayer{$sliderIDRand}" style="position:relative; width:100%; height:{$sliderParams.height}px; opacity:0;">
    {foreach from=$sliders item=slider}
        <div data-iview-image="{$slider.main_image}" data-iview-transition="{$slider.params.transition}" {if $slider.thumbnail}data-iview-thumbnail="{$slider.thumbnail}"{/if} data-iview-easing="{$slider.params.easing}" data-iview-pausetime="{$slider.params.pausetime}">


            {if isset($slider.layersparams)}
                {foreach from=$slider.layersparams item=layer}
                    <div class="iview-caption tp-caption{if $layer.layer_class} {$layer.layer_class}{/if}" 
                        data-x="{$layer.layer_left}" 
                        data-y="{$layer.layer_top}"
                        data-width="{$layer.layer_width}"
                        data-height="{$layer.layer_height}"
                        data-transition="{if $layer.layer_animation} {$layer.layer_animation}{else}wipeRight{/if}" 
                        data-easing="{if $layer.layer_easing}{$layer.layer_easing}{else}easeOutExpo{/if}"
                        data-speed="700"
                        data-start="{$layer.time_start}"
                        {if $layer.layer_endtime}
                            data-end="{$layer.layer_endtime}" 
                            data-endspeed="{$layer.layer_endspeed}" 
                            {if $layer.layer_endeasing != "nothing"}
                            data-endeasing="{$layer.layer_endeasing}"
                            {/if}
                        {/if}

                        {if $layer.css} style="{$layer.css}"{/if}>
                        {if $layer.layer_type == "video"}
                            {if $layer.layer_video_type == "vimeo"}
                            <iframe src="http://player.vimeo.com/video/{$layer.layer_video_id}?wmode=transparent&amp;title=0&amp;byline=0&amp;portrait=0;api=1" width="{$layer.layer_video_width}" height="{$layer.layer_video_height}" frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>
                            {else}
                            <iframe width="{$layer.layer_video_width}" height="{$layer.layer_video_height}" src="http://www.youtube.com/embed/{$layer.layer_video_id}?wmode=transparent" frameborder="0" allowfullscreen></iframe>
                            {/if}
                        {elseif $layer.layer_type == "image"}
                            {if $layer.layer_link}
                            <a href="{$layer.layer_link}">
                            {/if}
                                <img src="{$sliderImgUrl}{$layer.layer_content}" alt=""/>
                            {if $layer.layer_link}
                            </a>
                            {/if}
                        {else}
                            {if $layer.layer_link}
                            <a href="{$layer.layer_link}">
                            {/if}
                                {$layer.layer_caption|replace:"_ASM_":"&"|html_entity_decode:$smarty.const.ENT_QUOTES:"UTF-8"}

                            {if $layer.layer_link}
                            </a>
                            {/if}
                        {/if}
                    </div>

                {/foreach}
            {/if}
        </div>
    {/foreach}
    </div>
</div>

{/if}


{if $sliders && $sliderParams.navigator_type == 'custom'}
<ul class="pagination_slider">
    {foreach from=$sliders item=slider name=slider_name}
        <li{if $smarty.foreach.slider_name.index == 0} class="active"{/if} data-index="{$smarty.foreach.slider_name.index}" class="pagination_slider">{$slider.title}</li>
    {/foreach}
</ul>
{/if}


<script type="text/javascript">
$(document).ready(function() {
    $('#ptssliderlayer{$sliderIDRand}').iView({
        pauseTime: {if $sliderParams.delay}true{else}7000{/if},
        directionNav: false,
        controlNav: {if $sliderParams.navigator_type && $sliderParams.navigator_type != "custom"}true{else}false{/if},
        controlNavNextPrev: false, // previous,next navigation
        controlNavHoverOpacity: 0.6, // Navigation fade on hover
        controlNavThumbs: {if $sliderParams.navigator_type == 'thumb'}true{else}false{/if}, // Show thumbs navigation
        controlNavTooltip: false,
        directionNav: {if $sliderParams.navigator_arrows}true{else}false{/if}, 
        autoAdvance: {if $sliderParams.auto_play}true{else}false{/if},
        tooltipY: -15,
        timer: "{$sliderParams.show_time_line}",
        pauseOnHover: {if $sliderParams.stop_on_hover}true{else}false{/if},
        heightItem: {$sliderParams.height},
        widthItem: {$sliderParams.width},
        height: {$sliderParams.height},
        width: {$sliderParams.width},
        nextLabel: "",
        previousLabel: "",
        playLabel: "Play",
        pauseLabel: "Pause",
        closeLabel: "Close"
    });

    {if $sliders && $sliderParams.navigator_type == 'custom'}
        $('.pagination_slider li').click(function(){
            $('.pagination_slider li').removeClass('active');
            $(this).addClass('active');

            var index = $(this).attr('data-index');
            $('#ptssliderlayer{$sliderIDRand}').trigger('iView-goSlide', [index]);
        });

        $('#ptssliderlayer{$sliderIDRand}').bind("iview-onBeforeChange",function (data, e) {
            $('.pagination_slider li').removeClass('active');
            $('.pagination_slider li[data-index="'+e+'"]').addClass('active');
        });
    {/if}
});
</script>