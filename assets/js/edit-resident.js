// ============================================
// Edit Resident JavaScript
// ============================================

// Global variables
let currentStep = 1;
const totalSteps = 6;
let residentData = null;

// Store selected household data for review modal
let selectedHouseholdData = null;

// Webcam state
let webcamActive = false;
let inlineWebcamActive = false;
let capturedPhotoData = null;

// Store search results for safe onclick reference
let _householdSearchResults = [];

// ============================================
// Initialize on page load
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const residentId = document.getElementById('residentId').value;
    loadResidentData(residentId);
    initializeFormNavigation();
    initializeConditionalFields();
    initializePhotoUpload();
    initializeWebcam();
    initializePhoneNumberFormatting();
    initializeIdFormatting();
    initializeHouseholdInfo();
    console.log('Edit Resident page loaded successfully');
});

// ============================================
// Load Resident Data
// ============================================
function loadResidentData(residentId) {
    showLoadingState();
    fetch(`../model/get_resident_details.php?id=${residentId}`)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                residentData = result.data;
                populateForm(residentData);
                hideLoadingState();
            } else {
                showError('Failed to load resident data: ' + result.message);
                setTimeout(() => { window.location.href = '../residents.php'; }, 2000);
            }
        })
        .catch(error => {
            console.error('Error loading resident data:', error);
            showError('An error occurred while loading resident data');
            setTimeout(() => { window.location.href = '../residents.php'; }, 2000);
        });
}

// ============================================
// Populate Form with Resident Data
// ============================================
function populateForm(data) {
    // Helper: safely set value on an element by ID
    const setVal = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.value = (val !== null && val !== undefined) ? val : '';
    };

    // Helper: safely set src on an element by ID
    const setSrc = (id, val) => {
        const el = document.getElementById(id);
        if (el && val) el.src = val;
    };

    // Helper: safely set display style on an element by ID
    const setDisplay = (id, display) => {
        const el = document.getElementById(id);
        if (el) el.style.display = display;
    };

    // Step 1: Personal Details
    setVal('displayResidentId', data.resident_id || 'Auto-generated');
    setVal('firstName', data.first_name);
    setVal('middleName', data.middle_name);
    setVal('lastName', data.last_name);
    setVal('suffix', data.suffix);
    setVal('sex', data.sex);
    setVal('dateOfBirth', data.date_of_birth);
    setVal('religion', data.religion);
    setVal('ethnicity', data.ethnicity);

    // Photo
    if (data.photo) {
        setSrc('photoPreview', data.photo);
        setVal('existingPhoto', data.photo);
    }

    // Step 2: Contact Information
    setVal('mobileNumber', data.mobile_number);
    setVal('email', data.email);
    // Support both separate purok/street_name fields and legacy current_address
    setVal('purok', data.purok);
    setVal('streetName', data.street_name);

    // Step 3: Family Information
    setVal('civilStatus', data.civil_status);
    setVal('spouseName', data.spouse_name);
    setVal('fatherName', data.father_name);
    setVal('motherName', data.mother_name);
    setVal('numberOfChildren', data.number_of_children || 0);

    // Step 5: Education & Employment
    setVal('educationalAttainment', data.educational_attainment);
    setVal('employmentStatus', data.employment_status);
    setVal('occupation', data.occupation);

    // Step 6: Additional Information
    setVal('fourPs', data.fourps_member || 'No');
    setVal('fourpsId', data.fourps_id);
    setVal('voterStatus', data.voter_status);
    setVal('precinctNumber', data.precinct_number);

    if (data.fourps_member === 'Yes') {
        setDisplay('fourpsIdGroup', 'block');
    }
    if (data.voter_status === 'Yes') {
        setDisplay('precinctNumberGroup', 'block');
    }

    // Health Information
    setVal('philhealthId', data.philhealth_id);
    setVal('membershipType', data.membership_type);
    setVal('philhealthCategory', data.philhealth_category);
    setVal('ageHealthGroup', data.age_health_group);
    setVal('medicalHistory', data.medical_history);

    // Women's Reproductive Health (WRA)
    if (data.sex === 'Female') {
        setDisplay('wraSection', 'block');
        setVal('lmpDate', data.lmp_date);
        setVal('usingFpMethod', data.using_fp_method);
        setVal('fpMethodsUsed', data.fp_methods_used);
        setVal('fpStatus', data.fp_status);
        if (data.using_fp_method === 'Yes') {
            setDisplay('fpMethodsGroup', 'block');
            setDisplay('fpStatusGroup', 'block');
        }
    }

    // Step 4: Household Information - pre-populate from DB
    if (data.hm_household_id) {
        if (parseInt(data.is_head) === 1) {
            // Resident is the household head
            const yesRadio = document.getElementById('householdHeadYes');
            if (yesRadio) {
                yesRadio.checked = true;
                setDisplay('householdYesPanel', 'block');
                setDisplay('householdNoPanel', 'none');
                setVal('householdHeadValue', 'Yes');
            }
            setVal('householdNumber', data.household_number);
            setVal('householdContact', data.hh_contact);
            setVal('householdAddress', data.hh_address);
            setVal('waterSourceType', data.hh_water_source_type);
            setVal('toiletFacilityType', data.hh_toilet_facility_type);
        } else {
            // Resident is a household member (not head)
            const noRadio = document.getElementById('householdHeadNo');
            if (noRadio) {
                noRadio.checked = true;
                setDisplay('householdYesPanel', 'none');
                setDisplay('householdNoPanel', 'block');
                setVal('householdHeadValue', 'No');
            }

            // Set hidden household ID and relationship
            const hiddenInput = document.getElementById('selectedHouseholdId');
            if (hiddenInput) hiddenInput.value = data.household_id || '';
            setVal('householdRelationship', data.relationship_to_head);

            // Store household data for review modal
            selectedHouseholdData = {
                id: data.household_id,
                household_number: data.household_number,
                head_name: data.hh_head_name,
                address: data.hh_address,
                household_contact: data.hh_contact,
                water_source_type: data.hh_water_source_type,
                toilet_facility_type: data.hh_toilet_facility_type
            };

            // Show selected household card
            const selectedCard = document.getElementById('selectedHouseholdCard');
            const selectedInfo = document.getElementById('selectedHouseholdInfo');
            if (selectedCard && selectedInfo) {
                selectedInfo.innerHTML =
                    '<div class="form-group"><label style="font-size:12px;color:var(--text-secondary,#64748b);font-weight:600;text-transform:uppercase;">Household Number</label>' +
                    '<div style="font-weight:700;color:var(--primary-color,#3b82f6);">' + (data.household_number || 'N/A') + '</div></div>' +
                    '<div class="form-group"><label style="font-size:12px;color:var(--text-secondary,#64748b);font-weight:600;text-transform:uppercase;">Household Head</label>' +
                    '<div style="font-weight:600;">' + (data.hh_head_name || 'N/A') + '</div></div>' +
                    '<div class="form-group full-width"><label style="font-size:12px;color:var(--text-secondary,#64748b);font-weight:600;text-transform:uppercase;">Address</label>' +
                    '<div style="font-size:13px;">' + (data.hh_address || 'N/A') + '</div></div>' +
                    '<div class="form-group full-width" style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border-color,#e2e8f0);">' +
                    '<label for="relationshipToHead" style="font-size:13px;font-weight:600;margin-bottom:6px;display:block;">Relationship to Household Head <span style="color:#ef4444;">*</span></label>' +
                    '<input type="text" id="relationshipToHead" class="form-control" placeholder="e.g. Son, Daughter, Spouse, Sibling..." oninput="document.getElementById(\'householdRelationship\').value=this.value;" style="max-width:400px;" value="' + (data.relationship_to_head || '') + '">' +
                    '<small style="color:var(--text-secondary,#64748b);font-size:12px;margin-top:4px;display:block;">Enter your relationship to the household head</small></div>';
                selectedCard.style.display = 'block';
            }
        }
    }

    // Remarks
    setVal('remarks', data.remarks);
}

