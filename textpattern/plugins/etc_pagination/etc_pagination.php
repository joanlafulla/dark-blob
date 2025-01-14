<?php
// TXP 4.6 tag registration
	if(class_exists('\Textpattern\Tag\Registry')) Txp::get('\Textpattern\Tag\Registry')
		->register('etc_pagination')
		->register('etc_numpages')
		->register('etc_offset')
	;

if (@txpinterface == 'public') {
	register_callback('etc_pagination_url', 'pretext');
}

function etc_pagination_url($event, $step) {
	global $etc_pagination;
	$etc_pagination['url'] = preg_replace("|^https?://[^/]+|i","",serverSet('REQUEST_URI'));
}

function etc_pagination($atts, $thing='') {
	global $thispage, $etc_pagination;

	extract(lAtts(array(
		"root"=>null,
		"query"=>'',
		"pages"=>null,
//		"links"=>null,
		"page"=>null,
		"pgcounter"=>'pg',
		"offset"=>0,
		"range"=>-1,
		"scale"=>1,
		"mask"=>null,
		"link"=>'{*}',
		"current"=>'',
		"next"=>'',
		"prev"=>'',
		"first"=>null,
		"last"=>null,
		"gap"=>'&hellip;',
		"delimiter"=>',',
		"wraptag"=>'',
		"break"=>'',
		"class"=>'',
		"html_id"=>'',
		"atts"=>'',
		"reversenumberorder"=>0
	),$atts));

	$etc_pagination['pgcounter'] = $pgcounter;
	$cpages = 0;
	if(!isset($pages)) {
		$numberOfTabs = isset($thispage['numPages']) ? $thispage['numPages'] : 1;
		$pages = $numberOfTabs > 1 ? range(1, $numberOfTabs) : null;
	}
	elseif(strpos($pages, $delimiter) !== false) {
		$cpages = (strpos($pages, '::') !== false ? 2 : 1);
		$numberOfTabs = count($pages = do_list($pages, $delimiter));
	}
	elseif(strpos($pages, '..') !== false) {
		$cpages = 1;
		list($start, $end) = do_list($pages, '..');
		$numberOfTabs = count($pages = range($start, $end));
	} else {
		$numberOfTabs = intval($pages);
		$pages = range(1, $numberOfTabs);
	}

	if($numberOfTabs <= 1) return parse(EvalElse($thing, false));

	# if we got tabs, start the outputting
	$reversenumberorder = (int)$reversenumberorder;
	if(isset($links)) $links = array_pad(do_list($links, $delimiter), $numberOfTabs, '');
	elseif($cpages < 2) switch($reversenumberorder) {
		case 1 : case 2 : $links = array_reverse($pages); break;
		default : $links = &$pages;
	}
	else $links = array();

	$range = (int) $range;
	if($range < 0) $range = $numberOfTabs; else $range += 1;
	if($scale == 'auto') $scale = pow($numberOfTabs, 1/$range);
	else $scale = max(floatval($scale), 1);

	$out = $parts = array();
	$fragment = '';
	if($root === '') $hu = hu;
	elseif($root === null || $root[0] === '#') {$hu = strtok($etc_pagination['url'], '?'); $parts = $_GET; if($root) $fragment = $root;}
	else {
		$qs = parse_url($root);
		if(isset($qs['fragment'])) $root = str_replace($fragment = '#'.$qs['fragment'], '', $root);
		if(!empty($qs['query'])) parse_str(str_replace('&amp;', '&', $qs['query']), $parts);
		$hu = strtok($root, '?');
	}

	if($query) foreach(do_list($query, '&') as $qs) {
		@list($k, $v) = explode('=', $qs, 2);
		if(!isset($v)) if($k === '?') $parts = array();
			elseif($k === '#') $fragment = '';
			else unset($parts[$k]);
		else if($k === '#') $fragment = '#'.$v;
			elseif($k === '+') $hu .= $v;
			elseif($k[0] === '/') $hu = preg_replace($k, $v, $hu);
			else $parts[$k] = $v;
	}

	if(isset($page))
		if(!$cpages) $pgdefault = intval($page);
		elseif($cpages == 1)
			if(($pgdefault = array_search($page, $pages)) !== false) $pgdefault++;
			else $pgdefault = 0;
		else for($pgdefault = $numberOfTabs; $pgdefault > 0 && strpos($pages[$pgdefault-1].'::', $page.'::') !== 0; $pgdefault--);
	else $pgdefault = $reversenumberorder & 1 ? $numberOfTabs : 1;
	if(isset($parts[$pgcounter]))
		if($cpages < 2)
			if(($page = array_search($parts[$pgcounter], $links)) !== false) $page++;
			else $page = 0;
		else for($page = $numberOfTabs; $page > 0 && strpos($pages[$page-1].'::', $parts[$pgcounter].'::') !== 0; $page--);
	else $page = $pgdefault;
	$etc_pagination['page'] = $page;
	$page += $offset;
	if($page < 1 || $page > $numberOfTabs) return parse(EvalElse($thing, 0));

	unset($parts[$pgcounter]);
	$qs = array();//join_qs($parts);
	foreach($parts as $k => $v) $qs[] = urlencode($k) . '=' . urlencode(is_array($v) ? implode(',', $v) : $v);
	$qs = '?'.implode('&amp;', $qs);
	$pagebase = $qs !== '?' ? $hu.$qs : $hu;
	if($qs !== '?') $qs .= '&amp;';
	$pageurl = $pgcounter ? $hu.$qs.$pgcounter.'=' : '';

	$currentclass = (empty($thing) && $current && strpos($link, '{current}') === false ? ($break ? 1 : -1) : 0);

	@list($gap1, $gap2) = explode($delimiter, $gap); if(!isset($gap2)) $gap2 = $gap1;
	@list($link, $link_) = explode($delimiter, $link, 2); if(!isset($link_)) $link_ = $link;
	foreach(array('first', 'prev', 'next', 'last', 'current') as $item) if(isset($$item))
		{@list($$item, ${$item.'_'}) = explode($delimiter, $$item, 2); if(!isset(${$item.'_'})) ${$item.'_'} = '';}
	if($currentclass) {if($current) $current = " class='$current'"; if($current_) $current_ = " class='$current_'";}

	$skip1 = $range < 3 ? $range : 1 + ($gap1 ? 1 : 0) + (isset($first) ? 0 : 1);
	$skip2 = $range < 3 ? $range : 1 + ($gap2 ? 1 : 0) + (isset($last) ? 0 : 1);
	if($numberOfTabs < 2*$range) {$loopStart = 1; $loopEnd = $numberOfTabs;}
	elseif($page <= $range) {$loopStart = 1; $loopEnd = 2*$range - $skip2;}
	elseif($page > $numberOfTabs - $range) {$loopStart = $numberOfTabs - 2*$range + $skip1 + 1; $loopEnd = $numberOfTabs;}
	else {$loopStart = $page - $range + $skip1; $loopEnd = $page + $range - $skip2;}

	if($custom = isset($mask)) {
		if(isset($thing)) {$link = str_replace('{link}', $link, $thing); $link_ = str_replace('{link}', $link_, $thing);}
		$thing = $mask;
	}
	elseif(!isset($thing)) {
		if($link) $link = '<a href="{href}" data-rel="{rel}"'.($currentclass < 0 ? '{current}' : '').'>'.$link.'</a>';
		if($link_) $link_ = '<span data-rel="self"'.($currentclass < 0 ? '{current}' : '').'>'.$link_.'</span>';
		foreach(array('first', 'prev', 'next', 'last', 'gap1', 'gap2') as $item) {
			if(!empty($$item)) $$item = '<a href="{href}" rel="{rel}" title="{*}">'.$$item.'</a>';
			if(!empty(${$item.'_'})) ${$item.'_'} = '<span data-rel="'.$item.'">'.${$item.'_'}.'</span>';
		}
		$thing = '{link}';
	}
	else $thing = EvalElse($thing, 1);
	$replacements = array_fill_keys(array('{*}', '{#}', '{$}', '{href}', '{rel}', '{link}'), '');
	$replacements['{pages}'] = $numberOfTabs;
	$replacements['{current}'] = $current_;
	$mask = array_fill_keys(array('{links}', '{first}', '{prev}', '{next}', '{last}', '{<+}', '{+>}'), '');

	$outfirst = $outprev = $outgap = '';
	if($prev || $prev_) {
		etc_pagination_link($replacements, $links, $pages, $page-1, $pgdefault, $pagebase, $pageurl, $fragment, 'prev', $cpages>1);
		if($page <= 1) $replacements['{#}'] = $replacements['{*}'] = '';
		$replacements['{link}'] = $mask['{prev}'] = strtr($page > 1 ? $prev : $prev_, $replacements);
		if(!$custom && $replacements['{link}']) $outprev = strtr($thing, $replacements);
	}

	if($loopStart > 1 && $range > 1 || isset($first)) {
		etc_pagination_link($replacements, $links, $pages, 1, $pgdefault, $pagebase, $pageurl, $fragment, '', $cpages>1);
		$replacements['{link}'] = $mask['{first}'] = strtr(isset($first) ? ($page > 1 ? $first : $first_) : $link, $replacements);
		if(!$custom && $replacements['{link}']) $outfirst = strtr($thing, $replacements);
		if($gap1 && $loopStart > 1) {
//			if($custom) $mask['{<+}'] = $gap1; else $outgap = $gap1;
			$i = $loopStart-$range-1+$skip2;
			if($scale > 1 && $i >= $scale) {
				$n = pow($scale, min(floor(log($i, $scale)), ceil(log($numberOfTabs - $i + 1, $scale)))); $i = intval(floor($i/$n)*$n);
			}
			$i = max($range ? 2 : 1, $i);
			etc_pagination_link($replacements, $links, $pages, $i, $pgdefault, $pagebase, $pageurl, $fragment, 'prev', $cpages>1);
			if($replacements['{link}'] = strtr($gap1, $replacements))
				if($custom) $mask['{<+}'] = $replacements['{link}']; else $outgap = strtr($thing, $replacements);
		}
	}

	if($first) {
		if($outfirst) $out[] = $outfirst; if($outprev) $out[] = $outprev;
	} else {
		if($outprev) $out[] = $outprev; if($outfirst) $out[] = $outfirst;
	}
	if($outgap) $out[] = $outgap;

	if($link || $link_) for($i=$loopStart; $i<=$loopEnd; $i++) {
		etc_pagination_link($replacements, $links, $pages, $i, $pgdefault, $pagebase, $pageurl, $fragment, $i == $page-1 ? 'prev' : ($i == $page+1 ? 'next' : ''), $cpages>1);
		$self = $i == $page;
		$replacements['{current}'] = $self ? $current : $current_;
		if($replacements['{link}'] = strtr($self ? $link_ : $link, $replacements))
			if($custom) $mask['{links}'] .= $replacements['{link}'];
			else $out[] = ($currentclass > 0 ? ($self ? '{current}' : '{current_}') : '').strtr($thing, $replacements);
	}

	$outlast = $outnext = $outgap = '';
	$replacements['{current}'] = $current_;
	if($loopEnd < $numberOfTabs && $range > 1 || isset($last)) {
		etc_pagination_link($replacements, $links, $pages, $numberOfTabs, $pgdefault, $pagebase, $pageurl, $fragment, '', $cpages>1);
		$replacements['{link}'] = $mask['{last}'] = strtr(isset($last) ? ($page < $numberOfTabs ? $last : $last_) : $link, $replacements);
		if(!$custom && $replacements['{link}']) $outlast = strtr($thing, $replacements);
		if($gap2 && $loopEnd < $numberOfTabs) {
//			if($custom) $mask['{+>}'] = $gap2; else $outgap = $gap2;
			$i = $loopEnd+$range+1-$skip1;
			if($scale > 1) {
				$n = pow($scale, floor(log($numberOfTabs, $scale)));
				$nt = ceil($numberOfTabs/$n)*$n;
				if($nt >= $scale + $i - 1) {
					$n = pow($scale, min(floor(log($nt - $i + 1, $scale)), ceil(log($i, $scale))));
					do {
						$j = intval($nt - floor(($nt - $i + 1)/$n)*$n);
						$n /= $scale;
					} while($j >= $numberOfTabs && $n >= 1);
					$i = $j;
				}
			}
			$i = min($range ? $numberOfTabs-1 : $numberOfTabs, $i);

			etc_pagination_link($replacements, $links, $pages, $i, $pgdefault, $pagebase, $pageurl, $fragment, 'next', $cpages>1);
			if($replacements['{link}'] = strtr($gap2, $replacements))
				if($custom) $mask['{+>}'] = $replacements['{link}']; else $outgap = strtr($thing, $replacements);

		}
	}

	if($next || $next_) {
		etc_pagination_link($replacements, $links, $pages, $page+1, $pgdefault, $pagebase, $pageurl, $fragment, 'next', $cpages>1);
		if($page >= $numberOfTabs) $replacements['{#}'] = $replacements['{*}'] = '';
		$replacements['{link}'] = $mask['{next}'] = strtr($page < $numberOfTabs ? $next : $next_, $replacements);
		if(!$custom && $replacements['{link}']) $outnext = strtr($thing, $replacements);
	}

	if($outgap) $out[] = $outgap;
	if($last) {
		if($outnext) $out[] = $outnext; if($outlast) $out[] = $outlast;
	} else {
		if($outlast) $out[] = $outlast; if($outnext) $out[] = $outnext;
	}

	if($atts) $atts = ' '.$atts;
	if($custom) $out = array(strtr($thing, $mask));
	if($reversenumberorder & 1) $out = array_reverse($out);
	$out = doWrap($out, $wraptag, $break, $class, '', $atts, '', $html_id);
	if($currentclass > 0) $out = str_replace(array("<$break>{current}", "<$break>{current_}"), array("<{$break}{$current}>", "<{$break}{$current_}>"), $out);
	return parse($out);
}

