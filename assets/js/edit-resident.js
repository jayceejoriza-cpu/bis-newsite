let isEditModeActive = false;
let formIsDirty = false;

// Set active navigation
document.addEventListener('DOMContentLoaded', () => {
    // Track form changes
    const form = document.getElementById('inlineEditForm');
    if (form) {
        form.addEventListener('input', () => {
            if (isEditModeActive) formIsDirty = true;
        });
        form.addEventListener('change', () => {
            if (isEditModeActive) formIsDirty = true;
        });
    }

    // Navigation Guards
    window.addEventListener('beforeunload', function(e) {
        if (isEditModeActive && formIsDirty) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });

    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (link && link.href && !link.href.startsWith('javascript') && !link.getAttribute('href').startsWith('#') && !link.hasAttribute('download') && !link.target) {
            if (isEditModeActive && formIsDirty) {
                const currentUrl = window.location.href.split('#')[0];
                const targetUrl = link.href.split('#')[0];
                if (currentUrl !== targetUrl) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (confirm('You have unsaved changes. Are you sure you want to leave?')) {
                        isEditModeActive = false;
                        formIsDirty = false;
                        window.location.href = link.href;
                    }
                }
            }
        }
    });
    
    // Add listener to DOB input for age visibility
    const dobInput = document.querySelector('input[name="date_of_birth"]');
    if (dobInput) {
        dobInput.addEventListener('change', updateProfileAgeVisibility);
    }
    // Initial visibility update
    updateProfileAgeVisibility();

    // Smooth scroll for sidebar navigation
    document.querySelectorAll('.profile-nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Remove active class from all items
            document.querySelectorAll('.profile-nav-item').forEach(nav => {
                nav.classList.remove('active');
            });
            
            // Add active class to clicked item
            item.classList.add('active');
            
            // Smooth scroll to section
            const targetId = item.getAttribute('href');
            const targetSection = document.querySelector(targetId);
            if (targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
    
    // Update active nav on scroll
    const sections = document.querySelectorAll('.profile-section');
    const navItems = document.querySelectorAll('.profile-nav-item');
    
    window.addEventListener('scroll', () => {
        let current = '';
        let minDistance = Infinity;
        
        // Find the section that is most in view (closest to top of viewport)
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            const scrollPosition = window.pageYOffset + 220; // Account for header/offset
            
            // Calculate distance from scroll position to section top
            const distance = Math.abs(scrollPosition - sectionTop);
            
            // If this section is closer to the scroll position and is visible
            if (distance < minDistance && scrollPosition >= sectionTop - 100) {
                minDistance = distance;
                current = section.getAttribute('id');
            }
        });
        
        // Update active state
        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('href') === '#' + current) {
                item.classList.add('active');
            }
        });
    });

    // NEW: Check URL hash on page load and trigger click
    if (window.location.hash === '#household-details') {
        const householdDetailsNavItem = document.querySelector('.profile-nav-item[href="#household-details"]');
        if (householdDetailsNavItem) {
            // Programmatically click the nav item to trigger its event listener
            householdDetailsNavItem.click();
        }
    }
    
    // Check for edit parameter in URL on load
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('edit') === '1') {
        const editBtn = document.querySelector('.btn-primary.view-action');
        if (editBtn) { // Only trigger if they have permission to edit
            toggleEditMode(true);
        }
    }
});

