<?php
// 1. استدعاء ملف الاتصال بقاعدة البيانات
require_once 'db.php';

$message = "";
$messageType = "";

// 2. جلب قائمة الحسابات عشان نفتحها للمحاسب في القائمة المنسدلة (Select Box)
try {
    $stmt = $conn->prepare("SELECT * FROM accounts ORDER BY account_code ASC");
    $stmt->execute();
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطأ في جلب الحسابات: " . $e->getMessage());
}

// 3. معالجة حفظ القيد المحاسبي عند الضغط على زر الحفظ
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_voucher'])) {
    $kv_date = $_POST['kv_date'];
    $description = trim($_POST['description']);
    
    $debit_account = $_POST['debit_account'];
    $debit_amount = floatval($_POST['debit_amount']);
    
    $credit_account = $_POST['credit_account'];
    $credit_amount = floatval($_POST['credit_amount']);

    // التحقق الذكي: منع حفظ القيد إذا لم يتساو الطرف المدين مع الطرف الدائن
    if ($debit_amount <= 0 || $credit_amount <= 0) {
        $message = "خطأ: يجب أن تكون المبالغ أكبر من الصفر! ❌";
        $messageType = "danger";
    } elseif ($debit_amount !== $credit_amount) {
        $message = "خطأ محاسبي: القيد غير متوازن! يجب أن يتساوى حقل المدين ($debit_amount) مع الدائن ($credit_amount). ❌";
        $messageType = "danger";
    } elseif ($debit_account === $credit_account) {
        $message = "خطأ: لا يمكن اختيار نفس الحساب في الطرفين المدين والدائن! ❌";
        $messageType = "danger";
    } else {
        // إذا كان كل شيء سليم محاسبياً، نبدأ عملية الحفظ في الجدولين معاً
        try {
            // تفعيل خاصية الـ Transaction للأمان المالي
            $conn->beginTransaction();

            // أ) إدخال البيانات في الجدول الأول: رأس القيد
            $sql_v = "INSERT INTO journal_vouchers (kv_date, description) VALUES (:kv_date, :descr)";
            $stmt_v = $conn->prepare($sql_v);
            $stmt_v->execute([
                ':kv_date' => $kv_date,
                ':descr' => $description
            ]);
            
            // جلب رقم الـ ID تلقائياً الذي تم إنشاؤه حالا للقيد
            $voucher_id = $conn->lastInsertId();

            // ب) إدخال الطرف الأول (المدين Debit) في جدول التفاصيل
            $sql_d1 = "INSERT INTO journal_details (voucher_id, account_id, debit, credit) VALUES (:v_id, :acc_id, :debit, 0)";
            $stmt_d1 = $conn->prepare($sql_d1);
            $stmt_d1->execute([
                ':v_id' => $voucher_id,
                ':acc_id' => $debit_account,
                ':debit' => $debit_amount
            ]);

            // ج) إدخال الطرف الثاني (الدائن Credit) في جدول التفاصيل
            $sql_d2 = "INSERT INTO journal_details (voucher_id, account_id, debit, credit) VALUES (:v_id, :acc_id, 0, :credit)";
            $stmt_d2 = $conn->prepare($sql_d2);
            $stmt_d2->execute([
                ':v_id' => $voucher_id,
                ':acc_id' => $credit_account,
                ':credit' => $credit_amount
            ]);

            // إنهاء وحفظ العملية بنجاح في قاعدة البيانات
            $conn->commit();

            $message = "تم تسجيل القيد المحاسبي المالي بنجاح وتحديث الحسابات! ✨ توازن تام بقيمة: " . $debit_amount;
            $messageType = "success";
        } catch(PDOException $e) {
            // في حال حدوث أي خطأ، يتم التراجع عن كل شيء كأن شيئاً لم يكن لحماية أموال الشركة
            $conn->rollBack();
            $message = "فشل حفظ القيد في النظام: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدخال قيد محاسبي | ERP System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #212529; min-height: 100vh; color: white; }
        .sidebar .nav-link { color: rgba(255,255,255,.75); font-weight: 500; padding: 12px 20px; border-radius: 8px; margin: 5px 10px; text-decoration: none; display: block; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #343a40; color: #fff; }
        .main-content { padding: 30px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .voucher-header { background-color: #edf2f7; border-radius: 8px; padding: 20px; margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 col-lg-2 sidebar d-none d-md-block p-0 shadow">
            <div class="p-4 text-center border-bottom border-secondary">
                <h5 class="fw-bold mb-0 text-info"><i class="fa-solid fa-wallet me-2"></i>إتقان المالي</h5>
                <small class="text-muted">نظام Micro-ERP ذكي</small>
            </div>
            <ul class="nav flex-column mt-4">
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-chart-pie m-2"></i>الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php"><i class="fa-solid fa-list-check m-2"></i>دليل الحسابات</a></li>
                <li class="nav-item"><a class="nav-link active" href="add_voucher.php"><i class="fa-solid fa-file-invoice-dollar m-2"></i>القيود اليومية</a></li>
                <li class="nav-item"><a class="nav-link" href="ledger_report.php"><i class="fa-solid fa-receipt m-2"></i>تقارير الأستاذ العام</a></li>
            </ul>
        </div>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">دفتر القيود اليومية (Journal Entries)</h2>
                    <p class="text-muted">تسجيل حركات الفلوس اليومية والتحقق من التوازن المالي للشركة تلقائياً.</p>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-info me-2"></i> <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card p-4 shadow-sm">
                <h5 class="fw-bold mb-4 text-primary"><i class="fa-solid fa-pen-to-square me-1"></i> إنشاء قيد مالي جديد لـ متوازن</h5>
                
                <form action="add_voucher.php" method="POST">
                    
                    <div class="voucher-header">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">تاريخ الحركة المالية</label>
                                <input type="date" name="kv_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">البيان / شرح القيد</label>
                                <input type="text" name="description" class="form-control" placeholder="مثال: سداد قيمة الإيجار الشهري نقداً للفرع الرئيسي" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        
                        <div class="col-md-6">
                            <div class="p-3 border border-danger rounded bg-light-danger" style="background-color: #fff5f5;">
                                <h6 class="fw-bold text-danger mb-3"><i class="fa-solid fa-arrow-down-long me-1"></i> الطرف المدين (الحساب الآخذ / المستلم)</h6>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">اختر الحساب المدين</label>
                                    <select name="debit_account" class="form-select" required>
                                        <option value="">-- اختر الحساب --</option>
                                        <?php foreach ($accounts as $acc): ?>
                                            <option value="<?php echo $acc['id']; ?>">
                                                <?php echo $acc['account_code'] . " - " . $acc['account_name'] . " (" . $acc['account_type'] . ")"; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold">المبلغ (مدين)</label>
                                    <input type="number" step="0.01" name="debit_amount" class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 border border-success rounded bg-light-success" style="background-color: #f6fff6;">
                                <h6 class="fw-bold text-success mb-3"><i class="fa-solid fa-arrow-up-long me-1"></i> الطرف الدائن (الحساب العاطي / المموّل)</h6>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">اختر الحساب الدائن</label>
                                    <select name="credit_account" class="form-select" required>
                                        <option value="">-- اختر الحساب --</option>
                                        <?php foreach ($accounts as $acc): ?>
                                            <option value="<?php echo $acc['id']; ?>">
                                                <?php echo $acc['account_code'] . " - " . $acc['account_name'] . " (" . $acc['account_type'] . ")"; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label fw-semibold">المبلغ (دائن)</label>
                                    <input type="number" step="0.01" name="credit_amount" class="form-control" placeholder="0.00" required>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="text-start mt-4">
                        <button type="submit" name="save_voucher" class="btn btn-primary px-5 fw-bold btn-lg shadow-sm"><i class="fa-solid fa-receipt me-1"></i> ترحيل وحفظ القيد في الدفاتر 💾</button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>