<?php
require_once "config.php";
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "manager") {
    die("Access denied");
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
            
            $stmt = $pdo->prepare("INSERT INTO trainers (user_id, name, specialty) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $_POST["name"], $_POST["specialty"]]);
            $pdo->commit();
        } catch (Exception $e) { $pdo->rollBack(); }
    }
    
    // Schedule CRUD
    elseif ($_POST["action"] === "add_schedule") {
        $stmt = $pdo->prepare("INSERT INTO schedules (trainer_id, class_name, start_time, end_time, capacity) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_POST["trainer_id"], $_POST["class_name"], $_POST["start_time"], $_POST["end_time"], $_POST["capacity"]]);
    }
    
    // Renew
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
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }
    header("Location: manager.php");
    exit;
}

$members = $pdo->query("SELECT * FROM members")->fetchAll();
$packages = $pdo->query("SELECT * FROM packages")->fetchAll();
$trainers = $pdo->query("SELECT * FROM trainers")->fetchAll();

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
            <h5 class="alert-heading mb-0 fw-bold text-success">Thanh toán thành công!</h5>
            <small class="text-muted">Hệ thống đã gia hạn gói tập và tự động xuất hóa đơn thanh toán.</small>
        </div>
    </div>
    
    <!-- Receipt Card -->
    <div class="card mx-auto shadow-sm" style="max-width: 450px; border: 2px dashed #CBD5E1 !important;">
        <div class="card-body p-4">
            <div class="text-center mb-3">
                <h4 class="fw-bold text-primary mb-1"><i class="fas fa-dumbbell"></i> GYM FITNESS</h4>
                <p class="text-muted small mb-0">HÓA ĐƠN THANH TOÁN DỊCH VỤ</p>
                <small class="text-muted">Mã HĐ: <strong><?php echo $rc['receipt_no']; ?></strong> | Ngày: <?php echo $rc['created_at']; ?></small>
            </div>
            <hr class="my-2" style="border-top: 1px dashed #CBD5E1;">
            <div class="mb-2 small d-flex justify-content-between">
                <span class="text-muted">Khách hàng:</span>
                <strong><?php echo htmlspecialchars($rc['member_name']); ?></strong>
            </div>
            <div class="mb-2 small d-flex justify-content-between">
                <span class="text-muted">Gói dịch vụ:</span>
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
                <span class="text-muted">Thu ngân / Người lập:</span>
                <span><?php echo htmlspecialchars($rc['cashier']); ?></span>
            </div>
            <hr class="my-2" style="border-top: 2px solid #0F172A;">
            <div class="d-flex justify-content-between align-items-center py-2">
                <span class="fw-bold fs-6">TỔNG TIỀN:</span>
                <span class="fw-bolder fs-4 text-primary"><?php echo number_format($rc['price'], 0, ',', '.'); ?> VNĐ</span>
            </div>
            <div class="text-center mt-3 no-print">
                <button onclick="window.print()" class="btn btn-sm btn-primary me-2"><i class="fas fa-print me-1"></i> In Hóa Đơn</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="alert">Đóng</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<h2 class="mb-4"><i class="fas fa-chart-line text-primary"></i> Bảng Quản Trị Hệ Thống</h2>

<!-- Nav tabs -->
<ul class="nav nav-tabs mb-4" id="managerTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab" aria-controls="reports" aria-selected="true">Báo Cáo Hoạt Động</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="members-tab" data-bs-toggle="tab" data-bs-target="#members" type="button" role="tab" aria-controls="members" aria-selected="false">Quản Lý Hội Viên & Gia Hạn</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="packages-tab" data-bs-toggle="tab" data-bs-target="#packages" type="button" role="tab" aria-controls="packages" aria-selected="false">Gói Tập Luyện</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="trainers-tab" data-bs-toggle="tab" data-bs-target="#trainers" type="button" role="tab" aria-controls="trainers" aria-selected="false">Đội Ngũ Huấn Luyện Viên</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="schedules-tab" data-bs-toggle="tab" data-bs-target="#schedules" type="button" role="tab" aria-controls="schedules" aria-selected="false">Lịch Học & Lớp Luyện Tập</button>
  </li>
</ul>

