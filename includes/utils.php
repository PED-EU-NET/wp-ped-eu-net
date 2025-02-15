<?php

/**
 * Get label of GForms field
 *
 * @param array|int $form form or its id
 * @param string|int $id field id
 * @return string
 */
function pedeu_get_form_field_label($form, $id): string
{
    $field = GFFormsModel::get_field($form, $id);
    return $field->label;
}


/**
 * Get GForm data based on a post ID.
 *
 * @param int $pid post ID
 * @return array GForms entry
 */
function pedeu_get_form_data($pid): array
{
    if (!is_numeric($pid)) return array();

    $entry_id = get_post_meta($pid, "entry_id", true);
    $entry = GFAPI::get_entry($entry_id);

    return (array)$entry;
}


/**
 * Get basic information about all published PED projects
 *
 * @return array
 */
function pedeu_get_published_projects(): array
{
    $posts = get_posts(array(
        "numberposts" => -1,
        "post_type" => "projects",
        "meta_query" => array(),
        "post_status" => "publish",
    ));
    $projects = array();
    foreach ($posts as $value) {
        $id = $value->ID;
        $projects[$id] = array(
            "id" => $id,
            "title" => get_the_title($id),
            "entry_id" => get_post_meta($id, "entry_id", true),
        );
    }
    return $projects;
}


/**
 * Get basic information about all published PED case studies
 *
 * @return array
 */
function pedeu_get_published_case_studies(): array
{
    $posts = get_posts(array(
        "numberposts" => -1,
        "post_type" => "case-studies",
        "meta_query" => array(),
        "post_status" => "publish",
    ));
    $case_studies = array();
    foreach ($posts as $value) {
        $id = $value->ID;
        $case_studies[$id] = array(
            "id" => $id,
            "title" => get_the_title($id),
            "entry_id" => get_post_meta($id, "entry_id", true),
        );
    }
    return $case_studies;
}


/**
 * Get context for rendering tables
 *
 * @return array
 */
function pedeu_get_table_context(): array
{
    return array(
        "projects" => pedeu_get_published_projects(),
        "case-studies" => pedeu_get_published_case_studies(),
    );
}


/**
 * Generate a random string of given length.
 *
 * @param int $length length of the random string
 * @return string
 */
function pedeu_generate_random_string(int $length = 10): string
{
    $alphabet = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $heap = str_repeat($alphabet, ceil($length / strlen($alphabet)));
    return substr(str_shuffle($heap), 1, $length);
}


/**
 * Normalize entry with invitation record to latest version.
 */
function pedeu_normalize_invite_entry($entry): array
{
    $version = $entry["_version"] ?? 0;
    if ($version == 0) {
        // transform data from legacy version
        $entry["email"] = array_key_first($entry);
        $entry["link"] = $link = $entry[array_key_first($entry)];
        $parsed_url = parse_url($link);
        parse_str($parsed_url["query"], $query);
        $entry["code"] = $query["access_code"];
        if (str_contains($link, get_page_link(PED_EU_FORM_PROJECT_PID))) {
            $entry["type"] = "project";
        } elseif (str_contains($link, get_page_link(PED_EU_FORM_CASE_STUDY_PID))) {
            $entry["type"] = "case-study";
        } else {
            $entry["type"] = "unknown";
        }
        $entry["timestamp"] = 0;
        $entry["active"] = true;
        $entry["_version"] = 1;
    }
    return $entry;
}

/**
 * Get case study details from GForms entry.
 *
 * @param numeric $id post ID
 * @param array $entry GForms entry
 * @return array
 */
function pedeu_get_case_study_details($id, $entry): array
{
    $project_ids = $entry["833"];
    $project_ids = trim($project_ids, '*');
    $project_ids = str_replace("[", "", $project_ids);
    $project_ids = str_replace("]", "", $project_ids);
    $project_ids = str_replace("\"", "", $project_ids);
    $project_ids = array_filter(explode(",", $project_ids));

    return array(
        "id" => $id,
        "name" => $entry["3"],
        "is_ped_case_study" => $entry["7.1"] != "",
        "is_ped_relevant_case_study" => $entry["293.1"] != "",
        "is_ped_lab" => $entry["9.1"] != "",
        "phase" => $entry["11"],
        "project_ids" => $project_ids,
        "title" => $entry["3"],
        "def1" => $entry["def1"],
        "def2" => $entry["def2"],
        "proj_phase" => $entry["proj_phase"],
        "long" => $entry["20"],
        "lat" => $entry["21"],
    );
}

