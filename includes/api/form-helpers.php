<?php


/**
 * Determines if the field label has a prefix.
 *
 * @param $label
 * @return bool
 */
function pedeu_api_has_field_prefix($label) {
    $parts = explode(":", $label, 2);
    // match first letter A-Z, second is digit, third is P, three digits
    return count($parts) == 2 && preg_match("/^[A-Z][0-9]P[0-9]{3}$/", $parts[0]);
}


/**
 * Determines the type of the field.
 *
 * @param $field
 * @return string
 */
function pedeu_api_determine_field_type($field) {
    $type = $field["type"];

    if ($type == "number") {
        return "number";
    }
    if ($type == "checkbox" && count($field["choices"]) == 1) {
        return "boolean";
    }
    if ($type == "radio" && count($field["choices"]) == 2) {
        if (strtolower($field["choices"][0]["text"]) == "yes" && strtolower($field["choices"][1]["text"]) == "no") {
            return "boolean";
        }
        if (strtolower($field["choices"][1]["text"]) == "yes" && strtolower($field["choices"][0]["text"]) == "no") {
            return "boolean";
        }
        return "string";
    }
    if ($type == "multiselect" && $field->cssClass == "form_reference_casestudy") {
        return "case-studies";
    }
    if ($type == "multiselect" && $field->cssClass == "form_reference_project") {
        return "projects";
    }
    if ($type == "multiselect" || $type == "list" || $type == "fileupload") {
        return "list";
    }
    return "string";
}


/**
 * Transforms a form field entry to data.
 *
 * @param $form_entry
 * @param $form_field
 * @param $data_type
 * @param $context
 * @return array|bool|mixed|string|null
 */
function pedeu_api_form_field_to_data($form_entry, $form_field, $data_type, $context) {
    $key = $form_field->id;
    $type = $form_field->type;

    if ($type == "section" || $type == "html") {
        return "";
    }
    if ($type == "text" || $type == "textarea" || $type == "number" || $type == "email") {
        return $form_entry[$key];
    } else if ($type == "list") {
        return unserialize($form_entry[$key]);
    } else if ($type == "radio") {
        $value = $form_entry[$key];
        $choices = array();
        foreach ($form_field->choices as $choice) {
            $v = pedeu_get_array_value($choice, "value", null);
            $t = pedeu_get_array_value($choice, "text", null);
            if ($v == null || $t == null) {
                continue;
            }
            $choices[$v] = $t;
        }
        if ($data_type == "boolean") {
            return strtolower($value) == "yes";
        }

        if ($value == '') {
            return null;
        } else if (array_key_exists($value, $choices)){
            return $choices[$value];
        } else {
            return $value;
        }
    } else if ($type == "multiselect" && ($form_field->cssClass == "form_reference_casestudy" || $form_field->cssClass == "form_reference_project")) {
        $entities = $context["projects"];
        if ($form_field->cssClass == "form_reference_casestudy") {
            $entities = $context["case-studies"];
        }
        $value = json_decode($form_entry[$key]);

        $items = array();
        if ($value == null) {
            return $items;
        }
        foreach ($value as $item_id) {
            if (array_key_exists($item_id, $entities)) {
                $items[] = $entities[$item_id];
            }
        }

        $output = array();
        foreach ($items as $item) {
            $link = get_permalink($item["id"]);
            $title = $item["title"];
            $output[] = array(
                "id" => $item["id"],
                "title" => $title,
                "link" => $link,
            );
        }
        return $output;
    } else if ($type == "multiselect") {
        $value = json_decode($form_entry[$key]);
        $items = array();
        foreach ($form_field->choices as $choice) {
            $item_value = $choice['value'];
            $item_text = $choice['text'];

            if (pedeu_in_array(strval($item_value), $value)) {
                $items[] = $item_text;
            }
        }

        return $items;
    } else if ($type == "checkbox") {
        $inputs = $form_field->inputs;
        if (count($inputs) == 1 && $data_type == "boolean") {
            return $form_entry[$inputs[0]['id']] != '';
        } else {
            $items = array();
            foreach ($inputs as $input) {
                $is_checked = $form_entry[$input['id']] != '';
                if ($is_checked) {
                    $items[] = $input['label'];
                }
            }

            return $items;
        }
    } else if ($type == "select") {
        $value = $form_entry[$key];
        foreach ($form_field->choices as $choice) {
            if (pedeu_streq($choice["value"], $value)) {
                return $choice["text"];
            }
        }
        return null;
    } else if ($type == "fileupload") {
        $links = json_decode($form_entry[$key]);
        return $links;
    }

    return "";
}


/**
 * Transforms a form entry to data.
 *
 * @param $entry
 * @return array
 */
