<?php

/**
 * Projects dropdown
 *
 * @param $form
 * @return mixed
 */
function pedeu_ref_project_dropdown_callback($form)
{
    foreach ($form["fields"] as &$field) {
        if ( /*$field->type != "select" || */ strpos($field->cssClass, "form_reference_project") === false) {
            continue;
        }

        // you can add additional parameters here to alter the posts that are retrieved
        // more info: http://codex.wordpress.org/Template_Tags/get_posts
        $posts = get_posts("numberposts=-1&post_status=publish&post_type=projects");
        $choices = array();

        foreach ($posts as $post) {
            $choices[] = array("text" => $post->post_title, "value" => $post->ID);
        }

        $field->placeholder = "Select a Project";
        $field->choices = $choices;
    }
    return $form;
}

add_filter("gform_pre_render_1", "pedeu_ref_project_dropdown_callback");
add_filter("gform_pre_validation_1", "pedeu_ref_project_dropdown_callback");
add_filter("gform_pre_submission_filter_1", "pedeu_ref_project_dropdown_callback");
add_filter("gform_admin_pre_render_1", "pedeu_ref_project_dropdown_callback");

/**
 * Case studies dropdown.
 *
 * @param $form
 * @return mixed
 */
function pedeu_ref_casestudy_dropdown_callback($form)
{
    foreach ($form["fields"] as &$field) {
        if (strpos($field->cssClass, "form_reference_casestudy") === false) {
            continue;
        }

        // you can add additional parameters here to alter the posts that are retrieved
        // more info: http://codex.wordpress.org/Template_Tags/get_posts
        $posts = get_posts("numberposts=-1&post_status=publish&post_type=case-studies");
        $choices = array();

        foreach ($posts as $post) {
            $choices[] = array("text" => $post->post_title, "value" => $post->ID);
        }

        $field->placeholder = "Select a Case Study";
        $field->choices = $choices;
    }
    return $form;
}

add_filter("gform_pre_render_2", "pedeu_ref_casestudy_dropdown_callback");
add_filter("gform_pre_validation_2", "pedeu_ref_casestudy_dropdown_callback");
add_filter("gform_pre_submission_filter_2", "pedeu_ref_casestudy_dropdown_callback");
add_filter("gform_admin_pre_render_2", "pedeu_ref_casestudy_dropdown_callback");


/**
 * New project form
 */
function pedeu_new_project_callback($entry, $form)
{
    $end_date = rgar($entry, "7");
    $end_date1 = explode("/", $end_date);

    $start_date = rgar($entry, "8");
    $start_date1 = explode("/", $start_date);

    //get new post id from Advanced Post Creation add-on
    $created_posts = gform_get_meta($entry["id"], "gravityformsadvancedpostcreation_post_id");

    //there can be more than one post created with advanced post creation addon (one in our case)
    foreach ($created_posts as $post) {
        $post_id = $post["post_id"];
        $end_date_date = DateTime::createFromFormat("d-m-Y H:i:s", "01-{$end_date1[0]}-20{$end_date1[1]} 00:00:00");
        $start_date_date = DateTime::createFromFormat("d-m-Y H:i:s", "01-{$start_date1[0]}-20{$start_date1[1]} 00:00:00");

        if ($end_date_date === false) {
            // die("Incorrect date string");
        } else {
            $end_date_stamp = $end_date_date->getTimestamp();
            update_post_meta($post_id, "end-date", $end_date_stamp);
        }

        if ($start_date_date === false) {
            // die("Incorrect date string");
        } else {
            $start_date_stamp = $start_date_date->getTimestamp();
            update_post_meta($post_id, "start-date", $start_date_stamp);
        }
    }
}

add_action("gform_after_submission_2", "pedeu_new_project_callback", 10, 2);


/**
 * Shortcode for form diagram.
 *
 * @param array $atts
 * @return string
 */
function pedeu_form_diagram(array $atts): string
{
    $type = $atts["type"];
    $svg_data = file_get_contents(PED_EU_ROOT_PATH . "static/images/form/ped-db-key-plan.min.svg");
    return "<div class='pedeu-form-diagram pedeu-form-diagram-$type' data-type='$type'>$svg_data</div>";
}

add_shortcode(PED_EU_SHORTCODE_FORM_DIAGRAM, "pedeu_form_diagram");
