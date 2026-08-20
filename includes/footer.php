  </div>
</main>

<footer class="statusbar" id="statusBar">
  <div class="statusbar-inner">
    <div class="statusbar-group statusbar-instance">
      <span class="statusbar-label">Instance:</span>
      <span id="sbHostname">memuat…</span>
      <span class="statusbar-sep">·</span>
      <span id="sbServerIp">-</span>
    </div>
    <div class="statusbar-group">
      <span class="status-dot status-dot--pending" id="sbFeToBeDot"></span>
      <span class="statusbar-label">FE→BE:</span>
      <span id="sbFeToBeText">memeriksa…</span>
    </div>
    <div class="statusbar-group">
      <span class="status-dot status-dot--pending" id="sbFeToEfsDot"></span>
      <span class="statusbar-label">FE→EFS:</span>
      <span id="sbFeToEfsText">memeriksa…</span>
    </div>
    <div class="statusbar-group">
      <span class="status-dot status-dot--pending" id="sbBeHealthDot"></span>
      <span class="statusbar-label">BE:</span>
      <span id="sbBeHealthText">memeriksa…</span>
    </div>
    <div class="statusbar-group statusbar-updated">
      <span id="sbUpdatedAt"></span>
    </div>
  </div>
</footer>

<script src="<?= base_url('/assets/js/app.js') ?>"></script>
<script>
  mbgInitStatusBar('<?= base_url('/status.php') ?>');
</script>
</body>
</html>