// ============================================
// Initialize Form Navigation
// ============================================
function initializeFormNavigation() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');

    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) showStep(currentStep - 1);
    });

    nextBtn.addEventListener('click', () => {
        if (validateStep(currentStep) && currentStep < totalSteps) {
            showStep(currentStep + 1);
        }
    });
}

// ============================================
// Show Step
// ============================================
function showStep(step) {
    document.querySelectorAll('.form-step').forEach(s => s.classList.remove('active'));

    document.querySelectorAll('.step').forEach((s, index) => {
        const stepNumber = index + 1;
        s.classList.remove('active', 'completed');
        if (stepNumber < step) {
            s.classList.add('completed');
        } else if (stepNumber === step) {
            s.classList.add('active');
        }
    });

    document.querySelectorAll('.step-line').forEach((line, index) => {
        if (index < step - 1) {
            line.classList.add('completed');
        } else {
            line.classList.remove('completed');
        }
    });

    document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');

    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const reviewBtn = document.getElementById('reviewBtn');

    prevBtn.style.display = step === 1 ? 'none' : 'inline-flex';
    nextBtn.style.display = step === totalSteps ? 'none' : 'inline-flex';
    reviewBtn.style.display = step === totalSteps ? 'inline-flex' : 'none';

    currentStep = step;
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================
// Validate Step
// ============================================
function validateStep(step) {
    const currentStepElement = document.querySelector(`.form-step[data-step="${step}"]`);
    if (!currentStepElement) return false;

    const requiredFields = currentStepElement.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        const formGroup = field.closest('.form-group');
        const hint = formGroup ? formGroup.querySelector('.form-hint') : null;

        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            if (hint) hint.classList.add('show-error');
            field.addEventListener('input', function() {
                this.classList.remove('error');
                if (hint) hint.classList.remove('show-error');
            }, { once: true });
        } else {
            field.classList.remove('error');
            if (hint) hint.classList.remove('show-error');
        }
    });

    if (!isValid) {
        showNotification('Please fill in all required fields', 'error');
        const firstInvalid = currentStepElement.querySelector('.error');
        if (firstInvalid) firstInvalid.focus();
    }

    return isValid;
}

