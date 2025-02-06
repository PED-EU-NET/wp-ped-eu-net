<?php

const PED_EU_CAPS_MASK_EDITOR = 0b10;
const PED_EU_CAPS_MASK_ADMIN = 0b01;

const PED_EU_CAPS = array(
    //* Meta capabilities
    "read" => 0b10,
    "edit_cap_db_edit" => 0b10,
    "read_cap_db_edit" => 0b10,
    "delete_cap_db_edit" => 0b10,
    //* Primitive capabilities used outside of map_meta_cap()
    "edit_cap_db_edits" => 0b11,
    "edit_others_cap_db_edits" => 0b11,
    "publish_cap_db_edits" => 0b11,
    "read_private_cap_db_edits" => 0b11,
    //* Primitive capabilities used within of map_meta_cap()
    "delete_cap_db_edits" => 0b11,
    "delete_private_cap_db_edits" => 0b11,
    "delete_published_cap_db_edits" => 0b11,
    "delete_others_cap_db_edits" => 0b11,
    "edit_private_cap_db_edits" => 0b11,
    "edit_published_cap_db_edits" => 0b11,
    //* GravityForms permissions
    "gravityforms_view_entries" => 0b10,
    "gravityforms_edit_entries" => 0b10,
    "gravityforms_delete_entries" => 0b10,
    "gravityview_moderate_entries" => 0b10,
);


/**
 * Adjust permissions when plugin is loaded
 */
function pedeu_init_callback()
{
    $admin_role = get_role("administrator");
    $db_editor_role = get_role(PED_EU_DB_EDITOR_ROLE);

    foreach (PED_EU_CAPS as $cap => $mask) {
        if ($mask & PED_EU_CAPS_MASK_ADMIN) {
            $admin_role->add_cap($cap);
        }
        if ($mask & PED_EU_CAPS_MASK_EDITOR) {
            $db_editor_role->add_cap($cap);
        }
    }
}

add_action("plugins_loaded", "pedeu_init_callback");


/**
 * The code that runs during plugin activation.
 * It creates a role "pedeue_editor" (Database Editor) with appropriate
 * permissions to edit the database entries.
 */
function pedeu_plugin_activation()
{
    $caps = array();
    foreach (PED_EU_CAPS as $cap => $mask) {
        if ($mask & PED_EU_CAPS_MASK_EDITOR) {
            $caps[$cap] = true;
        }
    }
    add_role(PED_EU_DB_EDITOR_ROLE, "PED DB Editor", $caps);
}

register_activation_hook(__FILE__, "pedeu_plugin_activation");


/**
 * Require correctly doing AJAX.
 */
function pedeu_redirect_sub_callback()
{
    if (is_admin() && current_user_can("subscriber") && !(defined("DOING_AJAX") && DOING_AJAX)) {
        wp_redirect(home_url());
        exit;
    }
}

add_action("init", "pedeu_redirect_sub_callback");


/**
 * Add styles and scripts used by the plugin.
 */