function updateProfileAgeVisibility() {
    const dobInput = document.querySelector('input[name="date_of_birth"]');
    let age = 0;
    if (dobInput && dobInput.value) {
        const dob = new Date(dobInput.value);
        const today = new Date();
        age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
    } else {
        age = window.RESIDENT_AGE || 0;
    }

    const isMinor = age < 18;
    const is15Plus = age >= 15;
    const is10Plus = age >= 10;

    document.querySelectorAll('.adult-only').forEach(el => {
        el.style.display = isMinor ? 'none' : '';
        el.querySelectorAll('input, select, textarea').forEach(input => input.disabled = isMinor);
    });

    document.querySelectorAll('.minor-only').forEach(el => {
        el.style.display = isMinor ? '' : 'none';
        el.querySelectorAll('input, select, textarea').forEach(input => input.disabled = !isMinor);
    });

    document.querySelectorAll('.voter-only').forEach(el => {
        el.style.display = is15Plus ? '' : 'none';
        el.querySelectorAll('input, select, textarea').forEach(input => input.disabled = !is15Plus);
    });

    document.querySelectorAll('.age-10-plus').forEach(el => {
        el.style.display = is10Plus ? '' : 'none';
        el.querySelectorAll('input, select, textarea').forEach(input => input.disabled = !is10Plus);
    });

    document.querySelectorAll('.gov-programs-section').forEach(el => {
        el.style.display = is15Plus ? '' : 'none';
    });
}

function toggleEditMode(enable) {
    isEditModeActive = enable;
    if (!enable) {
        formIsDirty = false;
    }

    const viewFields = document.querySelectorAll('.view-field');
    const editFields = document.querySelectorAll('.edit-field');
    const viewActions = document.querySelectorAll('.view-action');
    const editActions = document.querySelectorAll('.edit-action');
    
    // Update URL parameter
    const url = new URL(window.location);
    if (enable) {
        url.searchParams.set('edit', '1');
    } else {
        url.searchParams.delete('edit');
    }
    window.history.replaceState({}, '', url);
    
    if (enable) {
        viewFields.forEach(f => f.style.display = 'none');
        editFields.forEach(f => f.style.display = 'block');
        viewActions.forEach(a => a.style.display = 'none');
        editActions.forEach(a => a.style.display = 'inline-flex');
        if (typeof toggleOtherReligion === 'function') toggleOtherReligion();
        if (typeof toggleOtherEthnicity === 'function') toggleOtherEthnicity();
        if (typeof handleCivilStatusChange === 'function') handleCivilStatusChange();
        if (typeof toggleOtherFpMethod === 'function') toggleOtherFpMethod();
    } else {
        viewFields.forEach(f => f.style.display = 'block');
        editFields.forEach(f => f.style.display = 'none');
        viewActions.forEach(a => a.style.display = 'inline-flex');
        editActions.forEach(a => a.style.display = 'none');
        document.querySelectorAll('.edit-field-conditional').forEach(f => f.style.display = 'none');
    }
}

function cancelEditMode() {
    if (formIsDirty) {
        if (!confirm('You have unsaved changes. Are you sure you want to cancel?')) {
            return;
        }
    }
    const url = new URL(window.location);
    url.searchParams.delete('edit');
    window.location.href = url.toString();
}

function toggleOtherReligion() {
    const select = document.getElementById('religionSelect');
    const otherInput = document.getElementById('religionOther');
    if (select && otherInput) {
        if (select.value === 'Other') {
            otherInput.style.display = 'block';
        } else {
            otherInput.style.display = 'none';
        }
    }
}

function toggleOtherEthnicity() {
    const select = document.getElementById('ethnicitySelect');
    const otherInput = document.getElementById('ethnicityOther');
    if (select && otherInput) {
        if (select.value === 'Other') {
            otherInput.style.display = 'block';
        } else {
            otherInput.style.display = 'none';
        }
    }
}

function toggleOtherFpMethod() {
    const select = document.getElementById('fpMethodSelect');
    const otherInput = document.getElementById('fpMethodOther');
    if (select && otherInput) {
        if (select.value === 'Other') {
            otherInput.style.display = 'block';
        } else {
            otherInput.style.display = 'none';
        }
    }
}

function handleCivilStatusChange() {
    const civilStatusSelect = document.getElementById('civilStatusSelect');
    const spouseLabel = document.getElementById('spouseNameLabel');
    const spouseInput = document.getElementById('spouseNameInput');
    
    if (civilStatusSelect && spouseLabel && spouseInput) {
        if (civilStatusSelect.value === 'Married') {
            spouseLabel.innerHTML = 'Spouse Name <span style="color:red;">*</span>';
            spouseInput.required = true;
        } else {
            spouseLabel.innerHTML = 'Spouse Name';
            spouseInput.required = false;
        }
    }
}

