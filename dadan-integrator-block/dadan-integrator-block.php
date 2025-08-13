<?php
/**
 * Plugin Name:       Dadan Integrator Block
 * Description:       A custom block to embed Dadan videos with configurable UI options.
 * Tested up to:      6.8.2
 * Requires at least: 6.7
 * Requires PHP:      8.0
 * Version:           0.9.4
 * Author:            Stingray82
 * Author URI:        https://reallyusefulplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       dadan-integrator-block
 * Website:           https://reallyusefulplugins.com
 */

function dadan_integrator_block_init() {
    ?>
    <script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
  (function(wp) {
    if (!wp || !wp.element || !wp.blocks) return;

    // ---------- helpers ----------
    function getSizeStyles(preset, width, height) {
      switch (preset) {
        case 'small':  return { width: '40%', paddingBottom: '30%' };
        case 'medium': return { width: '60%', paddingBottom: '33.75%' };
        case 'large':  return { width: '80%', paddingBottom: '38.1%' };
        case 'manual': return { width: width + '%', paddingBottom: height + '%' };
        default:       return { width: '60%', paddingBottom: '33.75%' };
      }
    }

    function extractDadanId(input) {
      var val = (input || '').trim();
      if (!val) return '';
      try {
        if (/^https?:\/\//i.test(val)) {
          var u = new URL(val);
          // works for /video/share/{id} and /video/embed/{id}
          var parts = u.pathname.split('/').filter(Boolean);
          return parts[parts.length - 1] || '';
        }
      } catch(e) {}
      return val; // treat as raw id
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

    function setAllUI(state, v) {
      return {
        ...state,
        hide_emojies: v,
        hide_interactions: v,
        hide_comments: v,
        hide_owner: v,
        hide_analytics: v,
        hide_watch_on_dadan: v,
        hide_chapters: v
      };
    }

    // ---------- WP deps ----------
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
      icon, category: 'embed',
      attributes: {
        // core sizing/styling
        embedID:     { type:'string', default:'' },
        sizePreset:  { type:'string', default:'medium' },
        width:       { type:'number', default:100 },
        height:      { type:'number', default:56.25 },
        margin:      { type:'number', default:0 },
        border:      { type:'string', default:'0' },

        // editor-only behavior
        previewInteractive: { type:'boolean', default:false },

        // UI preset + flags
        uiPreset:             { type:'string',  default:'all_on' }, // all_on | all_off | custom
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

        function onChangeEmbed(val){
          props.setAttributes({ embedID: extractDadanId(val) });
        }

        function onChangePreset(preset){
          if (preset === 'all_on') {
            props.setAttributes({ uiPreset:'all_on', ...setAllUI({}, false) });
          } else if (preset === 'all_off') {
            props.setAttributes({ uiPreset:'all_off', ...setAllUI({}, true) });
          } else {
            props.setAttributes({ uiPreset:'custom' });
          }
        }

        function onToggleFlag(key, v){
          var update = {};
          update[key] = v;
          update.uiPreset = 'custom'; // any manual change moves to custom
          props.setAttributes(update);
        }

        var size = getSizeStyles(a.sizePreset, a.width, a.height);
        var iframeURL = a.embedID ? buildEmbedUrl(a.embedID, a) : '';

        var blockProps = useBlockProps({
          className:'dadan-integrator-block',
          style:{
            margin: a.margin + 'px auto',
            border: a.border + 'px solid black',
            position:'relative',
            width: size.width,
            height: 0,
            paddingBottom: size.paddingBottom,
            overflow:'hidden'
          }
        });

        return el('div', blockProps,

          // Block toolbar: Preview toggle (editor only)
          el(BlockControls, {},
            el(ToolbarGroup, {},
              el(ToolbarButton, {
                icon: a.previewInteractive ? 'visibility' : 'hidden',
                label: a.previewInteractive ? 'Disable Preview' : 'Enable Preview',
                isPressed: !!a.previewInteractive,
                onClick: function(){ props.setAttributes({ previewInteractive: !a.previewInteractive }); }
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
                label:'Size Preset', value:a.sizePreset,
                options:[
                  {label:'Small', value:'small'},
                  {label:'Medium', value:'medium'},
                  {label:'Large', value:'large'},
                  {label:'Manual', value:'manual'}
                ],
                onChange:function(v){ props.setAttributes({ sizePreset:v }); }
              }),
              a.sizePreset === 'manual' && el(RangeControl, {
                label:'Width (%)', value:a.width,
                onChange:function(v){ props.setAttributes({ width:v }); },
                min:10, max:100
              }),
              a.sizePreset === 'manual' && el(RangeControl, {
                label:'Height (%)', value:a.height,
                onChange:function(v){ props.setAttributes({ height:v }); },
                min:10, max:100
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

              // --- UI controls ---
              el(SelectControl, {
                label:'UI Preset',
                value:a.uiPreset,
                options:[
                  { label:'All on (show everything)', value:'all_on' },
                  { label:'All off (hide everything)', value:'all_off' },
                  { label:'Custom', value:'custom' }
                ],
                onChange:onChangePreset,
                help:'"All on" = all hide_* = false; "All off" = all hide_* = true.'
              }),

              (a.uiPreset === 'custom') && el('div', {},
                el(ToggleControl, {
                  label:'Hide emojis', checked:a.hide_emojies,
                  onChange:function(v){ onToggleFlag('hide_emojies', v); }
                }),
                el(ToggleControl, {
                  label:'Hide interactions', checked:a.hide_interactions,
                  onChange:function(v){ onToggleFlag('hide_interactions', v); }
                }),
                el(ToggleControl, {
                  label:'Hide comments', checked:a.hide_comments,
                  onChange:function(v){ onToggleFlag('hide_comments', v); }
                }),
                el(ToggleControl, {
                  label:'Hide owner', checked:a.hide_owner,
                  onChange:function(v){ onToggleFlag('hide_owner', v); }
                }),
                el(ToggleControl, {
                  label:'Hide analytics', checked:a.hide_analytics,
                  onChange:function(v){ onToggleFlag('hide_analytics', v); }
                }),
                el(ToggleControl, {
                  label:'Hide “Watch on Dadan”', checked:a.hide_watch_on_dadan,
                  onChange:function(v){ onToggleFlag('hide_watch_on_dadan', v); }
                }),
                el(ToggleControl, {
                  label:'Hide chapters', checked:a.hide_chapters,
                  onChange:function(v){ onToggleFlag('hide_chapters', v); }
                })
              )
            )
          ),

          a.embedID
            ? el('div', { style:{ position:'absolute', inset:0 } },
                // Hint overlay when preview is OFF
                !a.previewInteractive && el('div', {
                  style:{
                    position:'absolute', right:'8px', top:'8px',
                    padding:'4px 8px', background:'rgba(0,0,0,0.6)',
                    color:'#fff', fontSize:'12px', borderRadius:'6px', zIndex:2
                  }
                }, 'Click block to select · Enable Preview to interact'),
                el('iframe', {
                  src: iframeURL,
                  style: {
                    border:0, position:'absolute', inset:0, width:'100%', height:'100%',
                    // Disable clicks unless in Preview mode (editor only)
                    pointerEvents: a.previewInteractive ? 'auto' : 'none'
                  },
                  webkitallowfullscreen: true,
                  mozallowfullscreen: true,
                  allowFullScreen: true,
                  allow: 'autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share'
                })
              )
            : el('p', {}, 'Paste a Dadan share/embed URL or ID in the settings panel.')
        );
      },

      save: function(props) {
        var a = props.attributes;
        var size = getSizeStyles(a.sizePreset, a.width, a.height);
        if (!a.embedID) return null;
        var iframeURL = buildEmbedUrl(a.embedID, a);
        var style = {
          margin: a.margin + 'px auto',
          border: a.border + 'px solid black',
          position: 'relative',
          width: size.width,
          height: 0,
          paddingBottom: size.paddingBottom,
          overflow: 'hidden'
        };
        return el('div', { className:'dadan-integrator-block', style },
          el('iframe', {
            src: iframeURL,
            style: { border:0, position:'absolute', inset:0, width:'100%', height:'100%' },
            webkitallowfullscreen: true,
            mozallowfullscreen: true,
            allowFullScreen: true,
            allow: 'autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share'
          })
        );
      }
    });
  })(window.wp);
});
</script>
    <?php
}