// ============================================
// Initialize Conditional Fields
// ============================================
function initializeConditionalFields() {
    // Sex - Show/hide WRA section
    const sexSelect = document.getElementById('sex');
    if (sexSelect) {
        sexSelect.addEventListener('change', function() {
            const wraSection = document.getElementById('wraSection');
            if (this.value === 'Female') {
                wraSection.style.display = 'block';
            } else {
                wraSection.style.display = 'none';
                const lmpDate = document.getElementById('lmpDate');
                const usingFpMethod = document.getElementById('usingFpMethod');
                const fpMethodsUsed = document.getElementById('fpMethodsUsed');
                const fpStatus = document.getElementById('fpStatus');
                if (lmpDate) lmpDate.value = '';
                if (usingFpMethod) usingFpMethod.value = '';
                if (fpMethodsUsed) fpMethodsUsed.value = '';
                if (fpStatus) fpStatus.value = '';
                const fpMethodsGroup = document.getElementById('fpMethodsGroup');
                const fpStatusGroup = document.getElementById('fpStatusGroup');
                if (fpMethodsGroup) fpMethodsGroup.style.display = 'none';
                if (fpStatusGroup) fpStatusGroup.style.display = 'none';
            }
        });
    }

    // 4Ps Member
    const fourPsSelect = document.getElementById('fourPs');
    if (fourPsSelect) {
        fourPsSelect.addEventListener('change', function() {
            const fourpsIdGroup = document.getElementById('fourpsIdGroup');
            if (this.value === 'Yes') {
                fourpsIdGroup.style.display = 'block';
            } else {
                fourpsIdGroup.style.display = 'none';
                document.getElementById('fourpsId').value = '';
            }
        });
    }

    // Voter Status
    const voterStatusSelect = document.getElementById('voterStatus');
    if (voterStatusSelect) {
        voterStatusSelect.addEventListener('change', function() {
            const precinctGroup = document.getElementById('precinctNumberGroup');
            if (this.value === 'Yes') {
                precinctGroup.style.display = 'block';
            } else {
                precinctGroup.style.display = 'none';
                document.getElementById('precinctNumber').value = '';
            }
        });
    }

    // Using FP Method
    const usingFpMethodSelect = document.getElementById('usingFpMethod');
    if (usingFpMethodSelect) {
        usingFpMethodSelect.addEventListener('change', function() {
            const fpMethodsGroup = document.getElementById('fpMethodsGroup');
            const fpStatusGroup = document.getElementById('fpStatusGroup');
            if (this.value === 'Yes') {
                if (fpMethodsGroup) fpMethodsGroup.style.display = 'block';
                if (fpStatusGroup) fpStatusGroup.style.display = 'block';
            } else {
                if (fpMethodsGroup) fpMethodsGroup.style.display = 'none';
                if (fpStatusGroup) fpStatusGroup.style.display = 'none';
                const fpMethodsUsed = document.getElementById('fpMethodsUsed');
                const fpStatus = document.getElementById('fpStatus');
                if (fpMethodsUsed) fpMethodsUsed.value = '';
                if (fpStatus) fpStatus.value = '';
            }
        });
    }

    // Civil Status
    const civilStatusSelect = document.getElementById('civilStatus');
    if (civilStatusSelect) {
        civilStatusSelect.addEventListener('change', function() {
            const spouseNameInput = document.getElementById('spouseName');
            if (spouseNameInput) {
                if (this.value === 'Married') {
                    spouseNameInput.required = true;
                    spouseNameInput.parentElement.querySelector('label').innerHTML = 'Spouse Name <span class="required">*</span>';
                } else {
                    spouseNameInput.required = false;
                    spouseNameInput.parentElement.querySelector('label').innerHTML = 'Spouse Name';
                }
            }
        });
    }
}

// ============================================
// Initialize Photo Upload
// ============================================
function initializePhotoUpload() {
    const photoInput = document.getElementById('photoInput');
    const photoPreview = document.getElementById('photoPreview');
    const resetPhotoBtn = document.getElementById('resetPhotoBtn');
    const defaultImage = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Crect fill='%2393c5fd' width='200' height='200'/%3E%3Ctext x='50%25' y='50%25' dominant-baseline='middle' text-anchor='middle' font-family='Arial' font-size='80' fill='%23ffffff'%3E%F0%9F%91%A4%3C/text%3E%3C/svg%3E";

    if (photoInput) {
        photoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showNotification('Please upload a valid image file (JPG, PNG, or GIF)', 'error');
                    photoInput.value = '';
                    return;
                }
                if (file.size > 1048576) {
                    showNotification('File size must be less than 1MB', 'error');
                    photoInput.value = '';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) { photoPreview.src = e.target.result; };
                reader.readAsDataURL(file);
                showNotification('Photo uploaded successfully', 'success');
            }
        });
    }

    if (resetPhotoBtn) {
        resetPhotoBtn.addEventListener('click', function() {
            if (photoInput) photoInput.value = '';
            const existingPhoto = document.getElementById('existingPhoto').value;
            photoPreview.src = existingPhoto ? existingPhoto : defaultImage;
            showNotification('Photo reset', 'info');
        });
    }
}

