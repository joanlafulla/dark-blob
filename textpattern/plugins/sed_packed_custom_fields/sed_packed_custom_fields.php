<?php

// ================== IMMEDIATE CODE & DECLARATIONS FOLLOW ===================
//
//	This plugin requires the services of my plugin library to allow it to pull apart packed variables from the custom fields.
//
@require_plugin('sed_plugin_library');

// ================== PRIVATE FUNCTIONS FOLLOW ===================

function _sed_parse_section_vars( $vars ) {
	$result = array();

	if( is_array($vars) and count($vars) ) {
		foreach( $vars as $k=>$v )
			$result[$k] = parse($v);
		}

	return $result;
	}

function _sed_pcf_txp_fn($atts) {
	//
	//	Generic callback switch. Takes the array it builds from the named section of a custom field and calls a function with the
	// array as an argument. Useful for calling back into the TXP core.
	//
	global $thisarticle;
	$permitted = array( 'email', 'image', 'thumbnail' );

	extract(lAtts(array(
		'txp_fn'	=> '',
		'custom'	=> '',
		'section'	=> '',
		'parse'		=> true,
		'default'	=> '',
	),$atts));

	if( !empty($txp_fn) and empty($section) )
		$section = $txp_fn;

	$result = $default;
	$vars = @$thisarticle[$custom];
	if	(
		!empty($txp_fn) and
		in_array($txp_fn, $permitted) and
		function_exists($txp_fn) and
		!empty($vars) and
		!empty($section)
		) {
		if( 'none' === $section )
			$vars = sed_lib_extract_name_value_pairs( $vars );
		else
			$vars = sed_lib_extract_packed_variable_section( $section , $vars );
		if( is_array( $vars ) ) {
			if( $parse )
				$vars = _sed_parse_section_vars( $vars );
			$result = @$txp_fn( $vars );
			}
		}
	return $result;
	}

// ================== CLIENT-SIDE TAGS FOLLOW ===================

function sed_pcf_get_value( $atts ) {
	//
	//	Returns the value of the named variable in the named section of the named custom field (if any) else returns the default value (NULL).
	//
	global $thisarticle;

	extract(lAtts(array(
		'custom'	=> '',
		'section'	=> '',
		'variable'  => '',
		'default'	=> NULL,
	),$atts));

	$result = $default;
	$vars = @$thisarticle[$custom];
	if( !empty( $vars ) and !empty($section) and !empty($variable) ) {
		if( 'none' === $section )
			$vars = sed_lib_extract_name_value_pairs( $vars );
		else
			$vars = sed_lib_extract_packed_variable_section( $section , $vars );
		if( is_array( $vars ) ) {
			$result = @$vars[$variable];
			}
		}
	return $result;
	}

function sed_pcf_if_value( $atts , $thing='' ) {
	//
	//	Tests to see if there is a value to the named variable in the named section of the named custom field.
	//
	extract(lAtts(array(
		'custom'	=> '',
		'section'	=> '',
		'variable'  => '',
		'val' => NULL,
	),$atts));

	$value = sed_pcf_get_value( $atts );

	if( $val !== NULL )
		$cond = (@$value == $val);
	else
		$cond = (isset($value) and !empty($value));

	return parse(EvalElse($thing, $cond));
	}

function sed_pcf_if_field_section( $atts , $thing='' ) {
	//
	//	Tests to see if there is a named section of the named custom field.
	//
	global $thisarticle;

	extract(lAtts(array(
		'custom'	=> '',
		'section'	=> '',
	),$atts));

	$cond = false;
	$vars = @$thisarticle[$custom];
	if( !empty( $vars ) and !empty($section) ) {
		$vars = sed_lib_extract_packed_variable_section( $section , $vars );
		$cond = is_array( $vars );
		}

	return parse(EvalElse($thing, $cond));
	}

function sed_pcf_image( $atts ) {
	$atts['txp_fn'] = 'image';
	return _sed_pcf_txp_fn( $atts );
	}

function sed_pcf_thumbnail( $atts ) {
	$atts['txp_fn'] = 'thumbnail';
	return _sed_pcf_txp_fn( $atts );
	}

function sed_pcf_email( $atts ) {
	$atts['txp_fn'] = 'email';
	return _sed_pcf_txp_fn( $atts );
	}

function sed_pcf_for_each_value( $atts , $thing )
	{
	global $thisarticle;

	assert_article();

	$def_custom_name = 'custom1';
	extract( $merged = lAtts( array(
		'debug'		=> 0,
		'name'	=> $def_custom_name,
		'form'  	=> '',
		'label'  	=> '',
		'labeltag'  => '',
		'wraptag'	=> 'ul',
		'break'		=> 'li',
		'class'		=> '',
		), $atts) );

	if( $debug ) echo dmp( $merged );

	$field = @$thisarticle[$name];
	if( empty( $field ) )	# Nothing to do -- the field is empty.
		{
		if( $debug ) echo "Returning early - nothing to do in CF[$name].";
		return '';
		}

	if( empty( $class ) )
		$class = $name;

	if( !empty( $form ) )		# grab the form (if any)
		$thing = fetch_form($form);

	if( empty( $thing ) )		# if no form, and no enclosed thing, use built-in formula...
		$thing = '{value}';

	$out = array();
	$field = do_list( $field );
	foreach( $field as $value )
		{
		$out[] = parse( str_replace( '{value}' , $value , $thing ) );
		}

	return doLabel($label, $labeltag).doWrap($out, $wraptag, $break, $class);
	}
