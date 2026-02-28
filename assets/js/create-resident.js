// ===================================
// Create Resident Page JavaScript
// ===================================

let currentStep = 1;
const totalSteps = 6;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    // Set active navigation
    setActiveNavigation();
    
    // Restore saved step from localStorage
    restoreSavedStep();
    
    // Initialize form functionality
    initializeForm();
    
    // Initialize photo upload
    initializePhotoUpload();
    
    // Initialize webcam
    initializeWebcam();
    
    // Initialize auto-save functionality
    initializeAutoSave();
    
    // Initialize household info functionality
    initializeHouseholdInfo();
    
    // Initialize phone number formatting
    initializePhoneNumberFormatting();
    
    console.log('Create Resident page loaded successfully');
});

// ===================================
// Save and Restore Step & Form Data
// ===================================
function restoreSavedStep() {
    const savedStep = localStorage.getItem('createResidentCurrentStep');
    if (savedStep) {
        const stepNumber = parseInt(savedStep);
        if (stepNumber >= 1 && stepNumber <= totalSteps) {
            currentStep = stepNumber;
            updateStep();
            console.log(`Restored to step ${currentStep}`);
        }
    }
    
    // Restore form data
    restoreFormData();
}

function saveCurrentStep() {
    localStorage.setItem('createResidentCurrentStep', currentStep.toString());
    // Also save form data whenever step changes
    saveFormData();
}

function clearSavedStep() {
    localStorage.removeItem('createResidentCurrentStep');
    clearFormData();
}

function saveFormData() {
    const form = document.getElementById('createResidentForm');
    if (!form) return;
    
    const formData = {};
    
    // Get all input, select, and textarea elements
    const inputs = form.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
        if (input.name && input.type !== 'file') { // Skip file inputs
            if (input.type === 'checkbox') {
                formData[input.name] = input.checked;
            } else if (input.type === 'radio') {
                if (input.checked) {
                    formData[input.name] = input.value;
                }
            } else {
                formData[input.name] = input.value;
            }
        }
    });
    
    // Save to localStorage
    localStorage.setItem('createResidentFormData', JSON.stringify(formData));
    console.log('Form data saved');
}

function restoreFormData() {
    const savedData = localStorage.getItem('createResidentFormData');
    if (!savedData) return;
    
    try {
        const formData = JSON.parse(savedData);
        const form = document.getElementById('createResidentForm');
        if (!form) return;
        
        // Restore all saved values
        Object.keys(formData).forEach(name => {
            const elements = form.querySelectorAll(`[name="${name}"]`);
            
            elements.forEach(element => {
                if (element.type === 'checkbox') {
                    element.checked = formData[name];
                } else if (element.type === 'radio') {
                    if (element.value === formData[name]) {
                        element.checked = true;
                    }
                } else {
                    element.value = formData[name];
                }
            });
        });
        
        console.log('Form data restored');
    } catch (error) {
        console.error('Error restoring form data:', error);
    }
}

function clearFormData() {
    localStorage.removeItem('createResidentFormData');
    console.log('Form data cleared');
}

// Auto-save form data on input change
function initializeAutoSave() {
    const form = document.getElementById('createResidentForm');
    if (!form) return;
    
    // Save data whenever any input changes
    form.addEventListener('input', () => {
        saveFormData();
    });
    
    // Also save on change event (for selects)
    form.addEventListener('change', () => {
        saveFormData();
    });
}

// ===================================
// Navigation
// ===================================
function setActiveNavigation() {
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    const residentsNavItem = document.querySelector('a[href="residents.php"]');
    if (residentsNavItem) {
        residentsNavItem.parentElement.classList.add('active');
    }
}

// ===================================
// Form Initialization
// ===================================
function initializeForm() {
    const form = document.getElementById('createResidentForm');
    const nextBtn = document.getElementById('nextBtn');
    const prevBtn = document.getElementById('prevBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    // Next button
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (validateStep(currentStep)) {
                nextStep();
            }
        });
    }
    
    // Previous button
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevStep();
        });
    }
    
    // Form submission
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            handleFormSubmit();
        });
    }
    
    // Auto-calculate age from date of birth
    const dobInput = document.getElementById('dateOfBirth');
    if (dobInput) {
        dobInput.addEventListener('change', calculateAge);
    }
    
    // Civil status change handler
    const civilStatusSelect = document.getElementById('civilStatus');
    if (civilStatusSelect) {
        civilStatusSelect.addEventListener('change', handleCivilStatusChange);
    }
    
    // Voter status change handler
    const voterStatusSelect = document.getElementById('voterStatus');
    if (voterStatusSelect) {
        voterStatusSelect.addEventListener('change', handleVoterStatusChange);
    }
    
    // 4Ps change handler
    const fourPsSelect = document.getElementById('fourPs');
    if (fourPsSelect) {
        fourPsSelect.addEventListener('change', handleFourPsChange);
    }
    
    // Sex change handler (for WRA section)
    const sexSelect = document.getElementById('sex');
    if (sexSelect) {
        sexSelect.addEventListener('change', handleSexChange);
    }
    
    // FP Method change handler
    const usingFpMethodSelect = document.getElementById('usingFpMethod');
    if (usingFpMethodSelect) {
        usingFpMethodSelect.addEventListener('change', handleFpMethodChange);
    }
}

// ===================================
// Step Navigation
// ===================================
function nextStep() {
    if (currentStep < totalSteps) {
        // Mark current step as completed
        const currentStepElement = document.querySelector(`.step[data-step="${currentStep}"]`);
        if (currentStepElement) {
            currentStepElement.classList.add('completed');
        }
        
        currentStep++;
        updateStep();
        saveCurrentStep(); // Save step to localStorage
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        updateStep();
        saveCurrentStep(); // Save step to localStorage
    }
}

