<?php
require_once 'includes/config.php';

// ID kontrolü
if (!isset($_GET['id'])) {
    die('ID parametresi gerekli');
}

$id = (int)$_GET['id'];

try {
    // Veritabanı bağlantısı
    $db = db();
    
    // Domain adını al
    $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://") . $_SERVER['HTTP_HOST'];
    
    // Link bilgilerini al
    $stmt = $db->prepare("SELECT js_url FROM links WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $link = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$link) {
        die('Link bulunamadı');
    }
} catch (Exception $e) {
    die('Bir hata oluştu: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Önizleme - <?php echo htmlspecialchars($link['js_url']); ?></title>
    <script src="<?php echo $domain . htmlspecialchars($link['js_url']); ?>"></script>
</head>
<body>
</body>
</html> 