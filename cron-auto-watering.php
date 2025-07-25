<?php
// cron-auto-watering.php
// File ini untuk dijadwalkan di cron job untuk auto watering

$statusFile = 'iot_status.json';
$scheduleFile = 'watering_schedule.json';
$commandFile = 'iot_commands.json';

function readJSON($filename) {
    if (file_exists($filename)) {
        return json_decode(file_get_contents($filename), true);
    }
    return [];
}

function writeJSON($filename, $data) {
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

function logActivity($message) {
    $logFile = 'iot_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] AUTO: $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Baca jadwal penyiraman
$schedule = readJSON($scheduleFile);
if (empty($schedule) || !$schedule['auto_mode']) {
    exit; // Auto mode tidak aktif
}

// Baca status terakhir
$status = readJSON($statusFile);
$lastWatering = $status['last_watering'] ?? 0;
$currentTime = time();

// Cek apakah sudah waktunya penyiraman
$interval = $schedule['interval'] * 3600; // konversi jam ke detik
if (($currentTime - $lastWatering) >= $interval) {
    // Saatnya penyiraman otomatis
    $commands = readJSON($commandFile);
    $newCommand = [
        'id' => uniqid(),
        'timestamp' => $currentTime,
        'action' => 'water',
        'data' => [
            'action' => 'water',
            'duration' => $schedule['duration'],
            'type' => 'auto'
        ],
        'executed' => false
    ];
    
    $commands[] = $newCommand;
    writeJSON($commandFile, $commands);
    
    logActivity("Auto watering triggered - Duration: {$schedule['duration']}s");
}
?>