// ============================================
// Webcam Initialization
// ============================================
function initializeWebcam() {
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

// ============================================
// Inline Webcam Functions
// ============================================
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

    if (photoPreview) photoPreview.style.display = 'none';
    if (inlineWebcamPreview) inlineWebcamPreview.style.display = 'block';
    if (cameraButtonText) cameraButtonText.textContent = 'Stop Camera';
    if (captureInlineBtn) captureInlineBtn.style.display = 'inline-flex';
    if (takePhotoBtn) {
        takePhotoBtn.classList.remove('btn-primary');
        takePhotoBtn.classList.add('btn-secondary');
    }

    setTimeout(() => {
        Webcam.attach('#inlineWebcamPreview');
        inlineWebcamActive = true;
        showNotification('Camera started successfully', 'success');
    }, 100);
}

function stopInlineWebcam() {
    const photoPreview = document.getElementById('photoPreview');
    const inlineWebcamPreview = document.getElementById('inlineWebcamPreview');
    const cameraButtonText = document.getElementById('cameraButtonText');
    const captureInlineBtn = document.getElementById('captureInlineBtn');
    const takePhotoBtn = document.getElementById('takePhotoBtn');

    Webcam.reset();
    inlineWebcamActive = false;

    if (photoPreview) photoPreview.style.display = 'block';
    if (inlineWebcamPreview) {
        inlineWebcamPreview.style.display = 'none';
        inlineWebcamPreview.innerHTML = '';
    }
    if (cameraButtonText) cameraButtonText.textContent = 'Start Camera';
    if (captureInlineBtn) captureInlineBtn.style.display = 'none';
    if (takePhotoBtn) {
        takePhotoBtn.classList.remove('btn-secondary');
        takePhotoBtn.classList.add('btn-primary');
    }
    showNotification('Camera stopped', 'info');
}

function captureInlinePhoto() {
    if (!inlineWebcamActive) {
        showNotification('Camera is not active', 'error');
        return;
    }
    Webcam.snap(function(data_uri) {
        fetch(data_uri)
            .then(res => res.blob())
            .then(blob => {
                if (blob.size > 1048576) {
                    showNotification('Photo size exceeds 1MB. Please try again.', 'error');
                    return;
                }
                const file = new File([blob], 'webcam-photo.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                const photoInput = document.getElementById('photoInput');
                if (photoInput) photoInput.files = dataTransfer.files;
                const photoPreview = document.getElementById('photoPreview');
                if (photoPreview) photoPreview.src = data_uri;
                stopInlineWebcam();
                showNotification('Photo captured successfully!', 'success');
            })
            .catch(error => {
                console.error('Error processing webcam photo:', error);
                showNotification('Error processing photo. Please try again.', 'error');
            });
    });
}

function closeWebcamModal() {
    const modal = document.getElementById('webcamModal');
    if (modal) {
        if (webcamActive) {
            Webcam.reset();
            webcamActive = false;
        }
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        capturedPhotoData = null;
    }
}

function capturePhoto() {
    if (!webcamActive) {
        showNotification('Webcam is not active', 'error');
        return;
    }
    Webcam.snap(function(data_uri) {
        capturedPhotoData = data_uri;
        const capturedImage = document.getElementById('capturedImage');
        if (capturedImage) capturedImage.src = data_uri;
        document.getElementById('webcamContainer').style.display = 'none';
        document.getElementById('capturedImageContainer').style.display = 'block';
        document.getElementById('webcamInitialActions').style.display = 'none';
        document.getElementById('webcamCapturedActions').style.display = 'flex';
        Webcam.freeze();
        showNotification('Photo captured successfully!', 'success');
    });
}

function retakePhoto() {
    document.getElementById('webcamContainer').style.display = 'block';
    document.getElementById('capturedImageContainer').style.display = 'none';
    document.getElementById('webcamInitialActions').style.display = 'flex';
    document.getElementById('webcamCapturedActions').style.display = 'none';
    Webcam.unfreeze();
    capturedPhotoData = null;
}

function useWebcamPhoto() {
    if (!capturedPhotoData) {
        showNotification('No photo captured', 'error');
        return;
    }
    fetch(capturedPhotoData)
        .then(res => res.blob())
        .then(blob => {
            if (blob.size > 1048576) {
                showNotification('Photo size exceeds 1MB. Please try again.', 'error');
                return;
            }
            const file = new File([blob], 'webcam-photo.jpg', { type: 'image/jpeg' });
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            const photoInput = document.getElementById('photoInput');
            if (photoInput) photoInput.files = dataTransfer.files;
            const photoPreview = document.getElementById('photoPreview');
            if (photoPreview) photoPreview.src = capturedPhotoData;
            closeWebcamModal();
            showNotification('Photo added successfully!', 'success');
        })
        .catch(error => {
            console.error('Error processing webcam photo:', error);
            showNotification('Error processing photo. Please try again.', 'error');
        });
}

// Close webcam modal when clicking outside
document.addEventListener('click', (e) => {
    const modal = document.getElementById('webcamModal');
    if (e.target === modal) closeWebcamModal();
});

// ============================================
// Household Information Management
// ============================================
function initializeHouseholdInfo() {
    const yesRadio = document.getElementById('householdHeadYes');
    const noRadio = document.getElementById('householdHeadNo');
    const searchBtn = document.getElementById('searchHouseholdBtn');
    const clearBtn = document.getElementById('clearHouseholdBtn');
    const householdSearch = document.getElementById('householdSearch');

    if (yesRadio) {
        yesRadio.addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('householdYesPanel').style.display = 'block';
                document.getElementById('householdNoPanel').style.display = 'none';
                document.getElementById('householdHeadValue').value = 'Yes';
                autoFillHouseholdContact();
                autoFillHouseholdAddress();
            }
        });
    }

    if (noRadio) {
        noRadio.addEventListener('change', function() {
            if (this.checked) {
                document.getElementById('householdYesPanel').style.display = 'none';
                document.getElementById('householdNoPanel').style.display = 'block';
                document.getElementById('householdHeadValue').value = 'No';
            }
        });
    }

    const mobileInput = document.getElementById('mobileNumber');
    if (mobileInput) {
        mobileInput.addEventListener('input', function() {
            if (yesRadio && yesRadio.checked) autoFillHouseholdContact();
        });
    }

    ['purok', 'streetName'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                if (yesRadio && yesRadio.checked) autoFillHouseholdAddress();
            });
            field.addEventListener('change', function() {
                if (yesRadio && yesRadio.checked) autoFillHouseholdAddress();
            });
        }
    });

    if (searchBtn) searchBtn.addEventListener('click', searchHouseholds);

    if (householdSearch) {
        householdSearch.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchHouseholds();
            }
        });
    }

    if (clearBtn) clearBtn.addEventListener('click', clearSelectedHousehold);
}

