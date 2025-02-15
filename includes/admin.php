<?php


/**
 * Menu item in administration for accessing PED DB Editor.
 */
function pedeu_dbeditor_page()
{
    add_menu_page(
        __("DB Editor Settings", "pedeu"),
        "DB Editor",
        "read",
        "db_editor_settings",
        "pedeu_dbeditor_page_callback",
        "dashicons-database",
        6
    );
}

add_action("admin_menu", "pedeu_dbeditor_page");


/**
 * Render DB Editor admin page.
 */
function pedeu_dbeditor_page_callback()
{
    include PED_EU_ROOT_PATH . "includes/admin-db-editor-page.php";
}


/**
 * Change edit links to projects and case studies from posts to
 * the corresponding GForms.
 *
 * @param $link
 * @param $post_id
 * @return mixed|string
 */
function pedeu_remove_get_edit_post_link($link, $post_id)
{
    $type = get_post_type($post_id);
    //https://pedeu.net/wp-admin/admin.php?page=gf_entries&view=entry&id=1&lid=22&orderby=3&order=ASC&filter&paged=1&pos=1&field_id&operator
    if ($type == "case-studies") {
        $entry_id = get_post_meta($post_id, "entry_id", true);
        if (is_numeric($entry_id)) {
            $link = site_url("wp-admin/admin.php?page=gf_entries&view=entry&id=1&lid={$entry_id}&orderby=3&order=ASC&filter&paged=1&pos=1&field_id&operator");
        }
    } elseif ($type == "projects") {
        $entry_id = get_post_meta($post_id, "entry_id", true);
        if (is_numeric($entry_id)) {
            $link = site_url("wp-admin/admin.php?page=gf_entries&view=entry&id=2&lid={$entry_id}&orderby=3&order=ASC&filter&paged=1&pos=1&field_id&operator");
        }
    }

    return $link;
}

add_filter("get_edit_post_link", "pedeu_remove_get_edit_post_link", 10, 2);


/**
 * Redirect to Gravity Form for new case study or project
 */
function pedeu_redirect_new_post_to_form(): void
{
    global $pagenow;
    if ($pagenow == "post-new.php" && $_GET["post_type"] == "projects") {
        wp_redirect(get_permalink(PED_EU_FORM_PROJECT_PID));
        die;
    }
    if ($pagenow == "post-new.php" && $_GET["post_type"] == "case-studies") {
        wp_redirect(get_permalink(PED_EU_FORM_CASE_STUDY_PID));
        die;
    }
}

add_action("init", "pedeu_redirect_new_post_to_form");


/**
 * Hide GForms from menu for DB Editors
 */
function pedeu_admin_head()
{
    $user = wp_get_current_user();
    if (in_array(PED_EU_DB_EDITOR_ROLE, (array)$user->roles)) {
        echo "<style>#toplevel_page_gf_edit_forms{display: none;}</style>";
    }
}

add_action("admin_head", "pedeu_admin_head");


/**
 * Add table column for case studies and projects (posts).
 */
function pedeu_posts_columns($columns)
{
    $columns["pedeu-actions"] = "Quick actions";
    return $columns;
}

add_filter("manage_case-studies_posts_columns", "pedeu_posts_columns");
add_filter("manage_projects_posts_columns", "pedeu_posts_columns");


/**
 * Add quick actions buttons to column.
 */
function pedeu_custom_posts_columns($column_key, $post_id)
{
    if ($column_key == "pedeu-actions") {
        $current_status = get_post_status($post_id);
        if ($current_status == "publish") {
            echo "
                <button class='button button-secondary ped-eu-btn-edit-status'
                        type='button'
                        data-pid='$post_id'
                        data-status='draft'>
                    Back to Draft
                </button>
            ";
        } else {
            echo "
                <button class='button button-secondary ped-eu-btn-edit-status'
                        type='button'
                        data-pid='$post_id'
                        data-status='publish'>
                    Publish
                </button>
            ";
        }

        $edit_url = site_url("wp-admin/post.php?post=$post_id&action=edit");
        echo "
            <a href='$edit_url' class='button button-secondary ped-eu-btn-edit-status'>Check Post</a>
        ";
    }
}

