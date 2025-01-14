<?php

function daz_clickable_keywords($atts, $key='') {
	global $thisarticle;
		extract(lAtts(array(
			'wraptag'  => '',
			'class'  => '',
			'break'  => '',
			'breakclass' => '',
			'keyword'  => 'keyword',
			'section'  => 'keywords'
		),$atts));

		$wraptag = trim($wraptag);
		$break = trim($break);
		$class = trim($class);
		$breakclass = trim($breakclass);
		$keyword = trim($keyword);
		$section = trim($section);

		$keywords = $thisarticle['keywords'];

		if($break) {
			$breakend = '</' . $break . '>';
			$break = '<' . $break;

				if($breakclass) {
					$break = $break . ' class = "' . $breakclass . '" ';
				}

			$break = $break . '>';

		} else {
			$break = '';
			$breakend = '';
		}

		if($wraptag) {
			$wraptagend = '</' . $wraptag . '>';
			$wraptag = '<' . $wraptag;

				if($class) {
					$wraptag = $wraptag . ' class = "' . $class . '" ';
				}

			$wraptag = $wraptag . '>';

		} else {
			$wraptag = '';
			$wraptag = '';
		}


		if(isset($keywords)) {
			$key = $wraptag;

				$keyworks = explode(",", $keywords);
					foreach($keyworks as $keywork) {
						$keywork = trim($keywork);
						 $key = $key . $break .  '<a href="/' . $section . '/?'. $keyword. '=' . $keywork .'">' . $keywork . '</a>' . $breakend;
					}

			$key = $key . $wraptagend;
		}

	return $key;
}


if (txpinterface === 'public') {
	// Register public tags.
	if (class_exists('\Textpattern\Tag\Registry')) {
		Txp::get('\Textpattern\Tag\Registry')
			->register('daz_clickable_keywords');
	}
}
