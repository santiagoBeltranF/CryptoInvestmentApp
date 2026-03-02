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
        .crypto-card:hover { transform: scale(1.02); }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="text-center fw-bold text-primary mb-2">CryptoInvestment</h1>
        <p class="text-center text-muted mb-4">Seguimiento Dinámico en Tiempo Real</p>

        <div class="row justify-content-center mb-5">
            <div class="col-md-6">
                <div class="input-group shadow-sm">
                    <input type="text" id="symbolInput" class="form-control" placeholder="Ej: BTC, ETH, ADA...">
                    <button class="btn btn-primary px-4" onclick="addNewCrypto()">Seguir Moneda</button>
                </div>
            </div>
        </div>

    
        <div id="cryptoCards" class="row g-3 mb-5"></div>

   
        <div class="card shadow-sm p-4">
            <h4 class="fw-bold mb-3">Línea de Tiempo de Precios</h4>
            <canvas id="mainChart" height="100"></canvas>
        </div>
    </div>

<script>
    let chart;
    let allData = [];
    let selectedCryptoId = null;

    async function addNewCrypto() {
        const symbol = document.getElementById('symbolInput').value;
        if(!symbol) return;
        
        const res = await fetch('/api/add', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ symbol })
        });
        const data = await res.json();
        if(data.status === 'success') {
            document.getElementById('symbolInput').value = '';
            loadDashboard(); 
        } else { alert(data.message); }
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
        container.innerHTML = allData.length === 0 ? '<p class="text-center w-100">Busca una moneda para empezar...</p>' : '';
        

        if (allData.length > 0) {
            container.innerHTML += `
                <div class="col-12 mb-3 text-end">
                    <button class="btn btn-sm btn-outline-secondary" onclick="selectCrypto(null)">Ver Todas las Monedas</button>
                </div>`;
        }

        allData.forEach(c => {
            const latest = c.histories[0];
            const isSelected = selectedCryptoId == c.id ? 'border-primary border-4 shadow' : 'border-0';
            
            container.innerHTML += `
                <div class="col-md-4 col-6">
                    <div class="card shadow-sm p-3 mb-3 crypto-card ${isSelected}" 
                         style="cursor: pointer; border-radius: 15px;" 
                         onclick="selectCrypto(${c.id})">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-bold">${c.name} (${c.symbol})</small>
                            ${selectedCryptoId == c.id ? '<span class="badge bg-primary">Seleccionada</span>' : ''}
                        </div>
                        <h2 class="mb-0 fw-bold">$${parseFloat(latest.price).toLocaleString(undefined, {minimumFractionDigits: 2})}</h2>
                        <p class="mb-0 ${latest.percent_change_24h >= 0 ? 'text-success' : 'text-danger'} fw-bold">
                            ${latest.percent_change_24h}% (24h)
                        </p>
                    </div>
                </div>`;
        });
        renderChart();
    }

  
    function selectCrypto(id) {
        selectedCryptoId = id;
        renderUI(); 
    }

    function renderChart() {
        if(allData.length === 0) return;
        const ctx = document.getElementById('mainChart').getContext('2d');
        

        const dataToDisplay = selectedCryptoId 
            ? allData.filter(c => c.id === selectedCryptoId) 
            : allData;

        const labels = dataToDisplay[0].histories.map(h => new Date(h.created_at).toLocaleTimeString()).reverse();
        const datasets = dataToDisplay.map(c => ({
            label: c.symbol,
            data: c.histories.map(h => h.price).reverse(),
            borderColor: getCryptoColor(c.symbol),
            backgroundColor: getCryptoColor(c.symbol, 0.1),
            fill: selectedCryptoId ? true : false, 
            tension: 0.3, 
            pointRadius: 4
        }));

        if(chart) chart.destroy();
        chart = new Chart(ctx, { 
            type: 'line', 
            data: { labels, datasets },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: selectedCryptoId ? 'Detalle Histórico de ' + dataToDisplay[0].symbol : 'Comparativa del Mercado'
                    }
                }
            }
        });
    }

   
    function getCryptoColor(symbol, alpha = 1) {
        const colors = { 'BTC': '#f7931a', 'ETH': '#627eea', 'SOL': '#14f195', 'ADA': '#0033ad', 'BNB': '#f3ba2f' };
        const base = colors[symbol] || '#' + Math.floor(Math.random()*16777215).toString(16);
        return base + (alpha < 1 ? Math.floor(alpha * 255).toString(16).padStart(2, '0') : '');
    }

    loadDashboard();
    setInterval(loadDashboard, 60000);
</script>
</body>
</html>