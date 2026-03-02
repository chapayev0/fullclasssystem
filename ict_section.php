    <section class="dilhara-section">
        <!-- Floating particles -->
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        
        <div class="dilhara-text">
            <?php 
            $full_name = get_site_setting('institute_name', 'ICT with Dilhara');
            $parts = explode(' ', $full_name);
            $first_part = array_shift($parts);
            $rest = implode(' ', $parts);
            if (empty($rest)) {
                $rest = $first_part;
                $first_part = '';
            }
            ?>
            <?php if ($first_part): ?>
                <div class="small-text"><?php echo htmlspecialchars($first_part); ?></div>
            <?php endif; ?>
            <div class="large-text"><?php echo htmlspecialchars($rest); ?></div>
        </div>
    </section>
