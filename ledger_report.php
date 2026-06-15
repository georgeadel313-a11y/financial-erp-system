<?php
require_once 'db.php';

// 1. جلب الحسابات عشان نضعها في قائمة الاختيار
$accounts = $conn->query("SELECT * FROM accounts ORDER BY account_code ASC")->fetchAll(PDO::FETCH_ASSOC);

$selected_account = isset($_GET['account_id']) ? intval($_GET['account_id']) : 0;
$report_data = [];
$account_info = null;
$total_debit = 0;
$total_credit = 0;

// 2. إذا اختار المحاسب حساب معين، نذهب لجلب حركاته المالية فوراً
if ($selected_account > 0) {
    // جلب معلومات الحساب المختار
    $stmt = $conn->prepare("SELECT * FROM accounts WHERE id = :id");
    $stmt->execute([':id' => $selected_account]);
    $account_info = $stmt->fetch(PDO::FETCH_ASSOC);

    // كود الـ SQL الساحر لدمج الجداول الثلاثة وجلب كشف الحساب
    $sql = "SELECT 
                v.kv_date, 
                v.description, 
                d.debit, 
                d.credit
            FROM journal_details d
            JOIN journal_vouchers v ON d.voucher_id = v.id
            WHERE d.account_id = :acc_id
            ORDER BY v.kv_date ASC, v.id ASC";
            
    $stmt_report = $conn->prepare($sql);
    $stmt_report->execute([':acc_id' => $selected_account]);
    $report_data = $stmt_report->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دفتر الأستاذ العام | ERP System</title>
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
        .table-responsive { background: white; border-radius: 12px; padding: 15px; }
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
                <li class="nav-item"><a class="nav-link" href="add_voucher.php"><i class="fa-solid fa-file-invoice-dollar m-2"></i>القيود اليومية</a></li>
                <li class="nav-item"><a class="nav-link active" href="ledger_report.php"><i class="fa-solid fa-receipt m-2"></i>تقارير الأستاذ العام</a></li>
            </ul>
        </div>

        <div class="col-md-9 col-lg-10 main-content">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">كشف حساب الأستاذ العام (General Ledger)</h2>
                <p class="text-muted">اختر أي حساب لمحاسبة تتبع أرصدته والعمليات المادية التي تمت عليه بدقة.</p>
            </div>

            <div class="card p-4 shadow-sm mb-4">
                <form action="ledger_report.php" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">اختر الحساب المحاسبي المراد مراجعته:</label>
                        <select name="account_id" class="form-select" required>
                            <option value="">-- اختر الحساب من الشجرة --</option>
                            <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo $acc['id']; ?>" <?php echo ($selected_account == $acc['id']) ? 'selected' : ''; ?>>
                                    <?php echo $acc['account_code'] . " - " . $acc['account_name'] . " (" . $acc['account_type'] . ")"; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-info w-100 fw-bold text-white"><i class="fa-solid fa-magnifying-glass me-1"></i> عرض كشف الحساب</button>
                    </div>
                </form>
            </div>

            <?php if ($selected_account > 0 && $account_info): ?>
                <div class="card p-4 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                        <h4 class="fw-bold text-primary mb-0">
                            <i class="fa-solid fa-file-lines me-1"></i> حركة حساب: <?php echo htmlspecialchars($account_info['account_name']); ?>
                        </h4>
                        <span class="badge bg-secondary px-3 py-2 fs-6">كود المحاسبة: <?php echo $account_info['account_code']; ?></span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>البيان / شرح الحركة المالية</th>
                                    <th class="text-danger">مدين (Debit) (+)</th>
                                    <th class="text-success">دائن (Credit) (-)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($report_data) == 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">لا توجد أي حركات مالية مسجلة على هذا الحساب حتى الآن.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($report_data as $row): 
                                        $total_debit += $row['debit'];
                                        $total_credit += $row['credit'];
                                    ?>
                                        <tr>
                                            <td><?php echo $row['kv_date']; ?></td>
                                            <td class="fw-semibold"><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td class="text-danger fw-bold"><?php echo number_format($row['debit'], 2); ?></td>
                                            <td class="text-success fw-bold"><?php echo number_format($row['credit'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    
                                    <tr class="table-light fw-bold border-top border-dark">
                                        <td colspan="2" class="text-end text-dark fs-5">إجمالي الحركة والرصيد المتبقي:</td>
                                        <td class="text-danger fs-5"><?php echo number_format($total_debit, 2); ?></td>
                                        <td class="text-success fs-5"><?php echo number_format($total_credit, 2); ?></td>
                                    </tr>
                                    <tr class="table-info fw-bold">
                                        <td colspan="2" class="text-end fs-4 text-dark">صافي الرصيد الحالي بالخزنة/الحساب:</td>
                                        <td colspan="2" class="text-center text-dark fs-3">
                                            <?php 
                                            // حساب الصافي (مدين - دائن)
                                            $final_balance = $total_debit - $total_credit;
                                            echo number_format(abs($final_balance), 2);
                                            echo ($final_balance >= 0) ? " (رصيد مدين)" : " (رصيد دائن)";
                                            ?>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>