/**
 * Get project details from GForms entry.
 *
 * @param numeric $id post ID
 * @param array $entry GForms entry
 * @return array
 */
function pedeu_get_project_details($id, $entry): array
{
    return array(
        "id" => $id,
        "title" => $entry["4"],
        "code" => $entry["3"],
        "start_date" => pedeu_read_date($entry["8"]),
        "end_date" => pedeu_read_date($entry["7"]),
    );
}

/**
 * Try to transform PED-formatted date to time.
 *
 * @param $ped_date
 * @return mixed
 */
function pedeu_read_date($ped_date)
{
    if (is_numeric($ped_date)) {
        return $ped_date;
    }
    if (is_string($ped_date)) {
        $parts = explode("/", $ped_date);
        if (count($parts) == 2) {
            $month = $parts[0];
            $year = $parts[1];
            if (strlen($year) == 2) {
                $year = "20$year";
            }
            $ped_date = "01/$month/$year";
        }
    }
    return strtotime($ped_date);
}

/**
 * Extract field description for regular users
 *
 * @param string $description
 * @return string|null
 */
function pedeu_extract_user_field_description(string $description)
{
    $parts = explode("---", $description);
    if (count($parts) > 1) {
        return htmlspecialchars(trim($parts[1]));
    }
    return null;
}

/**
 * Get metadata about form fields
 *
 * @param $form_fields array Gravity forms fields
 * @param $form_entry array Gravity forms entry
 * @param $context array
 * @return array
 */
function pedeu_count_form_meta($form_fields, $form_entry, $context)
{
    $metadata = array();
    $current_section = '';
    foreach ($form_fields as $form_field) {
        $type = $form_field->type;

        if ($type == "section") {
            $current_section = $form_field['id'];
            $metadata[$current_section] = array(
                'label' => $form_field['label'],
                'fields' => 0,
                'empty' => 0,
                'is_empty' => true,
            );
        } else {
            $metadata[$current_section]['fields'] += 1;
            if (pedeu_form_field_empty($form_field, $form_entry, $context)) {
                $metadata[$current_section]['empty'] += 1;
                $metadata[$form_field['id']] = array(
                    'is_empty' => true,
                );
            } else {
                $metadata[$current_section]['is_empty'] = false;
                $metadata[$form_field['id']] = array(
                    'is_empty' => false,
                );
            }
        }
    }
    return $metadata;
}


/**
 * Check whether a form field is marked as empty (value not specified)
 *
 * @param $form_field GF_Field Gravity forms field
 * @param $form_entry array Gravity forms entry
 * @param $context array
 * @return bool
 */
function pedeu_form_field_empty($form_field, $form_entry, $context): bool {
    $key = $form_field->id;
    $type = $form_field->type;

    if ($type == "text" || $type == "textarea" || $type == "number" || $type == "email") {
        return $form_entry[$key] === '';
    } else if ($type == "list") {
        return false;
    } else if ($type == "radio") {
        return $form_entry[$key] === '';
    } else if ($type == "multiselect" && ($form_field->cssClass == "form_reference_casestudy" || $form_field->cssClass == "form_reference_project")) {
        $entities = $context["projects"];
        if ($form_field->cssClass == "form_reference_casestudy") {
            $entities = $context["case-studies"];
        }
        $value = json_decode($form_entry[$key]);

        $items = array();
        foreach ($value as $item_id) {
            if (array_key_exists($item_id, $entities)) {
                $items[] = $entities[$item_id];
            }
        }

        return count($items) == 0;
    } else if ($type == "multiselect") {
        $value = json_decode($form_entry[$key]);
        $items = array();
        foreach ($form_field->choices as $choice) {
            $item_value = $choice['value'];
            $item_text = $choice['text'];

            if (in_array(strval($item_value), $value)) {
                $items[] = $item_text;
            }
        }

        return count($items) == 0;
    } else if ($type == "checkbox") {
        $inputs = $form_field->inputs;
        if (count($inputs) != 1) {
            $items = array();
            foreach ($inputs as $input) {
                $is_checked = $form_entry[$input['id']] != '';
                if ($is_checked) {
                    $items[] = $input['label'];
                }
            }

            return count($items) == 0;
        } else {
            return false;
        }
    } else if ($type == "select") {
        $value = $form_entry[$key];
        $any_value = false;
        foreach ($form_field->choices as $choice) {
            if (pedeu_streq($choice["value"], $value)) {
                $any_value = true;
                break;
            }
        }
        return !$any_value;
    } else if ($type == "fileupload") {
        $links = json_decode($form_entry[$key]);
        $any_value = false;
        foreach ($links as $index => $link) {
            $any_value = true;
            break;
        }
        return !$any_value;
    }
    return true;
}


