console.log('visualizations.js loaded');

/* MAIN */
jQuery(document).ready(function() {
    jQuery.get("/wp-json/ped-eu/v1/everything", function(data, status){
        console.log(data);
        jQuery('#pedeu-visualizations').html('<div id="pedeu-visualization-laboratory"></div>');
        makeLaboratory("pedeu-visualization-laboratory", data);
    });
})

function makeLaboratory(container, data) {
    const lab = new PEDLaboratory(container, data);
    lab.init();
}


// PED Laboratory
class PEDLaboratory {
    constructor(elemId, data) {
        this.elem = jQuery(`#${elemId}`);
        this.data = data;

        this.caseStudiesSelector = new CaseStudySelector(elemId, data);
        this.visualizations = [
            {
                title: "A1P022: Financial schemes (types)",
                function: makeHistogramSection,
                params: {'key': 'A1P022'},
            },
            {
                title: "A2P007-10: Energy demands",
                function: makeBarChart,
                params: {'keys': ['A2P007', 'A2P008', 'A2P009', 'A2P010'], 'units': 'GWh/annum'},
            },
            {
                title: "A2P023: Technological Solutions / Innovations - Energy Generation",
                function: makeHistogramSection,
                params: {'key': 'A2P023'},
            },
            {
                title: "A2P024: Technological Solutions / Innovations - Energy Flexibility",
                function: makeHistogramSection,
                params: {'key': 'A2P024'},
            },
            {
                title: "A2P025: Technological Solutions / Innovations - Energy Efficiency",
                function: makeHistogramSection,
                params: {'key': 'A2P025'},
            },
            {
                title: "A2P026: Technological Solutions / Innovations - Mobility",
                function: makeHistogramSection,
                params: {'key': 'A2P026'},
            },
            {
                title: "C1P001: Unlocking factors",
                function: makeRadarDiagramLikert,
                params: {'key': 'C1P001'},
            },
            {
                title: "C1P002: Driving factors",
                function: makeRadarDiagramLikert,
                params: {'key': 'C1P002'},
            },
            {
                title: "C1P003: Administrative barriers",
                function: makeRadarDiagramLikert,
                params: {'key': 'C1P003'},
            },
            {
                title: "C1P004: Policy barriers",
                function: makeRadarDiagramLikert,
                params: {'key': 'C1P004'},
            },
            {
                title: "C1P005: Legal and regulatory barriers",
                function: makeRadarDiagramLikert,
                params: {'key': 'C1P005'},
            },
            {
                title: "C1P008: Social and cultural barriers",
                function: makeRadarDiagramLikert,
                params: {'key': 'C1P008'},
            },
            {
                title: "C1P009: Information and awareness barriers",
                function: makeRadarDiagramLikert,
                params: {'key': 'C1P009'},
            },
            {
                title: "C1P010: Financial barriers",
                function: makeRadarDiagramLikert,
                params: {'key': 'C1P010'},
            },
            {
                title: "C1P011: Market barriers",
                function: makeRadarDiagramLikert,
                params: {'key': 'C1P011'},
            },
            {
                title: "C1P012: Stakeholders involvement",
                function: makeSankeyStakeholders,
                params: {},
            },
        ];
        this.selectedVisualizationIndex = null;
    }

    init() {
        this.caseStudiesSelector.init();
        this.caseStudiesSelector.callbacks.push(function(){ this.renderVisualization(); }.bind(this));
        this.renderVisualizationSelect();
        this.elem.append('<div id="pedeu-visualization-chart"></div>');
        this.renderVisualization();
    }

    renderVisualizationSelect() {
        const select = jQuery('<select id="pedeu-visualization-select"></select>');
        select.append('<option value="">-- Select a Visualization --</option>');
        this.visualizations.forEach((visualization, index) => {
            select.append(`<option value="${index}">${visualization.title}</option>`);
        });
        this.elem.append(select);
        select.change((e) => {
            const index = e.target.value;
            if (index) {
                this.selectedVisualizationIndex = parseInt(index);
            } else {
                this.selectedVisualizationIndex = null;
            }
            this.renderVisualization();
        });
    }

