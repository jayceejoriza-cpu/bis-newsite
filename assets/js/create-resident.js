// ===================================
// Create Resident Page JavaScript
// ===================================

let currentStep = 1;
const totalSteps = 6;
let currentAge = null;

// New DOM elements for Guardian Section
const guardianSection = document.getElementById('guardianSection');
const guardianNameInput = document.getElementById('guardianName');
const guardianRelationshipSelect = document.getElementById('guardianRelationship');
const guardianContactInput = document.getElementById('guardianContact');

// Reference to the main mobile number label
const mobileNumberLabel = document.querySelector('label[for="mobileNumber"]');
const mobileNumberInput = document.getElementById('mobileNumber');
const minorConsentNote = document.getElementById('minorConsentNote');

// New DOM elements for adult-only fields
const civilStatusSelect = document.getElementById('civilStatus');
const spouseNameInput = document.getElementById('spouseName');
const spouseNameGroup = document.getElementById('spouseNameGroup');
const voterStatusSelect = document.getElementById('voterStatus');
const employmentStatusSelect = document.getElementById('employmentStatus');
const occupationInput = document.getElementById('occupation');
const educationalAttainmentSelect = document.getElementById('educationalAttainment');
const philhealthIdInput = document.getElementById('philhealthId');
const membershipTypeSelect = document.getElementById('membershipType');
const philhealthCategorySelect = document.getElementById('philhealthCategory');

// New DOM elements for Age Milestones
const educationContainer = document.getElementById('educationContainer');
const voterStatusContainer = document.getElementById('voterStatusContainer');
const employmentContainer = document.getElementById('employmentContainer');
const occupationContainer = document.getElementById('occupationContainer');

// Global flag to track if the resident is a minor
let isMinor = false;

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', () => {
    // Set active navigation
    setActiveNavigation();
    
    // Restore saved step from localStorage
    restoreSavedStep();
    
    // Initialize form functionality
    initializeForm();
    
    // Initialize PWD status functionality
    initializePwdStatus();
    
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
    
    // Initialize ID formatting
    initializeIdFormatting();
    
    // Initialize navigation guard
    initializeNavigationGuard();

    setupAutocomplete('fatherName', 'fatherNameDropdown', 'Male', true, true);
    setupAutocomplete('motherName', 'motherNameDropdown', 'Female', true, true);
    setupAutocomplete('spouseName', 'spouseNameDropdown', null, false, true);
    setupAutocomplete('guardianName', 'guardianNameDropdown', null, false, true);
    setupAutocomplete('landlordName', 'landlordNameDropdown');
    
    console.log('Create Resident page loaded successfully');
});

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
        dobInput.addEventListener('change', updateMinorStatus);
        // Call updateMinorStatus once on load to set initial state
        updateMinorStatus();
    }
    
    // Religion Other toggle handler
    const religionSelect = document.getElementById('religion');
    const religionOtherInput = document.getElementById('religionOther');
    if (religionSelect && religionOtherInput) {
        const toggleReligion = () => {
            if (religionSelect.value === 'Other') {
                religionOtherInput.style.display = 'block';
                religionOtherInput.setAttribute('required', 'required');
            } else {
                religionOtherInput.style.display = 'none';
                religionOtherInput.removeAttribute('required');
                religionOtherInput.value = '';
                religionOtherInput.classList.remove('error');
            }
        };
        religionSelect.addEventListener('change', toggleReligion);
        toggleReligion(); // Initialize on load
    }

    // Guardian Relationship Other handler
    if (guardianRelationshipSelect) {
        // Create input element for "Other" relationship
        const guardianOtherInput = document.createElement('input');
        guardianOtherInput.type = 'text';
        guardianOtherInput.id = 'guardianRelationshipOther';
        guardianOtherInput.className = 'form-control mt-2';
        guardianOtherInput.placeholder = 'Please specify relationship';
        guardianOtherInput.style.display = 'none';
        
        // Insert after the select element
        guardianRelationshipSelect.parentNode.insertBefore(guardianOtherInput, guardianRelationshipSelect.nextSibling);
        
        guardianRelationshipSelect.addEventListener('change', function() {
            if (this.value === 'Other') {
                guardianOtherInput.style.display = 'block';
                guardianOtherInput.focus();
            } else {
                guardianOtherInput.style.display = 'none';
                guardianOtherInput.value = '';
            }
        });
        
        guardianOtherInput.addEventListener('blur', function() {
            const newValue = this.value.trim();
            if (newValue) {
                // Check if option already exists
                let optionExists = Array.from(guardianRelationshipSelect.options).some(opt => {
                    if (opt.value.toLowerCase() === newValue.toLowerCase()) {
                        guardianRelationshipSelect.value = opt.value;
                        return true;
                    }
                    return false;
                });
                
                // If it doesn't exist, add it
                if (!optionExists) {
                    const newOption = document.createElement('option');
                    newOption.value = newValue;
                    newOption.textContent = newValue;
                    
                    // Add before the 'Other' option if possible, or at the end
                    const otherOption = Array.from(guardianRelationshipSelect.options).find(opt => opt.value === 'Other');
                    if (otherOption) {
                        guardianRelationshipSelect.insertBefore(newOption, otherOption);
                    } else {
                        guardianRelationshipSelect.appendChild(newOption);
                    }
                    guardianRelationshipSelect.value = newValue;
                }
                
                // Hide and clear the input
                this.style.display = 'none';
                this.value = '';
                
                // Trigger change event to save form data
                guardianRelationshipSelect.dispatchEvent(new Event('change'));
            }
        });
        
        guardianOtherInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.blur();
            }
        });
    }

    // Adoption / Legal Guardian toggle logic
    const btnShowLegalGuardian = document.getElementById('btnShowLegalGuardian');
    const btnHideLegalGuardian = document.getElementById('btnHideLegalGuardian');
    const legalGuardianContainer = document.getElementById('legalGuardianContainer');
    const motherNameInput = document.getElementById('motherName');
    const motherRequired = document.getElementById('motherRequired');
    const legalGuardianInput = document.getElementById('legalGuardianName');

    if (btnShowLegalGuardian) {
        btnShowLegalGuardian.addEventListener('click', function() {
            legalGuardianContainer.style.display = 'block';
            this.style.display = 'none';
            
            // Disable Mother input and transfer required attribute to Guardian
            motherNameInput.removeAttribute('required');
            motherNameInput.setAttribute('disabled', 'disabled');
            motherNameInput.classList.remove('error');
            if (motherRequired) motherRequired.style.display = 'none';
            
            legalGuardianInput.setAttribute('required', 'required');
            legalGuardianInput.focus();
            saveFormData();
        });
    }

    if (btnHideLegalGuardian) {
        btnHideLegalGuardian.addEventListener('click', function() {
            legalGuardianContainer.style.display = 'none';
            btnShowLegalGuardian.style.display = 'block';
            
            // Re-enable Mother input and revert required attribute
            motherNameInput.removeAttribute('disabled');
            motherNameInput.setAttribute('required', 'required');
            if (motherRequired) motherRequired.style.display = '';
            
            legalGuardianInput.removeAttribute('required');
            legalGuardianInput.value = '';
            saveFormData();
        });
    }

    // Civil status change handler
    const civilStatusSelect = document.getElementById('civilStatus');
    if (civilStatusSelect) {
        // Call once on load to set initial state
        handleCivilStatusChange();
        civilStatusSelect.addEventListener('change', handleCivilStatusChange);
    }
    
    // Voter status change handler
    const voterStatusSelect = document.getElementById('voterStatus');
    if (voterStatusSelect) {
        handleVoterStatusChange();
        voterStatusSelect.addEventListener('change', handleVoterStatusChange);
    }
    
    // 4Ps change handler
    const fourPsSelect = document.getElementById('fourPs');
    if (fourPsSelect) {
        handleFourPsChange();
        fourPsSelect.addEventListener('change', handleFourPsChange);
    }
    
    // Sex change handler (for WRA section)
    const sexSelect = document.getElementById('sex');
    if (sexSelect) {
        handleSexChange();
        sexSelect.addEventListener('change', handleSexChange);
    }
    
    // FP Method change handler
    const usingFpMethodSelect = document.getElementById('usingFpMethod');
    if (usingFpMethodSelect) {
        handleFpMethodChange();
        usingFpMethodSelect.addEventListener('change', handleFpMethodChange);
    }

    // OFW Employment handlers
    if (employmentStatusSelect) {
        handleEmploymentStatusChange();
        employmentStatusSelect.addEventListener('change', handleEmploymentStatusChange);
    }

    // House occupancy handlers
    const isHouseOccupiedSelect = document.getElementById('isHouseOccupied');
    if (isHouseOccupiedSelect) {
        handleHouseOccupiedChange();
        isHouseOccupiedSelect.addEventListener('change', handleHouseOccupiedChange);
    }
}

