<?php
// iot-control.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, ngrok-skip-browser-warning');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// File untuk menyimpan status dan perintah
$statusFile = 'iot_status.json';
$commandFile = 'iot_commands.json';

// Fungsi untuk membaca file JSON
function readJSON($filename) {
    if (file_exists($filename)) {
        $content = file_get_contents($filename);
        return json_decode($content, true);
    }
    return [];
}

// Fungsi untuk menulis file JSON
function writeJSON($filename, $data) {
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
}

// Fungsi untuk log aktivitas
function logActivity($message) {
    $logFile = 'iot_log.txt';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Handle request dari web interface
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }
    
    $commands = readJSON($commandFile);
    $newCommand = [
        'id' => uniqid(),
        'timestamp' => time(),
        'action' => $input['action'],
        'data' => $input,
        'executed' => false
    ];
    
    $commands[] = $newCommand;
    writeJSON($commandFile, $commands);
    
    logActivity("New command: " . $input['action'] . " - " . json_encode($input));
    
    echo json_encode([
        'success' => true, 
        'command_id' => $newCommand['id'],
        'message' => 'Command queued successfully'
    ]);
    exit;
}

// Handle request dari IoT device (GET untuk polling commands)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_commands':
            // IoT device polling untuk perintah baru
            $commands = readJSON($commandFile);
            $pendingCommands = array_filter($commands, function($cmd) {
                return !$cmd['executed'];
            });
            
            echo json_encode([
                'commands' => array_values($pendingCommands),
                'count' => count($pendingCommands)
            ]);
            break;
            
        case 'mark_executed':
            // IoT device menandai perintah sudah dieksekusi
            $commandId = $_GET['command_id'] ?? '';
            $status = $_GET['status'] ?? 'completed';
            
            if ($commandId) {
                $commands = readJSON($commandFile);
                foreach ($commands as &$cmd) {
                    if ($cmd['id'] == $commandId) {
                        $cmd['executed'] = true;
                        $cmd['execution_status'] = $status;
                        $cmd['executed_at'] = time();
                        break;
                    }
                }
                writeJSON($commandFile, $commands);
                
                logActivity("Command executed: $commandId - Status: $status");
                
                echo json_encode(['success' => true, 'message' => 'Command marked as executed']);
            } else {
                echo json_encode(['error' => 'Command ID required']);
            }
            break;
            
        case 'update_status':
            // IoT device mengirim status terkini
            $status = [
                'timestamp' => time(),
                'pump_status' => $_GET['pump_status'] ?? 'off',
                'water_level' => $_GET['water_level'] ?? 100,
                'soil_moisture' => $_GET['soil_moisture'] ?? 50,
                'last_watering' => $_GET['last_watering'] ?? null,
                'auto_mode' => $_GET['auto_mode'] ?? false,
                'connection' => 'online'
            ];
            
            writeJSON($statusFile, $status);
            logActivity("Status update: " . json_encode($status));
            
            echo json_encode(['success' => true, 'message' => 'Status updated']);
            break;
            
        case 'get_status':
            // Web interface meminta status terkini
            $status = readJSON($statusFile);
            if (empty($status)) {
                $status = [
                    'timestamp' => time(),
                    'pump_status' => 'off',
                    'water_level' => 100,
                    'soil_moisture' => 50,
                    'last_watering' => null,
                    'auto_mode' => false,
                    'connection' => 'offline'
                ];
            }
            
            // Check if device is online (last update within 2 minutes)
            if (time() - $status['timestamp'] > 120) {
                $status['connection'] = 'offline';
            }
            
            echo json_encode($status);
            break;
            
        case 'get_logs':
            // Menampilkan log aktivitas
            $logFile = 'iot_log.txt';
            if (file_exists($logFile)) {
                $logs = file($logFile);
                $logs = array_reverse(array_slice($logs, -50)); // 50 log terakhir
                echo json_encode(['logs' => $logs]);
            } else {
                echo json_encode(['logs' => []]);
            }
            break;
            
        default:
            echo json_encode([
                'info' => 'IoT Control API',
                'endpoints' => [
                    'POST /' => 'Send command to IoT',
                    'GET /?action=get_commands' => 'Get pending commands (for IoT)',
                    'GET /?action=mark_executed&command_id=X' => 'Mark command as executed',
                    'GET /?action=update_status&pump_status=X&...' => 'Update device status',
                    'GET /?action=get_status' => 'Get current status',
                    'GET /?action=get_logs' => 'Get activity logs'
                ]
            ]);
    }
    exit;
}
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
?>