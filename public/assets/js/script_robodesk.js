
// Global state management
const appState = {
    currentPage: 'dashboard',
    currentTicket: null,
    tickets: [
        {
            id: 1,
            subject: 'Login issues with WordPress admin',
            customer: 'john@example.com',
            status: 'open',
            priority: 'high',
            created: '2024-01-15',
            messages: [
                {
                    author: 'john@example.com',
                    content: 'Hi, I\'m having trouble logging into my WordPress admin panel...',
                    timestamp: '2024-01-15 10:30 AM',
                    type: 'customer'
                },
                {
                    author: 'Support Agent 1',
                    content: 'Hello John, thank you for contacting support...',
                    timestamp: '2024-01-15 11:15 AM',
                    type: 'admin'
                }
            ]
        },
        {
            id: 2,
            subject: 'Plugin not working after update',
            customer: 'sarah@example.com',
            status: 'pending',
            priority: 'medium',
            created: '2024-01-14',
            messages: []
        },
        {
            id: 3,
            subject: 'Site loading very slowly',
            customer: 'mike@example.com',
            status: 'closed',
            priority: 'low',
            created: '2024-01-13',
            messages: []
        }
    ],
    settings: {
        basic: {
            siteName: 'My WordPress Site',
            supportEmail: 'support@example.com',
            ticketPrefix: 'TKT-',
            autoAssign: true,
            emailNotifications: true,
            customerNotifications: true
        },
        smtp: {
            host: '',
            port: 587,
            username: '',
            password: '',
            encryption: 'tls'
        }
    }
};

// Initialize app
document.addEventListener('DOMContentLoaded', function() {
    initializeNavigation();
    loadDashboardStats();
    initializeEventListeners();
});

// Navigation functions
function initializeNavigation() {
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const page = this.dataset.page;
            showPage(page);
            updateActiveNavLink(this);
        });
    });
}

function showPage(pageId) {
    // Hide all pages
    const pages = document.querySelectorAll('.page');
    pages.forEach(page => page.classList.remove('active'));
    
    // Show selected page
    const targetPage = document.getElementById(pageId);
    if (targetPage) {
        targetPage.classList.add('active');
        appState.currentPage = pageId;
    }
}

function updateActiveNavLink(activeLink) {
    // Remove active class from all nav links
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    // Add active class to clicked link
    activeLink.classList.add('active');
}

// Dashboard functions
function loadDashboardStats() {
    const stats = calculateStats();
    updateStatsDisplay(stats);
}

function calculateStats() {
    const total = appState.tickets.length;
    const open = appState.tickets.filter(t => t.status === 'open').length;
    const pending = appState.tickets.filter(t => t.status === 'pending').length;
    const closed = appState.tickets.filter(t => t.status === 'closed').length;
    
    return { total, open, pending, closed };
}

function updateStatsDisplay(stats) {
    const statCards = document.querySelectorAll('.stat-content h3');
    if (statCards[0]) statCards[0].textContent = stats.total;
    if (statCards[1]) statCards[1].textContent = stats.open;
    if (statCards[2]) statCards[2].textContent = stats.pending;
    if (statCards[3]) statCards[3].textContent = stats.closed;
}

// Ticket functions
function showTicketDetail(ticketId) {
    const ticket = appState.tickets.find(t => t.id === ticketId);
    if (ticket) {
        appState.currentTicket = ticket;
        populateTicketDetail(ticket);
        showPage('ticket-detail');
    }
}

function populateTicketDetail(ticket) {
    // Update ticket header
    const ticketTitle = document.querySelector('#ticket-detail h2');
    const ticketId = document.querySelector('.ticket-id');
    
    if (ticketTitle) ticketTitle.textContent = ticket.subject;
    if (ticketId) ticketId.textContent = `Ticket #${String(ticket.id).padStart(3, '0')}`;
    
    // Update status and priority
    const statusBadge = document.querySelector('#ticket-detail .status-badge');
    const prioritySpan = document.querySelector('#ticket-detail .priority-high, #ticket-detail .priority-medium, #ticket-detail .priority-low');
    
    if (statusBadge) {
        statusBadge.className = `status-badge status-${ticket.status}`;
        statusBadge.textContent = ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1);
    }
    
    if (prioritySpan) {
        prioritySpan.className = `priority-${ticket.priority}`;
        prioritySpan.textContent = ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) + ' Priority';
    }
    
    // Update ticket details
    const detailItems = document.querySelectorAll('#ticket-detail .detail-item span');
    if (detailItems[0]) detailItems[0].textContent = ticket.customer;
    if (detailItems[1]) detailItems[1].textContent = ticket.created + ' 10:30 AM';
    if (detailItems[2]) detailItems[2].textContent = new Date().toLocaleDateString() + ' 2:45 PM';
    if (detailItems[3]) detailItems[3].textContent = 'Support Agent 1';
}

function updateTicket() {
    const statusSelect = document.getElementById('ticketStatus');
    const assignSelect = document.getElementById('assignTo');
    
    if (appState.currentTicket && statusSelect) {
        appState.currentTicket.status = statusSelect.value;
        showNotification('Ticket updated successfully!', 'success');
        
        // Update the ticket in the main list
        const ticketIndex = appState.tickets.findIndex(t => t.id === appState.currentTicket.id);
        if (ticketIndex !== -1) {
            appState.tickets[ticketIndex] = appState.currentTicket;
        }
        
        // Refresh the tickets table
        renderTicketsTable();
        loadDashboardStats();
    }
}

