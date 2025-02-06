<?php

include_once PED_EU_ROOT_PATH . "includes/api/form-helpers.php";


/**
 * Fetches all projects from the database and returns them as JSON.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function pedeu_api_projects_list($request)
{
    $args = array(
        "numberposts" => -1,
        "post_type" => "projects",
        "meta_query" => array(),
        "post_status" => "publish",
    );

    $all_projects = get_posts($args);

    $result = array();

    foreach ($all_projects as $value) {
        $id = $value->ID;
        $entry_id = get_post_meta($id, "entry_id", true);
        if (!is_numeric($entry_id)) {
            continue;  # invalid entry
        }

        $entry = GFAPI::get_entry($entry_id);
        $data = pedeu_api_form_entry_to_data($entry);
        $data["postId"] = $id;
        $result[] = $data;
    }

    return rest_ensure_response($result);
}


/**
 * Fetches metadata for projects form.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function pedeu_api_projects_meta($request)
{
    $meta = pedeu_api_form_to_meta(PED_EU_FORM_PROJECT_GF_ID);
    return rest_ensure_response($meta);
}


/**
 * Fetches a single project from the database and returns it as JSON.
 *
 * @param WP_REST_Request $request
 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
 */
function pedeu_api_projects_get($request)
{
    $id = $request->get_param("id");

    $entry_id = get_post_meta($id, "entry_id", true);
    if (!is_numeric($entry_id)) {
        return new WP_Error("invalid_entry", "Invalid entry", array("status" => 404));
    }

    $entry = GFAPI::get_entry($entry_id);
    return rest_ensure_response(pedeu_api_form_entry_to_data($entry));
}

/**
 * Fetches all case studies from the database and returns them as JSON.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function pedeu_api_case_studies_list($request)
{
    $args = array(
        "numberposts" => -1,
        "post_type" => "case-studies",
        "meta_query" => array(),
        "post_status" => "publish",
    );

    $all_projects = get_posts($args);

    $result = array();

    foreach ($all_projects as $value) {
        $id = $value->ID;
        $entry_id = get_post_meta($id, "entry_id", true);
        if (!is_numeric($entry_id)) {
            continue;  # invalid entry
        }

        $entry = GFAPI::get_entry($entry_id);
        $data = pedeu_api_form_entry_to_data($entry);
        $data["postId"] = $id;
        $result[] = $data;
    }

    return rest_ensure_response($result);
}


/**
 * Fetches metadata for case studies form.
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response
 */
function pedeu_api_case_studies_meta($request)
{
    $meta = pedeu_api_form_to_meta(PED_EU_FORM_CASE_STUDY_GF_ID);
    return rest_ensure_response($meta);
}


/**
 * Fetches a single case study from the database and returns it as JSON.
 *
 * @param WP_REST_Request $request
 * @return WP_Error|WP_HTTP_Response|WP_REST_Response
 */
function pedeu_api_case_studies_get($request)
{
    $id = $request->get_param("id");

    $entry_id = get_post_meta($id, "entry_id", true);
    if (!is_numeric($entry_id)) {
        return new WP_Error("invalid_entry", "Invalid entry", array("status" => 404));
    }

    $entry = GFAPI::get_entry($entry_id);
    $data = pedeu_api_form_entry_to_data($entry);
    $data["postId"] = $id;
    return rest_ensure_response($data);
}

function pedeu_api_everything_get($request) {
    $case_studies = pedeu_api_case_studies_list($request)->data;
    $projects = pedeu_api_projects_list($request)->data;
    return rest_ensure_response(array(
        "caseStudies" => array(
            "meta" => pedeu_api_case_studies_meta($request)->data,
            "data" => $case_studies,
        ),
        "projects" => array(
            "meta" => pedeu_api_projects_meta($request)->data,
            "data" => $projects,
        ),
    ));
}


/**
 * Register CSV download API endpoint
 */
function pedeu_register_rest_api_endpoints()
{
    register_rest_route("ped-eu/v1", "/projects", array(
        "methods" => WP_REST_Server::READABLE,
        "callback" => "pedeu_api_projects_list",
    ));
    register_rest_route("ped-eu/v1", "/projects/meta", array(
        "methods" => WP_REST_Server::READABLE,
        "callback" => "pedeu_api_projects_meta",
    ));
    register_rest_route("ped-eu/v1", "/projects/(?P<id>[\d]+)", array(
        "methods" => WP_REST_Server::READABLE,
        "callback" => "pedeu_api_projects_get",
    ));

    register_rest_route("ped-eu/v1", "/case-studies", array(
        "methods" => WP_REST_Server::READABLE,
        "callback" => "pedeu_api_case_studies_list",
    ));
    register_rest_route("ped-eu/v1", "/case-studies/meta", array(
        "methods" => WP_REST_Server::READABLE,
        "callback" => "pedeu_api_case_studies_meta",
    ));
    register_rest_route("ped-eu/v1", "/case-studies/(?P<id>[\d]+)", array(
        "methods" => WP_REST_Server::READABLE,
        "callback" => "pedeu_api_case_studies_get",
    ));

    register_rest_route("ped-eu/v1", "/everything", array(
        "methods" => WP_REST_Server::READABLE,
        "callback" => "pedeu_api_everything_get",
    ));
}

add_action("rest_api_init", "pedeu_register_rest_api_endpoints");
