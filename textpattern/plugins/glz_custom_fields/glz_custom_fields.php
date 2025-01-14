<?php


##################
#
#	glz_custom_fields for Textpattern
#	version 2.0 – jools-r
#	Original version: Gerhard Lazu
#
##################

##################
#
#   DATABASE FUNCTIONS – Standalone reusable DB functions
#
##################

/**
 * Adds a new custom field.
 *
 * @param  array $in Incoming array of form values (see below)
 * @param  bool $debug Dump query
 * @return bool FALSE on error
 * @see    glz_db_cf_save()
 *
 * Expects an incoming array as follows:
 * $in = array(
 *     'custom_set',
 *     'custom_field_number',
 *     'custom_set_name',
 *     'custom_set_name_old', **
 *     'custom_set_title', **
 *     'custom_set_instructions', **
 *     'custom_set_type',
 *     'custom_set_position',
 *     'value', **
 *     'save', **
 *     'add_new'
 * );
 * where ** are optional
 *
 * Do all content checks / validation / data completion / manipulation
 * before passing the array to this function
 */

function glz_db_cf_new($in, $debug = false)
{
    // Abort if wrong input format
    if (!is_array($in)) {
        if ($debug) { dmp('$in: wrong input format'); }
        return false;
    }

    // Extract incoming values
    extract($in);
    // Get current UI language
    $current_lang = get_pref('language_ui', TEXTPATTERN_DEFAULT_LANG);

    $ok = false;

    // Insert: table 'txp_prefs' – custom_field entry
    // Custom fields IDs 1-10 already exist (in-built)
    if ($custom_field_number > 10) {
        $ok = safe_insert(
            'txp_prefs',
            "name     = '{$custom_set}',
             val      = '{$custom_set_name}',
             type     = '1',
             event    = 'custom',
             html     = '{$custom_set_type}',
             position = '{$custom_set_position}'",
            $debug
        );
    } else {
        // Custom field IDs 1-10 (update entry)
        $ok = safe_update(
            'txp_prefs',
            "val      = '{$custom_set_name}',
             html     = '{$custom_set_type}',
             position = '{$custom_set_position}'",
             "name = '{$custom_set}'",
            $debug
        );
    }

    // Insert: table 'textpattern' – new 'custom_X' column
    // Custom fields IDs 1-10 already exist (in-built)
    if ($custom_field_number > 10) {
        // If cf type = textarea, column type + default must be different
        $column_type = ($custom_set_type == "textarea") ? "TEXT" : "VARCHAR(255)";
        $dflt =        ($custom_set_type == "textarea") ? ''     : "DEFAULT ''";
        $ok = safe_alter(
            'textpattern',
            "ADD custom_{$custom_field_number} {$column_type} NOT NULL {$dflt}",
            $debug
        );
    } else {
        // Custom field IDs 1-10: only update column type and default as necessary
        // Update: table 'textpattern' – column type and default if type changes
        // If cf type = textarea, column type + default must be different
        $column_type = ($custom_set_type == "textarea") ? "TEXT" : "VARCHAR(255)";
        $dflt =        ($custom_set_type == "textarea") ? ''     : "DEFAULT ''";
        $ok = safe_alter(
            'textpattern',
            "MODIFY custom_{$custom_field_number} {$column_type} NOT NULL {$dflt}",
            $debug
        );
    }

    // Insert: table 'txp_lang' – prefspane label
    // Custom fields IDs 1-10 already exist (in-built)
    if ($custom_field_number > 10) {
        $custom_set_preflabel = gTxt('custom_x_set', array('{number}' => $custom_field_number));
        $ok = safe_insert(
            'txp_lang',
            "lang    = '{$current_lang}',
             name    = 'custom_{$custom_field_number}_set',
             event   = 'prefs',
             owner   = '',
             data    = '{$custom_set_preflabel}',
             lastmod = now()",
            $debug
        );
    }

    // Insert: table 'txp_lang' – custom field label title
    if (!empty($custom_set_title)) {
        $custom_set_cf_name = glz_cf_langname($custom_set_name);
        $custom_set_cf_data = doSlash($custom_set_title);
        $ok = safe_insert(
            'txp_lang',
            "lang    = '{$current_lang}',
             name    = '{$custom_set_cf_name}',
             event   = 'glz_cf',
             owner   = 'glz_cf_labels',
             data    = '{$custom_set_cf_data}',
             lastmod = now()",
            $debug
        );
        $set_gTxt[$custom_set_cf_name] = $custom_set_cf_data;
    }

    // Insert: table 'txp_lang' – custom field instructions
    if (!empty($custom_set_instructions)) {
        $custom_set_instr_name = 'instructions_custom_'.$custom_field_number;
        $custom_set_instr_data = doSlash($custom_set_instructions);
        $ok = safe_insert(
            'txp_lang',
            "lang    = '{$current_lang}',
             name    = '{$custom_set_instr_name}',
             event   = 'glz_cf',
             owner   = 'glz_cf_labels',
             data    = '{$custom_set_instr_data}',
             lastmod = now()",
            $debug
        );
        $set_gTxt[$custom_set_instr_name] = $custom_set_instr_data;
    }

    // Insert: table 'custom_fields' – multiple custom field values
    // 1. break textarea entries into array removing blanks and duplicates
    $cf_values = array_unique(array_filter(explode("\r\n", $value), 'glz_array_empty_values'));
    // 2. fashion insert statement from array
    if (is_array($cf_values) && !empty($cf_values)) {
        $insert = '';
        foreach ($cf_values as $key => $value) {
            // Skip empty values
            if (!empty($value)) {
                // Escape special chars before inserting into database
                $value = doSlash(trim($value));
                // Build insert row
                $insert .= "('{$custom_set}','{$value}'), ";
            }
        }
        // Trim final comma and space from insert statement
        $insert = rtrim($insert, ', ');
        // 3. do insert query
        $ok = safe_query("
            INSERT INTO
                ".safe_pfx('custom_fields')." (`name`,`value`)
            VALUES
                {$insert}
            ",
            $debug
        );
    }

    // As the table UI doesn't include the new strings until a page refresh,
    // set the language strings in $set_gTxt (if they exist) in the current context
    // (true = amend/append to existing textpack)
    if (isset($set_gTxt)) {
        if ($debug) { dmp('$set_gTxt: '.$set_gTxt); }
        Txp::get('\Textpattern\L10n\Lang')->setPack($set_gTxt, true);
    }

    return $ok;
}


/**
 * Saves / updates an existing custom field in the DB.
 *
 * @param  array $in  Incoming array of form values (see below)
 * @param  bool  $debug  Dump query
 * @return bool  FALSE on error
 * @see    glz_db_cf_new()
 *
 * Expects an incoming array as follows:
 * $in = array(
 *     'custom_set',
 *     'custom_field_number',
 *     'custom_set_name',
 *     'custom_set_name_old',
 *     'custom_set_title', **
 *     'custom_set_instructions', **
 *     'custom_set_type',
 *     'custom_set_position',
 *     'value', **
 *     'save',
 *     'add_new' **
 * );
 * where ** are optional
 *
 * Do all content checks / validation / data completion / manipulation
 * before passing the array to this function
 */

function glz_db_cf_save($in, $debug = false)
{
    // Abort if wrong input format
    if (!is_array($in)) {
        if ($debug) { dmp('$in: wrong input format'); }
        return false;
    }

    // Extract incoming values
    extract($in);
    // Get current UI language
    $current_lang = get_pref('language_ui', TEXTPATTERN_DEFAULT_LANG);

    // Has the custom field been renamed
    $is_cf_renamed = ($custom_set_name <> $custom_set_name_old) ? true : false;

    $ok = false;

    // Update: table 'txp_prefs' – custom_field entry
    $ok = safe_update(
        'txp_prefs',
        "val      = '{$custom_set_name}',
         html     = '{$custom_set_type}',
         position = '{$custom_set_position}'",
        "name     = '{$custom_set}'", // WHERE
        $debug
    );

    // Update: table 'textpattern' – column type and default if type changes
    // If cf type = textarea, column type + default must be different
    $column_type = ($custom_set_type == "textarea") ? "TEXT" : "VARCHAR(255)";
    $dflt =        ($custom_set_type == "textarea") ? ''     : "DEFAULT ''";
    $ok = safe_alter(
        'textpattern',
        "MODIFY custom_{$custom_field_number} {$column_type} NOT NULL {$dflt}",
        $debug
    );

    // Update: table 'custom_fields' – values entries (textarea requires none)
    // For textareas we do not need to touch custom_fields table
    if ($custom_set_type != "textarea") {
        safe_delete(
            'custom_fields',
            "name ='{$custom_set}'",
            $debug
        );
        // Insert: table 'custom_fields' – multiple custom field values
        // 1. break textarea entries into array removing blanks and duplicates
        $cf_values = array_unique(array_filter(explode("\r\n", $value), 'glz_array_empty_values'));
        // 2. fashion insert statement from array
        if (is_array($cf_values) && !empty($cf_values)) {
            $insert = '';
            foreach ($cf_values as $key => $value) {
                // Skip empty values
                if (!empty($value)) {
                    // Escape special chars before inserting into database
                    $value = doSlash(trim($value));
                    // Build insert row
                    $insert .= "('{$custom_set}','{$value}'), ";
                }
            }
            // Trim final comma and space from insert statement
            $insert = rtrim($insert, ', ');
            // 3. do insert query
            $ok = safe_query("
                INSERT INTO
                    ".safe_pfx('custom_fields')." (`name`,`value`)
                VALUES
                    {$insert}
                ",
                $debug
            );
        }
    } // endif ($custom_set_type != "textarea")

    // Update: table 'txp_lang' – custom field label title
    $has_cf_title = (!empty($custom_set_title)) ? true : false;
    $custom_set_cf_langname = glz_cf_langname($custom_set_name);

    if ($is_cf_renamed) {
        $custom_set_cf_langname_old = glz_cf_langname($custom_set_name_old);
        // OK, cf is renamed. Do cfnames still match perchance?
        // such as when renaming spaces/dashes to underscores or uppercase to lowercase
        if ($custom_set_cf_langname <> $custom_set_cf_langname_old) {
            // Update name of all custom fields (if cf_langname has actually changed)
            $custom_set_cf_name_old = $custom_set_cf_langname_old;
            $custom_set_cf_data_old = null;
            $ok = safe_update(
                'txp_lang',
                "name = '{$custom_set_cf_langname}'",
                "name = '{$custom_set_cf_langname_old}'",
                $debug
            );
            $set_gTxt[$custom_set_cf_name_old] = $custom_set_cf_data_old;
        }
    }

    if ($has_cf_title) {
        // A) Custom field title is specified: update or insert
        $custom_set_cf_name = $custom_set_cf_langname;
        $custom_set_cf_data = doSlash($custom_set_title);
        $ok = safe_upsert(
            'txp_lang',
            "event   = 'glz_cf',
             owner   = 'glz_cf_labels',
             data    = '{$custom_set_cf_data}',
             lastmod = now()",
             array('name' => $custom_set_cf_name, 'lang' => $current_lang),
            $debug
        );
        $set_gTxt[$custom_set_cf_name] = $custom_set_cf_data;
    } else {
        // B) Custom field title not specified (or blanked)
        $custom_set_cf_name = $custom_set_cf_langname;
        $custom_set_cf_data = null;
        // Only delete it if it actually exists
        $gtxt_data = glz_cf_gtxt($custom_set_name);
        if (!empty($gtxt_data)) {
            $ok = safe_delete(
                'txp_lang',
                "name = '{$custom_set_cf_name}' AND lang = '{$current_lang}'",
                $debug
            );
        }
        $set_gTxt[$custom_set_cf_name] = $custom_set_cf_data;
    }

    // Update: table 'txp_lang' – custom field instructions

    // A) If instructions string exists, update existing or insert new entry, or…
    if (!empty($custom_set_instructions)) {
        $custom_set_instr_name = 'instructions_custom_'.$custom_field_number;
        $custom_set_instr_data = doSlash($custom_set_instructions);
        $ok = safe_upsert(
            'txp_lang',
            "event   = 'glz_cf',
             owner   = 'glz_cf_labels',
             data    = '{$custom_set_instr_data}',
             lastmod = now()",
             array('name' => $custom_set_instr_name, 'lang' => $current_lang),
            $debug
        );
        $set_gTxt[$custom_set_instr_name] = $custom_set_instr_data;

    // B) If instructions string is empty but previously existed, remove old entry
    } elseif (glz_cf_gtxt('', $custom_field_number) != '') {
        $custom_set_instr_name = 'instructions_custom_'.$custom_field_number;
        $custom_set_instr_data = null;
        // Only delete it if it actually exists
        $gtxt_data = glz_cf_gtxt('',$custom_field_number);
        if (!empty($gtxt_data)) {
            $ok = safe_delete(
                'txp_lang',
                "name = '{$custom_set_instr_name}' AND lang = '{$current_lang}'",
                $debug
            );
        }
        $set_gTxt[$custom_set_instr_name] = $custom_set_instr_data;
    }

    // As the table UI doesn't include the new strings until a page refresh,
    // set the language strings in $set_gTxt (if they exist) in the current context
    // (true = amend/append to existing textpack)
    if (isset($set_gTxt)) {
        if ($debug) { dmp('$set_gTxt: '.$set_gTxt); }
        Txp::get('\Textpattern\L10n\Lang')->setPack($set_gTxt, true);
    }

    return $ok;
}


