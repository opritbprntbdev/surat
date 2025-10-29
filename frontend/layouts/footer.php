<?php
// Pastikan base URL tersedia di footer juga
if (!isset($base_url)) {
    $base_url = '/surat/frontend/';
}
?>
</div> <!-- .gmail-container -->

<!-- Scripts dengan base URL -->
<script>
    // Tetapkan base API absolut agar panggilan fetch konsisten di semua halaman
    window.API_BASE = '/surat/backend/api';
</script>
<?php $v = '20251029'; ?>
<script src="<?php echo $base_url; ?>assets/js/utils.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $base_url; ?>assets/js/api.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $base_url; ?>assets/js/components.js?v=<?php echo $v; ?>"></script>
<script src="<?php echo $base_url; ?>assets/js/main.js?v=<?php echo $v; ?>"></script>
</body>

</html>