add_action('admin_footer', 'dadan_integrator_block_init');

function dadan_integrator_enqueue_block_styles() {
    wp_enqueue_style(
        'dadan-integrator-block-styles',
        plugins_url('css/dadan.css', __FILE__),
        array(),
        '0.9.0'
    );
}
add_action('wp_enqueue_scripts', 'dadan_integrator_enqueue_block_styles');


// Define plugin constants (keep version in sync with header)
define('RUP_DADAN_INTEGRATOR_VERSION', '0.9.4');

// ──────────────────────────────────────────────────────────────────────────
//  Updater bootstrap (plugins_loaded priority 1):
// ──────────────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', function() {
    require_once __DIR__ . '/inc/updater.php';

    $updater_config = [
        'plugin_file' => plugin_basename( __FILE__ ),
        'slug'        => 'dadan-integrator-block',                // match repo/folder slug
        'name'        => 'Dadan Integrator Block',
        'version'     => RUP_DADAN_INTEGRATOR_VERSION,
        'key'         => '',
        // TODO: point this to your new repo once created:
        'server'      => 'https://raw.githubusercontent.com/stingray82/dadan-integrator-block/main/uupd/index.json',
    ];

    \RUP\Updater\Updater_V1::register( $updater_config );
}, 20 );

// MainWP Icon Filter
add_filter('mainwp_child_stats_get_plugin_info', function($info, $slug) {
    if ('dadan-integrator-block/dadan-integrator-block.php' === $slug) {
        // TODO: update to the new repo path once you push the icon.
        $info['icon'] = 'https://raw.githubusercontent.com/stingray82/dadan-integrator-block/main/uupd/icon-128.png';
    }
    return $info;
}, 10, 2);