function updateStep() {
    // Update form steps visibility
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });
    
    const activeFormStep = document.querySelector(`.form-step[data-step="${currentStep}"]`);
    if (activeFormStep) {
        activeFormStep.classList.add('active');
    }
    
    // Update progress steps
    document.querySelectorAll('.step').forEach((step, index) => {
        const stepNumber = index + 1;
        step.classList.remove('active');
        
        // Mark steps before current step as completed
        if (stepNumber < currentStep) {
            step.classList.add('completed');
        } else if (stepNumber === currentStep) {
            step.classList.add('active');
            // Remove completed class from current step if it exists
            step.classList.remove('completed');
        } else {
            // Remove completed class from future steps
            step.classList.remove('completed');
        }
    });
    
    // Update step lines to show completion
    document.querySelectorAll('.step-line').forEach((line, index) => {
        // Step lines are between steps, so line index 0 is between step 1 and 2
        if (index < currentStep - 1) {
            line.classList.add('completed');
        } else {
            line.classList.remove('completed');
        }
    });
    
    // Update navigation buttons
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    if (prevBtn) {
        prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-flex';
    }
    
    if (nextBtn) {
        nextBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
    }
    
    // Show review button on last step instead of submit button
    const reviewBtn = document.getElementById('reviewBtn');
    if (reviewBtn) {
        reviewBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ===================================
// Form Validation
// ===================================
function validateStep(step) {
    const formStep = document.querySelector(`.form-step[data-step="${step}"]`);
    if (!formStep) return false;
    
    const requiredFields = formStep.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        const formGroup = field.closest('.form-group');
        const hint = formGroup ? formGroup.querySelector('.form-hint') : null;
        
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            
            // Show error hint
            if (hint) {
                hint.classList.add('show-error');
            }
            
            // Remove error styling on input
            field.addEventListener('input', function() {
                this.classList.remove('error');
                if (hint) {
                    hint.classList.remove('show-error');
                }
            }, { once: true });
        } else {
            // Remove error if field has value
            field.classList.remove('error');
            if (hint) {
                hint.classList.remove('show-error');
            }
        }
    });
    
    if (!isValid) {
        showNotification('Please fill in all required fields', 'error');
        
        // Focus on first invalid field
        const firstInvalid = formStep.querySelector('.error');
        if (firstInvalid) {
            firstInvalid.focus();
        }
    }
    
    return isValid;
}

// ===================================
// Photo Upload
// ===================================
function initializePhotoUpload() {
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const resetPhotoBtn = document.getElementById('resetPhotoBtn');
    
    const defaultImage = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect fill='%2393c5fd' width='200' height='200'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='80' fill='%23ffffff'%3E%F0%9F%91%A4%3C/text%3E%3C/svg%3E";
    
    if (photoInput) {
        photoInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            
            if (file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showNotification('Please upload a valid image file (JPG, PNG, or GIF)', 'error');
                    photoInput.value = '';
                    return;
                }
                
                // Validate file size (1MB = 1048576 bytes)
                if (file.size > 1048576) {
                    showNotification('File size must be less than 1MB', 'error');
                    photoInput.value = '';
                    return;
                }
                
                // Preview image
                const reader = new FileReader();
                reader.onload = (e) => {
                    if (photoPreview) {
                        photoPreview.src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
                
                showNotification('Photo uploaded successfully', 'success');
            }
        });
    }
    
    if (resetPhotoBtn) {
        resetPhotoBtn.addEventListener('click', () => {
            if (photoInput) {
                photoInput.value = '';
            }
            if (photoPreview) {
                photoPreview.src = defaultImage;
            }
            showNotification('Photo reset to default', 'info');
        });
    }
}