/**
 * Resets a custom field.
 *
 * Passes through to glz_db_cf_delete()
 * with reset flag set to true
 *
 * @param  string $id Custom field ID#
 * @param  bool $debug Dump query
 * @return bool FALSE on error
 * @see    glz_db_cf_delete()
 *
 * Do all checks on $id (if integer / if exists)
 * before passing $id to this function
 */

function glz_db_cf_reset($id, $debug = false)
{
    return glz_db_cf_delete($id, $reset = true, $debug);
}


/**
 * Deletes or resets a custom field.
 *
 * ID#s 1-10 are always reset, not deleted
 *
 * @param  string $id Custom field ID#
 * @param  bool $reset Reset rather than delete
 * @param  bool $debug Dump query
 * @return bool FALSE on error
 * @see    glz_db_cf_reset()
 *
 * Do all checks on $id (if integer / if exists)
 * before passing $id to this function
 */

function glz_db_cf_delete($id, $reset = false, $debug = false)
{
    // Custom fields 1-10 are in-built -> only reset
    if ($id <= 10) {
        $reset = true;
    }

    // Retrieve this custom_field values
    $custom_set = glz_db_get_custom_set($id);

    $ok = false;

    // --- COMMON DELETE AND RESET STEPS

    // Delete: table 'txp_lang' – custom field label title
    if (!empty($custom_set['title'])) {
        $ok = safe_delete(
            'txp_lang',
            "name = '".glz_cf_langname($custom_set['name'])."'",
            $debug
        );
    }

    // Delete: table 'txp_lang' – custom field instructions
    if (!empty($custom_set['instructions'])) {
        $ok = safe_delete(
            'txp_lang',
            "name = 'instructions_custom_".$custom_set['id']."'",
            $debug
        );
    }

    // Delete: table 'custom_fields' – custom field multiple values settings
    $cf_row = safe_row('*', 'custom_fields', "name = '".$custom_set['custom_set']."'", $debug);
    if ($cf_row) {
        $ok = safe_delete(
            'custom_fields',
            "name = '".$custom_set['custom_set']."'",
            $debug
        );
    }

    if ($reset) {  // --- RESET ONLY STEPS

        // Reset: table 'txp_prefs' – reset to standard custom field settings
        $ok = safe_update(
            'txp_prefs',
            "val = '',
             html = 'text_input'",
            "name = '".$custom_set['custom_set']."'",
            $debug
        );

        // Reset: table 'textpattern' – empty custom field article data
        $ok = safe_update(
            'textpattern',
            "custom_".$custom_set['id']." = ''",
            "1 = 1",
            $debug
        );

        // Reset: table 'textpattern' – reset custom_X column type to VARCHAR
        $ok = safe_alter(
            'textpattern',
            "MODIFY custom_".$custom_set['id']." VARCHAR(255) NOT NULL DEFAULT ''",
            $debug
        );

    } else {  // --- DELETE ONLY STEPS

        // Delete: table 'txp_lang' – prefspane label
        $ok = safe_delete(
            'txp_lang',
            "name = '".$custom_set['custom_set']."'",
            $debug
        );

        // Delete: table 'txp_prefs' – custom field entry
        $ok = safe_delete(
            'txp_prefs',
            "name = '".$custom_set['custom_set']."'",
            $debug
        );

        // Delete: table 'textpattern' – custom field article data
        $ok = safe_alter(
            'textpattern',
            "DROP `custom_".$custom_set['id']."`",
            $debug
        );
    }

    return $ok;
}


function glz_db_get_all_custom_sets()
{
    $all_custom_sets = safe_rows(
        "`name` AS custom_set,
         `val` AS name,
         `position`,
         `html` AS type",
        'txp_prefs',
        "`event` = 'custom' ORDER BY `position`"
    );

    foreach ($all_custom_sets as $custom_set) {
        $custom_set['id'] = glz_custom_digit($custom_set['custom_set']);
        $custom_set['title'] = glz_cf_gtxt($custom_set['name']);
        $custom_set['instructions'] = glz_cf_gtxt('', $custom_set['id']);

        $out[$custom_set['custom_set']] = array(
            'id'            => $custom_set['id'],
            'name'          => $custom_set['name'],
            'title'         => $custom_set['title'],
            'instructions'  => $custom_set['instructions'],
            'position'      => $custom_set['position'],
            'type'          => $custom_set['type']
        );

    }
    return $out;
}


function glz_db_get_custom_set($id)
{
    if (!intval($id)) {
        return false;
    }
        $custom_set = safe_row(
            "name AS custom_set,
             val AS name,
             position,
             html AS type",
            'txp_prefs',
            "name = 'custom_".doSlash($id)."_set'"
        );

        $custom_set['id'] = glz_custom_digit($custom_set['custom_set']);
        $custom_set['title'] = glz_cf_gtxt($custom_set['name']);
        $custom_set['instructions'] = glz_cf_gtxt('', $custom_set['id']);

        return $custom_set;
}


