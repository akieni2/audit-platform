<canvas id="riskHeatMap"></canvas>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

fetch('/risk-map')
.then(response => response.json())
.then(data => {

    const dataset = data.map(r => ({
        x: r.probabilite_inherent,
        y: r.impact_inherent
    }));

    new Chart(
        document.getElementById('riskHeatMap'),
        {
            type: 'scatter',
            data: {
                datasets: [{
                    label: 'Risques',
                    data: dataset
                }]
            }
        }
    );

});

</script>
