<?php
require_once "config.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "manager") {
    die("Từ chối truy cập");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"])) {
    verify_csrf();
    
    // Package CRUD
    if ($_POST["action"] === "add_package") {
        $stmt = $pdo->prepare("INSERT INTO packages (name, duration_days, price) VALUES (?, ?, ?)");
        $stmt->execute([$_POST["name"], $_POST["duration_days"], $_POST["price"]]);
    } elseif ($_POST["action"] === "edit_package") {
        $stmt = $pdo->prepare("UPDATE packages SET name = ?, duration_days = ?, price = ? WHERE id = ?");
        $stmt->execute([$_POST["name"], $_POST["duration_days"], $_POST["price"], $_POST["id"]]);
    } elseif ($_POST["action"] === "delete_package") {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
        $stmt->execute([$_POST["id"]]);
    }
    
    // Member CRUD (and user)
    elseif ($_POST["action"] === "add_member") {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'member')");
            $hash = password_hash($_POST["password"], PASSWORD_BCRYPT);
            $stmt->execute([$_POST["username"], $hash]);
            $user_id = $pdo->lastInsertId();
            
            $stmt = $pdo->prepare("INSERT INTO members (user_id, name, phone) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $_POST["name"], $_POST["phone"]]);
            $pdo->commit();
        } catch (Exception $e) { $pdo->rollBack(); }
    } elseif ($_POST["action"] === "edit_member") {
        $stmt = $pdo->prepare("UPDATE members SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$_POST["name"], $_POST["phone"], $_POST["id"]]);
    } elseif ($_POST["action"] === "delete_member") {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = (SELECT user_id FROM members WHERE id = ?)");
        $stmt->execute([$_POST["id"]]);
    }
    
    // Trainer CRUD
    elseif ($_POST["action"] === "add_trainer") {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'trainer')");
            $hash = password_hash($_POST["password"], PASSWORD_BCRYPT);
            $stmt->execute([$_POST["username"], $hash]);
            $user_id = $pdo->lastInsertId();
            
            $trainer_name = $_POST["name"] . " (" . ($_POST["gender"] ?? "Nam") . ")";
            $stmt = $pdo->prepare("INSERT INTO trainers (user_id, name, specialty) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $trainer_name, $_POST["specialty"]]);
            $pdo->commit();
        } catch (Exception $e) { $pdo->rollBack(); }
    } elseif ($_POST["action"] === "edit_trainer") {
        $stmt = $pdo->prepare("UPDATE trainers SET name = ?, specialty = ? WHERE id = ?");
        $stmt->execute([$_POST["name"], $_POST["specialty"], $_POST["id"]]);
    } elseif ($_POST["action"] === "delete_trainer") {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = (SELECT user_id FROM trainers WHERE id = ?)");
        $stmt->execute([$_POST["id"]]);
    }
    
    // Schedule CRUD
    elseif ($_POST["action"] === "add_schedule") {
        $stmt = $pdo->prepare("INSERT INTO schedules (trainer_id, class_name, start_time, end_time, capacity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST["trainer_id"], $_POST["class_name"], $_POST["start_time"], $_POST["end_time"], $_POST["capacity"]]);
    } elseif ($_POST["action"] === "delete_schedule") {
        $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ?");
        $stmt->execute([$_POST["id"]]);
    }
    
    // Renew Package & Payment
    elseif ($_POST["action"] === "renew_package") {
        $member_id = $_POST["member_id"];
        $package_id = $_POST["package_id"];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("SELECT duration_days FROM packages WHERE id = ?");
            $stmt->execute([$package_id]);
            $package = $stmt->fetch();
            $stmt = $pdo->prepare("SELECT end_date FROM memberships WHERE member_id = ? ORDER BY end_date DESC LIMIT 1");
            $stmt->execute([$member_id]);
            $last_membership = $stmt->fetch();
            
            $start_date = ($last_membership && $last_membership["end_date"] > date("Y-m-d")) ? $last_membership["end_date"] : date("Y-m-d");
            $end_date = date("Y-m-d", strtotime($start_date . " + " . $package["duration_days"] . " days"));
            $stmt = $pdo->prepare("INSERT INTO memberships (member_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$member_id, $package_id, $start_date, $end_date]);
            
            $m_stmt = $pdo->prepare("SELECT name FROM members WHERE id = ?");
            $m_stmt->execute([$member_id]);
            $m_info = $m_stmt->fetch();
            
            $p_stmt = $pdo->prepare("SELECT name, price FROM packages WHERE id = ?");
            $p_stmt->execute([$package_id]);
            $p_info = $p_stmt->fetch();
            
            $_SESSION['receipt'] = [
                'receipt_no' => 'HD' . date('YmdHis'),
                'member_name' => $m_info['name'],
                'package_name' => $p_info['name'],
                'price' => $p_info['price'] * 25000,
                'payment_method' => $_POST['payment_method'] ?? 'Tiền mặt tại quầy',
                'start_date' => $start_date,
                'end_date' => $end_date,
                'cashier' => $_SESSION['username'] ?? 'Admin',
                'created_at' => date('d/m/Y H:i:s')
            ];
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    header("Location: manager.php");
    exit;
}

$members = $pdo->query("SELECT m.*, (SELECT end_date FROM memberships WHERE member_id = m.id ORDER BY end_date DESC LIMIT 1) as end_date FROM members m")->fetchAll();
$packages = $pdo->query("SELECT * FROM packages")->fetchAll();
$trainers = $pdo->query("SELECT * FROM trainers")->fetchAll();
$schedules_list = $pdo->query("SELECT s.*, t.name as trainer_name FROM schedules s JOIN trainers t ON s.trainer_id = t.id ORDER BY s.start_time DESC")->fetchAll();

// Reports
$revenue = $pdo->query("SELECT SUM(p.price) as rev FROM memberships m JOIN packages p ON m.package_id = p.id")->fetch()["rev"];
$new_members = $pdo->query("SELECT COUNT(*) as c FROM users WHERE role='member' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch()["c"];
$fill_rate = $pdo->query("SELECT (SELECT COUNT(*) FROM bookings) / NULLIF(SUM(capacity), 0) as rate FROM schedules")->fetch()["rate"];
?>
<?php require_once "header.php"; ?>

<?php if (isset($_SESSION['receipt'])): 
    $rc = $_SESSION['receipt'];
    unset($_SESSION['receipt']);
?>
<div class="alert alert-success alert-dismissible fade show shadow-sm mb-4 border-0" role="alert" style="background-color: #ECFDF5; border-left: 5px solid #10B981 !important;">
    <div class="d-flex align-items-center mb-3">
        <i class="fas fa-check-circle text-success fs-3 me-2"></i>
        <div>
            <h5 class="alert-heading mb-0 fw-bold text-success">Thanh toán & Xuất hóa đơn thành công!</h5>
            <small class="text-muted">Hệ thống đã tự động gia hạn quyền lợi gói tập cho hội viên.</small>
        </div>
    </div>
    
    <!-- Receipt Card -->
    <div class="card mx-auto shadow-sm" style="max-width: 440px; border: 2px dashed #CBD5E1 !important;">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <h4 class="fw-bold text-primary mb-1"><i class="fas fa-dumbbell me-1"></i>GYM FITNESS</h4>
                <p class="text-muted small mb-0 fw-semibold">PHIẾU THU TIỀN DỊCH VỤ</p>
                <small class="text-muted">Mã HĐ: <strong><?php echo $rc['receipt_no']; ?></strong> | Ngày: <?php echo $rc['created_at']; ?></small>
            </div>
            <hr class="my-2" style="border-top: 1px dashed #CBD5E1;">
            <div class="mb-2 small d-flex justify-content-between">
                <span class="text-muted">Hội viên thanh toán:</span>
                <strong class="text-dark"><?php echo htmlspecialchars($rc['member_name']); ?></strong>
            </div>
            <div class="mb-2 small d-flex justify-content-between">
                <span class="text-muted">Gói dịch vụ đăng ký:</span>
                <strong><?php echo htmlspecialchars($rc['package_name']); ?></strong>
            </div>
            <div class="mb-2 small d-flex justify-content-between">
                <span class="text-muted">Thời hạn áp dụng:</span>
                <span><?php echo $rc['start_date']; ?> đến <?php echo $rc['end_date']; ?></span>
            </div>
            <div class="mb-2 small d-flex justify-content-between">
                <span class="text-muted">Hình thức thanh toán:</span>
                <span class="badge bg-light text-dark border"><?php echo $rc['payment_method']; ?></span>
            </div>
            <div class="mb-3 small d-flex justify-content-between">
                <span class="text-muted">Thu ngân phụ trách:</span>
                <span><?php echo htmlspecialchars($rc['cashier']); ?></span>
            </div>
            <hr class="my-2" style="border-top: 2px solid #0F172A;">
            <div class="d-flex justify-content-between align-items-center py-2">
                <span class="fw-bold fs-6">TỔNG TIỀN:</span>
                <span class="fw-bolder fs-4 text-primary"><?php echo number_format($rc['price'], 0, ',', '.'); ?> VNĐ</span>
            </div>
            <div class="text-center mt-3 no-print">
                <button onclick="window.print()" class="btn btn-sm btn-primary me-2"><i class="fas fa-print me-1"></i>In Hóa Đơn</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="alert">Đóng</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1"><i class="fas fa-tasks text-primary me-2"></i>Bảng Quản Trị Phòng Tập</h2>
        <span class="text-muted">Trung tâm điều hành và quản lý hoạt động kinh doanh phòng Gym</span>
    </div>
</div>

<!-- Nav tabs -->
<ul class="nav nav-tabs mb-4" id="managerTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab"><i class="fas fa-chart-line me-1"></i>Báo Cáo Hoạt Động</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="members-tab" data-bs-toggle="tab" data-bs-target="#members" type="button" role="tab"><i class="fas fa-users me-1"></i>Hội Viên & Gia Hạn</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="packages-tab" data-bs-toggle="tab" data-bs-target="#packages" type="button" role="tab"><i class="fas fa-box-open me-1"></i>Gói Tập Luyện</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="trainers-tab" data-bs-toggle="tab" data-bs-target="#trainers" type="button" role="tab"><i class="fas fa-user-tie me-1"></i>Đội Ngũ Huấn Luyện Viên</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="schedules-tab" data-bs-toggle="tab" data-bs-target="#schedules" type="button" role="tab"><i class="fas fa-calendar-alt me-1"></i>Lịch Trình Lớp Học</button>
  </li>
</ul>

<!-- Tab panes -->
<div class="tab-content" id="managerTabsContent">
  <!-- Tab 1: Báo Cáo Hoạt Động -->
  <div class="tab-pane fade show active" id="reports" role="tabpanel">
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card h-100 p-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Tổng Doanh Thu</span>
                        <div class="bg-primary-subtle p-2 rounded text-primary"><i class="fas fa-wallet fs-5"></i></div>
                    </div>
                    <h3 class="fw-bold mb-1 text-primary"><?php echo number_format(($revenue ?? 0) * 25000, 0, ",", "."); ?> VNĐ</h3>
                    <small class="text-success"><i class="fas fa-arrow-up me-1"></i>Từ các gói hội viên đã kích hoạt</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Hội Viên Mới</span>
                        <div class="bg-success-subtle p-2 rounded text-success"><i class="fas fa-user-plus fs-5"></i></div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark"><?php echo $new_members; ?> hội viên</h3>
                    <small class="text-muted"><i class="far fa-calendar-check me-1"></i>Gia nhập trong 30 ngày qua</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 p-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small fw-semibold text-uppercase">Tỷ Lệ Lấp Đầy Lớp</span>
                        <div class="bg-info-subtle p-2 rounded text-info"><i class="fas fa-chart-pie fs-5"></i></div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark"><?php echo number_format(($fill_rate ?? 0) * 100, 1); ?>%</h3>
                    <small class="text-muted"><i class="fas fa-dumbbell me-1"></i>Hiệu suất đăng ký lịch tập</small>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Tab 2: Hội Viên & Gia Hạn -->
  <div class="tab-pane fade" id="members" role="tabpanel">
    <div class="row g-4">
        <!-- Danh Sách Hội Viên -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-users me-2 text-warning"></i>Danh Sách Hội Viên</h5>
                    <span class="badge bg-secondary"><?php echo count($members); ?> người</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-3">Họ và tên</th>
                                    <th>Số điện thoại</th>
                                    <th>Hạn thẻ</th>
                                    <th class="text-end pe-3">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $m): ?>
                                <tr>
                                    <td class="ps-3 fw-bold"><?php echo htmlspecialchars($m["name"]); ?></td>
                                    <td><span class="text-muted small"><?php echo htmlspecialchars($m["phone"] ?: 'Chưa có'); ?></span></td>
                                    <td>
                                        <?php if ($m["end_date"]): ?>
                                            <?php if ($m["end_date"] >= date('Y-m-d')): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle"><?php echo date('d/m/Y', strtotime($m["end_date"])); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Hết hạn: <?php echo date('d/m/Y', strtotime($m["end_date"])); ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Chưa có gói</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn-action-edit me-1" data-bs-toggle="modal" data-bs-target="#editMemberModal<?php echo $m['id']; ?>" title="Chỉnh sửa">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                            <input type="hidden" name="action" value="delete_member">
                                            <input type="hidden" name="id" value="<?php echo $m["id"]; ?>">
                                            <button type="submit" class="btn-action-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa hội viên <?php echo htmlspecialchars($m['name']); ?>?');" title="Xóa hội viên">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>

                                        <!-- Modal Sửa Hội Viên -->
                                        <div class="modal fade" id="editMemberModal<?php echo $m['id']; ?>" tabindex="-1" aria-hidden="true">
                                          <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                              <div class="modal-header bg-dark text-white">
                                                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit text-warning me-2"></i>Sửa Thông Tin Hội Viên</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                              </div>
                                              <form method="post">
                                                  <div class="modal-body p-4">
                                                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                                      <input type="hidden" name="action" value="edit_member">
                                                      <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                                      
                                                      <div class="mb-3">
                                                          <label class="form-label small fw-bold">Họ và tên:</label>
                                                          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($m['name']); ?>" required>
                                                      </div>
                                                      <div class="mb-3">
                                                          <label class="form-label small fw-bold">Số điện thoại liên hệ:</label>
                                                          <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($m['phone'] ?? ''); ?>">
                                                      </div>
                                                  </div>
                                                  <div class="modal-footer bg-light">
                                                      <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                      <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="fas fa-save me-1"></i>Lưu Thay Đổi</button>
                                                  </div>
                                              </form>
                                            </div>
                                          </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thêm Hội Viên & Gia Hạn Gói -->
        <div class="col-lg-5">
            <!-- Form Thêm Hội Viên -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user-plus text-success me-2"></i>Thêm Hội Viên Mới</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action" value="add_member">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control form-control-sm" placeholder="VD: hoivien16" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Mật khẩu</label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="Mật khẩu" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Họ và tên</label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="VD: Hoàng Văn A" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control form-control-sm" placeholder="VD: 0987654321">
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold py-2"><i class="fas fa-plus me-1"></i>Tạo Tài Khoản Hội Viên</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Form Gia Hạn Gói & Thanh Toán -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-credit-card text-info me-2"></i>Gia Hạn Gói & Thanh Toán POS</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action" value="renew_package">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Chọn hội viên cần gia hạn</label>
                            <select name="member_id" class="form-select form-select-sm" required>
                                <option value="">-- Chọn Hội Viên --</option>
                                <?php foreach ($members as $m): ?>
                                <option value="<?php echo htmlspecialchars($m["id"]); ?>"><?php echo htmlspecialchars($m["name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Gói dịch vụ đăng ký</label>
                            <select name="package_id" class="form-select form-select-sm" required>
                                <option value="">-- Chọn Gói Tập --</option>
                                <?php foreach ($packages as $p): ?>
                                <option value="<?php echo htmlspecialchars($p["id"]); ?>"><?php echo htmlspecialchars($p["name"]); ?> (<?php echo number_format($p["price"] * 25000, 0, ',', '.'); ?> VNĐ)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted">Hình thức thanh toán</label>
                            <select name="payment_method" class="form-select form-select-sm">
                                <option value="Tiền mặt tại quầy">💵 Tiền mặt tại quầy</option>
                                <option value="Chuyển khoản QR ngân hàng">📲 Chuyển khoản QR ngân hàng</option>
                                <option value="Quẹt thẻ POS / Thẻ tín dụng">💳 Quẹt thẻ POS / Thẻ tín dụng</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success w-100 fw-bold py-2 mt-2"><i class="fas fa-receipt me-1"></i>Xác Nhận & Xuất Hóa Đơn</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Tab 3: Gói Tập Luyện -->
  <div class="tab-pane fade" id="packages" role="tabpanel">
    <div class="row g-4">
        <!-- Danh Sách Gói Tập -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-box-open text-warning me-2"></i>Bảng Giá Gói Tập Luyện</h5>
                    <span class="badge bg-secondary"><?php echo count($packages); ?> gói</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-3">Tên gói tập</th>
                                    <th>Thời hạn</th>
                                    <th>Giá gói niêm yết</th>
                                    <th class="text-end pe-3">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($packages as $p): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary"><?php echo htmlspecialchars($p["name"]); ?></td>
                                    <td><span class="badge bg-light text-dark border"><i class="far fa-calendar me-1"></i><?php echo $p["duration_days"]; ?> ngày</span></td>
                                    <td class="fw-semibold text-success"><?php echo number_format($p["price"] * 25000, 0, ",", "."); ?> VNĐ</td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn-action-edit me-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $p['id']; ?>" title="Chỉnh sửa gói tập">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                            <input type="hidden" name="action" value="delete_package">
                                            <input type="hidden" name="id" value="<?php echo $p["id"]; ?>">
                                            <button type="submit" class="btn-action-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa gói tập này?');" title="Xóa gói tập">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>

                                        <!-- Modal Sửa Gói Tập -->
                                        <div class="modal fade" id="editModal<?php echo $p['id']; ?>" tabindex="-1" aria-hidden="true">
                                          <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                              <div class="modal-header bg-dark text-white">
                                                <h5 class="modal-title fw-bold"><i class="fas fa-pen-to-square me-2 text-warning"></i>Cập Nhật Gói Tập</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                              </div>
                                              <form method="post">
                                                  <div class="modal-body p-4">
                                                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                                      <input type="hidden" name="action" value="edit_package">
                                                      <input type="hidden" name="id" value="<?php echo $p['id']; ?>">
                                                      
                                                      <div class="mb-3">
                                                          <label class="form-label small fw-bold">Tên gói tập:</label>
                                                          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($p['name']); ?>" required>
                                                      </div>
                                                      <div class="mb-3">
                                                          <label class="form-label small fw-bold">Thời hạn (số ngày):</label>
                                                          <input type="number" name="duration_days" class="form-control" value="<?php echo $p['duration_days']; ?>" required>
                                                      </div>
                                                      <div class="mb-3">
                                                          <label class="form-label small fw-bold">Đơn giá quy đổi ($ hệ số x25.000 VNĐ):</label>
                                                          <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $p['price']; ?>" required>
                                                          <small class="text-muted">Tương đương: <?php echo number_format($p['price'] * 25000, 0, ',', '.'); ?> VNĐ</small>
                                                      </div>
                                                  </div>
                                                  <div class="modal-footer bg-light">
                                                      <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                      <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="fas fa-save me-1"></i>Lưu Thay Đổi</button>
                                                  </div>
                                              </form>
                                            </div>
                                          </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thêm Gói Tập Mới -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle text-success me-2"></i>Thêm Gói Tập Mới</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action" value="add_package">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Tên gói tập</label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="VD: Gói Tập 1 Tháng, Gói VIP..." required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Thời hạn (số ngày)</label>
                            <input type="number" name="duration_days" class="form-control form-control-sm" placeholder="VD: 30" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Giá tiền quy đổi ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control form-control-sm" placeholder="VD: 50" required>
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold py-2"><i class="fas fa-plus me-1"></i>Tạo Gói Tập Mới</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Tab 4: Đội Ngũ Huấn Luyện Viên -->
  <div class="tab-pane fade" id="trainers" role="tabpanel">
    <div class="row g-4">
        <!-- Danh Sách Huấn Luyện Viên -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user-tie text-warning me-2"></i>Danh Sách Huấn Luyện Viên (PT)</h5>
                    <span class="badge bg-secondary"><?php echo count($trainers); ?> HLV</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-3">Họ và tên HLV</th>
                                    <th>Chuyên môn thể hình</th>
                                    <th class="text-end pe-3">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainers as $t): ?>
                                <tr>
                                    <td class="ps-3 fw-bold"><?php echo htmlspecialchars($t["name"]); ?></td>
                                    <td><span class="badge bg-info-subtle text-info border border-info-subtle"><?php echo htmlspecialchars($t["specialty"]); ?></span></td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn-action-edit me-1" data-bs-toggle="modal" data-bs-target="#editTrainerModal<?php echo $t['id']; ?>" title="Chỉnh sửa HLV">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                            <input type="hidden" name="action" value="delete_trainer">
                                            <input type="hidden" name="id" value="<?php echo $t["id"]; ?>">
                                            <button type="submit" class="btn-action-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa HLV <?php echo htmlspecialchars($t['name']); ?>?');" title="Xóa HLV">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>

                                        <!-- Modal Sửa HLV -->
                                        <div class="modal fade" id="editTrainerModal<?php echo $t['id']; ?>" tabindex="-1" aria-hidden="true">
                                          <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                              <div class="modal-header bg-dark text-white">
                                                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit text-warning me-2"></i>Sửa Thông Tin Huấn Luyện Viên</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                              </div>
                                              <form method="post">
                                                  <div class="modal-body p-4">
                                                      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                                      <input type="hidden" name="action" value="edit_trainer">
                                                      <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                                      
                                                      <div class="mb-3">
                                                          <label class="form-label small fw-bold">Họ và tên HLV:</label>
                                                          <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($t['name']); ?>" required>
                                                      </div>
                                                      <div class="mb-3">
                                                          <label class="form-label small fw-bold">Chuyên môn thể hình:</label>
                                                          <select name="specialty" class="form-select" required>
                                                              <option value="Cardio & HIIT" <?php if($t['specialty']=='Cardio & HIIT') echo 'selected'; ?>>Cardio & HIIT</option>
                                                              <option value="HIIT" <?php if($t['specialty']=='HIIT') echo 'selected'; ?>>HIIT</option>
                                                              <option value="Yoga" <?php if($t['specialty']=='Yoga') echo 'selected'; ?>>Yoga</option>
                                                              <option value="Pilates" <?php if($t['specialty']=='Pilates') echo 'selected'; ?>>Pilates</option>
                                                              <option value="Calisthenics" <?php if($t['specialty']=='Calisthenics') echo 'selected'; ?>>Calisthenics</option>
                                                              <option value="Group X" <?php if($t['specialty']=='Group X') echo 'selected'; ?>>Group X</option>
                                                          </select>
                                                      </div>
                                                  </div>
                                                  <div class="modal-footer bg-light">
                                                      <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Đóng</button>
                                                      <button type="submit" class="btn btn-sm btn-primary fw-bold"><i class="fas fa-save me-1"></i>Lưu Thay Đổi</button>
                                                  </div>
                                              </form>
                                            </div>
                                          </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thêm Huấn Luyện Viên Mới -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-user-plus text-success me-2"></i>Thêm Huấn Luyện Viên Mới</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action" value="add_trainer">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control form-control-sm" placeholder="VD: hlv06" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Mật khẩu</label>
                            <input type="password" name="password" class="form-control form-control-sm" placeholder="Mật khẩu" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Họ và tên</label>
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="VD: Lê Thị Cúc" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Giới tính</label>
                            <select name="gender" class="form-select form-select-sm">
                                <option value="Nam">PT Nam</option>
                                <option value="Nữ">PT Nữ</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Chuyên môn</label>
                            <select name="specialty" class="form-select form-select-sm" required>
                                <option value="Cardio & HIIT">Cardio & HIIT</option>
                                <option value="HIIT">HIIT</option>
                                <option value="Yoga">Yoga</option>
                                <option value="Pilates">Pilates</option>
                                <option value="Calisthenics">Calisthenics</option>
                                <option value="Group X">Group X</option>
                            </select>
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold py-2"><i class="fas fa-plus me-1"></i>Tạo Tài Khoản HLV</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Tab 5: Lịch Trình Lớp Học -->
  <div class="tab-pane fade" id="schedules" role="tabpanel">
    <div class="row g-4">
        <!-- Danh Sách Lớp Học Đang Xếp -->
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt text-warning me-2"></i>Lịch Trình Các Lớp Tập</h5>
                    <span class="badge bg-secondary"><?php echo count($schedules_list); ?> lớp</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="ps-3">Tên lớp học</th>
                                    <th>HLV phụ trách</th>
                                    <th>Thời gian</th>
                                    <th>Sức chứa</th>
                                    <th class="text-end pe-3">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedules_list as $sl): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary"><?php echo htmlspecialchars($sl["class_name"]); ?></td>
                                    <td><?php echo htmlspecialchars($sl["trainer_name"]); ?></td>
                                    <td><small class="text-muted"><i class="far fa-clock me-1"></i><?php echo date('d/m H:i', strtotime($sl["start_time"])); ?> - <?php echo date('H:i', strtotime($sl["end_time"])); ?></small></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo $sl["capacity"]; ?> chỗ</span></td>
                                    <td class="text-end pe-3">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                            <input type="hidden" name="action" value="delete_schedule">
                                            <input type="hidden" name="id" value="<?php echo $sl["id"]; ?>">
                                            <button type="submit" class="btn-action-delete" onclick="return confirm('Bạn có chắc chắn muốn hủy lịch lớp học này?');" title="Hủy lớp">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Xếp Lịch Lớp Mới -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-plus text-success me-2"></i>Xếp Lịch Lớp Mới</h5>
                </div>
                <div class="card-body p-4">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action" value="add_schedule">
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Huấn luyện viên phụ trách</label>
                            <select name="trainer_id" class="form-select form-select-sm" required>
                                <option value="">-- Chọn Huấn Luyện Viên --</option>
                                <?php foreach ($trainers as $t): ?>
                                <option value="<?php echo $t["id"]; ?>"><?php echo htmlspecialchars($t["name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Môn học & Tên lớp</label>
                            <select name="class_name" class="form-select form-select-sm" required>
                                <option value="">-- Chọn bài tập / Lớp học --</option>
                                <option value="Lớp Cardio Thể Lực">Cardio</option>
                                <option value="Lớp HIIT Đốt Mỡ Nhanh">HIIT</option>
                                <option value="Lớp Yoga Dẻo Dai">Yoga</option>
                                <option value="Lớp Pilates Thon Gọn">Pilates</option>
                                <option value="Lớp Calisthenics Đường Phố">Calisthenics</option>
                                <option value="Lớp Group X Năng Động">Group X</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Bắt đầu</label>
                            <input type="datetime-local" name="start_time" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-muted">Kết thúc</label>
                            <input type="datetime-local" name="end_time" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-muted">Sức chứa tối đa (người)</label>
                            <input type="number" name="capacity" class="form-control form-control-sm" placeholder="VD: 5" required>
                        </div>
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold py-2"><i class="fas fa-calendar-check me-1"></i>Lưu Lịch Lớp Mới</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>

<?php require_once "footer.php"; ?>