add_action("manage_case-studies_posts_custom_column", "pedeu_custom_posts_columns", 10, 2);
add_action("manage_projects_posts_custom_column", "pedeu_custom_posts_columns", 10, 2);


/**
 * WP AJAX action for quick actions to update post status.
 */
function pedeu_update_post_status(): void
{
    $post_id = intval($_POST["post_id"]);
    $status = $_POST["status"];
    wp_update_post(array("ID" => $post_id, "post_status" => $status));
    wp_die();
}

add_action('wp_ajax_pedeu_update_post_status', 'pedeu_update_post_status');


/**
 * Hide admin bar.
 */
function pedeu_remove_admin_bar_callback(): void
{
    if (current_user_can("subscriber")) {
        show_admin_bar(false);
    }
}

add_action("after_setup_theme", "pedeu_remove_admin_bar_callback");


/**
 * Add scripts and styles to admin pages.
 */
function pedeu_admin_scripts_and_styles(): void
{
    wp_enqueue_style("ped-db-admin-css", PED_EU_ROOT_URL . "static/css/admin.css");
    wp_enqueue_script("ped-db-admin-js", PED_EU_ROOT_URL . "static/js/admin.js", array("jquery"), PED_EU_VERSION, true);
}

add_action("admin_enqueue_scripts", "pedeu_admin_scripts_and_styles");


/**
 * Send email with form invitation
 */
function pedeu_admin_send_form_invite($email, $invite_link, $type)
{
    $subject = "PED-EU-NET Database Form Invitation Link";

    $intro = "";
    if ($type === "project") {
        $intro = "You can use the following link to fulfill the input form for your PED Project / Initiative.";
    } elseif ($type === "case-study") {
        $intro = "You can use the following link to fulfil the input form for your PED Case Study, PED Relevant Case Study or PED Lab.";
    }

    $body = "
        <strong>Welcome to PED-EU-NET Database*</strong>,<br/>
        <br/>
        $intro<br/>
        $invite_link<br/>
        <br/>
        You can also save and continue anytime.<br/>
        <br/>
        Thank you,<br/>
        PED-EU-NET Team<br/>
        <br/>
        *The COST Action “Positive Energy Districts European Network” sets an overarching framework to develop a comprehensive PED Database in collaboration with IEA EBC Annex 83 and JPI Urban Europe.
        ";

    $headers = array("Content-Type: text/html; charset=UTF-8");
    wp_mail($email, $subject, $body, $headers);
}


/**
 * Make form invite link based on type and code.
 *
 * @param string $type Should be "project" or "case-study"
 * @param string $code Random code for accessing the form
 * @return false|string
 */
function pedeu_make_invite_link($type, $code)
{
    if ($type === "project") {
        return add_query_arg(array("access_code" => $code), get_page_link(PED_EU_FORM_PROJECT_PID));
    }
    if ($type === "case-study") {
        return add_query_arg(array("access_code" => $code), get_page_link(PED_EU_FORM_CASE_STUDY_PID));
    }
    return false;
}


/**
 * Extend form draft expiration.
 *
 * @param $entry_uuid
 * @return void
 */
function pedeu_extend_form_draft($entry_uuid): void
{
    global $wpdb;
    // Update date_created
    $wpdb->update(
        "wp_gf_draft_submissions",
        array("date_created" => current_time("mysql")),
        array("uuid" => $entry_uuid)
    );
}