function sendReply() {
    const replyMessage = document.getElementById('replyMessage');
    
    if (replyMessage && replyMessage.value.trim()) {
        const newMessage = {
            author: 'Support Agent 1',
            content: replyMessage.value.trim(),
            timestamp: new Date().toLocaleString(),
            type: 'admin'
        };
        
        if (appState.currentTicket) {
            appState.currentTicket.messages.push(newMessage);
        }
        
        replyMessage.value = '';
        showNotification('Reply sent successfully!', 'success');
        
        // Refresh ticket detail view
        populateTicketDetail(appState.currentTicket);
    }
}

function addNote() {
    const replyMessage = document.getElementById('replyMessage');
    
    if (replyMessage && replyMessage.value.trim()) {
        const newNote = {
            author: 'Support Agent 1',
            content: replyMessage.value.trim(),
            timestamp: new Date().toLocaleString() + ' - Internal Note',
            type: 'note'
        };
        
        if (appState.currentTicket) {
            appState.currentTicket.messages.push(newNote);
        }
        
        replyMessage.value = '';
        showNotification('Internal note added successfully!', 'success');
        
        // Refresh ticket detail view
        populateTicketDetail(appState.currentTicket);
    }
}

// Filter functions
function applyFilters() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    
    let filteredTickets = appState.tickets;
    
    // Apply search filter
    if (searchInput && searchInput.value.trim()) {
        const searchTerm = searchInput.value.trim().toLowerCase();
        filteredTickets = filteredTickets.filter(ticket => 
            ticket.subject.toLowerCase().includes(searchTerm) ||
            ticket.customer.toLowerCase().includes(searchTerm)
        );
    }
    
    // Apply status filter
    if (statusFilter && statusFilter.value) {
        filteredTickets = filteredTickets.filter(ticket => ticket.status === statusFilter.value);
    }
    
    // Apply priority filter
    if (priorityFilter && priorityFilter.value) {
        filteredTickets = filteredTickets.filter(ticket => ticket.priority === priorityFilter.value);
    }
    
    renderTicketsTable(filteredTickets);
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('priorityFilter').value = '';
    renderTicketsTable();
}

function renderTicketsTable(tickets = appState.tickets) {
    const tbody = document.getElementById('ticketsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    tickets.forEach(ticket => {
        const row = document.createElement('tr');
        row.onclick = () => showTicketDetail(ticket.id);
        
        row.innerHTML = `
            <td>#${String(ticket.id).padStart(3, '0')}</td>
            <td>${ticket.subject}</td>
            <td>${ticket.customer}</td>
            <td><span class="status-badge status-${ticket.status}">${ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1)}</span></td>
            <td><span class="priority-${ticket.priority}">${ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1)}</span></td>
            <td>${ticket.created}</td>
            <td>
                <button class="btn btn-small btn-primary" onclick="event.stopPropagation(); showTicketDetail(${ticket.id})">View</button>
                <button class="btn btn-small btn-secondary" onclick="event.stopPropagation(); editTicket(${ticket.id})">Edit</button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

// Settings functions
function showSettingsTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.classList.remove('active'));
    
    // Remove active class from all tab buttons
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => button.classList.remove('active'));
    
    // Show selected tab content
    const targetTab = document.getElementById(tabName + '-settings');
    if (targetTab) {
        targetTab.classList.add('active');
    }
    
    // Add active class to clicked tab button
    event.target.classList.add('active');
}

function saveSettings() {
    // Collect all settings data
    const basicSettings = {
        siteName: document.getElementById('siteName')?.value || '',
        supportEmail: document.getElementById('supportEmail')?.value || '',
        ticketPrefix: document.getElementById('ticketPrefix')?.value || '',
        autoAssign: document.getElementById('autoAssign')?.checked || false,
        emailNotifications: document.getElementById('emailNotifications')?.checked || false,
        customerNotifications: document.getElementById('customerNotifications')?.checked || false
    };
    
    const smtpSettings = {
        host: document.getElementById('smtpHost')?.value || '',
        port: document.getElementById('smtpPort')?.value || 587,
        username: document.getElementById('smtpUsername')?.value || '',
        password: document.getElementById('smtpPassword')?.value || '',
        encryption: document.getElementById('smtpEncryption')?.value || 'tls'
    };
    
    // Update app state
    appState.settings.basic = basicSettings;
    appState.settings.smtp = smtpSettings;
    
    // In a real application, you would send this data to the server
    console.log('Settings saved:', appState.settings);
    showNotification('Settings saved successfully!', 'success');
}

function testSMTP() {
    const smtpHost = document.getElementById('smtpHost')?.value;
    const smtpPort = document.getElementById('smtpPort')?.value;
    
    if (!smtpHost || !smtpPort) {
        showNotification('Please fill in SMTP host and port first.', 'error');
        return;
    }
    
    // Simulate SMTP test
    showNotification('Testing SMTP connection...', 'info');
    
    setTimeout(() => {
        showNotification('SMTP connection test successful!', 'success');
    }, 2000);
}

// Utility functions
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    // Style the notification
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 4px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    
    // Set background color based on type
    switch (type) {
        case 'success':
            notification.style.backgroundColor = '#27ae60';
            break;
        case 'error':
            notification.style.backgroundColor = '#e74c3c';
            break;
        case 'warning':
            notification.style.backgroundColor = '#f39c12';
            break;
        default:
            notification.style.backgroundColor = '#3498db';
    }
    
    // Add to document
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}

function editTicket(ticketId) {
    // In a real application, this would open an edit modal or form
    console.log('Edit ticket:', ticketId);
    showNotification('Edit functionality would be implemented here.', 'info');
}

// Initialize event listeners
function initializeEventListeners() {
    // Search input real-time filtering
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            if (this.value.length > 2 || this.value.length === 0) {
                applyFilters();
            }
        });
    }
    
    // Initialize tickets table
    renderTicketsTable();
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
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
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
