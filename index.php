<?php
// 1. استدعاء ملف الاتصال بقاعدة البيانات
require_once 'db.php';

// 2. معالجة البيانات القادمة من الفورم عند الضغط على زر الحفظ (POST Request)
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_account'])) {
    $account_code = trim($_POST['account_code']);
    $account_name = trim($_POST['account_name']);
    $account_type = trim($_POST['account_type']);

    // التأكد من أن الحقول ليست فارغة
    if (!empty($account_code) && !empty($account_name) && !empty($account_type)) {
        try {
            // كود إدخال البيانات في جدول accounts بقاعدة البيانات
            $sql = "INSERT INTO accounts (account_code, account_name, account_type) VALUES (:code, :name, :type)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':code' => $account_code,
                ':name' => $account_name,
                ':type' => $account_type
            ]);
            $message = "تم إضافة الحساب بنجاح إلى دليل الحسابات! ✨";
            $messageType = "success";
        } catch(PDOException $e) {
            $message = "خطأ: تعذر إضافة الحساب. قد يكون الكود مكرراً. " . $e->getMessage();
            $messageType = "error";
        }
    } else {
        $message = "يرجى ملء جميع الحقول المطلوبة!";
        $messageType = "error";
    }
}

// 3. جلب الحسابات المحدثة من الجدول لعرضها
try {
    $stmt = $conn->prepare("SELECT * FROM accounts ORDER BY account_code ASC");
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطأ في جلب البيانات: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دليل الحسابات التفاعلي | نظام ERP المالي</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: #f4f6f9; 
            margin: 0; 
            padding: 20px; 
            color: #333;
        }
        .container { 
            max-width: 900px; 
            margin: 20px auto; 
        }
        .card {
            background: white; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        h1, h2 { 
            color: #2c3e50; 
            margin-top: 0;
            font-weight: 700;
        }
        h1 { text-align: center; font-size: 28px; margin-bottom: 30px; }
        h2 { font-size: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; }
        
        /* تنسيق الفورم */
        .form-group-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
        }
        input, select {
            font-family: 'Cairo', sans-serif;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        input:focus, select:focus {
            border-color: #3498db;
        }
        .btn {
            font-family: 'Cairo', sans-serif;
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }
        .btn:hover { background-color: #27ae60; }
        
        /* التنبيهات */
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
            text-align: center;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* تنسيق الجدول */
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
        th, td { padding: 14px 18px; border-bottom: 1px solid #eee; text-align: right; }
        th { background-color: #34495e; color: white; font-weight: 600; }
        tr:last-child td { border-bottom: none; }
        tr:nth-child(even) { background-color: #fcfcfc; }
        tr:hover { background-color: #f8f9fa; }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-asol { background-color: #e3f2fd; color: #0d47a1; }
        .badge-masrof { background-color: #ffebee; color: #b71c1c; }
        .badge-khasm { background-color: #fff3e0; color: #e65100; }
        .badge-erad { background-color: #e8f5e9; color: #1b5e20; }
    </style>
</head>
<body>

    <div class="container">
        <h1>💼 لوحة الحسابات الذكية | Micro-ERP</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>➕ إضافة حساب جديد للدليل</h2>
            <form action="index.php" method="POST">
                <div class="form-group-container">
                    <div class="form-group">
                        <label for="account_code">كود الحساب (رقمي)</label>
                        <input type="text" id="account_code" name="account_code" placeholder="مثال: 1102" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="account_name">اسم الحساب</label>
                        <input type="text" id="account_name" name="account_name" placeholder="مثال: بنك مصر الجاري" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="account_type">نوع الحساب</label>
                        <select id="account_type" name="account_type" required>
                            <option value="">-- اختر النوع --</option>
                            <option value="أصول">أصول</option>
                            <option value="خصوم">خصوم</option>
                            <option value="إيرادات">إيرادات</option>
                            <option value="مصروفات">مصروفات</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_account" class="btn">حفظ الحساب في النظام 💾</button>
            </form>
        </div>

        <div class="card">
            <h2>📊 دليل الحسابات الحالي (Chart of Accounts)</h2>
            <table>
                <thead>
                    <tr>
                        <th>كود الحساب</th>
                        <th>اسم الحساب</th>
                        <th>نوع الحساب</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($accounts) > 0): ?>
                        <?php foreach ($accounts as $account): 
                            // لتلوين نوع الحساب بشكل جمالي حسب نوعه
                            $badgeClass = "badge-asol";
                            if($account['account_type'] == 'مصروفات') $badgeClass = "badge-masrof";
                            if($account['account_type'] == 'خصوم') $badgeClass = "badge-khasm";
                            if($account['account_type'] == 'إيرادات') $badgeClass = "badge-erad";
                        ?>
                            <tr>
                                <td style="font-weight: 600; color: #7f8c8d;"><?php echo htmlspecialchars($account['account_code']); ?></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($account['account_name']); ?></td>
                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($account['account_type']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #7f8c8d;">لا توجد حسابات مضافة حتى الآن.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>