function glz_db_get_custom_field_values($name, $extra)
{
    global $prefs;

    if (is_array($extra)) {
        extract($extra);

        if (!empty($name)) {

            switch ($prefs['glz_cf_values_ordering']) {
                case "ascending":
                    $orderby = "value ASC";
                    break;
                case "descending":
                    $orderby = "value DESC";
                    break;
                default:
                    $orderby = "id ASC";
            }

            $arr_values = getThings("
                SELECT
                    `value`
                FROM
                    ".safe_pfx('custom_fields')."
                WHERE
                    `name` = '{$name}'
                ORDER BY
                    {$orderby}
            ");

            if (count($arr_values) > 0) {
                // Decode all special characters e.g. ", & etc. and use them for keys
                foreach ($arr_values as $key => $value) {
                    $arr_values_formatted[glz_clean_default(htmlspecialchars($value))] = stripslashes($value);
                }
                return $arr_values_formatted;
            }
        }
    } else {
        trigger_error(gTxt('glz_cf_not_specified', array('{what}' => "extra attributes")), E_ERROR);
    }
}


function glz_db_get_all_existing_cf_values($name, $extra)
{
    if (is_array($extra)) {
        extract(lAtts(array(
            'custom_set_name'   => "",
            'status'            => 4
        ), $extra));

        // On occasions (e.g. initial migration) we may need to check the custom field values for all articles
        $status_condition = ($status == 0) ? "<> ''" : "= '$status'";

        if (!empty($name)) {
            $arr_values = getThings("
                SELECT DISTINCT
                    `$name`
                FROM
                    ".safe_pfx('textpattern')."
                WHERE
                    `Status` $status_condition
                AND
                    `$name` <> ''
                ORDER BY
                    `$name`
            ");

            // Trim all values
            foreach ($arr_values as $key => $value) {
                $arr_values[$key] = trim($value);
            }

            // DEBUG
            // dmp($arr_values);

            // Temporary string of array values for checking for instances of | and -.
            $values_check = join('::', $arr_values);

            // DEBUG
            // dmp($values_check);

            // Are any values multiple ones (=‘|’)?
            if (strstr($values_check, '|')) {
                // Initialize $out
                $out = array();
                // Put all values in an array
                foreach ($arr_values as $value) {
                    $arr_values = explode('|', $value);
                    $out = array_merge($out, $arr_values);
                }
                // Keep only the unique ones
                $out = array_unique($out);
                // Keys and values need to be the same
                $out = array_combine($out, $out);
            } else {
                // Keys and values need to be the same
                $out = array_combine($arr_values, $arr_values);
            }

            // Calling stripslashes on all array values
            array_map('glz_array_stripslashes', $out);

            return $out;
        }
    } else {
        trigger_error(gTxt('glz_cf_not_specified', array('{what}' => "extra attributes")), E_ERROR);
    }
}


function glz_db_get_article_custom_fields($name, $extra)
{
    if (is_array($extra)) {
        // See what custom fields we need to query for
        foreach ($extra as $custom => $custom_set) {
            $select[] = glz_custom_number($custom);
        }

        // Prepare the select elements
        $select = implode(',', $select);

        $arr_article_customs = safe_row(
            $select,
            'textpattern',
            "`ID`='".$name."'"
        );
        return $arr_article_customs;
    } else {
        trigger_error(gTxt('glz_cf_not_specified', array('{what}' => "extra attributes")), E_ERROR);
    }
}


// -------------------------------------------------------------
// Goes through all custom sets, returns the first one which is not being used
// Returns next free id#.
function glz_next_empty_custom()
{
    // get current custom fields in 'txp_prefs' sorted by number
    // LENGTH = 1-9 first, 10+ afterwards (not 1, 10, 11 ... 2, 3, ...)
    $rs = safe_rows(
        "name, val",
        'txp_prefs',
        "event = 'custom' ORDER BY LENGTH(name), name"
    );
    $counter = 1;
    foreach ($rs as $value) {
        $cf_number = filter_var($value['name'], FILTER_SANITIZE_NUMBER_INT);
        // Stop at first empty value in IDs 1-10
        if ($value['val'] == '') {
            $result = $cf_number;
            break;
        }
        // Stop at first empty number > 10
        if ($cf_number != $counter) {
            $result = $counter;
            break;
        }

        $counter++;
    }
    // No empty IDs in 1-10 and no holes > 11 -> get next number
    if (!isset($result)) {
        $result = $counter;
    }
    return $result;
}


// -------------------------------------------------------------
// Check if one of the special custom fields exists (e.g. date-picker / time-picker)
function glz_check_custom_set_exists($name)
{
    if (!empty($name)) {
        return safe_field("name", 'txp_prefs', "html = '".$name."' AND name LIKE 'custom_%'");
    }
}


##################
#
#	glz_custom_fields for Textpattern
#	version 2.0 – jools-r
#	Original version: Gerhard Lazu
#
##################

##################
#
#   HELPERS – Helper functions: checks, sanitizers, preps
#
##################


// -------------------------------------------------------------
// The types our custom fields can take
function glz_custom_set_types()
{
    return array(
        'normal' => array(
            'text_input',
            'checkbox',
            'radio',
            'select',
            'multi-select',
            'textarea'
        ),
        'special' => array(
            'date-picker',
            'time-picker',
            'custom-script'
        )
    );
}


// -------------------------------------------------------------
// Outputs only custom fields that have been set, i.e. have a name assigned to them
function glz_check_custom_set($all_custom_sets, $step)
{
    $out = array();
    foreach ($all_custom_sets as $key => $custom_field) {
        if (!empty($custom_field['name'])) {
            if (($step == "body") && ($custom_field['type'] == "textarea")) {
                $out[$key] = $custom_field;
            } elseif (($step == "custom_fields") && ($custom_field['type'] != "textarea")) {
                $out[$key] = $custom_field;
            }
        }
    }
    return $out;
}


// -------------------------------------------------------------
// Converts all values into id safe ones [A-Za-z0-9-]
function glz_cf_idname($text)
{
    return str_replace("_", "-", glz_sanitize_for_cf($text));
}


// -------------------------------------------------------------
// Converts input into a gTxt-safe lang string 'cf_' prefix + [a-z0-9_]
function glz_cf_langname($text)
{
    return 'cf_'.glz_sanitize_for_cf($text);
}


// -------------------------------------------------------------
// Gets translated title or instruction string if one exists
// @name = custom_field_name
// @cf_number = $custom_field_number for instructions
// returns language string or nothing if none exists
function glz_cf_gtxt($name, $cf_number = null)
{
    // get language string
    if (!empty($cf_number)) {
        // still work if 'custom_X' or 'custom_X_set' is passed in as cf_number
        if (strstr($cf_number, 'custom_')) {
            $parts = explode("_", $cf_number);
            $cf_number = $parts[1];
        }
        $cf_name = 'instructions_custom_'.$cf_number;
    } else {
        $cf_name = glz_cf_langname($name);
    }
    $cf_gtxt = gTxt($cf_name);
    // retrieve gTxt value if it exists
    return ($cf_gtxt != $cf_name) ? $cf_gtxt : '';
}


// -------------------------------------------------------------
// Cleans strings for custom field names and cf_language_names
function glz_sanitize_for_cf($text, $lite = false)
{
    $text = trim($text);

    if ($lite) {
        // lite (legacy)
        // U&lc letters, numbers, spaces, dashes and underscores
        return preg_replace('/[^A-Za-z0-9\s\_\-]/', '', $text);
    } else {
        // strict
        // lowercase letters, numbers and single underscores; may not start with a number
        $patterns[0] = "/[\_\s\-]+/"; // space(s), dash(es), underscore(s)
        $replacements[0] = "_";
        $patterns[1] = "/[^a-z0-9\_]/"; // only a-z, 0-9 and underscore
        $replacements[1] = "";
        $patterns[2] = "/^\d+/"; // numbers at start of string
        $replacements[2] = "";

        return trim(preg_replace($patterns, $replacements, strtolower($text)), "_");
    }
}


// -------------------------------------------------------------
// Checks if a custom field contains invalid characters, starts with a number or has double underscores
function glz_is_valid_cf_name($text)
{
    global $msg;

    if (preg_match('/[^a-z0-9\_]/', $text)) {
        $msg = array(gTxt('glz_cf_name_invalid_chars', array('{custom_name_input}' => $text)), E_WARNING);
    } elseif (preg_match('/^\d+/', $text)) {
        $msg = array(gTxt('glz_cf_name_invalid_starts_with_number', array('{custom_name_input}' => $text)), E_WARNING);
    } elseif (preg_match('/\_{2,}/', $text)) {
        $msg = array(gTxt('glz_cf_name_invalid_double_underscores', array('{custom_name_input}' => $text)), E_WARNING);
    }
}

// -------------------------------------------------------------
// Checks if specified start date matches current date format
function glz_is_valid_start_date($date)
{
    global $prefs;
    $formats = array(
          "d/m/Y" => "dd/mm/yyyy",
          "m/d/Y" => "mm/dd/yyyy",
          "Y-m-d" => "yyyy-mm-dd",
          "d m y" => "dd mm yy",
          "d.m.Y" => "dd.mm.yyyy"
    );

    $datepicker_format = array_search($prefs['glz_cf_datepicker_format'], $formats);

    $d = DateTime::createFromFormat($datepicker_format, $date);
    return $d && $d->format($datepicker_format) == $date;
}


// -------------------------------------------------------------
// Accommodate relative urls in prefs
// $addhost = true prepends the hostname
function glz_relative_url($url, $addhost = false)
{
    $parsed_url = parse_url($url);
    if (empty($parsed_url['scheme']) && empty($parsed_url['hostname'])) {
        if ($addhost) {
            $hostname = (empty($txpcfg['admin_url']) ? hu : ahu);
        } else {
            $hostname = "/";
        }
        $url = $hostname.ltrim($url, '/');
    }
    return $url;
}


// -------------------------------------------------------------
// Removes empty values from arrays - used for new custom fields
function glz_array_empty_values($value)
{
    if (!empty($value)) {
        return $value;
    }
}


// -------------------------------------------------------------
// Strips slashes in arrays, used in conjuction with e.g. array_map
function glz_array_stripslashes(&$value)
{
    return stripslashes($value);
}


// -------------------------------------------------------------
// Removes { } from values which are marked as default
function glz_clean_default($value)
{
    $pattern = "/^.*\{(.*)\}.*/";
    return preg_replace($pattern, "$1", $value);
}


// -------------------------------------------------------------
// Calls glz_clean_default() in an array context
function glz_clean_default_array_values(&$value)
{
    $value = glz_clean_default($value);
}


// -------------------------------------------------------------
// Return our default value from all custom_field values
function glz_default_value($all_values)
{
    if (is_array($all_values)) {
        preg_match("/(\{.*\})/", join(" ", $all_values), $default);
        return ((!empty($default) && $default[0]) ? $default[0] : '');
    }
}


// -------------------------------------------------------------
// Custom_set without "_set" e.g. custom_1_set => custom_1
// or custom set formatted for IDs e.g. custom-1
function glz_custom_number($custom_set, $delimiter="_")
{
    // Trim "_set" from the end of the string
    $custom_field = substr($custom_set, 0, -4);

    // If a delimeter is specified custom_X to custom{delimeter}X
    if ($delimiter != "_") {
        $custom_field = str_replace("_", $delimiter, $custom_field);
    }
    return $custom_field;
}


// -------------------------------------------------------------
// Custom_set digit e.g. custom_1_set => 1
function glz_custom_digit($custom_set)
{
    $out = explode("_", $custom_set);
    // $out[0] will always be 'custom'
    return $out[1]; // so take $out[1]
}


// -------------------------------------------------------------
// Returns the custom_X_set from a custom set name e.g. "rating" gives us custom_1_set
function glz_get_custom_set($value)
{
    $result = safe_field(
        "name",
        'txp_prefs',
        "event = 'custom' AND val = '".doSlash(val)."'"
    );
    if (!$result) {
        // No result -> return error message
        trigger_error(gTxt('glz_cf_doesnt_exist', array('{custom_set_name}' => $value)), E_USER_WARNING);
        return false;
    }
    return true;
}


// -------------------------------------------------------------
// Get the article ID, even if it's newly saved
function glz_get_article_id()
{
    return (!empty($GLOBALS['ID']) ? $GLOBALS['ID'] : gps('ID'));
}


// -------------------------------------------------------------
// Is the custom field name already taken?
function glz_check_custom_set_name($custom_set_name, $custom_set)
{
    // Check that the name input by the user as well as its sanitized version don't already exist
    return safe_field(
        "name",
        'txp_prefs',
        "event = 'custom' AND val IN ('".doSlash($custom_set_name)."', '".glz_sanitize_for_cf($custom_set_name)."') AND name <> '".doSlash($custom_set)."'"
    );
}


// -------------------------------------------------------------
// Edit/delete buttons in custom_fields table require a form each
function glz_form_buttons($action, $value, $custom_set, $custom_set_name, $custom_set_type, $custom_set_position, $onsubmit='')
{
    $onsubmit = ($onsubmit) ? 'onsubmit="'.$onsubmit.'"' : '';

    // ui-icon (see admin hive styling)
    if ($action == "delete") {
        $ui_icon = "close";
    }
    if ($action == "reset") {
        $ui_icon = "trash";
    }
    if ($action == "add") {
        $ui_icon = "circlesmall-plus";
    }

    return
    '<form class="action-button" method="post" action="index.php" '.$onsubmit.'>
        <input name="custom_set" value="'.$custom_set.'" type="hidden" />
        <input name="custom_set_name" value="'.$custom_set_name.'" type="hidden" />
        <input name="custom_set_type" value="'.$custom_set_type.'" type="hidden" />
        <input name="custom_set_position" value="'.$custom_set_position.'" type="hidden" />
        <input name="event" value="glz_custom_fields" type="hidden" />
        <button name="'.$action.'" type="submit" value="'.$value.'"
                class="jquery-ui-button-icon-left ui-button ui-corner-all ui-widget">
            <span class="ui-button-icon ui-icon ui-icon-'.$ui_icon.'"></span>
            <span class="ui-button-icon-space"> </span>
            '.gTxt("glz_cf_action_".$action).'
        </button>
    </form>';
}


// TODO: Appears to be unused?!
// -------------------------------------------------------------
// Returns all sections/categories that are searchable
function glz_all_searchable_sections_categories($type)
{
    $type = (in_array($type, array('category', 'section')) ? $type : 'section');
    $condition = "";

    if ($type == "section") {
        $condition .= "searchable='1'";
    } else {
        $condition .= "name <> 'root' AND type='article'";
    }

    $result = safe_rows('*', "txp_{$type}", $condition);

    $out = array();
    foreach ($result as $value) {
        $out[$value['name']] = $value['title'];
    }

    return $out;
}


##################
#
#	glz_custom_fields for Textpattern
#	version 2.0 – jools-r
#	Original version: Gerhard Lazu
#
##################

##################
#
#   PUBLIC TAGS
#
##################


/*
 * Adds a title attribute to txp:custom_fields.
 * Set to title="1" to show title in the current language.
 * If no title is available, it returns nothing.
 */

// Divert txp:custom_field calls through glz_custom_field
if (class_exists('\Textpattern\Tag\Registry')) {
        Txp::get('\Textpattern\Tag\Registry')
            ->register('glz_custom_field', 'custom_field');
}

function glz_custom_field($atts, $thing = null)
{
    // Extract attributes as vars
    extract(lAtts(array(
        'title' => '0',
        'name' => ''
    ), $atts, false));  // false: suppress warnings

    // Unset otherwise non-existent attribute
    unset($atts['title']);

    // if $title is specified, divert to glz_cf_gtxt
    return $title ? glz_cf_gtxt($name) : custom_field($atts, $thing);
}


##################
#
#	glz_custom_fields for Textpattern
#	version 2.0 – jools-r
#	Original version: Gerhard Lazu
#
##################

##################
#
#   PREFERENCES PANE – Functions for preferences + preferences pane
#
##################


function glz_cf_prefs_install()
{
    global $prefs, $txpcfg;

    $position = 200;
    $base_url = (empty($txpcfg['admin_url'])) ? hu : ahu;
    $base_path = (empty($txpcfg['admin_url'])) ? $prefs['path_to_site'] : str_replace("public", "admin", $prefs['path_to_site']);

    // array: old_prefname => array('pref.subevent', 'html', 'default-value')
    $plugin_prefs = array(
        'values_ordering'        => array('', 'glz_prefs_orderby', 'custom'),
        'multiselect_size'       => array('', 'glz_text_input_small', '5'),
        'css_asset_url'          => array('', 'glz_url_input', $base_url.'plugins/glz_custom_fields'),
        'js_asset_url'           => array('', 'glz_url_input', $base_url.'plugins/glz_custom_fields'),
        'custom_scripts_path'    => array('', 'glz_url_input', $base_path.'/plugins/glz_custom_fields'),
        'use_sortable'           => array('', 'yesnoradio', 1),
        'permit_full_deinstall'  => array('', 'yesnoradio', 0),
        'datepicker_url'         => array('glz_cf_datepicker', 'glz_url_input', $base_url.'plugins/glz_custom_fields/jquery.datePicker'),
        'datepicker_format'      => array('glz_cf_datepicker', 'glz_prefs_datepicker_format', 'dd/mm/yyyy'),
        'datepicker_first_day'   => array('glz_cf_datepicker', 'glz_prefs_datepicker_firstday', 1),
        'datepicker_start_date'  => array('glz_cf_datepicker', 'glz_input_start_date', '01/01/2018'),
        'timepicker_url'         => array('glz_cf_timepicker', 'glz_url_input', $base_url.'plugins/glz_custom_fields/jquery.timePicker'),
        'timepicker_start_time'  => array('glz_cf_timepicker', 'glz_text_input_small', '00:00'),
        'timepicker_end_time'    => array('glz_cf_timepicker', 'glz_text_input_small', '23:30'),
        'timepicker_step'        => array('glz_cf_timepicker', 'glz_text_input_small', 30),
        'timepicker_show_24'     => array('glz_cf_timepicker', 'glz_prefs_timepicker_format', true)
    );

    foreach ($plugin_prefs as $name => $val) {
        if (get_pref($name, false) === false) {
            // If pref is new, create new pref with 'glz_cf_' prefix
            create_pref('glz_cf_'.$name, $val[2], 'glz_custom_f'.($val[0] ? '.'.$val[0] : ''), PREF_PLUGIN, $val[1], $position, '');
        } else {
            // If pref exists, add 'glz_cf_' prefix to name, reassign position and html type and set to type PREF_PLUGIN
            safe_update(
                'txp_prefs',
                "name = 'glz_cf_".$name."',
                 event = 'glz_custom_f".($val[0] ? ".".$val[0] : "")."',
                 html = '".$val[1]."',
                 type = ".PREF_PLUGIN.",
                 position = ".$position,
                "name = '".$name."'"
            );
        }
        $position++;
    }

    // Make some $prefs hidden (for safety and troubleshooting)
    foreach (array(
        'use_sortable',
        'permit_full_deinstall'
    ) as $name) {
        safe_update(
            'txp_prefs',
            "type = ".PREF_HIDDEN,
            "name = 'glz_cf_".$name."'"
        );
    }

    // Set 'migrated' pref to 'glz_cf_migrated' and to hidden (type = 2);
    if (get_pref('migrated')) {
        safe_update(
            'txp_prefs',
            "name = 'glz_cf_migrated',
             type = ".PREF_HIDDEN,
            "name = 'migrated'"
        );
    }

    // Remove no longer needed 'max_custom_fields' pref
    safe_delete(
        'txp_prefs',
        "name = 'max_custom_fields'"
    );
}


/**
 * Renders a HTML choice of GLZ value ordering.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */
function glz_prefs_orderby($name, $val)
{
    $vals = array(
        'ascending'   => gTxt('glz_cf_prefs_value_asc'),
        'descending'  => gTxt('glz_cf_prefs_value_desc'),
        'custom'      => gTxt('glz_cf_prefs_value_custom')
    );
    return selectInput($name, $vals, $val, '', '', $name);
}

/**
 * Renders a HTML choice of date formats.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */
function glz_prefs_datepicker_format($name, $val)
{
    $vals = array(
        "dd/mm/yyyy"  => gTxt('glz_cf_prefs_slash_dmyy'),
        "mm/dd/yyyy"  => gTxt('glz_cf_prefs_slash_mdyy'),
        "yyyy-mm-dd"  => gTxt('glz_cf_prefs_dash_yymd'),
        "dd mm yy"    => gTxt('glz_cf_prefs_space_dmy'),
        "dd.mm.yyyy"  => gTxt('glz_cf_prefs_dot_dmyy')
    );
    return selectInput($name, $vals, $val, '', '', $name);
}

/**
 * Renders a HTML choice of weekdays.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */
function glz_prefs_datepicker_firstday($name, $val)
{
    $vals = array(
        0             => gTxt('glz_cf_prefs_sunday'),
        1             => gTxt('glz_cf_prefs_monday'),
        2             => gTxt('glz_cf_prefs_tuesday'),
        3             => gTxt('glz_cf_prefs_wednesday'),
        4             => gTxt('glz_cf_prefs_thursday'),
        5             => gTxt('glz_cf_prefs_friday'),
        6             => gTxt('glz_cf_prefs_saturday')
    );
    return selectInput($name, $vals, $val, '', '', $name);
}

/**
 * Renders a HTML choice of time formats.
 *
 * @param  string $name HTML name and id of the widget
 * @param  string $val  Initial (or current) selected item
 * @return string HTML
 */
function glz_prefs_timepicker_format($name, $val)
{
    $vals = array(
        'true'        => gTxt('glz_cf_prefs_24_hours'),
        'false'       => gTxt('glz_cf_prefs_12_hours')
    );
    return selectInput($name, $vals, $val, '', '', $name);
}


/**
 * Renders a small-width HTML &lt;input&gt; element.
 * Checks if start date matches current datepicker date format
 *
 * @param  string $name HTML name and id of the text box
 * @param  string $val  Initial (or current) content of the text box
 * @return string HTML
 */
function glz_input_start_date($name, $val)
{
    $out = text_input($name, $val, INPUT_SMALL);
    // Output error notice if start date does not match date format
    if (!glz_is_valid_start_date($val)) {
        $out .= '<br><span class="error"><span class="ui-icon ui-icon-alert"></span> '.gTxt('glz_cf_datepicker_start_date_error').'</span>';
    }
    return $out;
}


/**
 * Renders a medium-width HTML &lt;input&gt; element.
 *
 * @param  string $name HTML name and id of the text box
 * @param  string $val  Initial (or current) content of the text box
 * @return string HTML
 */
function glz_text_input_medium($name, $val)
{
    return text_input($name, $val, INPUT_MEDIUM);
}

/**
 * Renders a small-width HTML &lt;input&gt; element.
 *
 * @param  string $name HTML name and id of the text box
 * @param  string $val  Initial (or current) content of the text box
 * @return string HTML
 */
function glz_text_input_small($name, $val)
{
    return text_input($name, $val, INPUT_SMALL);
}

/**
 * Renders a regular-width HTML &lt;input&gt; element for an URL with path check.
 *
 * @param  string $name HTML name and id of the text box
 * @param  string $val  Initial (or current) content of the text box
 * @return string HTML
 */
function glz_url_input($name, $val)
{
    global $use_minified;
    $min = ($use_minified === true) ? '.min' : '';
    $check_paths = (gps('check_paths') == "1") ? true : false;

    // Output regular-width text_input for url
    $out  = fInput('text', $name, $val, '', '', '', INPUT_REGULAR, '', $name);

    // Array of possible expected url inputs and corresponding files and error-msg-stubs
    // 'pref_name' => array('/targetfilename.ext', 'gTxt_folder (inserted into error msg)')
    // paths do not require a target filename, urls do.
    $glz_cf_url_inputs = array(
        'glz_cf_css_asset_url'       => array('/glz_custom_fields'.$min.'.css', 'glz_cf_css_folder'),
        'glz_cf_js_asset_url'        => array('/glz_custom_fields'.$min.'.js',  'glz_cf_js_folder'),
        'glz_cf_datepicker_url'      => array('/datePicker'.$min.'.js',         'glz_cf_datepicker_folder'),
        'glz_cf_timepicker_url'      => array('/timePicker'.$min.'.js',         'glz_cf_timepicker_folder'),
        'glz_cf_custom_scripts_path' => array('',                               'glz_cf_custom_folder')
    );
    // File url or path to test = prefs_val (=url/path) + targetfilename (first item in array)
    $glz_cf_url_to_test          = $val.$glz_cf_url_inputs[$name][0];
    // gTxt string ref for folder name for error message (second item in array)
    $glz_cf_url_input_error_stub = $glz_cf_url_inputs[$name][1];

    // See if url / path is readable. If not, produce error message
    if ($glz_cf_url_to_test && $check_paths == true) {
        // permit relative URLs but conduct url test with hostname
        if (strstr($name, 'url')) {
            $glz_cf_url_to_test = glz_relative_url($glz_cf_url_to_test, $addhost = true);
        }
        $url_error = (@fopen($glz_cf_url_to_test, "r")) ? '' : gTxt('glz_cf_folder_error', array('{folder}' => gTxt($glz_cf_url_input_error_stub) ));

        // Output error notice if one exists, else success notice
        $out .= (!empty($url_error)) ?
            '<br><span class="error"><span class="ui-icon ui-icon-alert"></span> '.$url_error.'</span>' :
            '<br><span class="success"><span class="ui-icon ui-icon-check"></span> '.gTxt('glz_cf_folder_success').'</span>';
    }

    return $out;
}


##################
#
#	glz_custom_fields for Textpattern
#	version 2.0 – jools-r
#	Original version: Gerhard Lazu
#
##################

##################
#
#   FIELD TYPES – Rendering of custom fields on 'Write' pane
#
##################


// -------------------------------------------------------------
// Formats the custom set output based on its type
function glz_format_custom_set_by_type($custom, $custom_id, $custom_set_type, $arr_custom_field_values, $custom_value = "", $default_value = "")
{
    if (is_array($arr_custom_field_values)) {
        $arr_custom_field_values = array_map('glz_array_stripslashes', $arr_custom_field_values);
    }

    switch ($custom_set_type) {
        // These are the normal custom fields
        case "text_input":
            return array(
                fInput("text", $custom, $custom_value, "edit", "", "", "22", "", $custom_id),
                ''
            );

        case "select":
            return array(
                glz_selectInput($custom, $custom_id, $arr_custom_field_values, $custom_value, $default_value),
                'glz-custom-select'
            );

        case "multi-select":
            return array(
                glz_selectInput($custom, $custom_id, $arr_custom_field_values, $custom_value, $default_value, 1),
                'glz-custom-multiselect'
            );

        case "checkbox":
            return array(
                glz_checkbox($custom, $arr_custom_field_values, $custom_value, $default_value),
                'glz-custom-checkbox'
            );

        case "radio":
            return array(
                glz_radio($custom, $custom_id, $arr_custom_field_values, $custom_value, $default_value),
                'glz-custom-radio'
            );

        case "textarea":
            return array(
                text_area($custom, 0, 0, $custom_value, $custom_id),
                'glz-custom-textarea'
            );

        // Here start the special custom fields, might need to refactor the return, starting to repeat itself
        case "date-picker":
            return array(
                fInput("text", $custom, $custom_value, "edit date-picker", "", "", "22", "", $custom_id),
                'glz-custom-datepicker'
            );

        case "time-picker":
            return array(
                fInput("text", $custom, $custom_value, "edit time-picker", "", "", "22", "", $custom_id),
                'glz-custom-timepicker'
            );

        case "custom-script":
            global $custom_scripts_path;
            return array(
                glz_custom_script($custom_scripts_path."/".reset($arr_custom_field_values), $custom, $custom_id, $custom_value),
                'glz-custom-script'
            );

        // A type has been passed that is not supported yet
        default:
            return array(
                gTxt('glz_cf_type_not_supported'),
                'glz-custom-unknown'
            );
    }
}


// -------------------------------------------------------------
// Had to duplicate the default selectInput() because trimming \t and \n didn't work + some other mods & multi-select
function glz_selectInput($name = '', $id = '', $arr_values = '', $custom_value = '', $default_value = '', $multi = '')
{
    if (is_array($arr_values)) {
        global $prefs;
        $out = array();

        // If there is no custom_value coming from the article, let's use our default one
        if (empty($custom_value)) {
            $custom_value = $default_value;
        }

        foreach ($arr_values as $key => $value) {
            $selected = glz_selected_checked('selected', $key, $custom_value, $default_value);
            $out[] = "<option value=\"$key\"{$selected}>$value</option>";
        }

        // We'll need the extra attributes as well as a name that will produce an array
        if ($multi) {
            $multi = ' multiple="multiple" size="'.$prefs['glz_cf_multiselect_size'].'"';
            $name .= "[]";
        }

        return "<select id=\"".glz_cf_idname($id)."\" name=\"$name\" class=\"list\"$multi>".
      ($default_value ? '' : "<option value=\"\"$selected>&nbsp;</option>").
      ($out ? join('', $out) : '').
      "</select>";
    } else {
        return gTxt('glz_cf_field_problems', array('{custom_set_name}' => $name));
    }
}


// -------------------------------------------------------------
// Had to duplicate the default checkbox() to keep the looping in here and check against existing value/s
function glz_checkbox($name = '', $arr_values = '', $custom_value = '', $default_value = '')
{
    if (is_array($arr_values)) {
        $out = array();

        // If there is no custom_value coming from the article, let's use our default one
        if (empty($custom_value)) {
            $custom_value = $default_value;
        }

        foreach ($arr_values as $key => $value) {
            $checked = glz_selected_checked('checked', $key, $custom_value);

            $out[] = "<div class=\"txp-form-checkbox glz-cf-".str_replace("_", "-", glz_cf_idname($key))."\"><input type=\"checkbox\" name=\"{$name}[]\" value=\"$key\" class=\"checkbox\" id=\"".glz_cf_idname($key)."\"{$checked} /> <label for=\"".glz_cf_idname($key)."\">$value</label></div>";
        }

        return join('', $out);
    } else {
        return gTxt('glz_cf_field_problems', array('{custom_set_name}' => $name));
    }
}


// -------------------------------------------------------------
// Had to duplicate the default radio() to keep the looping in here and check against existing value/s
function glz_radio($name = '', $id = '', $arr_values = '', $custom_value = '', $default_value = '')
{
    if (is_array($arr_values)) {
        $out = array();

        // If there is no custom_value coming from the article, let's use our default one
        if (empty($custom_value)) {
            $custom_value = $default_value;
        }

        foreach ($arr_values as $key => $value) {
            $checked = glz_selected_checked('checked', $key, $custom_value);
            $default = ($default_value == $key) ? ' '.'default' : '';

            $out[] = "<div class=\"txp-form-radio glz-cf-".str_replace("_", "-", glz_cf_idname($key))."\"><input type=\"radio\" name=\"$name\" value=\"$key\" class=\"radio{$default}\" id=\"{$id}_".glz_cf_idname($key)."\"{$checked} /> <label for=\"{$id}_".glz_cf_idname($key)."\">$value</label></div>";
        }

        return join('', $out);
    } else {
        return gTxt('glz_cf_field_problems', array('{custom_set_name}' => $name));
    }
}


// -------------------------------------------------------------
// Checking if this custom field has selected or checked values
function glz_selected_checked($range_unit, $value, $custom_value = '')
{
    // We're comparing against a key which is a "clean" value
    $custom_value = htmlspecialchars($custom_value);

    // Make an array if $custom_value contains multiple values
    if (strpos($custom_value, '|')) {
        $arr_custom_value = explode('|', $custom_value);
    }

    if (isset($arr_custom_value)) {
        $out = (in_array($value, $arr_custom_value)) ? " $range_unit=\"$range_unit\"" : "";
    } else {
        $out = ($value == $custom_value) ? " $range_unit=\"$range_unit\"" : "";
    }

    return $out;
}


//-------------------------------------------------------------
// Evals a PHP script and displays output right under the custom field label
function glz_custom_script($script, $custom, $custom_id, $custom_value)
{
    global $prefs;
    if (is_file($prefs['glz_cf_custom_scripts_path'].$script)) {
        include_once($prefs['glz_cf_custom_scripts_path'].$script);
        $custom_function = basename($script, ".php");
        if (is_callable($custom_function)) {
            return call_user_func_array($custom_function, array($custom, $custom_id, $custom_value));
        } else {
            return gTxt('glz_cf_not_callable', array('{function}' => $custom_function, '{file}' => $script));
        }
    } else {
        return '<span class="error"><span class="ui-icon ui-icon-alert"></span> '.gTxt('glz_cf_not_found', array('{file}' => $prefs['glz_cf_custom_scripts_path'].$script)).'</span>';
    }
}


##################
#
#	glz_custom_fields for Textpattern
#	version 2.0 – jools-r
#	Original version: Gerhard Lazu
#
##################

##################
#
#   CSS + JSS – For injecting into admin-side <head> area
#
##################


// -------------------------------------------------------------
// Contains minified output of glz_custom_fields.css file
// To update: copy the minified output of the actual css file into the function
function glz_custom_fields_head_css()
{
    $css = <<<'CSS'
.glz-cf-setup-switch{float:right}[dir=rtl] .glz-cf-setup-switch{float:left}#glz_custom_fields_container .txp-list-col-id{width:3em;text-align:center}#glz_custom_fields_container .txp-list-col-options,#glz_custom_fields_container .txp-list-col-position{width:5em}#glz_custom_fields_container .txp-list-col-title .cf-instructions.ui-icon{width:2em;height:17px;float:right;background-repeat:no-repeat;background-position:center 2px;opacity:.33;cursor:pointer}#glz_custom_fields_container .txp-list-col-title.disabled .cf-instructions{opacity:1!important;pointer-events:auto}#glz_custom_fields_container .txp-list-col-options{text-align:center}#glz_custom_fields_container .txp-list-col-options .ui-icon{width:4em;background-repeat:no-repeat;background-position:50%}#glz_custom_fields_container .txp-list-col-options .ui-icon:hover{-webkit-filter:brightness(0) saturate(100%) invert(17%) sepia(51%) saturate(5958%) hue-rotate(211deg) brightness(89%) contrast(101%);filter:brightness(0) saturate(100%) invert(17%) sepia(51%) saturate(5958%) hue-rotate(211deg) brightness(89%) contrast(101%)}#glz_custom_fields_container table.fixed-width{table-layout:fixed}#glz_custom_fields_container table.sortable .txp-list-col-sort{width:3em;text-align:center}#glz_custom_fields_container table.sortable .ui-sortable-handle{cursor:row-resize;text-align:center;opacity:.66}#glz_custom_fields_container table.sortable .txp-list-col-position{display:none}#glz_custom_fields_container .ui-sortable-helper,#glz_custom_fields_container .ui-sortable-placeholder{display:table}#add_edit_custom_field .hidden{display:none}@media screen and (min-width:47em){.txp-edit .txp-form-field .txp-form-field-instructions,.txp-tabs-vertical-group .txp-form-field-instructions{max-width:50%;padding-left:50%}}.check-path{float:right;font-size:.7em;font-weight:400}[dir=rtl] .check-path{float:left}.ui-tabs-nav .check-path{display:none}#prefs-glz_cf_css_asset_url,#prefs-glz_cf_js_asset_url{display:none}.glz-custom-field-reset.disabled:hover{text-decoration:none}.glz-custom-field-reset.disabled{cursor:default}.glz-custom-checkbox .txp-form-field-value label,.glz-custom-radio .txp-form-field-value label{cursor:pointer}
CSS;

    return '<style>'.n.$css.n.'</style>';
}


// -------------------------------------------------------------
// Contains minified output of glz_custom_fields.css file
// To update copy the minified output of the actual js file into the function
function glz_custom_fields_head_js()
{
    $js = <<<'JS'
$(function(){function e(){$(".glz-custom-radio").length>0&&$(".glz-custom-radio").each(function(){var e=$(this).find("input:first").attr("name");$(this).find("label:first").after(' <span class="small"><a href="#" class="glz-custom-field-reset" name="'+e+'">Reset</a></span>'),$("input:radio[name="+e+"]").is(":checked")||$(".glz-custom-field-reset[name="+e+"]").addClass("disabled")})}function t(){$glz_value_field.find("textarea#value").length&&(s.textarea_value=$glz_value_field.find("textarea#value").html(),$glz_value_field.find("textarea#value").remove()),$glz_value_field.find("input#value").length?0==$glz_value_field.find("input#value").prop("disabled")&&(s.path_value=$glz_value_field.find("input#value").attr("value")):$glz_value_field.find(".txp-form-field-value").prepend('<input type="text" id="value" name="value" />'),$glz_value_field.find("input#value").attr("value","-----").prop("disabled",!0),$glz_value_instructions.html("")}function l(){$glz_value_field.find("input#value").length&&(0==$glz_value_field.find("input#value").prop("disabled")&&(s.path_value=$glz_value_field.find("input#value").attr("value")),$glz_value_field.find("input#value").remove(),$glz_value_instructions.html("")),$glz_value_field.find("textarea#value").length||$(".edit-custom-set-value .txp-form-field-value").prepend('<textarea id="value" name="value" rows="5"></textarea>'),s.textarea_value&&$glz_value_field.find("textarea#value").html(s.textarea_value),$glz_value_instructions.html(s.messages.textarea)}function a(){$glz_value_field.find("textarea#value").length&&(s.textarea_value=$glz_value_field.find("textarea#value").html(),$glz_value_field.find("textarea#value").remove(),$glz_value_instructions.html("")),$glz_value_field.find("input#value").length||$glz_value_field.find(".txp-form-field-value").prepend('<input type="text" id="value" name="value" size="32" />'),"-----"==$glz_value_field.find("input#value").attr("value")&&$glz_value_field.find("input#value").attr("value",""),$glz_value_field.find("input#value").prop("disabled",!1),$glz_value_instructions.html(s.messages.customscriptpath),s.path_value&&$glz_value_field.find("input#value").attr("value",s.path_value)}function i(){-1!=$.inArray($("select#custom_set_type :selected").attr("value"),[].concat(s.special_custom_types,["multi-select","custom-script"]))?$glz_select_instructions.html('<a href="//'+window.location.host+window.location.pathname+'?event=prefs#prefs_group_glz_custom_f">'+s.messages.configure+"</a>"):$glz_select_instructions.html("")}textpattern.Relay.register("txpAsyncForm.success",e);var s;s={},s.special_custom_types=["date-picker","time-picker"],s.no_value_custom_types=["text_input","textarea"],$glz_value_field=$(".edit-custom-set-value"),$glz_value_instructions=$glz_value_field.find(".txp-form-field-instructions"),$glz_select_instructions=$(".edit-custom-set-type").find(".txp-form-field-instructions"),s.messages={textarea:$(".glz-custom-textarea-msg").html(),configure:$glz_select_instructions.text(),customscriptpath:$(".glz-custom-script-msg").text()},$(".glz-custom-script-msg").remove(),$(".glz-custom-textarea-msg").remove(),i(),-1!=$.inArray($("select#custom_set_type :selected").attr("value"),[].concat(s.special_custom_types,s.no_value_custom_types))?t():"custom-script"==$("select#custom_set_type :selected").attr("value")&&a(),$("select#custom_set_type").change(function(){i(),-1!=$.inArray($("select#custom_set_type :selected").attr("value"),[].concat(s.special_custom_types,s.no_value_custom_types))?t():"custom-script"==$("select#custom_set_type :selected").attr("value")?a():l()}),e(),$(".txp-layout").on("click",".glz-custom-field-reset",function(){if($(this).hasClass("disabled"))return!1;var e=$(this).attr("name");return $("input[name="+e+"]").prop("checked",!1),$("input[name="+e+"].default").prop("checked",!0),$(this).addClass("disabled"),0===$(this).siblings(".txp-form-radio-reset").length&&0===$("input[name="+e+"]:checked").length&&$(this).after('<input type="hidden" class="txp-form-radio-reset" value="" name="'+e+'" />'),!1}),$(".txp-layout").on("click",".glz-custom-radio .radio",function(){var e=$(this).attr("name");$this_reset_button=$(".glz-custom-field-reset[name="+e+"]"),$this_reset_button.hasClass("disabled")&&($("input[type=hidden][name="+e+"]").remove(),$this_reset_button.removeClass("disabled"))})});
JS;
    return '<script>'.n.$js.n.'</script>';
}


##################
#
#	glz_custom_fields for Textpattern
#	version 2.0 – jools-r
#	Original version: Gerhard Lazu
#
##################

##################
#
#   CALLBACKS – Callback functions called from main.php
#
##################


// -------------------------------------------------------------
// Replaces the default custom fields on the 'Write' panel
function glz_custom_fields_replace($event, $step, $data, $rs)
{
    // Get all custom field sets from prefs
    $all_custom_sets = glz_db_get_all_custom_sets();

    // Filter all custom fields & keep only those that are set for that render step
    $arr_custom_fields = glz_check_custom_set($all_custom_sets, $step);

    // DEBUG
    // dmp($arr_custom_fields);

    $out = ' ';

    if (is_array($arr_custom_fields) && !empty($arr_custom_fields)) {
        // Get all custom fields values for this article
        $arr_article_customs = glz_db_get_article_custom_fields(glz_get_article_id(), $arr_custom_fields);

        // DEBUG
        // dmp($arr_article_customs);

        if (is_array($arr_article_customs)) {
            extract($arr_article_customs);
        }

        // Which custom fields are set
        foreach ($arr_custom_fields as $custom => $custom_set) {
            // Get all possible/default value(s) for this custom set from custom_fields table
            $arr_custom_field_values = glz_db_get_custom_field_values($custom, array('custom_set_name' => $custom_set['name']));

            // DEBUG
            // dmp($arr_custom_field_values);

            // Custom_set formatted for id e.g. custom_1_set => custom-1 - don't ask...
            $custom_id = glz_custom_number($custom, "-");
            // custom_set without "_set" e.g. custom_1_set => custom_1
            $custom = glz_custom_number($custom);

            // If current article holds no value for this custom field and we have no default value, make it empty
            // (not using empty() as it also eradicates values of '0')
            $custom_value = ((isset($$custom) && trim($$custom) <> '') ? $$custom : '');
            // DEBUG
            // dmp("custom_value: {$custom_value}");

            // Check if there is a default value
            // if there is, strip the { }
            $default_value = glz_clean_default(glz_default_value($arr_custom_field_values));
            // DEBUG
            // dmp("default_value: {$default_value}");

            // Now that we've found our default, we need to clean our custom_field values
            if (is_array($arr_custom_field_values)) {
                array_walk($arr_custom_field_values, "glz_clean_default_array_values");
            }

            // DEBUG
            // dmp($arr_custom_field_values);

            // The way our custom field value is going to look like
            list($custom_set_value, $custom_class) = glz_format_custom_set_by_type($custom, $custom_id, $custom_set['type'], $arr_custom_field_values, $custom_value, $default_value);

            // DEBUG
            // dmp($custom_set_value);

            // cf_lang string (define this in your language to create a field label)
            $cf_lang = glz_cf_langname($custom_set["name"]);
            // Get the (localised) label if one exists, otherwise the regular name (as before)
            $cf_label = (gTxt($cf_lang) != $cf_lang) ? gTxt($cf_lang) : $custom_set["name"];

            $out .= inputLabel(
                $custom_id,
                $custom_set_value,
                $cf_label,
                array('', 'instructions_'.$custom),
                array('class' => 'txp-form-field custom-field glz-cf '.$custom_class.' '.$custom_id.' cf-'.glz_cf_idname(str_replace('_', '-', $custom_set["name"])))
            );
        }
    }

    // DEBUG
    // dmp($out);

    // If we're writing textarea custom fields, we need to include the excerpt as well
    if ($step == "body") {
        $out = $data.$out;
    }

    return $out;
}


// -------------------------------------------------------------
// Prep custom fields values for db (convert multiple values into a string e.g. multi-selects, checkboxes & radios)
function glz_custom_fields_before_save()
{
    // Iterate over POST vars
    foreach ($_POST as $key => $value) {
        // Extract custom_{} keys with multiple values as arrays
        if (strstr($key, 'custom_') && is_array($value)) {
            // Convert to delimited string …
            $value = implode('|', $value);
            // and feed back into $_POST
            $_POST[$key] = $value;
        }
    }

    // DEBUG
    // dmp($_POST);
}


// -------------------------------------------------------------
// Inject css & js into admin head
function glz_custom_fields_inject_css_js($debug = false)
{
    global $event, $prefs, $use_minified, $debug;

    $msg = array();
    $min = ($use_minified) ? '.min' : '';

    // do we have a date-picker or time-picker custom field
    $date_picker = glz_check_custom_set_exists("date-picker");
    $time_picker = glz_check_custom_set_exists("time-picker");

    // glz_cf stylesheets (load from file when $debug is set to true)
    if ($debug) {
        $css = '<link rel="stylesheet" type="text/css" media="all" href="'.glz_relative_url($prefs['glz_cf_css_asset_url']).'/glz_custom_fields'.$min.'.css">'.n;
        // Show hidden fields
        $css .= '<style>#prefs-glz_cf_css_asset_url,#prefs-glz_cf_js_asset_url{display:flex}</style>';
    } else {
        $css = glz_custom_fields_head_css();
    }
    // glz_cf javascript
    $js = '';

    if ($event == 'article') {
        // If a date picker field exists
        if ($date_picker) {
            $css .= '<link rel="stylesheet" type="text/css" media="all" href="'.glz_relative_url($prefs['glz_cf_datepicker_url']).'/datePicker'.$min.'.css" />'.n;
            foreach (array('date'.$min.'.js', 'datePicker'.$min.'.js') as $file) {
                $js .= '<script src="'.glz_relative_url($prefs['glz_cf_datepicker_url'])."/".$file.'"></script>'.n;
            }
            $js_datepicker_msg = '<span class="messageflash error" role="alert" aria-live="assertive"><span class="ui-icon ui-icon-alert"></span> <a href="'.ahu.'index.php?event=prefs&check_url=1#prefs_group_glz_custom_f">'.gTxt('glz_cf_public_error_datepicker').'</a> <a class="close" role="button" title="Close" href="#close"><span class="ui-icon ui-icon-close">Close</span></a></span>';
            $js .= <<<JS
<script>
$(document).ready(function () {
    textpattern.Relay.register('txpAsyncForm.success', glzDatePicker);

    function glzDatePicker() {
        if ($("input.date-picker").length > 0) {
            try {
                Date.firstDayOfWeek = {$prefs['glz_cf_datepicker_first_day']};
                Date.format = '{$prefs["glz_cf_datepicker_format"]}';
                Date.fullYearStart = '19';
                $(".date-picker").datePicker({startDate:'{$prefs["glz_cf_datepicker_start_date"]}'});
                $(".date-picker").dpSetOffset(29, -1);
            } catch(err) {
                $('#messagepane').html('{$js_datepicker_msg}');
            }
        }
    }

    glzDatePicker();
});
</script>
JS;
        }

        // If a time picker field exists
        if ($time_picker) {
            $css .= '<link rel="stylesheet" type="text/css" media="all" href="'.glz_relative_url($prefs['glz_cf_timepicker_url']).'/timePicker'.$min.'.css" />'.n;
            $js  .= '<script src="'.glz_relative_url($prefs['glz_cf_timepicker_url']).'/timePicker'.$min.'.js"></script>'.n;
            $js_timepicker_msg = '<span class="messageflash error" role="alert" aria-live="assertive"><span class="ui-icon ui-icon-alert"></span> <a href="'.ahu.'index.php?event=prefs&check_url=1#prefs_group_glz_custom_f">'.gTxt('glz_cf_public_error_timepicker').'</a> <a class="close" role="button" title="Close" href="#close"><span class="ui-icon ui-icon-close">Close</span></a></span>';
            $js  .= <<<JS
<script>
$(document).ready(function () {
    textpattern.Relay.register('txpAsyncForm.success', glzTimePicker);

    function glzTimePicker() {
        if ($(".time-picker").length > 0) {
            try {
                $("input.time-picker").timePicker({
                    startTime: '{$prefs["glz_cf_timepicker_start_time"]}',
                    endTime: '{$prefs["glz_cf_timepicker_end_time"]}',
                    step: {$prefs["glz_cf_timepicker_step"]},
                    show24Hours: {$prefs["glz_cf_timepicker_show_24"]}
                });
                $(".glz-custom-timepicker .txp-form-field-value").on("click", function (){
                    $(this).children(".time-picker").trigger("click");
                });
            } catch(err) {
                $("#messagepane").html('{$js_timepicker_msg}');
            }
        }
    }

    glzTimePicker();
});
</script>
JS;
        }
    }
    if ($event == 'glz_custom_fields') {
        $js .= '<script src="'.glz_relative_url($prefs['glz_cf_js_asset_url']).'/glz_jqueryui.sortable'.$min.'.js"></script>';
    }

    // glz_cf javascript (load from file when $debug is set to true)
    if ($event != 'prefs') {
        if ($debug) {
            $js .= '<script src="'.glz_relative_url($prefs['glz_cf_js_asset_url']).'/glz_custom_fields'.$min.'.js"></script>';
        } else {
            $js .= glz_custom_fields_head_js();
        }

    }

    echo $js.n.t.
        $css.n.t;
}


// -------------------------------------------------------------
// Install glz_cf tables and prefs
function glz_custom_fields_install()
{
    global $prefs;
    $msg = '';

    // Set plugin preferences
    glz_cf_prefs_install();

    // Change 'html' key of default custom fields from 'custom_set'
    // to 'text_input' to avoid confusion with glz set_types()
    safe_update('txp_prefs', "html = 'text_input'", "event = 'custom' AND html = 'custom_set'");

/*
    // LEGACY of the old '<txp:glz_custom_fields_search_form />' tag?
    // Create a search section if not already available (for searching by custom fields)
    if (empty(safe_row("name", 'txp_section', "name='search'"))) {

        // Retrieve skin name used for 'default' section
        $current_skin = safe_field('skin', 'txp_section', "name='default'");

        // Add new 'search' section
        safe_insert('txp_section', "
            name         = 'search',
            title        = 'Search',
            skin         = '".$current_skin."',
            page         = 'default',
            css          = 'default',
            description  = '',
            on_frontpage = '0',
            in_rss       = '0',
            searchable   = '0'
        ");

        $msg = gTxt('glz_cf_search_section_created');
    }
*/
    // Create 'custom_fields' table if it does not already exist
    safe_create(
        'custom_fields',
        "`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL default '',
        `value` varchar(255) NOT NULL default '',
        PRIMARY KEY (id),
        KEY (`name`(50))",
        "ENGINE=MyISAM"
    );

    // Add an 'id' column to an existing legacy 'custom_fields' table
    if (!getRows("SHOW COLUMNS FROM ".safe_pfx('custom_fields')." LIKE 'id'")) {
        safe_alter(
            'custom_fields',
            "ADD `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT KEY"
        );
    }

    // Migrate existing custom_field data to new 'custom_fields' table

    // Skip if glz_cf migration has already been performed
    if (isset($prefs['glz_cf_migrated'])) {
        return;
    }

    // Skip if 'custom_fields' table already contains values (don't overwrite anything)
    if (($count = safe_count('custom_fields', "1 = 1")) !== false) {
        // Set flag in 'txp_prefs' that migration has already been performed
        set_pref("glz_cf_migrated", "1", "glz_custom_f", PREF_HIDDEN);
        $msg = gTxt('glz_cf_migration_skip');
        return;
    }

    // Get all custom field sets from prefs
    $all_custom_sets = glz_db_get_all_custom_sets();

    // Iterate over all custom_fields and retrieve all values
    // in custom field columns in textpattern table
    foreach ($all_custom_sets as $custom => $custom_set) {

        // Check only custom fields that have been set (have a name)
        if ($custom_set['name']) {

            // Get all existing custom values for ALL articles
            $all_values = glz_db_get_all_existing_cf_values(
                glz_custom_number($custom),
                array(
                    'custom_set_name' => $custom_set['name'],
                    'status' => 0
                )
            );

            // If we have results, assemble SQL insert statement to add them to custom_fields table
            if (count($all_values) > 0) {
                $insert = '';
                foreach ($all_values as $escaped_value => $value) {
                    // skip empty values or values > 255 characters (=probably textareas?)
                    if (!empty($escaped_value) && strlen($escaped_value) < 255) {
                        $insert .= "('{$custom}','{$escaped_value}'),";
                    }
                }
                // Trim final comma and space
                $insert = rtrim($insert, ', ');
                $query = "
                    INSERT INTO
                        ".safe_pfx('custom_fields')." (`name`,`value`)
                    VALUES
                        {$insert}
                    ";

                if (isset($query) && !empty($query)) {

                    // Add all custom field values to 'custom_fields' table
                    safe_query($query);

                    // Update the type of this custom field to select
                    // (might want to make this user-adjustable at some point)
                    safe_update(
                        'txp_prefs',
                        "val      = '".$custom_set['name']."',
                         html     = 'select',
                         position = '".$custom_set['position']."'",
                        "name = '{$custom}'"
                    );
                    $msg = gTxt('glz_cf_migration_success');
                }
            }
        }
    }

    // Set flag in txp_prefs that migration has been performed
    set_pref("glz_cf_migrated", "1", "glz_custom_f", PREF_HIDDEN);
}


/**
 * Uninstaller.
 *
 * IMPORTANT: There has been no uninstall function until to now to prevent
 * accidental loss of user input if uninstalling the plugin.
 *
 * This is intended just as an on-demand clean-up script and is hidden
 * behind a 'safety catch'. In the 'txp_prefs' table, set the column 'type'
 * of 'glz_cf_permit_full_deinstall' to '1' to reveal the switch in the
 * preferences panel. The installer sets this to hidden from the beginning.
 *
 */

function glz_custom_fields_uninstall()
{
    global $prefs;

    // To prevent inadvertent data loss, full deinstallation is only permitted
    // if the 'safety catch' has been disabled: set 'glz_cf_permit_full_deinstall' = 1
    if ($prefs['glz_cf_permit_full_deinstall'] == '1') {

        // Delete 'custom_fields' table
        safe_query(
            'DROP TABLE IF EXISTS '.safe_pfx('custom_fields')
        );

        // Get all custom fields > 10
        $additional_cfs = safe_rows('name', 'txp_prefs', "name LIKE 'custom\___\_set' AND name <> 'custom_10_set'");

        $drop_query ='';
        foreach ($additional_cfs as $val) {
            // Delete prefs labels for custom fields > 10
            safe_delete('txp_lang', "name = '".$val['name']."'");
            // Build DROP query for 'textpattern' table
            $drop_query .= 'DROP '.str_replace("_set", "", $val['name']).', ';
        }
        // Trim final comma and space from drop statement
        $drop_query = rtrim($drop_query, ', ');
        // Drop used 'custom_X' > 10 columns from 'textpattern' table
        safe_alter('textpattern', $drop_query);

        // Delete all saved language strings
        safe_delete('txp_lang', "event = 'glz_cf' OR name LIKE 'instructions\_glz\_cf%'");

        // Delete custom field entries > 10 from 'txp_prefs' (custom_ __ _set = must have two chars in the middle)
        safe_delete('txp_prefs', "name LIKE 'custom\___\_set' AND name <> 'custom_10_set' AND event = 'custom'");

        // Delete plugin prefs
        safe_delete('txp_prefs', "event LIKE 'glz\_custom\_f%'");

        // Reset all remaining custom fields (1-10) back to original type 'custom_set'
        safe_update('txp_prefs', "html = 'custom_set'", "event = 'custom'");

        // The following also clears the built-in custom fields 1-10
        // For the "full whammy" uncomment these too.
    /*
        // Zero custom field user input in the 'textpattern' table
        safe_update('textpattern', "custom_1 = NULL, custom_2 = NULL, custom_3 = NULL, custom_4 = NULL, custom_5 = NULL, custom_6 = NULL, custom_7 = NULL, custom_8 = NULL, custom_9 = NULL, custom_10 = NULL", "1 = 1");
        // Erase names from 'txp_prefs' tables
        safe_update('txp_prefs', "val = NULL", "name LIKE 'custom\_%%\_set'");
    */
        $message = "‘glz_custom_fields’ has been deinstalled. ALL CUSTOM FIELD USER DATA has also been removed.";

    } else {

        // Regular deinstall

        // Should we restore the 'html' type for custom fields 1-10 to 'text_input'?
        // Yes: it prevents errors occurring (or is there an automatic fallback)
        // No:  switching them back loses their settings. The data is kept but the
        //      custom_field type is then lost in the case of a reinstallation.

        $message = "‘glz_custom_fields’ has been deinstalled. Your custom field data has NOT been deleted and will reappear if you reinstall ‘glz_custom_fields’.";
    }

}


// -------------------------------------------------------------
// Re-route 'Options' link on Plugins panel to Admin › Preferences
function glz_custom_fields_prefs_redirect()
{
    header("Location: index.php?event=prefs#prefs_group_glz_custom_f");
}


// -------------------------------------------------------------
// Custom field sortable position router function
function glz_cf_positionsort_steps($event='', $step='', $msg='')
{
    switch ($step) {
    case 'get_js':
        glz_cf_positionsort(gps('js'));
        break;
    case 'put':
        glz_cf_positionsort(gps('type'));
        break;
    }
}


// -------------------------------------------------------------
// Custom field sortable position inject js
function glz_cf_positionsort_js()
{
    echo <<<HTML
<script src="index.php?event=glz_custom_fields&step=get_js"></script>
HTML;
}


// -------------------------------------------------------------
// Custom field sortable position steps function
function glz_cf_positionsort($js)
{
    header('Content-Type: text/javascript');

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

        header('Content-Type: application/json');
        $success = true;
        foreach ($_POST as $customfield => $sort) {
            if (!safe_update('txp_prefs', 'position=\''.doSlash($sort).'\'', 'name=\''.doSlash($customfield).'\'')) {
                $success = false;
            }
        }
        echo json_encode(array('success' => $success));

    } else {

        $position = array();
        foreach (safe_rows('name, position', 'txp_prefs', "event = 'custom'") as $row) {
            $customfield = $row['name'];
            $sort = $row['position'];
            if (!strlen($sort)) {
                $sort = 0;
            }
            $position['glz_' . $customfield] = ctype_digit($sort) ? (int)$sort : $sort;
        }

        // Language strings
        $ui_sort = gTxt('glz_cf_col_sort');
        $msg_success = gTxt('glz_cf_sort_success');
        $msg_error = gTxt('glz_cf_sort_error');

        echo 'var position = ', json_encode($position), ';'."\n";
        echo <<<EOB
$(function() {
    $('#glz_custom_fields_container thead tr').prepend('<th class="txp-list-col-sort">$ui_sort</th>').find('th').each(function() {
        var th = $(this);
        th.html(th.text());
    });
    $('#glz_custom_fields_container table').addClass('sortable').find('tbody tr').prepend('<td></td>').appendTo('#glz_custom_fields_container tbody').sortElements(function(a, b) {
        var a_sort = position[$(a).attr('id')];
        var b_sort = position[$(b).attr('id')];
        if (a_sort == b_sort) {
            return 0;
        }
        return a_sort > b_sort ? 1 : -1;
    }).parent().sortable({
        items: 'tr',
        helper: function(e, ui) {
            $('.ui-sortable').parent().addClass('fixed-width');
            ui.children().each(function() {
                $(this).width($(this).width());
            });
            return ui;
        },
        axis: 'y',
        handle: 'td:first-child',
        start: function(event, ui) {
        },
        stop: function() {
            $('.ui-sortable').parent().removeClass('fixed-width');
            var position = {};
            $(this).find('tr').each(function() {
                var tr = $(this);
                position[tr.attr('id').replace('glz_', '')] = tr.index();
            });
            var set_message = function(message, type) {
                $('#messagepane').html('<span id="message" class="messageflash ' + type + '" role="alert" aria-live="assertive">' + message + ' <a class="close" role="button" title="Close" href="#close"><span class="ui-icon ui-icon-close">Close</span></a>');
            }
            $.ajax(
                'index.php?event=glz_custom_fields&step=put', {
                    type: 'POST',
                    data: position,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            set_message('$msg_success', 'success')
                        } else {
                            this.error();
                        }
                    },
                    error: function() {
                        set_message('$msg_error', 'error');
                    }
                }
            );
        }
    }).find('tr').find('td:first-child').html('&#9776;');
});
EOB;

    }
    exit();
}


