path = "/home/huyenxinh/gym/web/src/manager.php"
with open(path, "r", encoding="utf-8") as f:
    text = f.read()

# Fix broken receipt block
bad_block = """<?php if (isset(['receipt'])): 
     = ['receipt'];
    unset(['receipt']);
?>"""

good_block = """<?php if (isset($_SESSION['receipt'])): 
    $rc = $_SESSION['receipt'];
    unset($_SESSION['receipt']);
?>"""

text = text.replace(bad_block, good_block)
text = text.replace("<?php echo ['receipt_no']; ?>", "<?php echo $rc['receipt_no']; ?>")
text = text.replace("<?php echo ['created_at']; ?>", "<?php echo $rc['created_at']; ?>")
text = text.replace("<?php echo htmlspecialchars(['member_name']); ?>", "<?php echo htmlspecialchars($rc['member_name']); ?>")
text = text.replace("<?php echo htmlspecialchars(['package_name']); ?>", "<?php echo htmlspecialchars($rc['package_name']); ?>")
text = text.replace("<?php echo ['start_date']; ?>", "<?php echo $rc['start_date']; ?>")
text = text.replace("<?php echo ['end_date']; ?>", "<?php echo $rc['end_date']; ?>")
text = text.replace("<?php echo ['payment_method']; ?>", "<?php echo $rc['payment_method']; ?>")
text = text.replace("<?php echo htmlspecialchars(['cashier']); ?>", "<?php echo htmlspecialchars($rc['cashier']); ?>")
text = text.replace("<?php echo number_format(['price'], 0, ',', '.'); ?>", "<?php echo number_format($rc['price'], 0, ',', '.'); ?>")

with open(path, "w", encoding="utf-8") as f:
    f.write(text)
print("FIXED SUCCESSFULLY")
