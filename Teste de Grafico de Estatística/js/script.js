document.addEventListener('DOMContentLoaded', function () {
    fetch('data.php')
      .then(response => {
        if (!response.ok) {
          throw new Error(`Erro na requisição: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (data.error) {
          console.error('Erro retornado pelo servidor:', data.error);
          return;
        }
  
        const ctx = document.getElementById('salesChart').getContext('2d');
  
        const salesChart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: data.vendas_diarias.labels,
            datasets: [
              {
                label: 'Vendas Diárias',
                data: data.vendas_diarias.dados,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                fill: false,
                tension: 0.1
              },
              {
                label: 'Vendas Semanais',
                data: data.vendas_semanais.dados,
                borderColor: 'rgba(153, 102, 255, 1)',
                backgroundColor: 'rgba(153, 102, 255, 0.2)',
                fill: false,
                tension: 0.1
              }
            ]
          },
          options: {
            responsive: true,
            plugins: {
              legend: {
                position: 'top',
              },
              title: {
                display: true,
                text: 'Volume de Vendas Diárias e Semanais'
              }
            },
            scales: {
              y: {
                beginAtZero: true
              }
            }
          }
        });
      })
      .catch(error => console.error('Erro ao carregar os dados:', error));
  });
  