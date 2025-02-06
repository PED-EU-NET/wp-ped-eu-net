<?php

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
    $validated = true;

    if (trim($email) == "") {
        echo "<div class='ped-eu-warning'>Email is required</div>";
        $validated = false;
    } elseif (!is_email($email)) {
        echo "<div class='ped-eu-warning'>Not a valid email address</div>";
        $validated = false;
    }

    $invite_link = pedeu_make_invite_link($type, $code);

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
)

?>

<div id="pedeu-db-editor-settings">
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
                <input type="email" name="email" value=""
                       class="email-field"
                       placeholder="Email Address"
                       required>
                <input type="submit" name="submit" value="Send Invite"
                       class="button button-primary button-small send-email-btn">
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
                <input type="email" name="email" value=""
                       class="email-field"
                       placeholder="Email Address"
                       required>
                <input type="submit" name="submit" value="Send Invite"
                       class="button button-primary button-small send-email-btn">
            </form>
        </div>
    </div>
</div>
<div id="pedeu-db-editor-email-list">
    <h3>Emails Sent</h3>
    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
        <tr>
            <td class="ped-eu-td-shrink ped-eu-td-right">ID</td>
            <td>Email</td>
            <td>From (Username)</td>
            <td>Type</td>
            <td>When</td>
            <td>Code + Link</td>
            <td class="ped-eu-td-shrink ped-eu-td-center">Active</td>
            <td>Actions</td>
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
                <td>
                    <form method="POST" action="">
                        <input type="hidden"
                               name="index"
                               value="<?= $index ?>">
                        <button type="submit" name="ped_action" value="delete"
                                class="button button-secondary button-small">
                            Delete
                        </button>
                        <button type="submit" name="ped_action" value="toggle"
                                class="button button-primary button-small">
                            <?= $entry["active"] ? "Deactivate" : "Activate" ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
