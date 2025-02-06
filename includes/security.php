<?php


/**
 * Custom login page.
 */
function pedeu_members_redirect_if_user_not_logged_in()
{
    if (is_page("members-area") && !is_user_logged_in()) {
        wp_redirect(site_url("login/"));
        exit;
    }
}

add_action("template_redirect", "pedeu_members_redirect_if_user_not_logged_in");


/**
 * Checks access for case study and project forms if user is
 * allowed to see it; otherwise, returns error message.
 *
 * @param string $content
 * @return string
 */
function pedeu_form_access_filter(string $content): string
{
    $protected_pages = [PED_EU_FORM_PROJECT_PID, PED_EU_FORM_CASE_STUDY_PID];
    $pid = get_the_ID();

    // if not protected page, no filtering
    if (!in_array($pid, $protected_pages)) {
        return $content;
    }

    // allowed for admin and db editor even without access code
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $allowed_roles = array(PED_EU_DB_EDITOR_ROLE, "administrator");
        if (array_intersect($allowed_roles, $user->roles)) {
            return $content;
        }
    }

    // check access code
    $sent_invites = get_option(PED_EU_WP_OPTION_EMAILS, array());
    $active_codes = array();
    foreach ($sent_invites as $value) {
        $entry = pedeu_normalize_invite_entry($value);
        if ($entry["active"]) {
            $active_codes[] = $entry["code"];
        }
    }
    $access_code = isset($_GET["access_code"]) ? esc_attr($_GET["access_code"]) : "";
    if ($access_code == "" || !in_array($access_code, $active_codes)) {
        return "<div class='pedeu-form-access-denied'>You need access code to view this content</div>";
    }

    return $content;
}

add_filter("the_content", "pedeu_form_access_filter");
