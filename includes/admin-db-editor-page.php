<?php
global $wpdb;

if (!defined('ABSPATH')) {
    exit;
}

// toggle active/inactive for an invitation entry
if (isset($_POST["ped_action"]) && $_POST["ped_action"] == "toggle") {
    $entry_index = esc_attr($_POST["index"]);
    $email_list = get_option(PED_EU_WP_OPTION_EMAILS, array());
    $entry = pedeu_normalize_invite_entry($email_list[$entry_index]);
    $entry["active"] = !($entry["active"] === true);
    $email_list[$entry_index] = $entry;
    update_option(PED_EU_WP_OPTION_EMAILS, $email_list);
    wp_safe_redirect(get_permalink());
}

// delete an invitation entry
if (isset($_POST["ped_action"]) && $_POST["ped_action"] == "delete") {
    $entry_index = esc_attr($_POST["index"]);
    $email_list = get_option(PED_EU_WP_OPTION_EMAILS, array());
    unset($email_list[$entry_index]);
    update_option(PED_EU_WP_OPTION_EMAILS, $email_list);
    wp_safe_redirect(get_permalink());
}

// send an invitation and create an invitation entry
if (isset($_POST["ped_action"]) && $_POST["ped_action"] == "send") {
    $email = esc_attr($_POST["email"]);
    $type = esc_attr($_POST["type"]);
    $code = esc_attr($_POST["code"]);
    $prefill = esc_attr($_POST[$type]);
    $validated = true;

    if (trim($email) == "") {
        echo "<div class='ped-eu-warning'>Email is required</div>";
        $validated = false;
    } elseif (!is_email($email)) {
        echo "<div class='ped-eu-warning'>Not a valid email address</div>";
        $validated = false;
    }

    if ($type != "case-study" && $type != "project") {
        echo "<div class='ped-eu-warning'>Invalid type</div>";
        $validated = false;
    }

    if ($code == "") {
        echo "<div class='ped-eu-warning'>Invalid code</div>";
        $validated = false;
    }

    $invite_link = pedeu_make_invite_link($type, $code);

    if ($prefill != "none") {
        $prefill = intval($prefill);
        if ($prefill == 0) {
            echo "<div class='ped-eu-warning'>Invalid prefill</div>";
            $validated = false;
        }

        $uuid = pedeu_prepare_form_draft($email, $prefill, $invite_link);
        $invite_link = $invite_link . "&gf_token=" . $uuid;
    }

    if ($validated && $invite_link) {
        pedeu_admin_send_form_invite($email, $invite_link, $type);
        $email_list = get_option(PED_EU_WP_OPTION_EMAILS, array());

        if (empty($email_list) || $email_list == "") {
            $email_list = array();
        }

        $userid = get_current_user_id();
        $userdata = get_userdata($userid);

        $email_list[] = array(
            "email" => $email,
            "link" => $invite_link,
            "from" => $userdata->user_login,
            "type" => $type,
            "code" => $code,
            "timestamp" => time(),
            "active" => true,
            "_version" => 1,  // whenever structure changes, increase the version
        );
        update_option(PED_EU_WP_OPTION_EMAILS, $email_list);
    }
    wp_safe_redirect(get_permalink());
}

// extend a draft submission
if (isset($_POST["ped_gf_action"]) && $_POST["ped_gf_action"] == "extend") {
    pedeu_extend_form_draft($_POST["entry_uuid"]);
    wp_safe_redirect(get_permalink());
}

// delete a draft submission
if (isset($_POST["ped_gf_action"]) && $_POST["ped_gf_action"] == "delete") {
    pedeu_delete_form_draft($_POST["entry_uuid"]);
    wp_safe_redirect(get_permalink());
}