// ===================================
// Form Handlers
// ===================================
function calculateAge() {
    const dobInput = document.getElementById('dateOfBirth');
    if (!dobInput || !dobInput.value) return;
    
    const dob = new Date(dobInput.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    
    console.log(`Calculated age: ${age} years`);
    
    // Auto-check senior citizen if age >= 60
    const seniorCitizenSelect = document.getElementById('seniorCitizen');
    if (seniorCitizenSelect && age >= 60) {
        seniorCitizenSelect.value = 'Yes';
    }
}

function handleCivilStatusChange() {
    const civilStatus = document.getElementById('civilStatus').value;
    const spouseNameInput = document.getElementById('spouseName');
    
    if (spouseNameInput) {
        if (civilStatus === 'Married') {
            spouseNameInput.required = true;
            spouseNameInput.parentElement.querySelector('label').innerHTML = 'Spouse Name <span class="required">*</span>';
        } else {
            spouseNameInput.required = false;
            spouseNameInput.parentElement.querySelector('label').innerHTML = 'Spouse Name';
        }
    }
}

function handleVoterStatusChange() {
    const voterStatus = document.getElementById('voterStatus').value;
    const precinctNumberGroup = document.getElementById('precinctNumberGroup');
    const precinctNumberInput = document.getElementById('precinctNumber');
    
    if (precinctNumberGroup) {
        if (voterStatus === 'Yes') {
            precinctNumberGroup.style.display = 'block';
            if (precinctNumberInput) {
                precinctNumberInput.required = true;
            }
        } else {
            precinctNumberGroup.style.display = 'none';
            if (precinctNumberInput) {
                precinctNumberInput.required = false;
                precinctNumberInput.value = ''; // Clear value when hidden
            }
        }
    }
}

function handleFourPsChange() {
    const fourPs = document.getElementById('fourPs').value;
    const fourpsIdGroup = document.getElementById('fourpsIdGroup');
    
    if (fourpsIdGroup) {
        if (fourPs === 'Yes') {
            fourpsIdGroup.style.display = 'block';
        } else {
            fourpsIdGroup.style.display = 'none';
            // Clear the value when hidden
            const fourpsIdInput = document.getElementById('fourpsId');
            if (fourpsIdInput) {
                fourpsIdInput.value = '';
            }
        }
    }
}

function handleSexChange() {
    const sex = document.getElementById('sex').value;
    const wraSection = document.getElementById('wraSection');
    
    if (wraSection) {
        if (sex === 'Female') {
            wraSection.style.display = 'block';
        } else {
            wraSection.style.display = 'none';
            // Clear WRA fields when hidden
            const lmpDate = document.getElementById('lmpDate');
            const usingFpMethod = document.getElementById('usingFpMethod');
            const fpMethodsUsed = document.getElementById('fpMethodsUsed');
            const fpStatus = document.getElementById('fpStatus');
            
            if (lmpDate) lmpDate.value = '';
            if (usingFpMethod) usingFpMethod.value = '';
            if (fpMethodsUsed) fpMethodsUsed.value = '';
            if (fpStatus) fpStatus.value = '';
            
            // Hide FP method fields
            const fpMethodsGroup = document.getElementById('fpMethodsGroup');
            const fpStatusGroup = document.getElementById('fpStatusGroup');
            if (fpMethodsGroup) fpMethodsGroup.style.display = 'none';
            if (fpStatusGroup) fpStatusGroup.style.display = 'none';
        }
    }
}

function handleFpMethodChange() {
    const usingFpMethod = document.getElementById('usingFpMethod').value;
    const fpMethodsGroup = document.getElementById('fpMethodsGroup');
    const fpStatusGroup = document.getElementById('fpStatusGroup');
    
    if (usingFpMethod === 'Yes') {
        if (fpMethodsGroup) fpMethodsGroup.style.display = 'block';
        if (fpStatusGroup) fpStatusGroup.style.display = 'block';
    } else {
        if (fpMethodsGroup) fpMethodsGroup.style.display = 'none';
        if (fpStatusGroup) fpStatusGroup.style.display = 'none';
        
        // Clear values when hidden
        const fpMethodsUsed = document.getElementById('fpMethodsUsed');
        const fpStatus = document.getElementById('fpStatus');
        if (fpMethodsUsed) fpMethodsUsed.value = '';
        if (fpStatus) fpStatus.value = '';
    }
}

// ===================================
// Form Submission
// ===================================
function handleFormSubmit() {
    // Validate final step
    if (!validateStep(currentStep)) {
        return;
    }
    
    const form = document.getElementById('createResidentForm');
    const formData = new FormData(form);
    
    // Add webcam photo if captured
    if (capturedPhotoData && !formData.get('photo')) {
        formData.append('webcam_photo', capturedPhotoData);
    }
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    // Submit to server
    fetch('save_resident.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
        if (data.success) {
            // Clear saved step and form data on successful submission
            clearSavedStep();
            
            // Show success message
            showSuccessMessage(data.message, data.data);
        } else {
            // Show error message
            showNotification(data.message || 'Failed to save resident. Please try again.', 'error');
            console.error('Server error:', data);
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        
        console.error('Error submitting form:', error);
        showNotification('An error occurred while saving. Please check your connection and try again.', 'error');
    });
}

function showSuccessMessage(message = 'Resident Created Successfully!', data = null) {
    const formContainer = document.querySelector('.form-container');
    
    let statusMessage = '';
    if (data && data.verification_status) {
        statusMessage = `<p style="color: #f59e0b; font-weight: 500; margin-top: 10px;">
            <i class="fas fa-clock"></i> Status: ${data.verification_status}
        </p>`;
    }
    
    formContainer.innerHTML = `
        <div class="success-message show">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2>${message}</h2>
            <p>The resident record has been added to the system and is pending verification.</p>
            ${statusMessage}
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 30px;">
                <button class="btn btn-primary" onclick="localStorage.removeItem('createResidentCurrentStep'); localStorage.removeItem('createResidentFormData'); window.location.href='../residents.php'">
                    <i class="fas fa-list"></i>
                    View All Residents
                </button>
                <button class="btn btn-secondary" onclick="localStorage.removeItem('createResidentCurrentStep'); localStorage.removeItem('createResidentFormData'); window.location.reload()">
                    <i class="fas fa-plus"></i>
                    Add Another Resident
                </button>
            </div>
        </div>
    `;
    
    // Hide the review button and navigation buttons
    const reviewBtn = document.getElementById('reviewBtn');
    if (reviewBtn) {
        reviewBtn.style.display = 'none';
    }
    
    const prevBtn = document.getElementById('prevBtn');
    if (prevBtn) {
        prevBtn.style.display = 'none';
    }
    
    const nextBtn = document.getElementById('nextBtn');
    if (nextBtn) {
        nextBtn.style.display = 'none';
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ===================================
// Utility Functions
// ===================================
function showNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.notification').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    let icon = 'info-circle';
    let bgColor = '#3b82f6';
    
    if (type === 'success') {
        icon = 'check-circle';
        bgColor = '#10b981';
    } else if (type === 'error') {
        icon = 'exclamation-circle';
        bgColor = '#ef4444';
    } else if (type === 'warning') {
        icon = 'exclamation-triangle';
        bgColor = '#f59e0b';
    }
    
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
    `;
    
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        z-index: 10000;
        animation: slideIn 0.3s ease;
        max-width: 400px;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// ===================================
// Phone Number Formatting
// ===================================
function formatPhoneNumber(value) {
    // Remove all non-digit characters
    const numbers = value.replace(/\D/g, '');
    
    // Limit to 11 digits (Philippine mobile number format)
    const limited = numbers.substring(0, 11);
    
    // Format as 0912-345-6789
    if (limited.length <= 4) {
        return limited;
    } else if (limited.length <= 7) {
        return limited.substring(0, 4) + '-' + limited.substring(4);
    } else {
        return limited.substring(0, 4) + '-' + limited.substring(4, 7) + '-' + limited.substring(7);
    }
}

function applyPhoneNumberFormatting(input) {
    if (!input) return;
    
    input.addEventListener('input', function(e) {
        const cursorPosition = this.selectionStart;
        const oldValue = this.value;
        const oldLength = oldValue.length;
        
        // Format the value
        const formatted = formatPhoneNumber(this.value);
        this.value = formatted;
        
        // Adjust cursor position after formatting
        const newLength = formatted.length;
        const diff = newLength - oldLength;
        
        // If a hyphen was added right before cursor, move cursor forward
        if (diff > 0 && formatted[cursorPosition] === '-') {
            this.setSelectionRange(cursorPosition + diff, cursorPosition + diff);
        } else {
            this.setSelectionRange(cursorPosition + diff, cursorPosition + diff);
        }
    });
    
    // Prevent non-numeric input on keypress
    input.addEventListener('keypress', function(e) {
        // Allow: backspace, delete, tab, escape, enter
        if ([8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true)) {
            return;
        }
        
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
}

function initializePhoneNumberFormatting() {
    // Apply to mobile number field
    const mobileNumberInput = document.getElementById('mobileNumber');
    applyPhoneNumberFormatting(mobileNumberInput);
}

// ===================================
// Household Information Management
// ===================================

// Store selected household data for review modal
let selectedHouseholdData = null;

function initializeHouseholdInfo() {
    const yesRadio = document.getElementById('householdHeadYes');
    const noRadio = document.getElementById('householdHeadNo');
    const searchBtn = document.getElementById('searchHouseholdBtn');
    const clearBtn = document.getElementById('clearHouseholdBtn');
    const householdSearch = document.getElementById('householdSearch');

    // Toggle panels on radio change
    if (yesRadio) {
        yesRadio.addEventListener('change', function () {
            if (this.checked) {
                document.getElementById('householdYesPanel').style.display = 'block';
                document.getElementById('householdNoPanel').style.display = 'none';
                document.getElementById('householdHeadValue').value = 'Yes';
                // Auto-fill contact and address from Step 2
                autoFillHouseholdContact();
                autoFillHouseholdAddress();
                saveFormData();
            }
        });
    }

    if (noRadio) {
        noRadio.addEventListener('change', function () {
            if (this.checked) {
                document.getElementById('householdYesPanel').style.display = 'none';
                document.getElementById('householdNoPanel').style.display = 'block';
                document.getElementById('householdHeadValue').value = 'No';
                saveFormData();
            }
        });
    }

    // Also auto-fill when mobileNumber or address fields change (in case user goes back)
    const mobileInput = document.getElementById('mobileNumber');
    if (mobileInput) {
        mobileInput.addEventListener('input', function () {
            if (document.getElementById('householdHeadYes') && document.getElementById('householdHeadYes').checked) {
                autoFillHouseholdContact();
            }
        });
    }

    ['houseNo', 'purok', 'streetName'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function () {
                if (document.getElementById('householdHeadYes') && document.getElementById('householdHeadYes').checked) {
                    autoFillHouseholdAddress();
                }
            });
            field.addEventListener('change', function () {
                if (document.getElementById('householdHeadYes') && document.getElementById('householdHeadYes').checked) {
                    autoFillHouseholdAddress();
                }
            });
        }
    });

    // Search button
    if (searchBtn) {
        searchBtn.addEventListener('click', searchHouseholds);
    }

    // Search on Enter key
    if (householdSearch) {
        householdSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchHouseholds();
            }
        });
    }

    // Clear selected household
    if (clearBtn) {
        clearBtn.addEventListener('click', clearSelectedHousehold);
    }
}

function autoFillHouseholdContact() {
    const mobileInput = document.getElementById('mobileNumber');
    const householdContact = document.getElementById('householdContact');
    if (mobileInput && householdContact) {
        householdContact.value = mobileInput.value;
    }
}

function autoFillHouseholdAddress() {
    const houseNo = document.getElementById('houseNo') ? document.getElementById('houseNo').value.trim() : '';
    const purok = document.getElementById('purok') ? document.getElementById('purok').value.trim() : '';
    const streetName = document.getElementById('streetName') ? document.getElementById('streetName').value.trim() : '';

    const parts = [];
    if (houseNo) parts.push('House No. ' + houseNo);
    if (purok) parts.push('Purok ' + purok);
    if (streetName) parts.push(streetName);

    const householdAddress = document.getElementById('householdAddress');
    if (householdAddress) {
        householdAddress.value = parts.join(', ');
    }
}

// Store search results for safe onclick reference
let _householdSearchResults = [];

function searchHouseholds() {
    const searchInput = document.getElementById('householdSearch');
    const resultsContainer = document.getElementById('householdSearchResults');
    const resultsList = document.getElementById('householdResultsList');
    const searchBtn = document.getElementById('searchHouseholdBtn');

    if (!searchInput || !resultsContainer || !resultsList) return;

    const query = searchInput.value.trim();

    // Show loading state
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';

    fetch(`search_households_for_resident.php?search=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i> Search';

            resultsContainer.style.display = 'block';

            if (!data.success || data.data.length === 0) {
                resultsList.innerHTML = `
                    <div style="text-align: center; padding: 20px; color: var(--text-secondary, #64748b);">
                        <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                        No households found. Try a different search term.
                    </div>`;
                return;
            }

            // Store results in module-level variable to avoid JSON-in-onclick issues
            _householdSearchResults = data.data;

            let html = `<p style="font-size: 13px; color: var(--text-secondary, #64748b); margin-bottom: 10px;">
                Found ${data.count} household(s). Click to select:</p>`;

            data.data.forEach((hh, index) => {
                html += `
                    <div class="household-result-item" 
                         onclick="selectHousehold(_householdSearchResults[${index}])"
                         style="background: #fff; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; 
                                padding: 12px 15px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s;"
                         onmouseover="this.style.borderColor='var(--primary-color, #3b82f6)'; this.style.background='#f0f9ff';"
                         onmouseout="this.style.borderColor='var(--border-color, #e2e8f0)'; this.style.background='#fff';">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong style="color: var(--primary-color, #3b82f6);">${hh.household_number}</strong>
                                <span style="margin-left: 10px; color: var(--text-primary, #1e293b); font-weight: 500;">${hh.head_name || 'N/A'}</span>
                            </div>
                            <span style="font-size: 12px; color: var(--text-secondary, #64748b);">
                                ${hh.member_count} member(s)
                            </span>
                        </div>
                        <div style="font-size: 13px; color: var(--text-secondary, #64748b); margin-top: 4px;">
                            <i class="fas fa-map-marker-alt"></i> ${hh.address || 'No address'}
                            ${hh.household_contact ? `&nbsp;&nbsp;<i class="fas fa-phone"></i> ${hh.household_contact}` : ''}
                        </div>
                    </div>`;
            });

            resultsList.innerHTML = html;
        })
        .catch(error => {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i> Search';
            resultsContainer.style.display = 'block';
            resultsList.innerHTML = `<div style="color: #ef4444; padding: 10px;">Error searching households. Please try again.</div>`;
            console.error('Search error:', error);
        });
}

