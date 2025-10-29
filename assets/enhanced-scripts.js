
// Enhanced UX JavaScript
class TFSPEnhancer {
    constructor() {
        this.init();
    }
    
    init() {
        this.addLoadingStates();
        this.enhanceAccessibility();
        this.addErrorHandling();
        this.improveNavigation();
    }
    
    addLoadingStates() {
        document.querySelectorAll("form").forEach(form => {
            form.addEventListener("submit", (e) => {
                const submitBtn = form.querySelector("[type=submit]");
                if (submitBtn) {
                    submitBtn.classList.add("loading");
                    submitBtn.disabled = true;
                }
            });
        });
    }
    
    enhanceAccessibility() {
        // Add ARIA labels
        document.querySelectorAll("input:not([aria-label])").forEach(input => {
            const label = document.querySelector(`label[for="${input.id}"]`);
            if (label) {
                input.setAttribute("aria-label", label.textContent);
            }
        });
        
        // Keyboard navigation
        document.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                const modal = document.querySelector(".modal.show");
                if (modal) {
                    this.closeModal(modal);
                }
            }
        });
    }
    
    addErrorHandling() {
        window.addEventListener("error", (e) => {
            console.error("JavaScript error:", e.error);
            this.showNotification("An error occurred. Please refresh the page.", "error");
        });
    }
    
    improveNavigation() {
        // Add breadcrumbs
        const breadcrumbs = this.generateBreadcrumbs();
        const container = document.querySelector(".container");
        if (container && breadcrumbs) {
            container.insertAdjacentHTML("afterbegin", breadcrumbs);
        }
    }
    
    generateBreadcrumbs() {
        const path = window.location.search;
        const view = new URLSearchParams(path).get("view") || "dashboard";
        
        const breadcrumbMap = {
            dashboard: "Dashboard",
            attendance: "Attendance",
            students: "Students",
            challenges: "Challenges",
            recommendations: "Recommendations",
            advisor: "Settings",
            documents: "Documents",
            messages: "Messages"
        };
        
        return `
            <nav aria-label="Breadcrumb" class="breadcrumb">
                <ol>
                    <li><a href="?view=dashboard">Home</a></li>
                    <li aria-current="page">${breadcrumbMap[view] || "Page"}</li>
                </ol>
            </nav>
        `;
    }
    
    showNotification(message, type = "info") {
        const notification = document.createElement("div");
        notification.className = `notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 4px;
            color: white;
            z-index: 9999;
            background: ${type === "error" ? "#dc3545" : type === "success" ? "#28a745" : "#17a2b8"};
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    new TFSPEnhancer();
});
