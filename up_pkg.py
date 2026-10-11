path = "/home/huyenxinh/gym/web/src/manager.php"
with open(path, "r", encoding="utf-8") as f:
    text = f.read()

# 1. Add edit_package backend action
old_backend = """    // Package CRUD
    if ($_POST["action"] === "add_package") {
        $stmt = $pdo->prepare("INSERT INTO packages (name, duration_days, price) VALUES (?, ?, ?)");
        $stmt->execute([$_POST["name"], $_POST["duration_days"], $_POST["price"]]);
    } elseif ($_POST["action"] === "delete_package") {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
        $stmt->execute([$_POST["id"]]);
    }"""

new_backend = """    // Package CRUD
    if ($_POST["action"] === "add_package") {
        $stmt = $pdo->prepare("INSERT INTO packages (name, duration_days, price) VALUES (?, ?, ?)");
        $stmt->execute([$_POST["name"], $_POST["duration_days"], $_POST["price"]]);
    } elseif ($_POST["action"] === "edit_package") {
        $stmt = $pdo->prepare("UPDATE packages SET name = ?, duration_days = ?, price = ? WHERE id = ?");
        $stmt->execute([$_POST["name"], $_POST["duration_days"], $_POST["price"], $_POST["id"]]);
    } elseif ($_POST["action"] === "delete_package") {
        $stmt = $pdo->prepare("DELETE FROM packages WHERE id = ?");
        $stmt->execute([$_POST["id"]]);
    }"""

if old_backend in text:
    text = text.replace(old_backend, new_backend)
    print("Backend edit_package added")
else:
    print("Could not find old package backend")

# 2. Update packages tab UI
old_packages_tab = """  <!-- Danh Sách Gói Tập Tab -->
  <div class="tab-pane fade" id="packages" role="tabpanel" aria-labelledby="packages-tab">
    <div class="row">
        <!-- Danh Sách Gói Tập Section -->
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white"><h5 class="mb-0">Danh Sách Gói Tập</h5></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <tbody>
                            <?php foreach ($packages as $p): ?>
                            <tr>
                                <td class="ps-3"><?php echo htmlspecialchars($p["name"]); ?></td>
                                <td><?php echo number_format($p["price"] * 25000, 0, ",", "."); ?> VNĐ</td>
                                <td class="text-end pe-3">
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                        <input type="hidden" name="action" value="delete_package">
                                        <input type="hidden" name="id" value="<?php echo $p["id"]; ?>">
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
                        <input type="hidden" name="action" value="add_package">
                        <div class="col-12"><input type="text" name="name" class="form-control form-control-sm" placeholder="Họ và tên" required></div>
                        <div class="col-6"><input type="number" name="duration_days" class="form-control form-control-sm" placeholder="Days" required></div>
                        <div class="col-6"><input type="number" step="0.01" name="price" class="form-control form-control-sm" placeholder="Price" required></div>
                        <div class="col-12"><button type="submit" class="btn btn-sm btn-primary w-100">Thêm Gói Tập Mới</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
  </div>"""

new_packages_tab = """  <!-- Danh Sách Gói Tập Tab -->
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
  </div>"""

if old_packages_tab in text:
    text = text.replace(old_packages_tab, new_packages_tab)
    print("Packages tab replaced successfully")
else:
    print("Could not find old packages tab directly")

# 3. Replace any remaining English text in manager.php
text = text.replace('Manager Dashboard', 'Bảng Quản Trị Hệ Thống')
text = text.replace('<h5 class="mb-0">Add Trainer</h5>', '<h5 class="mb-0">Thêm Huấn Luyện Viên Mới</h5>')
text = text.replace('<h5 class="mb-0">Add Schedule</h5>', '<h5 class="mb-0">Xếp Lịch Lớp Mới</h5>')

with open(path, "w", encoding="utf-8") as f:
    f.write(text)
print("Manager.php updated completely")