function pedeu_enqueue_script(): void
{
    global $post;

    $is_casestudy_map = is_a($post, "WP_Post") && has_shortcode($post->post_content, PED_EU_SHORTCODE_CS_MAP);
    $is_casestudy_table = is_a($post, "WP_Post") && has_shortcode($post->post_content, PED_EU_SHORTCODE_CS_TABLE);
    $is_project_table = is_a($post, "WP_Post") && has_shortcode($post->post_content, PED_EU_SHORTCODE_PR_TABLE);

    $has_form_diagram = is_a($post, "WP_Post") && has_shortcode($post->post_content, PED_EU_SHORTCODE_FORM_DIAGRAM);

    $has_overview = is_a($post, "WP_Post") && has_shortcode($post->post_content, PED_EU_SHORTCODE_OVERVIEW);
    $has_visualizations = is_a($post, "WP_Post") && has_shortcode($post->post_content, PED_EU_SHORTCODE_VISUALIZATIONS);
    $has_charts = $has_overview || $has_visualizations;

    if ($is_casestudy_map) {
        // leaflet (map)
        wp_enqueue_script("leaflet-js", PED_EU_ROOT_URL . "static/js/leaflet.js", array(), "1.9.4");
        wp_enqueue_style("leaflet-css", PED_EU_ROOT_URL . "static/css/leaflet.css", array(), "1.9.4", "all");
        wp_enqueue_script("leaflet-markercluster-js", PED_EU_ROOT_URL . "static/js/leaflet.markercluster.js", array(), "1.4.1");
        wp_enqueue_style("leaflet-markercluster-css", PED_EU_ROOT_URL . "static/css/leaflet.markercluster.css", array(), "1.4.1", "all");

        $ped_data = pedeu_map_script_data();
        wp_register_script("ped-eu-map-js", PED_EU_ROOT_URL . "static/js/map.js", array("jquery"), PED_EU_VERSION, true);
        wp_localize_script("ped-eu-map-js", "ped_data", $ped_data);
        wp_enqueue_script("ped-eu-map-js");
    }

    if ($is_casestudy_table || $is_project_table || $is_casestudy_map) {
        // datatables
        wp_enqueue_script("datatables-js", PED_EU_ROOT_URL . "static/js/datatables.min.js", array("jquery"), "1.13.4", false);
        wp_enqueue_style("datatables-css", PED_EU_ROOT_URL . "static/css/datatables.min.css", array(), "1.23.4", "all");

        wp_enqueue_script("ped-eu-tables-js", PED_EU_ROOT_URL . "static/js/tables.js", array("jquery"), PED_EU_VERSION, true);
    }

    if ($has_form_diagram) {
        wp_enqueue_script("ped-eu-forms-js", PED_EU_ROOT_URL . "static/js/forms.js", array("jquery"), PED_EU_VERSION, true);
    }

    if ($has_charts) {
        wp_enqueue_style("visualizations-css", PED_EU_ROOT_URL . "static/css/visualizations.css", array(), "1.23.4", "all");
        wp_enqueue_script("ped-eu-chart-js", PED_EU_ROOT_URL . "static/js/chart.umd.min.js", array("jquery"), "4.4.3", true);
        wp_enqueue_script("ped-eu-chart-sankey-js", PED_EU_ROOT_URL . "static/js/chartjs-chart-sankey.js", array("jquery"), "0.12.1", true);
        wp_enqueue_script("ped-eu-chart-geo-js", PED_EU_ROOT_URL . "static/js/chartjs-chart-geo.min.js", array("jquery"), "4.3.1", true);
    }
    if ($has_overview) {
        wp_enqueue_script("ped-eu-overview-js", PED_EU_ROOT_URL . "static/js/overview.js", array("jquery"), PED_EU_VERSION, true);
    }
    if ($has_visualizations) {
        wp_enqueue_script("ped-eu-visualizations-js", PED_EU_ROOT_URL . "static/js/visualizations.js", array("jquery"), PED_EU_VERSION, true);
    }

    // dropdown
    wp_enqueue_script("filterDropDown-js", PED_EU_ROOT_URL . "static/js/filterDropDown.min.js", array("jquery"), "1.0.0", false);

    // general
    wp_enqueue_style("ped-eu-public-css", PED_EU_ROOT_URL . "static/css/public.css", array(), PED_EU_VERSION, "all");
}

add_action("wp_enqueue_scripts", "pedeu_enqueue_script");


/**
 * Prepare data for map.js script
 *
 * @return array
 */
function pedeu_map_script_data(): array
{
    $all_cases = get_posts(array(
        "numberposts" => -1,
        "post_type" => "case-studies",
        "meta_query" => array(),
        "post_status" => "publish",
    ));
    $case_posts = array();
    $filters = pedeu_get_case_study_filters();

    foreach ($all_cases as $value) {
        $id = $value->ID;
        $entry_id = get_post_meta($id, "entry_id", true);
        $entry = GFAPI::get_entry($entry_id);
        if (is_wp_error($entry)) {
            continue;  // invalid entry
        }
        $details = pedeu_get_case_study_details($id, $entry);

        if (!pedeu_filter_case_study($details, $filters)) {
            continue;
        }
        $case_posts[] = $details;
    }

    return array(
        "plugin_url" => PED_EU_ROOT_URL,
        "posts" => json_encode($case_posts),
        "site_url" => site_url(),
    );
}