function saveProfile() {
    const form = document.getElementById('inlineEditForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const rawData = new FormData(form);
    const formData = new FormData();
    
    formData.append('mode', 'update');
    formData.append('residentId', rawData.get('resident_id'));
    
    // Personal Details
    formData.append('firstName', rawData.get('first_name') || '');
    formData.append('middleName', rawData.get('middle_name') || '');
    formData.append('lastName', rawData.get('last_name') || '');
    formData.append('suffix', rawData.get('suffix') || '');
    formData.append('sex', rawData.get('sex') || '');
    formData.append('dateOfBirth', rawData.get('date_of_birth') || '');
    formData.append('placeOfBirth', rawData.get('place_of_birth') || '');
    
    if (rawData.get('religion_select') === 'Other') {
        formData.append('religion', rawData.get('religion_other') || '');
    } else {
        formData.append('religion', rawData.get('religion_select') || '');
    }
    
    if (rawData.get('ethnicity_select') === 'Other') {
        formData.append('ethnicity', rawData.get('ethnicity_other') || '');
    } else {
        formData.append('ethnicity', rawData.get('ethnicity_select') || '');
    }
    
    // Contact
    formData.append('mobileNumber', rawData.get('mobile_number') || '');
    formData.append('streetName', rawData.get('street_name') || '');
    formData.append('houseNo', '');
    formData.append('purok', rawData.get('purok') || '');
    
    // Family
    formData.append('civilStatus', rawData.get('civil_status') || '');
    formData.append('spouseName', rawData.get('spouse_name') || '');
    formData.append('fatherName', rawData.get('father_name') || '');
    formData.append('motherName', rawData.get('mother_name') || '');
    formData.append('numberOfChildren', rawData.get('number_of_children') || '0');
    
    // Education & Employment
    formData.append('educationalAttainment', rawData.get('educational_attainment') || '');
    formData.append('employmentStatus', rawData.get('employment_status') || '');
    formData.append('occupation', rawData.get('occupation') || '');
    formData.append('monthlyIncome', rawData.get('monthly_income') || '');
    
    // Government Programs
    formData.append('fourPs', rawData.get('fourps_member') || 'No');
    formData.append('fourpsId', rawData.get('fourps_id') || '');
    formData.append('voterStatus', rawData.get('voter_status') || 'No');
    formData.append('precinctNumber', rawData.get('precinct_number') || '');
    
    // Health Info
    formData.append('philhealthId', rawData.get('philhealth_id') || '');
    formData.append('membershipType', rawData.get('membership_type') || '');
    formData.append('philhealthCategory', rawData.get('philhealth_category') || '');
    formData.append('ageHealthGroup', rawData.get('age_health_group') || '');
    formData.append('medicalHistory', rawData.get('medical_history') || '');
    
    // WRA
    formData.append('lmpDate', rawData.get('lmp_date') || '');
    formData.append('usingFpMethod', rawData.get('using_fp_method') || '');
    if (rawData.has('fp_methods_select')) {
        formData.append('fpMethodsUsed', rawData.get('fp_methods_select') === 'Other' ? rawData.get('fp_methods_other') : rawData.get('fp_methods_select'));
    } else {
        formData.append('fpMethodsUsed', '');
    }
    formData.append('fpStatus', rawData.get('fp_status') || '');
    
    formData.append('remarks', rawData.get('remarks') || '');
    
    // Pending Household Fields
    formData.append('pending_household_action', rawData.get('pending_household_action') || '');
    formData.append('pending_household_head_value', rawData.get('pending_household_head_value') || '');
    formData.append('pending_household_number', rawData.get('pending_household_number') || '');
    formData.append('pending_household_contact', rawData.get('pending_household_contact') || '');
    formData.append('pending_household_address', rawData.get('pending_household_address') || '');
    formData.append('pending_water_source', rawData.get('pending_water_source') || '');
    formData.append('pending_toilet_facility', rawData.get('pending_toilet_facility') || '');
    formData.append('pending_selected_household_id', rawData.get('pending_selected_household_id') || '');
    formData.append('pending_household_relationship', rawData.get('pending_household_relationship') || '');

    // Hidden required fields from original DB state
    formData.append('pwdStatus', window.RESIDENT_DATA.pwdStatus);
    formData.append('verificationStatus', window.RESIDENT_DATA.verificationStatus);
    formData.append('activityStatus', window.RESIDENT_DATA.activityStatus);
    formData.append('rejectionReason', window.RESIDENT_DATA.rejectionReason);
    formData.append('statusRemarks', window.RESIDENT_DATA.statusRemarks);
    formData.append('guardianName', rawData.get('guardian_name') || '');
    formData.append('guardianRelationship', rawData.get('guardian_relationship') || '');
    formData.append('guardianContact', rawData.get('guardian_contact') || '');
    formData.append('existingPhoto', window.RESIDENT_DATA.existingPhoto);
    
    const saveBtn = document.querySelector('.btn-success.edit-action');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    saveBtn.disabled = true;
    
    fetch('model/save_resident.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            formIsDirty = false;
            showNotification('Resident profile updated successfully!', 'success');
            setTimeout(() => {
                const url = new URL(window.location);
                url.searchParams.delete('edit');
                window.location.href = url.toString();
            }, 1500);
        } else {
            showNotification('Error updating profile: ' + data.message, 'error');
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while saving the profile.', 'error');
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}

function deleteHousehold(householdId) {
    if (!isEditModeActive) {
        showNotification('Please click "Edit Profile" first.', 'warning');
        return;
    }
    if (confirm('Since there are no other members in this household, removing the head will delete the entire household. This will be applied when you click "Save Changes". Proceed?')) {
        document.getElementById('pendingHouseholdAction').value = 'delete_household';
        document.getElementById('pendingSelectedHouseholdId').value = householdId;
        
        const sectionContent = document.querySelector('#household-details .section-content');
        if (sectionContent) {
            sectionContent.innerHTML = `
                <p class="no-data">This household is pending deletion.</p>
            `;
        }
        formIsDirty = true;
    }
}

function removeHouseholdMember(householdId, residentId) {
    if (!isEditModeActive) {
        showNotification('Please click "Edit Profile" first.', 'warning');
        return;
    }
    if (confirm('Are you sure you want to remove this resident from the household? This will be applied when you click "Save Changes".')) {
        document.getElementById('pendingHouseholdAction').value = 'remove';
        document.getElementById('pendingSelectedHouseholdId').value = householdId;
        
        const sectionContent = document.querySelector('#household-details .section-content');
        if (sectionContent) {
            sectionContent.innerHTML = `
                <div class="alert alert-warning" style="margin-bottom: 15px; padding: 10px; border-radius: 5px; background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a;">
                    <i class="fas fa-exclamation-triangle"></i> Pending Household Removal. Click "Save Changes" at the top to apply.
                </div>
                <p class="no-data">This resident is pending removal from the household.</p>
            `;
        }
        formIsDirty = true;
    }
}

function showNotification(message, type = 'info') {
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
    }
    
    notification.innerHTML = `<i class="fas fa-${icon}"></i> <span>${message}</span>`;
    notification.style.cssText = `position:fixed;top:20px;right:20px;background:${bgColor};color:white;padding:15px 20px;border-radius:8px;box-shadow:0 4px 6px rgba(0,0,0,0.1);display:flex;align-items:center;gap:10px;z-index:10000;animation:slideInRight 0.3s ease;`;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease forwards';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Custom Modal Opening Functions
function openAddToHouseholdModal() {
    document.getElementById('addToHouseholdModal').classList.add('active');
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
}
function closeAddToHouseholdModal() {
    document.getElementById('addToHouseholdModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Add to Household Modal Javascript
document.getElementById('householdHeadYes')?.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('householdYesPanel').style.display = 'block';
        document.getElementById('householdNoPanel').style.display = 'none';
        document.getElementById('householdHeadValue').value = 'Yes';
    }
});

document.getElementById('householdHeadNo')?.addEventListener('change', function() {
    if (this.checked) {
        document.getElementById('householdYesPanel').style.display = 'none';
        document.getElementById('householdNoPanel').style.display = 'block';
        document.getElementById('householdHeadValue').value = 'No';
    }
});

document.getElementById('searchHouseholdBtn')?.addEventListener('click', searchHouseholdsProfile);
document.getElementById('householdSearch')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        searchHouseholdsProfile();
    }
});
document.getElementById('clearHouseholdBtn')?.addEventListener('click', clearSelectedHouseholdProfile);