function selectHousehold(hh) {
    selectedHouseholdData = hh;

    // Set hidden input
    const hiddenInput = document.getElementById('selectedHouseholdId');
    if (hiddenInput) hiddenInput.value = hh.id;

    // Hide search results
    const resultsContainer = document.getElementById('householdSearchResults');
    if (resultsContainer) resultsContainer.style.display = 'none';

    // Show selected card
    const selectedCard = document.getElementById('selectedHouseholdCard');
    const selectedInfo = document.getElementById('selectedHouseholdInfo');

    if (selectedCard && selectedInfo) {
        selectedInfo.innerHTML = `
            <div class="form-group">
                <label style="font-size: 12px; color: var(--text-secondary, #64748b); margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Household Number</label>
                <div style="font-weight: 700; color: var(--primary-color, #3b82f6); font-size: 15px;">${hh.household_number}</div>
            </div>
            <div class="form-group">
                <label style="font-size: 12px; color: var(--text-secondary, #64748b); margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Household Head</label>
                <div style="font-weight: 600; font-size: 14px;">${hh.head_name || 'N/A'}</div>
            </div>
            <div class="form-group full-width">
                <label style="font-size: 12px; color: var(--text-secondary, #64748b); margin-bottom: 4px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px;">Address</label>
                <div style="font-size: 13px;">${hh.address || 'N/A'}</div>
            </div>
            <div class="form-group full-width" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--border-color, #e2e8f0);">
                <label for="relationshipToHead" style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">
                    Relationship to Household Head <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" 
                       id="relationshipToHead" 
                       class="form-control" 
                       placeholder="e.g. Son, Daughter, Spouse, Sibling..."
                       oninput="document.getElementById('householdRelationship').value = this.value; saveFormData();"
                       style="max-width: 400px;">
                <small style="color: var(--text-secondary, #64748b); font-size: 12px; margin-top: 4px; display: block;">
                    Enter your relationship to the household head
                </small>
            </div>
        `;
        selectedCard.style.display = 'block';

        // Restore relationship value if previously saved
        const savedRelationship = document.getElementById('householdRelationship');
        if (savedRelationship && savedRelationship.value) {
            const relInput = document.getElementById('relationshipToHead');
            if (relInput) relInput.value = savedRelationship.value;
        }
    }

    saveFormData();
    showNotification(`Household ${hh.household_number} selected`, 'success');
}

