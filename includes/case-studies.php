<?php


/**
 * Table view of case studies.
 *
 * @return string
 */
function pedeu_case_studies_table(): string
{
    $case_study_id = isset($_GET["case_study"]) ? esc_attr($_GET["case_study"]) : "";
    $compare_ids = isset($_GET["compare"]) ? array_filter(explode(".", esc_attr($_GET["compare"]))) : array();

    $all_cases = get_posts(array(
        "numberposts" => -1,
        "post_type" => "case-studies",
        "meta_query" => array(),
        "post_status" => "publish",
    ));

    $filters = pedeu_get_case_study_filters();
    $html_filters = pedeu_render_case_study_filters($filters);
    $compare_head = isset($_GET["case_study"]) ? "<th>Compare</th>" : "";

    $output = "
        $html_filters
        <table id='ped-eu-case-studies-table'>
            <thead><tr><th>Name</th><th>Project</th><th>Type</th>$compare_head</tr></thead>
            <tbody>
        ";

    foreach ($all_cases as $value) {
        $id = $value->ID;
        $title = get_the_title($id);
        $entry_id = get_post_meta($id, "entry_id", true);
        $entry = GFAPI::get_entry($entry_id);
        if (is_wp_error($entry)) {
            continue;  // invalid entry
        }
        $details = pedeu_get_case_study_details($id, $entry);

        if (!pedeu_filter_case_study($details, $filters)) {
            continue;
        }

        $query_params = array_filter(array(
            "ped_type" => isset($_GET["ped_type"]) ? esc_attr($_GET["ped_type"]) : "",
            "phase" => isset($_GET["phase"]) ? esc_attr($_GET["phase"]) : "",
            "project" => isset($_GET["project"]) ? esc_attr($_GET["project"]) : "",
            "case_study" => $id,
        ));
        $this_link = add_query_arg($query_params, get_the_permalink());
        $comp_class = in_array($id, $compare_ids) ? "compared" : "";

        $project_titles = array();
        foreach ($details["project_ids"] as $project_id) {
            if (get_post_status($project_id) === false) {
                continue;
            }
            $project_titles[] = get_the_title($project_id);
        }
        $project_cell = implode(", ", $project_titles);

        $ped_case_study = $details["is_ped_case_study"] ? " PED Case Study /" : "";
        $ped_rel_study = $details["is_ped_relevant_case_study"] != "" ? " PED Relevant Case Study /" : "";
        $ped_lab = $details["is_ped_lab"] != "" ? " PED Lab /" : "";
        $ped_string = $ped_case_study . $ped_rel_study . $ped_lab;
        $ped_string = substr($ped_string, 0, -1);

        $compare_cell = "";
        if (isset($_GET["case_study"])) {
            $compare_params = array("case_study" => $case_study_id);

            if (in_array($id, $compare_ids)) {
                $compare_ids_new = array_filter($compare_ids, function ($x) use ($id) { return $x != $id; });
                $compare_params["compare"] = implode(".", $compare_ids_new);
                $compare_link = add_query_arg($compare_params, $this_link);

                $compare_cell = "<td><a href='{$compare_link}'>Uncompare</a></td>";
            } else if ($id != $case_study_id) {
                $compare_ids[] = $id;
                $compare_params["compare"] = implode(".", $compare_ids);
                array_pop($compare_ids);
                $compare_link = add_query_arg($compare_params, $this_link);

                $compare_cell = "<td><a href='{$compare_link}'>Compare</a></td>";
            } else {
                $compare_cell = "<td></td>";
            }
        }

        $output .= "
            <tr class='$comp_class'>
                <td><a href='$this_link'>$title</a></td>
                <td>$project_cell</td>
                <td>$ped_string</td>
                $compare_cell
            </tr>
        ";
    }

    $output .= "</tbody></table>";

    // TODO: move context upper and use it for the table
    $context = pedeu_get_table_context();

    if (isset($_GET["case_study"]) && $_GET["case_study"] != "") {
        $output = pedeu_case_study_single($output, $this_link, $context);
    }

    return $output;
}

add_shortcode(PED_EU_SHORTCODE_CS_TABLE, "pedeu_case_studies_table");


