<?php
/*

Copyright 2005-2007 Alex Shiels http://thresholdstate.com/

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
version 2 as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

function zem_redirect_fix_url($url, $debug=0) {
	$parts = parse_url($url);
	extract($parts);
	$q = preg_replace('@&amp;@', '&', @$query);
	$q = $q ? '?'.$q : '';

	$host = empty($scheme) ? serverSet('HTTP_HOST') : $host;
	$scheme = empty($scheme) ? 'http' : $scheme;
	$port = empty($port) ? '' : ':'.$port;

	return $scheme.'://'.$host.$port.'/'.ltrim($path, '/').$q;
}

function zem_redirect_send_headers($location, $type='', $debug=0) {
	global $prefs, $pretext;
	extract($prefs);

	if ($production_status == 'debug' or $debug) {
		echo '<div style="color:#000;background-color:#fee;">'
			.'zem_redirect: from <a href="'.$pretext['request_uri'].'">'.$pretext['request_uri'].'</a> to <a href="'.$location.'">'.$location.'</a></div>'.n;
		return $location;
	}
	else {
		if ($production_status == 'live') {
			if (!$type)
				$type = '301 Moved Permanently';
		}
		else
			$type = '302 Found';

		txp_status_header($type);
		header("Location: $location");
		ob_flush();
		exit;
	}

}


	function zem_redirect_url($test=0) {
		global $pretext, $siteurl, $path_from_root, $prefs;

		extract($prefs);

 		if (empty($permlink_mode)
 			or $permlink_mode == 'messy'
			or strcasecmp(serverSet('REQUEST_METHOD'), 'POST') == 0
			or gps('txpreview'))
 			return;


		$actual_host = serverSet("HTTP_HOST");
		$actual_path = ($test !== 0 ? $test : $pretext['request_uri']);
		$actual_url = 'http://'.$actual_host.$actual_path;
		$canonical_url = '';

		# other unknown query parameters might be used by plugins
		$get = gpsa(array_keys($_GET));
		if (!$get) $get = array();
		unset($get['id']);
		unset($get['s']);
		unset($get['c']);

		// remove bogus GET paramaters inserted by rss_unlimited_categories
		global $plugins;
		if (@in_array('rss_unlimited_categories', $plugins)) {
			unset($get['request_uri']);
			unset($get['qs']);
		}

		if (gps('atom') or gps('rss')) {
			$canonical_url = pagelinkurl($get);
		}
		elseif ($pretext['status'] == 404) {
			// url with subdir stripped
			$url = @$pretext['req'];

			if (preg_match('@/(\d{4})/(\d{2})/(\d{2})/([^/?]*)/?@', $url, $m)) {
				$when = $m[1].'-'.$m[2].'-'.$m[3];
				$rs = lookupByDateTitle($when,$m[4]);
				if (!$rs)
					$rs = lookupByTitle($m[4]);
			}
			elseif (preg_match('@/([^/]+)/(\d+)/([^/?]+)/?@', $url, $m)) {
				$rs = lookupByID($m[2]);
			}
			elseif (preg_match('@/([^/?]+)/([^/?]+)/?@', $url, $m)) {
				if (is_numeric($m[2]))
					$rs = lookupByID($m[2]);
				else {
					$rs = lookupByTitleSection($m[2], $m[1]);
					if (!$rs)
						$rs = lookupByTitle($m[2]);
				}
			}
			elseif (preg_match('@/([^/?]+)/?@', $url, $m)) {
				$rs = lookupByTitle($m[1]);
			}

			if ($rs) {
				$canonical_url = permlinkurl_id($rs['ID']) . join_qs($get);
			}
			else {
				return;
			}
		}
		elseif ($pretext['s'] == 'file_download') {
			return;
		}
		elseif ($pretext['month'] and $permlink_mode == 'year_month_day_title') {
			return;
		}
		elseif ($pretext["id"]) {
			# Article page
			$parts = parse_url($pretext['req']);
			if (isset($parts['path'])) {
				$pathinfo = pathinfo($parts['path']);
				if (!empty($pathinfo['extension']) and $pathinfo['basename'] != 'index.php') {
					return;
				}
			}
			$id = safe_field("ID","textpattern","ID='".doSlash($pretext["id"])."' and Status IN ('4', '5') limit 1");
			if ($id) {
				$canonical_url = permlinkurl_id($id);
			}

			# reattach messy URL parameters, if any
			$canonical_url .= join_qs($get);
		}
		else {
			# List page
			$canonical_url = pagelinkurl($get, array('s'=>$pretext['s'], 'c'=>$pretext['c']));
		}

		if ($canonical_url) {

			# fix up ampersands
			$canonical_url = zem_redirect_fix_url($canonical_url);
			if ($actual_url != $canonical_url) {
				return zem_redirect_send_headers($canonical_url, '301 Moved Permanently', $test !== 0);
			}
		}

	}


function zem_redirect_handler($event, $step) {
	if ($event == 'textpattern')
		zem_redirect_url();
}

register_callback('zem_redirect_handler', 'textpattern');

if (txpinterface == 'public' and (gps('atom') or gps('rss'))) {
	zem_redirect_url();
}


function zem_redirect($atts) {
	global $pretext;

	extract(lAtts(array(
		'to' => '',
		'from' => '',
		'debug' => 0,
		'type' => '302',
	), $atts));

	$to = trim($to);
	$from = trim($from);


	$dest = '';
	if ($from and $to) {
		$from = addcslashes($from, '@');
		$to = addcslashes($to, '@');
		if (preg_match('@'.$from.'@', $pretext['request_uri'])) {
			$out = preg_replace('@'.$from.'@', $to, $pretext['request_uri']);
			$dest = zem_redirect_fix_url($out, $debug);
		}
	}
	elseif ($to) {
		$dest = zem_redirect_fix_url($to, $debug);
	}
	else {
		trigger_error('No destination specified');
	}

	$actual_host = serverSet("HTTP_HOST");
	$actual_path = ($test !== 0 ? $test : $pretext['request_uri']);
	$actual_url = 'http://'.$actual_host.$actual_path;

	if ($dest and $dest != $actual_url) {
		$status = ($type == '301' ? '301 Moved Permanently' : '302 Found');
		return zem_redirect_send_headers($dest, $status, $debug);
	}

}
