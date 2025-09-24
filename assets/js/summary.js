$(document).ready(function () {
    (function () {
        const D = window.SUMMARY_DATA || {statusCounts: {}, typeCounts: {}, workload: {}};

        function toSeries(obj) {
            const labels = Object.keys(obj);
            const data = labels.map(k => obj[k]);
            return {labels, data};
        }

        (function initStatus() {
            const s = toSeries(D.statusCounts);
            const total = s.data.reduce((a, b) => a + b, 0);
            const opts = {
                chart: {type: 'donut', height: 300},
                series: s.data,
                labels: s.labels,
                noData: {text: 'No data'},
                legend: {position: 'bottom'},
                dataLabels: {formatter: (val) => total ? val.toFixed(0) + '%' : ''},
            };

            new ApexCharts(document.getElementById("chartStatus"), opts).render();
        })();

        (function initTypes() {
            const s = toSeries(D.typeCounts);
            const total = s.data.reduce((a, b) => a + b, 0);

            const opts = {
                chart: {type: 'donut', height: 300},
                series: s.data,
                labels: s.labels,
                colors: ['#0d6efd', '#dc3545'],
                noData: {text: 'No data'},
                legend: {position: 'bottom'},
                dataLabels: {formatter: (val) => total ? val.toFixed(0) + '%' : ''},
            };
            new ApexCharts(document.getElementById("chartTypes"), opts).render();
        })();

        (function initWorkload() {
            const s = toSeries(D.workload);
            const sum = s.data.reduce((a, b) => a + b, 0) || 1;
            const pct = s.data.map(v => Math.round((v * 1000) / sum) / 10); // 0.1% preciznost

            const opts = {
                chart: {type: 'bar', height: 320},
                series: [{name: 'Work %', data: pct}],
                xaxis: {categories: s.labels, labels: {rotate: -20}},
                plotOptions: {
                    bar: {horizontal: true, borderRadius: 4}
                },
                dataLabels: {
                    enabled: true,
                    formatter: (val) => val + '%'
                },
                tooltip: {
                    y: {formatter: (val, {dataPointIndex}) => `${val}% (${s.data[dataPointIndex]} tickets)`}
                },
                noData: {text: 'No data'}
            };
            new ApexCharts(document.getElementById("chartWorkload"), opts).render();
        })();

    })();
});