function clearSelectedHousehold() {
    selectedHouseholdData = null;

    const hiddenInput = document.getElementById('selectedHouseholdId');
    if (hiddenInput) hiddenInput.value = '';

    const selectedCard = document.getElementById('selectedHouseholdCard');
    if (selectedCard) selectedCard.style.display = 'none';

    const searchInput = document.getElementById('householdSearch');
    if (searchInput) searchInput.value = '';

    const resultsContainer = document.getElementById('householdSearchResults');
    if (resultsContainer) resultsContainer.style.display = 'none';

    saveFormData();
    showNotification('Household selection cleared', 'info');
}

// ===================================
// Webcam Functionality
// ===================================
let webcamActive = false;
let inlineWebcamActive = false;
let capturedPhotoData = null;

function initializeWebcam() {
    // Configure WebcamJS
    Webcam.set({
        width: 640,
        height: 480,
        image_format: 'jpeg',
        jpeg_quality: 90,
        force_flash: false,
        flip_horiz: true,
        fps: 45
    });
    
    console.log('Webcam initialized');
}

// ===================================
// Inline Webcam Functions
// ===================================
function toggleInlineWebcam() {
    if (!inlineWebcamActive) {
        startInlineWebcam();
    } else {
        stopInlineWebcam();
    }
}

function startInlineWebcam() {
    const photoPreview = document.getElementById('photoPreview');
    const inlineWebcamPreview = document.getElementById('inlineWebcamPreview');
    const cameraButtonText = document.getElementById('cameraButtonText');
    const captureInlineBtn = document.getElementById('captureInlineBtn');
    const takePhotoBtn = document.getElementById('takePhotoBtn');
    
    // Hide image, show webcam container
    if (photoPreview) photoPreview.style.display = 'none';
    if (inlineWebcamPreview) inlineWebcamPreview.style.display = 'block';
    
    // Update button text and show capture button
    if (cameraButtonText) cameraButtonText.textContent = 'Stop Camera';
    if (captureInlineBtn) captureInlineBtn.style.display = 'inline-flex';
    if (takePhotoBtn) {
        takePhotoBtn.classList.remove('btn-primary');
        takePhotoBtn.classList.add('btn-secondary');
    }
    
    // Attach webcam to inline preview
    setTimeout(() => {
        Webcam.attach('#inlineWebcamPreview');
        inlineWebcamActive = true;
        console.log('Inline webcam started');
        showNotification('Camera started successfully', 'success');
    }, 100);
}

function stopInlineWebcam() {
    const photoPreview = document.getElementById('photoPreview');
    const inlineWebcamPreview = document.getElementById('inlineWebcamPreview');
    const cameraButtonText = document.getElementById('cameraButtonText');
    const captureInlineBtn = document.getElementById('captureInlineBtn');
    const takePhotoBtn = document.getElementById('takePhotoBtn');
    
    // Stop webcam
    Webcam.reset();
    inlineWebcamActive = false;
    
    // Show image, hide webcam container
    if (photoPreview) photoPreview.style.display = 'block';
    if (inlineWebcamPreview) {
        inlineWebcamPreview.style.display = 'none';
        inlineWebcamPreview.innerHTML = ''; // Clear webcam content
    }
    
    // Update button text and hide capture button
    if (cameraButtonText) cameraButtonText.textContent = 'Start Camera';
    if (captureInlineBtn) captureInlineBtn.style.display = 'none';
    if (takePhotoBtn) {
        takePhotoBtn.classList.remove('btn-secondary');
        takePhotoBtn.classList.add('btn-primary');
    }
    
    console.log('Inline webcam stopped');
    showNotification('Camera stopped', 'info');
}

