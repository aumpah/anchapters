<?php $curr = basename($_SERVER['PHP_SELF']); $currDir = basename(dirname($_SERVER['PHP_SELF'])); ?>
<div class="sidebar text-white py-4">
  <h4 class="text-center mb-4" style="letter-spacing:1px;"> An Chapters</h4>
  <a href="<?= BASE_URL ?>admin/dashboard.php" class="<?= $curr=='dashboard.php'?'active':'' ?>"> Dashboard</a>
  <a href="<?= BASE_URL ?>admin/kategori/index.php" class="<?= $currDir=='kategori'?'active':'' ?>"> Kategori Buku</a>
  <a href="<?= BASE_URL ?>admin/buku/index.php" class="<?= $currDir=='buku'?'active':'' ?>"> Data Buku</a>
  <a href="<?= BASE_URL ?>admin/user/index.php" class="<?= $currDir=='user'?'active':'' ?>"> Data User</a>
  <a href="<?= BASE_URL ?>admin/order/index.php" class="<?= $currDir=='order'?'active':'' ?>"> Pesanan</a>
  <a href="<?= BASE_URL ?>admin/contact/index.php" class="<?= $currDir=='contact'?'active':'' ?>"> Pesan Masuk</a>
  <hr style="border-color:rgba(255,255,255,.2);margin:16px 8px;">
  <a href="<?= BASE_URL ?>auth/logout.php"> Logout</a>
</div>
