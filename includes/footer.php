  </div><!-- /page-body -->
</div><!-- /main-wrap -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('open');
  document.getElementById('sidebarOverlay').classList.toggle('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}
// Auto-dismiss flash after 4s
setTimeout(() => {
  const a = document.querySelector('.alert.show');
  if (a) { const b = bootstrap.Alert.getOrCreateInstance(a); b.close(); }
}, 4000);
</script>
</body>
</html>