function captureInlinePhoto() {
    if (!inlineWebcamActive) {
        showNotification('Camera is not active', 'error');
        return;
    }
    
    // Take snapshot
    Webcam.snap(function(data_uri) {
        // Convert data URI to Blob
        fetch(data_uri)
            .then(res => res.blob())
            .then(blob => {
                // Check file size (1MB = 1048576 bytes)
                if (blob.size > 1048576) {
                    showNotification('Photo size exceeds 1MB. Please try again with better lighting.', 'error');
                    return;
                }
                
                // Create a File object from the blob
                const file = new File([blob], 'webcam-photo.jpg', { type: 'image/jpeg' });
                
                // Create a DataTransfer object to set the file input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                
                // Set the file input
                const photoInput = document.getElementById('photoInput');
                if (photoInput) {
                    photoInput.files = dataTransfer.files;
                }
                
                // Update preview
                const photoPreview = document.getElementById('photoPreview');
                if (photoPreview) {
                    photoPreview.src = data_uri;
                }
                
                // Stop webcam
                stopInlineWebcam();
                
                showNotification('Photo captured successfully!', 'success');
                console.log('Inline photo captured and set as profile picture');
            })
            .catch(error => {
                console.error('Error processing webcam photo:', error);
                showNotification('Error processing photo. Please try again.', 'error');
            });
    });
}

function openWebcamModal() {
    const modal = document.getElementById('webcamModal');
    const webcamContainer = document.getElementById('webcamContainer');
    const capturedImageContainer = document.getElementById('capturedImageContainer');
    const webcamInitialActions = document.getElementById('webcamInitialActions');
    const webcamCapturedActions = document.getElementById('webcamCapturedActions');
    
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Reset modal state
        webcamContainer.style.display = 'block';
        capturedImageContainer.style.display = 'none';
        webcamInitialActions.style.display = 'flex';
        webcamCapturedActions.style.display = 'none';
        capturedPhotoData = null;
        
        // Attach webcam to preview container
        setTimeout(() => {
            Webcam.attach('#webcamPreview');
            webcamActive = true;
            console.log('Webcam attached to modal');
        }, 100);
    }
}

function closeWebcamModal() {
    const modal = document.getElementById('webcamModal');
    
    if (modal && webcamActive) {
        // Stop webcam
        Webcam.reset();
        webcamActive = false;
        
        // Hide modal
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset captured photo data
        capturedPhotoData = null;
        
        console.log('Webcam modal closed');
    }
}

function capturePhoto() {
    if (!webcamActive) {
        showNotification('Webcam is not active', 'error');
        return;
    }
    
    // Take snapshot
    Webcam.snap(function(data_uri) {
        capturedPhotoData = data_uri;
        
        // Hide webcam, show captured image
        const webcamContainer = document.getElementById('webcamContainer');
        const capturedImageContainer = document.getElementById('capturedImageContainer');
        const capturedImage = document.getElementById('capturedImage');
        const webcamInitialActions = document.getElementById('webcamInitialActions');
        const webcamCapturedActions = document.getElementById('webcamCapturedActions');
        
        if (capturedImage) {
            capturedImage.src = data_uri;
        }
        
        if (webcamContainer) webcamContainer.style.display = 'none';
        if (capturedImageContainer) capturedImageContainer.style.display = 'block';
        if (webcamInitialActions) webcamInitialActions.style.display = 'none';
        if (webcamCapturedActions) webcamCapturedActions.style.display = 'flex';
        
        // Freeze webcam
        Webcam.freeze();
        
        console.log('Photo captured');
        showNotification('Photo captured successfully!', 'success');
    });
}

function retakePhoto() {
    const webcamContainer = document.getElementById('webcamContainer');
    const capturedImageContainer = document.getElementById('capturedImageContainer');
    const webcamInitialActions = document.getElementById('webcamInitialActions');
    const webcamCapturedActions = document.getElementById('webcamCapturedActions');
    
    // Show webcam, hide captured image
    if (webcamContainer) webcamContainer.style.display = 'block';
    if (capturedImageContainer) capturedImageContainer.style.display = 'none';
    if (webcamInitialActions) webcamInitialActions.style.display = 'flex';
    if (webcamCapturedActions) webcamCapturedActions.style.display = 'none';
    
    // Unfreeze webcam
    Webcam.unfreeze();
    
    // Reset captured photo data
    capturedPhotoData = null;
    
    console.log('Retaking photo');
}

function useWebcamPhoto() {
    if (!capturedPhotoData) {
        showNotification('No photo captured', 'error');
        return;
    }
    
    // Convert data URI to Blob
    fetch(capturedPhotoData)
        .then(res => res.blob())
        .then(blob => {
            // Check file size (1MB = 1048576 bytes)
            if (blob.size > 1048576) {
                showNotification('Photo size exceeds 1MB. Please try again with better lighting or lower quality.', 'error');
                return;
            }
            
            // Create a File object from the blob
            const file = new File([blob], 'webcam-photo.jpg', { type: 'image/jpeg' });
            
            // Create a DataTransfer object to set the file input
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            
            // Set the file input
            const photoInput = document.getElementById('photoInput');
            if (photoInput) {
                photoInput.files = dataTransfer.files;
            }
            
            // Update preview
            const photoPreview = document.getElementById('photoPreview');
            if (photoPreview) {
                photoPreview.src = capturedPhotoData;
            }
            
            // Close modal
            closeWebcamModal();
            
            showNotification('Photo added successfully!', 'success');
            console.log('Webcam photo set as profile picture');
        })
        .catch(error => {
            console.error('Error processing webcam photo:', error);
            showNotification('Error processing photo. Please try again.', 'error');
        });
}

// Close modal when clicking outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('webcamModal');
    if (e.target === modal) {
        closeWebcamModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const modal = document.getElementById('webcamModal');
        if (modal && modal.style.display === 'flex') {
            closeWebcamModal();
        }
    }
});

// ===================================
// Keyboard Navigation
// ===================================
// Add keyboard navigation
document.addEventListener('keydown', (e) => {
    // Alt + Right Arrow = Next
    if (e.altKey && e.key === 'ArrowRight') {
        e.preventDefault();
        const nextBtn = document.getElementById('nextBtn');
        if (nextBtn && nextBtn.style.display !== 'none') {
            nextBtn.click();
        }
    }
    
    // Alt + Left Arrow = Previous
    if (e.altKey && e.key === 'ArrowLeft') {
        e.preventDefault();
        const prevBtn = document.getElementById('prevBtn');
        if (prevBtn && prevBtn.style.display !== 'none') {
            prevBtn.click();
        }
    }
});