/**
 * Render a table row for a form
 *
 * @param $form_field GF_Field Gravity forms field
 * @param $form_entry array Gravity forms entry
 * @param $compared_entries array Gravity forms entires for commpare
 * @param $context array
 * @param $meta array
 * @return string
 */
function pedeu_render_form_table_row($form_field, $form_entry, $compared_entries, $context, $meta): string
{
    $key = $form_field->id;
    $label = $form_field->label;
    $type = $form_field->type;
    $description = pedeu_extract_user_field_description($form_field->description);

    // check conditions
    $row_visibility = "visible";
    if ($form_field->conditionalLogic && $form_field->conditionalLogic["enabled"]) {
        $cond = $form_field->conditionalLogic;
        $truth_values = array();

        foreach ($cond["rules"] as $rule) {
            $field_id = $rule["fieldId"];
            $operator = $rule["operator"];
            $cmp_value = $rule["value"];
            $result = false;
            foreach ($form_entry as $target_key => $target_value) {
                if (explode(".", $target_key)[0] == $field_id) {
                    if ($operator == "is" && $target_value == $cmp_value) {
                        $result = true;
                        break;
                    }
                    if ($operator == "isnot" && $target_value != $cmp_value) {
                        $result = true;
                        break;
                    }
                    if ($operator == "contains" && str_contains($target_value, $cmp_value)) {
                        $result = true;
                        break;
                    }
                    if ($operator == "starts_with" && str_starts_with($target_value, $cmp_value)) {
                        $result = true;
                        break;
                    }
                    if ($operator == "ends_with" && str_ends_with($target_value, $cmp_value)) {
                        $result = true;
                        break;
                    }
                    if ($operator == ">" && $target_value > $cmp_value) {
                        $result = true;
                        break;
                    }
                    if ($operator == "<" && $target_value < $cmp_value) {
                        $result = true;
                        break;
                    }
                }
            }
            $truth_values[] = $result;
        }

        if ($cond["logicType"] == "all") {
            $truth_value = in_array(false, $truth_values, true) === false;
        } else {
            $truth_value = in_array(true, $truth_values, true);
        }

        if (($cond["actionType"] == "show" && !$truth_value) || ($cond["actionType"] == "hide" && $truth_value)) {
            $row_visibility = "hidden";
        }
    }

    // ignored form field types
    if ($type == "html" || $type == "page") {
        return "";
    }

    // render row
    $extra_classes = $meta[$form_field['id']]['is_empty'] ? 'pedeu-table-row-empty' : '';
    $output = "<tr class='pedeu-table-row pedeu-table-row-$type pedeu-table-row-$row_visibility $extra_classes' data-entry-key='$key'>";
    $unit = pedeu_extract_label_unit($label);

    $output .= "<td class='pedeu-table-cell-label'";
    if ($unit != null) {
        $output .= " data-unit-hide='$unit'";
    }
    if ($description != null) {
        $output .= " data-tooltip='$description'";
    }
    $output .= ">$label</td>";

    $output .= pedeu_render_form_table_data_cell($form_field, $form_entry, $unit, $context);
    foreach ($compared_entries as $compared_entry) {
        $output .= pedeu_render_form_table_data_cell($form_field, $compared_entry, $unit, $context);
    }
    $output .= "</tr>";
    return $output;
}


/**
 * Extract a unit from form field label
 *
 * @param $label string
 * @return string|null
 */
function pedeu_extract_label_unit($label): ?string
{
    $result = array();
    preg_match('/^.* \[(.*)]$/', $label, $result);
    if (count($result) == 2) {
        return $result[1];
    }
    return null;
}


