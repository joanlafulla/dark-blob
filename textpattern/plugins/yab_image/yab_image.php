<?php
/**
 *
 * This plugin is released under the GNU General Public License Version 2 and above
 * Version 2: http://www.gnu.org/licenses/gpl-2.0.html
 * Version 3: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (class_exists('\Textpattern\Tag\Registry'))
{
	Txp::get('\Textpattern\Tag\Registry')
		->register('yab_image');
}

function yab_image($atts)
{
	global $img_dir;

	static $cache = array();

	extract(lAtts(array(
		'align'        => 'center',
		'class'        => '',
		'escape'       => '',
		'html_id'      => '',
		'id'           => '',
		'name'         => '',
		'style'        => '',
		'wraptag'      => '',
		'alt_caption'  => '',
		'alt_alt'      => '',
		'alt_title'    => '',
		'alt_as_title' => 0 // 0 or 1
	), $atts));

	if ($name)
	{
		if (isset($cache['n'][$name]))
		{
			$rs = $cache['n'][$name];
		}
		else
		{
			$name = doSlash($name);
			$rs = safe_row('*', 'txp_image', "name = '$name' limit 1");
			$cache['n'][$name] = $rs;
		}
	}
	elseif ($id)
	{
		if (isset($cache['i'][$id]))
		{
			$rs = $cache['i'][$id];
		}
		else
		{
			$id = (int) $id;
			$rs = safe_row('*', 'txp_image', "id = $id limit 1");
			$cache['i'][$id] = $rs;
		}
	}
	else
	{
		trigger_error(gTxt('unknown_image'));
		return;
	}

	if ($rs)
	{
		extract($rs);

		if ($escape == 'html')
		{
			$alt = htmlspecialchars($alt);
			$alt_alt = htmlspecialchars($alt_alt);
			$caption = htmlspecialchars($caption);
			$alt_caption = htmlspecialchars($alt_caption);
			$alt_title = htmlspecialchars($alt_title);
		}

		$img_title = '';
		if ($alt_title)
		{
			$img_title .= ' title = "'.$alt_title.'"';
		}
		else
		{
			if ($alt_as_title)
			{
				if ($alt_alt)
				{
					$img_alt .= ' title="'.$alt_alt.'"';
				}
				else
				{
					if ($alt)
					{
						$img_alt .= ' title="'.$alt.'"';
					}
				}
			}
			else
			{
				if ($alt_caption)
				{
					$img_title .= ' title = "'.$alt_caption.'"';
				}
				else
				{
					if ($caption)
					{
						$img_title .= ' title = "'.$caption.'"';
					}
				}
			}
		}

	$img_alt = '';
	if ($alt_alt)
	{
		$img_alt .= ' alt="'.$alt_alt.'"';
	}
	else
	{
		if ($alt)
		{
			$img_alt .= ' alt="'.$alt.'"';
		}
	}

		$out =
			'<img src="'.hu.$img_dir.'/'.$id.$ext.'" width="'.$w.'" height="'.$h.'"'.
				$img_alt.
				$img_title.
				(($html_id and !$wraptag) ? ' id="'.$html_id.'"' : '').
				(($class and !$wraptag) ? ' class="'.$class.'"' : '').
				($style ? ' style="'.$style.'"' : '').
		' />';

		if ($caption or $alt_caption)
		{
			if ($alt_caption)
			{
				$out .= doTag(
					$alt_caption,
					'small',
					'caption',
					' style="display:block;width:'.$w.'px;"',
					''
				);
			}
			else
			{
				$out .= doTag(
					$caption,
					'small',
					'caption',
					' style="display:block;width:'.$w.'px;"',
					''
				);
			}

			if ($align == 'left' or $align == 'right')
			{
				$out = doTag(
					$out,
					'span',
					'img-caption-'.$align,
					' style="float:'.$align.';"',
					''
				);
			}
			if ($align == 'center')
			{
				$out = doTag(
					$out,
					'span',
					'img-caption-'.$align,
					' style="display:block;text-align:'.$align.';"',
				''
				);
			}
		}

		return ($wraptag) ? doTag($out, $wraptag, $class, '', $html_id) : $out;
	}

	trigger_error(gTxt('unknown_image'));
}