// ===================================
// Review Modal Functions
// ===================================
function openReviewModal() {
    // Validate the last step before opening review
    if (!validateStep(currentStep)) {
        return;
    }
    
    const modal = document.getElementById('reviewModal');
    if (modal) {
        // Populate the review modal with form data
        populateReviewModal();
        
        // Reset checkbox and disable submit button
        resetConfirmationCheckbox();
        
        // Initialize confirmation checkbox event listener
        initializeConfirmationCheckbox();
        
        // Show modal
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        console.log('Review modal opened');
    }
}

function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset checkbox when closing modal
        resetConfirmationCheckbox();
        
        console.log('Review modal closed');
    }
}

// ===================================
// Confirmation Checkbox Functions
// ===================================
function resetConfirmationCheckbox() {
    const checkbox = document.getElementById('confirmDetailsCheckbox');
    const submitBtn = document.getElementById('finalSubmitBtn');
    
    if (checkbox) {
        checkbox.checked = false;
    }
    
    if (submitBtn) {
        submitBtn.disabled = true;
    }
}

function initializeConfirmationCheckbox() {
    // Use setTimeout to ensure DOM is fully ready
    setTimeout(() => {
        const checkbox = document.getElementById('confirmDetailsCheckbox');
        const submitBtn = document.getElementById('finalSubmitBtn');
        
        console.log('=== CHECKBOX INITIALIZATION ===');
        console.log('Checkbox found:', !!checkbox);
        console.log('Submit button found:', !!submitBtn);
        
        if (checkbox && submitBtn) {
            // Add event listener directly to checkbox
            checkbox.addEventListener('change', function(e) {
                console.log('=== CHECKBOX CHANGED ===');
                console.log('Checked:', this.checked);
                console.log('Button disabled before:', submitBtn.disabled);
                
                if (this.checked) {
                    submitBtn.disabled = false;
                    submitBtn.removeAttribute('disabled');
                    console.log('Button disabled after enable:', submitBtn.disabled);
                    console.log('Button should now be ENABLED');
                } else {
                    submitBtn.disabled = true;
                    submitBtn.setAttribute('disabled', 'disabled');
                    console.log('Button disabled after disable:', submitBtn.disabled);
                    console.log('Button should now be DISABLED');
                }
            });
            
            console.log('Event listener attached successfully!');
        } else {
            console.error('FAILED: Elements not found!');
        }
    }, 100);
}

