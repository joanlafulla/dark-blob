<?php
/**
 * yab_review_rating
 *
 * A Textpattern CMS plugin.
 * A comment based rating system for articles.
 *
 * @author Tommy Schmucker
 * @link   http://www.yablo.de/
 * @link   http://tommyschmucker.de/
 * @date   2013-12-24
 *
 * This plugin is released under the GNU General Public License Version 2 and above
 * Version 2: http://www.gnu.org/licenses/gpl-2.0.html
 * Version 3: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (class_exists('\Textpattern\Tag\Registry'))
{
	Txp::get('\Textpattern\Tag\Registry')
		->register('yab_review_rating')
		->register('yab_review_rating_input')
		->register('yab_review_rating_average');
}


/**
 * Config function holder to avoid some globals
 * Can later be changed to receive config from database
 *
 * @param  string $name name of the config
 * @return string
 */
function yab_rr_config($name)
{
	$config = array(
		'min' => 0, // min value of the rating (0-255)
		'max' => 5  // max value of the rating (0-255)
	);

	return $config[$name];
}

// admin callbacks
if (@txpinterface == 'admin') {
	register_callback(
		'yab_rr_install',
		'plugin_lifecycle.yab_review_rating',
		'installed'
	);
	register_callback(
		'yab_rr_discuss_ui',
		'admin_side',
		'body_end'
	);
	register_callback(
		'yab_rr_discuss_save',
		'discuss',
		'discuss_save'
	);
}

// public callbacks
register_callback('yab_rr_save', 'comment.saved');
register_callback('yab_rr_check', 'comment.save');

/**
 * Textpattern tag
 * Display the rating average for a given article
 *
 * @param  array $atts Array of Textpattern tag attributes
 * @return string
 */
function yab_review_rating_average($atts)
{
	global $thisarticle;

	extract(
		lAtts(
			array(
				'id'             => '', // article ids (comma separated) or empty in article context
				'exclude'        => null, // exclude ratings from calculations (e.g. 0)
				'only_visible'   => 1, // show all or only visible comments/reviews
				'default'        => 'not yet rated', // default text on articles without rating
				'decimals'       => 1, // precision of the calculation
				'separator'      => '.', // decimal separator
				'round_to_half'  => '' // round to first half integer up or down or not at all (up|down|empty)
			), $atts
		)
	);

	$exclude_rating = '';
	$average        = $default;

	if ($id)
	{
		$id = do_list($id);
		$id = join("','", doSlash($id));
	}
	else
	{
		assert_article();
		$id = $thisarticle['thisid'];
	}
	$parentid = "parentid IN ('$id')";

	$visible = '';
	if ($only_visible)
	{
		$visible = "AND visible = 1";
	}

	if ($exclude !== null)
	{
		$exclude_rating = do_list($exclude);
		$exclude_rating = join("','", doSlash($exclude_rating));
		$exclude_rating = "AND yab_rr_rating NOT IN ('$exclude_rating')";
	}

	$rs = safe_rows(
		'yab_rr_rating',
		'txp_discuss',
		"$parentid $visible $exclude_rating"
	);

	if ($rs)
	{
		$count   = sizeof($rs);
		$sum     = array_map('yab_rr_get_array_column', $rs);
		$sum     = array_sum($sum);
		$average = $sum / $count;

		if ($round_to_half)
		{
			if ($round_to_half == 'down')
			{
				$average = floor($average * 2) / 2;
			}
			else
			{
				$average = ceil($average * 2) / 2;
			}
		}

		$average = number_format($average, $decimals, $separator, '');
	}

	return $average;
}

/**
 * Get the rating column of the safe_rows() array
 * Is used as array_map function to build a array_sum array
 *
 * @param  array $element Array
 * @return string         yab_rr_rating Column from param array
 */
function yab_rr_get_array_column($element)
{
	return $element['yab_rr_rating'];
}

/**
 * Save the rating
 * Adminside Textpattern callback function
 * Fired after discuss is saved
 *
 * @param  string $event Textpattern admin event
 @ @param  string $step  Textpattern admin step
 * @return boolean      true on success
 */
function yab_rr_discuss_save($event, $step)
{
	$discussid = doSlash(assert_int(ps('discussid')));
	$rating    = doSlash(intval(ps('yab_rr_rating')));

	$rs = safe_update(
		'txp_discuss',
		"yab_rr_rating = '$rating'",
		"discussid = '$discussid'"
	);

	if ($rs)
	{
		update_lastmod();
		return true;
	}
	return false;
}

/**
 * Show Rating Input field in discuss edit panel
 * Adminside Textpattern callback function
 * Fired at body_end in ui
 *
 * @return mixed string in Textpattern admin step 'discuss_edit' | false
 */
function yab_rr_discuss_ui()
{
	global $step;

	// be sure we are in discuss edit area
	if ($step != 'discuss_edit')
	{
		return false;
	}

	$discussid = gps('discussid');
	$rating    = safe_field('yab_rr_rating', 'txp_discuss', "discussid = $discussid");
	$label     = gTxt('yab_rating_label');
	$js        = <<<EOF
<script>
(function() {
	var yab_rr_js = '<div class="txp-form-field edit-comment-yab-rating"><div class="txp-form-field-label"><label for="yab_rr_rating">$label</label></div><div class="txp-form-field-value"><input type="text" value="$rating" name="yab_rr_rating" size="32" id="yab_rr_rating"></div></div>';
	$('.edit-comment-name', '#discuss_edit_form').after(yab_rr_js);
})();
</script>
EOF;

	echo $js;
}

