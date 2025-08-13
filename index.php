<?php
function gercek_ip_al() {
    $ip_keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            // Birden fazla IP varsa (virgülle ayrılmış), ilkini al
            $ip_list = explode(',', $_SERVER[$key]);
            $ip = trim($ip_list[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

// İzin verilen IP'ler
$izinli_ipler = ['::1', '111.222.333.444']; // kendi IP'lerini ekle

$ziyaretci_ip = gercek_ip_al();

if (!in_array($ziyaretci_ip, $izinli_ipler)) {
    http_response_code(403);
    echo "403 Forbidden - / " . $ziyaretci_ip ." /IP yetkiniz yok.";
    exit;
}



// collections.json dosyası yoksa oluştur
if (!file_exists('collections.json')) {
    file_put_contents('collections.json', json_encode(new stdClass()));
}

$collections = json_decode(file_get_contents('collections.json'), true);

// Klasör ekle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_folder'])) {
    $folder = trim($_POST['folder_name']);
    if ($folder !== '' && !isset($collections[$folder])) {
        $collections[$folder] = [];
        file_put_contents('collections.json', json_encode($collections, JSON_PRETTY_PRINT));
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Koleksiyon Kaydet/Güncelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_collection'])) {
    $folder = $_POST['folder'] ?? 'Genel';
    $id = $_POST['collection_id'] ?? '';
    $data = [
        'name' => $_POST['name'],
        'url' => $_POST['url'],
        'method' => $_POST['method'],
        'headers' => $_POST['headers'],
        'body' => $_POST['body']
    ];
    if (!isset($collections[$folder])) $collections[$folder] = [];
    if ($id !== '' && isset($collections[$folder][$id])) {
        $collections[$folder][$id] = $data; // Güncelle
    } else {
        $collections[$folder][] = $data; // Yeni ekle
    }
    file_put_contents('collections.json', json_encode($collections, JSON_PRETTY_PRINT));
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Koleksiyon Sil
if (isset($_GET['delete']) && isset($_GET['folder'])) {
    $folder = $_GET['folder'];
    $id = (int) $_GET['delete'];
    if (isset($collections[$folder][$id])) {
        unset($collections[$folder][$id]);
        $collections[$folder] = array_values($collections[$folder]);
        file_put_contents('collections.json', json_encode($collections, JSON_PRETTY_PRINT));
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// İstek Gönder
$response = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    $url = $_POST['url'];
    $method = strtoupper($_POST['method']);
    $headers = array_filter(array_map('trim', explode("\n", $_POST['headers'])));
    $body = $_POST['body'];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    } elseif ($method !== 'GET') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $result = curl_exec($ch);
    curl_close($ch);

    $json = json_decode($result, true);
    $response = $json ? json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $result;
}

// Form düzenleme
$edit = ['name'=>'','url'=>'','method'=>'POST','headers'=>'','body'=>''];
$editId = '';
$editFolder = '';
if (isset($_GET['load'], $_GET['folder']) && isset($collections[$_GET['folder']][$_GET['load']])) {
    $edit = $collections[$_GET['folder']][$_GET['load']];
    $editId = $_GET['load'];
    $editFolder = $_GET['folder'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Mini PHP Postacı</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f5f6fa; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        textarea { font-family: monospace; }
        #body { height: 250px; resize: vertical; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; }
        .collection-list { max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body class="p-4">
<div class="container">
    <h2 class="mb-4">📮 Mini PHP Postacı</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card p-3 mb-3">
                <h5>Klasör Oluştur</h5>
                <form method="post" class="d-flex gap-2">
                    <input type="text" name="folder_name" class="form-control" placeholder="Klasör Adı" required>
                    <button type="submit" name="new_folder" class="btn btn-secondary">Ekle</button>
                </form>
            </div>
            <div class="card p-3">
                <h5>Koleksiyonlar</h5>
                <div class="collection-list">
                    <?php foreach ($collections as $folderName => $items): ?>
                        <div class="mb-2">
                            <strong><?= htmlspecialchars($folderName) ?></strong>
                            <ul class="list-group mt-1">
                                <?php foreach ($items as $i => $col): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <a href="?load=<?= $i ?>&folder=<?= urlencode($folderName) ?>">
                                            <?= htmlspecialchars($col['name']) ?>
                                        </a>
                                        <a href="?delete=<?= $i ?>&folder=<?= urlencode($folderName) ?>" class="text-danger">🗑️</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-3 mb-3">
                <form method="post">
                    <input type="hidden" name="collection_id" value="<?= $editId ?>">
                    <div class="mb-2">
                        <label>Klasör</label>
                        <select name="folder" class="form-select" required>
                            <?php foreach (array_keys($collections) as $folder): ?>
                                <option value="<?= htmlspecialchars($folder) ?>" <?= $editFolder === $folder ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($folder) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Koleksiyon Adı</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($edit['name']) ?>" required>
                    </div>
                    <div class="mb-2">
                        <label>URL</label>
                        <input type="text" name="url" class="form-control" value="<?= htmlspecialchars($edit['url']) ?>" required>
                    </div>
                    <div class="mb-2">
                        <label>Metod</label>
                        <select name="method" class="form-select">
                            <?php foreach (['GET','POST','PUT','DELETE','PATCH'] as $m): ?>
                                <option value="<?= $m ?>" <?= $edit['method']==$m?'selected':'' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label>Header'lar (satır satır)</label>
                        <textarea name="headers" class="form-control" rows="3"><?= htmlspecialchars($edit['headers']) ?></textarea>
                    </div>
                    <div class="mb-2">
                        <label>Body</label>
                        <textarea id="body" name="body" class="form-control"><?= htmlspecialchars($edit['body']) ?></textarea>
                    </div>
                    <button type="submit" name="send_request" class="btn btn-primary">Gönder</button>
                    <button type="submit" name="save_collection" class="btn btn-success">Kaydet</button>
                    <a href="index.php" class="btn btn-danger">Yeni</a>


                </form>
            </div>
            <?php if ($response): ?>
                <div class="card p-3">
                    <h5>Yanıt</h5>
                    <pre><?= htmlspecialchars($response) ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
