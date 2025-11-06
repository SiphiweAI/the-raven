document.addEventListener("DOMContentLoaded", function() {
    if (typeof forecastResults === 'undefined') return;


    const ctx = document.getElementById('forecastChart').getContext('2d');


    // Choose a single product for now (or loop through multiple products)
    const productIds = Object.keys(forecastResults);
    if (!productIds.length) return;


    const productId = productIds[0]; // first product
    const { actual, forecast } = forecastResults[productId];


    // Create date labels for actual and forecast
    const actualCount = actual.length;
    const forecastCount = forecast.length;


    const actualDates = Array.from({ length: actualCount }, (_, i) => `Day ${i + 1}`);
    const forecastDates = Array.from({ length: forecastCount }, (_, i) => `Day ${actualCount + i + 1}`);


    const labels = [...actualDates, ...forecastDates];


    // Combine actual and forecast for plotting
    const actualPlot = [...actual, ...Array(forecastCount).fill(null)];
    const forecastPlot = [...Array(actualCount).fill(null), ...forecast];


    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Actual Sales',
                    data: actualPlot,
                    borderColor: 'blue',
                    fill: false
                },
                {
                    label: 'Forecast',
                    data: forecastPlot,
                    borderColor: 'orange',
                    borderDash: [5,5],
                    fill: false
                }
            ]
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
