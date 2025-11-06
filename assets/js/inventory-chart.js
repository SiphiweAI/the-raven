document.addEventListener("DOMContentLoaded", function() {
  if (typeof invValues === 'undefined') return;


  const ctx = document.getElementById('inventoryChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: invLabels,
      datasets: [{
        label: 'Inventory Metrics',
        data: invValues,
        backgroundColor: ['#3498db', '#f1c40f', '#2ecc71']
      }]
    },
    options: {
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, title: { display: true, text: 'Units' } } }
    }
  });
});