/**
 * Render a single cell with form entry value of given field
 *
 * @param $form_field GF_Field Gravity forms field
 * @param $form_entry array Gravity forms entry
 * @param $unit string|null Unit of the value
 * @param $section_info array Metadata about sections
 * @return string
 */
function pedeu_render_form_table_data_cell($form_field, $form_entry, $unit, $context): string
{
    $key = $form_field->id;
    $type = $form_field->type;

    if ($type == "section") {
        return "<td></td>";
    }
    if ($type == "text" || $type == "textarea" || $type == "number" || $type == "email") {
        $value = $form_entry[$key];
        if ($value === '') {
            return "<td class='pedeu-not-specified'></td>";
        }
        $value = pedeu_replace_links($value);
        if ($unit) {
            return "<td data-unit='$unit'>$value</td>";
        }
        return "<td>$value</td>";
    } else if ($type == "list") {
        $value = unserialize($form_entry[$key]);
        $output = "<td><ul>";
        foreach ($value as $key => $item) {
            $item = pedeu_replace_links($item);
            $sep = $key === array_key_last($value) ? '' : ', ';
            $output .= "<li>$item<span class='pedeu-item-sep'>$sep</span></li>";
        }
        $output .= "</ul></td>";
        return $output;
    } else if ($type == "radio") {
        $value = $form_entry[$key];
        $choices = array();
        foreach ($form_field->choices as $choice) {
            $choices[$choice->value] = $choice->text;
        }
        if ($value == '') {
            return "<td class='pedeu-not-specified'></td>";
        } else if  (array_key_exists($value, $choices)){
            return "<td>$choices[$value]</td>";
        } else {
            return "<td data-note='legacy-value'>$value</td>";
        }
    } else if ($type == "multiselect" && ($form_field->cssClass == "form_reference_casestudy" || $form_field->cssClass == "form_reference_project")) {
        $entities = $context["projects"];
        if ($form_field->cssClass == "form_reference_casestudy") {
            $entities = $context["case-studies"];
        }
        $value = json_decode($form_entry[$key]);

        $items = array();
        foreach ($value as $item_id) {
            if (array_key_exists($item_id, $entities)) {
                $items[] = $entities[$item_id];
            }
        }

        if (count($items) == 0) {
            return "<td class='pedeu-not-specified'></td>";
        } else {
            $output = "<td><ul>";
            foreach ($items as $item) {
                $sep = $key === array_key_last($items) ? '' : ', ';
                $link = get_permalink($item["id"]);
                $title = $item["title"];
                $output .= "<li><a href='$link' target='_blank'>$title</a><span class='pedeu-item-sep'>$sep</span></li>";
            }
            $output .= "</ul></td>";
            return $output;
        }
    } else if ($type == "multiselect") {
        $value = json_decode($form_entry[$key]);
        $items = array();
        foreach ($form_field->choices as $choice) {
            $item_value = $choice['value'];
            $item_text = $choice['text'];

            if (in_array(strval($item_value), $value)) {
                $items[] = $item_text;
            }
        }

        if (count($items) == 0) {
            return "<td class='pedeu-not-specified'></td>";
        } else {
            $output = "<td>";
            $output .= "<ul>";
            foreach ($items as $key => $item) {
                $sep = $key === array_key_last($items) ? '' : ', ';
                $output .= "<li>$item<span class='pedeu-item-sep'>$sep</span></li>";
            }
            $output .= "</ul>";
        }

        $output .= "</td>";
        return $output;
    } else if ($type == "checkbox") {
        $inputs = $form_field->inputs;
        $output = "";
        if (count($inputs) == 1) {
            $is_checked = $form_entry[$inputs[0]['id']] != '';
            if ($is_checked) {
                $output .= "<td>yes</td>";
            } else {
                $output .= "<td>no</td>";
            }
        } else {
            $output .= "<td>";
            $items = array();
            foreach ($inputs as $input) {
                $is_checked = $form_entry[$input['id']] != '';
                if ($is_checked) {
                    $items[] = $input['label'];
                }
            }

            if (count($items) == 0) {
                return "<td class='pedeu-not-specified'></td>";
            } else {
                $output .= "<ul>";
                foreach ($items as $key => $item) {
                    $sep = $key === array_key_last($items) ? '' : ', ';
                    $output .= "<li>$item<span class='pedeu-item-sep'>$sep</span></li>";
                }
                $output .= "</ul>";
            }
            $output .= "</td>";
        }
        return $output;
    } else if ($type == "select") {
        $value = $form_entry[$key];
        foreach ($form_field->choices as $choice) {
            if (pedeu_streq($choice["value"], $value)) {
                $value = $choice["text"];
                return "<td>$value</td>";
            }
        }
        return "<td class='pedeu-not-specified'></td>";
    } else if ($type == "fileupload") {
        $links = json_decode($form_entry[$key]);
        $output = "<td><ul>";
        foreach ($links as $index => $link) {
            $n = $index + 1;
            $ext = strtolower(pathinfo($link, PATHINFO_EXTENSION));
            $sep = $index === array_key_last($links) ? '' : ', ';
            $output .= "<li><a href='$link' target='_blank' data-url-shorten='$link'>$link</a><span class='pedeu-item-sep'>$sep</span>";
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                $output .= "<br><a href='$link' target='_blank'><img src='$link' alt='Image $n'></a>";
            }
            $output .= "</li>";
        }
        $output .= "</ul></td>";
        return $output;
    }

    if (PED_EU_DB_DEV_MODE) {
        $v = print_r($form_field, true);
        $value = $form_entry[$key];
        return "<td style='color: red;'>Unknown type: $type <pre>$v</pre> <pre>$value</pre></td>";
    }
    return "<td data-info='unknown-type:$type'></td>";
}


