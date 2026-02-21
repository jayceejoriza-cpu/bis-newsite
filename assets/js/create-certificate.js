/**
 * Create Certificate Page JavaScript
 * PDF Template Editor with Draggable Field Placeholders
 */

document.addEventListener('DOMContentLoaded', function() {
    // ============================================
    // DOM Elements
    // ============================================
    const publishedToggle = document.getElementById('publishedToggle');
    const certificateName = document.getElementById('certificateName');
    const certificateFee = document.getElementById('certificateFee');
    const dateIssued = document.getElementById('dateIssued');
    const dateExpired = document.getElementById('dateExpired');
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    const sectionHeaders = document.querySelectorAll('.section-header');
    const uploadPdfBtn = document.getElementById('uploadPdfBtn');
    const previewBtn = document.getElementById('previewBtn');
    const saveCertificateBtn = document.getElementById('saveCertificateBtn');
    const pdfFileInput = document.getElementById('pdfFileInput');
    const pdfCanvasArea = document.getElementById('pdfCanvasArea');
    const noPdfState = document.getElementById('noPdfState');
    const pdfDisplayContainer = document.getElementById('pdfDisplayContainer');
    const pdfCanvas = document.getElementById('pdfCanvas');
    
    let uploadedPdfFile = null;
    let pdfDocument = null;
    let currentPage = 1;
    let placedFields = [];
    let selectedField = null;
    let draggedElement = null;
    let isDragging = false;
    let dragOffset = { x: 0, y: 0 };
    
    // Custom Field Management Variables (must be declared early for loadCertificateData)
    let customFields = [];
    let customFieldIdCounter = 1;
    
    // Custom Field DOM Elements (must be declared early for addCustomFieldToList)
    const addTextPlaceholderBtn = document.getElementById('addTextPlaceholderBtn');
    const addTextPlaceholderModal = document.getElementById('addTextPlaceholderModal');
    const textPlaceholderLabel = document.getElementById('textPlaceholderLabel');
    const textPlaceholderRequired = document.getElementById('textPlaceholderRequired');
    const addTextPlaceholderSubmit = document.getElementById('addTextPlaceholderSubmit');
    const textPlaceholderList = document.getElementById('textPlaceholderList');
    
    const addNumberPlaceholderBtn = document.getElementById('addNumberPlaceholderBtn');
    const addNumberPlaceholderModal = document.getElementById('addNumberPlaceholderModal');
    const numberPlaceholderLabel = document.getElementById('numberPlaceholderLabel');
    const numberPlaceholderRequired = document.getElementById('numberPlaceholderRequired');
    const addNumberPlaceholderSubmit = document.getElementById('addNumberPlaceholderSubmit');
    const numberPlaceholderList = document.getElementById('numberPlaceholderList');
    
    const addDropdownPlaceholderBtn = document.getElementById('addDropdownPlaceholderBtn');
    const addDropdownPlaceholderModal = document.getElementById('addDropdownPlaceholderModal');
    const dropdownPlaceholderLabel = document.getElementById('dropdownPlaceholderLabel');
    const dropdownPlaceholderOptions = document.getElementById('dropdownPlaceholderOptions');
    const dropdownPlaceholderRequired = document.getElementById('dropdownPlaceholderRequired');
    const addDropdownPlaceholderSubmit = document.getElementById('addDropdownPlaceholderSubmit');
    const dropdownPlaceholderList = document.getElementById('dropdownPlaceholderList');
    
    // ============================================
    // Initialize Dates
    // ============================================
    function initializeDates() {
        const today = new Date().toISOString().split('T')[0];
        dateIssued.value = today;
        
        const nextYear = new Date();
        nextYear.setFullYear(nextYear.getFullYear() + 1);
        dateExpired.value = nextYear.toISOString().split('T')[0];
    }
    
    initializeDates();
    
    // ============================================
    // Load Certificate Data (if editing)
    // ============================================
    if (window.certificateData) {
        loadCertificateData(window.certificateData);
    }
    
    // ============================================
    // Load Certificate Data Function
    // ============================================
    function loadCertificateData(data) {
        console.log('=== LOADING CERTIFICATE DATA ===');
        console.log('Certificate Data:', data);
        console.log('Fields data:', data.fields);
        console.log('Fields type:', typeof data.fields);
        
        // Populate form fields
        certificateName.value = data.title || '';
        certificateFee.value = data.fee || '0.00';
        publishedToggle.checked = data.status === 'Published';
        
        // Load PDF if exists
        if (data.template_content) {
            // Check if it's a file path or base64
            if (data.template_content.startsWith('uploads/') || data.template_content.includes('.pdf')) {
                // It's a file path - fetch the file
                fetch(data.template_content)
                    .then(response => response.blob())
                    .then(blob => {
                        const file = new File([blob], 'certificate.pdf', { type: 'application/pdf' });
                        uploadedPdfFile = file;
                        loadPdf(file);
                    })
                    .catch(error => {
                        console.error('Error loading PDF from path:', error);
                        showNotification('Error loading PDF template', 'error');
                    });
            } else {
                // It's base64 encoded
                try {
                    // Remove data URI prefix if present
                    let base64String = data.template_content;
                    if (base64String.includes('base64,')) {
                        base64String = base64String.split('base64,')[1];
                    }
                    
                    // Decode base64 to binary
                    const binaryString = atob(base64String);
                    const bytes = new Uint8Array(binaryString.length);
                    for (let i = 0; i < binaryString.length; i++) {
                        bytes[i] = binaryString.charCodeAt(i);
                    }
                    
                    // Create blob and file
                    const blob = new Blob([bytes], { type: 'application/pdf' });
                    const file = new File([blob], 'certificate.pdf', { type: 'application/pdf' });
                    uploadedPdfFile = file;
                    loadPdf(file);
                } catch (error) {
                    console.error('Error loading PDF from base64:', error);
                    showNotification('Error loading PDF template', 'error');
                }
            }
        }
        
        // Load placed fields
        if (data.fields) {
            try {
                const fields = typeof data.fields === 'string' ? JSON.parse(data.fields) : data.fields;
                if (Array.isArray(fields)) {
                    // STEP 1: First, identify and load ALL custom field definitions into customFields array
                    // This includes both placed and unplaced custom fields
                    const processedCustomFieldIds = new Set();
                    let maxCustomFieldId = 0;
                    
                    fields.forEach(field => {
                        // Check if this is a custom field (has customField flag or starts with 'custom_')
                        const isCustomField = field.customField === true || 
                                            (field.fieldName && field.fieldName.startsWith('custom_'));
                        
                        if (isCustomField && !processedCustomFieldIds.has(field.fieldName)) {
                            // Extract the numeric ID from custom field names (e.g., 'custom_text_1' -> 1)
                            const idMatch = field.fieldName.match(/custom_(?:text|number|dropdown)_(\d+)/);
                            if (idMatch) {
                                const numericId = parseInt(idMatch[1]);
                                if (numericId > maxCustomFieldId) {
                                    maxCustomFieldId = numericId;
                                }
                            }
                            
                            // Create custom field definition
                            const customField = {
                                id: field.fieldName,
                                type: field.fieldType || field.type || 'text',
                                label: field.fieldLabel || field.label,
                                options: field.fieldOptions || field.options || null,
                                required: field.fieldRequired || field.required || false
                            };
                            
                            // Add to customFields array (avoid duplicates)
                            if (!customFields.find(cf => cf.id === customField.id)) {
                                customFields.push(customField);
                                console.log('Loaded custom field definition:', customField);
                            }
                            
                            processedCustomFieldIds.add(field.fieldName);
                        }
                    });
                    
                    // Update the counter to avoid ID conflicts when adding new fields
                    if (maxCustomFieldId > 0) {
                        customFieldIdCounter = maxCustomFieldId + 1;
                        console.log('Updated customFieldIdCounter to:', customFieldIdCounter);
                    }
                    
                    // STEP 2: Add custom fields to the sidebar lists
                    console.log('Adding custom fields to sidebar. Total custom fields:', customFields.length);
                    customFields.forEach(customField => {
                        console.log('Adding to list:', customField);
                        addCustomFieldToList(customField);
                    });
                    
                    // STEP 3: Wait for PDF to load, then place all fields on canvas
                    setTimeout(() => {
                        fields.forEach(field => {
                            // Only place fields that have x,y coordinates (these are placed on canvas)
                            if (field.x !== undefined && field.y !== undefined) {
                                console.log('Placing field on canvas:', field.fieldName, 'at', field.x, field.y);
                                
                                addFieldPlaceholder(field.fieldName, field.fieldLabel, field.x, field.y);
                                
                                // Update field properties after placement
                                const placedField = placedFields.find(f => f.fieldName === field.fieldName && f.x === field.x && f.y === field.y);
                                if (placedField) {
                                    // Copy all properties from saved field
                                    placedField.fontFamily = field.fontFamily || 'Arial';
                                    placedField.fontSize = field.fontSize || 16;
                                    placedField.fontColor = field.fontColor || '#000000';
                                    placedField.fontBold = field.fontBold || false;
                                    placedField.fontItalic = field.fontItalic || false;
                                    placedField.fontUnderline = field.fontUnderline || false;
                                    placedField.textCase = field.textCase || 'normal';
                                    placedField.middleNameFormat = field.middleNameFormat || 'full';
                                    placedField.dateFormat = field.dateFormat || 'YYYY-MM-DD';
                                    
                                    // If it's a custom field, preserve the custom field metadata
                                    if (field.customField) {
                                        placedField.customField = true;
                                        placedField.fieldType = field.fieldType;
                                        placedField.fieldOptions = field.fieldOptions || null;
                                        placedField.fieldRequired = field.fieldRequired || false;
                                    }
                                    
                                    // Apply visual styles to the DOM element
                                    const element = document.getElementById(placedField.id);
                                    if (element) {
                                        element.style.fontFamily = placedField.fontFamily;
                                        element.style.fontSize = placedField.fontSize + 'px';
                                        element.style.color = placedField.fontColor;
                                        element.style.fontWeight = placedField.fontBold ? 'bold' : 'normal';
                                        element.style.fontStyle = placedField.fontItalic ? 'italic' : 'normal';
                                        element.style.textDecoration = placedField.fontUnderline ? 'underline' : 'none';
                                        
                                        // Apply text case transformation
                                        if (placedField.textCase === 'uppercase') {
                                            element.style.textTransform = 'uppercase';
                                        } else if (placedField.textCase === 'lowercase') {
                                            element.style.textTransform = 'lowercase';
                                        } else {
                                            element.style.textTransform = 'none';
                                        }
                                    }
                                }
                            }
                        });
                        
                        console.log('Certificate loaded - Custom fields:', customFields.length, 'Placed fields:', placedFields.length);
                        showNotification('Certificate loaded successfully', 'success');
                    }, 1000);
                }
            } catch (error) {
                console.error('Error parsing fields:', error);
                showNotification('Error loading certificate fields', 'error');
            }
        }
    }
    
    // ============================================
    // Tab Switching
    // ============================================
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            this.classList.add('active');
            
            const targetContent = document.getElementById(targetTab + 'Tab');
            if (targetContent) {
                targetContent.classList.add('active');
            }
        });
    });
    
    // ============================================
    // Collapsible Sections
    // ============================================
    sectionHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const sectionId = this.getAttribute('data-section');
            const sectionContent = document.getElementById(sectionId);
            
            this.classList.toggle('active');
            sectionContent.classList.toggle('active');
        });
    });
    
    // ============================================
    // PDF Upload Handling
    // ============================================
    uploadPdfBtn.addEventListener('click', function() {
        pdfFileInput.click();
    });
    
    pdfFileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (file && file.type === 'application/pdf') {
            uploadedPdfFile = file;
            loadPdf(file);
            showNotification('PDF uploaded successfully', 'success');
        } else {
            showNotification('Please select a valid PDF file', 'error');
            pdfFileInput.value = '';
        }
    });
    
    // ============================================
    // Load and Display PDF
    // ============================================
    function loadPdf(file) {
        const fileReader = new FileReader();
        
        fileReader.onload = function() {
            const typedarray = new Uint8Array(this.result);
            
            pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
                pdfDocument = pdf;
                renderPage(currentPage);
                
                // Hide no-pdf state, show canvas
                noPdfState.style.display = 'none';
                pdfDisplayContainer.style.display = 'block';
            }).catch(function(error) {
                console.error('Error loading PDF:', error);
                showNotification('Error loading PDF file', 'error');
            });
        };
        
        fileReader.readAsArrayBuffer(file);
    }
    
    // ============================================
    // Render PDF Page
    // ============================================
    function renderPage(pageNumber) {
        pdfDocument.getPage(pageNumber).then(function(page) {
            const scale = 1.589;
            const viewport = page.getViewport({ scale: scale });
            
            const context = pdfCanvas.getContext('2d');
            pdfCanvas.height = viewport.height;
            pdfCanvas.width = viewport.width;
            
            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };
            
            page.render(renderContext).promise.then(function() {
                console.log('Page rendered');
                // Re-render placed fields
                renderPlacedFields();
            });
        });
    }
    
    // ============================================
    // Draggable Fields
    // ============================================
    const draggableFields = document.querySelectorAll('.draggable-field');
    
    draggableFields.forEach(field => {
        field.addEventListener('dragstart', function(e) {
            const fieldName = this.getAttribute('data-field');
            const fieldLabel = this.querySelector('.field-name').textContent;
            
            e.dataTransfer.setData('fieldName', fieldName);
            e.dataTransfer.setData('fieldLabel', fieldLabel);
            e.dataTransfer.effectAllowed = 'copy';
        });
    });
    
    // ============================================
    // PDF Canvas Drop Zone
    // ============================================
    pdfDisplayContainer.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });
    
    pdfDisplayContainer.addEventListener('drop', function(e) {
        e.preventDefault();
        
        const fieldName = e.dataTransfer.getData('fieldName');
        const fieldLabel = e.dataTransfer.getData('fieldLabel');
        
        if (fieldName && fieldLabel) {
            // Get position relative to the canvas ONLY
            const canvasRect = pdfCanvas.getBoundingClientRect();
            const x = e.clientX - canvasRect.left;
            const y = e.clientY - canvasRect.top;
            
            addFieldPlaceholder(fieldName, fieldLabel, x, y);
        }
    });
    
    // ============================================
    // Add Field Placeholder
    // ============================================
    function addFieldPlaceholder(fieldName, fieldLabel, x, y) {
        const fieldId = 'field_' + Date.now();
        
        // Get canvas position within container
        const canvasRect = pdfCanvas.getBoundingClientRect();
        const containerRect = pdfDisplayContainer.getBoundingClientRect();
        const canvasOffsetX = canvasRect.left - containerRect.left;
        const canvasOffsetY = canvasRect.top - containerRect.top;
        
        // Get sample data for this field
        const sampleData = {
            full_name: 'Maria Santos Reyes',
            first_name: 'Maria',
            middle_name: 'Santos',
            last_name: 'Reyes',
            suffix: 'Jr',
            date_of_birth: '01/19/2026',
            age: '25',
            sex: 'Male',
            civil_status: 'Single',
            address: 'Manila City',
            mobile_number: '09123456789',
            email: 'sample@email.com'
        };
        
        const sampleValue = sampleData[fieldName] || fieldLabel;
        
        const placeholder = document.createElement('div');
        placeholder.className = 'field-placeholder';
        placeholder.id = fieldId;
        // Position placeholder relative to container (canvas offset + position on canvas)
        placeholder.style.left = (canvasOffsetX + x) + 'px';
        placeholder.style.top = (canvasOffsetY + y) + 'px';
        placeholder.setAttribute('data-field', fieldName);
        
        placeholder.innerHTML = `
            <div class="field-actions">
                <button class="field-action-btn" onclick="editFieldProperties('${fieldId}')" title="Edit Properties">
                    <i class="fas fa-cog"></i>
                </button>
                <button class="field-action-btn" onclick="removeField('${fieldId}')" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <span class="field-label">${sampleValue}</span>
        `;
        
        // Make it draggable
        makeDraggable(placeholder);
        
        // Add click to select
        placeholder.addEventListener('click', function(e) {
            e.stopPropagation();
            selectField(this);
        });
        
        pdfDisplayContainer.appendChild(placeholder);
        
        // Store ONLY canvas-relative positions
        placedFields.push({
            id: fieldId,
            fieldName: fieldName,
            fieldLabel: fieldLabel,
            x: x,  // Position relative to canvas (0,0 = top-left of canvas)
            y: y,
            fontFamily: 'Arial',
            fontSize: 16,
            fontColor: '#000000',
            fontBold: false,
            fontItalic: false,
            fontUnderline: false,
            textCase: 'normal',
            middleNameFormat: 'full'
        });
        
        showNotification(`${fieldLabel} added to certificate`, 'success');
    }
    
    // ============================================
    // Make Element Draggable
    // ============================================
    function makeDraggable(element) {
        let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
        
        element.onmousedown = dragMouseDown;
        
        function dragMouseDown(e) {
            e.preventDefault();
            e.stopPropagation();
            
            pos3 = e.clientX;
            pos4 = e.clientY;
            
            document.onmouseup = closeDragElement;
            document.onmousemove = elementDrag;
        }
        
        function elementDrag(e) {
            e.preventDefault();
            
            pos1 = pos3 - e.clientX;
            pos2 = pos4 - e.clientY;
            pos3 = e.clientX;
            pos4 = e.clientY;
            
            element.style.top = (element.offsetTop - pos2) + "px";
            element.style.left = (element.offsetLeft - pos1) + "px";
            
            // Update stored position
            updateFieldPosition(element.id, element.offsetLeft, element.offsetTop);
        }
        
        function closeDragElement() {
            document.onmouseup = null;
            document.onmousemove = null;
        }
    }
    
    // ============================================
    // Update Field Position
    // ============================================
    function updateFieldPosition(fieldId, placeholderX, placeholderY) {
        const field = placedFields.find(f => f.id === fieldId);
        if (field) {
            // Convert placeholder position (relative to container) to canvas-relative position
            const canvasRect = pdfCanvas.getBoundingClientRect();
            const containerRect = pdfDisplayContainer.getBoundingClientRect();
            const canvasOffsetX = canvasRect.left - containerRect.left;
            const canvasOffsetY = canvasRect.top - containerRect.top;
            
            // Subtract canvas offset to get position relative to canvas
            field.x = placeholderX - canvasOffsetX;
            field.y = placeholderY - canvasOffsetY;
        }
    }
    
    // ============================================
    // Select Field
    // ============================================
    function selectField(element) {
        // Deselect all
        document.querySelectorAll('.field-placeholder').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Select this one
        element.classList.add('selected');
        selectedField = element.id;
    }
    
    // Deselect when clicking outside
    pdfDisplayContainer.addEventListener('click', function(e) {
        if (e.target === this || e.target === pdfCanvas) {
            document.querySelectorAll('.field-placeholder').forEach(el => {
                el.classList.remove('selected');
            });
            selectedField = null;
        }
    });
    
    // ============================================
    // Edit Field Properties (Global Function)
    // ============================================
    window.editFieldProperties = function(fieldId) {
        const field = placedFields.find(f => f.id === fieldId);
        if (!field) return;
        
        // Populate modal
        document.getElementById('currentFieldId').value = fieldId;
        document.getElementById('fieldLabel').value = field.fieldLabel;
        document.getElementById('fieldFontFamily').value = field.fontFamily;
        document.getElementById('fieldFontSize').value = field.fontSize;
        document.getElementById('fieldFontColor').value = field.fontColor;
        document.getElementById('fieldBold').checked = field.fontBold;
        document.getElementById('fieldItalic').checked = field.fontItalic;
        document.getElementById('fieldUnderline').checked = field.fontUnderline;
        document.getElementById('fieldTextCase').value = field.textCase || 'normal';
        
        // Show/hide middle name format options
        const middleNameGroup = document.getElementById('middleNameFormatGroup');
        if (field.fieldName === 'full_name' || field.fieldName === 'middle_name') {
            middleNameGroup.style.display = 'block';
            if (field.middleNameFormat === 'initial') {
                document.getElementById('middleNameInitial').checked = true;
            } else {
                document.getElementById('middleNameFull').checked = true;
            }
        } else {
            middleNameGroup.style.display = 'none';
        }
        
        // Show/hide date format options
        const dateFormatGroup = document.getElementById('dateFormatGroup');
        if (field.fieldName === 'date_issued' || field.fieldName === 'date_expired') {
            dateFormatGroup.style.display = 'block';
            if (field.dateFormat) {
                document.getElementById('fieldDateFormat').value = field.dateFormat;
            }
        } else {
            dateFormatGroup.style.display = 'none';
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('fieldPropertiesModal'));
        modal.show();
    };
    
    // ============================================
    // Apply Field Properties
    // ============================================
    document.getElementById('applyFieldProperties').addEventListener('click', function() {
        const fieldId = document.getElementById('currentFieldId').value;
        const field = placedFields.find(f => f.id === fieldId);
        
        if (field) {
            field.fontFamily = document.getElementById('fieldFontFamily').value;
            field.fontSize = parseInt(document.getElementById('fieldFontSize').value);
            field.fontColor = document.getElementById('fieldFontColor').value;
            field.fontBold = document.getElementById('fieldBold').checked;
            field.fontItalic = document.getElementById('fieldItalic').checked;
            field.fontUnderline = document.getElementById('fieldUnderline').checked;
            field.textCase = document.getElementById('fieldTextCase').value;
            
            // Get middle name format if applicable
            if (field.fieldName === 'full_name' || field.fieldName === 'middle_name') {
                field.middleNameFormat = document.querySelector('input[name="middleNameFormat"]:checked').value;
            }
            
            // Get date format if applicable
            if (field.fieldName === 'date_issued' || field.fieldName === 'date_expired') {
                field.dateFormat = document.getElementById('fieldDateFormat').value;
            }
            
            // Update visual with text transformation
            const element = document.getElementById(fieldId);
            if (element) {
                element.style.fontFamily = field.fontFamily;
                element.style.fontSize = field.fontSize + 'px';
                element.style.color = field.fontColor;
                element.style.fontWeight = field.fontBold ? 'bold' : 'normal';
                element.style.fontStyle = field.fontItalic ? 'italic' : 'normal';
                element.style.textDecoration = field.fontUnderline ? 'underline' : 'none';
                
                // Apply text case transformation
                if (field.textCase === 'uppercase') {
                    element.style.textTransform = 'uppercase';
                } else if (field.textCase === 'lowercase') {
                    element.style.textTransform = 'lowercase';
                } else {
                    element.style.textTransform = 'none';
                }
                
                // Update text content if middle name format changed
                const labelSpan = element.querySelector('.field-label');
                if (labelSpan && (field.fieldName === 'full_name' || field.fieldName === 'middle_name')) {
                    let sampleValue = '';
                    if (field.fieldName === 'full_name') {
                        if (field.middleNameFormat === 'initial') {
                            sampleValue = 'Maria S. Reyes';
                        } else {
                            sampleValue = 'Maria Santos Reyes';
                        }
                    } else if (field.fieldName === 'middle_name') {
                        if (field.middleNameFormat === 'initial') {
                            sampleValue = 'S.';
                        } else {
                            sampleValue = 'Santos';
                        }
                    }
                    labelSpan.textContent = sampleValue;
                }
                
                // Update text content if date format changed
                if (labelSpan && (field.fieldName === 'date_issued' || field.fieldName === 'date_expired')) {
                    const sampleDate = new Date('2026-02-14');
                    const newSampleValue = formatDateSample(sampleDate, field.dateFormat);
                    labelSpan.textContent = newSampleValue;
                }
            }
            
            showNotification('Field properties updated', 'success');
        }
        
        // Close modal
        bootstrap.Modal.getInstance(document.getElementById('fieldPropertiesModal')).hide();
    });
    
    // ============================================
    // Remove Field (Global Function)
    // ============================================
    window.removeField = function(fieldId) {
        if (confirm('Remove this field from the certificate?')) {
            const element = document.getElementById(fieldId);
            if (element) {
                element.remove();
            }
            
            placedFields = placedFields.filter(f => f.id !== fieldId);
            showNotification('Field removed', 'success');
        }
    };
    
    // ============================================
    // Render Placed Fields
    // ============================================
    function renderPlacedFields() {
        // Re-position all placed fields after PDF re-render
        const canvasRect = pdfCanvas.getBoundingClientRect();
        const containerRect = pdfDisplayContainer.getBoundingClientRect();
        const canvasOffsetX = canvasRect.left - containerRect.left;
        const canvasOffsetY = canvasRect.top - containerRect.top;
        
        placedFields.forEach(field => {
            const element = document.getElementById(field.id);
            if (element) {
                // Position placeholder: canvas offset + position on canvas
                element.style.left = (canvasOffsetX + field.x) + 'px';
                element.style.top = (canvasOffsetY + field.y) + 'px';
            }
        });
    }
    
    // ============================================
    // Save Certificate
    // ============================================
    saveCertificateBtn.addEventListener('click', function() {
        if (!validateForm()) {
            return;
        }
        
        if (!uploadedPdfFile) {
            showNotification('Please upload a PDF template', 'warning');
            return;
        }
        
        if (placedFields.length === 0) {
            showNotification('Please add at least one field to the certificate', 'warning');
            return;
        }
        
        showLoading();
        
        const formData = new FormData();
        
        // Check if editing
        const certificateId = document.getElementById('certificateId');
        if (certificateId) {
            formData.append('certificateId', certificateId.value);
        }
        
        formData.append('certificateName', certificateName.value.trim());
        formData.append('certificateFee', certificateFee.value);
        formData.append('dateIssued', dateIssued.value);
        formData.append('dateExpired', dateExpired.value);
        formData.append('published', publishedToggle.checked ? '1' : '0');
        
        // Build complete fields array: placed fields + unplaced custom field definitions
        const allFields = [];
        
        // First, add all placed fields with their metadata
        placedFields.forEach(field => {
            // Check if this is a custom field
            const customField = customFields.find(cf => cf.id === field.fieldName);
            if (customField) {
                // Add custom field metadata to the placed field
                allFields.push({
                    ...field,
                    customField: true,
                    fieldType: customField.type,
                    fieldLabel: customField.label, // Ensure label is preserved
                    fieldOptions: customField.options || null,
                    fieldRequired: customField.required || false
                });
            } else {
                allFields.push(field);
            }
        });
        
        // Then, add unplaced custom field definitions (fields that exist but aren't on canvas)
        // This ensures the form fields show in requests even if not placed on PDF
        customFields.forEach(customField => {
            // Check if this custom field is already in placedFields
            const isPlaced = placedFields.some(pf => pf.fieldName === customField.id);
            if (!isPlaced) {
                // Add the custom field definition without x,y coordinates
                allFields.push({
                    fieldName: customField.id,
                    fieldLabel: customField.label,
                    customField: true,
                    fieldType: customField.type,
                    fieldOptions: customField.options || null,
                    fieldRequired: customField.required || false
                    // No x, y coordinates - this field is not placed on canvas
                });
            }
        });
        
        console.log('Saving fields - Total:', allFields.length);
        console.log('Placed fields:', placedFields.length);
        console.log('Custom fields:', customFields.length);
        console.log('All fields being saved:', JSON.stringify(allFields, null, 2));
        formData.append('fields', JSON.stringify(allFields));
        formData.append('pdfFile', uploadedPdfFile);
        
        fetch('save_certificate.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            hideLoading();
            
            if (data.success) {
                const message = certificateId ? 'Certificate updated successfully!' : 'Certificate created successfully!';
                showNotification(message, 'success');
                setTimeout(() => {
                    window.location.href = '../certificates.php';
                }, 2000);
            } else {
                showNotification(data.message || 'Failed to save certificate', 'error');
            }
        })
        .catch(error => {
            hideLoading();
            console.error('Error:', error);
            showNotification('An error occurred while saving the certificate', 'error');
        });
    });
    
    // ============================================
    // Form Validation
    // ============================================
    function validateForm() {
        if (!certificateName.value.trim()) {
            showNotification('Please enter a certificate name', 'warning');
            certificateName.focus();
            return false;
        }
        
        if (!dateIssued.value) {
            showNotification('Please select a date issued', 'warning');
            dateIssued.focus();
            return false;
        }
        
        if (!dateExpired.value) {
            showNotification('Please select a date expired', 'warning');
            dateExpired.focus();
            return false;
        }
        
        if (new Date(dateExpired.value) <= new Date(dateIssued.value)) {
            showNotification('Expiration date must be after issue date', 'warning');
            dateExpired.focus();
            return false;
        }
        
        return true;
    }
    
    // ============================================
    // Show Notification
    // ============================================
    function showNotification(message, type = 'info') {
        const existingNotifications = document.querySelectorAll('.success-notification');
        existingNotifications.forEach(notif => notif.remove());
        
        const notification = document.createElement('div');
        notification.className = 'success-notification';
        
        let icon = 'fa-info-circle';
        let bgColor = '#3b82f6';
        
        if (type === 'success') {
            icon = 'fa-check-circle';
            bgColor = '#10b981';
        } else if (type === 'error') {
            icon = 'fa-times-circle';
            bgColor = '#ef4444';
        } else if (type === 'warning') {
            icon = 'fa-exclamation-triangle';
            bgColor = '#f59e0b';
        }
        
        notification.style.backgroundColor = bgColor;
        notification.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('hide');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // ============================================
    // Loading Overlay
    // ============================================
    function showLoading() {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.id = 'loadingOverlay';
        overlay.innerHTML = '<div class="loading-spinner"></div>';
        document.body.appendChild(overlay);
    }
    
    function hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.remove();
        }
    }
    
    // ============================================
    // Preview Certificate
    // ============================================
    let previewZoom = 1.0;
    const previewCanvas = document.getElementById('previewCanvas');
    const previewModal = document.getElementById('previewModal');
    
    previewBtn.addEventListener('click', function() {
        if (!uploadedPdfFile) {
            showNotification('Please upload a PDF template first', 'warning');
            return;
        }
        
        // Show preview modal
        const modal = new bootstrap.Modal(previewModal);
        modal.show();
        
        // Render preview
        renderPreview();
    });
    
    // ============================================
    // Render Preview
    // ============================================
    function renderPreview() {
        if (!pdfDocument) return;
        
        pdfDocument.getPage(currentPage).then(function(page) {
            // Use the same scale as editor (1.5) for exact positioning
            const scale = 1.501;
            const viewport = page.getViewport({ scale: scale });
            
            const context = previewCanvas.getContext('2d');
            previewCanvas.height = viewport.height;
            previewCanvas.width = viewport.width;
            
            const renderContext = {
                canvasContext: context,
                viewport: viewport
            };
            
            page.render(renderContext).promise.then(function() {
                // Render fields with sample data on preview
                renderFieldsOnPreview(context, viewport, scale);
            });
        });
    }
    
    // ============================================
    // Render Fields on Preview
    // ============================================
    function renderFieldsOnPreview(context, viewport, scale) {
        // Sample data for preview
        const sampleData = {
            full_name: 'Maria Santos Reyes',
            first_name: 'Maria',
            middle_name: 'Santos',
            last_name: 'Reyes',
            suffix: 'Jr',
            date_of_birth: '01/19/2026',
            age: '25',
            sex: 'Male',
            civil_status: 'Single',
            address: 'Manila City',
            mobile_number: '09123456789',
            email: 'sample@email.com',
        };
        
        placedFields.forEach(field => {
            let sampleValue = sampleData[field.fieldName] || field.fieldLabel;
            
            // Use actual dates from form for date fields
            if (field.fieldName === 'date_issued' && dateIssued.value) {
                const actualDate = new Date(dateIssued.value);
                sampleValue = formatDateSample(actualDate, field.dateFormat || 'YYYY-MM-DD');
            } else if (field.fieldName === 'date_expired' && dateExpired.value) {
                const actualDate = new Date(dateExpired.value);
                sampleValue = formatDateSample(actualDate, field.dateFormat || 'YYYY-MM-DD');
            }
            // Apply middle name format
            else if (field.fieldName === 'full_name' && field.middleNameFormat === 'initial') {
                sampleValue = 'Maria S. Reyes';
            } else if (field.fieldName === 'middle_name' && field.middleNameFormat === 'initial') {
                sampleValue = 'S.';
            }
            
            // Apply text case transformation
            if (field.textCase === 'uppercase') {
                sampleValue = sampleValue.toUpperCase();
            } else if (field.textCase === 'lowercase') {
                sampleValue = sampleValue.toLowerCase();
            }
            
            // Set font properties
            context.font = `${field.fontBold ? 'bold' : 'normal'} ${field.fontItalic ? 'italic' : 'normal'} ${field.fontSize}px ${field.fontFamily}`;
            context.fillStyle = field.fontColor;
            context.textBaseline = 'top';
            
            // Draw text - use exact stored positions
            const x = field.x;
            const y = field.y;
            context.fillText(sampleValue, x, y);
            
            // Draw underline if needed
            if (field.fontUnderline) {
                const textWidth = context.measureText(sampleValue).width;
                context.beginPath();
                context.moveTo(x, y + field.fontSize);
                context.lineTo(x + textWidth, y + field.fontSize);
                context.strokeStyle = field.fontColor;
                context.lineWidth = 1;
                context.stroke();
            }
        });
    }
    
    // ============================================
    // Preview Zoom Controls
    // ============================================
    document.getElementById('previewZoomIn').addEventListener('click', function() {
        if (previewZoom < 2.0) {
            previewZoom += 0.1;
            updateZoomLevel();
            applyCanvasZoom();
        }
    });
    
    document.getElementById('previewZoomOut').addEventListener('click', function() {
        if (previewZoom > 0.5) {
            previewZoom -= 0.1;
            updateZoomLevel();
            applyCanvasZoom();
        }
    });
    
    function updateZoomLevel() {
        document.getElementById('previewZoomLevel').textContent = Math.round(previewZoom * 100) + '%';
    }
    
    function applyCanvasZoom() {
        // Apply zoom via CSS transform instead of re-rendering
        previewCanvas.style.transform = `scale(${previewZoom})`;
        previewCanvas.style.transformOrigin = 'top left';
    }
    
    // ============================================
    // Preview Download
    // ============================================
    document.getElementById('previewDownload').addEventListener('click', function() {
        const link = document.createElement('a');
        link.download = (certificateName.value || 'certificate') + '_preview.png';
        link.href = previewCanvas.toDataURL('image/png');
        link.click();
        showNotification('Preview downloaded', 'success');
    });
    
    // ============================================
    // Preview Print
    // ============================================
    document.getElementById('previewPrint').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Print Certificate Preview</title>');
        printWindow.document.write('<style>body{margin:0;padding:20px;display:flex;justify-content:center;align-items:center;min-height:100vh;background:#f5f5f5;}img{max-width:100%;height:auto;box-shadow:0 4px 12px rgba(0,0,0,0.15);}</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<img src="' + previewCanvas.toDataURL('image/png') + '" />');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        
        setTimeout(() => {
            printWindow.print();
        }, 250);
    });
    
    // Reset zoom when modal is closed
    previewModal.addEventListener('hidden.bs.modal', function() {
        previewZoom = 1.0;
        updateZoomLevel();
    });
    
    // ============================================
    // Date Field Buttons
    // ============================================
    const addDateIssuedBtn = document.getElementById('addDateIssuedBtn');
    const addDateExpiredBtn = document.getElementById('addDateExpiredBtn');
    const dateFormatModal = document.getElementById('dateFormatModal');
    const selectedDateType = document.getElementById('selectedDateType');
    const addDateFieldBtn = document.getElementById('addDateFieldBtn');
    
    // Date Issued Button Click
    if (addDateIssuedBtn) {
        addDateIssuedBtn.addEventListener('click', function() {
            if (!uploadedPdfFile) {
                showNotification('Please upload a PDF template first', 'warning');
                return;
            }
            
            selectedDateType.value = 'date_issued';
            const modal = new bootstrap.Modal(dateFormatModal);
            modal.show();
        });
    }
    
    // Date Expired Button Click
    if (addDateExpiredBtn) {
        addDateExpiredBtn.addEventListener('click', function() {
            if (!uploadedPdfFile) {
                showNotification('Please upload a PDF template first', 'warning');
                return;
            }
            
            selectedDateType.value = 'date_expired';
            const modal = new bootstrap.Modal(dateFormatModal);
            modal.show();
        });
    }
    
    // Add Date Field to Template
    if (addDateFieldBtn) {
        addDateFieldBtn.addEventListener('click', function() {
            const dateType = selectedDateType.value;
            const selectedFormat = document.querySelector('input[name="dateFormat"]:checked').value;
            
            if (!dateType) {
                showNotification('Please select a date type', 'warning');
                return;
            }
            
            // Get the center of the canvas to place the date field
            const canvasRect = pdfCanvas.getBoundingClientRect();
            const x = canvasRect.width / 2 - 100; // Center horizontally
            const y = canvasRect.height / 2; // Center vertically
            
            // Determine field label
            const fieldLabel = dateType === 'date_issued' ? 'Date Issued' : 'Date Expired';
            
            // Add the field with date format
            addDateFieldPlaceholder(dateType, fieldLabel, x, y, selectedFormat);
            
            // Close modal
            bootstrap.Modal.getInstance(dateFormatModal).hide();
            
            showNotification(`${fieldLabel} field added to template`, 'success');
        });
    }
    
    // ============================================
    // Add Date Field Placeholder
    // ============================================
    function addDateFieldPlaceholder(fieldName, fieldLabel, x, y, dateFormat) {
        const fieldId = 'field_' + Date.now();
        
        // Get canvas position within container
        const canvasRect = pdfCanvas.getBoundingClientRect();
        const containerRect = pdfDisplayContainer.getBoundingClientRect();
        const canvasOffsetX = canvasRect.left - containerRect.left;
        const canvasOffsetY = canvasRect.top - containerRect.top;
        
        // Format sample date based on selected format
        const sampleDate = new Date('2026-02-14');
        const sampleValue = formatDateSample(sampleDate, dateFormat);
        
        const placeholder = document.createElement('div');
        placeholder.className = 'field-placeholder';
        placeholder.id = fieldId;
        placeholder.style.left = (canvasOffsetX + x) + 'px';
        placeholder.style.top = (canvasOffsetY + y) + 'px';
        placeholder.setAttribute('data-field', fieldName);
        
        placeholder.innerHTML = `
            <div class="field-actions">
                <button class="field-action-btn" onclick="editFieldProperties('${fieldId}')" title="Edit Properties">
                    <i class="fas fa-cog"></i>
                </button>
                <button class="field-action-btn" onclick="removeField('${fieldId}')" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <span class="field-label">${sampleValue}</span>
        `;
        
        // Make it draggable
        makeDraggable(placeholder);
        
        // Add click to select
        placeholder.addEventListener('click', function(e) {
            e.stopPropagation();
            selectField(this);
        });
        
        pdfDisplayContainer.appendChild(placeholder);
        
        // Store field with date format
        placedFields.push({
            id: fieldId,
            fieldName: fieldName,
            fieldLabel: fieldLabel,
            x: x,
            y: y,
            fontFamily: 'Arial',
            fontSize: 16,
            fontColor: '#000000',
            fontBold: false,
            fontItalic: false,
            fontUnderline: false,
            textCase: 'normal',
            dateFormat: dateFormat // Store the date format
        });
    }
    
    // ============================================
    // Format Date Sample
    // ============================================
    function formatDateSample(date, format) {
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
    
    // ============================================
    // Keyboard Shortcuts
    // ============================================
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveCertificateBtn.click();
        }
        
        // Delete key to remove selected field
        if (e.key === 'Delete' && selectedField) {
            removeField(selectedField);
        }
    });
    
    // ============================================
    // Custom Field Management
    // ============================================
    // Note: customFields, customFieldIdCounter, and DOM elements are declared at the top
    
    // Add Text Placeholder Button
    if (addTextPlaceholderBtn) {
        addTextPlaceholderBtn.addEventListener('click', function() {
            // Reset form
            textPlaceholderLabel.value = '';
            textPlaceholderRequired.checked = false;
            
            // Show modal
            const modal = new bootstrap.Modal(addTextPlaceholderModal);
            modal.show();
        });
    }
    
    if (addTextPlaceholderSubmit) {
        addTextPlaceholderSubmit.addEventListener('click', function() {
            const label = textPlaceholderLabel.value.trim();
            
            if (!label) {
                showNotification('Please enter a label', 'warning');
                return;
            }
            
            // Create custom field object
            const customField = {
                id: 'custom_text_' + customFieldIdCounter++,
                type: 'text',
                label: label,
                required: textPlaceholderRequired.checked
            };
            
            customFields.push(customField);
            
            // Add to list
            addCustomFieldToList(customField);
            
            // Close modal
            bootstrap.Modal.getInstance(addTextPlaceholderModal).hide();
            
            showNotification('Text field added successfully', 'success');
        });
    }
    
    // Add Number Placeholder Button
    if (addNumberPlaceholderBtn) {
        addNumberPlaceholderBtn.addEventListener('click', function() {
            // Reset form
            numberPlaceholderLabel.value = '';
            numberPlaceholderRequired.checked = false;
            
            // Show modal
            const modal = new bootstrap.Modal(addNumberPlaceholderModal);
            modal.show();
        });
    }
    
    if (addNumberPlaceholderSubmit) {
        addNumberPlaceholderSubmit.addEventListener('click', function() {
            const label = numberPlaceholderLabel.value.trim();
            
            if (!label) {
                showNotification('Please enter a label', 'warning');
                return;
            }
            
            // Create custom field object
            const customField = {
                id: 'custom_number_' + customFieldIdCounter++,
                type: 'number',
                label: label,
                required: numberPlaceholderRequired.checked
            };
            
            customFields.push(customField);
            
            // Add to list
            addCustomFieldToList(customField);
            
            // Close modal
            bootstrap.Modal.getInstance(addNumberPlaceholderModal).hide();
            
            showNotification('Number field added successfully', 'success');
        });
    }
    
    // Add Dropdown Placeholder Button
    if (addDropdownPlaceholderBtn) {
        addDropdownPlaceholderBtn.addEventListener('click', function() {
            // Reset form
            dropdownPlaceholderLabel.value = '';
            dropdownPlaceholderOptions.value = '';
            dropdownPlaceholderRequired.checked = false;
            
            // Show modal
            const modal = new bootstrap.Modal(addDropdownPlaceholderModal);
            modal.show();
        });
    }
    
    if (addDropdownPlaceholderSubmit) {
        addDropdownPlaceholderSubmit.addEventListener('click', function() {
            const label = dropdownPlaceholderLabel.value.trim();
            const optionsText = dropdownPlaceholderOptions.value.trim();
            
            if (!label) {
                showNotification('Please enter a label', 'warning');
                return;
            }
            
            if (!optionsText) {
                showNotification('Please enter at least one option', 'warning');
                return;
            }
            
            // Parse options (one per line)
            const options = optionsText.split('\n').map(opt => opt.trim()).filter(opt => opt.length > 0);
            
            if (options.length === 0) {
                showNotification('Please enter at least one valid option', 'warning');
                return;
            }
            
            // Create custom field object
            const customField = {
                id: 'custom_dropdown_' + customFieldIdCounter++,
                type: 'dropdown',
                label: label,
                options: options,
                required: dropdownPlaceholderRequired.checked
            };
            
            customFields.push(customField);
            
            // Add to list
            addCustomFieldToList(customField);
            
            // Close modal
            bootstrap.Modal.getInstance(addDropdownPlaceholderModal).hide();
            
            showNotification('Dropdown field added successfully', 'success');
        });
    }
    
    // ============================================
    // Add Custom Field to List
    // ============================================
    function addCustomFieldToList(field) {
        let listContainer;
        
        if (field.type === 'text') {
            listContainer = textPlaceholderList;
        } else if (field.type === 'number') {
            listContainer = numberPlaceholderList;
        } else if (field.type === 'dropdown') {
            listContainer = dropdownPlaceholderList;
        }
        
        if (!listContainer) return;
        
        const fieldItem = document.createElement('div');
        fieldItem.className = 'custom-field-item draggable-field';
        fieldItem.setAttribute('data-field', field.id);
        fieldItem.setAttribute('data-field-type', field.type);
        fieldItem.setAttribute('draggable', 'true');
        fieldItem.id = 'custom_field_item_' + field.id;
        
        let typeIcon = 'fa-font';
        if (field.type === 'number') {
            typeIcon = 'fa-hashtag';
        } else if (field.type === 'dropdown') {
            typeIcon = 'fa-caret-down';
        }
        
        fieldItem.innerHTML = `
            <i class="fas fa-grip-vertical drag-handle"></i>
            <div class="field-info">
                <span class="field-label-text">${field.label}</span>
                <span class="field-type-badge"><i class="fas ${typeIcon}"></i> ${field.type.charAt(0).toUpperCase() + field.type.slice(1)}</span>
            </div>
            <div class="field-actions-inline">
                <button class="btn-field-edit" onclick="editCustomField('${field.id}')" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-field-delete" onclick="deleteCustomField('${field.id}')" title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        // Add drag event listeners
        fieldItem.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('fieldName', field.id);
            e.dataTransfer.setData('fieldLabel', field.label);
            e.dataTransfer.setData('fieldType', field.type);
            e.dataTransfer.effectAllowed = 'copy';
        });
        
        listContainer.appendChild(fieldItem);
    }
    
    // ============================================
    // Edit Custom Field (Global Function)
    // ============================================
    window.editCustomField = function(fieldId) {
        const field = customFields.find(f => f.id === fieldId);
        if (!field) return;
        
        if (field.type === 'text') {
            textPlaceholderLabel.value = field.label;
            textPlaceholderRequired.checked = field.required;
            
            // Change submit button to update
            addTextPlaceholderSubmit.textContent = 'Update';
            addTextPlaceholderSubmit.onclick = function() {
                field.label = textPlaceholderLabel.value.trim();
                field.required = textPlaceholderRequired.checked;
                
                // Update UI
                const fieldItem = document.getElementById('custom_field_item_' + fieldId);
                if (fieldItem) {
                    fieldItem.querySelector('.field-label-text').textContent = field.label;
                }
                
                // Reset button
                addTextPlaceholderSubmit.textContent = 'ADD';
                addTextPlaceholderSubmit.onclick = null;
                
                bootstrap.Modal.getInstance(addTextPlaceholderModal).hide();
                showNotification('Text field updated', 'success');
            };
            
            const modal = new bootstrap.Modal(addTextPlaceholderModal);
            modal.show();
        } else if (field.type === 'number') {
            numberPlaceholderLabel.value = field.label;
            numberPlaceholderRequired.checked = field.required;
            
            addNumberPlaceholderSubmit.textContent = 'Update';
            addNumberPlaceholderSubmit.onclick = function() {
                field.label = numberPlaceholderLabel.value.trim();
                field.required = numberPlaceholderRequired.checked;
                
                const fieldItem = document.getElementById('custom_field_item_' + fieldId);
                if (fieldItem) {
                    fieldItem.querySelector('.field-label-text').textContent = field.label;
                }
                
                addNumberPlaceholderSubmit.textContent = 'ADD';
                addNumberPlaceholderSubmit.onclick = null;
                
                bootstrap.Modal.getInstance(addNumberPlaceholderModal).hide();
                showNotification('Number field updated', 'success');
            };
            
            const modal = new bootstrap.Modal(addNumberPlaceholderModal);
            modal.show();
        } else if (field.type === 'dropdown') {
            dropdownPlaceholderLabel.value = field.label;
            dropdownPlaceholderOptions.value = field.options.join('\n');
            dropdownPlaceholderRequired.checked = field.required;
            
            addDropdownPlaceholderSubmit.textContent = 'Update';
            addDropdownPlaceholderSubmit.onclick = function() {
                field.label = dropdownPlaceholderLabel.value.trim();
                field.options = dropdownPlaceholderOptions.value.split('\n').map(opt => opt.trim()).filter(opt => opt.length > 0);
                field.required = dropdownPlaceholderRequired.checked;
                
                const fieldItem = document.getElementById('custom_field_item_' + fieldId);
                if (fieldItem) {
                    fieldItem.querySelector('.field-label-text').textContent = field.label;
                }
                
                addDropdownPlaceholderSubmit.textContent = 'ADD';
                addDropdownPlaceholderSubmit.onclick = null;
                
                bootstrap.Modal.getInstance(addDropdownPlaceholderModal).hide();
                showNotification('Dropdown field updated', 'success');
            };
            
            const modal = new bootstrap.Modal(addDropdownPlaceholderModal);
            modal.show();
        }
    };
    
    // ============================================
    // Delete Custom Field (Global Function)
    // ============================================
    window.deleteCustomField = function(fieldId) {
        if (!confirm('Are you sure you want to delete this field?')) {
            return;
        }
        
        // Remove from array
        customFields = customFields.filter(f => f.id !== fieldId);
        
        // Remove from UI
        const fieldItem = document.getElementById('custom_field_item_' + fieldId);
        if (fieldItem) {
            fieldItem.remove();
        }
        
        // Remove any placed instances from PDF
        placedFields = placedFields.filter(f => f.fieldName !== fieldId);
        document.querySelectorAll(`[data-field="${fieldId}"]`).forEach(el => {
            if (el.classList.contains('field-placeholder')) {
                el.remove();
            }
        });
        
        showNotification('Field deleted successfully', 'success');
    };
    
    console.log('Create Certificate page initialized');
});
