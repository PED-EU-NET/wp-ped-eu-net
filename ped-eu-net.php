<?php
/**
 * Plugin Name:       PED-EU-NET
 * Plugin URI:        https://github.com/PED-EU-NET/wp-ped-eu-net
 * Description:       Functionality specific for PEDEU.net website and PED Database.
 * Version:           1.3.0
 * Requires at least: 6.1
 * Requires PHP:      7.2
 * Author:            PED-EU-NET Team
 * Author URI:        https://pedeu.net
 * License:           MIT
 */

// If this file is called directly, abort.
if (!defined("WPINC")) {
    die;
}

const PED_EU_WP_OPTION_EMAILS = "pedeue_invite_emails";
const PED_EU_VERSION = "1.3.0";
define("PED_EU_ROOT_PATH", plugin_dir_path(__FILE__));
define("PED_EU_ROOT_URL", plugin_dir_url(__FILE__));

// These should be able to change in admin UI one day:
// - Page IDs
const PED_EU_FORM_PROJECT_PID = 1549;
const PED_EU_FORM_CASE_STUDY_PID = 1573;
// - Form IDs (GravityForms)
const PED_EU_FORM_PROJECT_GF_ID = 2;
const PED_EU_FORM_CASE_STUDY_GF_ID = 1;

const PED_EU_SHORTCODE_CS_MAP = "pedeu_case_studies_map";
const PED_EU_SHORTCODE_CS_TABLE = "pedeu_case_studies_table";
const PED_EU_SHORTCODE_PR_TABLE = "pedeu_projects_table";
const PED_EU_SHORTCODE_OVERVIEW = "pedeu_overview";
const PED_EU_SHORTCODE_VISUALIZATIONS = "pedeu_visualizations";
const PED_EU_SHORTCODE_FORM_DIAGRAM = "pedeu_form_diagram";
// Unfortunately role name is hard to change
const PED_EU_DB_EDITOR_ROLE = "pedeue_editor";
const PED_EU_GF_DRAFT_DAYS = 90;

const PED_EU_DB_DEV_MODE = false;

include_once PED_EU_ROOT_PATH . "includes/utils.php";
include_once PED_EU_ROOT_PATH . "includes/bootstrap.php";
include_once PED_EU_ROOT_PATH . "includes/security.php";
include_once PED_EU_ROOT_PATH . "includes/admin.php";
include_once PED_EU_ROOT_PATH . "includes/forms.php";
include_once PED_EU_ROOT_PATH . "includes/case-studies.php";
include_once PED_EU_ROOT_PATH . "includes/projects.php";
include_once PED_EU_ROOT_PATH . "includes/visualizations.php";
include_once PED_EU_ROOT_PATH . "includes/api/api.php";