function autoFillHouseholdContact() {
    const mobileInput = document.getElementById('mobileNumber');
    const householdContact = document.getElementById('householdContact');
    if (mobileInput && householdContact) householdContact.value = mobileInput.value;
}

function autoFillHouseholdAddress() {
    const purok = document.getElementById('purok') ? document.getElementById('purok').value.trim() : '';
    const streetName = document.getElementById('streetName') ? document.getElementById('streetName').value.trim() : '';
    const parts = [];
    if (purok) parts.push('Purok ' + purok);
    if (streetName) parts.push(streetName);
    const householdAddress = document.getElementById('householdAddress');
    if (householdAddress) householdAddress.value = parts.join(', ');
}

function searchHouseholds() {
    const searchInput = document.getElementById('householdSearch');
    const resultsContainer = document.getElementById('householdSearchResults');
    const resultsList = document.getElementById('householdResultsList');
    const searchBtn = document.getElementById('searchHouseholdBtn');

    if (!searchInput || !resultsContainer || !resultsList) return;

    const query = searchInput.value.trim();
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';

    fetch('../model/search_households_for_resident.php?search=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i> Search';
            resultsContainer.style.display = 'block';

            if (!data.success || data.data.length === 0) {
                resultsList.innerHTML = '<div style="text-align: center; padding: 20px; color: var(--text-secondary, #64748b);"><i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>No households found. Try a different search term.</div>';
                return;
            }

            _householdSearchResults = data.data;

            let html = '<p style="font-size: 13px; color: var(--text-secondary, #64748b); margin-bottom: 10px;">Found ' + data.count + ' household(s). Click to select:</p>';

            data.data.forEach(function(hh, index) {
                html += '<div class="household-result-item" onclick="selectHousehold(_householdSearchResults[' + index + '])" style="background: #fff; border: 1px solid var(--border-color, #e2e8f0); border-radius: 8px; padding: 12px 15px; margin-bottom: 8px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor=\'var(--primary-color, #3b82f6)\'; this.style.background=\'#f0f9ff\';" onmouseout="this.style.borderColor=\'var(--border-color, #e2e8f0)\'; this.style.background=\'#fff\';">';
                html += '<div style="display: flex; justify-content: space-between; align-items: center;">';
                html += '<div><strong style="color: var(--primary-color, #3b82f6);">' + hh.household_number + '</strong>';
                html += '<span style="margin-left: 10px; color: var(--text-primary, #1e293b); font-weight: 500;">' + (hh.head_name || 'N/A') + '</span></div>';
                html += '<span style="font-size: 12px; color: var(--text-secondary, #64748b);">' + hh.member_count + ' member(s)</span></div>';
                html += '<div style="font-size: 13px; color: var(--text-secondary, #64748b); margin-top: 4px;"><i class="fas fa-map-marker-alt"></i> ' + (hh.address || 'No address');
                if (hh.household_contact) html += '&nbsp;&nbsp;<i class="fas fa-phone"></i> ' + hh.household_contact;
                html += '</div></div>';
            });

            resultsList.innerHTML = html;
        })
        .catch(error => {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i> Search';
            resultsContainer.style.display = 'block';
            resultsList.innerHTML = '<div style="color: #ef4444; padding: 10px;">Error searching households. Please try again.</div>';
            console.error('Search error:', error);
        });
}

