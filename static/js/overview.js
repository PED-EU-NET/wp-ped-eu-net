console.log('overview.js loaded');

/* UTILS */
const BASE_COLORS = ["#00A19B", "#005850", "#F2786C", "#FDC01C", "#B2D489"];

function getRandomColor(color) {
  let p = 1,
    temp,
    random = Math.random(),
    result = '#';

  while (p < color.length) {
    temp = parseInt(color.slice(p, p += 2), 16)
    temp += Math.floor((255 - temp) * random);
    result += temp.toString(16).padStart(2, '0');
  }
  return result;
}

function lightenColor(color, opacity = 1) {
  // Convert to color channels
  const num = parseInt(color.slice(1), 16)
  let R = num >> 16,
    G = (num >> 8) & 0x00ff,
    B = num & 0x0000ff

  // Interpolate channel
  opacity = Math.min(Math.max(opacity, 0), 1)
  R = Math.round(R + (1 - opacity) * (255 - R))
  G = Math.round(G + (1 - opacity) * (255 - G))
  B = Math.round(B + (1 - opacity) * (255 - B))

  // Encode as hex
  return (
    '#' +
    (
      0x1000000 +
      (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
      (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 +
      (B < 255 ? (B < 1 ? 0 : B) : 255)
    )
      .toString(16)
      .slice(1)
  )
}

function generateColors(n) {
  const colors = [];
  for (let i = 0; i < n; i++) {
    if (i < BASE_COLORS.length) {
      colors.push(BASE_COLORS[i])
    } else {
      colors.push(getRandomColor(BASE_COLORS[i % BASE_COLORS.length]));
    }
  }
  return colors;
}

var waitForEl = function(selector, callback) {
  if (jQuery(selector).length) {
    callback();
  } else {
    setTimeout(function() {
      waitForEl(selector, callback);
    }, 100);
  }
};

var decodeEntities = (function() {
  // this prevents any overhead from creating the object each time
  var element = document.createElement('div');

  function decodeHTMLEntities (str) {
    if(str && typeof str === 'string') {
      // strip script/html tags
      str = str.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, '');
      str = str.replace(/<\/?\w(?:[^"'>]|"[^"]*"|'[^']*')*>/gmi, '');
      element.innerHTML = str;
      str = element.textContent;
      element.textContent = '';
    }

    return str;
  }

  return decodeHTMLEntities;
})();

/* MAIN */

jQuery(document).ready(function() {
  jQuery.get("/wp-json/ped-eu/v1/everything", function(data, status){
    console.log(data);
    jQuery('#pedeu-overview').html('<div id="pedeu-visualization-overview"></div>');
    prepareOverview(data, "pedeu-visualization-overview");
  });
})

/* OVERVIEW */

async function prepareOverview(data, divId) {
  const root = jQuery(`#${divId}`);

  root.append('<div class="chart-wrap card-100"><canvas id="sankeyChartBase"></canvas></div>');

  root.append('<div class="number-wrap card-33"><div id="numberSites"></div></div>');
  root.append('<div class="number-wrap card-34"><div id="numberProjects"></div></div>');
  root.append('<div class="number-wrap card-33"><div id="numberCountries"></div></div>');

  root.append('<div class="chart-wrap card-50"><canvas id="chartA1P004"></canvas></div>');
  root.append('<div class="chart-wrap card-50"><canvas id="chartA1P005"></canvas></div>');
  root.append('<div class="chart-wrap card-100"><canvas id="mapCases"></canvas></div>');
  root.append('<div class="chart-wrap card-50"><canvas id="chartA3P006"></canvas></div>');
  root.append('<div class="chart-wrap card-50"><canvas id="chartA3P007"></canvas></div>');
  root.append('<div class="chart-wrap card-50"><canvas id="chartA3P009"></canvas></div>');

  waitForEl("#sankeyChartBase", function(){
    makeSankeyChartBase(data, "sankeyChartBase");
  });
  waitForEl("#chartA1P004", function(){
    makeChartA1P004(data, "chartA1P004");
  });
  waitForEl("#chartA1P005", function(){
    makeChartA1P005(data, "chartA1P005");
  });
  waitForEl("#mapCases", function(){
    makeMapCases(data, "mapCases");
  });
  waitForEl("#chartA3P006", function(){
    makeChartA3P006(data, "chartA3P006");
  });
  waitForEl("#chartA3P007", function(){
    makeChartA3P007(data, "chartA3P007");
  });
  waitForEl("#chartA3P006", function(){
    makeChartA3P009(data, "chartA3P009");
  });

  waitForEl("#numberSites", function(){
    makeNumberSites(data, "numberSites");
  });
  waitForEl("#numberProjects", function(){
    makeNumberProjects(data, "numberProjects");
  });
  waitForEl("#numberCountries", function(){
    makeNumberCountries(data, "numberCountries");
  });

  setTimeout(() => {
    jQuery(".counter-number").each(function(index) {
      if(!this.initNumAnim) {
        this.initNumAnim = true;
        jQuery(this).prop('Counter', 0).animate({
          Counter: jQuery(this).data('count')
        }, {
          duration: 1500,
          step: function (now) {
            jQuery(this).text(Math.ceil(now));
          }
        });
      }
    });
  }, 800);
}

function makeNumberSites(data, elemId) {
  const text = "PED Sites";
  const count = data.caseStudies.data.length;
  renderNumber(elemId, count, text);
}

function makeNumberProjects(data, elemId) {
  const text = "Projects";
  const count = data.projects.data.length;
  renderNumber(elemId, count, text);
}

function makeNumberCountries(data, elemId) {
  const text = "Countries";
  const count = new Set(data.caseStudies.data.map(cs => cs.data['A1P012'])).size;
  renderNumber(elemId, count, text);
}

function renderNumber(elemId, count, text) {
  jQuery(`#${elemId}`).html(`<div class="counter-number" data-count="${count}">--</div><div class="counter-text">${text}</div>`);
}


async function makeMapCases(data, canvasId) {
// Fetch the map data (using a sample map data URL, replace with your own if necessary)
  const response = await fetch('https://raw.githubusercontent.com/leakyMirror/map-of-europe/master/GeoJSON/europe.geojson');
  const europe = await response.json();

  // Prepare the data for the chart
  const countries = europe.features;
  const countryData = {};
  countries.forEach((country) => {
    countryData[country.properties.NAME] = 0;
  });
  data.caseStudies.data.forEach((cs) => {
    const country = cs.data['A1P012'];
    if (country && countryData[country] !== undefined) {
      countryData[country] += 1;
    }
  });
  const maxCount = Math.max(...Object.values(countryData));

  // Create a Geo Chart
  const ctx = document.getElementById(canvasId).getContext('2d');
  new Chart(ctx, {
    type: 'choropleth',
    data: {
      labels: countries.map(d => d.properties.NAME),
      datasets: [{
        label: 'CS count',
        outline: countries,
        data: countries.map((d) => ({ feature: d, value: countryData[d.properties.NAME]})),
        hoverBackgroundColor: '#FDC01C',
        borderWidth: 0,
        borderColor: 'grey',
      }]
    },
    options: {
      scales: {
        projection: {
          axis: 'x',
          projection: 'equalEarth',
        },
        color: {
          axis: 'x',
          interpolate: (v) => lightenColor('#005850', v),
        },
      },
      plugins: {
        legend: {
          display: false,
        },
        title: {
          display: true,
          text: 'Number of PED case studies / labs per country',
        },
      },
    }
  });
}

function makeSankeyChartBase(data, canvasId) {
  const pedNames = data.caseStudies.data.map(cs => cs.data['A1P001']);
  const pedCs = data.caseStudies.data.filter(cs => cs.data['A1P003']['A1P003.1']).map(cs => cs.entryId);
  const pedRcs = data.caseStudies.data.filter(cs => cs.data['A1P003']['A1P003.2']).map(cs => cs.entryId);
  const pedLabs = data.caseStudies.data.filter(cs => cs.data['A1P003']['A1P003.3']).map(cs => cs.entryId);

  const pedCsLabel = 'PED Case';
  const pedRcsLabel = 'PED Relevant';
  const pedLabsLabel = 'PED Lab';

  const colors = {};
  const labels = {};

  const priority = {};
  const sortedPedNames = pedNames.slice().sort();
  for (let i = 0; i < data.caseStudies.data.length; i++) {
    priority[data.caseStudies.data[i].entryId] = sortedPedNames.indexOf(pedNames[i]);
    colors[data.caseStudies.data[i].entryId] = "gray";
    labels[data.caseStudies.data[i].entryId] = pedNames[i];
  }
  colors[pedCsLabel] = "#00A19B";
  colors[pedRcsLabel] = "#005850";
  colors[pedLabsLabel] = "#B2D489";
  labels[pedCsLabel] = pedCsLabel;
  labels[pedRcsLabel] = pedRcsLabel;
  labels[pedLabsLabel] = pedLabsLabel;

  const dataSankey = [];
  const csSizes = {};
  for (let i = 0; i < pedCs.length; i++) {
    dataSankey.push({ from: pedCsLabel, to: pedCs[i], flow: 1 });
    if (!csSizes[pedCs[i]]) {
      csSizes[pedCs[i]] = 0;
    }
    csSizes[pedCs[i]]++;
  }
  for (let i = 0; i < pedRcs.length; i++) {
    dataSankey.push({ from: pedRcsLabel, to: pedRcs[i], flow: 1 });
    if (!csSizes[pedRcs[i]]) {
      csSizes[pedRcs[i]] = 0;
    }
    csSizes[pedRcs[i]]++;
  }
  for (let i = 0; i < pedLabs.length; i++) {
    dataSankey.push({ from: pedLabsLabel, to: pedLabs[i], flow: 1 });
    if (!csSizes[pedLabs[i]]) {
      csSizes[pedLabs[i]] = 0;
    }
    csSizes[pedLabs[i]]++;
  }

  for (let i = 0; i < data.caseStudies.data.length; i++) {
    const cs = data.caseStudies.data[i];
    for (let j = 0; j < cs.data['A1P008'].length; j++) {
      const project = cs.data['A1P008'][j];
      const pid = `project-${project.id}`;
      dataSankey.push({ from: cs.entryId, to: pid, flow: csSizes[cs.entryId] });
      labels[pid] = decodeEntities(project.title);
      colors[pid] = "#FDC01C";
      priority[pid] = 1;
    }
    if (cs.data['A1P008'].length === 0) {
      dataSankey.push({ from: cs.entryId, to: 'no-project', flow: csSizes[cs.entryId] });
    }
  }
  labels['no-project'] = "(no associated project)";
  colors['no-project'] = "#F2786C";
  priority['no-project'] = 1000;

  function getColor(name) {
    return colors[name] || "gray";
  }

  new Chart(document.getElementById(canvasId), {
    type: "sankey",
    data: {
      datasets: [
        {
          data: dataSankey,
          labels: labels,
          priority: priority,
          colorFrom: (c) => getColor(c.dataset.data[c.dataIndex].from),
          colorTo: (c) => getColor(c.dataset.data[c.dataIndex].to),
          borderWidth: 0,
          borderColor: '',
        }
      ]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: false,
        },
        title: {
          display: true,
          text: 'Connections between PED cases, PED relevant cases, PED labs and projects'
        }
      }
    }
  });
}

function makeChartA1P004(data, canvasId) {
  const metaIndex = data.caseStudies.meta.fieldKeys['A1P004'];
  const field = data.caseStudies.meta.fields[metaIndex];

  const choices = [];
  const choiceKeys = [];
  const choiceCounts = [];
  for (let i = 0; i < field.subfields.length; i++) {
    if (field.subfields[i].dataType !== 'boolean') {
      continue;
    }
    choices.push(field.subfields[i].label);
    choiceKeys.push(field.subfields[i].key);
    choiceCounts.push(0);
  }

  for (let i = 0; i < data.caseStudies.data.length; i++) {
    for (let j = 0; j < choiceKeys.length; j++) {
      if (data.caseStudies.data[i].data['A1P004'][choiceKeys[j]]) {
        choiceCounts[j]++;
      }
    }
  }

  const dataPie = {
    labels: choices,
    datasets: [
      {
        label: 'Targets of PED case studies / labs',
        data: choiceCounts,
        fill: true,
        backgroundColor: generateColors(choices.length),
      },
    ]
  };
  new Chart(document.getElementById(canvasId), {
    type: 'bar',
    data: dataPie,
    options: {
      maintainAspectRatio: false,
      responsive: true,
      plugins: {
        legend: {
          display: false,
          position: 'top',
        },
        title: {
          display: true,
          text: 'Targets of PED case studies / labs'
        }
      }
    },
  });
}

function makeChartA1P005(data, canvasId) {
  const metaIndex = data.caseStudies.meta.fieldKeys['A1P005'];
  const field = data.caseStudies.meta.fields[metaIndex];

  const dataPie = {
    labels: field.choices,
    datasets: [
      {
        label: 'Phases of PED case studies / labs',
        data: field.choices.map(choice => data.caseStudies.data.filter(cs => cs.data['A1P005'] === choice).length),
        fill: true,
        backgroundColor: generateColors(field.choices.length),
      },
    ]
  };
  new Chart(document.getElementById(canvasId), {
    type: 'pie',
    data: dataPie,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'Phases of PED case studies / labs'
        }
      }
    },
  });
}

