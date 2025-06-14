const params = new URLSearchParams(window.location.search);
const key = params.get('key');
if (!key) throw new Error("Missing event key");

fetch("nurse_saul.php?key=" + key)
  .then(res => res.json())
  .then(data => {
    const ctx = document.getElementById('nurseSaulChart').getContext('2d');
    const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#00A36C'];

    new Chart(ctx, {
      type: 'line',
      data: {
        datasets: data.datasets.map((d, i) => ({
          label: d.label,
          data: d.data,
          borderColor: colors[i % colors.length],
          borderWidth: 2,
          fill: false,
          tension: 0.2
        }))
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          title: { display: true, text: 'Temperature vs Maturity (Nurse-Saul)' },
          legend: { display: true, position: 'bottom' }
        },
        scales: {
          x: {
            title: { display: true, text: 'Maturity (°C·hrs)' },
            type: 'linear'
          },
          y: {
            title: { display: true, text: 'Temperature (°C)' }
          }
        }
      }
    });
  })
  .catch(console.error);
