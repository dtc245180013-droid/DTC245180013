import os

member_path = r'C:\Users\Admin\.gemini\antigravity\scratch\gym_app\web\src\member.php'
trainer_path = r'C:\Users\Admin\.gemini\antigravity\scratch\gym_app\web\src\trainer.php'

# Update member.php
with open(member_path, 'r', encoding='utf-8') as f:
    content = f.read()

new_member_html = '''<ul class="nav nav-tabs mb-4" id="memberTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab" aria-controls="bookings" aria-selected="true">My Bookings</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="classes-tab" data-bs-toggle="tab" data-bs-target="#classes" type="button" role="tab" aria-controls="classes" aria-selected="false">Available Classes</button>
  </li>
</ul>

<div class="tab-content" id="memberTabsContent">
  <!-- My Bookings Tab -->
  <div class="tab-pane fade show active" id="bookings" role="tabpanel" aria-labelledby="bookings-tab">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list-ul"></i> My Bookings</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Class</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_bookings as $b): ?>
                            <tr>
                                <td class="ps-3"><?php echo htmlspecialchars($b["class_name"]); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, H:i', strtotime($b["start_time"]))); ?></td>
                                <td>
                                    <?php if ($b["status"] === "booked"): ?>
                                        <span class="badge bg-warning text-dark">Booked</span>
                                    <?php elseif ($b["status"] === "attended"): ?>
                                        <span class="badge bg-success">Attended</span>
                                    <?php elseif ($b["status"] === "cancelled"): ?>
                                        <span class="badge bg-danger">Cancelled</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($b["status"]); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($b["status"] === "booked"): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($b["id"]); ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Cancel</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($my_bookings) === 0): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No bookings yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
  </div>

  <!-- Available Classes Tab -->
  <div class="tab-pane fade" id="classes" role="tabpanel" aria-labelledby="classes-tab">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-plus"></i> Available Classes</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Class</th>
                                <th>Time</th>
                                <th>Capacity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td class="ps-3"><?php echo htmlspecialchars($s["class_name"]); ?></td>
                                <td><?php echo htmlspecialchars(date('M d, H:i', strtotime($s["start_time"]))); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($s["capacity"]); ?></span></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                        <input type="hidden" name="action" value="book">
                                        <input type="hidden" name="schedule_id" value="<?php echo htmlspecialchars($s["id"]); ?>">
                                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-bookmark"></i> Book</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($schedules) === 0): ?>
                            <tr><td colspan="4" class="text-center text-muted py-3">No classes available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
'''
start_idx = content.find('<div class="row">')
end_idx = content.find('<?php require_once "footer.php"; ?>')
if start_idx != -1 and end_idx != -1:
    new_content = content[:start_idx] + new_member_html + content[end_idx:]
    with open(member_path, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print('member.php updated successfully')

# Update trainer.php
with open(trainer_path, 'r', encoding='utf-8') as f:
    content = f.read()

new_trainer_html = '''<ul class="nav nav-tabs mb-4" id="trainerTabs" role="tablist">
    <?php foreach ($schedules as $index => $s): ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                id="schedule-tab-<?php echo $s['id']; ?>" 
                data-bs-toggle="tab" 
                data-bs-target="#schedule-<?php echo $s['id']; ?>" 
                type="button" role="tab" 
                aria-controls="schedule-<?php echo $s['id']; ?>" 
                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>">
            <?php echo htmlspecialchars($s["class_name"]); ?>
        </button>
    </li>
    <?php endforeach; ?>
</ul>

<div class="tab-content" id="trainerTabsContent">
    <?php foreach ($schedules as $index => $s): ?>
    <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
         id="schedule-<?php echo $s['id']; ?>" 
         role="tabpanel" 
         aria-labelledby="schedule-tab-<?php echo $s['id']; ?>">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-check"></i> <?php echo htmlspecialchars($s["class_name"]); ?></h5>
                        <small><i class="far fa-clock"></i> <?php echo htmlspecialchars($s["start_time"]); ?> - <?php echo htmlspecialchars($s["end_time"]); ?></small>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $bStmt = $pdo->prepare("SELECT b.id, m.name, b.status FROM bookings b JOIN members m ON b.member_id = m.id WHERE b.schedule_id = ?");
                        $bStmt->execute([$s["id"]]);
                        $bookings = $bStmt->fetchAll();
                        ?>
                        <table class="table table-hover table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Member</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td class="ps-3"><?php echo htmlspecialchars($b["name"]); ?></td>
                                    <td>
                                        <?php if ($b["status"] === "booked"): ?>
                                            <span class="badge bg-warning text-dark">Booked</span>
                                        <?php elseif ($b["status"] === "attended"): ?>
                                            <span class="badge bg-success">Attended</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($b["status"]); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($b["status"] === "booked"): ?>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION["csrf_token"]; ?>">
                                                <input type="hidden" name="action" value="mark_attendance">
                                                <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($b["id"]); ?>">
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Mark Attended</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (count($bookings) === 0): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No bookings yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
'''
start_idx = content.find('<div class="row">')
end_idx = content.find('<?php require_once "footer.php"; ?>')
if start_idx != -1 and end_idx != -1:
    new_content = content[:start_idx] + new_trainer_html + content[end_idx:]
    with open(trainer_path, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print('trainer.php updated successfully')
