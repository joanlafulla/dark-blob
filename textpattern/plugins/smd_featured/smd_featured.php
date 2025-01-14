<?php
/**
 * smd_featured
 *
 * A Textpattern CMS plugin for pimping your articles on landing pages.
 *
 * @author Stef Dawson
 * @link   http://stefdawson.com/
 */
if (txpinterface === 'admin') {
    global $smd_featured_event, $smd_featured_pref_privs;

    $smd_featured_event = 'smd_featured';
    $smd_featured_privs = get_pref('smd_featured_privs', '1, 2');
    $smd_featured_pref_privs = array(
        'all' => array(
            'smd_featured_display',
            'smd_featured_box_size',
            'smd_featured_sort',
        ),
        '1' => array(
            'smd_featured_section_list',
            'smd_featured_textile',
            'smd_featured_show_ui',
        ),
    );

    add_privs($smd_featured_event, $smd_featured_privs);
    register_tab("content", $smd_featured_event, gTxt('smd_feat_tab_name'));
    register_callback('smd_featured_manage', $smd_featured_event);
    register_callback('smd_featured_welcome', 'plugin_lifecycle.smd_featured');
    register_callback('smd_featured_inject_css', 'admin_side', 'head_end');
} elseif (txpinterface === 'public') {
    if (class_exists('\Textpattern\Tag\Registry')) {
        Txp::get('\Textpattern\Tag\Registry')
            ->register('smd_featured')
            ->register('smd_featured_info')
            ->register('smd_unfeatured')
            ->register('smd_if_featured');
    }
}

if (!defined('SMD_FEAT')) {
    define("SMD_FEAT", 'smd_featured');
}

