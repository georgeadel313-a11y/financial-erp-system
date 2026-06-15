<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "financial_erp";

try {
    // إنشاء الاتصال بقاعدة البيانات
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    
    // تفعيل وضع الأخطاء ليوضح لنا أي مشكلة برمجة
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("فشل الاتصال بقاعدة البيانات: " . $e->getMessage());
}
?>