/**
 * Get filters for case studies from GET query params
 *
 * @return array
 */
function pedeu_get_case_study_filters(): array
{
    $valid_phases = ["planning", "implementation", "completed", "in-operation"];
    $valid_types = ["case-study", "lab", "relevant-case-study"];

    return array(
        "phase" => isset($_GET["phase"]) && in_array($_GET["phase"], $valid_phases) ? $_GET["phase"] : null,
        "ped_type" => isset($_GET["ped_type"]) && in_array($_GET["ped_type"], $valid_types) ? $_GET["ped_type"] : null,
        "project" => isset($_GET["phase"]) ? $_GET["project"] : null,
    );
}


/**
 * Render case studies filters
 *
 * @param $case_study_filters array
 * @return string
 */
function pedeu_render_case_study_filters($case_study_filters): string
{
    $phase = $case_study_filters["phase"];
    $ped_type = $case_study_filters["ped_type"];
    $project = $case_study_filters["project"];

    // prepare types
    $ped_types = array(
        "case-study" => "PED Case Study",
        "lab" => "PED Lab",
        "relevant-case-study" => "PED Relevant Case Study",
    );
    $ped_type_options = "<option value=''>All Types</option>";
    foreach ($ped_types as $ped_type_key => $ped_type_title) {
        $is_selected = $ped_type == $ped_type_key ? " selected" : "";
        $ped_type_options .= "<option value='$ped_type_key'$is_selected>$ped_type_title</option>";
    }

    // prepare phases
    $phases = array(
        "planning" => "Planning Phase",
        "implementation" => "Implementation Phase",
        "completed" => "Completed",
        "in-operation" => "In Operation",
    );
    $phase_options = "<option value=''>All Phases</option>";
    foreach ($phases as $phase_key => $phase_title) {
        $is_selected = $phase == $phase_key ? " selected" : "";
        $phase_options .= "<option value='$phase_key'$is_selected>$phase_title</option>";
    }

    // prepare projects
    $project_options = "<option value=''>All Projects</option>";
    $all_projects = get_posts(array(
        "numberposts" => -1,
        "post_type" => "projects",
        "post_status" => "publish",
    ));
    foreach ($all_projects as $value) {
        $project_id = $value->ID;
        $entry_id = get_post_meta($project_id, "entry_id", true);
        if (!is_numeric($entry_id)) {
            continue;  # invalid entry
        }
        $entry = GFAPI::get_entry($entry_id);
        $project_details = pedeu_get_project_details($project_id, $entry);
        $project_title = $project_details["title"];
        $is_selected = $project == $project_id ? " selected" : "";
        $project_options .= "<option value='$project_id'$is_selected>$project_title</option>";
    }

    // prepare links
    $export_all_link = site_url("wp-json/ped-eu/v1/export-all/");
    $no_filter_link = get_permalink();

    // render
    return "
        <div class='ped-eu-filters-row'>
            <form class='ped-eu-filters' method='GET' action=''>
                <span>Filters:</span>
                <select name='ped_type' title='PED Type' onchange='this.form.submit()'>$ped_type_options</select>
                <select name='phase' title='Phase' onchange='this.form.submit()'>$phase_options</select>
                <select name='project' title='Project' onchange='this.form.submit()'>$project_options</select>
            </form>
            <div class='ped-eu-links'>
                <a href='$no_filter_link'>Clear Filters</a>
                <a href='$export_all_link'>Export All Entries (CSV)</a>
            </div>
        </div>
    ";
}