##################
#
#	glz_custom_fields for Textpattern
#	version 2.0 – jools-r
#	Original version: Gerhard Lazu
#
##################

##################
#
#   STEPS – List, Save, Edit, Reset, Delete
#
##################


/**
 * Renders the main custom fields list pane
 *
 * @param  string $msg  Success, error or warning message shown by Textpattern
 * @return string HTML  Table of custom fields
 */

function glz_cf_list($msg='', $debug = false)
{
    global $event, $step;

    pageTop(gTxt('glz_cf_tab_name'), $msg);

    // Retrieve array of all custom fields properties
    $all_custom_sets = glz_db_get_all_custom_sets();

    $out = array();

    $out[] =
        tag_start('div', array('class' => 'txp-layout')).
            tag_start('div', array('class' => 'txp-layout-2col')).
                hed(gTxt('glz_cf_tab_name'), 1, array('class' => 'txp-heading')).
            tag_end('div').
            tag_start('div', array('class' => 'txp-layout-2col')).
                href(gTxt('tab_preferences'), '?event=prefs#prefs_group_glz_custom_f', array('class' => 'glz-cf-setup-switch')).
            tag_end('div').
            tag_start('div', array('class' => 'txp-layout-1col', 'id' => $event.'_container'));

    // 'Add new custom field' button
    $out[] =
        n.tag(
            href(
                gTxt('glz_cf_add_new_cf'),
                array(
            'event' => 'glz_custom_fields',
            'step'  => 'add',
            '_txp_token' => form_token(),
                ),
                array(
                    'class' => 'txp-button',
                    'title' => gTxt('glz_cf_add_new_cf')
                )
            ),
            'div',
            array('class' => 'txp-control-panel')
        );

    // Column headings
    $headers = array(
        'position'  => 'position',
        'id'        => 'id',
        'name'      => 'name',
        'title'     => 'title',
        'type'      => 'type',
        'options'   => 'options'
    );
    $head_row = '';

    foreach ($headers as $header => $column_head) {
        $head_row .= column_head(
            array(
                'options' => array('class' => trim('txp-list-col-'.$header)),
                'value'   => $column_head,
                'sort'    => $header
            )
        );
    }

    // Table start
    $out[] =
        tag_start('div', array('class' => 'txp-listtables')).
        n.tag_start('table', array('class' => 'txp-list--no-options')).
        n.tag_start('thead').
            tr($head_row).
        n.tag_end('thead').
        n.tag_start('tbody');


    // Table body rows
    foreach ($all_custom_sets as $custom => $custom_set) {

        // Edit link (with 'name' and 'id' as link text)
        foreach (array('name', 'id') as $text) {
            $edit_link[$text] = href(
                $custom_set[$text],
                array(
                    'event'      => 'glz_custom_fields',
                    'step'       => 'edit',
                    'ID'         => $custom_set['id'],
                    '_txp_token' => form_token(),
                ),
                array(
                    'class'       => 'edit-link',
                    'title'       => gTxt('glz_cf_action_edit_title', array('{custom_set_name}' => gTxt('glz_cf_title').' #'.glz_custom_digit($custom)))
                )
            );
        }

        // Reset or delete buttons
        if ($custom_set['id'] < 11) {
            $delete_link = href(
                gTxt('reset'),
                array(
                    'event'      => 'glz_custom_fields',
                    'step'       => 'reset',
                    'ID'         => $custom_set['id'],
                    '_txp_token' => form_token(),
                ),
                array(
                    'class'       => 'ui-icon ui-icon-trash',
                    'title'       => gTxt('reset'),
                    'data-verify' => gTxt('glz_cf_confirm_reset', array('{custom}' => 'ID# '.glz_custom_digit($custom).': '.htmlspecialchars($custom_set['name']) )),
                )
            );
        } else {
            $delete_link = href(
                gTxt('delete'),
                array(
                    'event'      => 'glz_custom_fields',
                    'step'       => 'delete',
                    'ID'         => $custom_set['id'],
                    '_txp_token' => form_token(),
                ),
                array(
                    'class'       => 'ui-icon ui-icon-close',
                    'title'       => gTxt('delete'),
                    'data-verify' => gTxt('glz_cf_confirm_delete', array('{custom}' => 'ID# '.glz_custom_digit($custom).': '.htmlspecialchars($custom_set['name']) )),
                )
            );
        }

        $custom_label = (empty($custom_set['title']) ? gTxt('undefined') : $custom_set['title']);

        if (!empty($custom_set["name"])) {
            $out[] =
                tr(
                    hCell(
                        $custom_set['position'],
                        '',
                        array('class' => 'txp-list-col-position')
                    ).
                    td(
                        $edit_link['id'],
                        '',
                        'txp-list-col-id'
                    ).
                    td(
                        $edit_link['name'],
                        '',
                        'txp-list-col-name'
                    ).
                    td(
                        $custom_label.(empty($custom_set['instructions']) ? '' : ' <span class="cf-instructions ui-icon ui-icon-clipboard" title="'.$custom_set['instructions'].'"></span>'),
                        '',
                        'txp-list-col-title'.(empty($custom_set['title']) ? ' disabled' : '')
                    ).
                    td(
                        (($custom_set['name']) ? gTxt('glz_cf_'.$custom_set['type']) : ''),
                        '',
                        'txp-list-col-type'
                    ).
                    td(
                        $delete_link,
                        '',
                        'txp-list-col-options'
                    ),
                    array('id' => 'glz_custom_'.$custom_set['id'].'_set')
                );
        }
    }

    // Table end
    $out[] =
        n.tag_end('tbody').
        n.tag_end('table').
        n.tag_end('div'). // End of .txp-listtables.
        pluggable_ui('customfields_ui', 'table_end', '').
        tag_end('div'). // End of .txp-layout-1col.
        tag_end('div'); // End of .txp-layout.

    // Render panel
    if (is_array($out)) {
        $out = implode(n, $out);
    }
    echo $out;
}