function pedeu_delete_form_draft($entry_uuid): void
{
    global $wpdb;

    // Delete files
    $draft = $wpdb->get_row("SELECT * FROM wp_gf_draft_submissions WHERE uuid = '$entry_uuid'");
    $submission = json_decode($draft->submission, true);
    $files = $submission["files"];
    $form_id = $submission["partial_entry"]["form_id"];
    $upload_path = GFFormsModel::get_upload_path($form_id) . "/tmp/";
    foreach ($files as $file) {
        foreach ($file as $item) {
            $file_path = $upload_path . $item["temp_filename"];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    // Delete draft
    $wpdb->delete(
        "wp_gf_draft_submissions",
        array("uuid" => $entry_uuid)
    );
}


/**
 * Prepare form draft for user.
 *
 * @param string $email
 * @param int $post_id
 * @param string $invite_link
 */
function pedeu_prepare_form_draft($email, $post_id, $invite_link): string
{
    global $wpdb;
    $draft_uuid = pedeu_get_uuid();

    $entry_id = get_post_meta($post_id, "entry_id", true);
    $entry = GFAPI::get_entry($entry_id);
    $form_id = $entry["form_id"];
    $form = GFAPI::get_form($form_id);

    $unique_id = substr(pedeu_get_uuid(), 0, 13);
    $files = array();
    $values = array();
    $partial_entry = array(
        "id" => null,
        "post_id"  => null,
        "date_created"  => null,
        "date_updated"  => null,
        "form_id"  => $form_id,
        "ip" => "127.0.0.1",
        "source_url" => $invite_link,
        "user_agent" => "PED-EU-NET",
        "created_by" => null,
        "currency" => "USD",
    );

    foreach ($entry as $key => $value) {
        // if form field is fileupload
        if ($form["fields"][$key] && $form["fields"][$key]["type"] === "fileupload") {
            $result = array();
            $links = json_decode($entry[$key]);
            foreach ($links as $index => $link) {
                $filename = basename($link);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $file_item = array(
                    "temp_filename" => $unique_id . "_input_" . $key . "_" . pedeu_generate_random_string(20) . "_" . $index . "." . $ext,
                    "uploaded_filename" => $filename,
                );
                $upload_path = GFFormsModel::get_upload_path($form_id) . "/tmp/" . $file_item["temp_filename"];
                file_put_contents($upload_path, file_get_contents($link));
                $result[] = $file_item;
            }
            $files["input_" . $key] = $result;
            $entry[$key] = "";
            $values[$key] = "";
        }

        if (preg_match("/^[0-9]+\.[0-9]+$/", $key)) {
            $parent_key = explode(".", $key)[0];
            if (!isset($values[$parent_key])) {
                $values[$parent_key] = array();
            }
            $values[$parent_key][$key] = $value;
            $entry[$key] = $value;
        } elseif (preg_match("/^[0-9]+$/", $key)) {
            $values[$key] = $value;
            $entry[$key] = $value;
        }
    }

    $submission = array(
        "submitted_values" => $values,
        "partial_entry" => $partial_entry,
        "field_values" => "",
        "page_number" => 1,
        "files" => $files,
        "gform_unique_id" => $unique_id,
    );

    $wpdb->insert(
        $wpdb->prefix . "gf_draft_submissions",
        array(
            "uuid" => $draft_uuid,
            "email" => $email,
            "form_id" => $form_id,
            "date_created" => current_time("mysql"),
            "source_url" => $invite_link,
            "ip" => "127.0.0.1",
            "submission" => json_encode($submission),
        )
    );

    return $draft_uuid;
}


add_filter('gform_incomplete_submissions_expiration_days', 'pedeu_change_incomplete_submissions_expiration_days');
function pedeu_change_incomplete_submissions_expiration_days($expiration_days) {
    $expiration_days = PED_EU_GF_DRAFT_DAYS;
    return $expiration_days;
}

function pedeu_draft_remaining_days($creation_date) {
    $expiration_days = PED_EU_GF_DRAFT_DAYS;
    $creation_date = strtotime($creation_date);
    $current_date = strtotime(current_time("mysql"));
    $diff = $current_date - $creation_date;
    $days = floor($diff / (60 * 60 * 24));
    return max($expiration_days - $days, 0);
}
