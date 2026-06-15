<?php
require_once 'db.php';

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_account'])) {
    $account_code = trim($_POST['account_code']);
    $account_name = trim($_POST['account_name']);
    $account_type = trim($_POST['account_type']);

    if (!empty($account_code) && !empty($account_name) && !empty($account_type)) {
        try {
            $sql = "INSERT INTO accounts (account_code, account_name, account_type) VALUES (:code, :name, :type)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':code' => $account_code, ':name' => $account_name, ':type' => $account_type]);
            $message = "تم إضافة الحساب بنجاح! ✨";
            $messageType = "success";
        } catch(PDOException $e) {
            $message = "خطأ: كود الحساب مكرر أو مفقود.";
            $messageType = "danger";
        }
    }
}

// جلب البيانات والأعداد للإحصائيات
$accounts = $conn->query("SELECT * FROM accounts ORDER BY account_code ASC")->fetchAll(PDO::FETCH_ASSOC);
$total_accounts = count($accounts);

// حساب إحصائيات سريعة تقريبية للـ Cards
$asol_count = 0; $masrof_count = 0;
foreach($accounts as $a) {
    if($a['account_type'] == 'أصول') $asol_count++;
    if($a['account_type'] == 'مصروفات') $masrof_count++;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم المالية | ERP System</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #212529; min-height: 100vh; color: white; }
        .sidebar .nav-link { color: #rgba(255,255,255,.75); font-weight: 500; padding: 12px 20px; border-radius: 8px; margin: 5px 10px; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: #343a40; color: #fff; }
        .main-content { padding: 30px; }
        .card { border: none; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .table-responsive { background: white; border-radius: 12px; padding: 15px; }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
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
                <li class="nav-item"><a class="nav-link active" href="#"><i class="fa-solid fa-chart-pie m-2"></i>الرئيسية</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fa-solid fa-list-check m-2"></i>دليل الحسابات</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fa-solid fa-file-invoice-dollar m-2"></i>القيود اليومية</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="fa-solid fa-receipt m-2"></i>تقارير الأستاذ العام</a></li>
            </ul>
        </div>

        <div class="col-md-9 col-lg-10 main-content">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">لوحة إدارة الحسابات المالية</h2>
                    <p class="text-muted">مرحباً بك مجدداً، نظرة عامة على النظام اليوم.</p>
                </div>
                <div class="text-muted fw-bold bg-white p-2 border rounded shadow-sm">
                    <i class="fa-regular fa-calendar-days text-primary m-1"></i> <?php echo date('Y-m-d'); ?>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <i class="fa-solid fa-circle-check me-2"></i> <?php echo $message; ?>
                    <button type="submit" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card bg-white p-3 border-start border-primary border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">إجمالي الحسابات بالدليل</h6>
                                <h3 class="fw-bold mb-0"><?php echo $total_accounts; ?></h3>
                            </div>
                            <div class="bg-light-primary text-primary p-3 rounded-circle"><i class="fa-solid fa-folder-tree fa-2x"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-white p-3 border-start border-success border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">حسابات الأصول</h6>
                                <h3 class="fw-bold mb-0 text-success"><?php echo $asol_count; ?></h3>
                            </div>
                            <div class="text-success p-3 rounded-circle"><i class="fa-solid fa-vault fa-2x"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-white p-3 border-start border-danger border-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">حسابات المصروفات</h6>
                                <h3 class="fw-bold mb-0 text-danger"><?php echo $masrof_count; ?></h3>
                            </div>
                            <div class="text-danger p-3 rounded-circle"><i class="fa-solid fa-money-bill-transfer fa-2x"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card p-4 shadow-sm h-100">
                        <h5 class="fw-bold mb-3 text-secondary"><i class="fa-solid fa-plus-circle me-1"></i> فتح حساب جديد</h5>
                        <form action="index.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">كود الحساب</label>
                                <input type="text" name="account_code" class="form-control" placeholder="مثال: 1103" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">اسم الحساب</label>
                                <input type="text" name="account_name" class="form-control" placeholder="مثال: المبيعات" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">نوع الحساب</label>
                                <select name="account_type" class="form-select" required>
                                    <option value="">اختر النوع...</option>
                                    <option value="أصول">أصول</option>
                                    <option value="خصوم">خصوم</option>
                                    <option value="إيرادات">إيرادات</option>
                                    <option value="مصروفات">مصروفات</option>
                                </select>
                            </div>
                            <button type="submit" name="add_account" class="btn btn-dark w-100 fw-bold mt-2"><i class="fa-solid fa-floppy-disk me-1"></i> حفظ في النظام</button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card p-4 shadow-sm h-100">
                        <h5 class="fw-bold mb-3 text-secondary"><i class="fa-solid fa-table-list me-1"></i> شجرة الحسابات النشطة</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>الكود</th>
                                        <th>اسم الحساب المحاسبي</th>
                                        <th>نوع الحساب</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($accounts as $account): 
                                        $badgeColor = "bg-primary";
                                        if($account['account_type'] == 'مصروفات') $badgeColor = "bg-danger";
                                        if($account['account_type'] == 'خصوم') $badgeColor = "bg-warning text-dark";
                                        if($account['account_type'] == 'إيرادات') $badgeColor = "bg-success";
                                    ?>
                                        <tr>
                                            <td class="fw-bold text-secondary"><?php echo htmlspecialchars($account['account_code']); ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($account['account_name']); ?></td>
                                            <td><span class="badge <?php echo $badgeColor; ?> px-3 py-2 fs-7"><?php echo htmlspecialchars($account['account_type']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>