/**
 * Add a new custom field.
 * Finds the next vacant custom field and passes it to the edit form
 *
 * @param  string $msg  Pass-thru of success, error or warning message shown by Textpattern
 */
function glz_cf_add($msg='', $debug = false)
{
    // Get next free custom field id
    $next_free_cf_id = glz_next_empty_custom();
    // Pass into edit pane
    glz_cf_edit($msg, $next_free_cf_id);
}


/**
 * Edit a custom field / Add a new custom field.
 * Retrieves values for ID url variable (or id from glz_cf_add)
 *
 * @param  string  $msg  Success, error or warning message shown by Textpattern
 * @param  integer  $id  passed from glz_cf_add
 */
function glz_cf_edit($msg='', $id='', $debug = false)
{
    global $event, $step, $prefs;
    // get ID from URL of $id not supplied (e.g. by "add" step)
    if (empty($id)) {
        $id = gps('ID');
    }
    // Check ID is properly formed, else back to list
    if (!intval($id)) {
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    }
    // If editing (not adding), check ID actually exists, else back to list
    if (($step === 'edit') && (!get_pref('custom_'.$id.'_set'))) {
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    };

    if ($step === 'edit') {
        // 'Edit' Step: retrieve array of all custom field properties
        $custom_field = glz_db_get_custom_set($id);
        $panel_title = gTxt('glz_cf_action_edit_title', array('{custom_set_name}' => gTxt('glz_cf_title').' #'.$custom_field['id']));
    } else {
        // 'Add' step: set available starting properties, null others
        $custom_field = array();
        $custom_field['id'] = $id;
        $custom_field['custom_set'] = 'custom_'.$id.'_set';
        foreach (array('name', 'position', 'type', 'title', 'instructions') as $key) {
            $custom_field[$key] = null;
        }
        $panel_title = gTxt('glz_cf_action_new_title');
    }

    // Pass existing name in case custom field is renamed
    $existing_name = ($step === 'edit') ?
        hInput('custom_set_name_old', $custom_field['name']) :
        '';
    // Pass in existing position as hidden input (change position value in the list)
    $existing_position = (($step === 'edit') && ($prefs['glz_cf_use_sortable'] == '1')) ?
        hInput('custom_set_position', $custom_field['position']) :
        '';

    // Custom field types drop-down
    $arr_custom_set_types = glz_custom_set_types();
    $custom_set_types = null;
    foreach ($arr_custom_set_types as $custom_type_group => $custom_types) {
        $custom_set_types .= '<optgroup label="'.gTxt('glz_cf_types_'.$custom_type_group).'">'.n;
        foreach ($custom_types as $custom_type) {
            $selected = ($custom_field['type'] == $custom_type) ?
                ' selected="selected"' :
                null;
            $custom_set_types .= '<option value="'.$custom_type.'" dir="auto"'.$selected.'>'.gTxt('glz_cf_'.$custom_type).'</option>'.n;
        }
        $custom_set_types .= '</optgroup>'.n;
    }

    // Fetch (multiple) type values for this custom field
    if ($step === 'edit') {
        if ($custom_field['type'] == "text_input") {
            $arr_values = glz_db_get_all_existing_cf_values(glz_custom_number($custom_field['custom_set']), array('custom_set_name' => $custom_field['name'], 'status' => 4));
        } else {
            $arr_values = glz_db_get_custom_field_values($custom_field['custom_set'], array('custom_set_name' => $custom_field['name']));
        }
        $values = ($arr_values) ? implode("\r\n", $arr_values) : '';
    } else {
        $values = '';
    }
    // This needs to be different for a script
    if (isset($custom_field['type']) && $custom_field['type'] == "custom-script") {
        $value = fInput('text', 'value', $values, '', '', '', '', '', 'value');
        $value_instructions = 'glz_cf_js_script_msg';
    } else {
        $value = text_area('value', 0, 0, $values, 'value');
        $value_instructions = 'glz_cf_multiple_values_instructions';
    }

    $action = ($step === 'edit') ?
        fInput('submit', 'save', gTxt('save'), 'publish') :
        fInput('submit', 'add_new', gTxt('glz_cf_add_new_cf'), 'publish');

    // Build the form
    pageTop($panel_title, $msg);
    // dmp($custom_field);

    $out = array();

    $out[] = hed($panel_title, 2);
    $out[] =
        inputLabel(
                'custom_set_name',
                fInput('text', 'custom_set_name', htmlspecialchars($custom_field['name']), '', '', '', 28, '', 'custom_set_name'),
                'glz_cf_edit_name',
                array(
                    0 => '',
                    1 => 'glz_cf_edit_name_hint' // Inline help string
                )
            ).
        inputLabel(
                'custom_set_title',
                fInput('text', 'custom_set_title', htmlspecialchars($custom_field['title']), '', '', '', INPUT_REGULAR, '', 'custom_set_title'),
                'glz_cf_edit_title',
                array(
                    0 => '',
                    1 => 'glz_cf_edit_title_hint' // Inline help string
                )
            ).
        inputLabel(
                'custom_set_instructions',
                fInput('text', 'custom_set_instructions', htmlspecialchars($custom_field['instructions']), '', '', '', INPUT_REGULAR, '', 'custom_set_instructions'),
                'glz_cf_edit_instructions',
                array(
                    0 => '',
                    1 => 'glz_cf_edit_instructions_hint' // Inline help string
                )
            ).
        inputLabel(
                'custom_set_type',
                '<select name="custom_set_type" id="custom_set_type">'.$custom_set_types.'</select>',
                'glz_cf_edit_type',
                array(
                    0 => '',
                    1 => 'glz_cf_js_configure_msg'  // Inline help string
                )
            ).
        ($prefs['glz_cf_use_sortable'] == '0' ?
            inputLabel(
                    'custom_set_position',
                    fInput('text', 'custom_set_position', htmlspecialchars($custom_field['position']), '', '', '', INPUT_MEDIUM, '', 'custom_set_position'),
                    'glz_cf_edit_position',
                    array(
                        0 => '',
                        1 => 'glz_cf_edit_position_hint'  // Inline help string
                    )
                )
        : '').
        inputLabel(
                'custom_set_value',
                $value,
                'glz_cf_edit_value',
                array(
                    0 => '',
                    1 => $value_instructions  // Inline help string
                )
            ).
        n.tag(gTxt('glz_cf_js_script_msg'), 'span', array('class' => 'glz-custom-script-msg hidden')).
        n.tag(gTxt('glz_cf_js_textarea_msg'), 'span', array('class' => 'glz-custom-textarea-msg hidden')).
        eInput('glz_custom_fields').
        sInput('save').
        hInput('custom_set', $custom_field['custom_set']).
        hInput('custom_field_number', $custom_field['id']).
        $existing_name.
        $existing_position.
        graf(
            sLink('glz_custom_fields', '', gTxt('cancel'), 'txp-button').
            $action,
            array('class' => 'txp-edit-actions')
        );

    echo form(join('', $out), '', '', 'post', 'txp-edit', '', 'add_edit_custom_field');
}


