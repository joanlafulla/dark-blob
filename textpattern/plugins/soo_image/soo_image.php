<?php

if(class_exists('\Textpattern\Tag\Registry')) {
	Txp::get('\Textpattern\Tag\Registry')
		->register('soo_image_select')
		->register('soo_image')
		->register('soo_image_alt')
		->register('soo_image_author')
		->register('soo_image_caption')
		->register('soo_image_category')
		->register('soo_image_date')
		->register('soo_image_height')
		->register('soo_image_id')
		->register('soo_image_name')
		->register('soo_image_url')
		->register('soo_image_width')
		->register('soo_image_thumbnail_height')
		->register('soo_image_thumbnail_url')
		->register('soo_image_thumbnail_width')
		->register('soo_if_txp_image')
		->register('soo_if_image_author')
		->register('soo_if_image_category')
		->register('soo_if_image_thumbnail')
		->register('soo_if_image_count')
		->register('soo_image_next')
		->register('soo_image_prev')
		->register('soo_image_page_count')
		->register('soo_exif')
		->register('soo_exif_field')
		->register('soo_exif_value')
		;
}

require_plugin('soo_txp_obj');
@require_plugin('soo_plugin_pref');		// optional

// Plugin init not needed on admin side
if ( @txpinterface == 'public' )
{
	global $soo_image;
	$soo_image = function_exists('soo_plugin_pref_vals') ?
		array_merge(soo_image_defaults(true), soo_plugin_pref_vals('soo_image'))
		: soo_image_defaults(true);
	$soo_image['page_param'] = 'soo_img_pg';
	$soo_image['jump_param'] = 'soo_img_jp';
}
elseif ( @txpinterface == 'admin' )
{
	add_privs('plugin_prefs.soo_image','1,2');
	add_privs('plugin_lifecycle.soo_image','1,2');
	register_callback('soo_image_prefs', 'plugin_prefs.soo_image');
	register_callback('soo_image_prefs', 'plugin_lifecycle.soo_image');
}

function soo_image_prefs( $event, $step ) {
	if ( function_exists('soo_plugin_pref') )
		return soo_plugin_pref($event, $step, soo_image_defaults());
	if ( substr($event, 0, 12) == 'plugin_prefs' ) {
		$plugin = substr($event, 12);
		$message = '<p><br /><strong>' . gTxt('edit') . " $plugin " .
			gTxt('edit_preferences') . ':</strong><br />' . gTxt('install_plugin')
			. ' <a href="http://ipsedixit.net/txp/92/soo_plugin_pref">soo_plugin_pref</a></p>';
		pagetop(gTxt('edit_preferences') . " &#8250; $plugin", $message);
	}
}

function soo_image_defaults( $vals_only = false ) {
	$defaults = array(
		'default_form'			=>	array(
			'val'	=>	'soo_image',
			'html'	=>	'text_input',
			'text'	=>	'Default image form',
		),
		'default_listform'		=>	array(
			'val'	=>	'',
			'html'	=>	'text_input',
			'text'	=>	'Default image list form',
		),
		'default_dimensions'	=>	array(
			'val'	=>	1,
			'html'	=>	'yesnoradio',
			'text'	=>	'Add height and width in pixels to img tag by default?',
		),
		'persistent_context'	=>	array(
			'val'	=>	1,
			'html'	=>	'yesnoradio',
			'text'	=>	'Use persistent context for tags outside an image form?',
		),
	);
	if ( $vals_only )
		foreach ( $defaults as $name => $arr )
			$defaults[$name] = $arr['val'];
	return $defaults;
}

  //---------------------------------------------------------------------//
 //									Tags								//
//---------------------------------------------------------------------//