function selectHousehold(hh) {
    selectedHouseholdData = hh;

    const hiddenInput = document.getElementById('selectedHouseholdId');
    if (hiddenInput) hiddenInput.value = hh.id;

    const resultsContainer = document.getElementById('householdSearchResults');
    if (resultsContainer) resultsContainer.style.display = 'none';

    const selectedCard = document.getElementById('selectedHouseholdCard');
    const selectedInfo = document.getElementById('selectedHouseholdInfo');

    if (selectedCard && selectedInfo) {
        selectedInfo.innerHTML =
            '<div class="form-group"><label style="font-size:12px;color:var(--text-secondary,#64748b);font-weight:600;text-transform:uppercase;">Household Number</label>' +
            '<div style="font-weight:700;color:var(--primary-color,#3b82f6);">' + hh.household_number + '</div></div>' +
            '<div class="form-group"><label style="font-size:12px;color:var(--text-secondary,#64748b);font-weight:600;text-transform:uppercase;">Household Head</label>' +
            '<div style="font-weight:600;">' + (hh.head_name || 'N/A') + '</div></div>' +
            '<div class="form-group full-width"><label style="font-size:12px;color:var(--text-secondary,#64748b);font-weight:600;text-transform:uppercase;">Address</label>' +
            '<div style="font-size:13px;">' + (hh.address || 'N/A') + '</div></div>' +
            '<div class="form-group full-width" style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border-color,#e2e8f0);">' +
            '<label for="relationshipToHead" style="font-size:13px;font-weight:600;margin-bottom:6px;display:block;">Relationship to Household Head <span style="color:#ef4444;">*</span></label>' +
            '<input type="text" id="relationshipToHead" class="form-control" placeholder="e.g. Son, Daughter, Spouse, Sibling..." oninput="document.getElementById(\'householdRelationship\').value=this.value;" style="max-width:400px;">' +
            '<small style="color:var(--text-secondary,#64748b);font-size:12px;margin-top:4px;display:block;">Enter your relationship to the household head</small></div>';

        selectedCard.style.display = 'block';

        const savedRelationship = document.getElementById('householdRelationship');
        if (savedRelationship && savedRelationship.value) {
            const relInput = document.getElementById('relationshipToHead');
            if (relInput) relInput.value = savedRelationship.value;
        }
    }

    showNotification('Household ' + hh.household_number + ' selected', 'success');
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
    showNotification('Household selection cleared', 'info');
}

// ============================================
// Phone Number Formatting
// ============================================
function formatPhoneNumber(value) {
    const numbers = value.replace(/\D/g, '');
    const limited = numbers.substring(0, 10);
    if (limited.length <= 3) return limited;
    if (limited.length <= 6) return limited.substring(0, 3) + ' ' + limited.substring(3);
    return limited.substring(0, 3) + ' ' + limited.substring(3, 6) + ' ' + limited.substring(6);
}

function applyPhoneNumberFormatting(input) {
    if (!input) return;
    input.addEventListener('input', function(e) {
        const cursorPosition = this.selectionStart;
        const oldLength = this.value.length;
        const formatted = formatPhoneNumber(this.value);
        this.value = formatted;
        const newLength = formatted.length;
        const diff = newLength - oldLength;
        this.setSelectionRange(cursorPosition + diff, cursorPosition + diff);
    });
    input.addEventListener('keypress', function(e) {
        if ([8, 9, 27, 13].indexOf(e.keyCode) !== -1 ||
            (e.keyCode === 65 && e.ctrlKey === true) ||
            (e.keyCode === 67 && e.ctrlKey === true) ||
            (e.keyCode === 86 && e.ctrlKey === true) ||
            (e.keyCode === 88 && e.ctrlKey === true)) {
            return;
        }
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    });
}

function initializePhoneNumberFormatting() {
    applyPhoneNumberFormatting(document.getElementById('mobileNumber'));
}

// ============================================
// ID Formatting (4Ps & Philhealth)
// ============================================
function formatFourPsId(input) {
    let value = input.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
    if (value.length > 10) value = value.substring(0, 10);
    if (value.length > 6) value = value.substring(0, 2) + '-' + value.substring(2, 6) + '-' + value.substring(6);
    else if (value.length > 2) value = value.substring(0, 2) + '-' + value.substring(2);
    input.value = value;
}

function formatPhilhealthId(input) {
    let value = input.value.replace(/\D/g, '');
    if (value.length > 12) value = value.substring(0, 12);
    if (value.length > 8) value = value.substring(0, 4) + '-' + value.substring(4, 8) + '-' + value.substring(8);
    else if (value.length > 4) value = value.substring(0, 4) + '-' + value.substring(4);
    input.value = value;
}