/**
 * Saves a new or existing custom field
 * Retrieves incoming $POST variables
 *
 * @param  string  $msg  Success, error or warning message shown by Textpattern
 */
function glz_cf_save($msg='', $debug = false)
{
    global $event, $step, $prefs, $msg;

    $in = array_map('assert_string', psa(array(
        'custom_set',
        'custom_field_number',
        'custom_set_name',
        'custom_set_name_old',
        'custom_set_title',
        'custom_set_instructions',
        'custom_set_type',
        'custom_set_position',
        'value',
        'save',
        'add_new'
    )));

    extract($in);

    // No name given -> error + return to list
    if (empty($custom_set_name)) {
        if ($debug) {
            dmp('No name specified');
        } // DEBUG info
        $msg = array(gTxt('glz_cf_no_name'), E_ERROR);
        glz_cf_list($msg);
        return;
    }

    // Same name given as another existing custom field -> error + return to list
    if (glz_check_custom_set_name($custom_set_name, $custom_set)) {
        if ($debug) {
            dmp('Same name as other custom field specified');
        } // DEBUG info
        // If the sanitized cf name matches an existing custom field, provide an extra hint in the error message
        $name_sanitized = glz_sanitize_for_cf($custom_set_name);
        $name_exists_msg = ($custom_set_name <> $name_sanitized) ? $custom_set_name.' ('.$name_sanitized.')' : $custom_set_name;
        $msg = array(gTxt('glz_cf_exists', array('{custom_set_name}' => $name_exists_msg)), E_ERROR);
        glz_cf_list($msg);
        return;
    }

    // No values specified for checkbox type -> error + return to list
    if ($custom_set_type == 'checkbox' && empty($value)) {
        $msg = array(gTxt('glz_cf_no_values'), E_ERROR);
        glz_cf_list($msg);
        return;
    }

    // At lest two values must specified for radiobutton/multiselect type -> error + return to list
    $cf_values = array_unique(array_filter(explode("\r\n", $value), 'glz_array_empty_values'));
    if ( ($custom_set_type == 'radio' || $custom_set_type == 'multi-select') && (count($cf_values) < 2) ) {
        $msg = array(gTxt('glz_cf_not_enough_values', array('{cf_type}' => gTxt('glz_cf_'.$custom_set_type))), E_ERROR);
        glz_cf_list($msg);
        return;
    }

    if ($debug) {
        dmp('CF name as input: '.$custom_set_name);
    } // DEBUG info

    $create_new_cf = (!empty($add_new)) ? true : false;

    if ($create_new_cf) {
        // Adding a new custom field
        if ($debug) {
            dmp('Creating a new custom field');
        } // DEBUG info

        // Note the custom field name input by the user
        $custom_set_name_input = $custom_set_name;
        // Sanitize custom field name : use strict mode for new custom fields
        $custom_set_name = glz_sanitize_for_cf($custom_set_name);
        // Compare: if different -> Raise information notice
        if ($custom_set_name_input <> $custom_set_name) {
            $msg = array(gTxt('glz_cf_name_renamed_notice', array('{custom_name_input}' => $custom_set_name_input, '{custom_name_output}' => $custom_set_name )), E_WARNING);
        }
    } else {
        // Editing an existing custom field
        if ($debug) {
            dmp('Updating an existing custom field');
        } // DEBUG info

        // Check if custom field name is valid -> Raise warning notice if not
        glz_is_valid_cf_name($custom_set_name);
        // Sanitize custom field name : use $lite mode for backwards compatibility
        $custom_set_name = glz_sanitize_for_cf($custom_set_name, $lite = true);
    }

    // Use sanitized custom set name
    $in['custom_set_name'] = $custom_set_name;
    if ($debug) {
        dmp('CF name cleaned: '.$custom_set_name);
    } // DEBUG info

    // If there is no value for 'position' specified, use the custom field numbers
    // if using jqueryui.sortable use 999 (the end of the list)
    if (empty($custom_set_position)) {
        $in['custom_set_position'] = ($prefs['glz_cf_use_sortable'] == '1') ? '999' : $custom_field_number;
    }

    if ($debug) {
        dmp('$in: '.$in);
    } // DEBUG info

    // OK, good to go

    if ($create_new_cf) {

        // ACTION! Save new custom field to DB
        $result = glz_db_cf_new($in, $debug);

        if ($result) {
            // update lastmod + corresponding event
            update_lastmod(
                'custom_field_created',
                compact(
                    'custom_set',
                    'custom_field_number',
                    'custom_set_name',
                    'custom_set_title',
                    'custom_set_instructions',
                    'custom_set_type',
                    'custom_set_position'
                    )
                );

            // Success or warning message (if generated earlier by glz_is_valid_cf_name)
            if (empty($msg)) {
                $msg = gTxt('glz_cf_created', array('{custom_set_name}' => $custom_set_name));
            }
        }
    } else {

        // ACTION! Update custom field in DB
        $result = glz_db_cf_save($in, $debug);

        if ($result) {
            // update lastmod + corresponding event
            update_lastmod(
                'custom_field_updated',
                compact(
                    'custom_set',
                    'custom_field_number',
                    'custom_set_name',
                    'custom_set_name_old',
                    'custom_set_title',
                    'custom_set_instructions',
                    'custom_set_type',
                    'custom_set_position'
                    )
                );
            // Success or warning message (if generated earlier by glz_is_valid_cf_name)
            if (empty($msg)) {
                $msg = gTxt('glz_cf_updated', array('{custom_set_name}' => $custom_set_name));
            }
        }
    }

    // Render custom field list
    glz_cf_list($msg);
}


