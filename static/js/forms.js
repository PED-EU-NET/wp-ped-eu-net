jQuery.noConflict();
(function ($) {
  $(function () {
    $(document).bind('gform_post_render', function (event, formId, current_page) {
      $('.pedeu-form-diagram').each(function () {
        $(this).attr('data-page', current_page);
      });
    });

    $(".gsection_description").each(function () {
      const el = $(this);
      el.text(el.text().split("---")[0].trim());
    });

    setInterval(function () {
      if ($(".form_saved_message").length) {
        $("#nav_map_img img").hide();
      }
    }, 500);
  });
})(jQuery);