function initializeIdFormatting() {
    const fourpsIdInput = document.getElementById('fourpsId');
    if (fourpsIdInput) fourpsIdInput.addEventListener('input', function() { formatFourPsId(this); });
    const philhealthIdInput = document.getElementById('philhealthId');
    if (philhealthIdInput) philhealthIdInput.addEventListener('input', function() { formatPhilhealthId(this); });
}

// ============================================
// Open Review Modal
// ============================================
function openReviewModal() {
    if (!validateStep(currentStep)) return;

    populateReviewModal();
    resetConfirmationCheckbox();
    initializeConfirmationCheckbox();

    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

// ============================================
// Close Review Modal
// ============================================
function closeReviewModal() {
    const modal = document.getElementById('reviewModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        resetConfirmationCheckbox();
    }
}

// ============================================
// Confirmation Checkbox
// ============================================
function resetConfirmationCheckbox() {
    const checkbox = document.getElementById('confirmDetailsCheckbox');
    const submitBtn = document.getElementById('finalSubmitBtn');
    if (checkbox) checkbox.checked = false;
    if (submitBtn) submitBtn.disabled = true;
}

function initializeConfirmationCheckbox() {
    setTimeout(() => {
        const checkbox = document.getElementById('confirmDetailsCheckbox');
        const submitBtn = document.getElementById('finalSubmitBtn');
        if (checkbox && submitBtn) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    submitBtn.disabled = false;
                    submitBtn.removeAttribute('disabled');
                } else {
                    submitBtn.disabled = true;
                    submitBtn.setAttribute('disabled', 'disabled');
                }
            });
        }
    }, 100);
}

// ============================================
// Populate Review Modal
// ============================================
function populateReviewModal() {
    const form = document.getElementById('editResidentForm');
    if (!form) return;

    const getValue = (name) => {
        const el = form.querySelector('[name="' + name + '"]');
        return el ? el.value : '';
    };

    const createField = (label, value) => {
        if (!value || value.trim() === '') return '';
        return '<div class="review-field"><div class="review-field-label">' + label + '</div><div class="review-field-value">' + value + '</div></div>';
    };

    // 1. Personal Information
    let personalHTML = '';
    const photoPreview = document.getElementById('photoPreview');
    if (photoPreview && photoPreview.src && !photoPreview.src.includes('data:image/svg+xml')) {
        personalHTML += '<div class="review-photo"><img src="' + photoPreview.src + '" alt="Resident Photo"></div>';
    }
    personalHTML += '<div class="review-fields-grid">';
    personalHTML += createField('First Name', getValue('firstName'));
    personalHTML += createField('Middle Name', getValue('middleName'));
    personalHTML += createField('Last Name', getValue('lastName'));
    personalHTML += createField('Suffix', getValue('suffix'));
    personalHTML += createField('Sex', getValue('sex'));
    personalHTML += createField('Date of Birth', getValue('dateOfBirth'));
    personalHTML += createField('Religion', getValue('religion'));
    personalHTML += createField('Ethnicity', getValue('ethnicity'));
    personalHTML += '</div>';
    document.getElementById('reviewPersonalInfo').innerHTML = personalHTML;

    // 2. Contact Information
    let contactHTML = '<div class="review-fields-grid">';
    contactHTML += createField('Mobile Number', getValue('mobileNumber'));
    contactHTML += createField('Email Address', getValue('email'));
    const houseNo = getValue('houseNo');
    const purok = getValue('purok');
    const streetName = getValue('streetName');
    const addressParts = [];
    if (houseNo) addressParts.push('House No. ' + houseNo);
    if (purok) addressParts.push('Purok ' + purok);
    if (streetName) addressParts.push(streetName);
    contactHTML += createField('Complete Address', addressParts.join(', '));
    contactHTML += '</div>';
    document.getElementById('reviewContactInfo').innerHTML = contactHTML;

    // 3. Family Information
    let familyHTML = '<div class="review-fields-grid">';
    familyHTML += createField('Civil Status', getValue('civilStatus'));
    familyHTML += createField('Spouse Name', getValue('spouseName'));
    familyHTML += createField("Father's Name", getValue('fatherName'));
    familyHTML += createField("Mother's Maiden Name", getValue('motherName'));
    familyHTML += createField('Number of Children', getValue('numberOfChildren'));
    familyHTML += '</div>';
    document.getElementById('reviewFamilyInfo').innerHTML = familyHTML;

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
        householdHTML += createField('Relationship to Head', getValue('householdRelationship'));
    } else if (householdHeadValue === 'No') {
        householdHTML += '<div class="review-field"><div class="review-field-value" style="color:#f59e0b;"><i class="fas fa-exclamation-triangle"></i> No household selected</div></div>';
    }
    householdHTML += '</div>';
    document.getElementById('reviewHouseholdInfo').innerHTML = householdHTML;

    // 5. Education & Employment
    let educationHTML = '<div class="review-fields-grid">';
    educationHTML += createField('Educational Attainment', getValue('educationalAttainment'));
    educationHTML += createField('Employment Status', getValue('employmentStatus'));
    educationHTML += createField('Occupation', getValue('occupation'));
    educationHTML += '</div>';
    document.getElementById('reviewEducationEmployment').innerHTML = educationHTML;

    // 6. Additional Information
    let additionalHTML = '<h5 style="margin:0 0 15px 0;color:var(--primary-color);"><i class="fas fa-landmark"></i> Government Programs</h5>';
    additionalHTML += '<div class="review-fields-grid">';
    additionalHTML += createField('4Ps Member', getValue('fourPs'));
    additionalHTML += createField('4Ps ID Number', getValue('fourpsId'));
    additionalHTML += createField('Voter Status', getValue('voterStatus'));
    additionalHTML += createField('Precinct Number', getValue('precinctNumber'));
    additionalHTML += '</div>';

    additionalHTML += '<h5 style="margin:20px 0 15px 0;color:var(--primary-color);"><i class="fas fa-heartbeat"></i> Health Information</h5>';
    additionalHTML += '<div class="review-fields-grid">';
    additionalHTML += createField('Philhealth ID', getValue('philhealthId'));
    additionalHTML += createField('Membership Type', getValue('membershipType'));
    additionalHTML += createField('Philhealth Category', getValue('philhealthCategory'));
    additionalHTML += createField('Age/Health Group', getValue('ageHealthGroup'));
    additionalHTML += createField('Medical History', getValue('medicalHistory'));
    additionalHTML += '</div>';

    const sex = getValue('sex');
    if (sex === 'Female') {
        const lmpDate = getValue('lmpDate');
        const usingFpMethod = getValue('usingFpMethod');
        const fpMethodsUsed = getValue('fpMethodsUsed');
        const fpStatus = getValue('fpStatus');
        if (lmpDate || usingFpMethod || fpMethodsUsed || fpStatus) {
            additionalHTML += '<h5 style="margin:20px 0 15px 0;color:var(--primary-color);"><i class="fas fa-female"></i> Women\'s Reproductive Health</h5>';
            additionalHTML += '<div class="review-fields-grid">';
            additionalHTML += createField('Last Menstrual Period', lmpDate);
            additionalHTML += createField('Using FP Method', usingFpMethod);
            additionalHTML += createField('FP Methods Used', fpMethodsUsed);
            additionalHTML += createField('FP Status', fpStatus);
            additionalHTML += '</div>';
        }
    }

    const remarks = getValue('remarks');
    if (remarks) {
        additionalHTML += '<h5 style="margin:20px 0 15px 0;color:var(--primary-color);"><i class="fas fa-sticky-note"></i> Additional Notes</h5>';
        additionalHTML += '<div class="review-fields-grid">';
        additionalHTML += createField('Remarks', remarks);
        additionalHTML += '</div>';
    }

    document.getElementById('reviewAdditionalInfo').innerHTML = additionalHTML;
}