/**
 * Reset step in UI – for custom field IDs 1-10.
 * Retrieves value for ID from url
 *
 * @param  string  $msg  Pass-thru of success / error / warning message
 * @param  bool  $debug  Switch on debug messaging and query dumps
 */
function glz_cf_reset($msg='', $debug = false)
{
    global $event, $step;

    // Get ID from URL
    $id = gps('ID');

    // Check ID is properly formed, else back to list
    if (!intval($id)) {
        if ($debug) {
            dmp($id.' is not an integer');
        } // DEBUG info
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    }
    // Check ID actually exists before resetting, else back to list
    if (!get_pref('custom_'.$id.'_set')) {
        if ($debug) {
            dmp('custom_'.$id.'_set does not exist');
        } // DEBUG info
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    };

    // ACTION! Reset in DB
    $result = glz_db_cf_reset($id, $debug);

    if ($result) {
        update_lastmod('custom_field_reset', $id);
        $msg = gTxt('glz_cf_reset', array('{custom_set_id}' => 'ID# '.$id));
    } else {
        $msg = array(gTxt('glz_cf_reset_error', array('{custom_set_id}' => 'ID# '.$id)), E_ERROR);
    }

    // Render custom field list + message
    glz_cf_list($msg);
}


/**
 * Delete step in UI – for custom fields ID > 10.
 * Retrieves value for ID from url
 *
 * @param  string  $msg  Success / error / warning message
 * @param  bool  $debug  Switch on debug messaging and query dumps
 */
