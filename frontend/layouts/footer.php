<?php
// Pastikan base URL tersedia di footer juga
if (!isset($base_url)) {
    $base_url = '/surat/frontend/';
}
?>
</div> <!-- .gmail-container -->

<!-- Scripts dengan base URL -->
<script src="<?php echo $base_url; ?>assets/js/utils.js"></script>
<script src="<?php echo $base_url; ?>assets/js/api.js"></script>
<script src="<?php echo $base_url; ?>assets/js/components.js"></script>
<script src="<?php echo $base_url; ?>assets/js/main.js"></script>
</body>

</html>