let _profileHouseholdSearchResults = [];

function searchHouseholdsProfile() {
    const query = document.getElementById('householdSearch').value.trim();
    const searchBtn = document.getElementById('searchHouseholdBtn');
    const resultsContainer = document.getElementById('householdSearchResults');
    const resultsList = document.getElementById('householdResultsList');

    if (!query) return;
    searchBtn.disabled = true;
    searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch('model/search_households_for_resident.php?search=' + encodeURIComponent(query))
        .then(res => res.json())
        .then(data => {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i> Search';
            resultsContainer.style.display = 'block';

            if (!data.success || data.data.length === 0) {
                resultsList.innerHTML = '<div style="text-align: center; padding: 10px; color: #64748b;">No households found.</div>';
                return;
            }

            _profileHouseholdSearchResults = data.data;
            let html = '<p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 10px;">Select a household:</p>';

            data.data.forEach((hh, index) => {
                html += `
                <div onclick="selectHouseholdProfile(${index})" style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 14px 16px; margin-bottom: 10px; cursor: pointer; transition: all 0.2s;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;"><strong style="color: var(--primary-color); font-size: 15px;">${hh.household_number}</strong><span style="font-size: 12px; color: var(--text-secondary); background: var(--bg-secondary); padding: 3px 8px; border-radius: 12px; border: 1px solid var(--border-color);">${hh.member_count !== undefined ? hh.member_count + ' members' : ''}</span></div>
                    <div style="color: var(--text-primary); font-weight: 500; font-size: 14px; margin-bottom: 4px;"><i class="fas fa-user-tie" style="color: var(--text-secondary); width: 16px;"></i> ${hh.head_name || 'N/A'}</div>
                    <div style="font-size: 13px; color: var(--text-secondary);"><i class="fas fa-map-marker-alt" style="color: var(--text-secondary); width: 16px;"></i> ${hh.address || 'No address'}</div>
                </div>`; 
            });
            resultsList.innerHTML = html;
        })
        .catch(err => {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i> Search';
            resultsContainer.style.display = 'block';
            resultsList.innerHTML = '<div style="color: #ef4444; padding: 10px;">Error searching households.</div>';
        });
}