    renderVisualization() {
        const div = jQuery('#pedeu-visualization-chart');
        div.html('');

        if (this.selectedVisualizationIndex === null) {
            div.append('<div class="no-visualization"></div>');
        } else {
            const visualization = this.visualizations[this.selectedVisualizationIndex];
            div.append(`<div class="chart-wrap card-100"><canvas id="visualization"></canvas><div id="visualization-appendix"></div></div>`);
            waitForEl(`#visualization`, () => {
                visualization.function(`visualization`, this.data, this.caseStudiesSelector.selectedIds, visualization.params);
            });
        }
    }
}

class CaseStudySelector {
    constructor(elemId, data) {
        this.elem = jQuery(`#${elemId}`);
        this.caseStudies = data.caseStudies.data.sort((a, b) => a.data["A1P001"].localeCompare(b.data["A1P001"]));
        this.selectedIds = [];
        this.currentId = null;
        this.callbacks = [];
        this.expanded = false;
    }

    invokeCallbacks() {
        this.callbacks.forEach((callback) => callback(this.selectedIds));
    }

    init() {
        // init controls
        this.elem.append('<div id="pedeu-case-studies-selector"></div>');
        this.elem.append('<div id="pedeu-selected-case-studies-wrap"><div id="pedeu-selected-case-studies-info"></div><div id="pedeu-selected-case-studies"></div></div>');
        this.renderAll();
    }

    removeCaseStudy(id) {
        this.selectedIds = this.selectedIds.filter((selectedId) => selectedId !== id);
        this.renderAll();
        this.invokeCallbacks();
    }

    addCaseStudy(id) {
        this.selectedIds.push(id);
        this.renderAll();
        this.invokeCallbacks();
    }

    addAll() {
        this.selectedIds = this.caseStudies.map((caseStudy) => caseStudy.entryId);
        this.renderAll();
        this.invokeCallbacks();
    }

    removeAll() {
        this.selectedIds = [];
        this.renderAll();
        this.invokeCallbacks();
    }

    isCaseStudySelected(id) {
        return this.selectedIds.includes(id);
    }

    renderAll() {
        this.renderSelect();
        this.renderInfo();
        this.renderSelected();
    }

    renderInfo() {
        const info = jQuery('#pedeu-selected-case-studies-info');
        info.html('');
        info.append(`<div class="text">${this.selectedIds.length} PED sites selected</div>`);

        const removeAllButton = jQuery('<button>Remove all</button>');
        info.append(removeAllButton);
        if (this.selectedIds.length === 0) {
            removeAllButton.attr('disabled', 'disabled');
        }
        removeAllButton.click(() => {
            this.removeAll();
        });

        if (this.expanded) {
            const collapseButton = jQuery('<button>Collapse</button>');
            info.append(collapseButton);
            collapseButton.click(() => {
                this.expanded = false;
                this.renderInfo();
                this.renderSelected();
            });
        } else {
            const expandButton = jQuery('<button>Expand</button>');
            info.append(expandButton);
            expandButton.click(() => {
                this.expanded = true;
                this.renderInfo();
                this.renderSelected();
            });
        }
    }

    renderSelected() {
        const selected = jQuery('#pedeu-selected-case-studies');

        if (this.expanded) {
            selected.addClass('expanded');
            selected.removeClass('collapsed');
        } else {
            selected.removeClass('expanded');
            selected.addClass('collapsed');
        }

        selected.html('');
        this.selectedIds.forEach((entryId) => {
            const caseStudy = this.caseStudies.find((caseStudy) => caseStudy.entryId === entryId);
            if (!caseStudy) {
                return;
            }
            selected.append(`<div id="cs-selected-${entryId}"></div>`);
            const selectedDiv = jQuery(`#cs-selected-${entryId}`);
            selectedDiv.append(`<span>${caseStudy.data["A1P001"]}</span>`);
            const removeButton = jQuery('<button>Remove</button>');
            selectedDiv.append(removeButton);
            removeButton.click(() => {
                this.removeCaseStudy(entryId);
            });
        });
    }