function populateReviewModal() {
    const form = document.getElementById('createResidentForm');
    if (!form) return;
    
    // Helper function to get form value
    const getValue = (name) => {
        const element = form.querySelector(`[name="${name}"]`);
        return element ? element.value : '';
    };
    
    // Helper function to create field display
    const createField = (label, value) => {
        if (!value || value.trim() === '') return '';
        return `
            <div class="review-field">
                <div class="review-field-label">${label}</div>
                <div class="review-field-value">${value}</div>
            </div>
        `;
    };
    
    // 1. Personal Information
    let personalInfoHTML = '';
    
    // Add photo if available
    const photoPreview = document.getElementById('photoPreview');
    if (photoPreview && photoPreview.src && !photoPreview.src.includes('data:image/svg+xml')) {
        personalInfoHTML += `
            <div class="review-photo">
                <img src="${photoPreview.src}" alt="Resident Photo">
            </div>
        `;
    }
    
    personalInfoHTML += '<div class="review-fields-grid">';
    personalInfoHTML += createField('First Name', getValue('firstName'));
    personalInfoHTML += createField('Middle Name', getValue('middleName'));
    personalInfoHTML += createField('Last Name', getValue('lastName'));
    personalInfoHTML += createField('Suffix', getValue('suffix'));
    personalInfoHTML += createField('Sex', getValue('sex'));
    personalInfoHTML += createField('Date of Birth', getValue('dateOfBirth'));
    personalInfoHTML += createField('Religion', getValue('religion'));
    personalInfoHTML += createField('Ethnicity', getValue('ethnicity'));
    personalInfoHTML += '</div>';
    
    document.getElementById('reviewPersonalInfo').innerHTML = personalInfoHTML;
    
    // 2. Contact Information
    let contactInfoHTML = '<div class="review-fields-grid">';
    contactInfoHTML += createField('Mobile Number', getValue('mobileNumber'));
    contactInfoHTML += createField('Email Address', getValue('email'));
    
    // Construct address for review
    const houseNo = getValue('houseNo');
    const purok = getValue('purok');
    const streetName = getValue('streetName');
    const addressParts = [houseNo ? `House No. ${houseNo}` : '', purok ? `Purok ${purok}` : '', streetName].filter(Boolean);
    const fullAddress = addressParts.join(', ');

    contactInfoHTML += createField('Complete Address', fullAddress);
    contactInfoHTML += '</div>';
    
    document.getElementById('reviewContactInfo').innerHTML = contactInfoHTML;
    
    // 3. Family Information
    let familyInfoHTML = '<div class="review-fields-grid">';
    familyInfoHTML += createField('Civil Status', getValue('civilStatus'));
    familyInfoHTML += createField('Spouse Name', getValue('spouseName'));
    familyInfoHTML += createField("Father's Name", getValue('fatherName'));
    familyInfoHTML += createField("Mother's Name", getValue('motherName'));
    familyInfoHTML += createField('Number of Children', getValue('numberOfChildren'));
    familyInfoHTML += '</div>';
    
    document.getElementById('reviewFamilyInfo').innerHTML = familyInfoHTML;
    
    // 4. Household Information
    let householdHTML = '<div class="review-fields-grid">';
    const householdHeadValue = getValue('householdHeadValue');
    householdHTML += createField('Household Head?', householdHeadValue || 'Not specified');

    if (householdHeadValue === 'Yes') {
        householdHTML += createField('Household Number', getValue('householdNumber'));
        householdHTML += createField('Household Contact', getValue('householdContact'));
        householdHTML += createField('Household Address', getValue('householdAddress'));
        householdHTML += createField('Water Source Type', getValue('waterSourceType'));
        householdHTML += createField('Toilet Facility Type', getValue('toiletFacilityType'));
    } else if (householdHeadValue === 'No' && selectedHouseholdData) {
        householdHTML += createField('Selected Household', selectedHouseholdData.household_number);
        householdHTML += createField('Household Head', selectedHouseholdData.head_name || 'N/A');
        householdHTML += createField('Address', selectedHouseholdData.address || 'N/A');
        householdHTML += createField('Contact', selectedHouseholdData.household_contact || 'N/A');
        householdHTML += createField('Relationship to Head', getValue('householdRelationship'));
        if (selectedHouseholdData.water_source_type) {
            householdHTML += createField('Water Source', selectedHouseholdData.water_source_type);
        }
        if (selectedHouseholdData.toilet_facility_type) {
            householdHTML += createField('Toilet Facility', selectedHouseholdData.toilet_facility_type);
        }
    } else if (householdHeadValue === 'No') {
        householdHTML += '<div class="review-field"><div class="review-field-value" style="color: #f59e0b;"><i class="fas fa-exclamation-triangle"></i> No household selected</div></div>';
    }

    householdHTML += '</div>';
    document.getElementById('reviewHouseholdInfo').innerHTML = householdHTML;

    // 5. Education & Employment
    let educationHTML = '<div class="review-fields-grid">';
    educationHTML += createField('Educational Attainment', getValue('educationalAttainment'));
    educationHTML += createField('Employment Status', getValue('employmentStatus'));
    educationHTML += createField('Occupation', getValue('occupation'));
    educationHTML += createField('Monthly Income', getValue('monthlyIncome'));
    educationHTML += '</div>';
    
    document.getElementById('reviewEducationEmployment').innerHTML = educationHTML;
    
    // 6. Additional Information
    let additionalHTML = '';
    
    // Government Programs
    additionalHTML += '<h5 style="margin: 0 0 15px 0; color: var(--primary-color);"><i class="fas fa-landmark"></i> Government Programs</h5>';
    additionalHTML += '<div class="review-fields-grid">';
    additionalHTML += createField('4Ps Member', getValue('fourPs'));
    additionalHTML += createField('4Ps ID Number', getValue('fourpsId'));
    additionalHTML += createField('Voter Status', getValue('voterStatus'));
    additionalHTML += createField('Precinct Number', getValue('precinctNumber'));
    additionalHTML += '</div>';
    
    // Health Information
    additionalHTML += '<h5 style="margin: 20px 0 15px 0; color: var(--primary-color);"><i class="fas fa-heartbeat"></i> Health Information</h5>';
    additionalHTML += '<div class="review-fields-grid">';
    additionalHTML += createField('Philhealth ID', getValue('philhealthId'));
    additionalHTML += createField('Membership Type', getValue('membershipType'));
    additionalHTML += createField('Philhealth Category', getValue('philhealthCategory'));
    additionalHTML += createField('Age/Health Group', getValue('ageHealthGroup'));
    additionalHTML += createField('Medical History', getValue('medicalHistory'));
    additionalHTML += '</div>';
    
    // Women's Reproductive Health (if applicable)
    const sex = getValue('sex');
    if (sex === 'Female') {
        const lmpDate = getValue('lmpDate');
        const usingFpMethod = getValue('usingFpMethod');
        const fpMethodsUsed = getValue('fpMethodsUsed');
        const fpStatus = getValue('fpStatus');
        
        if (lmpDate || usingFpMethod || fpMethodsUsed || fpStatus) {
            additionalHTML += '<h5 style="margin: 20px 0 15px 0; color: var(--primary-color);"><i class="fas fa-female"></i> Women\'s Reproductive Health</h5>';
            additionalHTML += '<div class="review-fields-grid">';
            additionalHTML += createField('Last Menstrual Period', lmpDate);
            additionalHTML += createField('Using FP Method', usingFpMethod);
            additionalHTML += createField('FP Methods Used', fpMethodsUsed);
            additionalHTML += createField('FP Status', fpStatus);
            additionalHTML += '</div>';
        }
    }
    
    // Remarks
    const remarks = getValue('remarks');
    if (remarks) {
        additionalHTML += '<h5 style="margin: 20px 0 15px 0; color: var(--primary-color);"><i class="fas fa-sticky-note"></i> Additional Notes</h5>';
        additionalHTML += '<div class="review-fields-grid">';
        additionalHTML += createField('Remarks', remarks);
        additionalHTML += '</div>';
    }
    
    document.getElementById('reviewAdditionalInfo').innerHTML = additionalHTML;
}

function submitFormFromReview() {
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    const originalBtnText = finalSubmitBtn.innerHTML;
    
    // Show loading state
    finalSubmitBtn.disabled = true;
    finalSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    const form = document.getElementById('createResidentForm');
    const formData = new FormData(form);
    
    // Add webcam photo if captured
    if (capturedPhotoData && !formData.get('photo')) {
        formData.append('webcam_photo', capturedPhotoData);
    }
    
    // Submit to server
    fetch('save_resident.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        finalSubmitBtn.disabled = false;
        finalSubmitBtn.innerHTML = originalBtnText;
        
        if (data.success) {
            // Close review modal
            closeReviewModal();
            
            // Clear saved step and form data on successful submission
            clearSavedStep();
            
            // Show success message
            showSuccessMessage(data.message, data.data);
        } else {
            // Show error message
            showNotification(data.message || 'Failed to save resident. Please try again.', 'error');
            console.error('Server error:', data);
        }
    })
    .catch(error => {
        finalSubmitBtn.disabled = false;
        finalSubmitBtn.innerHTML = originalBtnText;
        
        console.error('Error submitting form:', error);
        showNotification('An error occurred while saving. Please check your connection and try again.', 'error');
    });
}

// Close review modal when clicking outside
document.addEventListener('click', (e) => {
    const reviewModal = document.getElementById('reviewModal');
    if (e.target === reviewModal) {
        closeReviewModal();
    }
});

// Close review modal on Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const reviewModal = document.getElementById('reviewModal');
        if (reviewModal && reviewModal.style.display === 'flex') {
            closeReviewModal();
        }
    }
});

// Add animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
`;
document.head.appendChild(style);