function soo_image_select( $atts, $thing = null ) {

// Controller for the soo_image plugin.
//
// Determine what images the user is requesting, fetch image data objects
// (or raw URLs, as appropriate) for those images, and pass this along
// to an image form (i.e., a form called by this tag, containing one or
// more of soo_image's output tags) for display.

	global $soo_image, $thisarticle, $context, $c;

	extract(lAtts(array(
		'aspect_ratio'	=>	'',
		'author'		=>	'',
		'break'			=>	'',
		'category'		=>	'',
		'class'			=>	'',
		'ext'			=>	'',
		'form'			=>	$soo_image['default_form'],		// required
		'html_id'		=>	'',
		'id'			=>	'',
		'limit'			=>	0,
		'listform'		=>	$soo_image['default_listform'],
		'name'			=>	'',
		'pagination'	=>	true,
		'persistent_context'	=> $soo_image['persistent_context'],
		'sort'			=>	'name asc',		// accepts lists, e.g. "a asc,b,c desc"
		'wraptag'		=>	'',
	), $atts));

	$page = intval(gps($soo_image['page_param']));
	if ( $page < 1 ) $page = 1;
	$offset = $pagination ? ($page - 1) * $limit : 0;

	$jump_to = intval(gps($soo_image['jump_param']));

	$query = new soo_txp_select('txp_image');

// Selection priority: name; id; (category|author|ext|aspect_ratio); article image.
// Comma-separated lists allowed for all criteria; preserve list order for name|id.

	if ( $name or $id ) {
		$sort = '';
		if ( $name )
			$query->in('name', $name)->order_by_field('name', $name);
		else
			$ids = _soo_range_to_list($id);
	}

	elseif ( $category or $author or $ext or $aspect_ratio ) {
		if ( $category ) $query->in('category', $category);
		if ( $author ) $query->in('author', $author);
		if ( $ext ) {
			if ( $ext = _soo_valid_ext($ext) )
				$query->in('ext', $ext);
			else
				return;
		}
		if ( $aspect_ratio ) _soo_image_aspect_ratio($query, trim($aspect_ratio));
	}

	elseif ( ! empty($thisarticle) ) {
		$image_array = _soo_article_image_list();
		if ( ! $image_array )
			return false;
		foreach ( $image_array as $img ) {
			if ( is_numeric($img) )
				$ids[] = $img;
			else
				$urls[] = $img;
		}
	}

	elseif ( $context == gTxt('image_context') and $c ) {
		$query->in('category', $c);
	}

	else return;

	if ( isset($ids) ) {
		$sort = '';
		$query->in('id', $ids)->order_by_field('id', $ids);
	}

	$full_count = $query->count();
	if ( $pagination && $full_count <= $offset ) {		// in case of direct URI manipulation and invalid #
		$page = $limit ? ceil($full_count / $limit) : 1;	// go to last page
		$offset = ($page - 1) * $limit;
	}

	$soo_image['this_page'] = $page;
	if ( $pagination ) $soo_image['total_pages'] = $limit ? ceil($full_count / $limit) : 1;

	if ( $jump_to and is_numeric($jump_to) ) {
		$uri = new soo_uri;
		$uri->set_query_param($soo_image['jump_param']);
		$all_ids = new soo_txp_rowset($query->order_by($sort));
		$all_ids = array_flip($all_ids->field_vals('id'));
		if ( isset($all_ids[$jump_to]) ) {
			$jump_index = $all_ids[$jump_to];
			if ( $limit ) {
				$page = ceil(($jump_index + 1) / $limit);
				$offset = ($page - 1) * $limit;
				$_POST[$soo_image['page_param']] = $page;
			}
		}
	}

	$query->order_by($sort)->limit($limit)->offset($offset);
	$rs = new soo_txp_rowset($query);
	unset($query);

	if ( isset($urls) )
		foreach ( $image_array as $i => $img ) {
			if ( isset($rs->rows[$img]) )
				$image_array[$i] = $rs->rows[$img];
			elseif ( ! in_array($img, $urls) )
				unset($image_array[$i]);
		}
	else
		$image_array = $rs->rows;

	if ( empty($image_array) )
		return false;

	$soo_image['count'] = count($image_array);

	if ( $pagination && $limit && ( $offset + $limit < $full_count ) )
		$soo_image['next'] = $page + 1;

	// $image_array now contains data objects and/or presumed URLs

	if ( ( $soo_image['count'] > 1 or $page > 1) and $listform )
		$form = $listform;

	$soo_image['first_selection'] = isset($jump_index) ? new soo_txp_img($jump_to) : current($image_array);

	foreach ( $image_array as $img ) {
		if ( $img instanceof soo_txp_img )
			$soo_image['data_obj'] = $img;
		else
			$soo_image['url'] = $img;
		$parsed = is_null($thing) ? parse_form($form) : parse($thing);
		if ( $parsed ) $out[] = $parsed;
		unset($soo_image['data_obj']);
		unset($soo_image['url']);
	}

	if ( ! $persistent_context )
		unset($soo_image['first_selection']);

	unset($soo_image['count']);

	return isset($out) ? doWrap($out, $wraptag, $break, $class, '', '', '', $html_id) : false;

}
////////////////////// end of soo_image_select /////////////////////

