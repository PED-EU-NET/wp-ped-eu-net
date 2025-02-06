const map_element = document.getElementById('ped-eu-map');

if (typeof (map_element) != 'undefined' && map_element != null) {
    const map = L.map('ped-eu-map', {
        minZoom: 2,
        maxZoom: 18,
    });
    map.setView([51.1657, 10.4515], 4);
    const pluginUrl = ped_data.plugin_url;
    const data = JSON.parse(ped_data.posts);

    const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://openstreetmap.org">OpenStreetMap</a> Contributors',
        maxZoom: 18,
    });
    map.addLayer(tiles);

    const markers = L.markerClusterGroup({
        spiderfyOnMaxZoom: false,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: false,
        maxClusterRadius: 20,
    });
    markers.on('clusterclick', function (a) {
        a.layer.zoomToBounds({padding: [10, 10]});
    });

    const searchParams = new URLSearchParams(window.location.search);
    const compareIds = [];
    if (searchParams.has("compare")) {
        searchParams.get("compare").split(".").forEach((compareId) => {
            if (!compareIds.includes(compareId)) {
                compareIds.push(compareId.trim());
            }
        })
    }

    for (const k in data) {
        const caseStudy = data[k];
        const long = caseStudy.long;
        const lat = caseStudy.lat;
        const title = caseStudy.name;
        const id = caseStudy.id.toString();

        // prepare marker graphics
        let marker_icon = "ped-combined";
        if (!caseStudy.is_ped_lab && !caseStudy.is_ped_relevant_case_study && !caseStudy.is_ped_case_study) {
            marker_icon = "ped-none";
        } else if (caseStudy.is_ped_lab && !caseStudy.is_ped_relevant_case_study && !caseStudy.is_ped_case_study) {
            marker_icon = "ped-lab";
        } else if (!caseStudy.is_ped_lab && caseStudy.is_ped_relevant_case_study && !caseStudy.is_ped_case_study) {
            marker_icon = "ped-relevant";
        } else if (!caseStudy.is_ped_lab && !caseStudy.is_ped_relevant_case_study && caseStudy.is_ped_case_study) {
            marker_icon = "ped-case-study";
        }
        const mapMarker = L.icon({
            iconUrl: `${pluginUrl}static/images/map/${marker_icon}.svg`,
            iconSize: [36, 51], // size of the icon
        });

        // prepare open and compare links
        const isSomeOpen = searchParams.has('case_study');
        const isCurrent = isSomeOpen && searchParams.get('case_study') === id;
        const isCompared = isCurrent || compareIds.includes(id);

        const openUrl = new URL(window.location.href);
        openUrl.searchParams.set("case_study", id)
        const openLink = isCurrent ? "<em>Opened</em>" : `<a href="${openUrl.href}">Open</a>`;

        const compareUrl = new URL(window.location.href);
        if (!isCompared) {
            compareUrl.searchParams.set("compare", [...compareIds, id].join("."));
        }
        const cmpLink = (!isSomeOpen || isCompared) ? "" : `<a href="${compareUrl.href}">Compare</a>`;

        // add marker
        const marker = L.marker([lat, long], {
            title: title,
            icon: mapMarker,
        });
        markers.addLayer(marker);
        marker.bindPopup(`<h5 class='event_test'>${title}</h5><div class='map_marker_info'>${openLink} ${cmpLink}</div>`);
    }

    map.addLayer(markers);
}
