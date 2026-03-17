// Enhanced Authentication Pages JavaScript

// Toggle password visibility
function togglePassword(inputId, icon) {
    const passwordInput = document.getElementById(inputId);
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.textContent = '🔒';
    } else {
        passwordInput.type = 'password';
        icon.textContent = '👁️';
    }
}

// Password strength checker
function checkPasswordStrength(password) {
    let strength = 0;
    
    // Check password length
    if (password.length >= 8) strength += 1;
    if (password.length >= 12) strength += 1;
    
    // Check for lowercase letters
    if (/[a-z]/.test(password)) strength += 1;
    
    // Check for uppercase letters
    if (/[A-Z]/.test(password)) strength += 1;
    
    // Check for numbers
    if (/\d/.test(password)) strength += 1;
    
    // Check for special characters
    if (/[^a-zA-Z\d]/.test(password)) strength += 1;
    
    return strength;
}

// Update password strength indicator
function updatePasswordStrength(inputId, strengthBarId, strengthTextId) {
    const passwordInput = document.getElementById(inputId);
    const strengthBar = document.getElementById(strengthBarId);
    const strengthText = document.getElementById(strengthTextId);
    const strengthContainer = strengthBar.parentElement;
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        if (password.length === 0) {
            strengthContainer.classList.remove('show');
            return;
        }
        
        strengthContainer.classList.add('show');
        const strength = checkPasswordStrength(password);
        
        // Remove all classes
        strengthBar.className = 'password-strength-bar';
        strengthText.className = 'password-strength-text';
        
        // Add appropriate class based on strength
        if (strength <= 2) {
            strengthBar.classList.add('weak');
            strengthText.classList.add('weak');
            strengthText.textContent = 'Weak password';
        } else if (strength <= 4) {
            strengthBar.classList.add('medium');
            strengthText.classList.add('medium');
            strengthText.textContent = 'Medium password';
        } else {
            strengthBar.classList.add('strong');
            strengthText.classList.add('strong');
            strengthText.textContent = 'Strong password';
        }
    });
}

// Add input icons dynamically
function addInputIcons() {
    // Add user icon to full name input
    const fullNameInput = document.getElementById('full_name');
    if (fullNameInput && !fullNameInput.previousElementSibling?.classList.contains('input-icon')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'input-with-icon';
        fullNameInput.parentNode.insertBefore(wrapper, fullNameInput);
        
        const icon = document.createElement('span');
        icon.className = 'input-icon';
        icon.textContent = '👤';
        
        wrapper.appendChild(fullNameInput);
        wrapper.appendChild(icon);
    }
    
    // Add email icon to email input
    const emailInput = document.getElementById('email');
    if (emailInput && !emailInput.previousElementSibling?.classList.contains('input-icon')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'input-with-icon';
        emailInput.parentNode.insertBefore(wrapper, emailInput);
        
        const icon = document.createElement('span');
        icon.className = 'input-icon';
        icon.textContent = '📧';
        
        wrapper.appendChild(emailInput);
        wrapper.appendChild(icon);
    }
    
    // Add lock icons to password inputs
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        const wrapper = input.closest('.password-wrapper');
        if (wrapper && !wrapper.querySelector('.input-icon')) {
            const icon = document.createElement('span');
            icon.className = 'input-icon';
            icon.textContent = '🔒';
            wrapper.insertBefore(icon, input);
        }
    });
}

// Form submission with loading state
function handleFormSubmit() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            
            // Add loading state
            submitButton.classList.add('button-loading');
            submitButton.disabled = true;
            
            const originalText = submitButton.textContent;
            submitButton.textContent = 'Please wait...';
            
            // If form validation fails, re-enable button
            setTimeout(() => {
                if (!form.checkValidity()) {
                    submitButton.classList.remove('button-loading');
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            }, 100);
        });
    });
}

// Real-time email validation
function validateEmail() {
    const emailInput = document.getElementById('email');
    if (!emailInput) return;
    
    emailInput.addEventListener('blur', function() {
        const email = this.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            this.style.borderColor = '#f56565';
        } else if (email) {
            this.style.borderColor = '#48bb78';
        }
    });
    
    emailInput.addEventListener('focus', function() {
        this.style.borderColor = '#667eea';
    });
}

// Password match validation for registration
function validatePasswordMatch() {
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    
    if (!confirmPasswordInput) return;
    
    confirmPasswordInput.addEventListener('input', function() {
        if (this.value === '') {
            this.style.borderColor = '#e2e8f0';
            return;
        }
        
        if (this.value === passwordInput.value) {
            this.style.borderColor = '#48bb78';
        } else {
            this.style.borderColor = '#f56565';
        }
    });
}

// Add brand logo
function addBrandLogo() {
    const containers = document.querySelectorAll('.login-container, .register-container');
    
    containers.forEach(container => {
        const h2 = container.querySelector('h2');
        if (h2 && !container.querySelector('.brand-logo')) {
            const logo = document.createElement('div');
            logo.className = 'brand-logo';
            
            const subtitle = document.createElement('p');
            subtitle.className = 'subtitle';
            subtitle.textContent = 'Welcome to Coopamart';
            
            container.insertBefore(logo, h2);
            h2.insertAdjacentElement('afterend', subtitle);
        }
    });
}

// Initialize password strength indicator for register page
function initPasswordStrength() {
    const passwordInput = document.getElementById('password');
    if (!passwordInput) return;
    
    // Check if we're on register page (has confirm_password)
    const confirmPassword = document.getElementById('confirm_password');
    if (!confirmPassword) return;
    
    // Create strength indicator
    const wrapper = passwordInput.closest('.password-wrapper');
    if (wrapper && !wrapper.querySelector('.password-strength')) {
        const strengthContainer = document.createElement('div');
        strengthContainer.className = 'password-strength';
        strengthContainer.id = 'password-strength';
        
        const strengthBar = document.createElement('div');
        strengthBar.className = 'password-strength-bar';
        strengthBar.id = 'password-strength-bar';
        
        const strengthText = document.createElement('div');
        strengthText.className = 'password-strength-text';
        strengthText.id = 'password-strength-text';
        
        strengthContainer.appendChild(strengthBar);
        
        wrapper.parentNode.insertBefore(strengthContainer, wrapper.nextSibling);
        wrapper.parentNode.insertBefore(strengthText, strengthContainer.nextSibling);
        
        // Initialize strength checker
        updatePasswordStrength('password', 'password-strength-bar', 'password-strength-text');
    }
}

// Auto-dismiss success/error messages after 5 seconds
function autoDismissMessages() {
    const messages = document.querySelectorAll('.success, .error');
    
    messages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            message.style.opacity = '0';
            message.style.transform = 'translateY(-20px)';
            
            setTimeout(() => {
                message.remove();
            }, 500);
        }, 5000);
    });
}

// Initialize all enhancements when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    addBrandLogo();
    addInputIcons();
    handleFormSubmit();
    validateEmail();
    validatePasswordMatch();
    initPasswordStrength();
    autoDismissMessages();
    
    // Add smooth focus effects
    const inputs = document.querySelectorAll('input');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.01)';
            this.parentElement.style.transition = 'transform 0.2s ease';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
});