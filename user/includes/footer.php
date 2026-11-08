<footer class="text-center">
  <div class="container">
    <img src="<?= BASE_URL ?>assets/images/Logo(1).png" style="height:40px;" class="mb-2">
    <h5 class="mb-2">An Chapters</h5>
    <p class="mb-1" style="opacity:.85;">Toko buku online tepercaya untuk semua kalangan pembaca.</p>
    <p class="mb-0" style="font-size:.85rem;opacity:.7;">&copy; <?= date('Y') ?> An Chapters. All rights reserved.</p>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if (isset($_SESSION['flash'])): ?>
<script>
Swal.fire({
  icon: '<?= $_SESSION['flash']['type'] ?>',
  title: '<?= addslashes($_SESSION['flash']['title']) ?>',
  text: '<?= addslashes($_SESSION['flash']['text'] ?? '') ?>',
  timer: 2200,
  showConfirmButton: false
});
</script>
<?php unset($_SESSION['flash']); endif; ?>
</body>
</html>
