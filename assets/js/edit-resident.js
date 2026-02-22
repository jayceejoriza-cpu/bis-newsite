// ============================================
// Edit Resident JavaScript
// ============================================

// Global variables
let currentStep = 1;
const totalSteps = 6;
let emergencyContactCount = 0;
let residentData = null;

// ============================================
// Initialize on page load
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Get resident ID from hidden input
    const residentId = document.getElementById('residentId').value;
    
    // Load resident data
    loadResidentData(residentId);
    
    // Initialize form navigation
    initializeFormNavigation();
    
    // Initialize conditional fields
    initializeConditionalFields();
    
    // Initialize photo upload
    initializePhotoUpload();
    
    // Initialize emergency contacts
    initializeEmergencyContacts();
    
    // Initialize phone number formatting
    initializePhoneNumberFormatting();
});

// ============================================
// Load Resident Data
// ============================================
function loadResidentData(residentId) {
    // Show loading state
    showLoadingState();
    
    fetch(`get_resident_details.php?id=${residentId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                residentData = result.data;
                populateForm(residentData);
                hideLoadingState();
            } else {
                showError('Failed to load resident data: ' + result.message);
                setTimeout(() => {
                    window.location.href = '../residents.php';
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error loading resident data:', error);
            showError('An error occurred while loading resident data');
            setTimeout(() => {
                window.location.href = '../residents.php';
            }, 2000);
        });
}

// ============================================
// Populate Form with Resident Data
// ============================================
function populateForm(data) {
    // Step 1: Personal Details
    document.getElementById('displayResidentId').value = data.resident_id || 'Auto-generated';
    document.getElementById('firstName').value = data.first_name || '';
    document.getElementById('middleName').value = data.middle_name || '';
    document.getElementById('lastName').value = data.last_name || '';
    document.getElementById('suffix').value = data.suffix || '';
    document.getElementById('sex').value = data.sex || '';
    document.getElementById('dateOfBirth').value = data.date_of_birth || '';
    document.getElementById('religion').value = data.religion || '';
    document.getElementById('ethnicity').value = data.ethnicity || '';
    
    // Status fields
    document.getElementById('verificationStatus').value = data.verification_status || 'Pending';
    document.getElementById('activityStatus').value = data.activity_status || 'Active';
    document.getElementById('rejectionReason').value = data.rejection_reason || '';
    document.getElementById('statusRemarks').value = data.status_remarks || '';
    
    // Show/hide rejection reason based on verification status
    if (data.verification_status === 'Rejected') {
        document.getElementById('rejectionReasonGroup').style.display = 'block';
    }
    
    // Photo
    if (data.photo) {
        document.getElementById('photoPreview').src = data.photo;
        document.getElementById('existingPhoto').value = data.photo;
    }
    
    // Step 2: Contact Information
    document.getElementById('mobileNumber').value = data.mobile_number || '';
    document.getElementById('email').value = data.email || '';
    document.getElementById('currentAddress').value = data.current_address || '';
    
    // Step 3: Family Information
    document.getElementById('civilStatus').value = data.civil_status || '';
    document.getElementById('spouseName').value = data.spouse_name || '';
    document.getElementById('fatherName').value = data.father_name || '';
    document.getElementById('motherName').value = data.mother_name || '';
    document.getElementById('numberOfChildren').value = data.number_of_children || 0;
    
    // Step 4: Emergency Contacts
    if (data.emergency_contacts_list && data.emergency_contacts_list.length > 0) {
        populateEmergencyContacts(data.emergency_contacts_list);
    } else {
        // Add default empty contact
        addEmergencyContact();
    }
    
    // Step 5: Education & Employment
    document.getElementById('educationalAttainment').value = data.educational_attainment || '';
    document.getElementById('employmentStatus').value = data.employment_status || '';
    document.getElementById('occupation').value = data.occupation || '';
    document.getElementById('monthlyIncome').value = data.monthly_income || '';
    
    // Step 6: Additional Information
    document.getElementById('fourPs').value = data.fourps_member || 'No';
    document.getElementById('fourpsId').value = data.fourps_id || '';
    document.getElementById('voterStatus').value = data.voter_status || '';
    document.getElementById('precinctNumber').value = data.precinct_number || '';
    
    // Show/hide conditional fields
    if (data.fourps_member === 'Yes') {
        document.getElementById('fourpsIdGroup').style.display = 'block';
    }
    if (data.voter_status === 'Yes') {
        document.getElementById('precinctNumberGroup').style.display = 'block';
    }
    
    // Health Information
    document.getElementById('philhealthId').value = data.philhealth_id || '';
    document.getElementById('membershipType').value = data.membership_type || '';
    document.getElementById('philhealthCategory').value = data.philhealth_category || '';
    document.getElementById('ageHealthGroup').value = data.age_health_group || '';
    document.getElementById('medicalHistory').value = data.medical_history || '';
    
    // Women's Reproductive Health (WRA)
    if (data.sex === 'Female') {
        document.getElementById('wraSection').style.display = 'block';
        document.getElementById('lmpDate').value = data.lmp_date || '';
        document.getElementById('usingFpMethod').value = data.using_fp_method || '';
        document.getElementById('fpMethodsUsed').value = data.fp_methods_used || '';
        document.getElementById('fpStatus').value = data.fp_status || '';
        
        // Show/hide FP fields
        if (data.using_fp_method === 'Yes') {
            document.getElementById('fpMethodsGroup').style.display = 'block';
            document.getElementById('fpStatusGroup').style.display = 'block';
        }
    }
    
    // Remarks
    document.getElementById('remarks').value = data.remarks || '';
}

// ============================================
// Populate Emergency Contacts
// ============================================
function populateEmergencyContacts(contacts) {
    const container = document.getElementById('emergencyContactsContainer');
    container.innerHTML = '';
    emergencyContactCount = 0;
    
    contacts.forEach((contact, index) => {
        emergencyContactCount++;
        const contactHtml = `
            <div class="emergency-contact-item" data-contact-index="${emergencyContactCount}">
                <div class="contact-item-header">
                    <h6 style="margin: 0; color: var(--text-primary); font-size: 16px; font-weight: 600;">
                        <i class="fas fa-user-circle"></i> Contact Person ${emergencyContactCount}
                    </h6>
                    ${emergencyContactCount > 1 ? `<button type="button" class="btn btn-danger btn-sm" onclick="removeEmergencyContact(${emergencyContactCount})">
                        <i class="fas fa-trash"></i> Remove
                    </button>` : ''}
                </div>
                <div class="form-grid" style="margin-top: 15px; margin-bottom: 15px">
                    <div class="form-group">
                        <label>Contact Person Name <span class="required">*</span></label>
                        <input type="text" name="emergencyContactName_${emergencyContactCount}" class="form-control" required placeholder="Enter Contact Person Name" value="${contact.name || ''}">
                        <small class="form-hint">Contact person name is required</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Relationship <span class="required">*</span></label>
                        <input type="text" name="emergencyRelationship_${emergencyContactCount}" class="form-control" required placeholder="Enter Relationship" value="${contact.relationship || ''}">
                        <small class="form-hint">Relationship is required</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="tel" name="emergencyContactNumber_${emergencyContactCount}" class="form-control" placeholder="+63 XXX XXX XXXX" value="${contact.contact_number || ''}">
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="emergencyAddress_${emergencyContactCount}" class="form-control" placeholder="Enter Address" value="${contact.address || ''}">
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', contactHtml);
        
        // Apply phone number formatting to the contact number field
        const contactNumberInput = document.querySelector(`input[name="emergencyContactNumber_${emergencyContactCount}"]`);
        applyPhoneNumberFormatting(contactNumberInput);
    });
}

