function getThemeTextColor() {
  return getComputedStyle(document.body).getPropertyValue('--header-text').trim();
}

function loadNurseSaulGraph() {
  const params = new URLSearchParams(window.location.search);
  const key = params.get('key');
  if (!key) throw new Error("Missing event key");

  fetch("nurse_saul.php?key=" + key)
    .then(res => res.json())
    .then(data => {
      const ctx = document.getElementById('nurseSaulChart').getContext('2d');
      const themeTextColor = getThemeTextColor();
      const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#E7E9ED', '#00A36C'];

      if (window.nurseSaulChartInstance) {
        window.nurseSaulChartInstance.destroy();
      }

      window.nurseSaulChartInstance = new Chart(ctx, {
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
            title: {
              display: true,
              text: 'Temperature vs Maturity (Nurse-Saul)',
              color: themeTextColor
            },
            legend: {
              display: true,
              position: 'bottom',
              labels: {
                color: themeTextColor
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.dataset.label || 'Probe';
                  const temp = context.parsed.y;
                  const maturity = context.parsed.x;
                  return `${label}: ${temp.toFixed(1)}°C at ${maturity.toFixed(0)} °C·hrs`;
                }
              }
            }
          },
          scales: {
            x: {
              title: {
                display: true,
                text: 'Maturity (°C·hrs)',
                color: themeTextColor
              },
              ticks: {
                color: themeTextColor
              },
              type: 'linear'
            },
            y: {
              title: {
                display: true,
                text: 'Temperature (°C)',
                color: themeTextColor
              },
              ticks: {
                color: themeTextColor
              }
            }
          }
        }
      });
    })
    .catch(console.error);
}

// Delay until DOM and styles are ready
window.addEventListener('DOMContentLoaded', loadNurseSaulGraph);