function pedeu_api_form_entry_to_data($entry) {
    $form_id = $entry["form_id"];
    $form = GFAPI::get_form($form_id);
    $context = pedeu_get_table_context();
    $meta = pedeu_api_form_to_meta($form_id);

    $fields = array();
    foreach ($form["fields"] as $field) {
        $fields[$field["id"]] = $field;
    }

    $data = array();
    foreach ($meta["fields"] as $field) {
        $key = $field["key"];
        if ($field["fieldType"] == "section") {
            $subfields = array();
            foreach ($field["subfields"] as $subfield) {
                $subkey = $subfield["key"];
                $subfields[$subkey] = pedeu_api_form_field_to_data($entry, $fields[$subfield["fieldId"]], $subfield["dataType"], $context);
            }
            $data[$key] = $subfields;
        } else {
            $data[$key] = pedeu_api_form_field_to_data($entry, $fields[$field["fieldId"]], $field["dataType"], $context);
        }
    }

    return array(
        "entryId" => $entry["id"],
        "formId" => $form_id,
        "data" => $data,
    );
}


/**
 * Transforms a form field to meta.
 *
 * @param $field
 * @param $key
 * @param $context
 * @return array
 */
function pedeu_api_form_field_to_meta($field, $key, $context) {
    $data_type = pedeu_api_determine_field_type($field["full"]);

    $result = array(
        "fieldId" => $field["fieldId"],
        "key" => $key,
        "label" => $field["label"],
        "fieldType" => $field["type"],
        "dataType" => $data_type,
    );
    $form_field = $field["full"];

    if ($data_type == "case-studies") {
        $result["choices"] = array();
        foreach ($context["case-studies"] as $case_study) {
            $result["choices"][] = array(
                "id" => $case_study["id"],
                "title" => $case_study["title"],
            );
        }
    } else if ($data_type == "projects") {
        $result["choices"] = array();
        foreach ($context["projects"] as $project) {
            $result["choices"][] = array(
                "id" => $project["id"],
                "title" => $project["title"],
            );
        }
    } else if ($data_type != "boolean" && pedeu_in_array($form_field["type"], array("multiselect", "select", "radio", "checkbox", "list"))) {
        $result["choices"] = array();
        $form_choices = pedeu_get_array_value($form_field, "choices", array());
        foreach ($form_choices as $choice) {
            $result["choices"][] = $choice["text"];
        }
    }

    return $result;
}


/**
 * Transforms a form to meta.
 *
 * @param $form_id
 * @return array
 */
function pedeu_api_form_to_meta($form_id) {
    $form = GFAPI::get_form($form_id);
    $context = pedeu_get_table_context();

    $sections = array();
    $section_id = -1;
    foreach ($form["fields"] as $field) {
        if ($field["type"] == "section") {
            $section_id += 1;
            $sections[] = array(
                "key" => null,
                "label" => $field["label"],
                "fields" => array(),
            );
            if (pedeu_api_has_field_prefix($field["label"])) {
                $parts = explode(":", $field["label"], 2);
                $sections[$section_id]["id"] = $parts[0];
                $sections[$section_id]["label"] = trim($parts[1]);
            }
            continue;
        }
        if ($field["type"] == "html") {
            continue;
        }

        $field = array(
            "fieldId" => $field["id"],
            "key" => null,
            "label" => $field["label"],
            "type" => $field["type"],
            "full" => $field,
        );
        if (pedeu_api_has_field_prefix($field["label"])) {
            $parts = explode(":", $field["label"], 2);
            $field["id"] = $parts[0];
            $field["label"] = trim($parts[1]);
        }

        $sections[$section_id]["fields"][] = $field;
    }

    $fields = array();
    foreach ($sections as $section) {
        $section_id = pedeu_get_array_value($section, "id", null);
        $section_fields = pedeu_get_array_value($section, "fields", array());
        if ($section_id == null || count($section_fields) == 0) {
            continue;
        }
        if (count($section_fields) == 1 && pedeu_get_array_value($section_fields[0], "id") == $section_id) {
            $fields[] = pedeu_api_form_field_to_meta($section_fields[0], $section_id, $context);
        } else {
            $subfields = array();

            $index = 0;

            foreach ($section_fields as $field) {
                $index += 1;
                $subfields[] = pedeu_api_form_field_to_meta($field, $section_id . "." . $index, $context);
            }

            $fields[] = array(
                "fieldId" => null,
                "key" => $section_id,
                "label" => pedeu_get_array_value($section, "label", ""),
                "fieldType" => "section",
                "dataType" => "object",
                "subfields" => $subfields,
            );
        }
    }

    $fieldKeys = array();
    foreach ($fields as $index => $field) {
        $fieldKeys[$field["key"]] = $index;
    }

    return array(
        "formId" => $form_id,
        "formName" => pedeu_get_array_value($form, "title", ""),
        "formLabel" => pedeu_get_array_value($form, "label", ""),
        "fields" => $fields,
        "fieldKeys" => $fieldKeys,
    );
}