function soo_image( $atts ) {
// Display an image. Selection by, in descending order of priority:
	// the tag's 'name' attribute;
	// the tag's 'id' attribute;
	// the image context:
		// id or url sent by $soo_image to an image form
		// article image
		// first selection from prior soo_image_select (subject to pref setting)

	extract(lAtts(array(
		'id'			=>	'',
		'name'			=>	'',
		'thumbnail'		=>	false,
		'height'		=>	'',
		'width'			=>	'',
		'html_id'		=>	'',
		'class'			=>	'',
		'title'			=>	'{caption}',
		'link'			=>	false,
		'link_class'	=>	'',
		'link_id'		=>	'',
		'link_rel'		=>	'',
		'link_to'		=>	'',
		'escape'		=>	true,
		'onclick'		=>	'',		// only for linked thumbnails
	), $atts));

	global $soo_image;

	if ( $link_class or $link_id or $link_rel or $onclick or $link_to )
		$link = true;

	if ( $link )
		$thumbnail = true;

	if ( $name or $id )
		$image = new soo_txp_img($name ? $name : $id);
	else
		$image = _soo_image_by_context();
			// false if image context is empty
			// otherwise a soo_txp_img or soo_html_img object
			// if soo_html_img it will be URL only, no other properties

	if ( $image instanceof soo_html_img and $thumbnail )
		return false;

	if ( $image instanceof soo_txp_img ) {
		if ( $thumbnail and !$image->thumbnail )
			return false;
		$image_data = $image;
		$image = new soo_html_img($image_data, $thumbnail, $escape);
		if ( $title )
		{
			$tokens = array('{alt}', '{caption}');
			$replace = array($image->alt, $image->title);
			if ( strpos($title, '{author}') !== false )
			{
				$tokens[] = '{author}';
				$replace[] = get_author_name($image_data->author);
			}
			$title = str_replace($tokens, $replace, $title);
		}
	}

	if ( ! ( $image instanceof soo_html_img ) )
		return false;

// Standard height and width attributes (i.e., actual size in pixels)
// will be added to the img tag if the default_dimensions preference is true
// and has not been overriden (by specifying height or width other than 1),
// or if either attribute has been set to 1. Otherwise, height and width
// attributes will be passed through as is (in which case a value of 0
// or empty will result in no attribute in the img tag).

	if ( $width === '0' or $height === '0' or $width > 1 or $height > 1 )
		$soo_image['default_dimensions'] = false;

	if ( ! $soo_image['default_dimensions'] and $height != 1 and $width != 1 )
		$image->height($height)->width($width);

	$image->class($class)
		->id($html_id)
		->title($title);

	// Shadowbox-specific block to create galleries
 	if ( strtolower($link_rel) == "shadowbox"
 		and isset($soo_image['first_selection'])
 		and $soo_image['first_selection'] instanceof soo_txp_img ) {
 			$img_obj = $soo_image['first_selection'];
 			$link_rel .= '[' . $img_obj->id . ']';
	}

	if ( $thumbnail and $link ) {
		if ( $link_to )
			$url = hu . $link_to . '?' . $soo_image['jump_param']
				. '=' . $image_data->id;
		else
			$url = $image_data->full_url;
		$anchor = new soo_html_anchor($url, $image);
		$anchor->title($title)
			->onclick($onclick)
			->rel($link_rel)
			->class($link_class)
			->id($link_id);
	}

	return isset($anchor) ? $anchor->tag() : $image->tag();
}
///////////////////////// end of soo_image //////////////////////////

function soo_image_alt($atts) { return _soo_image_data('alt', $atts); }
function soo_image_author($atts) { return _soo_image_data('author', $atts); }
function soo_image_caption($atts) {	return _soo_image_data('caption', $atts); }
function soo_image_category($atts) { return _soo_image_data('category', $atts); }
function soo_image_date($atts) { return _soo_image_data('date', $atts); }
function soo_image_height($atts) { return _soo_image_data('h', $atts); }
function soo_image_id($atts) { return _soo_image_data('id', $atts); }
function soo_image_name($atts) { return _soo_image_data('name', $atts); }
function soo_image_url($atts) { return _soo_image_data('full_url', $atts); }
function soo_image_width($atts) { return _soo_image_data('w', $atts); }
function soo_thumbnail_height($atts) { return _soo_image_data('thumb_h', $atts); }
function soo_thumbnail_url($atts) { return _soo_image_data('thumb_url', $atts); }
function soo_thumbnail_width($atts) { return _soo_image_data('thumb_w', $atts); }