<!-- Tab panes -->
<div class="tab-content" id="managerTabsContent">
  <!-- Reports Tab -->
  <div class="tab-pane fade show active" id="reports" role="tabpanel" aria-labelledby="reports-tab">
    <div class="row mb-4">
        <div class="col-md-4 mb-2">
            <div class="card bg-success text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-dollar-sign"></i> Tổng Doanh Thu Phòng Gym</h5>
                    <h3 class="mb-0"><?php echo number_format(($revenue ?? 0) * 25000, 0, ",", "."); ?> VNĐ</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card bg-info text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-users"></i> Hội Viên Mới (30 ngày qua)</h5>
                    <h3 class="mb-0"><?php echo $new_members; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card bg-warning text-dark shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-percentage"></i> Tỷ Lệ Lấp Đầy Lớp Học</h5>
                    <h3 class="mb-0"><?php echo number_format(($fill_rate ?? 0) * 100, 2); ?>%</h3>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Danh Sách Hội Viên & Renew Tab -->
  <div class="tab-pane fade" id="members" role="tabpanel" aria-labelledby="members-tab">
    <div class="row">
        <!-- Danh Sách Hội Viên Section -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><h5 class="mb-0">Danh Sách Hội Viên</h5></div>
                <div class="card-body p-0" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-hover mb-0">
                        <tbody>
                            <?php foreach ($members as $m): ?>
                            <tr>
                                <td class="ps-3"><?php echo htmlspecialchars($m["name"]); ?></td>
                                <td class="text-end pe-3">
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                        <input type="hidden" name="action" value="delete_member">
                                        <input type="hidden" name="id" value="<?php echo $m["id"]; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-light">
                    <form method="post" class="row g-2">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action" value="add_member">
                        <div class="col-6"><input type="text" name="username" class="form-control form-control-sm" placeholder="Tên đăng nhập" required></div>
                        <div class="col-6"><input type="password" name="password" class="form-control form-control-sm" placeholder="Mật khẩu" required></div>
                        <div class="col-6"><input type="text" name="name" class="form-control form-control-sm" placeholder="Họ và tên" required></div>
                        <div class="col-6"><input type="text" name="phone" class="form-control form-control-sm" placeholder="Số điện thoại"></div>
                        <div class="col-12"><button type="submit" class="btn btn-sm btn-primary w-100">Thêm Hội Viên Mới</button></div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Gia Hạn Gói & Thanh Toán Section -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><h5 class="mb-0">Gia Hạn Gói & Thanh Toán</h5></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action" value="renew_package">
                        <div class="mb-3">
                            <select name="member_id" class="form-select form-select-sm" required>
                                <option value="">-- Chọn Hội Viên --</option>
                                <?php foreach ($members as $m): ?>
                                <option value="<?php echo htmlspecialchars($m["id"]); ?>"><?php echo htmlspecialchars($m["name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <select name="package_id" class="form-select form-select-sm" required>
                                <option value="">-- Chọn Gói Tập --</option>
                                <?php foreach ($packages as $p): ?>
                                <option value="<?php echo htmlspecialchars($p["id"]); ?>"><?php echo htmlspecialchars($p["name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Gia Hạn Gói & Thanh Toán</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Danh Sách Gói Tập Tab -->
  <div class="tab-pane fade" id="packages" role="tabpanel" aria-labelledby="packages-tab">
    <div class="row">
        <!-- Danh Sách Gói Tập Section -->
        <div class="col-md-7 mb-4">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-box-open text-warning me-2"></i>Danh Sách Gói Tập Hiện Có</h5>
                    <span class="badge bg-secondary"><?php echo count($packages); ?> gói</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Tên gói tập</th>
                                    <th>Thời hạn</th>
                                    <th>Giá gói</th>
                                    <th class="text-end pe-3">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($packages as $p): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary"><?php echo htmlspecialchars($p["name"]); ?></td>
                                    <td><span class="badge bg-light text-dark border"><i class="far fa-clock me-1"></i><?php echo $p["duration_days"]; ?> ngày</span></td>
                                    <td class="fw-semibold text-success"><?php echo number_format($p["price"] * 25000, 0, ",", "."); ?> VNĐ</td>
                                    <td class="text-end pe-3">
                                        <!-- Nút Sửa (Edit) -->
                                        <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $p['id']; ?>" title="Chỉnh sửa gói tập">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <!-- Nút Xóa -->
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                            <input type="hidden" name="action" value="delete_package">
                                            <input type="hidden" name="id" value="<?php echo $p["id"]; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa gói tập này?');" title="Xóa gói tập">
                                                <i class="fas fa-trash"></i>
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
                                                          <label class="form-label small fw-bold">Đơn giá gốc ($ - hệ số x25.000 VNĐ):</label>
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

        <!-- Thêm Gói Tập Mới Section -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-dark text-white"><h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle text-success me-2"></i>Thêm Gói Tập Mới</h5></div>
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
                            <label class="form-label small fw-semibold text-muted">Giá tiền gốc ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control form-control-sm" placeholder="VD: 50" required>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-sm btn-primary w-100 fw-bold py-2"><i class="fas fa-plus me-1"></i>Tạo Gói Mới</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Danh Sách Huấn Luyện Viên (PT) Tab -->
  <div class="tab-pane fade" id="trainers" role="tabpanel" aria-labelledby="trainers-tab">
    <div class="row">
        <!-- Danh Sách Huấn Luyện Viên (PT) Section -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><h5 class="mb-0">Thêm Huấn Luyện Viên Mới</h5></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action" value="add_trainer">
                        <div class="mb-2"><input type="text" name="username" class="form-control form-control-sm" placeholder="Tên đăng nhập" required></div>
                        <div class="mb-2"><input type="password" name="password" class="form-control form-control-sm" placeholder="Mật khẩu" required></div>
                        <div class="mb-2"><input type="text" name="name" class="form-control form-control-sm" placeholder="Họ và tên" required></div>
                        <div class="mb-2">
    <select name="gender" class="form-select form-select-sm">
        <option value="Nam">PT Nam</option>
        <option value="Nữ">PT Nữ</option>
    </select>
</div>
<div class="mb-2">
    <select name="specialty" class="form-select form-select-sm" required>
        <option value="Cardio & HIIT">Cardio & HIIT</option>
        <option value="HIIT">HIIT</option>
        <option value="Yoga">Yoga</option>
        <option value="Pilates">Pilates</option>
        <option value="Calisthenics">Calisthenics</option>
        <option value="Group X">Group X</option>
    </select>
</div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Thêm Huấn Luyện Viên Mới</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Lịch Trình Các Lớp Học Tab -->
  <div class="tab-pane fade" id="schedules" role="tabpanel" aria-labelledby="schedules-tab">
    <div class="row">
        <!-- Lịch Trình Các Lớp Học Section -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><h5 class="mb-0">Xếp Lịch Lớp Mới</h5></div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                        <input type="hidden" name="action" value="add_schedule">
                        <div class="mb-2">
                            <select name="trainer_id" class="form-select form-select-sm" required>
                                <option value="">-- Chọn Huấn Luyện Viên --</option>
                                <?php foreach ($trainers as $t): ?>
                                <option value="<?php echo $t["id"]; ?>"><?php echo htmlspecialchars($t["name"]); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
    <select name="class_name" class="form-select form-select-sm" required>
        <option value="">-- Chọn bài tập / Lớp học --</option>
        <option value="Lớp Cardio Thể Lực">Cardio</option>
        <option value="Lớp HIIT Đốt Mỡ">HIIT</option>
        <option value="Lớp Yoga Dẻo Dai">Yoga</option>
        <option value="Lớp Pilates Thon Gọn">Pilates</option>
        <option value="Lớp Calisthenics Đường Phố">Calisthenics</option>
        <option value="Lớp Group X Năng Động">Group X</option>
    </select>
</div>
                        <div class="mb-2">
                            <label class="form-label mb-0 small">Start</label>
                            <input type="datetime-local" name="start_time" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label mb-0 small">End</label>
                            <input type="datetime-local" name="end_time" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3"><input type="number" name="capacity" class="form-control form-control-sm" placeholder="Sức chứa tối đa (người)" required></div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Xếp Lịch Lớp Mới</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
<?php require_once "footer.php"; ?>