// ===================================
// PWD Status Management
// ===================================
function initializePwdStatus() {
    const yesRadio = document.getElementById('pwdStatusYes');
    const noRadio = document.getElementById('pwdStatusNo');
    const hiddenInput = document.getElementById('pwdStatus');
    const pwdTypeGroup = document.getElementById('pwdTypeGroup');
    const pwdIdGroup = document.getElementById('pwdIdGroup');
    const pwdType = document.getElementById('pwdType');
    const pwdIdNumber = document.getElementById('pwdIdNumber');

    const togglePwdFields = (isYes) => {
        if (pwdTypeGroup && pwdIdGroup) {
            pwdTypeGroup.style.display = isYes ? 'block' : 'none';
            pwdIdGroup.style.display = isYes ? 'block' : 'none';
            if (pwdType) {
                if (isYes) {
                    pwdType.setAttribute('required', 'required');
                } else {
                    pwdType.removeAttribute('required');
                    pwdType.value = '';
                    pwdType.classList.remove('error');
                }
            }
            if (!isYes && pwdIdNumber) pwdIdNumber.value = '';
        }
    };

    if (yesRadio && hiddenInput) {
        yesRadio.addEventListener('change', function() {
            if (this.checked) {
                hiddenInput.value = 'Yes';
                togglePwdFields(true);
                saveFormData();
            }
        });
    }

    if (noRadio && hiddenInput) {
        noRadio.addEventListener('change', function() {
            if (this.checked) {
                hiddenInput.value = 'No';
                    togglePwdFields(false);
                saveFormData();
            }
        });
    }
        
        if (hiddenInput && hiddenInput.value === 'Yes') {
            togglePwdFields(true);
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

         // Skip Step 5 if age < 10
        if (currentStep === 5 && currentAge !== null && currentAge < 10) {
            currentStep++;
        }
        updateStep();
        saveCurrentStep(); // Save step to localStorage
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
         // Skip Step 5 if age < 10
        if (currentStep === 5 && currentAge !== null && currentAge < 10) {
            currentStep--;
        }
        updateStep();
        saveCurrentStep();
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
        // If a required field is disabled (e.g., for minors), it shouldn't be validated.
        const isVisible = !!(field.offsetWidth || field.offsetHeight || field.getClientRects().length);

        // If a required field is disabled OR not visible, it shouldn't be validated.
        // This is the key fix for the minor registration validation error.
        if (field.disabled || !isVisible) {
            field.classList.remove('error'); // Clean up any previous error state
            return; // Skip validation for this field.
        }

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
    
    // Specific validation for minors in Step 3 (Family Information)
    if (step === 3 && isMinor) {
        // Check if guardian fields are required and empty
        if (guardianNameInput && guardianNameInput.hasAttribute('required') && !guardianNameInput.value.trim()) {
            showNotification('Guardian\'s Full Name is required for minors.', 'error');
            guardianNameInput.focus();
            isValid = false;
        } else if (guardianRelationshipSelect && guardianRelationshipSelect.hasAttribute('required') && !guardianRelationshipSelect.value) {
            showNotification('Guardian\'s Relationship is required for minors.', 'error');
            guardianRelationshipSelect.focus();
            isValid = false;
        } else if (guardianContactInput && guardianContactInput.hasAttribute('required') && !guardianContactInput.value.trim()) {
            showNotification('Guardian\'s Contact Number is required for minors.', 'error');
            guardianContactInput.focus();
            isValid = false;
        }
    }

    if (!isValid) {
        // Only show generic notification if it's not a specific minor validation error
        if (!(step === 3 && isMinor && (!guardianNameInput.value.trim() || !guardianRelationshipSelect.value))) {
             showNotification('Please fill in all required fields', 'error');
        }
        
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
// ===================================
// Progressive Milestone System
// This function evaluates each milestone independently based on age
// ===================================
function updateMinorStatus() {
    const dobInput = document.getElementById('dateOfBirth');
    const minorAlert = document.getElementById('minorAlert');
    const ageHealthGroupSelect = document.getElementById('ageHealthGroup');
    const adultOnlyElements = document.querySelectorAll('.adult-only');
    const minorOnlyElements = document.querySelectorAll('.minor-only');

    // New container elements for progressive milestones
    const educationContainer = document.getElementById('educationContainer');
    const voterStatusContainer = document.getElementById('voterStatusContainer');
    const employmentContainer = document.getElementById('employmentContainer');
    const occupationContainer = document.getElementById('occupationContainer');
    const incomeContainer = document.getElementById('incomeContainer');

    // If DOB is empty, reset everything to default state
    if (!dobInput.value) {
        currentAge = null;
        isMinor = false;
        
        // Reset guardian fields
        if (guardianSection) guardianSection.style.display = 'none';
        if (guardianNameInput) guardianNameInput.removeAttribute('required');
        if (guardianRelationshipSelect) guardianRelationshipSelect.removeAttribute('required');
        if (guardianContactInput) guardianContactInput.removeAttribute('required');
        if (mobileNumberLabel) mobileNumberLabel.innerHTML = 'Mobile Number <span class="required">*</span>';
        if (mobileNumberInput) mobileNumberInput.setAttribute('required', 'required');
        if (minorConsentNote) minorConsentNote.style.display = 'none';
        if (minorAlert) minorAlert.style.display = 'none';
        if (ageHealthGroupSelect) ageHealthGroupSelect.value = '';
        
        // Restore Mobile Number field
        const mobileNumberCol = mobileNumberInput ? mobileNumberInput.closest('.col-md-3') : null;
        if (mobileNumberCol) mobileNumberCol.style.display = '';
        if (mobileNumberInput) mobileNumberInput.setAttribute('required', 'required');

        // Restore Step 5
        const stepLines = document.querySelectorAll('.step-line');
        const step5Indicator = document.querySelector('.step[data-step="5"]');
        const step5Line = stepLines.length > 3 ? stepLines[3] : null;
        if (step5Indicator) step5Indicator.style.display = '';
        if (step5Line) step5Line.style.display = '';
        
        // Show all containers by default
        minorOnlyElements.forEach(el => el.style.display = 'none');
        adultOnlyElements.forEach(el => el.style.display = 'block');
        
        // Show all progressive milestone containers
        if (educationContainer) educationContainer.style.display = 'block';
        if (voterStatusContainer) voterStatusContainer.style.display = 'block';
        if (employmentContainer) employmentContainer.style.display = 'block';
        if (occupationContainer) occupationContainer.style.display = 'block';
        if (incomeContainer) incomeContainer.style.display = 'block';
        document.querySelectorAll('.gov-programs-section').forEach(el => el.style.display = '');
        const govHeaderReset = Array.from(document.querySelectorAll('h5')).find(el => el.textContent.includes('Government Programs'));
        if (govHeaderReset) govHeaderReset.style.display = '';
        
        // Enable all relevant fields
        if (civilStatusSelect) {
            civilStatusSelect.removeAttribute('disabled');
            civilStatusSelect.setAttribute('required', 'required');
        }
        if (spouseNameInput) {
            spouseNameInput.removeAttribute('disabled');
            handleCivilStatusChange();
        }
        if (voterStatusSelect) voterStatusSelect.removeAttribute('disabled');
        if (employmentStatusSelect) employmentStatusSelect.removeAttribute('disabled');
        if (occupationInput) occupationInput.removeAttribute('disabled');
        if (educationalAttainmentSelect) educationalAttainmentSelect.removeAttribute('disabled');
        if (philhealthIdInput) philhealthIdInput.removeAttribute('disabled');
        if (membershipTypeSelect) membershipTypeSelect.removeAttribute('disabled');
        if (philhealthCategorySelect) philhealthCategorySelect.removeAttribute('disabled');
        handleEmploymentStatusChange();
        handleSexChange();

        // Allow adult to be household head by default
        const yesRadio = document.getElementById('householdHeadYes');
        if (yesRadio) {
            yesRadio.disabled = false;
            const yesLabel = yesRadio.closest('.radio-option') || yesRadio.closest('label');
            if (yesLabel) yesLabel.style.display = '';
        }

        return;
    }

    const dob = new Date(dobInput.value);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
    }

    // Calculate age in days for precise classification (Newborn/Infant)
    const diffTime = Math.abs(today - dob);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    let healthGroup = '';
    if (diffDays <= 28) {
        healthGroup = 'Newborn (0-28 days)';
    } else if (age < 1) {
        healthGroup = 'Infant (29 days - 1 year)';
    } else if (age >= 1 && age <= 9) {
        healthGroup = 'Child (1-9 years)';
    } else if (age >= 10 && age <= 19) {
        healthGroup = 'Adolescent (10-19 years)';
    } else if (age >= 20 && age <= 59) {
        healthGroup = 'Adult (20-59 years)';
    } else if (age >= 60) {
        healthGroup = 'Senior Citizen (60+ years)';
    }

    if (ageHealthGroupSelect) {
        ageHealthGroupSelect.value = healthGroup;
        saveFormData();
    }

    console.log(`Calculated age: ${age} years`);
    currentAge = age;

    // Hide Mobile Number field in Step 2 if age <= 10
    const mobileNumberCol = mobileNumberInput ? mobileNumberInput.closest('.col-md-3') || mobileNumberInput.closest('.form-group') : null;
    if (age <= 10) {
        if (mobileNumberCol) mobileNumberCol.style.display = 'none';
        if (mobileNumberInput) {
            mobileNumberInput.removeAttribute('required');
            mobileNumberInput.value = '';
        }
    } else {
        if (mobileNumberCol) mobileNumberCol.style.display = '';
        if (mobileNumberInput) mobileNumberInput.setAttribute('required', 'required');
    }

    // ==============================================
    // Hide/Show Step 5 (Education & Employment) entirely if age < 10
    // ==============================================
    const step5Indicator = document.querySelector('.step[data-step="5"]');
    const stepLines = document.querySelectorAll('.step-line');
    const step5Line = stepLines.length > 3 ? stepLines[3] : null;
    
    if (age < 10) {
        if (step5Indicator) step5Indicator.style.display = 'none';
        if (step5Line) step5Line.style.display = 'none';
        
        if (currentStep === 5) {
            currentStep = 6;
            updateStep();
            saveCurrentStep();
        }
    } else {
        if (step5Indicator) step5Indicator.style.display = '';
        if (step5Line) step5Line.style.display = '';
    }

    // ==============================================
    // PROGRESSIVE MILESTONE SYSTEM
    // Each milestone is evaluated independently
    // ==============================================

    // ==============================================
    // 1. EDUCATION MILESTONE (Age 10+)
    // Educational Attainment is visible for age >= 10
    // ==============================================
    if (age >= 10) {
        if (educationContainer) {
            educationContainer.style.display = 'block';
        }
        if (educationalAttainmentSelect) {
            educationalAttainmentSelect.removeAttribute('disabled');
        }
    } else {
        if (educationContainer) {
            educationContainer.style.display = 'none';
        }
        if (educationalAttainmentSelect) {
            educationalAttainmentSelect.value = '';
            educationalAttainmentSelect.setAttribute('disabled', 'disabled');
        }
    }

    // ==============================================
    // 2. VOTER MILESTONE (Age 15+)
    // Voter Status is visible starting at age 15
    // ==============================================
    const govHeader = Array.from(document.querySelectorAll('h5')).find(el => el.textContent.includes('Government Programs'));

    if (age >= 15) {
        // Show voter status container
        if (voterStatusContainer) voterStatusContainer.style.display = 'block';
        if (voterStatusSelect) voterStatusSelect.removeAttribute('disabled');
        
        document.querySelectorAll('.gov-programs-section').forEach(el => el.style.display = '');
        if (govHeader) govHeader.style.display = '';
    } else {
        // Hide and disable for age < 15
        if (voterStatusContainer) voterStatusContainer.style.display = 'none';
        if (voterStatusSelect) {
            voterStatusSelect.value = 'No';
            voterStatusSelect.setAttribute('disabled', 'disabled');
            voterStatusSelect.dispatchEvent(new Event('change'));
        }
        
        document.querySelectorAll('.gov-programs-section').forEach(el => el.style.display = 'none');
        if (govHeader) govHeader.style.display = 'none';
    }

    // ==============================================
    // 3. GUARDIAN MILESTONE (Age 0-17 only)
    // Guardian section is visible ONLY for minors (age < 18)
    // ==============================================
    isMinor = (age < 18);
    
    if (isMinor) {
        // Show minor-only fields (Guardian Section)
        minorOnlyElements.forEach(el => el.style.display = 'block');
        
        if (guardianSection) guardianSection.style.display = 'block';
        // Set guardian fields as required
        if (guardianNameInput) guardianNameInput.setAttribute('required', 'required');
        if (guardianRelationshipSelect) guardianRelationshipSelect.setAttribute('required', 'required');
        if (guardianContactInput) guardianContactInput.setAttribute('required', 'required');

        // Update Mobile Number label for resident (not required for minors)
        if (mobileNumberLabel && age > 10) mobileNumberLabel.innerHTML = 'Mobile Number';
        if (mobileNumberInput) mobileNumberInput.removeAttribute('required');

        // Show minor-related UI elements
        if (minorConsentNote) minorConsentNote.style.display = 'block';
        if (minorAlert) minorAlert.style.display = 'block';

        // Hide adult-only fields but keep them accessible for data display
        adultOnlyElements.forEach(el => el.style.display = 'none');

        // Disable adult-specific fields
        if (civilStatusSelect) {
            civilStatusSelect.value = 'Single';
            civilStatusSelect.setAttribute('disabled', 'disabled');
            civilStatusSelect.removeAttribute('required');
        }
        if (spouseNameInput) {
            spouseNameInput.value = '';
            spouseNameInput.setAttribute('disabled', 'disabled');
            if (spouseNameGroup) spouseNameGroup.style.display = 'none';
            spouseNameInput.required = false;
        }
        if (philhealthIdInput) philhealthIdInput.setAttribute('disabled', 'disabled');
        if (membershipTypeSelect) membershipTypeSelect.setAttribute('disabled', 'disabled');
        if (philhealthCategorySelect) philhealthCategorySelect.setAttribute('disabled', 'disabled');
        
        // Hide and disable Employment and Occupation for minors
        if (employmentContainer) employmentContainer.style.display = 'none';
        if (employmentStatusSelect) {
            employmentStatusSelect.value = '';
            employmentStatusSelect.setAttribute('disabled', 'disabled');
        }
        handleEmploymentStatusChange();
        
        if (occupationContainer) occupationContainer.style.display = 'none';
        if (occupationInput) {
            occupationInput.value = '';
            occupationInput.setAttribute('disabled', 'disabled');
        }
        
        if (incomeContainer) incomeContainer.style.display = 'none';
        
        // Disable 4Ps for minors
        const fourPsSelect = document.getElementById('fourPs');
        if (fourPsSelect) {
            fourPsSelect.value = 'No';
            fourPsSelect.setAttribute('disabled', 'disabled');
            fourPsSelect.dispatchEvent(new Event('change'));
        }

        // Prevent minor from being household head
        const yesRadio = document.getElementById('householdHeadYes');
        const noRadio = document.getElementById('householdHeadNo');
        if (yesRadio && noRadio) {
            yesRadio.disabled = true;
            if (yesRadio.checked) {
                noRadio.checked = true;
                noRadio.dispatchEvent(new Event('change'));
            }
            const yesLabel = yesRadio.closest('.radio-option') || yesRadio.closest('label');
            if (yesLabel) yesLabel.style.display = 'none';
        }
    } else {
        // Age >= 18 - Hide Guardian section
        minorOnlyElements.forEach(el => el.style.display = 'none');
        
        if (guardianSection) guardianSection.style.display = 'none';
        // Unset guardian fields as required
        if (guardianNameInput) guardianNameInput.removeAttribute('required');
        if (guardianRelationshipSelect) guardianRelationshipSelect.removeAttribute('required');
        if (guardianContactInput) guardianContactInput.removeAttribute('required');
        
        // Restore mobile number requirement
        if (mobileNumberLabel) mobileNumberLabel.innerHTML = 'Mobile Number <span class="required">*</span>';
        if (mobileNumberInput) mobileNumberInput.setAttribute('required', 'required');
        
        // Hide minor-related UI elements
        if (minorConsentNote) minorConsentNote.style.display = 'none';
        if (minorAlert) minorAlert.style.display = 'none';

        // Allow adult to be household head
        const yesRadio = document.getElementById('householdHeadYes');
        if (yesRadio) {
            yesRadio.disabled = false;
            const yesLabel = yesRadio.closest('.radio-option') || yesRadio.closest('label');
            if (yesLabel) yesLabel.style.display = '';
        }
    }

    // ==============================================
    // 4. ADULT MILESTONE (Age 18+)
    // Civil Status and Spouse Name are unlocked at age 18
    // ==============================================
    if (age >= 18) {
        // Show adult-only fields
        adultOnlyElements.forEach(el => {
            // Don't forcefully show conditionally hidden elements
            if (el.id === 'spouseNameGroup' || el.id === 'fourpsIdGroup' || el.id === 'precinctNumberGroup') {
                return;
            }
            el.style.display = 'block';
        });

        // Enable and require Civil Status
        if (civilStatusSelect) {
            civilStatusSelect.removeAttribute('disabled');
            civilStatusSelect.setAttribute('required', 'required');
        }
        
        // Enable Spouse Name field (visibility controlled by civilStatus)
        if (spouseNameInput) {
            spouseNameInput.removeAttribute('disabled');
            handleCivilStatusChange();
        }
        
        // Enable PhilHealth fields
        if (philhealthIdInput) philhealthIdInput.removeAttribute('disabled');
        if (membershipTypeSelect) membershipTypeSelect.removeAttribute('disabled');
        if (philhealthCategorySelect) philhealthCategorySelect.removeAttribute('disabled');
        
        // Enable 4Ps for adults
        const fourPsSelect = document.getElementById('fourPs');
        if (fourPsSelect) {
            fourPsSelect.removeAttribute('disabled');
            handleFourPsChange();
        }
        
        // Enable Employment & Occupation
        if (employmentContainer) employmentContainer.style.display = 'block';
        if (employmentStatusSelect) employmentStatusSelect.removeAttribute('disabled');
        
        if (occupationContainer) occupationContainer.style.display = 'block';
        if (occupationInput) occupationInput.removeAttribute('disabled');
        
        if (incomeContainer) incomeContainer.style.display = 'block';
        
        handleVoterStatusChange();
    } else {
        // Age < 18 - Adult fields already hidden above
        // But we need to ensure they're disabled
        if (civilStatusSelect) {
            civilStatusSelect.setAttribute('disabled', 'disabled');
        }
        if (spouseNameInput) {
            spouseNameInput.setAttribute('disabled', 'disabled');
        }
    }
    
    handleSexChange();
    saveFormData();
}

function handleEmploymentStatusChange() {
    const ofwSection = document.getElementById('ofwHouseSection');
    if (employmentStatusSelect && employmentStatusSelect.value === 'OFW' && !isMinor) {
        if (ofwSection) ofwSection.style.display = 'block';
    } else {
        if (ofwSection) {
            ofwSection.style.display = 'none';
        }
    }
}

function handleHouseOccupiedChange() {
    const isOccupiedSelect = document.getElementById('isHouseOccupied');
    const caretakerGroup = document.getElementById('caretakerInfoGroup');
    const caretakerName = document.getElementById('caretakerName');
    const caretakerContact = document.getElementById('caretakerContact');

    if (isOccupiedSelect && isOccupiedSelect.value === 'No') {
        if (caretakerGroup) caretakerGroup.style.display = 'block';
        if (caretakerName) caretakerName.setAttribute('required', 'required');
        if (caretakerContact) caretakerContact.setAttribute('required', 'required');
    } else {
        if (caretakerGroup) caretakerGroup.style.display = 'none';
        if (caretakerName) {
            caretakerName.removeAttribute('required');
            caretakerName.value = '';
        }
        if (caretakerContact) {
            caretakerContact.removeAttribute('required');
            caretakerContact.value = '';
        }
    }
}

function handleCivilStatusChange() {
    const civilStatus = document.getElementById('civilStatus').value;
    const spouseGroup = document.getElementById('spouseNameGroup'); // Target the group
    const spouseInput = document.getElementById('spouseName');

    if (spouseGroup && spouseInput) {
        if (civilStatus === 'Married' || civilStatus === 'Live-In') {
            spouseGroup.style.display = 'block';
            spouseInput.required = true;
            if (civilStatus === 'Married') {
                spouseGroup.querySelector('label').innerHTML = 'Spouse Name <span class="required">*</span>';
            } else {
                spouseGroup.querySelector('label').innerHTML = 'Live-In Partner Name <span class="required">*</span>';
            }
        } else {
            spouseGroup.style.display = 'none';
            spouseInput.required = false;
            spouseInput.value = ''; // Clear value when hidden
            spouseGroup.querySelector('label').innerHTML = 'Spouse Name';
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
        // Show only if Female AND age is between 15 and 49 (Women of Reproductive Age)
        const isWRA = (sex === 'Female' && currentAge !== null && currentAge >= 15 && currentAge <= 49);
        
        if (isWRA) {
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
    
    // Temporarily enable disabled fields so they are included in FormData
    const disabledElements = Array.from(form.querySelectorAll(':disabled'));
    disabledElements.forEach(el => el.disabled = false);
    
    const formData = new FormData(form);
    
    // Re-disable them
    disabledElements.forEach(el => el.disabled = true);
    
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
    
    formContainer.innerHTML = `
        <div class="success-message show">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2>${message}</h2>
            <p>The resident record has been successfully added to the system.</p>
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
    
    // Hide the progress steps
    const progressSteps = document.querySelector('.progress-steps');
    if (progressSteps) {
        progressSteps.style.display = 'none';
    }

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
        icon = 'exclamation-circle';
        bgColor = '#ef4444';
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
        z-index: 10000000;
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
    let numbers = value.replace(/\D/g, '');
    
    // Enforce first digit is 9
    if (numbers.length > 0 && numbers[0] !== '9') {
        numbers = '';
    }
    
    // Limit to 10 digits (9XX XXX XXXX format for +63)
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

// ===================================
// Autocomplete Setup
// ===================================
function setupAutocomplete(inputId, dropdownId, filterSex = null, requireOlder = false, onlyAdult = false, onlyMinor = false) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(dropdownId);
    let timeout = null;

    if (!input || !dropdown) return;

    input.addEventListener('input', function(e) {
        // Clear hidden ID when typing manually
        if (e.isTrusted) {
            const hiddenId = document.getElementById(inputId + 'Id');
            if (hiddenId) hiddenId.value = '';
        }

        clearTimeout(timeout);
        const query = this.value.trim();

        if (query.length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        timeout = setTimeout(() => {
            const basePath = window.location.pathname.includes('/model/') ? '' : 'model/';
            
            // Get current DOB from the form
            const dobInput = document.getElementById('dateOfBirth') || document.querySelector('input[name="date_of_birth"]');
            const currentDob = dobInput ? dobInput.value : '';
            
            let url = `${basePath}search_residents.php?search=${encodeURIComponent(query)}&include_deceased=true`;
            if (requireOlder && currentDob) {
                url += `&dob_before=${encodeURIComponent(currentDob)}`;
            }
            if (onlyAdult) {
                url += `&filter=adult`;
            }
            if (onlyMinor) {
                url += `&filter=minor`;
            }
            
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    dropdown.innerHTML = '';
                    if (data.success && data.data && data.data.length > 0) {
                        let count = 0;
                        data.data.forEach(resident => {
                            if (filterSex && resident.sex !== filterSex) return;
                            
                            // Frontend check to ensure the suggested person is strictly older
                            if (requireOlder && currentDob && resident.date_of_birth) {
                                if (new Date(resident.date_of_birth) >= new Date(currentDob)) return;
                            }
                            
                            // Age check for minor/adult filtering
                            if ((onlyMinor || onlyAdult) && resident.date_of_birth) {
                                const dob = new Date(resident.date_of_birth);
                                const today = new Date();
                                let age = today.getFullYear() - dob.getFullYear();
                                const m = today.getMonth() - dob.getMonth();
                                if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
                                
                                if (onlyMinor && age >= 18) return;
                                if (onlyAdult && age < 18) return;
                            }

                            count++;
                            const item = document.createElement('div');
                            item.className = 'autocomplete-item';
                            
                            // Highlight matching text
                            const safeQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                            const regex = new RegExp(`(${safeQuery})`, 'gi');
                            let displayHtml = resident.full_name.replace(regex, '<strong>$1</strong>');
                            if (resident.activity_status === 'Deceased') {
                                displayHtml += ' <span style="font-size: 11px; color: #ef4444; font-style: italic;">(Deceased)</span>';
                            }
                            item.innerHTML = displayHtml;
                            
                            item.addEventListener('click', () => {
                                input.value = resident.full_name;
                                dropdown.style.display = 'none';
                                
                                const hiddenId = document.getElementById(inputId + 'Id');
                                if (hiddenId) hiddenId.value = resident.id;
                                
                                // Auto-fill guardian mobile number if selecting a guardian
                                if (inputId === 'guardianName' && resident.mobile_number) {
                                    const contactInput = document.getElementById('guardianContact');
                                    if (contactInput) {
                                        contactInput.value = resident.mobile_number;
                                        contactInput.dispatchEvent(new Event('input'));
                                    }
                                }

                                // Auto-fill number of children if selecting a spouse/live-in partner
                                if (inputId === 'spouseName' && resident.number_of_children !== undefined) {
                                    const childrenInput = document.getElementById('numberOfChildren');
                                    if (childrenInput) {
                                        childrenInput.value = resident.number_of_children;
                                        childrenInput.dispatchEvent(new Event('input'));
                                    }
                                }

                                input.dispatchEvent(new Event('input'));
                            });
                            dropdown.appendChild(item);
                        });
                        
                        if (count > 0) {
                            dropdown.style.display = 'block';
                        } else {
                            dropdown.style.display = 'none';
                        }
                    } else {
                        dropdown.style.display = 'none';
                    }
                })
                .catch(err => {
                    console.error('Error fetching residents:', err);
                    dropdown.style.display = 'none';
                });
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (e.target !== input && e.target !== dropdown) {
            dropdown.style.display = 'none';
        }
    });
}

function initializePhoneNumberFormatting() {
    // Apply to mobile number field
    const mobileNumberInput = document.getElementById('mobileNumber');
    applyPhoneNumberFormatting(mobileNumberInput);

    // Apply to caretaker contact field
    const caretakerContactInput = document.getElementById('caretakerContact');
    applyPhoneNumberFormatting(caretakerContactInput);

    // Apply to guardian contact field
    const guardianContactInput = document.getElementById('guardianContact');
    applyPhoneNumberFormatting(guardianContactInput);
}

// ===================================
// ID Formatting (4Ps & Philhealth)
// ===================================
function formatFourPsId(input) {
    input.value = input.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
}

function formatPhilhealthId(input) {
    input.value = input.value.replace(/\D/g, '');
}

function initializeIdFormatting() {
    const fourpsIdInput = document.getElementById('fourpsId');
    if (fourpsIdInput) {
        fourpsIdInput.addEventListener('input', function() {
            formatFourPsId(this);
        });
    }

    const philhealthIdInput = document.getElementById('philhealthId');
    if (philhealthIdInput) {
        philhealthIdInput.addEventListener('input', function() {
            formatPhilhealthId(this);
        });
    }
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

    // Ownership Status toggle
    const ownershipRadios = document.getElementsByName('ownershipStatus');
    const landlordNameGroup = document.getElementById('landlordNameGroup');
    const landlordNameInput = document.getElementById('landlordName');

    const updateLandlordVisibility = () => {
        const checkedRadio = Array.from(ownershipRadios).find(r => r.checked);
        if (checkedRadio && checkedRadio.value === 'Rent') {
            if (landlordNameGroup) landlordNameGroup.style.display = 'block';
            if (landlordNameInput) landlordNameInput.setAttribute('required', 'required');
        } else {
            if (landlordNameGroup) landlordNameGroup.style.display = 'none';
            if (landlordNameInput) {
                landlordNameInput.removeAttribute('required');
                landlordNameInput.value = '';
                const landlordId = document.getElementById('landlordNameId');
                if (landlordId) landlordId.value = '';
            }
        }
    };

    ownershipRadios.forEach(radio => {
        radio.addEventListener('change', updateLandlordVisibility);
    });
    updateLandlordVisibility(); // Initial check
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
                    Relationship of Member to Household Head <span style="color: #ef4444;">*</span>
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
let confirmationListenerAttached = false;
let privacyNoticeViewed = false;

function resetConfirmationCheckbox() {
    const checkbox = document.getElementById('confirmDetailsCheckbox');
    const submitBtn = document.getElementById('finalSubmitBtn');
    const acknowledgeBtn = document.getElementById('acknowledgePrivacyBtn');
    const scrollIndicator = document.getElementById('scrollIndicator');
    
    if (checkbox) checkbox.checked = false;
    if (submitBtn) submitBtn.disabled = true;
    if (acknowledgeBtn) acknowledgeBtn.disabled = true;
    if (scrollIndicator) scrollIndicator.style.display = 'block';
    privacyNoticeViewed = false;
}

function initializeConfirmationCheckbox() {
    if (confirmationListenerAttached) return;
    
    const checkbox = document.getElementById('confirmDetailsCheckbox');
    const submitBtn = document.getElementById('finalSubmitBtn');
    const viewPrivacyLink = document.getElementById('viewPrivacyLink');
    const privacyNoticeBody = document.getElementById('privacyNoticeBody');
    const acknowledgeBtn = document.getElementById('acknowledgePrivacyBtn');
    const scrollIndicator = document.getElementById('scrollIndicator');

    if (viewPrivacyLink) {
        viewPrivacyLink.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('privacyNoticeModal').style.display = 'flex';
        });
    }

    if (privacyNoticeBody) {
        privacyNoticeBody.addEventListener('scroll', function() {
            // Check if user reached the bottom
            const isAtBottom = privacyNoticeBody.scrollHeight - privacyNoticeBody.scrollTop <= privacyNoticeBody.clientHeight + 5;
            if (isAtBottom) {
                privacyNoticeViewed = true;
                if (acknowledgeBtn) acknowledgeBtn.disabled = false;
                if (scrollIndicator) scrollIndicator.style.display = 'none';
            }
        });
    }

    if (checkbox) {
        checkbox.addEventListener('change', function() {
            if (this.checked && !privacyNoticeViewed) {
                this.checked = false;
                showNotification('Please read the Privacy Notice first', 'warning');
                document.getElementById('privacyNoticeModal').style.display = 'flex';
            } else {
                submitBtn.disabled = !(this.checked && privacyNoticeViewed);
            }
        });
    }

    confirmationListenerAttached = true;
}

window.closePrivacyNoticeModal = function() {
    document.getElementById('privacyNoticeModal').style.display = 'none';
};

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
        const displayValue = (value && String(value).trim() !== '') ? value : 'N/A';
        return `
            <div class="review-field">
                <div class="review-field-label">${label}</div>
                <div class="review-field-value">${displayValue}</div>
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
    } else {
        personalInfoHTML += `
            <div class="review-photo">
                <div style="width: 100px; height: 100px; background: var(--bg-primary); border: 1px dashed var(--border-color); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: var(--text-secondary); font-size: 14px;">N/A</div>
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
    personalInfoHTML += createField('Place of Birth', getValue('placeOfBirth'));
    
    let religionValue = getValue('religion');
    if (religionValue === 'Other') {
        religionValue = getValue('religion_other') || 'Other';
    }
    personalInfoHTML += createField('Religion', religionValue);
    personalInfoHTML += createField('Ethnicity', getValue('ethnicity'));
    personalInfoHTML += '</div>';
    
    document.getElementById('reviewPersonalInfo').innerHTML = personalInfoHTML;
    
    // 2. Contact Information
    let contactInfoHTML = '<div class="review-fields-grid">';
    contactInfoHTML += createField('Mobile Number', getValue('mobileNumber'));
    
    // Construct address for review
    const houseNo = getValue('houseNo');
    const purok = getValue('purok');
    const streetName = getValue('streetName');
    const addressParts = [purok ? `Purok ${purok}` : '', streetName].filter(Boolean);
    const fullAddress = addressParts.join(', ');

    contactInfoHTML += createField('Complete Address', fullAddress);
    contactInfoHTML += '</div>';
    
    document.getElementById('reviewContactInfo').innerHTML = contactInfoHTML;
    const reviewContactInfo = document.getElementById('reviewContactInfo');
    if (currentAge !== null && currentAge <= 10) {
        reviewContactInfo.closest('.review-section').style.display = 'none';
    } else {
        reviewContactInfo.closest('.review-section').style.display = 'block';
        reviewContactInfo.innerHTML = contactInfoHTML;
    }
    
    // 3. Family Information
    let familyInfoHTML = '<div class="review-fields-grid">';
    if (!isMinor) {
        familyInfoHTML += createField('Civil Status', getValue('civilStatus'));
        const nameLabel = getValue('civilStatus') === 'Live-In' ? 'Live-In Partner Name' : 'Spouse Name';
        familyInfoHTML += createField(nameLabel, getValue('spouseName'));
    }
    familyInfoHTML += createField("Father's Name", getValue('fatherName'));
    familyInfoHTML += createField("Mother's Name", getValue('motherName'));
    
    if (getValue('legalGuardianName')) {
        familyInfoHTML += createField('Legal Guardian', getValue('legalGuardianName'));
    }

    if (!isMinor) {
        familyInfoHTML += createField('Number of Children', getValue('numberOfChildren'));
    }
    familyInfoHTML += '</div>';

    // Add Guardian Information if minor
    if (isMinor) {
        familyInfoHTML += '<h5 style="margin: 20px 0 15px 0; color: var(--primary-color);"><i class="fas fa-user-shield"></i> Guardian Information</h5>';
        familyInfoHTML += '<div class="review-fields-grid">';
        familyInfoHTML += createField('Guardian\'s Full Name', getValue('guardianName'));
        familyInfoHTML += createField('Relationship to Guardian', getValue('guardianRelationship'));
        familyInfoHTML += createField('Guardian\'s Mobile Number', getValue('guardianContact'));
        familyInfoHTML += '</div>';
    }

    // Add OFW Information if applicable (Adults only)
    if (!isMinor && getValue('employmentStatus') === 'OFW') {
        familyInfoHTML += '<h5 style="margin: 20px 0 15px 0; color: var(--primary-color);"><i class="fas fa-plane-departure"></i> OFW Additional Information</h5>';
        familyInfoHTML += '<div class="review-fields-grid">';
        familyInfoHTML += createField('House Occupied?', getValue('isHouseOccupied'));
        if (getValue('isHouseOccupied') === 'No') {
            familyInfoHTML += createField('Caretaker Name', getValue('caretakerName'));
            familyInfoHTML += createField('Caretaker Contact', getValue('caretakerContact'));
        }
        familyInfoHTML += '</div>';
    }
    
    document.getElementById('reviewFamilyInfo').innerHTML = familyInfoHTML;
    
    // 4. Household Information
    let householdHTML = '<div class="review-fields-grid">';
    const householdHeadValue = getValue('householdHeadValue');
    householdHTML += createField('Household Head?', householdHeadValue);

    if (householdHeadValue === 'Yes') {
        householdHTML += createField('Household Number', getValue('householdNumber'));
        const ownership = form.querySelector('input[name="ownershipStatus"]:checked')?.value || 'Owned';
        householdHTML += createField('Ownership Status', ownership);
        if (ownership === 'Rent') {
            householdHTML += createField("Landlord's Name", getValue('landlordName'));
        }
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
        householdHTML += createField('Water Source', selectedHouseholdData.water_source_type);
        householdHTML += createField('Toilet Facility', selectedHouseholdData.toilet_facility_type);
        householdHTML += `<div style="display: none;">${createField('Updated At', selectedHouseholdData.updated_at)}</div>`;
    } else if (householdHeadValue === 'No') {
        householdHTML += '<div class="review-field"><div class="review-field-value" style="color: #f59e0b;"><i class="fas fa-exclamation-triangle"></i> No household selected</div></div>';
    }

    householdHTML += '</div>';
    document.getElementById('reviewHouseholdInfo').innerHTML = householdHTML;

    // 5. Education & Employment
    let educationHTML = '<div class="review-fields-grid">';
    if (currentAge === null || currentAge >= 10) {
        educationHTML += createField('Educational Attainment', getValue('educationalAttainment'));
    }
    if (currentAge >= 15) {
        educationHTML += createField('Employment Status', getValue('employmentStatus'));
        educationHTML += createField('Occupation', getValue('occupation'));
    }
    educationHTML += '</div>';
    
    document.getElementById('reviewEducationEmployment').innerHTML = educationHTML;
    
    // 6. Additional Information
    let additionalHTML = '';
    
    // Government Programs (Adults only)
    if (currentAge >= 15) {
        additionalHTML += '<h5 style="margin: 0 0 15px 0; color: var(--primary-color);"><i class="fas fa-landmark"></i> Government Programs</h5>';
        additionalHTML += '<div class="review-fields-grid">';
        additionalHTML += createField('4Ps Member', getValue('fourPs'));
        if (getValue('fourPs') === 'Yes') {
            additionalHTML += createField('4Ps ID Number', getValue('fourpsId'));
        }
        additionalHTML += createField('Registered Voter', getValue('voterStatus'));
        if (getValue('voterStatus') === 'Yes') {
            additionalHTML += createField('Precinct Number', getValue('precinctNumber'));
        }
        additionalHTML += '</div>';
    }
    
    // Health Information
    additionalHTML += '<h5 style="margin: 20px 0 15px 0; color: var(--primary-color);"><i class="fas fa-heartbeat"></i> Health Information</h5>';
    additionalHTML += '<div class="review-fields-grid">';
    if (!isMinor) {
        additionalHTML += createField('Philhealth ID', getValue('philhealthId'));
        additionalHTML += createField('Membership Type', getValue('membershipType'));
        additionalHTML += createField('Philhealth Category', getValue('philhealthCategory'));
    }
    additionalHTML += createField('Age/Health Group', getValue('ageHealthGroup'));
    additionalHTML += createField('Disability Status', getValue('pwdStatus'));
    if (getValue('pwdStatus') === 'Yes') {
        additionalHTML += createField('Type of Disability', getValue('pwdType'));
        additionalHTML += createField('PWD ID Number', getValue('pwdIdNumber'));
    }
    additionalHTML += createField('Medical History', getValue('medicalHistory'));
    additionalHTML += '</div>';
    
    // Women's Reproductive Health (if applicable - Female aged 15-49)
    const sex = getValue('sex');
    const isWRAage = currentAge !== null && currentAge >= 15 && currentAge <= 49;
    if (sex === 'Female' && isWRAage) {
        additionalHTML += '<h5 style="margin: 20px 0 15px 0; color: var(--primary-color);"><i class="fas fa-female"></i> Women\'s Reproductive Health</h5>';
        additionalHTML += '<div class="review-fields-grid">';
        additionalHTML += createField('Last Menstrual Period', getValue('lmpDate'));
        additionalHTML += createField('Using FP Method', getValue('usingFpMethod'));
        additionalHTML += createField('FP Methods Used', getValue('fpMethodsUsed'));
        additionalHTML += createField('FP Status', getValue('fpStatus'));
        additionalHTML += '</div>';
    }
    
    // Remarks
    additionalHTML += '<h5 style="margin: 20px 0 15px 0; color: var(--primary-color);"><i class="fas fa-sticky-note"></i> Additional Notes</h5>';
    additionalHTML += '<div class="review-fields-grid">';
    additionalHTML += createField('Remarks', getValue('remarks'));
    additionalHTML += '</div>';
    
    document.getElementById('reviewAdditionalInfo').innerHTML = additionalHTML;

    // Show/hide minor consent note in the privacy modal
    if (minorConsentNote) {
        minorConsentNote.style.display = isMinor ? 'block' : 'none';
    }
}

function submitFormFromReview() {
    const finalSubmitBtn = document.getElementById('finalSubmitBtn');
    const originalBtnText = finalSubmitBtn.innerHTML;
    
    // Show loading state
    finalSubmitBtn.disabled = true;
    finalSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    const form = document.getElementById('createResidentForm');
    
    // Temporarily enable disabled fields so they are included in FormData
    const disabledElements = Array.from(form.querySelectorAll(':disabled'));
    disabledElements.forEach(el => el.disabled = false);
    
    const formData = new FormData(form);
    
    // Re-disable them
    disabledElements.forEach(el => el.disabled = true);
    
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
                    if (element.tagName === 'SELECT' && formData[name]) {
                        // Check if option exists, if not, append it
                        let optionExists = Array.from(element.options).some(opt => opt.value === formData[name]);
                        if (!optionExists) {
                            const newOption = document.createElement('option');
                            newOption.value = formData[name];
                            newOption.textContent = formData[name];
                            const otherOption = Array.from(element.options).find(opt => opt.value === 'Other');
                            if (otherOption) {
                                element.insertBefore(newOption, otherOption);
                            } else {
                                element.appendChild(newOption);
                            }
                        }
                    }
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
// Navigation Guard
// ===================================
function initializeNavigationGuard() {
    // 1. Handle Browser Back Button
    // Push a dummy state so we can intercept the back button
    if (window.history && window.history.pushState) {
        // Prevent pushing duplicate state on refresh
        if (window.history.state !== 'resident_form_guard') {
            window.history.pushState('resident_form_guard', null, window.location.href);
        }
        
        window.addEventListener('popstate', function(e) {
            // Check if form has data
            if (hasSavedData()) {
                // Custom confirmation
                if (confirm('Do you want to cancel your resident input? All entered data will be cleared.')) {
                    clearSavedStep(); // Clear storage
                    // Go back again to actually leave the page
                    setTimeout(() => {
                        window.history.back();
                    }, 10);
                } else {
                    // User wants to stay, push state back to trap navigation again
                    window.history.pushState('forward', null, window.location.href);
                    window.history.pushState('resident_form_guard', null, window.location.href);
                }
            } else {
                // No data, just go back
                window.history.back();
                setTimeout(() => {
                    window.history.back();
                }, 10);
            }
        });
    }

    // 2. Handle internal links (sidebar, back button, etc.)
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        
        // If clicked on a link that navigates away
        if (link && link.href && !link.href.startsWith('javascript') && link.getAttribute('href') !== '#' && !link.hasAttribute('download') && !link.target) {
            
            // Check if form has data
            if (hasSavedData()) {
                // Check if the link is just a hash change or same page
                const currentUrl = window.location.href.split('#')[0];
                const targetUrl = link.href.split('#')[0];
                
                if (currentUrl !== targetUrl) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Custom confirmation
                    if (confirm('Do you want to cancel your resident input? All entered data will be cleared.')) {
                        clearSavedStep(); // Clear storage
                        window.location.href = link.href; // Proceed
                    }
                }
            }
        }
    });
    
    // 3. Handle Page Refresh / Close Tab
    window.addEventListener('beforeunload', function(e) {
        if (hasSavedData()) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });
}

function hasSavedData() {
    const savedData = localStorage.getItem('createResidentFormData');
    if (!savedData) return false;
    try {
        const parsed = JSON.parse(savedData);
        // Check if any value is not empty/false/null
        return Object.values(parsed).some(val => val !== '' && val !== false && val !== null);
    } catch (e) {
        return false;
    }
}