function selectHouseholdProfile(index) {
    const hh = _profileHouseholdSearchResults[index];
    document.getElementById('selectedHouseholdId').value = hh.id;
    document.getElementById('householdSearchResults').style.display = 'none';
    document.getElementById('householdSearch').value = '';

    const selectedCard = document.getElementById('selectedHouseholdCard');
    const selectedInfo = document.getElementById('selectedHouseholdInfo');

    selectedInfo.innerHTML = `
        <div class="form-group mb-2"><label style="font-size:12px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;margin-bottom:4px;display:block;">Household Number</label><div style="font-weight:700;color:var(--primary-color);font-size:15px;">${hh.household_number}</div></div>
        <div class="form-group mb-2" style="margin-top:12px;"><label style="font-size:12px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;margin-bottom:4px;display:block;">Household Head</label><div style="font-weight:600;color:var(--text-primary);">${hh.head_name || 'N/A'}</div></div>
        <div class="form-group full-width" style="margin-top:12px;"><label style="font-size:12px;color:var(--text-secondary);font-weight:600;text-transform:uppercase;margin-bottom:4px;display:block;">Address</label><div style="font-size:13px;color:var(--text-primary);">${hh.address || 'N/A'}</div></div>
        <div class="form-group full-width" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-color);">
            <label for="relationshipToHead" style="font-size:13px;font-weight:600;margin-bottom:8px;color:var(--text-primary);display:block;">Relationship to Household Head <span style="color:#ef4444;">*</span></label>
            <input type="text" id="relationshipToHead" class="form-control" placeholder="e.g. Son, Daughter, Spouse, Sibling..." oninput="document.getElementById('householdRelationship').value=this.value;" style="max-width:400px;width:100%;">
        </div>
    `;
    selectedCard.style.display = 'block';
}

