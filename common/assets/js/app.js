/**
 * Main Application JavaScript
 */

// Flash message handler
document.addEventListener('DOMContentLoaded', function () {
    const flashMessage = document.querySelector('.flash-message');
    if (flashMessage) {
        setTimeout(() => {
            flashMessage.style.opacity = '0';
            setTimeout(() => flashMessage.remove(), 300);
        }, 5000);
    }
});

// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

// Close sidebar on outside click (mobile)
document.addEventListener('click', function (e) {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.querySelector('.sidebar-toggle');

    if (sidebar && sidebar.classList.contains('open')) {
        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
            sidebar.classList.remove('open');
        }
    }
});

// Confirm delete actions
function confirmDelete(message) {
    return confirm(message || 'Are you sure you want to delete this item?');
}

// Image preview for file uploads
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById(previewId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Form validation helper
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('border-red-500');
            isValid = false;
        } else {
            input.classList.remove('border-red-500');
        }
    });

    return isValid;
}

// Dropdown toggle
function toggleDropdown(dropdownId) {
    const dropdown = document.getElementById(dropdownId);
    if (dropdown) {
        dropdown.classList.toggle('hidden');
    }
}

// Close dropdowns on outside click
document.addEventListener('click', function (e) {
    if (!e.target.matches('.dropdown-toggle')) {
        const dropdowns = document.querySelectorAll('.dropdown-menu');
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }
});

// Search filter
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);

    if (!input || !table) return;

    const filter = input.value.toUpperCase();
    const rows = table.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;

        for (let j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toUpperCase().indexOf(filter) > -1) {
                found = true;
                break;
            }
        }

        rows[i].style.display = found ? '' : 'none';
    }
}

// Tab switcher
function switchTab(tabName, tabGroup) {
    // Hide all tab contents in group
    const contents = document.querySelectorAll(`[data-tab-group="${tabGroup}"]`);
    contents.forEach(content => {
        content.classList.add('hidden');
    });

    // Remove active class from all tabs
    const tabs = document.querySelectorAll(`[data-tab-name]`);
    tabs.forEach(tab => {
        tab.classList.remove('active', 'text-purple-600', 'border-purple-600');
        tab.classList.add('text-gray-500');
    });

    // Show selected tab content
    const selectedContent = document.querySelector(`[data-tab-group="${tabGroup}"][data-tab-content="${tabName}"]`);
    if (selectedContent) {
        selectedContent.classList.remove('hidden');
    }

    // Add active class to selected tab
    const selectedTab = document.querySelector(`[data-tab-name="${tabName}"]`);
    if (selectedTab) {
        selectedTab.classList.add('active', 'text-purple-600', 'border-purple-600');
        selectedTab.classList.remove('text-gray-500');
    }
}

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
}

// Close modal on outside click
window.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.closest('.modal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
});

// Print ticket
function printTicket() {
    window.print();
}

// Copy to clipboard
function copyToClipboard(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);

    // Show feedback
    showToast('Copied to clipboard!');
}

// Toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${type === 'success' ? 'bg-green-500' :
            type === 'error' ? 'bg-red-500' :
                'bg-blue-500'
        } animate-fade-in z-50`;
    toast.textContent = message;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Star rating
function setRating(rating) {
    const stars = document.querySelectorAll('.star-rating i');
    const input = document.getElementById('rating-input');

    stars.forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('far');
            star.classList.add('fas', 'text-yellow-400');
        } else {
            star.classList.remove('fas', 'text-yellow-400');
            star.classList.add('far');
        }
    });

    if (input) {
        input.value = rating;
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function () {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function () {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);

            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
            tooltip.style.left = rect.left + (rect.width - tooltip.offsetWidth) / 2 + 'px';
        });

        element.addEventListener('mouseleave', function () {
            const tooltip = document.querySelector('.tooltip');
            if (tooltip) tooltip.remove();
        });
    });
});