// ============================================
// Initialize Form Navigation
// ============================================
function initializeFormNavigation() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const reviewBtn = document.getElementById('reviewBtn');
    
    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });
    
    nextBtn.addEventListener('click', () => {
        if (validateStep(currentStep)) {
            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        }
    });
}

// ============================================
// Show Step
// ============================================
function showStep(step) {
    // Hide all steps
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
    
    // Show current step
    document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');
    document.querySelector(`.step[data-step="${step}"]`).classList.add('active');
    
    // Update navigation buttons
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const reviewBtn = document.getElementById('reviewBtn');
    
    prevBtn.style.display = step === 1 ? 'none' : 'flex';
    nextBtn.style.display = step === totalSteps ? 'none' : 'flex';
    reviewBtn.style.display = step === totalSteps ? 'flex' : 'none';
    
    currentStep = step;
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================
// Validate Step
// ============================================
function validateStep(step) {
    const currentStepElement = document.querySelector(`.form-step[data-step="${step}"]`);
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    
    if (!isValid) {
        showError('Please fill in all required fields');
    }
    
    return isValid;
}

// ============================================
// Initialize Conditional Fields
// ============================================
function initializeConditionalFields() {
    // Verification Status - Show/hide rejection reason
    document.getElementById('verificationStatus').addEventListener('change', function() {
        const rejectionGroup = document.getElementById('rejectionReasonGroup');
        if (this.value === 'Rejected') {
            rejectionGroup.style.display = 'block';
        } else {
            rejectionGroup.style.display = 'none';
            document.getElementById('rejectionReason').value = '';
        }
    });
    
    // Sex - Show/hide WRA section
    document.getElementById('sex').addEventListener('change', function() {
        const wraSection = document.getElementById('wraSection');
        if (this.value === 'Female') {
            wraSection.style.display = 'block';
        } else {
            wraSection.style.display = 'none';
        }
    });
    
    // 4Ps Member
    document.getElementById('fourPs').addEventListener('change', function() {
        const fourpsIdGroup = document.getElementById('fourpsIdGroup');
        if (this.value === 'Yes') {
            fourpsIdGroup.style.display = 'block';
        } else {
            fourpsIdGroup.style.display = 'none';
            document.getElementById('fourpsId').value = '';
        }
    });
    
    // Voter Status
    document.getElementById('voterStatus').addEventListener('change', function() {
        const precinctGroup = document.getElementById('precinctNumberGroup');
        if (this.value === 'Yes') {
            precinctGroup.style.display = 'block';
        } else {
            precinctGroup.style.display = 'none';
            document.getElementById('precinctNumber').value = '';
        }
    });
    
    // Using FP Method
    document.getElementById('usingFpMethod').addEventListener('change', function() {
        const fpMethodsGroup = document.getElementById('fpMethodsGroup');
        const fpStatusGroup = document.getElementById('fpStatusGroup');
        if (this.value === 'Yes') {
            fpMethodsGroup.style.display = 'block';
            fpStatusGroup.style.display = 'block';
        } else {
            fpMethodsGroup.style.display = 'none';
            fpStatusGroup.style.display = 'none';
            document.getElementById('fpMethodsUsed').value = '';
            document.getElementById('fpStatus').value = '';
        }
    });
}

// ============================================
// Initialize Photo Upload
// ============================================
function initializePhotoUpload() {
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const resetPhotoBtn = document.getElementById('resetPhotoBtn');
    
    photoInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                photoPreview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
    
    resetPhotoBtn.addEventListener('click', function() {
        photoInput.value = '';
        // Reset to existing photo or default
        const existingPhoto = document.getElementById('existingPhoto').value;
        if (existingPhoto) {
            photoPreview.src = existingPhoto;
        } else {
            photoPreview.src = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect fill='%2393c5fd' width='200' height='200'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='80' fill='%23ffffff'%3E%F0%9F%91%A4%3C/text%3E%3C/svg%3E";
        }
    });
}

// ============================================
// Phone Number Formatting
// ============================================
function formatPhoneNumber(value) {
    // Remove all non-digit characters
    const numbers = value.replace(/\D/g, '');
    
    // Limit to 10 digits (Philippine mobile number format without 0 prefix if +63 is used)
    const limited = numbers.substring(0, 10);
    
    // Format as 912 345 6789
    if (limited.length <= 3) {
        return limited;
    } else if (limited.length <= 6) {
        return limited.substring(0, 3) + ' ' + limited.substring(3);
    } else {
        return limited.substring(0, 3) + ' ' + limited.substring(3, 6) + ' ' + limited.substring(6);
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
        
        // If a space was added right before cursor, move cursor forward
        if (diff > 0 && formatted[cursorPosition] === ' ') {
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
    
    // Apply to existing emergency contact number fields
    document.querySelectorAll('input[name^="emergencyContactNumber_"]').forEach(input => {
        applyPhoneNumberFormatting(input);
    });
}

// ============================================
// Initialize Emergency Contacts
// ============================================
function initializeEmergencyContacts() {
    document.getElementById('addContactBtn').addEventListener('click', addEmergencyContact);
}

// ============================================
// Add Emergency Contact
// ============================================
function addEmergencyContact() {
    emergencyContactCount++;
    const container = document.getElementById('emergencyContactsContainer');
    
    const contactHtml = `
        <div class="emergency-contact-item" data-contact-index="${emergencyContactCount}">
            <div class="contact-item-header">
                <h6 style="margin: 0; color: var(--text-primary); font-size: 16px; font-weight: 600;">
                    <i class="fas fa-user-circle"></i> Contact Person ${emergencyContactCount}
                </h6>
                ${emergencyContactCount > 1 ? `<button type="button" class="btn btn-danger btn-sm" onclick="removeEmergencyContact(${emergencyContactCount})">
                    <i class="fas fa-trash"></i> Remove
                </button>` : ''}
            </div>
            <div class="form-grid" style="margin-top: 15px; margin-bottom: 15px">
                <div class="form-group">
                    <label>Contact Person Name</label>
                    <input type="text" name="emergencyContactName_${emergencyContactCount}" class="form-control"  placeholder="Enter Contact Person Name">
                    
                </div>
                
                <div class="form-group">
                    <label>Relationship</label>
                    <input type="text" name="emergencyRelationship_${emergencyContactCount}" class="form-control"  placeholder="Enter Relationship">

                </div>
                
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="tel" name="emergencyContactNumber_${emergencyContactCount}" class="form-control" placeholder="+63 XXX XXX XXXX">
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="emergencyAddress_${emergencyContactCount}" class="form-control" placeholder="Enter Address">
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', contactHtml);
    
    // Apply phone number formatting to the newly added contact number field
    const newContactNumberInput = document.querySelector(`input[name="emergencyContactNumber_${emergencyContactCount}"]`);
    applyPhoneNumberFormatting(newContactNumberInput);
}

// ============================================
// Remove Emergency Contact
// ============================================
function removeEmergencyContact(index) {
    const contactItem = document.querySelector(`.emergency-contact-item[data-contact-index="${index}"]`);
    if (contactItem) {
        contactItem.remove();
    }
}

// ============================================
// Open Review Modal
// ============================================
function openReviewModal() {
    if (!validateStep(currentStep)) {
        return;
    }
    
    // Populate review modal with form data
    populateReviewModal();
    
    // Show modal
    document.getElementById('reviewModal').style.display = 'flex';
}

// ============================================
// Close Review Modal
// ============================================
function closeReviewModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

// ============================================
// Populate Review Modal
// ============================================
function populateReviewModal() {
    // This function would populate the review sections
    // Similar to create-resident.js but adapted for edit
    // For brevity, showing key sections
    
    const form = document.getElementById('editResidentForm');
    const formData = new FormData(form);
    
    // Personal Info
    let personalHtml = '<div class="review-grid">';
    personalHtml += `<div class="review-item"><span class="review-label">Resident ID:</span><span class="review-value">${document.getElementById('displayResidentId').value}</span></div>`;
    personalHtml += `<div class="review-item"><span class="review-label">Name:</span><span class="review-value">${formData.get('firstName')} ${formData.get('middleName')} ${formData.get('lastName')} ${formData.get('suffix')}</span></div>`;
    personalHtml += `<div class="review-item"><span class="review-label">Sex:</span><span class="review-value">${formData.get('sex')}</span></div>`;
    personalHtml += `<div class="review-item"><span class="review-label">Date of Birth:</span><span class="review-value">${formData.get('dateOfBirth')}</span></div>`;
    personalHtml += `<div class="review-item"><span class="review-label">Verification Status:</span><span class="review-value">${formData.get('verificationStatus')}</span></div>`;
    personalHtml += `<div class="review-item"><span class="review-label">Activity Status:</span><span class="review-value">${formData.get('activityStatus')}</span></div>`;
    personalHtml += '</div>';
    document.getElementById('reviewPersonalInfo').innerHTML = personalHtml;
    
    // Add other sections as needed...
}

// ============================================
// Submit Form from Review
// ============================================
function submitFormFromReview() {
    const form = document.getElementById('editResidentForm');
    const formData = new FormData(form);
    
    // Add mode flag
    formData.append('mode', 'update');
    
    // Show loading
    const submitBtn = document.getElementById('finalSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    fetch('save_resident.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccess('Resident updated successfully!');
            setTimeout(() => {
                window.location.href = `../resident_profile.php?id=${document.getElementById('residentId').value}`;
            }, 1500);
        } else {
            showError('Failed to update resident: ' + result.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check"></i> Confirm & Update';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while updating resident');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Confirm & Update';
    });
}

// ============================================
// Utility Functions
// ============================================
function showLoadingState() {
    // Show loading indicator
    console.log('Loading...');
}

function hideLoadingState() {
    // Hide loading indicator
    console.log('Loading complete');
}

function showError(message) {
    showNotification(message, 'error');
}

function showSuccess(message) {
    showNotification(message, 'success');
}

function showNotification(message, type = 'info') {
    // Remove existing notifications
    document.querySelectorAll('.notification-toast').forEach(n => n.remove());
    
    const notification = document.createElement('div');
    notification.className = `notification-toast notification-${type}`;
    
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
    const bgColor = type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6';
    
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
        animation: slideInRight 0.3s ease;
        font-size: 14px;
        font-weight: 500;
        min-width: 300px;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add notification animations
if (!document.getElementById('notification-styles')) {
    const style = document.createElement('style');
    style.id = 'notification-styles';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
        
        .notification-toast i {
            font-size: 18px;
        }
    `;
    document.head.appendChild(style);
}

// ============================================
// Webcam Functions (from create-resident.js)
// ============================================
function toggleInlineWebcam() {
    // Implement webcam functionality
    console.log('Toggle webcam');
}

function captureInlinePhoto() {
    // Implement photo capture
    console.log('Capture photo');
}

function closeWebcamModal() {
    document.getElementById('webcamModal').style.display = 'none';
}

function capturePhoto() {
    console.log('Capture photo');
}

function retakePhoto() {
    console.log('Retake photo');
}

function useWebcamPhoto() {
    console.log('Use webcam photo');
}