// prepare data
$existing_codes = array();
$email_list = get_option(PED_EU_WP_OPTION_EMAILS, array());
foreach ($email_list as $value) {
    $entry = pedeu_normalize_invite_entry($value);
    $existing_codes[] = $entry["code"];
}
$existing_codes = array_unique($existing_codes);
$next_code = pedeu_generate_random_string();
while (in_array($next_code, $existing_codes)) {
    $next_code = pedeu_generate_random_string();
}
$entry_types = array(
    "case-study" => "Case Study",
    "project" => "Project",
    "unknown" => "<em>Unknown</em>",
);

$all_cases = get_posts(array(
    "numberposts" => -1,
    "post_type" => "case-studies",
    "meta_query" => array(),
    "post_status" => "publish",
));
$all_projects = get_posts(array(
    "numberposts" => -1,
    "post_type" => "projects",
    "meta_query" => array(),
    "post_status" => "publish",
));

$draftsRaw = $wpdb->get_results("SELECT uuid, email, form_id, date_created, source_url FROM wp_gf_draft_submissions ORDER BY date_created DESC", ARRAY_A);
$drafts = array();
foreach ($draftsRaw as $draft) {
    // extract code from source_url (after ?access_code=)
    $code = "";
    $urlParts = parse_url($draft["source_url"]);
    if (isset($urlParts["query"])) {
        parse_str($urlParts["query"], $query);
        if (isset($query["access_code"])) {
            $code = $query["access_code"];
        }
    }
    // add uuid as "gf_token" query param to the URL
    $url = $draft["source_url"];
    if (strpos($url, "?") === false) {
        $url .= "?";
    } else {
        $url .= "&";
    }
    $url .= "gf_token=" . $draft["uuid"];

    $type = "unknown";
    if ($draft["form_id"] == PED_EU_FORM_CASE_STUDY_GF_ID) {
        $type = "Case Study";
    } elseif ($draft["form_id"] == PED_EU_FORM_PROJECT_GF_ID) {
        $type = "Project";
    }

    $drafts[] = array(
        "uuid" => $draft["uuid"],
        "email" => $draft["email"],
        "form_id" => $draft["form_id"],
        "date_created" => explode(" ", $draft["date_created"])[0],
        "code" => $code,
        "type" => $type,
        "source_url" => $draft["source_url"],
        "url" => $url,
    );
}

?>

<div id="pedeu-db-editor-settings" class="pedeu-db-editor-section">
    <h3>Send Email Invitation</h3>
    <div class="db_inner">
        <div class="ped-send-email ped-send-email-case-study">
            <h4>Case Study</h4>
            <?php $case_code_link = pedeu_make_invite_link("case-study", $next_code); ?>
            <div class="invite-link">
                New link: <a href="<?= $case_code_link ?>" target="_blank"><?= $case_code_link ?></a>
            </div>
            <form class="send-email-form" method="POST" action="">
                <input type="hidden" name="code" value="<?= $next_code ?>">
                <input type="hidden" name="type" value="case-study">
                <input type="hidden" name="ped_action" value="send">
                <input type="email" name="email" value="" class="email-field" placeholder="Email Address" required>
                <select name="case-study">
                    <option value="none">-- (no case study to prefill) --</option>
                    <?php
                    foreach ($all_cases as $value) {
                        $id = $value->ID;
                        $title = get_the_title($id);
                        echo "<option value='$id'>$title (ID: $id)</option>";
                    }
                    ?>
                </select>
                <input type="submit" name="submit" value="Send Invite" class="button button-primary button-small send-email-btn">
            </form>
        </div>
        <div class="ped-send-email ped-send-email-project">
            <h4>Project</h4>
            <?php $proj_code_link = pedeu_make_invite_link("project", $next_code); ?>
            <div class="invite-link">
                New link: <a href="<?= $proj_code_link ?>" target="_blank"><?= $proj_code_link ?></a>
            </div>
            <form class="send-email-form" method="POST" action="">
                <input type="hidden" name="code" value="<?= $next_code ?>">
                <input type="hidden" name="type" value="project">
                <input type="hidden" name="ped_action" value="send">
                <input type="email" name="email" value="" class="email-field" placeholder="Email Address" required>
                <select name="project">
                    <option value="none">-- (no project to prefill) --</option>
                    <?php
                    foreach ($all_projects as $value) {
                        $id = $value->ID;
                        $title = get_the_title($id);
                        echo "<option value='$id'>$title (ID: $id)</option>";
                    }
                    ?>
                </select>
                <input type="submit" name="submit" value="Send Invite" class="button button-primary button-small send-email-btn">
            </form>
        </div>
    </div>
