<?php
if (!defined('ABSPATH')) {
    exit;
}

function render_resources_section() {
    ?>
    
    <div class="resources-section">
        <h3>🛠️ Resources & Tools</h3>
        
        <div class="resources-grid">
            <div class="resource-category">
                <h4>🌐 Application Portals</h4>
                <div class="portal-links">
                    <a href="https://www.hbcuconnect.com/" target="_blank" class="portal-link hbcu">
                        <div class="portal-icon">🎓</div>
                        <div class="portal-info">
                            <div class="portal-name">HBCU Common App</div>
                            <div class="portal-desc">Apply to HBCUs</div>
                        </div>
                    </a>
                    
                    <a href="https://www.commonapp.org/" target="_blank" class="portal-link common">
                        <div class="portal-icon">📝</div>
                        <div class="portal-info">
                            <div class="portal-name">Common Application</div>
                            <div class="portal-desc">Apply to 900+ colleges</div>
                        </div>
                    </a>
                    
                    <a href="https://studentaid.gov/h/apply-for-aid/fafsa" target="_blank" class="portal-link fafsa">
                        <div class="portal-icon">💰</div>
                        <div class="portal-info">
                            <div class="portal-name">FAFSA Application</div>
                            <div class="portal-desc">Federal financial aid</div>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="resource-category">
                <h4>📚 Templates & Guides</h4>
                <div class="template-links">
                    <a href="#" class="template-link" onclick="downloadTemplate('resume')">
                        <div class="template-icon">📄</div>
                        <div class="template-info">
                            <div class="template-name">Resume Template</div>
                            <div class="template-desc">Professional academic resume</div>
                        </div>
                        <div class="download-icon">⬇️</div>
                    </a>
                    
                    <a href="#" class="template-link" onclick="downloadTemplate('essay')">
                        <div class="template-icon">✍️</div>
                        <div class="template-info">
                            <div class="template-name">Essay Guide</div>
                            <div class="template-desc">Personal statement tips</div>
                        </div>
                        <div class="download-icon">⬇️</div>
                    </a>
                    
                    <a href="#" class="template-link" onclick="downloadTemplate('checklist')">
                        <div class="template-icon">✅</div>
                        <div class="template-info">
                            <div class="template-name">Application Checklist</div>
                            <div class="template-desc">Complete application guide</div>
                        </div>
                        <div class="download-icon">⬇️</div>
                    </a>
                    
                    <a href="#" class="template-link" onclick="downloadTemplate('scholarship')">
                        <div class="template-icon">🏆</div>
                        <div class="template-info">
                            <div class="template-name">Scholarship Guide</div>
                            <div class="template-desc">Find and apply for scholarships</div>
                        </div>
                        <div class="download-icon">⬇️</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .resources-section {
        background: white;
        padding: 30px;
        border-radius: 16px;
        margin: 30px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .resources-section h3 {
        margin: 0 0 25px 0;
        color: #2d5016;
        font-size: 24px;
        font-weight: 700;
    }
    .resources-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
    }
    .resource-category h4 {
        margin: 0 0 20px 0;
        color: #333;
        font-size: 18px;
        font-weight: 600;
    }
    .portal-links, .template-links {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .portal-link, .template-link {
        display: flex;
        align-items: center;
        padding: 15px;
        border-radius: 12px;
        text-decoration: none;
        transition: all 0.3s;
        cursor: pointer;
    }
    .portal-link.hbcu {
        background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
        color: white;
    }
    .portal-link.common {
        background: linear-gradient(135deg, #e91e63 0%, #c2185b 100%);
        color: white;
    }
    .portal-link.fafsa {
        background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%);
        color: white;
    }
    .template-link {
        background: #f8f9fa;
        color: #333;
        border: 1px solid #e9ecef;
    }
    .template-link:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }
    .portal-link:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .portal-icon, .template-icon {
        font-size: 24px;
        margin-right: 15px;
        width: 40px;
        text-align: center;
    }
    .portal-info, .template-info {
        flex: 1;
    }
    .portal-name, .template-name {
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 4px;
    }
    .portal-desc, .template-desc {
        font-size: 12px;
        opacity: 0.8;
    }
    .download-icon {
        font-size: 20px;
        margin-left: 15px;
    }
    
    @media (max-width: 768px) {
        .resources-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }
    }
    </style>
    
    <script>
    function downloadTemplate(type) {
        // Template download logic
        const templates = {
            'resume': 'Academic_Resume_Template.pdf',
            'essay': 'Personal_Essay_Guide.pdf', 
            'checklist': 'Application_Checklist.pdf',
            'scholarship': 'Scholarship_Guide.pdf'
        };
        
        const filename = templates[type];
        if (filename) {
            // In a real implementation, this would download the actual file
            alert(`Downloading ${filename}...`);
            console.log(`Download template: ${type}`);
        }
    }
    </script>
    <?php
}
?>