// **********
// ADMIN SIDE
// **********
// -------------------------------------------------------------
// CSS definitions: hopefully kind to themers
function smd_featured_get_style_rules()
{
    $sizes = do_list(get_pref('smd_featured_box_size', '150x40', 1), 'x');
    $sizes[0] = (isset($sizes[0]) && is_numeric($sizes[0])) ? $sizes[0] : '150';
    $sizes[1] = (isset($sizes[1]) && is_numeric($sizes[1])) ? $sizes[1] : '40';
    $stylekeys = array('{bw}', '{bh}');
    $stylevals = array($sizes[0], $sizes[1]);

    $smd_featured_styles = array(
        'common' => '
#smd_all h3 { margin:15px 0; }
#smd_featured_cpanel select, #smd_featured_cpanel input[type="text"] { margin-bottom:10px; }
#smd_featured_cpanel input[type="text"] { padding:3px; }
#control input, #control select { margin:0; }
.smd_hidden { display:none; }
.smd_featured { background-color:#e2dfce; position:relative; }
.smd_featured a { font-weight: bold; color:#80551e; }
#smd_featured_cpanel form { border:1px solid #ccc; margin: 0.5rem 0; padding: 1rem; }
.smd_featured_subpanel h4 { margin: 0 0 0.5rem }
.smd_featured_table { margin:0 auto; text-align:center; }
.smd_clear { clear:both; }
.smd_featured_table div { display:inline-block; width:{bw}px; height:{bh}px; border:1px solid #aaa; padding:0.4em; overflow:hidden; }
.smd_feat_edart { position:absolute; right:5px; bottom:3px; }
.smd_feat_edpos { position:absolute; left:2px; bottom:2px; font-size:75%; width:7em;}
#smd_feat_saveform { margin:1em; }
#smd_feat_loading { color:#80551e; text-align:center; font-size:200%; font-style:italic; width:500px; height:100px; margin:0 auto; border:1px solid #777; background:#e2dfce;display:block }
',
    );

    return str_replace($stylekeys, $stylevals, $smd_featured_styles);
}

// -------------------------------------------------------------
function smd_featured_inject_css($evt, $stp)
{
    global $smd_featured_event, $event;

    if ($event === $smd_featured_event) {
        $smd_featured_styles = smd_featured_get_style_rules();
        echo '<style type="text/css">', $smd_featured_styles['common'], '</style>';
    }

    return;
}
// ------------------------
function smd_featured_manage($evt, $stp)
{
    if ($stp == 'save_pane_state') {
        smd_featured_save_pane_state();
    } else {
        if(!$stp or !in_array($stp, array(
                'smd_featured_table_install',
                'smd_featured_table_remove',
                'smd_featured_prefsave',
                'smd_featured_save',
                'smd_featured_store_pos',
                'smd_featured_tagit',
                'smd_featured_change_pageby',
            ))) {
            smd_featured_list('');
        } else $stp();
    }
}

// ------------------------
function smd_featured_welcome($evt, $stp)
{
    $msg = '';
    switch ($stp) {
        case 'installed':
            smd_featured_table_install(0);
            // Remove some of the per-user prefs on upgrade from v0.3x to v0.40
            safe_delete ('txp_prefs', "name IN ('smd_featured_textile') AND user_name != ''");
            $msg = 'Thanks for installing smd_featured. Please read the docs :-)';
            break;
        case 'deleted':
            smd_featured_table_remove(0);
            break;
    }
    return $msg;
}

// ------------------------
function smd_featured_list($msg = '')
{
    global $smd_featured_event, $smd_featured_list_pageby, $txp_user, $smd_featured_pref_privs;

    pagetop(gTxt('smd_feat_tab_name'), $msg);
    extract(gpsa(array('smd_feat_id', 'smd_feat_label', 'smd_feat_searchkeep', 'smd_feat_filtkeep', 'page')));

    if (smd_featured_table_exist(1)) {
        smd_featured_table_upgrade();
        $featlist = safe_rows('*', SMD_FEAT, '1=1');
        $featlist = empty($featlist) ? array() : $featlist;
        $editname = $feat_label = $feat_title = $feat_desc = '';

        $etypes = $ftypes = $flist = array();
        $etypes[''] = gTxt('smd_feat_unlabelled');
        $ftypes['smd_unlabelled'] = gTxt('smd_feat_unlabelled');
        foreach ($featlist as $item) {
            if (isset($item['label']) && !empty($item['label'])) {
                $ftypes[$item['label']] = $etypes[$item['label']] = $item['label'];
            }
            $flist[$item['feat_id']] = array(
                'label' => $item['label'],
                'position' => $item['feat_position'],
                'title' => $item['feat_title'],
                'title_html' => $item['feat_title_html'],
                'description' => $item['description'],
                'desc_html' => $item['desc_html']
            );
        }

        $featlist = $flist;

        $privs = safe_field('privs', 'txp_users', "name = '".doSlash($txp_user)."'");
        $rights = array_key_exists($privs, $smd_featured_pref_privs);

        // Get additional filtering from hidden prefs
        $where = array('1=1');
        $seclist = get_pref('smd_featured_section_list', '', 1);
        if (!$seclist) {
            $seclist = gps('section_list');
        }
        if ($seclist) {
            $where[] = "Section IN ('".join("','", do_list($seclist))."')";
        }
        $where[] = "Status IN (4,5)";

        $orderby = get_pref('smd_featured_sort', 'Posted desc', 1);
        $orderqry = ($orderby) ? ' ORDER BY '.doSlash($orderby) : '';
        $currOrder = explode(' ', $orderby);
        $currOrder[0] = (isset($currOrder[0])) ? $currOrder[0] : 'Posted';
        $currOrder[1] = (isset($currOrder[1])) ? $currOrder[1] : 'desc';
        $display = get_pref('smd_featured_display', 'all', 1);

        // Generate the extra criteria if in list view
        if ($display === 'paginated') {
            if ($smd_feat_searchkeep) {
                $items = do_list($smd_feat_searchkeep, ' ');
                $itlist = array();
                foreach ($items as $item) {
                    if (trim($item) != '') {
                        $itlist[] = "Title like '%$item%'";
                    }
                }
                $where[] = '('.join(' OR ', $itlist).')';
            }
            if ($smd_feat_filtkeep) {
                $lbl = ($smd_feat_filtkeep == 'smd_unlabelled') ? '' : $smd_feat_filtkeep;
                $ids = safe_column('feat_id', SMD_FEAT, "BINARY label = '$lbl'");
                $where[] = "ID IN ('".join("','", $ids)."')";
            }
        }

        $where = join(' AND ', $where);
        $total = safe_count('textpattern', $where);
        $do_pag = !$smd_feat_filtkeep && !$smd_feat_searchkeep;

        $limit = ($display=='paginated' && $do_pag) ? max($smd_featured_list_pageby, 15) : 99999;
        list($page, $offset, $numPages) = pager($total, $limit, $page);

        $rs = safe_rows('*', 'textpattern', $where. $orderqry . " limit $offset, $limit");
        $out = array();

        foreach ($rs as $row) {
            $ftype = isset($featlist[$row['ID']]['label']) ? $featlist[$row['ID']]['label'] : '';
            $out[] = array($row['ID'], $row['Title'], $ftype);
            if ($smd_feat_id && $row['ID'] == $smd_feat_id) {
                $editname = $row['Title'];
                $feat_label = $featlist[$smd_feat_id]['label'];
                $feat_title = $featlist[$smd_feat_id]['title'];
                $feat_position = $featlist[$smd_feat_id]['position'];
                $feat_desc = doStrip(str_replace('\r\n','
', $featlist[$smd_feat_id]['description'])); // Hackish newline kludge
            }
            // Add the position to the most recent array entry
            if (isset($featlist[$row['ID']]['position'])) {
                $out[count($out)-1][] = $featlist[$row['ID']]['position'];
            }
        }

        //TODO: i18n
        $sortopts = array(
            'Posted' => 'Posted',
            'Expires' => 'Expiry',
            'LastMod' => 'Modified',
            'Title' => 'Title',
            'Section' => 'Section',
            'Category1' => 'Category1',
            'Category2' => 'Category2',
        );
        $sortdirs = array(
            'asc' => 'Ascending',
            'desc' => 'Descending',
        );
        $displayopts = array(
            'all' => 'All',
            'paginated' => 'Paginated',
        );
        $textileonoff = explode(',', get_pref('smd_featured_textile', 'title,desc', 1));
        $showonoff = explode(',', get_pref('smd_featured_show_ui', 'label,title,desc', 1));
        $txt_ttl = in_array('title', $textileonoff);
        $txt_desc = in_array('desc', $textileonoff);
        $show_lbl = in_array('label', $showonoff);
        $show_ttl = in_array('title', $showonoff);
        $show_desc = in_array('desc', $showonoff);
        $use_edit = ($show_lbl || $show_ttl || $show_desc) ? '1' : '0';

        $sizes = do_list(get_pref('smd_featured_box_size', '150x40', 1), 'x');
        $sizes[0] = (isset($sizes[0]) && is_numeric($sizes[0])) ? $sizes[0] : '150';
        $sizes[1] = (isset($sizes[1]) && is_numeric($sizes[1])) ? $sizes[1] : '40';
        $display_js = ($display=='all') ? 1 : 0;

        echo n.'<div id="smd_feat_loading"><div id="smd_feat_loading_holder">Loading...</div></div>';
        echo n.'<div id="smd_container" class="txp-container" style="display:none;">';
        echo n.'<div id="smd_featured_control_panel"><span class="txp-summary lever'.(get_pref('pane_smd_featured_cpanel_visible') ? ' expanded' : '').'"><a href="#smd_featured_cpanel">'.gTxt('smd_feat_control_panel').'</a></span>';
        echo n.'<div id="smd_featured_cpanel" class="toggle" style="display:'.(get_pref('pane_smd_featured_cpanel_visible') ? 'block' : 'none').'">';

        echo n.'<form id="smd_feat_prefs" action="index.php" method="post">';
        echo n.'<div id="smd_featured_prefs" class="smd_featured_subpanel"><h4>'.gTxt('smd_feat_prefs').'</h4>';

        echo n.'<div class="smd_featured_pref">';
        echo n.'<label for="smd_feat_display">'.gTxt('smd_feat_pref_display').'</label>'.
            n.selectInput('smd_feat_display', $displayopts, $display, '', '', 'smd_feat_display');
        echo n.'</div>';

        echo n.'<div class="smd_featured_pref">';
        echo n.'<label for="smd_feat_sort">'.gTxt('smd_feat_pref_sort').'</label>'.
            n.selectInput('smd_feat_sort', $sortopts, $currOrder[0], '', '', 'smd_feat_sort').
            n.selectInput('smd_feat_sortdir', $sortdirs, $currOrder[1], '', '', 'smd_feat_sortdir');
        echo n.'</div>';

        echo n.'<div class="smd_featured_pref">';
        echo n.'<div id="smd_feat_display_box">';
        echo n.'<label for="smd_feat_boxsize">'.gTxt('smd_feat_pref_boxsize').'</label>'.
            n.fInput('text', 'smd_feat_box_x', $sizes[0], '', '', '', '3', '', 'smd_feat_boxsize').n.
            gTxt('smd_feat_pref_boxsizeby').n.fInput('text', 'smd_feat_box_y', $sizes[1], '', '', '', '3');
        echo n.'</div>';

        if ($rights) {
            echo n.'<div class="smd_featured_pref smd_feat_show_ui">';
            echo n.'<label for="smd_feat_show_ui">'.gTxt('smd_feat_show_ui').'</label>';
            echo n.checkbox('smd_feat_show_ui[]', 'label', $show_lbl, '', 'smd_feat_show_label').sp.gTxt('smd_feat_label');
            echo n.checkbox('smd_feat_show_ui[]', 'title', $show_ttl, '', 'smd_feat_show_title').sp.gTxt('smd_feat_title');
            echo n.checkbox('smd_feat_show_ui[]', 'desc', $show_desc, '', 'smd_feat_show_desc').sp.gTxt('smd_feat_desc');
            echo n.'</div>';

            echo n.'<div class="smd_featured_pref smd_feat_textile">';
            echo n.'<label for="smd_feat_textile">'.gTxt('smd_feat_textile').'</label>';
            echo n.checkbox('smd_feat_textile[]', 'title', $txt_ttl, '', 'smd_feat_textile_title').sp.gTxt('smd_feat_title');
            echo n.checkbox('smd_feat_textile[]', 'desc', $txt_desc, '', 'smd_feat_textile_desc').sp.gTxt('smd_feat_desc');
            echo n.'</div>';

            echo n.'<div class="smd_featured_pref smd_feat_section_list">';
            echo n.'<label for="smd_feat_section_list">'.gTxt('smd_feat_section_list').'</label>';
            echo n.fInput('text', 'smd_feat_section_list', $seclist, '', '', '', '30', '', 'smd_feat_section_list');
            echo n.'</div>';
        }

        echo n.'</div>';

        echo n.eInput($smd_featured_event).sInput('smd_featured_prefsave');
        echo n.fInput('submit', '', gTxt('save'), 'smd_featured_save');
        echo n.'</div>';
        echo n.'</form>';

        echo n.'<form id="smd_feat_filtform" action="index.php" method="post" onsubmit="smd_featured_editkeep(0);return false;">';
        echo n.'<div id="smd_featured_filt" class="smd_featured_subpanel"><h4>'.gTxt((($display=='all') ? 'smd_feat_search_live' : 'smd_feat_search_standard')).'</h4>';
        echo n.'<label for="smd_feat_search">'.gTxt('smd_feat_by_name').'</label>'.n.fInput('text', 'smd_feat_search', $smd_feat_searchkeep, '', '', '', '', '', 'smd_feat_search').
            (($ftypes) ? n.'<div id="smd_featured_bylabel">'.gTxt('smd_feat_by_label').n.selectInput('smd_feat_filt', $ftypes, $smd_feat_filtkeep, 1, '', 'smd_feat_filt').'</div>' : '');
        echo ($display=='paginated') ? n.fInput('submit', '', gTxt('go'), 'smd_featured_save', '', 'return smd_featured_editkeep(0);') : '';
        echo n.eInput($smd_featured_event).n.sInput('smd_featured_list');
        echo n.'</div>';
        echo n.'</form>';

        echo n.'</div>';
        echo n.'</div>';

        echo n.'<form id="smd_feat_editform" action="index.php" method="post">';
        echo n.hInput('smd_feat_searchkeep', '').
                n.hInput('smd_feat_filtkeep', '').
                n.hInput('smd_feat_id', '').
                n.eInput($smd_featured_event).
                n.sInput('smd_featured_list');
        echo n.'</form>';

        echo n.'<form id="smd_feat_saveform" action="index.php" method="post" onsubmit="return smd_featured_savekeep();">';
        echo n.startTable();
        if ($smd_feat_id) {
            echo n.tr(
                n.td('&nbsp;').tdcs(gTxt('edit').sp.strong(eLink('article', 'edit', 'ID', $smd_feat_id, $editname)), 2).
                n.td(fInput('submit', '', gTxt('save'))).
                n.hInput('smd_feat_searchkeep', '').
                n.hInput('smd_feat_filtkeep', '').
                n.hInput('smd_feat_id', $smd_feat_id).
                n.eInput($smd_featured_event).
                n.sInput('smd_featured_save')
            );
            if ($show_lbl) {
                echo n.tr(
                    n.fLabelCell(gTxt('label'), '', 'smd_feat_label').
                    n.td(
                        n.fInput('text', 'smd_feat_label', $feat_label, '', '', '', '', '', 'smd_feat_label').
                        n.selectInput('smd_feat_labelchoose', $etypes, $feat_label, 0, '', 'smd_feat_labelchoose')
                    )
                );
            }
            if ($show_ttl) {
                echo n.tr(
                    n.fLabelCell(gTxt('title'), '', 'smd_feat_title').
                    n.td(
                        n.fInput('text', 'smd_feat_title', $feat_title, '', '', '', '', '', 'smd_feat_title')
                    )
                );
            }
            if ($show_desc) {
                echo n.tr(
                    n.fLabelCell(gTxt('description'), '', 'smd_feat_desc').
                    n.td(text_area('smd_feat_desc', 80, 400, $feat_desc, 'smd_feat_desc'))
                );
            }
            echo n.tr(
                n.fLabelCell(gTxt('smd_feat_position'), '', 'smd_feat_position').
                n.td(fInput('text', 'smd_feat_position', $feat_position, '', '', '', '', '', 'smd_feat_position'))
            );
        }
        echo n.endTable();
        echo n.'</form>';

        $edbtn = small('['.gTxt('edit').']');
        $edtip = gTxt('smd_feat_lbl_edfeat');
        $edartbtn = '&rarr;';
        $edarttip = gTxt('smd_feat_lbl_edart');
        $edpostip = gTxt('smd_feat_lbl_edpos');
        $unfeattip = gTxt('smd_feat_lbl_unfeature');

        if ($out) {
            $rows = count($rs);
            $tblout = array();
            $atts = ' class="smd_featured_table smd_clear" id="smd_'.$display.'"';

            $tblout[] = hed(gTxt('smd_feat_manage_lbl', array(), 'raw'), 3, ' class="smd_clear"');
            for ($idx = 0; $idx < $rows; $idx++) {
                $isfeat = (isset($out[$idx]) && array_key_exists($out[$idx][0], $featlist));
                $cellclass = $isfeat ? ' class="smd_featured"' : '';
                $edlink = $isfeat && $use_edit ? '<a class="smd_feat_edlink" href="#" onclick="return smd_featured_editkeep(\''.$out[$idx][0].'\');" title="'.$edtip.'">'.$edbtn.'</a>'.sp : '';
                $edpos = $isfeat ? '<input name="smd_featured_position" class="smd_feat_edpos" title="'.$edpostip.'" value="'.$out[$idx][3].'" onblur="return smd_featured_store_pos(this, \''.$out[$idx][0].'\')" />' : '';
                $edart = $isfeat ? '<a class="smd_feat_edart" href="?event=article&step=edit&ID='.$out[$idx][0].'" title="'.$edarttip.'">'.$edartbtn.'</a>' : '';
                $rowdata = (isset($out[$idx])) ? '<span name="smd_feat_name" class="smd_hidden">'.$out[$idx][1].'</span>' : '';
                $rowdata .= $isfeat ? '<span name="smd_feat_label" class="extra smd_hidden">'.$out[$idx][2].'</span>' : '';
                $tblout[] = (isset($out[$idx])) ? '<div'.$cellclass.'>'.$edlink.'<a href="#" title="'.(($isfeat) ? $unfeattip : '').'" onclick="return smd_featured_select(this, \''.$out[$idx][0].'\')">'.$out[$idx][1].'</a>'.$edpos.$edart.$rowdata.'</div>' : '<div></div>';
            }
            echo tag(join("",$tblout), 'div', $atts);
            echo '<div class="smd_clear"></div>';
            if ($display=='paginated' && $do_pag) {
                echo n.'<div id="'.$smd_featured_event.'_navigation" class="txp-navigation">'.
                    n.nav_form($smd_featured_event, $page, $numPages, '', '', '', '', $total, $limit).
                    n.pageby_form($smd_featured_event, $smd_featured_list_pageby);
                    n.'</div>';
            }
        }

        $qs = array(
            "event" => $smd_featured_event,
        );

        $qsVars = "index.php".join_qs($qs);
        $verifyTxt = gTxt('smd_feat_unfeature_confirm');

        echo <<<EOJS
<script type="text/javascript">
function smd_featured_select(obj, id) {
    obj = jQuery(obj).parent();

    // N.B. Negative logic used here because we're checking the class _before_ it's been toggled
    var action = ((obj).hasClass("smd_featured")) ? 'remove' : 'add';
    if (action == 'remove') {
        if ('1' == {$use_edit}) {
            var ret = confirm('{$verifyTxt}');
            if (ret == false) {
                return false;
            }
        }
    }
    jQuery.post('{$qsVars}', { step: "smd_featured_tagit", smd_feat_id: id, smd_feat_action: action },
        function(data) {
            obj.toggleClass('smd_featured');
            if (action == 'add') {
                obj.find('a').attr('title', "{$unfeattip}");
                if ('1' == '{$use_edit}') {
                    obj.prepend('<a class="smd_feat_edlink" title="{$edtip}" href="#" onclick="return smd_featured_editkeep(\''+id+'\')">{$edbtn}</a>&nbsp;');
                }
                obj.append('<input name="smd_featured_position" class="smd_feat_edpos" title="{$edpostip}" value="" onblur="return smd_featured_store_pos(this, \''+id+'\')" />');
                obj.append('<a class="smd_feat_edart" title="{$edarttip}" href="?event=article&step=edit&ID='+id+'">{$edartbtn}</a>');
                obj.append('<span name="smd_feat_label" class="extra smd_hidden"></span>');
                obj.find('input.smd_feat_edpos').focus();
            } else {
                obj.find('a.smd_feat_edlink').remove();
                obj.find('input.smd_feat_edpos').remove();
                obj.find('a.smd_feat_edart').remove();
                obj.find('a').attr('title', '');
                obj.find('span.extra').remove();
                if (jQuery("#smd_feat_search").val() != '') jQuery("#smd_feat_search").keyup();
                if (jQuery("#smd_feat_filt").val() != '') jQuery("#smd_feat_filt").change();
            }
        }
    );
    return false;
}

function smd_featured_editkeep(id) {
    jQuery("#smd_feat_editform [name='smd_feat_searchkeep']").val(jQuery("#smd_feat_search").val());
    jQuery("#smd_feat_editform [name='smd_feat_filtkeep']").val(jQuery("#smd_featured_bylabel #smd_feat_filt option:selected").val());
    jQuery("#smd_feat_editform [name='smd_feat_id']").val(id);
    jQuery("#smd_feat_editform").submit();
}

function smd_featured_savekeep() {
    jQuery("#smd_feat_saveform [name='smd_feat_searchkeep']").val(jQuery("#smd_feat_search").val());
    jQuery("#smd_feat_saveform [name='smd_feat_filtkeep']").val(jQuery("#smd_featured_bylabel #smd_feat_filt option:selected").val());
}

function smd_feat_filter(selector, query, nam, csense, exact) {
    if ({$display_js} == 1) {
        var query = jQuery.trim(query);
        csense = (csense) ? "" : "i";
        query = query.replace(/ /gi, '|'); // add OR for regex query
        if (exact) {
            tmp = query.split('|');
            for (var idx = 0; idx < tmp.length; idx++) {
                tmp[idx] = '^'+tmp[idx]+'$';
            }
            query = tmp.join('|');
        }
        var re = new RegExp(query, csense);
        jQuery(selector).each(function() {
            sel = (typeof nam=="undefined" || nam=='') ? jQuery(this) : jQuery(this).find("span[name='"+nam+"']");
            if (query == '') {
                if (sel.length == 1 && sel.text() == '') {
                    jQuery(this).show();
                } else {
                    jQuery(this).hide();
                }
            } else {
                if (sel.text().search(re) < 0) {
                    jQuery(this).hide();
                } else {
                    jQuery(this).show();
                }
            }
        });
    }
}

function smd_featured_store_pos(obj, id) {
    var obj = jQuery(obj);
    var pos = obj.val();

    //TODO: feedback on success
    sendAsyncEvent(
    {
        event: textpattern.event,
        step: 'smd_featured_store_pos',
        smd_feat_id: id,
        smd_feat_pos: pos
    });
}

jQuery(function() {
    jQuery("#smd_feat_search").keyup(function(event) {
        jQuery("#smd_feat_filt").val('0'); // Clear the filter dropdown
        // if esc is pressed or nothing is entered
        if (event.keyCode == 27 || jQuery(this).val() == '') {
            jQuery(this).val('');
            if ({$display_js} == 1) {
                jQuery(".smd_featured_table div:not(.tblhead)").show();
            }
        } else {
            smd_feat_filter('.smd_featured_table div:not(.tblhead)', jQuery(this).val(), 'smd_feat_name', 0, 0);
        }
    });
    if ('{$smd_feat_searchkeep}' != '') {
        jQuery("#smd_feat_search").keyup();
    }
    jQuery("#smd_feat_filt").change(function(event) {
        jQuery("#smd_feat_search").val(''); // Empty the search box
        if (jQuery(this).val() == '') {
            if ({$display_js} == 1) {
                jQuery('.smd_featured_table div:not(.tblhead)').show();
            }
        } else if (jQuery(this).val() == 'smd_unlabelled') {
            smd_feat_filter('.smd_featured_table div:not(.tblhead)', '', 'smd_feat_label', 0, 0);
        } else {
            smd_feat_filter('.smd_featured_table div:not(.tblhead)', jQuery(this).val(), 'smd_feat_label', 1, 1);
        }
    });
    if ('{$smd_feat_filtkeep}' != '') {
        jQuery("#smd_feat_filt").change();
    }
    if ('{$smd_feat_id}' != '') {
        jQuery("#smd_feat_label").focus();
        jQuery("#smd_feat_labelchoose").change(function(event) {
            jQuery("#smd_feat_label").val(jQuery(this).val());
        });
    }
    jQuery("#smd_feat_loading").hide();
    jQuery("#smd_container").show();
});

</script>
EOJS;

        echo '</div>';
    } else {
        // Table not installed
        $btnInstall = '<form method="post" action="?event='.$smd_featured_event.a.'step=smd_featured_table_install" style="display:inline">'.fInput('submit', 'submit', gTxt('smd_feat_tbl_install_lbl')).'</form>';
        $btnStyle = ' style="border:0;height:25px"';
        echo startTable();
        echo tr(tda(strong(gTxt('smd_feat_prefs_some_tbl')).br.br
                .gTxt('smd_feat_prefs_some_explain').br.br
                .gTxt('smd_feat_prefs_some_opts'), ' colspan="2"')
        );
        echo tr(tda($btnInstall, $btnStyle));
        echo endTable();
    }
}

// -------------------------------------------------------------
function smd_featured_change_pageby()
{
    global $smd_featured_event;

    event_change_pageby($smd_featured_event);
    smd_featured_list();
}

// ------------------------
// Update the passed record in the featured table
function smd_featured_save()
{
    global $smd_featured_event;

    extract(gpsa(array('smd_feat_id', 'smd_feat_label', 'smd_feat_title', 'smd_feat_desc', 'smd_feat_position')));
    assert_int($smd_feat_id);

    $smd_feat_titletile = $smd_feat_title;
    $smd_feat_desctile = $smd_feat_desc;
    $smd_feat_label = doSlash($smd_feat_label);
    $smd_feat_position = doSlash($smd_feat_position);

    if (smd_featured_table_exist()) {
        @include_once txpath.'/lib/classTextile.php';
        @include_once txpath.'/publish.php';

        $textileonoff = explode(',', get_pref('smd_featured_textile', ''));
        $txt_ttl = in_array('title', $textileonoff);
        $txt_desc = in_array('desc', $textileonoff);

        if (class_exists('Textile')) {
            $textile = new Textile();
            $smd_feat_titletile = doSlash((($txt_ttl) ? $textile->TextileThis(parse($smd_feat_title)) : parse($smd_feat_title)));
            $smd_feat_desctile = doSlash((($txt_desc) ? $textile->TextileThis(parse($smd_feat_desc)) : parse($smd_feat_desc)));
        }

        $smd_feat_title = doSlash($smd_feat_title);
        $smd_feat_desc = doSlash($smd_feat_desc);
        $ret = safe_upsert(SMD_FEAT, "label='$smd_feat_label', feat_position='$smd_feat_position', feat_title='$smd_feat_title', feat_title_html='$smd_feat_titletile', description='$smd_feat_desc', desc_html='$smd_feat_desctile'", "feat_id='$smd_feat_id'");
        unset($_POST['smd_feat_id']);
    }

    smd_featured_list(gTxt('smd_feat_saved'));
}

// ------------------------
// Create an empty entry in the featured table or destroy it
function smd_featured_tagit()
{
    global $smd_featured_event;
    extract(doSlash(gpsa(array('smd_feat_id', 'smd_feat_action'))));

    assert_int($smd_feat_id);

    if ($smd_feat_action == 'add') {
        $ret = safe_upsert(SMD_FEAT, "label=''", "feat_id='$smd_feat_id'");
    } else if ($smd_feat_action == 'remove') {
        $ret = safe_delete(SMD_FEAT, "feat_id='$smd_feat_id'");
    }
}

// -------------------------------------------------------------
// Stash the position against the given featured item
function smd_featured_store_pos()
{
    $id = gps('smd_feat_id');
    assert_int($id);

    $id = doSlash($id);
    $pos = doSlash(gps('smd_feat_pos'));

    $exists = safe_row('*', SMD_FEAT, "feat_id=$id");
    if ($exists) {
        safe_update(SMD_FEAT, "feat_position='$pos'", "feat_id=$id");
        send_xml_response();
    }
}

// -------------------------------------------------------------
function smd_featured_prefsave()
{
    global $smd_featured_pref_privs, $txp_user;

    // Three different types of pref can be stored: see below for details
    $stdprefs = array(
        PREF_GLOBAL => array(
            'smd_featured_section_list' => 'smd_feat_section_list',
        ),
        PREF_PRIVATE => array(
            'smd_featured_display' => 'smd_feat_display',
            'smd_featured_section_list' => 'smd_feat_section_list',
        )
    );
    $joinprefs = array(
        PREF_PRIVATE => array(
            // Index is the pref name; First item in array is the join string
            'smd_featured_box_size' => array('x', 'smd_feat_box_x', 'smd_feat_box_y'),
            'smd_featured_sort' => array(' ', 'smd_feat_sort', 'smd_feat_sortdir'),
        )
    );
    $arrayprefs = array(
        PREF_GLOBAL => array(
            'smd_featured_textile' => 'smd_feat_textile',
            'smd_featured_show_ui' => 'smd_feat_show_ui',
        )
    );

    $privs = safe_field('privs', 'txp_users', "name = '".doSlash($txp_user)."'");
    $rights = array_key_exists($privs, $smd_featured_pref_privs);
    $perprefs = ($rights) ? $smd_featured_pref_privs[$privs] : array();
    $preflist = array_merge($smd_featured_pref_privs['all'], $perprefs);

    // Standard prefs are just single widget values that are stored directly
    foreach ($stdprefs as $type => $prfs) {
        foreach ($prfs as $key => $val) {
            if (in_array($key, $preflist)) {
                set_pref(doSlash($key), doSlash(gps($val)), 'smd_featured', PREF_HIDDEN, 'text_input', 0, $type);
            }
        }
    }

    // Join prefs are discrete widget values (with different HTML names) that need combining into a single pref value
    foreach ($joinprefs as $type => $prfs) {
        foreach ($prfs as $key => $val) {
            if (in_array($key, $preflist)) {
                $joinstr = '';
                $combined = array();
                foreach ($val as $idx => $item) {
                    if ($idx==0) {
                        $joinstr = $item;
                    } else {
                        $combined[] = doSlash(gps($item));
                    }
                }
                set_pref(doSlash($key), join($joinstr, $combined), 'smd_featured', PREF_HIDDEN, 'text_input', 0, $type);
            }
        }
    }

    // Array prefs are widget values from combined checkboxes under the same HTML name.
    // They may not be presented as arrays if only one item is checked
    foreach ($arrayprefs as $type => $prfs) {
        foreach ($prfs as $key => $val) {
            if (in_array($key, $preflist)) {
                $joinstr = ',';
                $val = doSlash(gps($val));
                $combined = join($joinstr, ((is_array($val)) ? $val : array($val)));
                set_pref(doSlash($key), $combined, 'smd_featured', PREF_HIDDEN, 'text_input', 0, $type);
            }
        }
    }
    smd_featured_list(gTxt('preferences_saved'));
}

// -------------------------------------------------------------
function smd_featured_save_pane_state()
{
    $panes = array('smd_featured_cpanel');
    $pane = gps('pane');
    if (in_array($pane, $panes))
    {
        set_pref("pane_{$pane}_visible", (gps('visible') == 'true' ? '1' : '0'), 'smd_featured', PREF_HIDDEN, 'yesnoradio', 0, PREF_PRIVATE);
        send_xml_response();
    } else {
        send_xml_response(array('http-status' => '400 Bad Request'));
    }
}

// ------------------------
// Add featured table if not already installed
function smd_featured_table_install($showpane = '1')
{
    $GLOBALS['txp_err_count'] = 0;
    $ret = '';
    $sql = array();
    $sql[] = "CREATE TABLE IF NOT EXISTS `".PFX.SMD_FEAT."` (
        `feat_id`         int(8)       NOT NULL default 0,
        `label`           varchar(32)  NULL     default '',
        `feat_position`   varchar(16)  NULL     default '',
        `feat_title`      varchar(255) NULL     default '',
        `feat_title_html` varchar(255) NULL     default '',
        `description`     varchar(255) NULL     default '',
        `desc_html`       varchar(255) NULL     default '',
        PRIMARY KEY (`feat_id`)
    ) ENGINE=MyISAM";

    if (gps('debug')) {
        dmp($sql);
    }

    foreach ($sql as $qry) {
        $ret = safe_query($qry);
        if ($ret===false) {
            $GLOBALS['txp_err_count']++;
            echo "<b>".$GLOBALS['txp_err_count'].".</b> ".mysql_error()."<br />\n";
            echo "<!--\n $qry \n-->\n";
        }
    }

    // Spit out results
    if ($GLOBALS['txp_err_count'] == 0) {
        if ($showpane) {
            $message = gTxt('smd_feat_tbl_installed');
            smd_featured_list($message);
        }
    } else {
        if ($showpane) {
            $message = gTxt('smd_feat_tbl_not_installed');
            smd_featured_list($message);
        }
    }
}

// ------------------------
// Drop table if in database
function smd_featured_table_remove()
{
    $ret = '';
    $sql = array();
    $GLOBALS['txp_err_count'] = 0;
    if (smd_featured_table_exist()) {
        $sql[] = "DROP TABLE IF EXISTS " .PFX.SMD_FEAT. "; ";
        if(gps('debug')) {
            dmp($sql);
        }
        foreach ($sql as $qry) {
            $ret = safe_query($qry);
            if ($ret===false) {
                $GLOBALS['txp_err_count']++;
                echo "<b>".$GLOBALS['txp_err_count'].".</b> ".mysql_error()."<br />\n";
                echo "<!--\n $qry \n-->\n";
            }
        }
    }
    if ($GLOBALS['txp_err_count'] == 0) {
        $message = gTxt('smd_feat_tbl_removed');
    } else {
        $message = gTxt('smd_feat_tbl_not_removed');
        smd_featured_list($message);
    }
}

// ------------------------
// Handle upgrades from previous versions.
function smd_featured_table_upgrade()
{
    global $DB;

    $varCharSize = (version_compare($DB->version, '5.0.3', '>=') ? '16384' : '255');
    $colInfo = getRows('describe `'.PFX.SMD_FEAT.'`');
    $cols = array();
    $descTypes = array();

    foreach ($colInfo as $row) {
        $cols[] = $row['Field'];

        if (in_array($row['Field'], array('description', 'desc_html'))) {
            $descTypes[$row['Field']] = $row['Type'];
        }
    }

    if (!in_array('feat_id', $cols)) {
        safe_alter(SMD_FEAT, "CHANGE `id` `feat_id` int( 8 ) NOT NULL DEFAULT '0'");
    }
    if (!in_array('feat_title', $cols)) {
        safe_alter(SMD_FEAT, "ADD `feat_title` varchar( 255 ) NULL DEFAULT '' AFTER `label`");
    }
    if (!in_array('feat_title_html', $cols)) {
        safe_alter(SMD_FEAT, "ADD `feat_title_html` varchar( 255 ) NULL DEFAULT '' AFTER `feat_title`");
    }
    if (!in_array('feat_position', $cols)) {
        safe_alter(SMD_FEAT, "ADD `feat_position` varchar ( 16 ) NULL DEFAULT '' AFTER `label`");
    }
    if ($descTypes['description'] === 'text') {
        safe_alter(SMD_FEAT, "MODIFY `description` varchar (" . $varCharSize . ") NULL");
    }
    if ($descTypes['desc_html'] === 'text') {
        safe_alter(SMD_FEAT, "MODIFY `desc_html` varchar (" . $varCharSize . ") NULL");
    }
}

// ------------------------
function smd_featured_table_exist($all = '')
{
    if ($all) {
        $tbls = array(SMD_FEAT => 7);
        $out = count($tbls);

        foreach ($tbls as $tbl => $cols) {
            if (gps('debug')) {
                echo "++ TABLE ".$tbl." HAS ".count(@safe_show('columns', $tbl))." COLUMNS; REQUIRES ".$cols." ++".br;
            }
            if (count(@safe_show('columns', $tbl)) == $cols) {
                $out--;
            }
        }
        return ($out === 0) ? 1 : 0;
    } else {
        if (gps('debug')) {
            echo "++ TABLE ".SMD_FEAT." HAS ".count(@safe_show('columns', SMD_FEAT))." COLUMNS;";
        }
        return (@safe_show('columns', SMD_FEAT));
    }
}

// ****************
// PUBLIC SIDE TAGS
// ****************
// ------------------------
function smd_featured($atts, $thing)
{
    global $smd_featured_info, $smd_prior_featured, $prefs;

    extract(lAtts(array(
        'label'    => '',
        'unlabel'  => 'Featured',
        'labeltag' => '',
        'time'     => 'past',
        'status'   => '4,5',
        'section'  => '',
        'history'  => '1',
        'limit'    => '10',
        'sort'     => 'feat_position asc, Posted desc',
        'form'     => '',
        'wraptag'  => '',
        'break'    => '',
        'class'    => '',
        'html_id'  => '',
        'debug'    => '',
    ),$atts));

    $unlabel = trim($unlabel);
    // Use isset() because unlabelled articles ($label="") is a valid user option
    if (isset($atts['label'])) {
        $label = trim($label);
        if ($label) {
            $where = "BINARY label REGEXP '".$label."'";
        } else {
            $where = "label=''";
        }
        unset($atts['label']);
    } else {
        // If no label attribute has been given, treat this as 'all featured items'
        $ids = safe_column('feat_id', SMD_FEAT, '1=1');
        $ids = join("','", doSlash($ids));
        $where = "smdfeat.feat_id IN ('$ids')";
    }

    // Exclude previously seen articles
    if ($history && !empty($smd_prior_featured)) {
        $where .= ' AND smdfeat.feat_id NOT IN(' . join(',', $smd_prior_featured) . ')';
    }

    // NOTE: time is left in the $atts array and passed to article_custom. Otherwise the default
    // time value (past) will be used
    if ($time) {
        switch ($time) {
            case 'any':
                break;
            case 'future':
                $where .= " AND Posted > now()";
                break;
            default:
                $where .= " AND Posted <= now()";
        }
        if (!$prefs['publish_expired_articles']) {
            $where .= " AND (now() <= Expires OR Expires IS NULL)";
        }
    }

    if ($status) {
        $where .= ' AND Status IN ('.join(',', do_list($status)).')';
        unset($atts['status']);
    }

    if ($section) {
        $where .= " AND Section IN ('".join("','", do_list($section))."')";
        unset($atts['section']);
    }

    if ($sort) {
        $where .=' ORDER BY '.$sort;
        unset($atts['sort']);
    }

    // Leave limit in the $atts array too so it doesn't default to article_custom's value if set here
    if ($limit) {
        $where .=' LIMIT '.$limit;
    }

    // Don't pass the remaining attributes we've already handled onto the article_custom tag
    unset(
        $atts['label'],
        $atts['unlabel'],
        $atts['labeltag'],
        $atts['wraptag'],
        $atts['break'],
        $atts['class'],
        $atts['history'],
        $atts['html_id'],
        $atts['debug']
    );

    if ($debug) {
        echo '++ WHERE ++';
        dmp($where);
    }

    $rs = getRows('SELECT *, unix_timestamp(Posted) as uPosted, unix_timestamp(Expires) as uExpires, unix_timestamp(LastMod) as uLastMod FROM '.PFX.'textpattern AS txp LEFT JOIN '.PFX.SMD_FEAT.' AS smdfeat ON txp.ID=smdfeat.feat_id WHERE '.$where, $debug);

    if ($debug > 1 && $rs) {
        echo '++ RECORD SET ++';
        dmp($rs);
    }

    $truePart = EvalElse($thing, 1);
    $falsePart = EvalElse($thing, 0);

    $out = array();
    if ($rs) {
        foreach ($rs as $row) {
            $smd_featured_info['label'] = $row['label'];
            $smd_featured_info['position'] = $row['feat_position'];
            $smd_featured_info['title'] = $row['feat_title_html'];
            $smd_featured_info['description'] = $row['desc_html'];
            $atts['id'] = $row['ID'];

            $artout = article_custom($atts, $truePart);

            if ($artout) {
                $smd_prior_featured[] = $row['ID'];
                $out[] = $artout;
            }
        }
    } else {
        return parse($falsePart);
    }
    if ($out) {
        return (($labeltag) ? doLabel( ( ($label == '') ? $unlabel : $label), $labeltag ) : '')
            .doWrap($out, $wraptag, $break, $class, '', '', '', $html_id);
    }
    return '';
}

// ------------------------
function smd_unfeatured($atts, $thing)
{
    global $smd_prior_featured, $thispage, $pretext;

    $time = (isset($atts['time'])) ? $atts['time'] : '';
    $status = (isset($atts['status'])) ? $atts['status'] : 4;
    $section = (isset($atts['section'])) ? $atts['section'] : '';
    $history = (isset($atts['history'])) ? $atts['history'] : '1';
    unset($atts['history']);

    $where = "1=1";

    if ($time) {
        switch ($time) {
            case 'any':
                break;
            case 'future':
                $where .= " AND Posted > now()";
                break;
            default:
                $where .= " AND Posted <= now()";
        }
        if (!$prefs['publish_expired_articles']) {
            $where .= " AND (now() <= Expires OR Expires IS NULL)";
        }
    }

    if ($status) {
        $where .= ' AND Status IN ('.join(',', do_list($status)).')';
    }

    if ($section) {
        $where .= " AND Section IN ('".join("','", do_list($section))."')";
    }

    // Exclude previously seen articles
    if ($history && !empty($smd_prior_featured)) {
        $where .= ' AND id NOT IN(' . join(',', $smd_prior_featured) . ')';
    }

    $offset = (isset($atts['offset'])) ? $atts['offset'] : 0;

    if (isset($atts['limit']) && isset($atts['pageby'])) {
        $limit = $atts['limit'];
        $pageby = $atts['pageby'];
        $pageby = ($pageby == 'limit') ? $limit : $pageby;
        $pg = gps('pg');

        $grand_total = safe_count('textpattern',$where);
        $total = $grand_total - $offset;
        $numPages = ceil($total/$pageby);
        $pg = (!$pg) ? 1 : $pg;
        $pgoffset = $offset + (($pg - 1) * $pageby);
        // send paging info to txp:newer and txp:older
        $pageout['pg']          = $pg;
        $pageout['numPages']    = $numPages;
        $pageout['s']           = $pretext['s'];
        $pageout['c']           = $pretext['c'];
        $pageout['context']     = 'article';
        $pageout['grand_total'] = $grand_total;
        $pageout['total']       = $total;

        if (empty($thispage)) {
            $thispage = $pageout;
        }
    } else {
        $pgoffset = $offset;
    }

    $truePart = EvalElse($thing, 1);
    $falsePart = EvalElse($thing, 0);

    $rs = safe_column('ID', 'textpattern', $where);
    if ($rs) {
        $atts['offset'] = $pgoffset;
        $atts['id'] = join(',', $rs);
        $out = article_custom($atts, $truePart);
        return $out;
    } else {
        return parse($falsePart);
    }
}

// ------------------------
function smd_featured_info($atts)
{
    global $smd_featured_info;

    extract(lAtts(array(
        'item' => '',
    ),$atts));

    return (isset($smd_featured_info[$item]) ? $smd_featured_info[$item] : '');
}

// ------------------------
function smd_if_featured($atts, $thing)
{
    global $smd_featured_info, $thisarticle;

    extract(lAtts(array(
        'id' => '',
        'label' => '',
    ),$atts));

    if ($id) {
        $id = join("','", do_list(doSlash($id)));
    }

    if (!$id && $thisarticle) {
        $id = $thisarticle['thisid'];
    }

    $where[] = "feat_id IN ('".$id."')";

    if (isset($atts['label'])) {
        $label = explode(',', doSlash(trim($label)));
        $lblwhere = array();
        foreach ($label as $lbl) {
            $lblwhere[] = "'".trim($lbl)."'";
        }
        if ($lblwhere) {
            $where[] = '( BINARY label IN (' . join(',', $lblwhere) . ') )';
        }
    }

    $ret = safe_row('feat_id', SMD_FEAT, join(' AND', $where));

    return parse(EvalElse($thing, $ret));
}