function etc_pagination_link(&$replacements, $links, $pages, $page, $pgdefault, $pagebase, $pageurl, $fragment, $rel, $custom) {
		if (isset($pages[$page-1])) if($custom) @list($replacements['{#}'], $replacements['{*}']) = explode('::', $pages[$page-1].'::'.$pages[$page-1]);
			else {$replacements['{*}'] = $pages[$page-1]; $replacements['{#}'] = $links[$page-1];}
		$replacements['{$}'] = $page;
//		if($reversenumberorder) $replacements['{$}'] = $numberOfTabs-$replacements['{$}']+1;
		$replacements['{href}'] = ($replacements['{$}'] == $pgdefault ? $pagebase : $pageurl.$replacements['{#}']).$fragment;
		$replacements['{rel}'] = $rel;
}


// -------------------------------------------------------------
	function etc_numpages($atts)
	{
		global $pretext, $prefs, $thispage, $etc_pagination, $etc_pagination_total;
		if(empty($atts) && isset($thispage)) {$etc_pagination_total = $etc_pagination['total'] = $thispage['total']; return empty($thispage['numPages']) ? 1 : $thispage['numPages'];}
		extract($pretext);
		if(empty($atts['table']) && !isset($atts['total'])) {
			$customFields = getCustomFields();
			$customlAtts = array_null(array_flip($customFields));
		} else $customFields = $customlAtts = array();

		//getting attributes
		extract(lAtts(array(
			'table'     => '',
			'total'     => null,
			'limit'     => 10,
			'pageby'    => '',
			'category'  => '',
			'section'   => '',
			'exclude'   => '',
			'include'   => '',
			'excerpted' => '',
			'author'    => '',
			'realname'  => '',
			'month'     => '',
			'keywords'  => '',
			'expired'   => $prefs['publish_expired_articles'],
			'id'        => '',
			'time'      => 'past',
			'status'    => '4',
			'offset'    => 0
		)+$customlAtts, $atts));

		if(!($pageby = intval(empty($pageby) ? $limit : $pageby))) return 0;
		$etc_pagination['pageby'] = $pageby;
		if(isset($total)) return ceil(intval($total)/$pageby);

		$where = array("1");

		//Building query parts
		$category  = join("','", doSlash(do_list($category)));
		if($category) $where[] = !$table ? "(Category1 IN ('".$category."') or Category2 IN ('".$category."'))" : "category IN ('".$category."')";
		if($author) $where[] = (!$table ? "AuthorID" : "author")." IN ('".join("','", doSlash(do_list($author)))."')";
		if($id) $where[] = "ID IN (".join(',', array_map('intval', do_list($id))).")";
		if($status && (!$table || $table == 'file')) $where[] = 'Status in('.implode(',', doSlash(do_list($status))).')';
		if ($realname) {
			$authorlist = safe_column('name', 'txp_users', "RealName IN ('". join("','", doArray(doSlash(do_list($realname)), 'urldecode')) ."')" );
			$where[] = (!$table ? "AuthorID" : "author")." IN ('".join("','", doSlash($authorlist))."')";
		}
		if(!$table) {
			if($section) $where[] = "Section IN ('".join("','", doSlash(do_list($section)))."')";
			if($month) $where[] = "Posted like '".doSlash($month)."%'";
			if($excerpted=='y' || $excerpted=='1') $where[] = "Excerpt !=''";
			switch ($time) {
				case 'past':
					$where[] = "Posted <= now()"; break;
				case 'future':
					$where[] = "Posted > now()"; break;
			}
			if (!$expired) {
				$where[] = "(now() <= Expires or Expires IS NULL)";
			}
			//Allow keywords for no-custom articles. That tagging mode, you know
			if ($keywords) {
				$keys = doSlash(do_list($keywords));
				foreach ($keys as $key) {
					$keyparts[] = "FIND_IN_SET('".$key."',Keywords)";
				}
				$where[] = "(" . join(' or ',$keyparts) . ")";
			}
		} else {
			if($include) $where[] = "name IN ('".join("','", doSlash(do_list($include)))."')";
			if($exclude) $where[] = "name NOT IN ('".join("','", doSlash(do_list($exclude)))."')";
		}

		$customq = '';
		if ($customFields) {
			foreach($customFields as $cField) {
				if (isset($atts[$cField]))
					$customPairs[$cField] = $atts[$cField];
			}
			if(!empty($customPairs)) {
				$customq = buildCustomSql($customFields,$customPairs);
			}
		}

//		$where = "1=1" . $statusq. $time. $search . $id . $category . $section . $excerpted . $month . $author . $keywords . $customq;
		$where = implode(' AND ', $where) . $customq;

		//paginate
		$grand_total = safe_count($table ? 'txp_'.$table : 'textpattern', $where);
		$etc_pagination_total = $etc_pagination['total'] = $grand_total - $offset;
		return ceil($etc_pagination['total']/$pageby);
	}

	function etc_offset($atts)
	{
		global $etc_pagination;
		//getting attributes
		extract(lAtts((empty($etc_pagination) ? array() : $etc_pagination) + array(
			'type'      => '',
			'pageby'    => '10',
			'pgcounter' => 'pg',
			'offset'    => '0'
		), $atts));

		if(!empty($etc_pagination)) extract($etc_pagination);
		$counter = isset($page) ? $page : urldecode(gps($pgcounter));
		$page = max(intval($counter), 1) + $offset;
		$max = isset($total) ? $total : $page*$pageby;
		switch($type) {
			case 'value' : return htmlspecialchars($counter, ENT_QUOTES);
			case 'page' : return $page;
			case 'start' : return min($max, ($page - 1)*$pageby + 1);
			case 'end' : return min($max, $page*$pageby);
			default : return ($page - 1)*$pageby;
		}
	}