/**
 * Textpattern tag
 * Display the rating
 *
 * @param  array $atts Array of Textpattern tag attributes
 * @return string
 */
function yab_review_rating($atts)
{
	global $thiscomment;

	extract(
		lAtts(
			array(
				'id'  => '', // id of the comment
				'char' => '' // type of input, if empty number is displayed
			), $atts
		)
	);

	// commentid is given, serve before all othe
	if ($id)
	{
		$discussid = (int) $id;
		$rating = safe_field('yab_rr_rating', 'txp_discuss', "discussid = $discussid");
	}
	else
	{
		// normal comment list
		if (isset($thiscomment['yab_rr_rating']))
		{
			$rating = $thiscomment['yab_rr_rating'];
		}
		// recent comments
		elseif (isset($thiscomment['discussid']))
		{
			$discussid = $thiscomment['discussid'];
			$rating    = safe_field('yab_rr_rating', 'txp_discuss', "discussid = $discussid");
		}
		// comment preview
		else
		{
			$rating = intval(ps('yab_rr_value'));
		}
	}

	if ($char)
	{
		$chars = '';
		for ($i = 0; $i < $rating; $i++)
		{
			$chars .= $char;
		}
		return $chars;
	}

	return $rating;
}

/**
 * Check rating value and
 * Public callback function
 * Fired after comment is send
 *
 * @return void Die on invalid value
 */
function yab_rr_check()
{
	$min    = yab_rr_config('min');
	$max    = yab_rr_config('max');
	$rating = intval(ps('yab_rr_value'));

	if ($rating < $min or $rating > $max)
	{
		txp_die(
			'Unable to record the comment. No valid rating value.',
			'412 Precondition failed');
	}
}

/**
 * Save the rating after the comment is saved
 * Public callback function
 * Fired after comment is saved
 *
 * @param  string  $event   public Textpattern event
 * @param  string  $step    public Textpattern step
 * @param  array   $comment array of name-value pairs of saved comment
 * @return boolean true on success
 */
function yab_rr_save($event, $step, $comment)
{
	$id     = $comment['commentid'];
	$rating = doSlash(intval(ps('yab_rr_value')));

	$rs = safe_update(
		'txp_discuss',
		"yab_rr_rating = '$rating'",
		"discussid = '$id'"
	);

	if ($rs)
	{
		return true;
	}
	return false;
}

/**
 * Textpattern tag
 * Display the rating input in the comment form
 *
 * @param  array $atts Array of Textpattern tag attributes
 * @return string
 */
function yab_review_rating_input($atts)
{
	extract(
		lAtts(
			array(
				'type'    => 'text', // select, text, radio, number, range
				'html_id' => '', // HTML id to apply the item attribute value
				'class'   => 'yab-rr-review', // HTML class to apply the item attribute value
				'break'   => 'br', // br or empty
				'reverse' => 0, // reverse order for radio and select types
				'default' => '' // preselected rating value
			), $atts
		)
	);

	$selector_attributes = '';
	$min                 = yab_rr_config('min');
	$max                 = yab_rr_config('max');

	if (!$default)
	{
		$default = $min;
	}

	// show selected value on comment preview
	if (ps('preview'))
	{
		$default = intval(ps('yab_rr_value'));
	}

	$value_attribute = ' value="'.$default.'"';

	if ($class)
	{
		$selector_attributes .= ' class="'.$class.'"';
	}

	if ($html_id)
	{
		$selector_attributes .= ' id="'.$html_id.'"';
	}

	switch ($type)
	{
		case 'select':
			$options = array();
			for ($i = $min; $i <= $max; $i++)
			{
				$selected = '';
				if ($i == $default)
				{
					$selected = ' selected="selected"';
				}
				$options[] = '<option value="'.$i.'"'.$selected.'>'.$i.'</option>';
			}
			if ($reverse)
			{
				$options = array_reverse($options);
			}
				$out = doWrap(
					$options,
					'select',
					'',
					'',
					'',
					' name="yab_rr_value"'.$selector_attributes
				);
			break;
		case 'radio':
			$radios = array();
			for ($i = $min; $i <= $max; $i++)
			{
				$checked = '';
				if ($i == $default)
				{
					$checked = ' checked="checked"';
				}
				$radios[] = '<label for="yab-rr-'.$i.'">'.$i.'</label>'
					.'<input  name="yab_rr_value" type="'.$type.'"'
					.$checked
					.' value="'.$i.'"'
					.' id="yab-rr-'.$i.'"'
				.' />';
			}
			if ($reverse)
			{
				$radios = array_reverse($radios);
			}
			$out = doWrap($radios, '', $break);
			break;
		case 'text':
			$out = '<input name="yab_rr_value" type="'.$type.'"'
				.$selector_attributes
				.$value_attribute
			.' />';
			break;
		case 'number':
		case 'range':
			$out = '<input name="yab_rr_value"  type="'.$type.'"'
				.$selector_attributes
				.$value_attribute
				.' min="'.$min.'" max="'.$max.'"'
			.' />';
			break;
		default:
			$out = '';
			break;
	}

	return $out;
}

/**
 * Install the rating column inside txp_discuss
 * It is an unsigned tinyint type so 0-255 are valid values
 *
 * @return void
 */
function yab_rr_install()
{
	$columns = getRows('show columns from '.safe_pfx('txp_discuss'));
	foreach($columns as $column)
	{
		if ($column['Field'] == 'yab_rr_rating')
		{
			return;
		}
	}
	safe_query(
		"ALTER TABLE "
		.safe_pfx("txp_discuss")
		." ADD yab_rr_rating TINYINT UNSIGNED NOT NULL DEFAULT '0';"
	);
}