</div><!-- end adm-body -->
    <footer class="adm-footer">
        <span>&copy; <?php echo date('Y'); ?> MEDDEMY. All rights reserved.</span>
        <span>Made with <span style="color:#e74c3c">♥</span> for students.</span>
    </footer>
</div><!-- end adm-main -->

<script>
// Sidebar toggle
const admMenuToggle = document.getElementById('admMenuToggle');
const admSidebar    = document.getElementById('admSidebar');
if (admMenuToggle) {
    admMenuToggle.addEventListener('click', () => admSidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
        if (admSidebar.classList.contains('open') &&
            !admSidebar.contains(e.target) && !admMenuToggle.contains(e.target))
            admSidebar.classList.remove('open');
    });
}

// Auto-dismiss alerts after 5s
document.querySelectorAll('.adm-alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s, transform .4s';
        el.style.opacity = '0';
        el.style.transform = 'translateY(-6px)';
        setTimeout(() => el.remove(), 400);
    }, 5000);
});
</script>
</body>
</html>