</div>
<div id="pedeu-db-editor-email-list" class="pedeu-db-editor-section">
    <h3>Emails Sent</h3>
    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
        <tr>
            <td class="ped-eu-td-shrink ped-eu-td-right">ID</td>
            <td>Email</td>
            <td>From (Username)</td>
            <td>Type</td>
            <td>Created at</td>
            <td>Code + Link</td>
            <td class="ped-eu-td-shrink ped-eu-td-center">Active</td>
            <td class="ped-eu-td-actions">Actions</td>
        </thead>
        <tbody id="the-list">
        <?php foreach (array_reverse($email_list, true) as $index => $value) { ?>
            <?php $entry = pedeu_normalize_invite_entry($value) ?>
            <tr class="iedit author-self level-0 hentry">
                <td class="ped-eu-td-shrink ped-eu-td-right">
                    <?= $index ?>
                </td>
                <td>
                    <?= $entry["email"] ?>
                </td>
                <td>
                    <?= $entry["from"] == "" ? "<em>Unknown</em>" : $entry["from"] ?>
                </td>
                <td>
                    <?= $entry_types[$entry["type"]] ?>
                </td>
                <td>
                    <?= $entry["timestamp"] == 0 ? "<em>Unknown</em>" : strftime("%Y-%m-%d %H:%M:%S", $entry["timestamp"]) ?>
                </td>
                <td>
                    <a href="<?= $entry["link"] ?>" target="_blank"><?= $entry["code"]; ?></a>
                </td>
                <td class="ped-eu-td-shrink ped-eu-td-center">
                    <input type="checkbox" <?= $entry["active"] ? "checked" : "" ?> disabled>
                </td>
                <td class="ped-eu-td-actions">
                    <form method="POST" action="">
                        <input type="hidden" name="index" value="<?= $index ?>">
                        <button type="submit" name="ped_action" value="delete" class="button button-secondary button-small">
                            Delete
                        </button>
                        <button type="submit" name="ped_action" value="toggle" class="button button-primary button-small">
                            <?= $entry["active"] ? "Deactivate" : "Activate" ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<div id="pedeu-db-editor-draft-list" class="pedeu-db-editor-section">
    <h3>Submission Drafts</h3>
    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
        <tr>
            <td>Created at</td>
            <td>Email</td>
            <td>Type</td>
            <td>Code + Secret Link</td>
            <td class="ped-eu-td-actions">Actions</td>
        </thead>
        <tbody>
        <?php foreach ($drafts as $draft) { ?>
            <tr class="iedit author-self level-0 hentry">
                <td>
                    <?= explode(" ", $draft["date_created"])[0] ?>
                    (<?= pedeu_draft_remaining_days($draft["date_created"]) ?> days remaining)
                </td>
                <td>
                    <?= $draft["email"] ?>
                </td>
                <td>
                    <?= $draft["type"] ?>
                </td>
                <td>
                    <a href="<?= $draft["url"] ?>" target="_blank"><?= $draft["code"]; ?></a>
                </td>
                <td class="ped-eu-td-actions">
                    <form method="POST" action="">
                        <input type="hidden" name="entry_uuid" value="<?= $draft["uuid"]  ?>">
                        <button type="submit" name="ped_gf_action" value="delete" class="button button-secondary button-small">
                            Delete
                        </button>
                        <button type="submit" name="ped_gf_action" value="extend" class="button button-primary button-small">
                            Extend
                        </button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