// ============================================
// Submit Form from Review
// ============================================
function submitFormFromReview() {
    const form = document.getElementById('editResidentForm');
    const formData = new FormData(form);
    formData.append('mode', 'update');

    const submitBtn = document.getElementById('finalSubmitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';

    fetch('../model/save_resident.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            closeReviewModal();
            showNotification('Resident updated successfully!', 'success');
            setTimeout(() => {
                window.location.href = '../resident_profile.php?id=' + document.getElementById('residentId').value;
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

// Close review modal when clicking outside
document.addEventListener('click', (e) => {
    const reviewModal = document.getElementById('reviewModal');
    if (e.target === reviewModal) closeReviewModal();
});

// ============================================
// Utility Functions
// ============================================
function showLoadingState() {
    console.log('Loading...');
}

function hideLoadingState() {
    console.log('Loading complete');
}

function showError(message) {
    showNotification(message, 'error');
}

function showNotification(message, type) {
    type = type || 'info';
    document.querySelectorAll('.notification').forEach(function(n) { n.remove(); });

    const notification = document.createElement('div');
    notification.className = 'notification notification-' + type;

    let icon = 'info-circle';
    let bgColor = '#3b82f6';
    if (type === 'success') { icon = 'check-circle'; bgColor = '#10b981'; }
    else if (type === 'error') { icon = 'exclamation-circle'; bgColor = '#ef4444'; }
    else if (type === 'warning') { icon = 'exclamation-triangle'; bgColor = '#f59e0b'; }

    notification.innerHTML = '<i class="fas fa-' + icon + '"></i><span>' + message + '</span>';
    notification.style.cssText = 'position:fixed;top:20px;right:20px;background:' + bgColor + ';color:white;padding:15px 20px;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);display:flex;align-items:center;gap:10px;z-index:10000;animation:slideIn 0.3s ease;max-width:400px;font-size:14px;font-weight:500;';

    document.body.appendChild(notification);
    setTimeout(function() {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(function() { notification.remove(); }, 300);
    }, 3000);
}

// Add animations
const style = document.createElement('style');
style.textContent = '@keyframes slideIn{from{transform:translateX(400px);opacity:0}to{transform:translateX(0);opacity:1}}@keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(400px);opacity:0}}';
document.head.appendChild(style);
