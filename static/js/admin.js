jQuery(document).ready(function ($) {
  $("button.ped-eu-btn-edit-status").click(function () {
    const btn = $(this);
    const pid = btn.data("pid");
    const status = btn.data("status");

    let data = {
      'action': 'pedeu_update_post_status',
      'post_id': pid,
      'status': status,
    };

    jQuery.post(ajaxurl, data, function (response) {
      document.location.reload(true);
    });
  });
});