    renderSelect() {
        const selectDiv = jQuery('#pedeu-case-studies-selector');
        const addCaseStudyButton = jQuery('<button id="pedeu-add-case-study">Add</button>');
        selectDiv.html('');
        const select = jQuery('<select id="pedeu-case-study-select"></select>');
        select.append('<option value="">-- Select a PED Site and press Add --</option>');
        this.caseStudies.forEach((caseStudy) => {
            const disabled = this.isCaseStudySelected(caseStudy.entryId) ? 'disabled' : '';
            select.append(`<option value="${caseStudy.entryId}" ${disabled}>${caseStudy.data["A1P001"]}</option>`);
        });
        selectDiv.append(select);
        select.change((e) => {
            this.currentId = e.target.value;
            if (this.isCaseStudySelected(this.currentId) || !this.currentId) {
                addCaseStudyButton.attr('disabled', 'disabled');
            } else {
                addCaseStudyButton.removeAttr('disabled');
            }
        });
        if (this.isCaseStudySelected(this.currentId) || !this.currentId) {
            addCaseStudyButton.attr('disabled', 'disabled');
        }

        selectDiv.append(addCaseStudyButton);
        addCaseStudyButton.click(() => {
            if (this.currentId && !this.isCaseStudySelected(this.currentId)) {
                this.addCaseStudy(this.currentId);
            }
        });

        const addAllButton = jQuery('<button id="pedeu-add-all">Add all</button>');
        selectDiv.append(addAllButton);
        if (this.selectedIds.length === this.caseStudies.length) {
            addAllButton.attr('disabled', 'disabled');
        }
        addAllButton.click(() => {
            this.addAll();
        });
    }
}

function makeRadarDiagramLikert(elemId, data, caseStudyIds, params) {
    const fieldKey = params.key;
    const field = data.caseStudies.meta.fields[data.caseStudies.meta.fieldKeys[fieldKey]];
    const subfields = field.subfields.filter((subfield) => subfield.fieldType === "select");
    const labels = subfields.map((subfield) => subfield.key);
    const datasets = caseStudyIds.map((caseStudyId) => {
        const caseStudy = data.caseStudies.data.find((caseStudy) => caseStudy.entryId === caseStudyId);
        const csLabel = caseStudy.data["A1P001"];
        const csData = subfields.map((subfield) => {
            if (!caseStudy.data[fieldKey][subfield.key]) {
                return 0;
            }
            const val = parseInt(caseStudy.data[fieldKey][subfield.key].substring(0, 1));
            if (isNaN(val) || val < 1 || val > 5) {
                return 0;
            }
            return val;
        });
        return {
            label: csLabel,
            data: csData,
            fill: true,
        };
    });

    const appendix = jQuery(`#${elemId}-appendix`);
    appendix.html('');
    const table = jQuery('<table></table>');
    table.append('<thead><tr><th>Factor</th><th>Label</th></tr></thead>');
    const tbody = jQuery('<tbody></tbody>');
    table.append(tbody);
    subfields.forEach((subfield, index) => {
        tbody.append(`<tr><td>${subfield.key}</td><td>${subfield.label}</td></tr>`);
    });
    appendix.append(table);

    // chart
    new Chart(document.getElementById(elemId), {
        type: 'radar',
        data: {
            labels: labels,
            datasets: datasets
        },
        options: {
            elements: {
                line: {
                    borderWidth: 3
                }
            },
            scales: {
                r: {
                    angleLines: {
                        display: true,
                    },
                    min: 0,
                    max: 5,
                    beginAtZero: true,
                    ticks: {
                        count: 6,
                    }
                }
            }
        }
    });
}

function makeBarChart(elemId, data, caseStudyIds, params) {
    const keys = params.keys;
    const datasets = caseStudyIds.map((caseStudyId) => {
        const caseStudy = data.caseStudies.data.find((caseStudy) => caseStudy.entryId === caseStudyId);
        const csLabel = caseStudy.data["A1P001"];
        const csData = keys.map((key) => {
            if (!caseStudy.data[key]) {
                return 0;
            }
            return parseFloat(caseStudy.data[key]);
        });
        return {
            label: csLabel,
            data: csData,
            fill: true,
        };
    });

    const appendix = jQuery(`#${elemId}-appendix`);
    appendix.html('');
    const table = jQuery('<table></table>');
    table.append('<thead><tr><th>Key</th><th>Label</th></tr></thead>');
    const tbody = jQuery('<tbody></tbody>');
    table.append(tbody);
    keys.forEach((key, index) => {
        const field = data.caseStudies.meta.fields[data.caseStudies.meta.fieldKeys[key]];
        tbody.append(`<tr><td>${key}</td><td>${field.label}</td></tr>`);
    });
    appendix.append(table);

    // chart
    new Chart(document.getElementById(elemId), {
        type: 'bar',
        data: {
            labels: keys,
            datasets: datasets,
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: params.units,
                    },
                },
            },
        },
    });
}

