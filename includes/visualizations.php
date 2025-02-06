<?php


function pedeu_overview_html()
{
    return "<div id='pedeu-overview'><div class='loader'></div></div>";
}


add_shortcode(PED_EU_SHORTCODE_OVERVIEW, "pedeu_overview_html");


function pedeu_visualizations_html()
{
    return "<div id='pedeu-visualizations'><div class='loader'></div></div>";
}


add_shortcode(PED_EU_SHORTCODE_VISUALIZATIONS, "pedeu_visualizations_html");