function makeChartA3P006(data, canvasId) {
  const metaIndex = data.caseStudies.meta.fieldKeys['A3P006'];
  const section = data.caseStudies.meta.fields[metaIndex];
  const field = section.subfields[0];

  const dataPie = {
    labels: field.choices,
    datasets: [
      {
        label: 'Economic strategies',
        data: field.choices.map(choice => data.caseStudies.data.filter(cs => cs.data[section.key][field.key].includes(choice)).length),
        fill: true,
        backgroundColor: generateColors(field.choices.length),
      },
    ]
  };
  new Chart(document.getElementById(canvasId), {
    type: 'pie',
    data: dataPie,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'Economic strategies'
        }
      }
    },
  });
}

function makeChartA3P007(data, canvasId) {
  const metaIndex = data.caseStudies.meta.fieldKeys['A3P007'];
  const section = data.caseStudies.meta.fields[metaIndex];
  const field = section.subfields[0];

  const dataPie = {
    labels: field.choices,
    datasets: [
      {
        label: 'Social models',
        data: field.choices.map(choice => data.caseStudies.data.filter(cs => cs.data[section.key][field.key].includes(choice)).length),
        fill: true,
        backgroundColor: generateColors(field.choices.length),
      },
    ]
  };
  new Chart(document.getElementById(canvasId), {
    type: 'pie',
    data: dataPie,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'Social models'
        }
      }
    },
  });
}

function makeChartA3P009(data, canvasId) {
  const metaIndex = data.caseStudies.meta.fieldKeys['A3P009'];
  const section = data.caseStudies.meta.fields[metaIndex];
  const field = section.subfields[0];

  const dataPie = {
    labels: field.choices,
    datasets: [
      {
        label: 'Environmental strategies',
        data: field.choices.map(choice => data.caseStudies.data.filter(cs => cs.data[section.key][field.key].includes(choice)).length),
        fill: true,
        backgroundColor: generateColors(field.choices.length),
      },
    ]
  };
  new Chart(document.getElementById(canvasId), {
    type: 'pie',
    data: dataPie,
    options: {
      responsive: true,
      plugins: {
        legend: {
          position: 'top',
        },
        title: {
          display: true,
          text: 'Environmental strategies'
        }
      }
    },
  });
}
