/**
 * Requests Page JavaScript
 * Handles certificate request creation and management
 */

(function() {
    'use strict';

    // Global variables
    let selectedResidentId = null;
    let selectedCertificateId = null;
    let certificatesData = [];
    let residentsData = [];
    let currentCertificateFields = []; // Store current certificate fields
    let currentResidentData = {}; // Store current resident data

    // DOM Elements
    const createRequestBtn = document.getElementById('createRequestBtn');
    const createRequestModal = new bootstrap.Modal(document.getElementById('createRequestModal'));
    const residentSelectionModal = new bootstrap.Modal(document.getElementById('residentSelectionModal'));
    const certificatePreviewModal = new bootstrap.Modal(document.getElementById('certificatePreviewModal'));
    
    const selectResidentBtn = document.getElementById('selectResidentBtn');
    const residentNameInput = document.getElementById('residentNameInput');
    const selectedResidentIdInput = document.getElementById('selectedResidentId');
    const certificateTypeSelect = document.getElementById('certificateType');
    const certificateFeeInput = document.getElementById('certificateFeeInput');
    const dynamicFieldsContainer = document.getElementById('dynamicFieldsContainer');
    const dynamicFieldsContent = document.getElementById('dynamicFieldsContent');
    const certificatePreviewArea = document.getElementById('certificatePreviewArea');
    const previewCertificateBtn = document.getElementById('previewCertificateBtn');
    const printCertificateBtn = document.getElementById('printCertificateBtn');
    const downloadPreviewBtn = document.getElementById('downloadPreviewBtn');
    
    const residentSearchInput = document.getElementById('residentSearchInput');
    const residentsListContainer = document.getElementById('residentsListContainer');

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        initializeEventListeners();
        loadCertificates();
    });

    /**
     * Initialize event listeners
     */
    function initializeEventListeners() {
        // Create request button
        if (createRequestBtn) {
            createRequestBtn.addEventListener('click', function() {
                resetForm();
                createRequestModal.show();
            });
        }

        // Select resident button
        if (selectResidentBtn) {
            selectResidentBtn.addEventListener('click', function() {
                loadResidents();
                residentSelectionModal.show();
            });
        }

        // Certificate type change
        if (certificateTypeSelect) {
            certificateTypeSelect.addEventListener('change', handleCertificateTypeChange);
        }

        // Resident search
        if (residentSearchInput) {
            residentSearchInput.addEventListener('input', debounce(filterResidents, 300));
        }

        // Preview button - Opens modal
        if (previewCertificateBtn) {
            previewCertificateBtn.addEventListener('click', function() {
                if (!selectedResidentId || !selectedCertificateId) {
                    showNotification('Please select a resident and certificate type first', 'warning');
                    return;
                }
                generatePreview();
                certificatePreviewModal.show();
            });
        }

        // Download preview button
        if (downloadPreviewBtn) {
            downloadPreviewBtn.addEventListener('click', handleDownloadPreview);
        }

        // Print button
        if (printCertificateBtn) {
            printCertificateBtn.addEventListener('click', handlePrint);
        }

        // Search and filter functionality
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        const refreshBtn = document.getElementById('refreshBtn');

        if (searchInput) {
            searchInput.addEventListener('input', debounce(handleSearch, 300));
        }

        if (clearSearch) {
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                handleSearch();
            });
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                location.reload();
            });
        }
    }

    /**
     * Load certificates from database
     */
    function loadCertificates() {
        fetch('get_certificates.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    certificatesData = data.certificates;
                    populateCertificateDropdown(data.certificates);
                } else {
                    showNotification('Error loading certificates: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error loading certificates:', error);
                showNotification('Failed to load certificates', 'error');
            });
    }

    /**
     * Populate certificate dropdown
     */
    function populateCertificateDropdown(certificates) {
        if (!certificateTypeSelect) return;

        // Clear existing options except the first one
        certificateTypeSelect.innerHTML = '<option value="">Select certificate type</option>';

        certificates.forEach(cert => {
            const option = document.createElement('option');
            option.value = cert.id;
            option.textContent = cert.title;
            option.dataset.fee = cert.fee;
            option.dataset.fields = cert.fields;
            certificateTypeSelect.appendChild(option);
        });
    }

    /**
     * Handle certificate type change
     */
    function handleCertificateTypeChange(e) {
        const selectedOption = e.target.options[e.target.selectedIndex];
        
        if (selectedOption.value) {
            selectedCertificateId = selectedOption.value;
            
            // Update fee
            const fee = selectedOption.dataset.fee || '0.00';
            certificateFeeInput.value = parseFloat(fee).toFixed(2);
            
            // Load dynamic fields
            const fieldsJson = selectedOption.dataset.fields;
            loadDynamicFields(fieldsJson);
            
            // Generate preview if resident is selected
            if (selectedResidentId) {
                generatePreview();
            }
        } else {
            selectedCertificateId = null;
            certificateFeeInput.value = '0.00';
            dynamicFieldsContainer.style.display = 'none';
            showPreviewPlaceholder();
        }
    }

    /**
     * Load dynamic fields based on certificate
     * Only shows custom fields (not resident fields)
     */
    function loadDynamicFields(fieldsJson) {
        if (!fieldsJson || fieldsJson === 'null') {
            dynamicFieldsContainer.style.display = 'none';
            currentCertificateFields = [];
            return;
        }

        try {
            const fields = JSON.parse(fieldsJson);
            
            if (!fields || fields.length === 0) {
                dynamicFieldsContainer.style.display = 'none';
                currentCertificateFields = [];
                return;
            }

            // Store all fields for preview rendering
            currentCertificateFields = fields;

            dynamicFieldsContent.innerHTML = '';
            
            // Filter to show only custom fields (fields with customField: true)
            const customFields = fields.filter(field => field.customField === true);
            
            if (customFields.length === 0) {
                dynamicFieldsContainer.style.display = 'none';
                return;
            }
            
            // Create input fields only for custom fields
            customFields.forEach(field => {
                const fieldDiv = createDynamicField(field);
                dynamicFieldsContent.appendChild(fieldDiv);
            });

            dynamicFieldsContainer.style.display = 'block';
        } catch (error) {
            console.error('Error parsing fields:', error);
            dynamicFieldsContainer.style.display = 'none';
            currentCertificateFields = [];
        }
    }

    /**
     * Create dynamic field element
     */
    function createDynamicField(field) {
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'form-group mb-3';

        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = field.label || field.fieldLabel;
        if (field.required || field.fieldRequired) {
            label.innerHTML += ' <span class="text-danger">*</span>';
        }

        let input;
        
        // Determine field type - handle both old format and new custom field format
        const fieldType = field.customField ? field.fieldType : (field.type || 'text');
        
        if (fieldType === 'text') {
            input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.name = `field_${field.id || field.fieldName}`;
            input.setAttribute('data-field-name', field.fieldName || field.id);
            input.placeholder = field.label || field.fieldLabel;
            input.required = field.required || field.fieldRequired || false;
        } else if (fieldType === 'number') {
            input = document.createElement('input');
            input.type = 'number';
            input.className = 'form-control';
            input.name = `field_${field.id || field.fieldName}`;
            input.setAttribute('data-field-name', field.fieldName || field.id);
            input.placeholder = field.label || field.fieldLabel;
            input.required = field.required || field.fieldRequired || false;
        } else if (fieldType === 'dropdown') {
            input = document.createElement('select');
            input.className = 'form-control';
            input.name = `field_${field.id || field.fieldName}`;
            input.setAttribute('data-field-name', field.fieldName || field.id);
            input.required = field.required || field.fieldRequired || false;
            
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select ' + (field.label || field.fieldLabel);
            input.appendChild(defaultOption);
            
            const options = field.fieldOptions || field.options;
            if (options && Array.isArray(options)) {
                options.forEach(option => {
                    const opt = document.createElement('option');
                    opt.value = option;
                    opt.textContent = option;
                    input.appendChild(opt);
                });
            }
        }

        // Add change event listener to trigger preview update
        if (input) {
            input.addEventListener('change', function() {
                if (selectedResidentId && selectedCertificateId) {
                    generatePreview();
                }
            });
            
            input.addEventListener('input', function() {
                if (selectedResidentId && selectedCertificateId) {
                    generatePreview();
                }
            });
        }

        fieldDiv.appendChild(label);
        fieldDiv.appendChild(input);

        return fieldDiv;
    }

    /**
     * Load residents from database
     */
    function loadResidents() {
        residentsListContainer.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Loading residents...</p>
            </div>
        `;

        fetch('search_residents.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Handle both 'residents' and 'data' properties for compatibility
                    residentsData = data.residents || data.data || [];
                    displayResidents(residentsData);
                } else {
                    console.error('Error loading residents:', data.error || data.message);
                    showEmptyResidentsState();
                }
            })
            .catch(error => {
                console.error('Error loading residents:', error);
                showEmptyResidentsState();
            });
    }

    /**
     * Display residents list
     */
    function displayResidents(residents) {
        if (!residents || residents.length === 0) {
            showEmptyResidentsState();
            return;
        }

        residentsListContainer.innerHTML = '';

        residents.forEach(resident => {
            const residentItem = createResidentItem(resident);
            residentsListContainer.appendChild(residentItem);
        });
    }

    /**
     * Create resident item element
     */
    function createResidentItem(resident) {
        const div = document.createElement('div');
        div.className = 'resident-item';
        
        const fullName = `${resident.first_name} ${resident.middle_name || ''} ${resident.last_name} ${resident.suffix || ''}`.trim();
        
        div.innerHTML = `
            <div class="resident-info">
                <div class="resident-name">${fullName}</div>
                <div class="resident-details">
                    <span><i class="fas fa-id-card"></i> ${resident.resident_id || 'N/A'}</span>
                    <span><i class="fas fa-phone"></i> ${resident.mobile_number || 'N/A'}</span>
                </div>
            </div>
            <button type="button" class="btn btn-primary btn-select">
                <i class="fas fa-check"></i>
                Select
            </button>
        `;

        const selectBtn = div.querySelector('.btn-select');
        selectBtn.addEventListener('click', function() {
            selectResident(resident, fullName);
        });

        return div;
    }

    /**
     * Select a resident
     */
    function selectResident(resident, fullName) {
        selectedResidentId = resident.id;
        residentNameInput.value = fullName;
        selectedResidentIdInput.value = resident.id;
        
        residentSelectionModal.hide();
        
        // Generate preview if certificate is selected
        if (selectedCertificateId) {
            generatePreview();
        }
        
        showNotification('Resident selected successfully', 'success');
    }

    /**
     * Filter residents based on search
     */
    function filterResidents() {
        const searchTerm = residentSearchInput.value.toLowerCase().trim();
        
        if (!searchTerm) {
            displayResidents(residentsData);
            return;
        }

        const filtered = residentsData.filter(resident => {
            const fullName = `${resident.first_name} ${resident.middle_name || ''} ${resident.last_name}`.toLowerCase();
            const residentId = (resident.resident_id || '').toLowerCase();
            const mobile = (resident.mobile_number || '').toLowerCase();
            
            return fullName.includes(searchTerm) || 
                   residentId.includes(searchTerm) || 
                   mobile.includes(searchTerm);
        });

        displayResidents(filtered);
    }

    /**
     * Show empty residents state
     */
    function showEmptyResidentsState() {
        residentsListContainer.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>No residents found</p>
            </div>
        `;
    }

    /**
     * Generate certificate preview with PDF rendering
     * Includes custom field values from form inputs
     */
    function generatePreview() {
        if (!selectedResidentId || !selectedCertificateId) {
            showNotification('Please select both resident and certificate type', 'warning');
            return;
        }

        // Show loading state
        certificatePreviewArea.innerHTML = `
            <div class="preview-placeholder">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Generating preview...</p>
            </div>
        `;

        // Fetch preview data
        fetch(`get_certificate_preview.php?resident_id=${selectedResidentId}&certificate_id=${selectedCertificateId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Store resident data for later use
                    currentResidentData = data.resident_data;
                    
                    // Get custom field values from form inputs
                    const customFieldValues = {};
                    const dynamicInputs = dynamicFieldsContent.querySelectorAll('input, select, textarea');
                    dynamicInputs.forEach(input => {
                        const fieldName = input.getAttribute('data-field-name');
                        if (fieldName && input.value) {
                            customFieldValues[fieldName] = input.value;
                        }
                    });
                    
                    // Merge resident data with custom field values
                    const combinedData = { ...data.resident_data, ...customFieldValues };
                    
                    if (data.use_html) {
                        // Use HTML preview
                        certificatePreviewArea.innerHTML = `
                            <div class="certificate-preview-content">
                                ${data.preview_html}
                            </div>
                        `;
                    } else {
                        // Render PDF with positioned fields (including custom field values)
                        renderPDFPreview(data.pdf_path, data.fields, combinedData);
                    }
                } else {
                    showPreviewError(data.message);
                }
            })
            .catch(error => {
                console.error('Error generating preview:', error);
                showPreviewError('Failed to generate preview');
            });
    }

    /**
     * Render PDF preview with positioned fields using PDF.js
     */
    function renderPDFPreview(pdfPath, fields, residentData) {
        // Create canvas container
        certificatePreviewArea.innerHTML = `
            <div class="pdf-preview-container" style="position: relative; display: inline-block;">
                <canvas id="previewPdfCanvas"></canvas>
            </div>
        `;

        const canvas = document.getElementById('previewPdfCanvas');
        const context = canvas.getContext('2d');

        // Load PDF using PDF.js
        const loadingTask = pdfjsLib.getDocument(pdfPath);
        
        loadingTask.promise.then(function(pdf) {
            // Render first page
            pdf.getPage(1).then(function(page) {
                const scale = 1.5;
                const viewport = page.getViewport({ scale: scale });

                canvas.height = viewport.height;
                canvas.width = viewport.width;

                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };

                page.render(renderContext).promise.then(function() {
                    // Overlay fields with resident data
                    overlayFieldsOnCanvas(context, fields, residentData, scale);
                });
            });
        }).catch(function(error) {
            console.error('Error loading PDF:', error);
            showPreviewError('Failed to load PDF template');
        });
    }

    /**
     * Overlay fields with resident data on canvas
     */
    function overlayFieldsOnCanvas(context, fields, residentData, scale) {
        if (!fields || fields.length === 0) {
            return;
        }

        fields.forEach(field => {
            // Get field value from resident data
            let fieldValue = residentData[field.fieldName] || field.fieldLabel;

            // Handle date fields with formatting
            if ((field.fieldName === 'date_issued' || field.fieldName === 'date_expired') && residentData[field.fieldName]) {
                const dateValue = residentData[field.fieldName];
                const dateFormat = field.dateFormat || 'YYYY-MM-DD';
                fieldValue = formatDateValue(dateValue, dateFormat);
            }
            // Apply middle name formatting if applicable
            else if ((field.fieldName === 'full_name' || field.fieldName === 'middle_name') && field.middleNameFormat === 'initial') {
                if (field.fieldName === 'full_name') {
                    // Format: "First M. Last Suffix"
                    const firstName = residentData.first_name || '';
                    const middleName = residentData.middle_name || '';
                    const lastName = residentData.last_name || '';
                    const suffix = residentData.suffix || '';
                    
                    let formattedName = firstName;
                    if (middleName) {
                        formattedName += ' ' + middleName.charAt(0).toUpperCase() + '.';
                    }
                    formattedName += ' ' + lastName;
                    if (suffix) {
                        formattedName += ' ' + suffix;
                    }
                    fieldValue = formattedName.trim();
                } else if (field.fieldName === 'middle_name') {
                    // Format: "M."
                    const middleName = residentData.middle_name || '';
                    if (middleName) {
                        fieldValue = middleName.charAt(0).toUpperCase() + '.';
                    }
                }
            }

            // Apply text case transformation
            if (field.textCase === 'uppercase') {
                fieldValue = fieldValue.toUpperCase();
            } else if (field.textCase === 'lowercase') {
                fieldValue = fieldValue.toLowerCase();
            }
            // 'normal' or undefined keeps the original case

            // Apply font styling
            let fontStyle = '';
            if (field.fontBold) fontStyle += 'bold ';
            if (field.fontItalic) fontStyle += 'italic ';
            
            context.font = `${fontStyle}${field.fontSize}px ${field.fontFamily}`;
            context.fillStyle = field.fontColor || '#000000';
            context.textBaseline = 'top';

            // Draw text at specified position
            context.fillText(fieldValue, field.x, field.y);

            // Draw underline if needed
            if (field.fontUnderline) {
                const textWidth = context.measureText(fieldValue).width;
                context.beginPath();
                context.moveTo(field.x, field.y + field.fontSize);
                context.lineTo(field.x + textWidth, field.y + field.fontSize);
                context.strokeStyle = field.fontColor || '#000000';
                context.lineWidth = 1;
                context.stroke();
            }
        });
    }

    /**
     * Format date value according to specified format
     */
    function formatDateValue(dateString, format) {
        const date = new Date(dateString);
        
        if (isNaN(date.getTime())) {
            return dateString; // Return original if invalid date
        }

        const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                       'July', 'August', 'September', 'October', 'November', 'December'];
        
        const day = date.getDate();
        const month = date.getMonth() + 1;
        const year = date.getFullYear();
        const monthName = months[date.getMonth()];
        
        // Add ordinal suffix (st, nd, rd, th)
        const getOrdinal = (n) => {
            const s = ['th', 'st', 'nd', 'rd'];
            const v = n % 100;
            return n + (s[(v - 20) % 10] || s[v] || s[0]);
        };
        
        const pad = (n) => n.toString().padStart(2, '0');
        
        switch (format) {
            case 'YYYY-MM-DD':
                return `${year}-${pad(month)}-${pad(day)}`;
            case 'DD-MM-YYYY':
                return `${pad(day)}-${pad(month)}-${year}`;
            case 'MM-DD-YYYY':
                return `${pad(month)}-${pad(day)}-${year}`;
            case 'YY-MM-DD':
                return `${year.toString().slice(-2)}-${pad(month)}-${pad(day)}`;
            case 'DD-MM-YY':
                return `${pad(day)}-${pad(month)}-${year.toString().slice(-2)}`;
            case 'MM-DD-YY':
                return `${pad(month)}-${pad(day)}-${year.toString().slice(-2)}`;
            case 'Month DD, YYYY':
                return `${monthName} ${pad(day)}, ${year}`;
            case 'DD Month YYYY':
                return `${pad(day)} ${monthName} ${year}`;
            case 'Month DDth, YYYY':
                return `${monthName} ${getOrdinal(day)}, ${year}`;
            case 'DDth':
                return getOrdinal(day);
            case 'Month':
                return monthName;
            case 'YYYY':
                return year.toString();
            default:
                return `${year}-${pad(month)}-${pad(day)}`;
        }
    }

    /**
     * Show preview placeholder
     */
    function showPreviewPlaceholder() {
        certificatePreviewArea.innerHTML = `
            <div class="preview-placeholder">
                <i class="fas fa-file-alt"></i>
                <p>Select a certificate type and resident to preview</p>
            </div>
        `;
    }

    /**
     * Show preview error
     */
    function showPreviewError(message) {
        certificatePreviewArea.innerHTML = `
            <div class="preview-placeholder">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                <p class="text-danger">${message}</p>
            </div>
        `;
    }

    /**
     * Handle download preview
     */
    function handleDownloadPreview() {
        const canvas = certificatePreviewArea.querySelector('canvas');
        if (!canvas) {
            showNotification('No preview available to download', 'warning');
            return;
        }
        
        // Convert canvas to blob and download
        canvas.toBlob(function(blob) {
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'certificate_preview.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
            showNotification('Preview downloaded successfully', 'success');
        });
    }

    /**
     * Handle print - Save to database then print
     */
    function handlePrint() {
        if (!selectedResidentId || !selectedCertificateId) {
            showNotification('Please select both resident and certificate type', 'warning');
            return;
        }

        // Disable button to prevent double submission
        printCertificateBtn.disabled = true;
        printCertificateBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

        // Collect form data
        const formData = new FormData();
        formData.append('resident_id', selectedResidentId);
        formData.append('certificate_id', selectedCertificateId);
        formData.append('certificate_fee', certificateFeeInput.value);
        
        const purposeInput = document.getElementById('purposeInput');
        if (purposeInput) {
            formData.append('purpose', purposeInput.value);
        }

        // Collect dynamic field values
        const fieldValues = {};
        const dynamicInputs = dynamicFieldsContent.querySelectorAll('input, select, textarea');
        dynamicInputs.forEach(input => {
            if (input.name && input.name.startsWith('field_')) {
                fieldValues[input.name] = input.value;
            }
        });
        formData.append('field_values', JSON.stringify(fieldValues));

        // Save to database
        fetch('save_certificate_request.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Certificate request saved successfully! Reference No: ' + data.reference_no, 'success');
                
                // Close the modal
                createRequestModal.hide();
                
                // Open print window with the certificate
                setTimeout(() => {
                    printCertificate(data.request_id);
                }, 500);
                
                // Reload page after a delay to show new request in table
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                showNotification('Error: ' + data.message, 'error');
                printCertificateBtn.disabled = false;
                printCertificateBtn.innerHTML = '<i class="fas fa-print"></i> Print';
            }
        })
        .catch(error => {
            console.error('Error saving certificate request:', error);
            showNotification('Failed to save certificate request', 'error');
            printCertificateBtn.disabled = false;
            printCertificateBtn.innerHTML = '<i class="fas fa-print"></i> Print';
        });
    }

    /**
     * Print certificate
     */
    function printCertificate(requestId) {
        // Generate preview first
        if (!selectedResidentId || !selectedCertificateId) {
            return;
        }

        // Fetch preview data
        fetch(`get_certificate_preview.php?resident_id=${selectedResidentId}&certificate_id=${selectedCertificateId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create a temporary container for printing
                    const printWindow = window.open('', '_blank');
                    printWindow.document.write('<html><head><title>Print Certificate</title>');
                    printWindow.document.write('<style>');
                    printWindow.document.write('body { margin: 0; padding: 20px; display: flex; justify-content: center; align-items: center; }');
                    printWindow.document.write('canvas { max-width: 100%; height: auto; }');
                    printWindow.document.write('@media print { body { padding: 0; } }');
                    printWindow.document.write('</style>');
                    printWindow.document.write('</head><body>');
                    printWindow.document.write('<canvas id="printCanvas"></canvas>');
                    printWindow.document.write('</body></html>');
                    printWindow.document.close();

                    // Render PDF in the print window
                    const canvas = printWindow.document.getElementById('printCanvas');
                    const context = canvas.getContext('2d');

                    if (data.use_html) {
                        // Use HTML preview
                        printWindow.document.body.innerHTML = data.preview_html;
                        setTimeout(() => {
                            printWindow.print();
                            printWindow.close();
                        }, 500);
                    } else {
                        // Load and render PDF
                        const loadingTask = pdfjsLib.getDocument(data.pdf_path);
                        loadingTask.promise.then(function(pdf) {
                            pdf.getPage(1).then(function(page) {
                                const scale = 1.5;
                                const viewport = page.getViewport({ scale: scale });

                                canvas.height = viewport.height;
                                canvas.width = viewport.width;

                                const renderContext = {
                                    canvasContext: context,
                                    viewport: viewport
                                };

                                page.render(renderContext).promise.then(function() {
                                    // Overlay fields
                                    overlayFieldsOnCanvas(context, data.fields, data.resident_data, scale);
                                    
                                    // Print after rendering
                                    setTimeout(() => {
                                        printWindow.print();
                                        printWindow.close();
                                    }, 500);
                                });
                            });
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error generating print preview:', error);
            });
    }

    /**
     * Reset form
     */
    function resetForm() {
        selectedResidentId = null;
        selectedCertificateId = null;
        
        if (residentNameInput) residentNameInput.value = '';
        if (selectedResidentIdInput) selectedResidentIdInput.value = '';
        if (certificateTypeSelect) certificateTypeSelect.value = '';
        if (certificateFeeInput) certificateFeeInput.value = '0.00';
        if (dynamicFieldsContainer) dynamicFieldsContainer.style.display = 'none';
        if (dynamicFieldsContent) dynamicFieldsContent.innerHTML = '';
        
        const purposeInput = document.getElementById('purposeInput');
        if (purposeInput) purposeInput.value = '';
        
        showPreviewPlaceholder();
    }

    /**
     * Handle search in requests table
     */
    function handleSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchTerm = searchInput.value.toLowerCase().trim();
        const tableRows = document.querySelectorAll('#requestsTableBody tr');

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Initialize filter functionality
     */
    function initializeFilters() {
        const filterBtn = document.getElementById('filterBtn');
        const filterPanel = document.getElementById('filterPanel');
        const applyFiltersBtn = document.getElementById('applyFiltersBtn');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');

        if (filterBtn) {
            filterBtn.addEventListener('click', function() {
                if (filterPanel.style.display === 'none' || !filterPanel.style.display) {
                    filterPanel.style.display = 'block';
                    filterBtn.classList.add('active');
                } else {
                    filterPanel.style.display = 'none';
                    filterBtn.classList.remove('active');
                }
            });
        }

        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', applyFilters);
        }

        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearFilters);
        }
    }

    /**
     * Apply filters to requests table
     */
    function applyFilters() {
        const filters = {
            residentID: document.getElementById('filterResidentID').value.toLowerCase().trim(),
            residentName: document.getElementById('filterResidentName').value.toLowerCase().trim(),
            certificate: document.getElementById('filterCertificate').value,
            dateRequest: document.getElementById('filterDateRequest').value
        };

        console.log('Applying filters:', filters);

        const tableRows = document.querySelectorAll('#requestsTableBody tr');
        let visibleCount = 0;

        tableRows.forEach(row => {
            // Skip empty state row
            if (row.cells.length < 6) {
                return;
            }

            const rowData = {
                residentID: row.getAttribute('data-resident-id') || '',
                residentName: row.getAttribute('data-resident-name') || '',
                certificate: row.getAttribute('data-certificate') || '',
                dateRequest: row.getAttribute('data-date-request') || ''
            };

            let shouldShow = true;

            // Resident ID filter
            if (filters.residentID && !rowData.residentID.toLowerCase().includes(filters.residentID)) {
                shouldShow = false;
            }

            // Resident Name filter
            if (filters.residentName && !rowData.residentName.toLowerCase().includes(filters.residentName)) {
                shouldShow = false;
            }

            // Certificate filter
            if (filters.certificate && rowData.certificate !== filters.certificate) {
                shouldShow = false;
            }

            // Date Request filter
            if (filters.dateRequest && rowData.dateRequest) {
                const filterDate = new Date(filters.dateRequest);
                const rowDate = new Date(rowData.dateRequest);
                
                // Compare dates (ignoring time)
                if (filterDate.toDateString() !== rowDate.toDateString()) {
                    shouldShow = false;
                }
            }

            row.style.display = shouldShow ? '' : 'none';
            if (shouldShow) visibleCount++;
        });

        // Count active filters
        const activeFiltersCount = Object.values(filters).filter(v => v !== '').length;

        if (activeFiltersCount > 0) {
            showNotification(`${activeFiltersCount} filter(s) applied - ${visibleCount} request(s) found`, 'success');
        } else {
            showNotification('No filters selected', 'info');
        }
    }

    /**
     * Clear all filters
     */
    function clearFilters() {
        document.getElementById('filterResidentID').value = '';
        document.getElementById('filterResidentName').value = '';
        document.getElementById('filterCertificate').value = '';
        document.getElementById('filterDateRequest').value = '';

        // Show all rows
        const tableRows = document.querySelectorAll('#requestsTableBody tr');
        tableRows.forEach(row => {
            row.style.display = '';
        });

        showNotification('Filters cleared', 'success');
    }

    // Initialize filters when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeFilters);
    } else {
        initializeFilters();
    }

})();