function glz_cf_delete($msg='', $reset= false, $debug = false)
{
    global $event, $step;

    // Get ID from URL
    $id = gps('ID');

    // Check ID is properly formed, else back to list
    if (!intval($id)) {
        if ($debug) {
            dmp($id.' is not an integer');
        } // DEBUG info
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    }
    // Check ID actually exists before deleting, else back to list
    if (!get_pref('custom_'.$id.'_set')) {
        if ($debug) {
            dmp('custom_'.$id.'_set does not exist');
        } // DEBUG info
        glz_cf_list(array(gTxt('glz_cf_no_such_custom_field'), E_ERROR));
        return false;
    };

    // ACTION! Delete from DB (reset for IDs 1-10)
    $result = glz_db_cf_delete($id, $reset, $debug);

    if ($result) {
        update_lastmod('custom_field_deleted', $id);
        $msg = gTxt('glz_cf_deleted', array('{custom_set_id}' => 'ID# '.$id));
    } else {
        $msg = array(gTxt('glz_cf_deleted_error', array('{custom_set_id}' => 'ID# '.$id)), E_ERROR);
    }

    // Render custom field list + message
    glz_cf_list($msg);
}


##################
#
#	glz_custom_fields for Textpattern
#	version 2.0 – jools-r
#	Original version: Gerhard Lazu
#
##################

##################
#
#   MAIN – Register plugin privs + callbacks + dispatcher
#
##################

global  $event, $step, $txp_permissions, $use_minified, $debug;

// DEBUG: Set $debug to true to load css and js from files instead of injecting into head
$debug = false;
// DEBUG: Set $use_minified to false to load regular (non-minified) js and css files
$use_minified = true;

if(@txpinterface == 'admin') {

    // glz admin panels / events
    $glz_admin_events = array(
        'article',
        'prefs',
        'glz_custom_fields'
    );

    // Add prefs privs
    add_privs('prefs.glz_custom_f', '1');
    add_privs('prefs.glz_custom_f.glz_cf_datepicker', '1');
    add_privs('prefs.glz_custom_f.glz_cf_timepicker', '1');

    // Disable regular customs preferences (remove privs)
    $txp_permissions['prefs.custom'] = '';

    // Redirect 'Options' link on plugins panel to preferences
    add_privs('plugin_prefs.glz_custom_fields', '1');
    register_callback('glz_custom_fields_prefs_redirect', 'plugin_prefs.glz_custom_fields');

    // Install plugin
    register_callback('glz_custom_fields_install', 'plugin_lifecycle.glz_custom_fields', 'installed');
    register_callback('glz_custom_fields_uninstall', 'plugin_lifecycle.glz_custom_fields', 'deleted');

    // Restrict css/js + pre-save to relevant admin pages only
    if (in_array($event, $glz_admin_events)) {

        // Add CSS & JS to admin head area
        register_callback('glz_custom_fields_inject_css_js', 'admin_side', 'head_end');

        // Use jqueryui.sortable to set the custom field position value
        if ($prefs['glz_cf_use_sortable'] == '1') {
            register_callback('glz_cf_positionsort_js', 'customfields_ui', 'table_end');
            register_callback('glz_cf_positionsort_steps', 'glz_custom_fields');
        }

        // Write tab: multiple value array -> string conversion on save/create
        if (($step === 'edit') || ($step === 'create')) {
            register_callback('glz_custom_fields_before_save', 'article', '', 1);
        }
    }

    // Custom fields tab under extensions
    add_privs('glz_custom_fields', '1,2');
    register_tab('extensions', 'glz_custom_fields', gTxt('glz_cf_tab_name'));
    register_callback('glz_cf_dispatcher', 'glz_custom_fields');

    // Write tab: replace regular custom fields with glz custom fields
    // -> custom fields
    register_callback('glz_custom_fields_replace', 'article_ui', 'custom_fields');
    // -> textareas
    register_callback('glz_custom_fields_replace', 'article_ui', 'body');

}


/**
 * Jump off to relevant stub for handling actions.
 */
function glz_cf_dispatcher()
{
    global $event, $step;

    // Available steps
    $steps = array(
        'add'    => true,
        'edit'   => true,
        'save'   => true,
        'reset'  => true,
        'delete' => true
    );

    // Use default step if nothing matches
    if(!$step || ((!bouncer($step, $steps)) || !isset($steps[$step]))) {
        $step = 'list';
    }

    // Run the function
    $func = 'glz_cf_' . $step;
    $func();
}