<?php


/**
 * Render PED EU table view of projects.
 *
 * @return string rendered content
 */
function pedeu_projects_table(): string
{
    $args = array(
        "numberposts" => -1,
        "post_type" => "projects"
    );

    $all_projects = get_posts($args);

    $output = "
        <table id='ped-eu-projects-table'>
            <thead>
                <tr><td>Project Name</td><td>Project Code</td><td>Start Date</td><td>End Date</td></tr>
            </thead>
            <tbody>
    ";

    foreach ($all_projects as $value) {
        $id = $value->ID;
        $entry_id = get_post_meta($id, "entry_id", true);
        if (!is_numeric($entry_id)) {
            continue;  # invalid entry
        }
        $entry = GFAPI::get_entry($entry_id);
        $details = pedeu_get_project_details($id, $entry);
        $start_date = date("m/Y", $details['start_date']);
        $end_date = date("m/Y", $details['end_date']);
        $output .= "
            <tr>
                <td><a href='?id={$id}'>{$details['title']}</a></td>
                <td>{$details['code']}</td>
                <td>$start_date</td>
                <td>$end_date</td>
            </tr>
        ";
    }

    $output .= "
            </tbody>
        </table>
    ";

    // TODO: move context upper and use it for the table
    $context = pedeu_get_table_context();

    if (isset($_GET["id"])) {
        $id = esc_attr($_GET["id"]);
        $output .= pedeu_project_single($id, $context);
    }

    return $output;
}

add_shortcode(PED_EU_SHORTCODE_PR_TABLE, "pedeu_projects_table");


/**
 * Render table for a single project
 *
 * @param $id
 * @param $context
 * @return string
 */
function pedeu_project_single($id, $context): string
{
    $entry_id = get_post_meta($id, "entry_id", true);
    $entry = GFAPI::get_entry($entry_id);
    $form_id = $entry["form_id"];
    $this_form = GFAPI::get_form($form_id);
    $form_fields = $this_form["fields"];

    $output = "<table id='ped-eu-project-table'><tbody>";

    foreach ($form_fields as $form_field) {
        $key = $form_field->id;
        $label = $form_field->label;
        $type = $form_field->type;

        // check conditions
        if ($form_field->conditionalLogic && $form_field->conditionalLogic['enabled']) {
            $cond = $form_field->conditionalLogic;
            $truth_values = array();

            foreach ($cond['rules'] as $rule) {
                $field_id = $rule['fieldId'];
                $operator = $rule['operator'];
                $cmp_value = $rule['value'];
                $result = false;
                foreach ($entry as $target_key => $target_value) {
                    if (explode('.', $target_key)[0] == $field_id) {
                        if ($operator == 'is' && $target_value == $cmp_value) {
                            $result = true;
                            break;
                        }
                        if ($operator == 'isnot' && $target_value != $cmp_value) {
                            $result = true;
                            break;
                        }
                        if ($operator == 'contains' && str_contains($target_value, $cmp_value)) {
                            $result = true;
                            break;
                        }
                        if ($operator == 'starts_with' && str_starts_with($target_value, $cmp_value)) {
                            $result = true;
                            break;
                        }
                        if ($operator == 'ends_with' && str_ends_with($target_value, $cmp_value)) {
                            $result = true;
                            break;
                        }
                        if ($operator == '>' && $target_value > $cmp_value) {
                            $result = true;
                            break;
                        }
                        if ($operator == '<' && $target_value < $cmp_value) {
                            $result = true;
                            break;
                        }
                    }
                }
                $truth_values[] = $result;
            }

            if ($cond['logicType'] == 'all') {
                $truth_value = pedeu_in_array(false, $truth_values, true) === false;
            } else {
                $truth_value = pedeu_in_array(true, $truth_values, true);
            }

            if (($cond['actionType'] == 'show' && !$truth_value) || ($cond['actionType'] == 'hide' && $truth_value)) {
                continue;
            }
        }

        // render based on type
        if ($type == "html") {
            continue;
        } else if ($type == "section") {
            $output .= "<tr><td colspan='2' style='font-weight: bold' data-entry-key='$key'>$label</td></tr>";
        } else {
            $unit = pedeu_extract_label_unit($label);
            $cell = pedeu_render_form_table_data_cell($form_field, $entry, $unit, $context);
            $output .= "<tr><td data-entry-key='$key'>$label</td>$cell</tr>";
        }
    }
    $output .= "</tbody></table>";
    return $output;
}
