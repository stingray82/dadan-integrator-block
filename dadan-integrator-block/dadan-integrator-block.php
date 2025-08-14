<?php
/**
 * Plugin Name:       Dadan Integrator Block
 * Description:       A custom block to embed Dadan videos with configurable UI options.
 * Tested up to:      6.8.2
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Version:           0.9.5
 * Author:            Stingray82
 * Author URI:        https://reallyusefulplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dadan-integrator-block
 * Website:           https://reallyusefulplugins.com
 */

define('RUP_DADAN_INTEGRATOR_VERSION', '0.9.5');

/**
 * Inline block JS (no <script> tags). Runs only in the editor with proper deps/timing.
 */
function dadan_integrator_block_js() {
    return <<<'JS'
(function(wp){
  if (!wp || !wp.element || !wp.blocks || !wp.blockEditor) return;

  wp.domReady(function(){

    // --- helpers ---
    function extractDadanId(input) {
      var val = (input || '').trim();
      if (!val) return '';
      try {
        if (/^https?:\/\//i.test(val)) {
          var u = new URL(val);
          var parts = u.pathname.split('/').filter(Boolean);
          return parts[parts.length - 1] || '';
        }
      } catch(e) {}
      return val;
    }

    function buildEmbedUrl(id, opts) {
      var base = 'https://app.dadan.io/video/embed/' + encodeURIComponent(id);
      var params = new URLSearchParams({
        hide_emojies: String(!!opts.hide_emojies),
        hide_interactions: String(!!opts.hide_interactions),
        hide_comments: String(!!opts.hide_comments),
        hide_owner: String(!!opts.hide_owner),
        hide_analytics: String(!!opts.hide_analytics),
        hide_watch_on_dadan: String(!!opts.hide_watch_on_dadan),
        hide_chapters: String(!!opts.hide_chapters)
      });
      return base + '?' + params.toString();
    }

    function setAllUI(base, v) {
      base = base || {};
      base.hide_emojies        = v;
      base.hide_interactions   = v;
      base.hide_comments       = v;
      base.hide_owner          = v;
      base.hide_analytics      = v;
      base.hide_watch_on_dadan = v;
      base.hide_chapters       = v;
      return base;
    }

    // --- WP deps ---
    var el = wp.element.createElement;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var BlockControls     = wp.blockEditor.BlockControls;
    var useBlockProps     = wp.blockEditor.useBlockProps;

    var TextControl   = wp.components.TextControl;
    var PanelBody     = wp.components.PanelBody;
    var ToggleControl = wp.components.ToggleControl;
    var SelectControl = wp.components.SelectControl;
    var RangeControl  = wp.components.RangeControl;
    var ToolbarGroup  = wp.components.ToolbarGroup;
    var ToolbarButton = wp.components.ToolbarButton;

    var icon = el('svg', { xmlns:"http://www.w3.org/2000/svg", viewBox:"0 0 24 24" },
      el('path', { d:"M8 5v14l11-7-11-7z" })
    );

    wp.blocks.registerBlockType('custom/dadan-integrator', {
      title: 'Dadan Integrator',
      icon: icon,
      category: 'embed',
      supports: {
        align: [ 'wide', 'full' ],
        spacing: { blockGap: false, margin: false, padding: false }
      },
      attributes: {
        embedID:     { type:'string', default:'' },

        // size presets only (manual removed)
        sizePreset:  { type:'string', default:'container' }, // container | small | medium | large

        margin:      { type:'number', default:0 },
        border:      { type:'string', default:'0' },
        previewInteractive: { type:'boolean', default:false },

        uiPreset:             { type:'string',  default:'all_on' },
        hide_emojies:         { type:'boolean', default:false },
        hide_interactions:    { type:'boolean', default:false },
        hide_comments:        { type:'boolean', default:false },
        hide_owner:           { type:'boolean', default:false },
        hide_analytics:       { type:'boolean', default:false },
        hide_watch_on_dadan:  { type:'boolean', default:false },
        hide_chapters:        { type:'boolean', default:false }
      },

      edit: function(props) {
        var a = props.attributes;

        function onChangeEmbed(val) {
          props.setAttributes({ embedID: extractDadanId(val) });
        }

        function onChangePreset(preset) {
          if (preset === 'all_on') {
            props.setAttributes( setAllUI({ uiPreset:'all_on' }, false) );
          } else if (preset === 'all_off') {
            props.setAttributes( setAllUI({ uiPreset:'all_off' }, true) );
          } else {
            props.setAttributes({ uiPreset:'custom' });
          }
        }

        function onToggleFlag(key, v) {
          var update = { uiPreset: 'custom' };
          update[key] = v;
          props.setAttributes(update);
        }

        var iframeURL = a.embedID ? buildEmbedUrl(a.embedID, a) : '';

        // Outer wrapper controls width like wp-block-embed
        var wrapperProps = useBlockProps({ className: 'dadan-integrator-wrapper' });

        // Inner aspect box (no manual vars anymore)
        var innerStyle = {
          margin: a.margin + 'px auto',
          border: a.border + 'px solid black'
        };

        return el('div', wrapperProps,
          el(BlockControls, {},
            el(ToolbarGroup, {},
              el(ToolbarButton, {
                icon: a.previewInteractive ? 'visibility' : 'hidden',
                label: a.previewInteractive ? 'Disable Preview' : 'Enable Preview',
                isPressed: !!a.previewInteractive,
                onClick: function() {
                  props.setAttributes({ previewInteractive: !a.previewInteractive });
                }
              })
            )
          ),

          el(InspectorControls, {},
            el(PanelBody, { title:'Dadan Integrator Settings', initialOpen:true },
              el(TextControl, {
                label:'Dadan Share/Embed URL or ID',
                value:a.embedID,
                onChange:onChangeEmbed,
                placeholder:'Paste https://app.dadan.io/video/share/... or the ID'
              }),

              el(SelectControl, {
                label:'Size Preset',
                value:a.sizePreset,
                options:[
                  {label:'Container (fill parent)', value:'container'},
                  {label:'Small (55% / 4:3)', value:'small'},
                  {label:'Medium (65% / 16:9)', value:'medium'},
                  {label:'Large (80% / 21:9)', value:'large'}
                ],
                onChange:function(v){ props.setAttributes({ sizePreset:v }); }
              }),

              el(RangeControl, {
                label:'Margin (px)', value:a.margin,
                onChange:function(v){ props.setAttributes({ margin:v }); },
                min:0, max:100
              }),
              el(TextControl, {
                label:'Border (px)', value:a.border,
                onChange:function(v){ props.setAttributes({ border:v }); },
                placeholder:'e.g. 2'
              }),

              el(SelectControl, {
                label:'UI Preset',
                value:a.uiPreset,
                options:[
                  { label:'All on (show everything)', value:'all_on' },
                  { label:'All off (hide everything)', value:'all_off' },
                  { label:'Custom', value:'custom' }
                ],
                onChange:onChangePreset
              }),
              (a.uiPreset === 'custom') && el('div', {},
                el(ToggleControl, { label:'Hide emojis', checked:a.hide_emojies, onChange:function(v){ onToggleFlag('hide_emojies', v); } }),
                el(ToggleControl, { label:'Hide interactions', checked:a.hide_interactions, onChange:function(v){ onToggleFlag('hide_interactions', v); } }),
                el(ToggleControl, { label:'Hide comments', checked:a.hide_comments, onChange:function(v){ onToggleFlag('hide_comments', v); } }),
                el(ToggleControl, { label:'Hide owner', checked:a.hide_owner, onChange:function(v){ onToggleFlag('hide_owner', v); } }),
                el(ToggleControl, { label:'Hide analytics', checked:a.hide_analytics, onChange:function(v){ onToggleFlag('hide_analytics', v); } }),
                el(ToggleControl, { label:'Hide “Watch on Dadan”', checked:a.hide_watch_on_dadan, onChange:function(v){ onToggleFlag('hide_watch_on_dadan', v); } }),
                el(ToggleControl, { label:'Hide chapters', checked:a.hide_chapters, onChange:function(v){ onToggleFlag('hide_chapters', v); } })
              )
            )
          ),

          // Inner aspect box + iframe
          el('div', {
            className: 'dadan-integrator-block size-' + a.sizePreset,
            style: innerStyle
          },
            a.embedID
              ? el('iframe', {
                  src: iframeURL,
                  style: {
                    display: 'block',
                    width: '100%',
                    height: '100%',
                    pointerEvents: a.previewInteractive ? 'auto' : 'none'
                  },
                  scrolling: 'no',
                  webkitallowfullscreen: true,
                  mozallowfullscreen: true,
                  allowFullScreen: true,
                  allow: 'autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share'
                })
              : el('p', {}, 'Paste a Dadan share/embed URL or ID in the settings panel.')
          )
        );
      },

      save: function(props) {
        var a = props.attributes;
        if (!a.embedID) return null;
        var iframeURL = buildEmbedUrl(a.embedID, a);

        var innerStyle = {
          margin: a.margin + 'px auto',
          border: a.border + 'px solid black'
        };

        var sizeClass = a.sizePreset ? 'size-' + a.sizePreset : 'size-container';

        return el('div', { className: 'dadan-integrator-wrapper' },
          el('div', {
            className: 'dadan-integrator-block ' + sizeClass,
            style: innerStyle
          },
            el('iframe', {
              src: iframeURL,
              webkitallowfullscreen: true,
              mozallowfullscreen: true,
              allowFullScreen: true,
              allow: 'autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share'
            })
          )
        );
      }
    });
  });
})(window.wp);
JS;
}

