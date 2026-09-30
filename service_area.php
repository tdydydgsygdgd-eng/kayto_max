<?php
session_start();
require "db.php";
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: index.php");
    exit;
}

// Data 31 kecamatan Surabaya dengan koordinat pusat
$kecamatan = [
    ["name" => "Genteng",         "lat" => -7.2656, "lng" => 112.7394],
    ["name" => "Bubutan",         "lat" => -7.2421, "lng" => 112.7300],
    ["name" => "Simokerto",       "lat" => -7.2367, "lng" => 112.7478],
    ["name" => "Pabean Cantian",  "lat" => -7.2202, "lng" => 112.7386],
    ["name" => "Semampir",        "lat" => -7.2163, "lng" => 112.7603],
    ["name" => "Krembangan",      "lat" => -7.2267, "lng" => 112.7183],
    ["name" => "Kenjeran",        "lat" => -7.2063, "lng" => 112.7872],
    ["name" => "Bulak",           "lat" => -7.1891, "lng" => 112.7869],
    ["name" => "Tambaksari",      "lat" => -7.2468, "lng" => 112.7722],
    ["name" => "Gubeng",          "lat" => -7.2767, "lng" => 112.7558],
    ["name" => "Wonokromo",       "lat" => -7.3044, "lng" => 112.7378],
    ["name" => "Tegalsari",       "lat" => -7.2812, "lng" => 112.7278],
    ["name" => "Sawahan",         "lat" => -7.2934, "lng" => 112.7183],
    ["name" => "Asemrowo",        "lat" => -7.2267, "lng" => 112.7028],
    ["name" => "Benowo",          "lat" => -7.2434, "lng" => 112.6394],
    ["name" => "Pakal",           "lat" => -7.2133, "lng" => 112.6497],
    ["name" => "Lakarsantri",     "lat" => -7.3267, "lng" => 112.6478],
    ["name" => "Sambikerep",      "lat" => -7.2667, "lng" => 112.6661],
    ["name" => "Sukomanunggal",   "lat" => -7.2700, "lng" => 112.7044],
    ["name" => "Tandes",          "lat" => -7.2434, "lng" => 112.6939],
    ["name" => "Dukuh Pakis",     "lat" => -7.2967, "lng" => 112.7044],
    ["name" => "Wiyung",          "lat" => -7.3200, "lng" => 112.6939],
    ["name" => "Karang Pilang",   "lat" => -7.3467, "lng" => 112.6961],
    ["name" => "Wonocolo",        "lat" => -7.3367, "lng" => 112.7300],
    ["name" => "Gayungan",        "lat" => -7.3333, "lng" => 112.7469],
    ["name" => "Jambangan",       "lat" => -7.3467, "lng" => 112.7211],
    ["name" => "Tenggilis Mejoyo","lat" => -7.3267, "lng" => 112.7700],
    ["name" => "Gunung Anyar",    "lat" => -7.3467, "lng" => 112.7922],
    ["name" => "Rungkut",         "lat" => -7.3133, "lng" => 112.7939],
    ["name" => "Sukolilo",        "lat" => -7.2833, "lng" => 112.7939],
    ["name" => "Mulyorejo",       "lat" => -7.2567, "lng" => 112.8017],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jangkauan Layanan Surabaya - Kayto Drive</title>
    <meta name="description" content="Kayto Drive melayani seluruh 31 kecamatan di Surabaya. Cek area jangkauan layanan kami di peta interaktif.">
    <link rel="stylesheet" href="style.css">
    <!-- Leaflet.js -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .leaflet-container { background: #0D0F1A !important; }
        .leaflet-popup-content-wrapper {
            background: rgba(20,22,35,0.97);
            border: 1px solid rgba(108,99,255,0.4);
            border-radius: 12px;
            color: #F0F2FF;
            backdrop-filter: blur(20px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.5);
        }
        .leaflet-popup-tip { background: rgba(20,22,35,0.97); }
        .leaflet-popup-content { margin: 14px 18px; font-family: 'Inter', sans-serif; }
        .popup-name { font-weight: 700; font-size: 15px; margin-bottom: 4px; color: #fff; }
        .popup-badge { display: inline-flex; align-items: center; gap: 4px; background: rgba(0,212,170,0.15); border: 1px solid rgba(0,212,170,0.3); color: #00D4AA; padding: 2px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; }
    </style>
</head>

<body>
    <!-- HEADER -->
    <div class="header">
        <div class="header-brand">
            <span class="brand-icon">☁️</span>
            <h1>Kayto Drive</h1>
        </div>
        <div class="header-nav">
            <a href="dashboard.php" class="btn-nav secondary" id="btn-dashboard">🏠 Dashboard</a>
            <a href="logout.php" class="btn-nav danger" id="btn-logout">🚪 Logout</a>
        </div>
    </div>

    <div class="page-container">

        <!-- HERO -->
        <div class="service-hero">
            <h1>🗺️ Jangkauan Layanan</h1>
            <p>Kayto Drive tersedia untuk seluruh wilayah Surabaya. Temukan area kecamatan yang terlayani di bawah ini.</p>
        </div>

        <!-- STATS ROW -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number">31</div>
                <div class="stat-label">Kecamatan Terlayani</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">330+</div>
                <div class="stat-label">Kelurahan Dicakup</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">100%</div>
                <div class="stat-label">Gratis untuk Warga</div>
            </div>
        </div>

        <!-- MAP -->
        <div id="map-container">
            <div id="map"></div>
        </div>

        <!-- SECTION LABEL -->
        <div class="section-label">📍 Daftar 31 Kecamatan Surabaya</div>

        <!-- KECAMATAN GRID -->
        <div class="kecamatan-grid" id="kecamatan-list">
            <?php foreach($kecamatan as $i => $kec): ?>
            <div class="kec-item" id="kec-<?= $i ?>" style="animation-delay: <?= $i * 0.03 ?>s; animation: fadeUp 0.4s ease both;">
                <div class="kec-dot"></div>
                <span><?= htmlspecialchars($kec['name']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

    </div><!-- end .page-container -->

    <script>
        // Inisialisasi peta Leaflet
        const map = L.map('map', {
            center: [-7.2754, 112.7417],
            zoom: 12,
            zoomControl: true,
        });

        // Dark tile layer menggunakan CartoDB Dark Matter
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 19
        }).addTo(map);

        // Data kecamatan dari PHP
        const kecamatanData = <?= json_encode($kecamatan) ?>;

        // Custom marker icon
        function createMarker(name, lat, lng, index) {
            const marker = L.circleMarker([lat, lng], {
                radius: 10,
                fillColor: '#6C63FF',
                color: '#00D4AA',
                weight: 2,
                opacity: 1,
                fillOpacity: 0.85
            });

            marker.bindPopup(`
                <div class="popup-name">📍 Kec. ${name}</div>
                <span class="popup-badge">✅ Terlayani</span>
            `);

            marker.on('mouseover', function() {
                this.setStyle({ fillColor: '#00D4AA', radius: 13 });
                this.openPopup();
            });

            marker.on('mouseout', function() {
                this.setStyle({ fillColor: '#6C63FF', radius: 10 });
            });

            marker.on('click', function() {
                // Highlight kec item di bawah
                document.querySelectorAll('.kec-item').forEach(el => el.style.borderColor = '');
                const kecEl = document.getElementById('kec-' + index);
                if(kecEl) {
                    kecEl.style.borderColor = 'rgba(108,99,255,0.8)';
                    kecEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            return marker;
        }

        // Tambahkan semua marker
        kecamatanData.forEach((kec, i) => {
            createMarker(kec.name, kec.lat, kec.lng, i).addTo(map);
        });

        // Circle coverage besar
        L.circle([-7.2754, 112.7417], {
            radius: 22000,
            color: 'rgba(108,99,255,0.4)',
            fillColor: 'rgba(108,99,255,0.05)',
            fillOpacity: 1,
            weight: 1.5,
            dashArray: '6 6'
        }).addTo(map);

        // Klik kec-item → fly ke marker
        document.querySelectorAll('.kec-item').forEach((el, i) => {
            el.addEventListener('click', () => {
                const kec = kecamatanData[i];
                map.flyTo([kec.lat, kec.lng], 15, { duration: 1.2 });
                document.querySelectorAll('.kec-item').forEach(e => e.style.borderColor = '');
                el.style.borderColor = 'rgba(108,99,255,0.8)';
            });
            el.style.cursor = 'pointer';
        });
    </script>
</body>

</html>