function clearSelectedHouseholdProfile() {
    document.getElementById('selectedHouseholdId').value = '';
    document.getElementById('selectedHouseholdCard').style.display = 'none';
    document.getElementById('householdSearch').value = '';
    document.getElementById('householdSearchResults').style.display = 'none';
    document.getElementById('householdRelationship').value = '';
}

function submitAddToHousehold() {
    const form = document.getElementById('addToHouseholdForm');
    const formData = new FormData(form);

    const householdHeadValue = formData.get('householdHeadValue');
    if (!householdHeadValue) {
        showNotification('Please select whether the resident is a Household Head.', 'error');
        return;
    }

    if (householdHeadValue === 'Yes' && !formData.get('householdNumber')) {
        showNotification('Household number is required.', 'error');
        return;
    }

    if (householdHeadValue === 'No') {
        if (!formData.get('selectedHouseholdId')) {
            showNotification('Please select a household to join.', 'error');
            return;
        }
        if (!formData.get('householdRelationship')) {
            showNotification('Please specify the relationship to the household head.', 'error');
            return;
        }
    }

    document.getElementById('pendingHouseholdAction').value = 'add';
    document.getElementById('pendingHouseholdHeadValue').value = householdHeadValue;
    document.getElementById('pendingHouseholdNumber').value = formData.get('householdNumber') || '';
    document.getElementById('pendingHouseholdContact').value = formData.get('householdContact') || '';
    document.getElementById('pendingHouseholdAddress').value = formData.get('householdAddress') || '';
    document.getElementById('pendingWaterSource').value = formData.get('waterSourceType') || '';
    document.getElementById('pendingToiletFacility').value = formData.get('toiletFacilityType') || '';
    document.getElementById('pendingSelectedHouseholdId').value = formData.get('selectedHouseholdId') || '';
    document.getElementById('pendingHouseholdRelationship').value = formData.get('householdRelationship') || '';

    const sectionContent = document.querySelector('#household-details .section-content');
    if (sectionContent) {
        if (householdHeadValue === 'Yes') {
            sectionContent.innerHTML = `
                <div class="household-info-card">
                    <h3 class="subsection-title"><i class="fas fa-info-circle"></i> Household Information (Pending)</h3>
                    <div class="info-grid">
                        <div class="info-item"><label>Household Number</label><p>${formData.get('householdNumber')}</p></div>
                        <div class="info-item"><label>Household Contact</label><p>${formData.get('householdContact') || 'N/A'}</p></div>
                        <div class="info-item full-width"><label>Address</label><p>${formData.get('householdAddress')}</p></div>
                    </div>
                </div>
            `;
        } else {
            sectionContent.innerHTML = `
                <div class="alert alert-warning" style="margin-bottom: 15px; padding: 10px; border-radius: 5px; background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a;">
                    <i class="fas fa-exclamation-triangle"></i> Pending Household Addition. Click "Save Changes" at the top to apply.
                </div>
                <div class="household-info-card">
                    <h3 class="subsection-title"><i class="fas fa-info-circle"></i> Household Information (Pending)</h3>
                    <div class="info-grid">
                        <div class="info-item"><label>Relationship</label><p>${formData.get('householdRelationship')}</p></div>
                    </div>
                </div>
            `;
        }
        const actions = document.querySelector('#household-details .household-actions');
        if (actions) actions.style.display = 'none';
    }

    formIsDirty = true;
    closeAddToHouseholdModal();
    showNotification('Household details queued. Click "Save Changes" to apply.', 'info');
}
// Add notification animation keyframes
if (!document.getElementById('notification-animations')) {
    const style = document.createElement('style');
    style.id = 'notification-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}