/** Load editor script with correct deps & timing. */
add_action('enqueue_block_editor_assets', function () {
    $js = dadan_integrator_block_js();
    wp_register_script(
        'dadan-integrator-inline',
        false,
        array('wp-blocks','wp-element','wp-block-editor','wp-components','wp-dom-ready'),
        RUP_DADAN_INTEGRATOR_VERSION,
        true
    );
    wp_enqueue_script('dadan-integrator-inline');
    wp_add_inline_script('dadan-integrator-inline', $js);
});

/** Styles — front-end and editor. */
function dadan_integrator_enqueue_block_styles() {
    wp_enqueue_style(
        'dadan-integrator-block-styles',
        plugins_url('css/dadan.css', __FILE__),
        array(),
        RUP_DADAN_INTEGRATOR_VERSION
    );
}
add_action('wp_enqueue_scripts', 'dadan_integrator_enqueue_block_styles');
add_action('enqueue_block_editor_assets', 'dadan_integrator_enqueue_block_styles');

/** Updater bootstrap */
add_action( 'plugins_loaded', function() {
    require_once __DIR__ . '/inc/updater.php';
    $updater_config = [
        'plugin_file' => plugin_basename( __FILE__ ),
        'slug'        => 'dadan-integrator-block',
        'name'        => 'Dadan Integrator Block',
        'version'     => RUP_DADAN_INTEGRATOR_VERSION,
        'key'         => '',
        'server'      => 'https://raw.githubusercontent.com/stingray82/dadan-integrator-block/main/uupd/index.json',
    ];
    \RUP\Updater\Updater_V1::register( $updater_config );
}, 20);

/** MainWP icon */
add_filter('mainwp_child_stats_get_plugin_info', function($info, $slug) {
    if ('dadan-integrator-block/dadan-integrator-block.php' === $slug) {
        $info['icon'] = 'https://raw.githubusercontent.com/stingray82/dadan-integrator-block/main/uupd/icon-128.png';
    }
    return $info;
}, 10, 2);