function pedeu_case_study_single($output, $this_link, $context): string
{
    $pid = isset($_GET["case_study"]) ? esc_attr($_GET["case_study"]) : "";
    $data = pedeu_get_form_data($pid);

    $compare_ids = isset($_GET["compare"]) ? array_filter(explode(".", esc_attr($_GET["compare"]))) : array();
    $compares = array();
    foreach ($compare_ids as $compare_id) {
        $compare_data = pedeu_get_form_data($compare_id);
        if (array_key_exists("3", $compare_data)) {
            $compare_data["id"] = $compare_id;
            $compares[] = $compare_data;
        }
    }
    $compare_index = 0;
    $compares_count = count($compares);

    $table_class = $compares_count > 0 ? "pedeu-table-compared" : "pedeu-table-single";
    $output .= "<table id='ped-eu-case-study-table' class='$table_class'><thead><tr>";
    $current_title = $data["3"];

    $output .= "<th>Title</th><th>$current_title</th>";

    foreach ($compares as $compare_data) {
        $compare_id = $compare_data["id"];
        $compare_title = $compare_data["3"];
        $output .= "<th><div class='pedeu-th-title-row'>$compare_title</div><div class='pedeu-column-buttons'>";

        if ($compare_index > 0) {
            $compare_left_params = array("case_study" => $pid);
            $compare_left_ids_new = $compare_ids;
            pedeu_array_swap($compare_left_ids_new, $compare_index-1, $compare_index);
            $compare_left_params["compare"] = implode(".", $compare_left_ids_new);
            $compare_left_link = add_query_arg($compare_left_params, $this_link);
            $output .= "<a class='pedeu-column-action-left' data-item-id='$compare_id' href='$compare_left_link'>&laquo;</a>";
        } else {
            $output .= "<a class='pedeu-column-action-left pedeu-column-action-disabled'>&laquo;</a>";
        }

        $uncompare_params = array("case_study" => $pid);
        $uncompare_ids_new = array_filter($compare_ids, function ($x) use ($compare_id) { return $x != $compare_id; });
        $uncompare_params["compare"] = implode(".", $uncompare_ids_new);
        $uncompare_link = add_query_arg($uncompare_params, $this_link);
        $output .= "<a class='pedeu-column-action-close' href='$uncompare_link'>&times;</a>";

        if ($compare_index < $compares_count-1) {
            $compare_right_params = array("case_study" => $pid);
            $compare_right_ids_new = $compare_ids;
            pedeu_array_swap($compare_right_ids_new, $compare_index, $compare_index+1);
            $compare_right_params["compare"] = implode(".", $compare_right_ids_new);
            $compare_right_link = add_query_arg($compare_right_params, $this_link);
            $output .= "<a class='pedeu-column-action-right' href='$compare_right_link'>&raquo;</a>";
        } else {
            $output .= "<a class='pedeu-column-action-right pedeu-column-action-disabled'>&raquo;</a>";
        }

        $output .= "</div></th>";
        $compare_index++;
    }
    $output .= "</tr></thead><tbody>";

    $entry_id = get_post_meta($pid, "entry_id", true);
    $entry = GFAPI::get_entry($entry_id);
    $form_id = $entry["form_id"];
    $this_form = GFAPI::get_form($form_id);
    $form_fields = $this_form["fields"];

    $meta = pedeu_count_form_meta($form_fields, $entry, $context);
    foreach ($form_fields as $form_field) {
        $output .= pedeu_render_form_table_row($form_field, $entry, $compares, $context, $meta);
    }

    $output .= "</tbody></table>";
    return $output;
}


/**
 * Render PED EU map view of case studies.
 *
 * @return string rendered content
 */
function pedeu_case_studies_map(): string
{
    $case_study_id = isset($_GET["case_study"]) ? esc_attr($_GET["case_study"]) : "";

    $filters = pedeu_get_case_study_filters();
    $html_filters = pedeu_render_case_study_filters($filters);

    $output = $html_filters;

    $img_folder = PED_EU_ROOT_URL . "static/images/map/";
    $output .= "
        <div id='ped-eu-map'></div>
        <div id='ped-eu-map-legend'>
            <span><img src='{$img_folder}ped-lab.svg' alt='PED Lab pin'>PED Lab</span>
            <span><img src='{$img_folder}ped-case-study.svg' alt='PED Case Study pin'>PED Case Study</span>
            <span><img src='{$img_folder}ped-relevant.svg' alt='PED Relevant Case Study pin'>PED Relevant Case Study</span>
            <span><img src='{$img_folder}ped-combined.svg' alt='Combined PED pin'>Both PED Lab / PED Relevant Case Study</span>
        </div>";

    $query_params = array_filter(array(
        "ped_type" => isset($_GET["ped_type"]) ? esc_attr($_GET["ped_type"]) : "",
        "phase" => isset($_GET["phase"]) ? esc_attr($_GET["phase"]) : "",
        "project" => isset($_GET["project"]) ? esc_attr($_GET["project"]) : "",
        "case_study" => $case_study_id,
    ));
    $this_link = add_query_arg($query_params, get_the_permalink());

    if (isset($_GET["case_study"]) && $_GET["case_study"] != "") {
        $context = pedeu_get_table_context();
        $output = pedeu_case_study_single($output, $this_link, $context);
    }
    return $output;
}

add_shortcode(PED_EU_SHORTCODE_CS_MAP, "pedeu_case_studies_map");


/**
 * Export case studies as CSV
 */
function pedeu_export_all_studies()
{
    $form = GFAPI::get_form(PED_EU_FORM_CASE_STUDY_GF_ID);
    $entry = GFAPI::get_entries(PED_EU_FORM_CASE_STUDY_GF_ID);

    $csv_arr = array();

    foreach ($entry as $ent) {
        foreach ($ent as $key => $single) {
            if (is_numeric($key)) {
                $label = pedeu_get_form_field_label($form, $key);
                if (is_string($label)) {
                    $csv_arr[0][] = $label;
                }

            }
        }
        break;
    }

    $count = 1;
    foreach ($entry as $ent) {
        foreach ($ent as $key => $single) {
            $created_posts = gform_get_meta($single["id"], "gravityformsadvancedpostcreation_post_id");
            foreach ($created_posts as $post) {
                $pid = $post["post_id"];
                break;
            }
            if (get_post_status($pid) != "publish") {
                // break;
            }
            if (is_numeric($key)) {
                $csv_arr[$count][] = $single;
            }
        }
        $count++;
    }

    pedeu_array_to_csv_download($csv_arr);
}


/**
 * Start download CSV as a stream
 */
function pedeu_array_to_csv_download($array, $filename = "export.csv", $delimiter = ",")
{
    header("Content-Type: application/csv");
    header("Content-Disposition: attachment; filename=\"$filename\";");

    // open the "output" stream
    // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
    $f = fopen("php://output", "w");

    foreach ($array as $line) {
        fputcsv($f, $line, $delimiter);
    }
}


/**
 * Register CSV download API endpoint
 */
function pedeu_register_rest_csv_download()
{
    //https://pedeu.net/wp-json/pedeue/v1/exportAll/
    register_rest_route("ped-eu/v1", "/export-all", array(
        "methods" => "GET",
        "callback" => "pedeu_export_all_studies",
    ));
}

add_action("rest_api_init", "pedeu_register_rest_csv_download");
