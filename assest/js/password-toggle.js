document.addEventListener('DOMContentLoaded', function () {
    const passwordInputs = document.querySelectorAll('input[type="password"]');

    passwordInputs.forEach(input => {
        // Ensure parent has relative positioning
        const wrapper = input.parentElement;
        if (getComputedStyle(wrapper).position === 'static') {
            wrapper.style.position = 'relative';
        }

        // Create Toggle Button
        const toggleBtn = document.createElement('span');
        toggleBtn.className = 'password-toggle-icon';
        toggleBtn.style.position = 'absolute';
        toggleBtn.style.right = '15px';
        toggleBtn.style.top = '38px'; // Adjusted for label height + padding
        // If label is present, checking offset might be safer, but fixed top is okay for now if structures are consistent.
        // Better: top 50% of the INPUT, not the wrapper.

        // Let's try centering purely based on the input's box.
        // But the input is typically 100% width relative to wrapper.
        // Let's stick to standard positioning.
        // Most forms here have <div class="form-group"><label>...</label><input>...</div>
        // So top: 50% of wrapper isn't right because of the label.
        // It needs to be aligned with the input.

        toggleBtn.style.top = 'auto';
        toggleBtn.style.bottom = '12px'; // Approximate center for standard inputs with labels
        toggleBtn.style.cursor = 'pointer';
        toggleBtn.style.color = '#64748B';
        toggleBtn.style.zIndex = '10';

        const eyeIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>`;
        const eyeOffIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>`;

        toggleBtn.innerHTML = eyeIcon;

        toggleBtn.onclick = function () {
            if (input.type === "password") {
                input.type = "text";
                this.innerHTML = eyeOffIcon;
            } else {
                input.type = "password";
                this.innerHTML = eyeIcon;
            }
        };

        wrapper.appendChild(toggleBtn);
    });
});