function makeHistogramSection(elemId, data, caseStudyIds, params) {
    const fieldKey = params.key;
    const field = data.caseStudies.meta.fields[data.caseStudies.meta.fieldKeys[fieldKey]];
    const subfields = field.subfields.filter((subfield) => subfield.dataType === "boolean");
    const labels = subfields.map((subfield) => subfield.key);
    const datasets = caseStudyIds.map((caseStudyId) => {
        const caseStudy = data.caseStudies.data.find((caseStudy) => caseStudy.entryId === caseStudyId);
        const csLabel = caseStudy.data["A1P001"];
        const csData = subfields.map((subfield) => {
            if (!caseStudy.data[fieldKey][subfield.key]) {
                return 0;
            }
            return caseStudy.data[fieldKey][subfield.key] ? 1 : 0;
        });
        return {
            label: csLabel,
            data: csData,
            fill: true,
        };
    });

    const appendix = jQuery(`#${elemId}-appendix`);
    appendix.html('');
    const table = jQuery('<table></table>');
    table.append('<thead><tr><th>Factor</th><th>Label</th></tr></thead>');
    const tbody = jQuery('<tbody></tbody>');
    table.append(tbody);
    subfields.forEach((subfield, index) => {
        tbody.append(`<tr><td>${subfield.key}</td><td>${subfield.label}</td></tr>`);
    });
    appendix.append(table);

    // chart
    new Chart(document.getElementById(elemId), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: datasets,
        },
        options: {
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    beginAtZero: true,
                    stacked: true,
                },
            },
        },
    });
}

function makeSankeyStakeholders(elemId, data, caseStudyIds, params) {
    const phases = [
        "Planning/leading",
        "Design/demand aggregation",
        "Construction/implementation",
        "Monitoring/operation/management",
    ];
    const field = data.caseStudies.meta.fields[data.caseStudies.meta.fieldKeys["C1P012"]];
    const subfields = field.subfields.filter((subfield) => subfield.dataType === "list");
    const stakeholders = subfields.map((subfield) => subfield.label);
    const fromTo = new Map();
    for (const phase of phases) {
        for (const stakeholder of stakeholders) {
            fromTo.set(`${phase} -> ${stakeholder}`, 0);
        }
    }

    for (const caseStudyId of caseStudyIds) {
        const caseStudy = data.caseStudies.data.find((caseStudy) => caseStudy.entryId === caseStudyId);
        subfields.forEach((subfield) => {
            const stakeholder = subfield.label;
            const phases = caseStudy.data["C1P012"][subfield.key];
            if (!phases) {
                return;
            }
            phases.filter((phase) => phases.includes(phase)).forEach((phase) => {
                fromTo.set(`${phase} -> ${stakeholder}`, fromTo.get(`${phase} -> ${stakeholder}`) + 1);
            });
        })
    }

    const dataSankey = [];
    for (const phase of phases) {
        for (const stakeholder of stakeholders) {
            const flowValue = fromTo.get(`${phase} -> ${stakeholder}`);
            if (flowValue > 0) {
                dataSankey.push({from: phase, to: stakeholder, flow: fromTo.get(`${phase} -> ${stakeholder}`)});
            }
        }
    }

    const colors = {
        "Planning/leading": "#00A19B",
        "Design/demand aggregation": "#005850",
        "Construction/implementation": "#B2D489",
        "Monitoring/operation/management": "#FDC01C",
    };
    const priority = {
        "Planning/leading": 0,
        "Design/demand aggregation": 1,
        "Construction/implementation": 2,
        "Monitoring/operation/management": 3,
    };
    for (const stakeholder of stakeholders) {
        colors[stakeholder] = "gray";
        priority[stakeholder] = stakeholders.indexOf(stakeholder);
    }


    function getColor(name) {
        return colors[name] || "gray";
    }

    if (dataSankey.length === 0) {
        const canvas = document.getElementById(elemId);
        const ctx = canvas.getContext('2d');
        ctx.font = '20px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('No data available', canvas.width / 2, canvas.height / 2);
        return;
    }

    new Chart(document.getElementById(elemId), {
        type: "sankey",
        data: {
            datasets: [
                {
                    data: dataSankey,
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
                    display: false,
                }
            }
        }
    });
}


let waitForEl = function(selector, callback) {
    if (jQuery(selector).length) {
        callback();
    } else {
        setTimeout(function() {
            waitForEl(selector, callback);
        }, 100);
    }
};
