<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoInvestment Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f7f6; }
        .crypto-card { transition: transform 0.2s; border: none; border-radius: 15px; }
        .crypto-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <div class="container py-5">
        <header class="text-center mb-5">
            <h1 class="display-4 fw-bold text-primary">CryptoInvestment</h1>
            <p class="text-muted">Seguimiento en tiempo real de activos digitales</p>
        </header>

        <!-- Cards de Precios en tiempo real -->
        <div id="crypto-cards" class="row g-4 mb-5">
            <div class="text-center">Cargando datos del mercado...</div>
        </div>

        <!-- Gráfico de líneas de tiempo -->
        <div class="card shadow-sm border-0 p-4" style="border-radius: 15px;">
            <h3 class="mb-4">Evolución de Precios (Línea de tiempo)</h3>
            <canvas id="cryptoChart" height="120"></canvas>
        </div>
    </div>

    <script>
        let myChart;

        async function refreshData() {
            try {
                // Actualiza datos desde la API de CMC a nuestra DB
                await fetch('/api/update');
                // Obtiene los datos actualizados de nuestra DB
                const res = await fetch('/api/data');
                const data = await res.json();
                
                renderUI(data);
            } catch (e) { console.error("Error cargando datos", e); }
        }

        function renderUI(data) {
            const container = document.getElementById('crypto-cards');
            container.innerHTML = '';
            
            data.forEach(crypto => {
                const latest = crypto.histories[0];
                const colorClass = latest.percent_change_24h >= 0 ? 'text-success' : 'text-danger';
                
                container.innerHTML += `
                    <div class="col-md-4">
                        <div class="card crypto-card shadow-sm p-3">
                            <div class="card-body">
                                <h6 class="text-muted mb-1">${crypto.name}</h6>
                                <h3 class="fw-bold">${crypto.symbol}</h3>
                                <h2 class="text-dark">$${parseFloat(latest.price).toLocaleString()}</h2>
                                <p class="${colorClass} fw-bold mb-0">
                                    ${latest.percent_change_24h}% (24h)
                                </p>
                            </div>
                        </div>
                    </div>`;
            });
            updateChart(data);
        }

        function updateChart(data) {
            const ctx = document.getElementById('cryptoChart').getContext('2d');
            const labels = data[0].histories.map(h => new Date(h.created_at).toLocaleTimeString()).reverse();
            
            const datasets = data.map(crypto => ({
                label: crypto.symbol,
                data: crypto.histories.map(h => h.price).reverse(),
                borderWidth: 3,
                tension: 0.4,
                pointRadius: 0
            }));

            if (myChart) myChart.destroy();
            myChart = new Chart(ctx, {
                type: 'line',
                data: { labels, datasets },
                options: { responsive: true, plugins: { legend: { position: 'top' } } }
            });
        }

        // Actualización automática cada 30 segundos (Sin recargar la página)
        refreshData();
        setInterval(refreshData, 30000);
    </script>
</body>
</html>