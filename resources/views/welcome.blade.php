<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CryptoInvestment Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .card { border: none; border-radius: 12px; transition: 0.3s; }
        .crypto-card:hover { transform: scale(1.02); cursor: pointer; }
        .selected { border: 3px solid #0d6efd !important; box-shadow: 0 4px 15px rgba(13,110,253,0.2); }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="text-center fw-bold text-primary mb-2">CryptoInvestment</h1>
        <p class="text-center text-muted mb-4">Seguimiento Dinámico en Tiempo Real</p>

        <div class="row justify-content-center mb-4">
            <div class="col-md-6 text-center">
                <div class="input-group shadow-sm mb-2">
                    <input type="text" id="symbolInput" class="form-control" placeholder="Ej: BTC, ETH, ADA...">
                    <button class="btn btn-primary px-4" onclick="addNewCrypto()">Seguir Moneda</button>
                </div>
                <button class="btn btn-sm btn-link text-decoration-none" onclick="selectCrypto(null)">Ver Todas las Monedas</button>
            </div>
        </div>

        <div id="cryptoCards" class="row g-3 mb-5"></div>

        <div class="card shadow-sm p-4">
            <h4 class="fw-bold mb-3" id="chartTitle">Línea de Tiempo Comparativa</h4>
            <canvas id="mainChart" height="100"></canvas>
        </div>
    </div>

<script>
    let chart;
    let allData = [];
    let selectedId = null;

    async function addNewCrypto() {
        const symbol = document.getElementById('symbolInput').value;
        if(!symbol) return;
        const res = await fetch('/api/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ symbol })
        });
        if(res.ok) {
            document.getElementById('symbolInput').value = '';
            loadDashboard();
        } else { alert("Error al buscar moneda"); }
    }

    async function loadDashboard() {
        try {
            await fetch('/api/update');
            const res = await fetch('/api/data');
            allData = await res.json();
            renderUI();
        } catch (e) { console.error(e); }
    }

    function renderUI() {
        const container = document.getElementById('cryptoCards');
        container.innerHTML = allData.length === 0 ? '<p class="text-center w-100">Agrega una moneda para empezar...</p>' : '';
        
        allData.forEach(c => {
            if (!c.histories || c.histories.length === 0 || parseFloat(c.histories[0].price) === 0) return;
            const latest = c.histories[0];
            const isSelected = selectedId == c.id ? 'selected' : '';
            const colorClass = latest.percent_change_24h >= 0 ? 'text-success' : 'text-danger';

            container.innerHTML += `
                <div class="col-md-4 col-6">
                    <div class="card shadow-sm p-3 mb-2 crypto-card ${isSelected}" onclick="selectCrypto(${c.id})">
                        <small class="text-muted fw-bold d-block">${c.name} (${c.symbol})</small>
                        <h3 class="mb-0 fw-bold">$${parseFloat(latest.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</h3>
                        <p class="mb-0 ${colorClass} fw-bold" style="font-size: 0.9rem">
                            ${parseFloat(latest.percent_change_24h).toFixed(2)}% (24h)
                        </p>
                    </div>
                </div>`;
        });
        renderChart();
    }

    function selectCrypto(id) {
        selectedId = id;
        document.getElementById('chartTitle').innerText = id ? 'Detalle de Moneda' : 'Línea de Tiempo Comparativa';
        renderUI();
    }

    function renderChart() {
        if(allData.length === 0) return;
        const ctx = document.getElementById('mainChart').getContext('2d');
        const displayData = selectedId ? allData.filter(c => c.id === selectedId) : allData;
        
        // Evitar error si la moneda filtrada no tiene historial
        if (displayData.length === 0 || !displayData[0].histories.length) return;

        const labels = displayData[0].histories.map(h => new Date(h.created_at).toLocaleTimeString()).reverse();
        const datasets = displayData.map(c => ({
            label: c.symbol,
            data: c.histories.map(h => h.price).reverse(),
            borderColor: '#' + (Math.random().toString(16) + '000000').substring(2,8),
            tension: 0.3, fill: selectedId ? true : false
        }));

        if(chart) chart.destroy();
        chart = new Chart(ctx, { type: 'line', data: { labels, datasets } });
    }

    loadDashboard();
    setInterval(loadDashboard, 60000);
</script>
</body>
</html>