function _soo_image_data($field, $atts = array()) {
	extract(lAtts(array(
		'format'	=>	'',
		'escape'	=>	1,
		'no_widow'	=>	1,
		'wraptag'	=> '',
		'class'		=> '',
		'html_id'	=> '',
	), $atts));
	$image = _soo_image_by_context();
	if ( ! $image instanceof soo_txp_img )
		$out = $field == 'url' ? $image : '';
	else
		switch ( $field ) {
			case 'alt':
				$out = $escape ? htmlspecialchars($image->alt) : $image->alt;
				break;
			case 'author':
				$out = get_author_name($image->author);
				break;
			case 'caption':
				$out = $escape ? htmlspecialchars($image->caption) : $image->caption;
				$out = $no_widow ? preg_replace('/\s+(\S+)$/', '&nbsp;\1', $out) : $out;
				break;
			case 'date':
				global $dateformat;
				$f = $format ? $format : $dateformat;
				$utime = safe_strtotime($image->date);
				$out = $image->date ? safe_strftime($f, $utime) : '';
				break;
			default:
				$out = $image->$field;
		}
	return doWrap(array($out), $wraptag, '', $class, '', '', '', $html_id);
}

function soo_if_txp_image($atts, $thing) {

	$image = _soo_image_by_context();
	return parse(EvalElse($thing, $image instanceof soo_txp_img ));
}

function soo_if_image_author($atts, $thing) {

	extract(lAtts(array('name' => ''), $atts));
	$image = _soo_image_by_context();
	return parse(EvalElse($thing, $image instanceof soo_txp_img and in_list($image->author, $name) ));
}

function soo_if_image_category($atts, $thing) {

	extract(lAtts(array('name' => ''), $atts));
	$image = _soo_image_by_context();
	return parse(EvalElse($thing, $image instanceof soo_txp_img and in_list($image->category, $name) ));
}

function soo_if_image_thumbnail($atts, $thing) {

	$image = _soo_image_by_context();
	return parse(EvalElse($thing, $image instanceof soo_txp_img and $image->thumbnail));
}

function soo_if_image_count($atts, $thing) {
// Expected context:
//   - Inside a soo_image_select tag/form, else
//   - Inside an article/article_custom tag/form, else
//   - On an image category page
// Otherwise the tag returns empty.
	extract(lAtts(array(
		'exact'		=> null,
		'min'		=> null,
		'max'		=> null,
	), $atts));
	global $soo_image, $thisarticle, $context, $c;

	if ( isset($soo_image['count']) )	// inside a soo_image_select
		$count = $soo_image['count'];
	elseif ( ! empty($thisarticle) )
		$count = $thisarticle['article_image'] ? count(do_list($thisarticle['article_image'])) : 0;
	elseif ( $context == gTxt('image_context') and ! empty($c) )
		$count = safe_count('txp_image', 'category = "' . $c . '"');
	else return;

	if ( ! is_null($exact) )
		$condition = $count == $exact;
	elseif ( ! is_null($min) )
		$condition = $count >= $min;
	elseif ( ! is_null($max) )
		$condition = $count <= $max;
	else return;

	return parse(EvalElse($thing, $condition));
}

function soo_image_next($atts, $thing = null) {

	extract(lAtts(array(
		'link_text'		=> '&rarr;',
		'class'			=> '',
		'html_id'		=> '',
	), $atts));
	global $soo_image;

	if ( ! isset($soo_image['next']) )
		return soo_util::secondpass(__FUNCTION__, $atts, $thing);
	$uri = new soo_uri;
	$uri->set_query_param($soo_image['jump_param']);
	$uri->set_query_param($soo_image['page_param'], $soo_image['next']);
	$out = new soo_html_anchor($uri->full, $thing ? $thing : $link_text);
	return $out->class($class)->id($html_id)->tag();
}

function soo_image_prev($atts, $thing = null) {

	extract(lAtts(array(
		'link_text'		=> '&larr;',
		'class'			=> '',
		'html_id'		=> '',
	), $atts));
	global $soo_image;

	$prev = intval(gps($soo_image['page_param'])) - 1;
	if ( $prev < 1 )
		return soo_util::secondpass(__FUNCTION__, $atts, $thing);
	$uri = new soo_uri;
	$uri->set_query_param($soo_image['jump_param']);
	$uri->set_query_param($soo_image['page_param'], $prev > 1 ? $prev : null);
	$out = new soo_html_anchor($uri->full, $thing ? $thing : $link_text);
	return $out->class($class)->id($html_id)->tag();
}

