<?php

// Function to get real client IP address when behind Cloudflare
function getRealClientIp() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Если X-Forwarded-For содержит несколько IP-адресов, берем первый
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    switch ($_GET['action']) {
        case 'latency':
            // Latency test - just return the timestamp
            echo json_encode(array('time' => microtime(true)));
            break;
        case 'upload':
            // Upload test - accept and measure upload speed
            echo json_encode(array('status' => 'OK', 'size' => $_SERVER['CONTENT_LENGTH']));
            break;
        case 'download':
            // Download test - generate random data dynamically
            $size = min((int)$_GET['size'], 50 * 1024 * 1024); // Max 50MB
            header('Content-Length: ' . $size);
            header('Cache-Control: no-store, no-cache, must-revalidate');
            $chunkSize = 8192;
            for ($sent = 0; $sent < $size; $sent += $chunkSize) {
                // Генерируем случайную строку нужной длины
                $randomData = str_repeat(uniqid(mt_rand(), true), $chunkSize);
                // Получаем подстроку нужной длины
                $dataChunk = substr($randomData, 0, min($chunkSize, $size - $sent));
                // Выводим данные и очищаем буфер вывода
                echo $dataChunk;
                flush();
            }
            break;
        default:
            echo json_encode(array('error' => 'Invalid action'));
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Network Speed Test</title>
    <style>
        :root {
            --orange: #FF6B35;
            --orange-light: #FF9E35;
            --blue: #4281A4;
            --blue-light: #8BB8D6;
            --dark: #2C3E50;
            --light: #F8F9FA;
            --gray: #E9ECEF;
        }
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            margin: 0;
            padding: 20px;
            color: var(--dark);
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            padding: 30px;
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h1 {
            color: var(--orange);
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.4rem;
        }
        .panel {
            background: var(--light);
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 5px solid var(--blue);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .panel:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }
        .panel h2 {
            margin-top: 0;
            color: var(--dark);
            margin-bottom: 20px;
            font-size: 1.5rem;
        }
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .btn {
            background: var(--orange);
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            flex: 1;
            min-width: 220px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn:hover {
            background: var(--orange-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }
        .btn-blue {
            background: var(--blue);
        }
        .btn-blue:hover {
            background: var(--blue-light);
            box-shadow: 0 4px 12px rgba(66, 129, 164, 0.3);
        }
        .btn:disabled {
            background: var(--gray);
            color: #999;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }
        .result-panel {
            margin-top: 25px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: none;
            animation: fadeIn 0.5s ease-out;
        }
        .progress-container {
            width: 100%;
            height: 25px;
            background: var(--gray);
            border-radius: 12px;
            margin: 25px 0;
            overflow: hidden;
            position: relative;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--orange), var(--orange-light));
            border-radius: 12px;
            width: 0%;
            transition: width 0.5s cubic-bezier(0.65, 0, 0.35, 1);
            position: relative;
            overflow: hidden;
        }
        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255,255,255,0) 0%,
                rgba(255,255,255,0.3) 50%,
                rgba(255,255,255,0) 100%
            );
            animation: shimmer 2s infinite;
            transform: translateX(-100%);
        }
        @keyframes shimmer {
            100% { transform: translateX(100%); }
        }
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: bold;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            font-size: 14px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stat-card {
            background: white;
            padding: 18px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-top: 4px solid var(--blue);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 1rem;
            color: var(--dark);
            font-weight: 600;
        }
        .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--orange);
            margin: 0;
        }
        .connection-info {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding: 12px;
            background: rgba(66, 129, 164, 0.1);
            border-radius: 8px;
        }
        .connection-status {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ccc;
            margin-right: 10px;
            position: relative;
        }
        .connection-status.active {
            background: #2ECC71;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(46, 204, 113, 0); }
            100% { box-shadow: 0 0 0 0 rgba(46, 204, 113, 0); }
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-card {
            background: rgba(248, 249, 250, 0.8);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--blue);
        }
        .info-card strong {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: var(--dark);
        }
        .info-card span {
            font-size: 1.1rem;
            color: var(--orange);
            font-weight: 600;
        }
        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }
            .btn {
                min-width: 100%;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Network Speed Test</h1>
        <div class="panel">
            <h2>Connection Information</h2>
            <div class="connection-info">
                <div class="connection-status" id="connectionStatusIcon"></div>
                <span id="connectionStatusText">Detecting your connection...</span>
            </div>
            <div class="info-grid">
                <div class="info-card">
                    <strong>IP Address</strong>
					<span id="ipAddress"><?php echo htmlspecialchars(getRealClientIp()); ?></span>
                </div>
                <div class="info-card">
                    <strong>Browser</strong>
                    <span id="userAgent">Detecting...</span>
                </div>
                <div class="info-card">
                    <strong>Network Type</strong>
                    <span id="networkType">Unknown</span>
                </div>
                <div class="info-card">
                    <strong>Server Location</strong>
                    <span id="serverLocation">Helsinki, Finland (stc01-upcloud)</span>
                </div>
            </div>
        </div>
        <div class="panel">
            <h2>Speed Test</h2>
            <p>Select the appropriate test for your connection:</p>
            <div class="btn-group">
                <button id="fastTest" class="btn">
                    🚀 Fast Test (15x5MB)
                </button>
                <button id="slowTest" class="btn btn-blue">
                    🐢 Slow Test (1x5MB)
                </button>
                <button id="stopTest" class="btn" disabled style="background: #e74c3c;">
                    ⏹ Stop Test
                </button>
            </div>
            <div class="progress-container">
                <div id="progressBar" class="progress-bar"></div>
                <div id="progressText" class="progress-text">0%</div>
            </div>
            <div id="speedResults" class="result-panel">
                <h2>Test Results</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Download Speed</h3>
                        <p id="downloadSpeed" class="stat-value">-</p>
                    </div>
                    <div class="stat-card">
                        <h3>Upload Speed</h3>
                        <p id="uploadSpeed" class="stat-value">-</p>
                    </div>
                    <div class="stat-card">
                        <h3>Latency</h3>
                        <p id="latency" class="stat-value">-</p>
                    </div>
                    <div class="stat-card">
                        <h3>Test Duration</h3>
                        <p id="testDuration" class="stat-value">-</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Current test state
        var currentTest = null;
        var abortController = null;
        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Display user agent
            document.getElementById('userAgent').textContent = navigator.userAgent;
            // Detect network information if available
            detectNetworkInfo();
            // Setup event listeners
            document.getElementById('fastTest').addEventListener('click', startFastTest);
            document.getElementById('slowTest').addEventListener('click', startSlowTest);
            document.getElementById('stopTest').addEventListener('click', stopCurrentTest);
        });
        // Detect network information
        function detectNetworkInfo() {
            var statusIcon = document.getElementById('connectionStatusIcon');
            var statusText = document.getElementById('connectionStatusText');
            var networkType = document.getElementById('networkType');
            statusIcon.classList.add('active');
            statusText.textContent = 'Connection detected';
            if (navigator.connection) {
                var connection = navigator.connection;
                // Network type
                var type = connection.effectiveType || connection.type || 'Unknown';
                networkType.textContent = type.charAt(0).toUpperCase() + type.slice(1);
                // Connection downlink
                if (connection.downlink) {
                    networkType.textContent += ' (~' + Math.round(connection.downlink) + ' Mbps)';
                }
                // Monitor connection changes
                connection.addEventListener('change', function() {
                    var newType = connection.effectiveType || connection.type || 'Unknown';
                    networkType.textContent = newType.charAt(0).toUpperCase() + newType.slice(1);
                    if (connection.downlink) {
                        networkType.textContent += ' (~' + Math.round(connection.downlink) + ' Mbps)';
                    }
                });
            } else {
                networkType.textContent = 'Not detectable';
            }
        }
        // Format speed (bits per second)
        function formatSpeed(bitsPerSecond) {
            if (bitsPerSecond >= 1000000000) {
                return (bitsPerSecond / 1000000000).toFixed(2) + ' Gbps';
            } else if (bitsPerSecond >= 1000000) {
                return (bitsPerSecond / 1000000).toFixed(2) + ' Mbps';
            } else if (bitsPerSecond >= 1000) {
                return (bitsPerSecond / 1000).toFixed(2) + ' Kbps';
            }
            return bitsPerSecond.toFixed(2) + ' bps';
        }
        // Format time (milliseconds)
        function formatTime(ms) {
            if (ms >= 1000) {
                return (ms / 1000).toFixed(2) + ' seconds';
            }
            return ms.toFixed(2) + ' ms';
        }
        // Update progress bar
        function updateProgress(percent, text) {
            var progressBar = document.getElementById('progressBar');
            var progressText = document.getElementById('progressText');
            progressBar.style.width = percent + '%';
            progressText.textContent = text || percent.toFixed(0) + '%';
            // Change color based on progress
            if (percent >= 90) {
                progressBar.style.background = 'linear-gradient(90deg, var(--orange), #2ecc71)';
            } else if (percent >= 50) {
                progressBar.style.background = 'linear-gradient(90deg, var(--orange), var(--orange-light))';
            }
        }
        // Test download speed
        function testDownload(size, iterations, callback) {
            var totalBytes = 0;
            var totalTime = 0;
            var completed = 0;
            var testStart = performance.now();
            // Create new AbortController for this test
            abortController = new AbortController();
            function runTest(i) {
                if (i >= iterations || abortController.signal.aborted) {
                    var testEnd = performance.now();
                    callback({
                        bytes: totalBytes,
                        time: totalTime,
                        totalDuration: testEnd - testStart
                    });
                    return;
                }
                updateProgress((i / iterations) * 100, 'Download ' + (i+1) + '/' + iterations);
                var xhr = new XMLHttpRequest();
                var startTime = performance.now();
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 2) { // HEADERS_RECEIVED
                        startTime = performance.now();
                    }
                    if (xhr.readyState === 4) { // DONE
                        var endTime = performance.now();
                        var duration = (endTime - startTime) / 1000; // in seconds
                        totalBytes += size;
                        totalTime += duration;
                        completed++;
                        updateProgress((completed / iterations) * 100);
                        // Small delay between tests
                        setTimeout(function() {
                            runTest(i + 1);
                        }, 200);
                    }
                };
                xhr.open('GET', '?action=download&size=' + size + '&r=' + Math.random(), true);
                // Support for aborting
                if (abortController) {
                    abortController.signal.addEventListener('abort', function() {
                        xhr.abort();
                    });
                }
                xhr.send();
            }
            runTest(0);
        }
        // Test upload speed
        function testUpload(size, callback) {
            updateProgress(0, 'Preparing upload...');
            var xhr = new XMLHttpRequest();
            var startTime = performance.now();
            // Generate random data for upload
            var data = new Uint8Array(size);
            for (var i = 0; i < size; i++) {
                data[i] = Math.floor(Math.random() * 256);
            }
            xhr.open('POST', '?action=upload', true);
            // Track upload progress
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percent = (e.loaded / e.total) * 100;
                    updateProgress(percent, 'Uploading ' + Math.round(percent) + '%');
                }
            };
            xhr.onload = function() {
                var endTime = performance.now();
                var duration = (endTime - startTime) / 1000; // in seconds
                callback({
                    bytes: size,
                    time: duration
                });
            };
            xhr.onerror = function() {
                callback({
                    bytes: 0,
                    time: 0,
                    error: 'Upload failed'
                });
            };
            // Support for aborting
            if (abortController) {
                abortController.signal.addEventListener('abort', function() {
                    xhr.abort();
                });
            }
            xhr.send(data);
        }
        // Test latency (ping)
        function testLatency(tests, callback) {
            var results = [];
            var completed = 0;
            var testStart = performance.now();
            function runTest() {
                if (completed >= tests || abortController.signal.aborted) {
                    var testEnd = performance.now();
                    // Calculate average latency
                    var sum = results.reduce(function(a, b) { return a + b; }, 0);
                    var avg = sum / results.length;
                    callback({
                        latency: avg,
                        totalDuration: testEnd - testStart
                    });
                    return;
                }
                updateProgress((completed / tests) * 100, 'Ping ' + (completed+1) + '/' + tests);
                var startTime = performance.now();
                var xhr = new XMLHttpRequest();
                xhr.open('GET', '?action=latency&t=' + startTime, true);
                xhr.onload = function() {
                    var endTime = performance.now();
                    results.push(endTime - startTime);
                    completed++;
                    // Small delay between pings
                    setTimeout(runTest, 500);
                };
                xhr.onerror = function() {
                    completed++; // Count failed attempts too
                    setTimeout(runTest, 500);
                };
                // Support for aborting
                if (abortController) {
                    abortController.signal.addEventListener('abort', function() {
                        xhr.abort();
                    });
                }
                xhr.send();
            }
            runTest();
        }
        // Start fast test (15x5MB)
        function startFastTest() {
            resetTestUI();
            currentTest = 'fast';
            var testStart = performance.now();
            var results = {
                download: { speed: 0 },
                upload: { speed: 0 },
                latency: 0,
                duration: 0
            };
            // Enable stop button
            document.getElementById('stopTest').disabled = false;
            // 1. Test download speed (15 chunks of 5MB)
            testDownload(5*1024*1024, 15, function(dlResult) {
                results.download.speed = (dlResult.bytes * 8) / dlResult.time;
                document.getElementById('downloadSpeed').textContent = formatSpeed(results.download.speed);
                // 2. Test upload speed (5MB)
                testUpload(5*1024*1024, function(ulResult) {
                    results.upload.speed = (ulResult.bytes * 8) / ulResult.time;
                    document.getElementById('uploadSpeed').textContent = formatSpeed(results.upload.speed);
                    // 3. Test latency (5 pings)
                    testLatency(5, function(latencyResult) {
                        results.latency = latencyResult.latency;
                        results.duration = performance.now() - testStart;
                        document.getElementById('latency').textContent = formatTime(results.latency);
                        document.getElementById('testDuration').textContent = formatTime(results.duration);
                        // Show results
                        document.getElementById('speedResults').style.display = 'block';
                        updateProgress(100, 'Test complete!');
                        // Reset test state
                        currentTest = null;
                        document.getElementById('stopTest').disabled = true;
                    });
                });
            });
        }
        // Start slow test (1x5MB)
        function startSlowTest() {
            resetTestUI();
            currentTest = 'slow';
            var testStart = performance.now();
            var results = {
                download: { speed: 0 },
                upload: { speed: 0 },
                latency: 0,
                duration: 0
            };
            // Enable stop button
            document.getElementById('stopTest').disabled = false;
            // 1. Test download speed (1 chunk of 5MB)
            testDownload(5*1024*1024, 1, function(dlResult) {
                results.download.speed = (dlResult.bytes * 8) / dlResult.time;
                document.getElementById('downloadSpeed').textContent = formatSpeed(results.download.speed);
                // 2. Test upload speed (1MB)
                testUpload(1*1024*1024, function(ulResult) {
                    results.upload.speed = (ulResult.bytes * 8) / ulResult.time;
                    document.getElementById('uploadSpeed').textContent = formatSpeed(results.upload.speed);
                    // 3. Test latency (3 pings)
                    testLatency(3, function(latencyResult) {
                        results.latency = latencyResult.latency;
                        results.duration = performance.now() - testStart;
                        document.getElementById('latency').textContent = formatTime(results.latency);
                        document.getElementById('testDuration').textContent = formatTime(results.duration);
                        // Show results
                        document.getElementById('speedResults').style.display = 'block';
                        updateProgress(100, 'Test complete!');
                        // Reset test state
                        currentTest = null;
                        document.getElementById('stopTest').disabled = true;
                    });
                });
            });
        }
        // Stop current test
        function stopCurrentTest() {
            if (abortController) {
                abortController.abort();
                updateProgress(0, 'Test stopped');
                document.getElementById('stopTest').disabled = true;
                currentTest = null;
            }
        }
        // Reset test UI
        function resetTestUI() {
            document.getElementById('speedResults').style.display = 'none';
            document.getElementById('downloadSpeed').textContent = '-';
            document.getElementById('uploadSpeed').textContent = '-';
            document.getElementById('latency').textContent = '-';
            document.getElementById('testDuration').textContent = '-';
            var progressBar = document.getElementById('progressBar');
            progressBar.style.background = 'linear-gradient(90deg, var(--orange), var(--orange-light))';
        }
    </script>
</body>
</html>