/**
 * Filter case studies by given filters
 *
 * @param $case_study_details array
 * @param $case_study_filters array
 * @return bool
 */
function pedeu_filter_case_study($case_study_details, $case_study_filters): bool
{
    $phase = $case_study_filters["phase"];
    $ped_type = $case_study_filters["ped_type"];
    $project = $case_study_filters["project"];

    // filter: type
    $ped_types = array(
        "case-study" => "is_ped_case_study",
        "lab" => "is_ped_lab",
        "relevant-case-study" => "is_ped_relevant_case_study",
    );
    foreach ($ped_types as $ped_type_key => $ped_type_check) {
        if ($ped_type == $ped_type_key && !$case_study_details[$ped_type_check]) {
            return false;
        }
    }

    // filter: phase
    $current_phase = $case_study_details["phase"];
    $phases = array(
        "planning" => "Planning Phase",
        "implementation" => "Implementation Phase",
        "completed" => "Completed",
        "in-operation" => "In Operation",
    );
    if ($phase != "" && !pedeu_streq($current_phase, $phases[$phase])) {
        return false;
    }

    // filter: projects
    if ($project != "") {
        $match = false;
        foreach ($case_study_details["project_ids"] as $vvalue) {
            $vvalue = str_replace('"', '', $vvalue);
            if (is_numeric($vvalue)) {
                if ($vvalue == $project) {
                    $match = true;
                }
            }
        }
        if (!$match) {
            return false;
        }
    }

    return true;
}


/**
 * Swap two elements of an array (in-place)
 *
 * @param $array
 * @param $swap_a
 * @param $swap_b
 */
function pedeu_array_swap(&$array, $swap_a, $swap_b): void
{
    list($array[$swap_a], $array[$swap_b]) = array($array[$swap_b], $array[$swap_a]);
}


/**
 * Replace links in a text to HTML
 *
 * @see https://gist.github.com/AlexPashley/5861213
 * @param $text string
 * @return false|string
 */
function pedeu_replace_links($text): string
{
    $text = preg_replace('#(script|about|applet|activex|chrome):#is', "\\1:", $text);
    $ret = ' ' . $text;
    // Replace Links with http://
    $ret = preg_replace("#(^|[\n ])([\w]+?://[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"\\2\" target=\"_blank\" rel=\"nofollow\">\\2</a>", $ret);
    // Replace Links without http://
    $ret = preg_replace("#(^|[\n ])((www|ftp)\.[\w\#$%&~/.\-;:=,?@\[\]+]*)#is", "\\1<a href=\"http://\\2\" target=\"_blank\" rel=\"nofollow\">\\2</a>", $ret);
    // Replace Email Addresses
    $ret = preg_replace("#(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)#i", "\\1<a href=\"mailto:\\2@\\3\">\\2@\\3</a>", $ret);
    $ret = substr($ret, 1);
    return $ret;
}


/**
 * Compare if strings should be considered as equal
 *
 * @param $string1 string
 * @param $string2 string
 * @return bool
 */
function pedeu_streq(string $string1, string $string2): bool
{
    return strcasecmp(trim($string1), trim($string2)) == 0;
}


/**
 * Get a UUID
 *
 * @param string $s separator
 * @return string
 */
function pedeu_get_uuid($s = ''): string
{
    if (function_exists('openssl_random_pseudo_bytes')) {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf("%s%s{$s}%s{$s}%s{$s}%s{$s}%s%s%s", str_split(bin2hex($data), 4));
    } else {
        return sprintf(
            "%04x%04x{$s}%04x{$s}%04x{$s}%04x{$s}%04x%04x%04x",
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