function soo_image_page_count($atts) {

	extract(lAtts(array(
		'format' 		=>	'Page {current} of {total}',
		'showalways'	=>	false,
	), $atts));
	global $soo_image;

	if ( ! isset($soo_image['this_page']) )
		return soo_util::secondpass(__FUNCTION__, $atts);

	if ( ! $showalways and $soo_image['total_pages'] <= 1 ) return;

	return str_replace(array('{current}', '{total}'),
		array($soo_image['this_page'], $soo_image['total_pages']), $format);
}

function soo_exif($atts, $thing) {

	global $dateformat, $soo_exif_field, $soo_exif_value;
	extract(lAtts(array(
		'field'		=>	'',
		'format'	=>	'{field}: {value}',
		'wraptag'	=>	'',
		'break'		=>	'',
	), $atts));

	$display = array(
		'Model'					=>	'Camera',
		'ExposureTime'			=>	'Shutter speed',
		'FNumber'				=>	'F-stop',
		'ISOSpeedRatings'		=>	'ISO speed',
		'DateTimeOriginal'		=>	'Exposure date',
		'FocalLength'			=>	'Focal length',
		'CropFactor'			=>	'Crop factor',
		'FOV'					=>	'Computed FOV',
		'ImageHistory'			=>	'History',
		);

	$shortcuts = array(
		'model'					=>	'Model',
		'exp-time'				=>	'ExposureTime',
		'f-number'				=>	'FNumber',
		'iso-speed'				=>	'ISOSpeedRatings',
		'date-taken'			=>	'DateTimeOriginal',
		'focal-len'				=>	'FocalLength',
		'crop'					=>	'CropFactor',
		'history'				=>	'ImageHistory',
	);

	if ( empty($field) )
		$fields = array_keys($display);

	else {
		$fields = do_list($field);
		foreach ( $fields as $i => $f )
			if ( array_key_exists(strtolower($f), $shortcuts) )
				$fields[$i] = $shortcuts[$f];
	}

	$exif = exif_read_data(_soo_image_path());

	extract($exif);

	if ( isset($FNumber) )
		$exif['FNumber'] = '&fnof;/' . round(_soo_image_convert_ratio($FNumber), 1);

	if ( isset($FocalLength) )
		$exif['FocalLength'] = _soo_image_convert_ratio($FocalLength) . 'mm';

	if ( isset($FocalLengthIn35mmFilm) ) {
		$fl35 = _soo_image_convert_ratio($FocalLengthIn35mmFilm);
		if ( isset($FocalLength) )
			$exif['CropFactor'] = round($fl35 / _soo_image_convert_ratio($FocalLength), 1);
		$exif['FocalLengthIn35mmFilm'] = $fl35 . 'mm';
		// 43.27 is the diagonal length (mm) of a full-frame sensor
		$exif['FOV'] = round(rad2deg(2 * atan(43.27 / ( 2 * $fl35 ))), 1) . '&deg;';
	}

	if ( isset($DateTimeOriginal) )
		$exif['DateTimeOriginal'] =
			safe_strftime($dateformat, strtotime($DateTimeOriginal));

	if ( strtolower($fields[0]) == 'dump' )
		$fields = array_keys($exif);

	foreach ( $fields as $k ) {
		if ( isset($exif[$k]) ) {
			$v = $exif[$k];
			$soo_exif_field = isset($display[$k]) ? $display[$k] : $k;
			$soo_exif_value = is_array($v) ? implode(' ', $v) : trim($v);
			$r_format = str_replace('{field}', $soo_exif_field, $format);
			$r_format = str_replace('{value}', $soo_exif_value, $r_format);
			$out[] = $thing ? parse($thing) : $r_format;
		}
	}
	return isset($out) ? doWrap($out, $wraptag, $break) : '';
}

function soo_exif_field($atts) {

	global $soo_exif_field;
	extract(lAtts(array(
		'wraptag'	=>	'',
	), $atts));

	if ( $soo_exif_field )
		return doWrap(array($soo_exif_field), $wraptag, '');
}

function soo_exif_value($atts) {

	global $soo_exif_value;
	extract(lAtts(array(
		'wraptag'	=>	'',
	), $atts));

	if ( $soo_exif_value ) {
		if ( ! is_array($soo_exif_value) )
			$soo_exif_value = array($soo_exif_value);
		return doWrap($soo_exif_value, $wraptag, '');
	}
}

  //---------------------------------------------------------------------//
 //							Support Functions							//
