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
    
    // Initialize emergency contacts functionality
    initializeEmergencyContacts();
    
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
                <button class="btn btn-primary" onclick="localStorage.removeItem('createResidentCurrentStep'); localStorage.removeItem('createResidentFormData'); window.location.href='residents.php'">
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
// Emergency Contacts Management
// ===================================
let contactCount = 1;
const MAX_CONTACTS = 3;

function initializeEmergencyContacts() {
    const addContactBtn = document.getElementById('addContactBtn');
    
    if (addContactBtn) {
        addContactBtn.addEventListener('click', addEmergencyContact);
    }
}

function addEmergencyContact() {
    if (contactCount >= MAX_CONTACTS) {
        showNotification(`Maximum of ${MAX_CONTACTS} emergency contacts allowed`, 'warning');
        return;
    }
    
    contactCount++;
    const container = document.getElementById('emergencyContactsContainer');
    
    const contactHTML = `
        <div class="emergency-contact-item" data-contact-index="${contactCount}">
            <div class="contact-item-header">
                <h6 style="margin: 0; color: var(--text-primary); font-size: 16px; font-weight: 600;">
                    <i class="fas fa-user-circle"></i> Contact Person ${contactCount}
                </h6>
                <button type="button" class="btn-remove-contact" onclick="removeEmergencyContact(${contactCount})">
                    <i class="fas fa-trash"></i>
                    Remove
                </button>
            </div>
            <div class="form-grid" style="margin-top: 15px;">
                <div class="form-group">
                    <label>Contact Person Name <span class="required">*</span></label>
                    <input type="text" name="emergencyContactName_${contactCount}" class="form-control" required placeholder="Enter Contact Person Name">
                    <small class="form-hint">Contact person name is required</small>
                </div>
                
                <div class="form-group">
                    <label>Relationship <span class="required">*</span></label>
                    <input type="text" name="emergencyRelationship_${contactCount}" class="form-control" required placeholder="Enter Relationship">
                    <small class="form-hint">Relationship is required</small>
                </div>
                
                <div class="form-group">
                    <label>Contact Number <span class="required">*</span></label>
                    <input type="tel" name="emergencyContactNumber_${contactCount}" class="form-control" required placeholder="+63 XXX XXX XXXX">
                    <small class="form-hint">Contact number is required</small>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="emergencyAddress_${contactCount}" class="form-control" placeholder="Enter Address">
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', contactHTML);
    
    // Update button state
    updateAddContactButton();
    
    // Save form data
    saveFormData();
    
    showNotification(`Contact Person ${contactCount} added successfully`, 'success');
}

function removeEmergencyContact(index) {
    const contactItem = document.querySelector(`.emergency-contact-item[data-contact-index="${index}"]`);
    
    if (contactItem) {
        // Add fade out animation
        contactItem.style.animation = 'fadeOut 0.3s ease';
        
        setTimeout(() => {
            contactItem.remove();
            contactCount--;
            
            // Renumber remaining contacts
            renumberContacts();
            
            // Update button state
            updateAddContactButton();
            
            // Save form data
            saveFormData();
            
            showNotification('Contact removed successfully', 'info');
        }, 300);
    }
}

function renumberContacts() {
    const contacts = document.querySelectorAll('.emergency-contact-item');
    contactCount = 0;
    
    contacts.forEach((contact, index) => {
        contactCount++;
        const newIndex = index + 1;
        
        // Update data attribute
        contact.setAttribute('data-contact-index', newIndex);
        
        // Update header
        const header = contact.querySelector('.contact-item-header h6');
        if (header) {
            header.innerHTML = `<i class="fas fa-user-circle"></i> Contact Person ${newIndex}`;
        }
        
        // Update remove button
        const removeBtn = contact.querySelector('.btn-remove-contact');
        if (removeBtn) {
            removeBtn.setAttribute('onclick', `removeEmergencyContact(${newIndex})`);
        }
        
        // Update input names
        const inputs = contact.querySelectorAll('input');
        inputs.forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                const baseName = name.replace(/_\d+$/, '');
                input.setAttribute('name', `${baseName}_${newIndex}`);
            }
        });
    });
}

function updateAddContactButton() {
    const addContactBtn = document.getElementById('addContactBtn');
    
    if (addContactBtn) {
        if (contactCount >= MAX_CONTACTS) {
            addContactBtn.disabled = true;
            addContactBtn.style.opacity = '0.5';
            addContactBtn.style.cursor = 'not-allowed';
        } else {
            addContactBtn.disabled = false;
            addContactBtn.style.opacity = '1';
            addContactBtn.style.cursor = 'pointer';
        }
    }
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
        console.log('Review modal closed');
    }
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
    contactInfoHTML += createField('Complete Address', getValue('currentAddress'));
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
    
    // 4. Emergency Contacts
    let emergencyHTML = '';
    const emergencyContacts = document.querySelectorAll('.emergency-contact-item');
    
    emergencyContacts.forEach((contact, index) => {
        const contactIndex = index + 1;
        const name = getValue(`emergencyContactName_${contactIndex}`);
        const relationship = getValue(`emergencyRelationship_${contactIndex}`);
        const number = getValue(`emergencyContactNumber_${contactIndex}`);
        const address = getValue(`emergencyAddress_${contactIndex}`);
        
        if (name || relationship || number) {
            emergencyHTML += `
                <div class="review-emergency-contact">
                    <h5><i class="fas fa-user-circle"></i> Contact Person ${contactIndex}</h5>
                    <div class="review-fields-grid">
                        ${createField('Name', name)}
                        ${createField('Relationship', relationship)}
                        ${createField('Contact Number', number)}
                        ${createField('Address', address)}
                    </div>
                </div>
            `;
        }
    });
    
    if (!emergencyHTML) {
        emergencyHTML = '<p class="review-no-data">No emergency contacts added</p>';
    }
    
    document.getElementById('reviewEmergencyContact').innerHTML = emergencyHTML;
    
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
