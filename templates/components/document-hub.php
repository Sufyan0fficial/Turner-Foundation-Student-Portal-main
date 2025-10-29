<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_document_hub($user_id) {
    global $wpdb;
    $documents = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tfsp_documents WHERE user_id = %d ORDER BY upload_date DESC", $user_id));
    ?>
    
    <div class="document-hub">
        <h3>📄 Document Management Hub</h3>
        
        <div class="upload-section">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📁</div>
                <p><strong>Drag & drop files here</strong> or <span class="browse-link">choose files</span></p>
                <input type="file" id="fileInput" multiple style="display: none;">
                <div class="selected-files" id="selectedFiles" style="display: none;"></div>
            </div>
            
            <div class="upload-controls">
                <select id="documentType" class="doc-type-select">
                    <option value="">Select document type</option>
                    <option value="transcript">Official Transcript</option>
                    <option value="essay">Personal Essay</option>
                    <option value="resume">Academic Resume</option>
                    <option value="recommendation">Recommendation Letter</option>
                    <option value="other">Other</option>
                </select>
                <button class="upload-btn" id="uploadBtn" onclick="uploadDocument()" disabled>Upload Document</button>
            </div>
            
            <div class="upload-progress" id="uploadProgress" style="display: none;">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">Uploading...</div>
            </div>
        </div>
        
        <div class="documents-list">
            <h4>📋 Your Documents</h4>
            <?php if (empty($documents)): ?>
                <div class="empty-state">
                    <p>No documents uploaded yet. Start by uploading your first document above!</p>
                </div>
            <?php else: ?>
                <div class="document-items">
                    <?php foreach ($documents as $doc): ?>
                        <div class="document-item">
                            <div class="doc-icon">📄</div>
                            <div class="doc-info">
                                <div class="doc-name"><?php echo esc_html($doc->file_name); ?></div>
                                <div class="doc-type"><?php echo esc_html($doc->document_type); ?></div>
                            </div>
                            <div class="doc-status">
                                <?php 
                                $status_colors = array(
                                    'pending' => '#ff9800',
                                    'submitted' => '#2196f3', 
                                    'accepted' => '#4caf50',
                                    'needs_revision' => '#f44336'
                                );
                                $color = $status_colors[$doc->status] ?? '#666';
                                ?>
                                <span class="status-badge" style="background: <?php echo $color; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $doc->status)); ?>
                                </span>
                            </div>
                            <div class="doc-actions">
                                <a href="<?php echo $doc->file_path; ?>" target="_blank" class="view-btn">👁️</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <style>
    .document-hub {
        background: white;
        padding: 30px;
        border-radius: 16px;
        margin: 30px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .document-hub h3 {
        margin: 0 0 25px 0;
        color: #2d5016;
        font-size: 24px;
        font-weight: 700;
    }
    .upload-area {
        border: 2px dashed #8BC34A;
        border-radius: 12px;
        padding: 40px 20px;
        text-align: center;
        background: #f8fff8;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 20px;
    }
    .upload-area:hover {
        border-color: #7CB342;
        background: #f0fff0;
    }
    .upload-area.dragover {
        border-color: #4caf50;
        background: #e8f5e8;
    }
    .upload-icon {
        font-size: 48px;
        margin-bottom: 15px;
    }
    .upload-area p {
        margin: 0;
        color: #666;
        font-size: 16px;
    }
    .browse-link {
        color: #8BC34A;
        font-weight: 600;
        cursor: pointer;
    }
    .upload-controls {
        display: flex;
        gap: 15px;
        align-items: center;
        margin-bottom: 30px;
    }
    .doc-type-select {
        flex: 1;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 14px;
    }
    .upload-btn {
        background: #8BC34A;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
    }
    .upload-btn:hover {
        background: #7CB342;
    }
    .upload-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    .selected-files {
        margin-top: 15px;
        padding: 10px;
        background: #e8f5e8;
        border-radius: 6px;
        border-left: 3px solid #4caf50;
    }
    .file-item {
        display: flex;
        align-items: center;
        padding: 5px 0;
        font-size: 14px;
        color: #333;
    }
    .file-item .file-icon {
        margin-right: 8px;
        color: #4caf50;
    }
    .upload-progress {
        margin-top: 15px;
        padding: 15px;
        background: #f0f8ff;
        border-radius: 8px;
        border-left: 3px solid #2196f3;
    }
    .progress-bar {
        width: 100%;
        height: 8px;
        background: #e0e0e0;
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    .progress-fill {
        height: 100%;
        background: #2196f3;
        width: 0%;
        transition: width 0.3s ease;
    }
    .progress-text {
        font-size: 14px;
        color: #2196f3;
        font-weight: 600;
    }
    .documents-list h4 {
        margin: 0 0 20px 0;
        color: #333;
        font-size: 18px;
        font-weight: 600;
    }
    .document-items {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .document-item {
        display: flex;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: background 0.3s;
    }
    .document-item:hover {
        background: #e9ecef;
    }
    .doc-icon {
        font-size: 24px;
        margin-right: 15px;
    }
    .doc-info {
        flex: 1;
    }
    .doc-name {
        font-weight: 600;
        color: #333;
        margin-bottom: 4px;
    }
    .doc-type {
        font-size: 12px;
        color: #666;
        text-transform: capitalize;
    }
    .status-badge {
        color: white;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .view-btn {
        margin-left: 15px;
        text-decoration: none;
        font-size: 18px;
        padding: 8px;
        border-radius: 6px;
        transition: background 0.3s;
    }
    .view-btn:hover {
        background: rgba(0,0,0,0.1);
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #666;
        background: #f8f9fa;
        border-radius: 8px;
    }
    </style>
    
    <script>
    // File upload functionality
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const selectedFilesDiv = document.getElementById('selectedFiles');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadProgress = document.getElementById('uploadProgress');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    uploadArea.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
    
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.classList.remove('dragover');
    });
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        const files = e.dataTransfer.files;
        fileInput.files = files;
        handleFiles(files);
    });
    
    function handleFiles(files) {
        if (files.length === 0) {
            selectedFilesDiv.style.display = 'none';
            uploadBtn.disabled = true;
            return;
        }
        
        // Show selected files
        selectedFilesDiv.innerHTML = '<strong>📎 Selected Files:</strong><br>';
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <span class="file-icon">📄</span>
                <span>${file.name} (${formatFileSize(file.size)})</span>
            `;
            selectedFilesDiv.appendChild(fileItem);
        }
        selectedFilesDiv.style.display = 'block';
        
        // Enable upload button
        uploadBtn.disabled = false;
        uploadBtn.textContent = `Upload ${files.length} File${files.length > 1 ? 's' : ''}`;
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    function uploadDocument() {
        const files = fileInput.files;
        const docType = document.getElementById('documentType').value;
        
        if (files.length === 0) {
            alert('Please select files to upload');
            return;
        }
        if (!docType) {
            alert('Please select document type');
            return;
        }
        
        // Show progress
        uploadProgress.style.display = 'block';
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Uploading...';
        
        // Simulate upload progress
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 15;
            if (progress > 90) progress = 90;
            progressFill.style.width = progress + '%';
            progressText.textContent = `Uploading... ${Math.round(progress)}%`;
        }, 200);
        
        // Actual upload using AJAX
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }
        formData.append('document_type', docType);
        formData.append('action', 'tfsp_upload_general_document');
        formData.append('nonce', '<?php echo wp_create_nonce('tfsp_nonce'); ?>');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            clearInterval(progressInterval);
            progressFill.style.width = '100%';
            progressText.textContent = 'Upload complete!';
            
            setTimeout(() => {
                if (data.success) {
                    alert('✅ Document uploaded successfully!');
                    location.reload();
                } else {
                    alert('❌ Upload failed: ' + (data.data || 'Unknown error'));
                    resetUploadForm();
                }
            }, 500);
        })
        .catch(error => {
            clearInterval(progressInterval);
            alert('❌ Upload failed: ' + error.message);
            resetUploadForm();
        });
    }
    
    function resetUploadForm() {
        uploadProgress.style.display = 'none';
        selectedFilesDiv.style.display = 'none';
        fileInput.value = '';
        uploadBtn.disabled = true;
        uploadBtn.textContent = 'Upload Document';
        progressFill.style.width = '0%';
    }
    </script>
    <?php
}
?>