//---------------------------------------------------------------------//

function _soo_image_path() {

	global $img_dir;

	$img_dir_path = $_SERVER['DOCUMENT_ROOT'] . "/$img_dir/";
	$image = _soo_image_by_context();

	if ( $image instanceof soo_txp_img )
		$file_path = $img_dir_path . $image->id . $image->ext;

	elseif ( $image instanceof soo_html_img ) {
		$pattern = '&^(' . str_replace('.', '\.', hu) . "|\/)($img_dir.+)&";
		if ( preg_match($pattern, $image->src, $match) )
			$file_path = $match[2];
	}

	if ( empty($file_path) or ! file_exists($file_path) )
		return null;

	return $file_path;
}

function _soo_article_image_list() {
	global $thisarticle;
	return empty($thisarticle['article_image']) ?
		array() : _soo_range_to_list($thisarticle['article_image']);
}

function _soo_range_to_list( $csv ) {

	$items = do_list($csv);
	foreach ( $items as $item )
		if ( preg_match('/(\d+)(:|-)(\d+)/', $item, $match) ) {
			if ( $match[3] > $match[1] )
				for ( $i = $match[1]; $i <= $match[3]; $i++ )
					$out[] = $i;
			else
				for ( $i = $match[1]; $i >= $match[3]; $i-- )
					$out[] = $i;
		}
		else
			$out[] = $item;
	return $out;
}

function _soo_valid_ext( $in ) {
	$ok = array('.jpg', '.jpeg', '.gif', '.png', '.bmp');
	$exts = is_array($in) ? $in : do_list($in);
	$out = array();
	foreach ($exts as $ext) {
		$ext = '.' . ltrim($ext, '.');
		if ( in_array($ext, $ok) )
			$out[] = $ext;
	}
	return $out;
}

function _soo_image_convert_ratio( $r ) {
	if ( is_numeric($r) )
		return $r;
	if ( ! preg_match('/(.+)(:|\/)(.+)/', $r, $match) )
		return null;
	if ( is_numeric($match[1]) and is_numeric($match[3]) and $match[3] != 0 )
		return $match[1] / $match[3];
	else
		return null;
}

function _soo_image_aspect_ratio( $query, $aspect_ratio ) {

	$fudge = 0.01;
	$pattern = '/^(\+|-)?([^-]+)(-)?(.+)?$/';
	if ( ! preg_match($pattern, $aspect_ratio, $match) )
		return;

	if ( $match[1] == '+' ) {
		$min_ar = _soo_image_convert_ratio($match[2]);
		if ( is_null($min_ar) )
			return;
	}
	elseif ( $match[1] == '-' ) {
		$max_ar = _soo_image_convert_ratio($match[2]);
		if ( is_null($max_ar) )
			return;
	}

	elseif ( isset($match[3]) ) {
		$min_ar = _soo_image_convert_ratio($match[2]);
		$max_ar = _soo_image_convert_ratio($match[4]);
		if ( is_null($min_ar) or is_null($max_ar) )
			return;
		if ( $max_ar < $min_ar )
			list($max_ar, $min_ar) = array($min_ar, $max_ar);
	}
	else {
		$ar = _soo_image_convert_ratio($match[2]);
		if ( is_null($ar) )
			return;
		$query->where('w/h', $ar + $fudge, '<')
			->where('w/h', $ar - $fudge, '>');
		return;
	}
	if ( isset($min_ar) )
		$query->where('w/h', $min_ar - $fudge, '>=');
	if ( isset($max_ar) )
		$query->where('w/h', $max_ar + $fudge, '<=');

}

function _soo_image_by_context( ) {
// Return a single image object (Txp or Html) for the current image context.
	// Image passed by parent soo_image_select tag, or;
	// first article image, or;
	// first selection from previous soo_image_select tag (subject to prefs).
// Return false if image context is empty

	global $soo_image;

	$_soo_article_image_list = _soo_article_image_list();
	$article_image = array_shift($_soo_article_image_list);

	if ( isset($soo_image['data_obj']) )
		$img = $soo_image['data_obj'];

	elseif ( isset($soo_image['url']) )
		$img = $soo_image['url'];

	elseif ( $article_image )
		$img = $article_image;

	elseif ( ! empty($soo_image['first_selection']) )
		$img = $soo_image['first_selection'];

	if ( empty($img) ) return false;
	if ( is_object($img) ) return $img;
	if ( is_numeric($img) ) return new soo_txp_img($img);
	return new soo_html_img(array('src' => $img));
}
