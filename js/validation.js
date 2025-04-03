document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const emailForm = document.getElementById('emailForm');
    const emailInput = document.getElementById('email');
    const validateBtn = document.getElementById('validateBtn');
    const resultContainer = document.getElementById('resultContainer');
    
    if (emailForm) {
        emailForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simple email format validation
            const emailValue = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailValue) {
                showError('Please enter an email address.');
                return;
            }
            
            if (!emailRegex.test(emailValue)) {
                showError('Please enter a valid email format.');
                return;
            }
            
            // Show loading state
            validateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Validating...';
            validateBtn.disabled = true;
            
            // Submit the form to process server-side validation
            this.submit();
        });
    }
    
    // Show error message
    function showError(message) {
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger alert-dismissible fade show';
        errorAlert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insert at the top of the form
        emailForm.prepend(errorAlert);
        
        // Remove after 5 seconds
        setTimeout(() => {
            errorAlert.remove();
        }, 5000);
    }
    
    // Toggle MX records visibility
    const toggleMxBtn = document.getElementById('toggleMxRecords');
    const mxRecordsSection = document.getElementById('mxRecordsSection');
    
    if (toggleMxBtn && mxRecordsSection) {
        toggleMxBtn.addEventListener('click', function() {
            if (mxRecordsSection.style.display === 'none') {
                mxRecordsSection.style.display = 'block';
                this.innerHTML = '<i class="bi bi-eye-slash"></i> Hide MX Records';
            } else {
                mxRecordsSection.style.display = 'none';
                this.innerHTML = '<i class="bi bi-eye"></i> Show MX Records';
            }
        });
    }
    
    // Animation for results
    if (resultContainer && resultContainer.dataset.show === 'true') {
        resultContainer.classList.add('fade-in');
    }
});
