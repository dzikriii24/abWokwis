<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Penyiraman IoT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes pulse-grow {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .pulse-grow {
            animation: pulse-grow 2s infinite;
        }

        @keyframes water-drop {
            0% {
                transform: translateY(-20px);
                opacity: 0;
            }

            50% {
                opacity: 1;
            }

            100% {
                transform: translateY(20px);
                opacity: 0;
            }
        }

        .water-animation {
            animation: water-drop 1.5s infinite;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-green-50 to-blue-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-green-700 mb-2">🌱 Sistem Penyiraman IoT</h1>
            <p class="text-gray-600">Kontrol penyiraman lahan secara otomatis</p>
        </div>

        <!-- Status Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Status Sistem</h2>
                    <p id="status-text" class="text-gray-600">Standby</p>
                    <p id="last-update" class="text-sm text-gray-500 mt-1">Terakhir update: -</p>
                </div>
                <div id="status-indicator" class="w-16 h-16 bg-gray-400 rounded-full flex items-center justify-center text-2xl">
                    💧
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <!-- Manual Control -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    🎮 Kontrol Manual
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Durasi Penyiraman (detik)
                        </label>
                        <input type="range" id="duration-slider"
                            class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
                            min="5" max="60" value="10">
                        <div class="flex justify-between text-xs text-gray-500 mt-1">
                            <span>5s</span>
                            <span id="duration-value" class="font-medium text-blue-600">10s</span>
                            <span>60s</span>
                        </div>
                    </div>

                    <button id="manual-water"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed">
                        💧 Siram Sekarang
                    </button>
                </div>
            </div>

            <!-- Automatic Schedule -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    ⏰ Jadwal Otomatis
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Interval Penyiraman (jam)
                        </label>
                        <select id="interval-select" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="0">Matikan Auto</option>
                            <option value="1">Setiap 1 Jam</option>
                            <option value="2">Setiap 2 Jam</option>
                            <option value="4">Setiap 4 Jam</option>
                            <option value="6">Setiap 6 Jam</option>
                            <option value="12">Setiap 12 Jam</option>
                            <option value="24">Setiap 24 Jam</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Durasi Auto (detik)
                        </label>
                        <input type="number" id="auto-duration"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                            min="5" max="60" value="15">
                    </div>

                    <button id="save-schedule"
                        class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                        💾 Simpan Jadwal
                    </button>
                </div>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                📋 Log Aktivitas
            </h3>
            <div id="activity-log" class="space-y-2 max-h-48 overflow-y-auto">
                <p class="text-gray-500 text-sm">Belum ada aktivitas...</p>
            </div>
        </div>

        <!-- Water Animation -->
        <div id="water-animation" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-6xl hidden">
            <div class="water-animation">💧</div>
        </div>
    </div>

    <script>
        // State management
        let systemState = {
            isWatering: false,
            autoMode: false,
            interval: 0,
            autoDuration: 15,
            lastWatering: null
        };

        // DOM elements
        const durationSlider = document.getElementById('duration-slider');
        const durationValue = document.getElementById('duration-value');
        const manualWaterBtn = document.getElementById('manual-water');
        const intervalSelect = document.getElementById('interval-select');
        const autoDurationInput = document.getElementById('auto-duration');
        const saveScheduleBtn = document.getElementById('save-schedule');
        const statusText = document.getElementById('status-text');
        const statusIndicator = document.getElementById('status-indicator');
        const lastUpdate = document.getElementById('last-update');
        const activityLog = document.getElementById('activity-log');
        const waterAnimation = document.getElementById('water-animation');

        // Update duration display
        durationSlider.addEventListener('input', function() {
            durationValue.textContent = this.value + 's';
        });

        // Manual watering
        manualWaterBtn.addEventListener('click', function() {
            const duration = parseInt(durationSlider.value);
            startWatering('manual', duration);
        });

        // Save schedule
        saveScheduleBtn.addEventListener('click', function() {
            const interval = parseInt(intervalSelect.value);
            const autoDuration = parseInt(autoDurationInput.value);

            systemState.interval = interval;
            systemState.autoDuration = autoDuration;
            systemState.autoMode = interval > 0;

            sendToIoT({
                action: 'schedule',
                interval: interval,
                duration: autoDuration,
                autoMode: systemState.autoMode
            });

            addLog(`Jadwal ${interval > 0 ? 'diaktifkan: setiap ' + interval + ' jam, durasi ' + autoDuration + 's' : 'dimatikan'}`);
            updateStatus();
        });

        // Start watering function
        function startWatering(type, duration) {
            if (systemState.isWatering) return;

            systemState.isWatering = true;
            systemState.lastWatering = new Date();

            // Send to IoT
            sendToIoT({
                action: 'water',
                duration: duration,
                type: type
            });

            // Update UI
            updateStatus();
            showWaterAnimation();
            addLog(`Penyiraman ${type} dimulai (${duration}s)`);

            // Disable manual button
            manualWaterBtn.disabled = true;
            manualWaterBtn.textContent = `💧 Menyiram... (${duration}s)`;

            // Countdown
            let remaining = duration;
            const countdown = setInterval(() => {
                remaining--;
                manualWaterBtn.textContent = `💧 Menyiram... (${remaining}s)`;

                if (remaining <= 0) {
                    clearInterval(countdown);
                    stopWatering();
                }
            }, 1000);
        }

        // Stop watering function
        function stopWatering() {
            systemState.isWatering = false;

            // Update UI
            updateStatus();
            hideWaterAnimation();
            addLog('Penyiraman selesai');

            // Re-enable manual button
            manualWaterBtn.disabled = false;
            manualWaterBtn.textContent = '💧 Siram Sekarang';
        }

        // Send data to IoT (simulated PHP endpoint)
        function sendToIoT(data) {
            console.log('Sending to IoT:', data);

            fetch('iot-control.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(response => {
                    if (!response.ok) throw new Error("Network error");
                    return response.json();
                })
                .then(result => {
                    console.log("Response from backend:", result);
                    // Optional: tampilkan feedback ke user
                })
                .catch(error => {
                    console.error("Error sending command:", error);
                    // Optional: kasih notifikasi error ke user
                });
        }


        // Update status display
        function updateStatus() {
            const now = new Date();

            if (systemState.isWatering) {
                statusText.textContent = 'Sedang Menyiram';
                statusIndicator.className = 'w-16 h-16 bg-blue-500 rounded-full flex items-center justify-center text-2xl pulse-grow';
                statusIndicator.textContent = '💧';
            } else if (systemState.autoMode) {
                statusText.textContent = `Auto Mode: Setiap ${systemState.interval} jam`;
                statusIndicator.className = 'w-16 h-16 bg-green-500 rounded-full flex items-center justify-center text-2xl';
                statusIndicator.textContent = '⏰';
            } else {
                statusText.textContent = 'Standby - Mode Manual';
                statusIndicator.className = 'w-16 h-16 bg-gray-400 rounded-full flex items-center justify-center text-2xl';
                statusIndicator.textContent = '💧';
            }

            lastUpdate.textContent = `Terakhir update: ${now.toLocaleTimeString('id-ID')}`;
        }

        // Show water animation
        function showWaterAnimation() {
            waterAnimation.classList.remove('hidden');
            setTimeout(() => waterAnimation.classList.add('hidden'), 3000);
        }

        // Hide water animation
        function hideWaterAnimation() {
            waterAnimation.classList.add('hidden');
        }

        // Add log entry
        function addLog(message) {
            const logEntry = document.createElement('div');
            logEntry.className = 'text-sm p-2 bg-gray-50 rounded border-l-2 border-blue-300';
            logEntry.innerHTML = `<span class="text-gray-500">${new Date().toLocaleTimeString('id-ID')}</span> - ${message}`;

            if (activityLog.children.length === 1 && activityLog.children[0].textContent.includes('Belum ada')) {
                activityLog.innerHTML = '';
            }

            activityLog.insertBefore(logEntry, activityLog.firstChild);

            // Keep only last 10 entries
            while (activityLog.children.length > 10) {
                activityLog.removeChild(activityLog.lastChild);
            }
        }

        // Auto watering simulation (in real app, this would be handled by PHP cron job)
        function simulateAutoWatering() {
            if (systemState.autoMode && !systemState.isWatering) {
                const now = new Date();
                const lastWater = systemState.lastWatering;

                if (!lastWater || (now - lastWater) >= (systemState.interval * 60 * 60 * 1000)) {
                    startWatering('auto', systemState.autoDuration);
                }
            }
        }

        // Check auto watering every minute (for demo purposes)
        setInterval(simulateAutoWatering, 60000);

        // Initialize
        updateStatus();
        addLog('Sistem penyiraman IoT siap digunakan');
    </script>
</body>

</html>