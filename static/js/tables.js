jQuery.noConflict();
(function ($) {
  $(function () {
    $('#ped-eu-case-studies-table').DataTable({
      ordering: false,
    });

    $('#ped-eu-projects-table').DataTable({
      ordering: false,
      paging: false,
    });

    $('#ped-eu-case-study-table').DataTable({
      ordering: false,
      dom: 'Bfrtip',
      paging: false,
      buttons: [
        {
          extend: 'pdfHtml5',
          title: 'Case Study Data',
          text: 'PDF'
        },
        {
          extend: 'csvHtml5',
          title: 'Case Study Data',
          text: 'CSV',
        },
        {
          extend: 'excel',
          title: 'Case Study Data',
          text: 'XLSX',
        }
      ],
    });

    const caseStudyTable = document.getElementById("ped-eu-case-study-table_wrapper");
    if (caseStudyTable) {
      caseStudyTable.scrollIntoView();
    }
    const projectTable = document.getElementById("ped-eu-projects-table_wrapper");
    if (projectTable) {
      projectTable.scrollIntoView();
    }

    $("td.pedeu-not-specified").each(function () {
      $(this).text("(not specified)");
    });

    $("[data-unit-hide]").each(function () {
      const el = $(this);
      const unit = el.data('unit-hide');
      el.text(el.text().replace(`[${unit}]`, ""));
    });

    $("[data-unit]").each(function () {
      const el = $(this);
      const unit = el.data('unit');
      const text = el.text();
      el.text('');
      el.append($('<span>').addClass('value').text(text));
      el.append('&nbsp;');
      el.append($('<span>').addClass('unit').text(unit));
    });

    $("[data-tooltip]").each(function () {
      const el = $(this);
      const text = el.data('tooltip');
      el.append(' ');
      el.append(
        $('<div>')
          .addClass('pedeu-tooltip')
          .text('?')
          .append(
            $('<span>')
              .addClass('pedeu-tooltip-text')
              .append(revertHtmlEscaped(text))
          )
      );
    });

    $("[data-url-shorten]").each(function () {
      const el = $(this);
      const url = el.data('url-shorten');
      el.text(url.substring(url.lastIndexOf('/') + 1) + " ");
    });

    $("[data-longitude]").each(function () {
      const el = $(this);
      const longitude = Number(el.data('longitude'));
      if (isNaN(longitude)) {
        return;
      }

      const dms = convertDDToDMS(longitude, true);
      el.append($('<span>').addClass('sep').text(" "));
      el.append($('<span>').addClass('value').text(`(${dms.deg}° ${dms.min}' ${dms.sec}" ${dms.dir})`));
    })
    $("[data-latitude]").each(function () {
      const el = $(this);
      const latitude = Number(el.data('latitude'));
      if (isNaN(latitude)) {
        return;
      }

      const dms = convertDDToDMS(latitude, false);
      el.append($('<span>').addClass('sep').text(" "));
      el.append($('<span>').addClass('value').text(`(${dms.deg}° ${dms.min}' ${dms.sec}" ${dms.dir})`));
    })
  });
})(jQuery);


function convertDDToDMS(dd, lng) {
  return {
    dir: dd < 0 ? (lng ? "W" : "S") : lng ? "E" : "N",
    deg: 0 | (dd < 0 ? (dd = -dd) : dd),
    min: 0 | (((dd += 1e-9) % 1) * 60),
    sec: (0 | (((dd * 60) % 1) * 6000)) / 100,
  };
}


function revertHtmlEscaped(text) {
  const charmap = {
    '&amp;': '&',
    '&#038;': "&",
    '&lt;': '<',
    '&gt;': '>',
    '&quot;': '"',
    '&#039;': "'",
    '&#8217;': "’",
    '&#8216;': "‘",
    '&#8211;': "–",
    '&#8212;': "—",
    '&#8230;': "…",
    '&#8221;': '”'
  };
  return text.replace(/\&[\w\d\#]{2,5}\;/g, function (m) {
    return charmap[m];
  });
}
