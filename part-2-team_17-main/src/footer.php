<footer class="p-3 d-flex justify-content-between align-items-center">

    <section id="page-links" class="d-flex flex-column">
        <a class="link" href="dashboard.php" rel="noopener noreferrer">Dashboard</a>
        <a class="link" href="search.php?type=projects" rel="noopener noreferrer">Search Projects</a>
        <a class="link" href="search.php?type=knowledge" rel="noopener noreferrer">Search Knowledge</a>
    </section>

    <section id="attributes" class="m-4">
        <h4 class="primary-text text-end">2026 Team_17 for COB290 [Make-It-All]</h4>
    </section>
</footer>
</body>

<script>
// Toggles the badges so they are selectable for the search. 
document.querySelectorAll('.badge-toggle').forEach(badge => {
badge.classList.add('disabled'); 

badge.addEventListener('click', () => {
    const enabled = !badge.classList.contains('disabled');

    if (enabled) {
        // Disable
        badge.classList.add('disabled');
    } else {
        // Enable
        badge.classList.remove('disabled');
    }
});

// Call Update Search
});
</script>

</html>