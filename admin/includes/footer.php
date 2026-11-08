    </div>
  </div>
</div>
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
