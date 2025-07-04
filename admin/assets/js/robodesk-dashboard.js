// Sample data
const sampleTickets = [
  {
    id: 1,
    title: "Login issues with WordPress admin",
    status: "open",
    priority: "high",
    customer: "John Smith",
    email: "john@example.com",
    created: "2024-01-15T10:30:00Z",
    updated: "2024-01-15T14:20:00Z",
    assignee: "Support Team",
    category: "Technical",
    description:
      "I can't login to my WordPress admin panel. Getting error 500.",
    replies: [
      {
        id: 1,
        author: "John Smith",
        message:
          "I can't login to my WordPress admin panel. Getting error 500.",
        timestamp: "2024-01-15T10:30:00Z",
        type: "customer",
      },
      {
        id: 2,
        author: "Support Agent",
        message:
          "We're looking into this issue. Can you provide your website URL?",
        timestamp: "2024-01-15T14:20:00Z",
        type: "agent",
      },
    ],
    notes: [
      {
        id: 1,
        author: "Support Agent",
        note: "Customer reported server error. Checking logs.",
        timestamp: "2024-01-15T14:15:00Z",
      },
    ],
  },
  {
    id: 2,
    title: "Theme customization help needed",
    status: "in-progress",
    priority: "medium",
    customer: "Sarah Johnson",
    email: "sarah@example.com",
    created: "2024-01-14T09:15:00Z",
    updated: "2024-01-15T11:30:00Z",
    assignee: "Design Team",
    category: "Design",
    description: "Need help customizing the theme colors and layout.",
    replies: [
      {
        id: 1,
        author: "Sarah Johnson",
        message: "Need help customizing the theme colors and layout.",
        timestamp: "2024-01-14T09:15:00Z",
        type: "customer",
      },
    ],
    notes: [],
  },
  {
    id: 3,
    title: "Plugin compatibility issue",
    status: "resolved",
    priority: "low",
    customer: "Mike Wilson",
    email: "mike@example.com",
    created: "2024-01-13T16:45:00Z",
    updated: "2024-01-14T10:00:00Z",
    assignee: "Tech Support",
    category: "Technical",
    description: "Plugin conflicts causing site issues.",
    replies: [
      {
        id: 1,
        author: "Mike Wilson",
        message: "Plugin conflicts causing site issues.",
        timestamp: "2024-01-13T16:45:00Z",
        type: "customer",
      },
      {
        id: 2,
        author: "Tech Support",
        message: "Issue resolved by updating plugin versions.",
        timestamp: "2024-01-14T10:00:00Z",
        type: "agent",
      },
    ],
    notes: [],
  },
  {
    id: 4,
    title: "Billing inquiry about subscription",
    status: "open",
    priority: "medium",
    customer: "John Smith",
    email: "john@example.com",
    created: "2024-01-16T08:30:00Z",
    updated: "2024-01-16T08:30:00Z",
    assignee: "Sales Team",
    category: "Billing",
    description: "Questions about upgrading subscription plan.",
    replies: [
      {
        id: 1,
        author: "John Smith",
        message: "Questions about upgrading subscription plan.",
        timestamp: "2024-01-16T08:30:00Z",
        type: "customer",
      },
    ],
    notes: [],
  },
];

// Global variables
// let tickets = [...sampleTickets]; // Use sample data for initial tickets
let currentPage = "dashboard";
let currentTicket = null;
let currentTicketTab = "replies";
let currentSettingsTab = "basic";
let currentDashboardFilter = "recent";

// Initialize the application
document.addEventListener("DOMContentLoaded", function () {
  showPage("dashboard");
  updateDashboardStats();
  loadDashboardTickets();
  setupEventListeners();
});

// Event listeners
function setupEventListeners() {
  // Search input
  document
    .getElementById("search-input")
    .addEventListener("input", debounce(filterTickets, 300));

  // Filter inputs
  document
    .getElementById("customer-filter")
    .addEventListener("input", debounce(filterTickets, 300));
  document
    .getElementById("agent-filter")
    .addEventListener("input", debounce(filterTickets, 300));
  document
    .getElementById("status-filter")
    .addEventListener("change", filterTickets);

  // New ticket form
  document
    .getElementById("new-ticket-form")
    .addEventListener("submit", handleNewTicketSubmit);
}

// Utility functions
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

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString("en-US", {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function getStatusClass(status) {
  switch (status) {
    case "open":
      return "status-open";
    case "in-progress":
      return "status-in-progress";
    case "resolved":
      return "status-resolved";
    default:
      return "bg-gray-100 text-gray-800";
  }
}

function getPriorityClass(priority) {
  switch (priority) {
    case "high":
      return "priority-high";
    case "medium":
      return "priority-medium";
    case "low":
      return "priority-low";
    default:
      return "bg-gray-100 text-gray-800";
  }
}

function showToast(message, type = "success") {
  const toast = document.getElementById("toast");
  const toastMessage = document.getElementById("toast-message");

  toastMessage.textContent = message;
  toast.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg ${
    type === "success" ? "toast-success" : "toast-error"
  }`;
  toast.classList.remove("hidden");

  setTimeout(() => {
    toast.classList.add("hidden");
  }, 3000);
}

// Navigation functions
function showPage(pageName) {
  // Hide all pages
  document.querySelectorAll(".page-content").forEach((page) => {
    page.classList.add("hidden");
  });

  // Show selected page
  document.getElementById(pageName + "-page").classList.remove("hidden");

  // Update navigation
  document.querySelectorAll(".nav-link").forEach((link) => {
    link.classList.remove("active");
  });
  document.querySelector(`[data-page="${pageName}"]`).classList.add("active");

  currentPage = pageName;

  // Load page-specific content
  if (pageName === "tickets") {
    loadTicketsTable();
  } else if (pageName === "settings") {
    showSettingsTab("basic");
  } else if (pageName === "dashboard") {
    loadDashboardTickets();
  }
}

// Dashboard functions
function updateDashboardStats() {
  const stats = {
    total: tickets.length,
    open: tickets.filter((t) => t.status === "open").length,
    inProgress: tickets.filter((t) => t.status === "in-progress").length,
    resolved: tickets.filter((t) => t.status === "resolved").length,
  };

  document.getElementById("total-tickets").innerHTML = stats.total;
  document.getElementById("open-tickets").innerHTML = stats.open;
  document.getElementById("in-progress-tickets").innerHTML = stats.inProgress;
  document.getElementById("resolved-tickets").innerHTML = stats.resolved;
}

function filterDashboardTickets(filterType) {
  // Update active filter button
  document.querySelectorAll(".dashboard-filter-btn").forEach((btn) => {
    btn.classList.remove("active");
  });
  document
    .querySelector(`[data-filter="${filterType}"]`)
    .classList.add("active");

  currentDashboardFilter = filterType;
  loadDashboardTickets();
}

function loadDashboardTickets() {
  const tableBody = document.getElementById("dashboard-tickets-table");
  let filteredTickets = [];

  switch (currentDashboardFilter) {
    case "recent":
      filteredTickets = tickets
        .slice()
        .sort((a, b) => new Date(b.updated) - new Date(a.updated))
        .slice(0, 5);
      break;
    case "replies":
      filteredTickets = tickets
        .filter((t) => t.replies && t.replies.length > 0)
        .sort((a, b) => new Date(b.updated) - new Date(a.updated))
        .slice(0, 5);
      break;
    case "waiting":
      filteredTickets = tickets
        .filter((t) => t.status === "open" || t.status === "in-progress")
        .sort((a, b) => new Date(a.updated) - new Date(b.updated))
        .slice(0, 5);
      break;
  }

  tableBody.innerHTML = filteredTickets
    .map(
      (ticket) => `
        <tr class="table-row cursor-pointer" onclick="viewTicket(${ticket.id})">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">#${
                  ticket.id
                }</div>
                <div class="text-sm text-gray-500 truncate max-w-xs">${
                  ticket.title
                }</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
            <div class="text-sm font-medium text-gray-900">${
              ticket.customer
            }</div>
            <div class="text-sm font-medium text-gray-900">${ticket.email}</div>
            </td>
            <td>
            <div class="text-sm font-medium text-gray-900">${
              ticket.source
            }</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${getStatusClass(
                  ticket.status
                )} editable-status" onclick="event.stopPropagation(); editStatus(${
        ticket.id
      }, this)">
                    ${ticket.status.replace("-", " ")}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${getPriorityClass(
                  ticket.priority
                )} editable-priority" onclick="event.stopPropagation(); editPriority(${
        ticket.id
      }, this)">
                    ${ticket.priority}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${formatDate(ticket.updated)}
            </td>
        </tr>
    `
    )
    .join("");
}

// Inline editing functions for dashboard
function editStatus(ticketId, element) {
  const currentStatus = tickets.find((t) => t.id === ticketId).status;
  const select = document.createElement("select");
  select.className = "styled-select text-xs";
  select.innerHTML = `
        <option value="open" ${
          currentStatus === "open" ? "selected" : ""
        }>Open</option>
        <option value="in-progress" ${
          currentStatus === "in-progress" ? "selected" : ""
        }>In Progress</option>
        <option value="resolved" ${
          currentStatus === "resolved" ? "selected" : ""
        }>Resolved</option>
    `;

  select.onchange = function () {
    updateTicketStatus(ticketId, this.value);
    loadDashboardTickets();
    updateDashboardStats();
  };

  select.onblur = function () {
    loadDashboardTickets();
  };

  element.replaceWith(select);
  select.focus();
}

function editPriority(ticketId, element) {
  const currentPriority = tickets.find((t) => t.id === ticketId).priority;
  const select = document.createElement("select");
  select.className = "styled-select text-xs";
  select.innerHTML = `
        <option value="low" ${
          currentPriority === "low" ? "selected" : ""
        }>Low</option>
        <option value="medium" ${
          currentPriority === "medium" ? "selected" : ""
        }>Medium</option>
        <option value="high" ${
          currentPriority === "high" ? "selected" : ""
        }>High</option>
    `;

  select.onchange = function () {
    updateTicketPriority(ticketId, this.value);
    loadDashboardTickets();
  };

  select.onblur = function () {
    loadDashboardTickets();
  };

  element.replaceWith(select);
  select.focus();
}

// New ticket functions
function handleNewTicketSubmit(event) {
  event.preventDefault();

  const formData = {
    customerName: document.getElementById("customer-name").value,
    customerEmail: document.getElementById("customer-email").value,
    title: document.getElementById("ticket-title").value,
    priority: document.getElementById("ticket-priority").value,
    category: document.getElementById("ticket-category").value,
    assignee: document.getElementById("ticket-assignee").value,
    description: document.getElementById("ticket-description").value,
  };

  // Validate form
  if (!validateNewTicketForm(formData)) {
    return;
  }

  // Create new ticket
  const newTicket = {
    id: Math.max(...tickets.map((t) => t.id)) + 1,
    title: formData.title,
    status: "open",
    priority: formData.priority,
    customer: formData.customerName,
    email: formData.customerEmail,
    created: new Date().toISOString(),
    updated: new Date().toISOString(),
    assignee: formData.assignee,
    category: formData.category,
    description: formData.description,
    replies: [
      {
        id: 1,
        author: formData.customerName,
        message: formData.description,
        timestamp: new Date().toISOString(),
        type: "customer",
      },
    ],
    notes: [],
  };

  tickets.push(newTicket);
  updateDashboardStats();

  // Reset form
  document.getElementById("new-ticket-form").reset();

  showToast("Ticket created successfully");
  showPage("tickets");
}

function validateNewTicketForm(formData) {
  let isValid = true;

  // Clear previous errors
  document
    .querySelectorAll(".form-error")
    .forEach((el) => el.classList.remove("form-error"));
  document.querySelectorAll(".error-message").forEach((el) => el.remove());

  // Validate required fields
  const requiredFields = [
    "customerName",
    "customerEmail",
    "title",
    "priority",
    "category",
    "assignee",
    "description",
  ];

  requiredFields.forEach((field) => {
    if (!formData[field] || formData[field].trim() === "") {
      const element = document.getElementById(
        field.replace(/([A-Z])/g, "-$1").toLowerCase()
      );
      element.classList.add("form-error");

      const errorMsg = document.createElement("div");
      errorMsg.className = "error-message";
      errorMsg.textContent = "This field is required";
      element.parentNode.appendChild(errorMsg);

      isValid = false;
    }
  });

  // Validate email
  if (formData.customerEmail && !isValidEmail(formData.customerEmail)) {
    const element = document.getElementById("customer-email");
    element.classList.add("form-error");

    const errorMsg = document.createElement("div");
    errorMsg.className = "error-message";
    errorMsg.textContent = "Please enter a valid email address";
    element.parentNode.appendChild(errorMsg);

    isValid = false;
  }

  return isValid;
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// Tickets page functions
function loadTicketsTable() {
  const tableBody = document.getElementById("tickets-table");

  tableBody.innerHTML = tickets
    .map(
      (ticket) => `
        <tr class="table-row">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">#${
                  ticket.id
                }</div>
                <div class="text-sm text-gray-500 truncate max-w-xs">${
                  ticket.title
                }</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${ticket.customer}</div>
                <div class="text-sm text-gray-500">${ticket.email}</div>
            </td>
           
           
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${getStatusClass(ticket.status)}">
                    ${ticket.status.replace("-", " ")}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${getPriorityClass(ticket.priority)}">
                    ${ticket.priority}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${
              ticket.assignee
            }</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${formatDate(ticket.updated)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="viewTicket(${
                  ticket.id
                })" class="text-blue-600 hover:text-blue-900">
                    View
                </button>
            </td>
        </tr>
    `
    )
    .join("");
}

function filterTickets() {
  const searchTerm = document
    .getElementById("search-input")
    .value.toLowerCase();
  const customerFilter = document
    .getElementById("customer-filter")
    .value.toLowerCase();
  const agentFilter = document
    .getElementById("agent-filter")
    .value.toLowerCase();
  const statusFilter = document.getElementById("status-filter").value;

  const filteredTickets = tickets.filter((ticket) => {
    const matchesSearch =
      ticket.title.toLowerCase().includes(searchTerm) ||
      ticket.customer.toLowerCase().includes(searchTerm) ||
      ticket.email.toLowerCase().includes(searchTerm);
    const matchesCustomer =
      !customerFilter || ticket.customer.toLowerCase().includes(customerFilter);
    const matchesAgent =
      !agentFilter || ticket.assignee.toLowerCase().includes(agentFilter);
    const matchesStatus =
      statusFilter === "all" || ticket.status === statusFilter;

    return matchesSearch && matchesCustomer && matchesAgent && matchesStatus;
  });

  const tableBody = document.getElementById("tickets-table");
  tableBody.innerHTML = filteredTickets
    .map(
      (ticket) => `
        <tr class="table-row">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">#${
                  ticket.id
                }</div>
                <div class="text-sm text-gray-500 truncate max-w-xs">${
                  ticket.title
                }</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm text-gray-900">${ticket.customer}</div>
                <div class="text-sm text-gray-500">${ticket.email}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${getStatusClass(ticket.status)}">
                    ${ticket.status.replace("-", " ")}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${getPriorityClass(ticket.priority)}">
                    ${ticket.priority}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${
              ticket.assignee
            }</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                ${formatDate(ticket.updated)}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button onclick="viewTicket(${
                  ticket.id
                })" class="text-blue-600 hover:text-blue-900">
                    View
                </button>
            </td>
        </tr>
    `
    )
    .join("");
}

// Ticket detail functions
function viewTicket(ticketId) {
  currentTicket = tickets.find((t) => t.id === ticketId);
  if (!currentTicket) return;

  showPage("ticket-detail");
  loadTicketDetail();
  showTicketTab("replies");
}

function loadTicketDetail() {
  if (!currentTicket) return;

  document.getElementById(
    "ticket-detail-title"
  ).textContent = `Ticket #${currentTicket.id} - ${currentTicket.title}`;

  // Load customer information
  const customerInfo = document.getElementById("customer-info");
  customerInfo.innerHTML = `
        <div class="text-sm">
            <div class="font-medium text-gray-900">${currentTicket.customer}</div>
            <div class="text-gray-500">${currentTicket.email}</div>
        </div>
    `;

  // Load ticket meta information
  const ticketMetaInfo = document.getElementById("ticket-meta-info");
  ticketMetaInfo.innerHTML = `
        <div class="text-sm">
            <div class="mb-2">
                <span class="font-medium text-gray-500">Category:</span>
                <span class="text-gray-900">${currentTicket.category}</span>
            </div>
            <div class="mb-2">
                <span class="font-medium text-gray-500">Created:</span>
                <span class="text-gray-900">${formatDate(
                  currentTicket.created
                )}</span>
            </div>
            <div>
                <span class="font-medium text-gray-500">Last Updated:</span>
                <span class="text-gray-900">${formatDate(
                  currentTicket.updated
                )}</span>
            </div>
        </div>
    `;

  // Set current values in dropdowns
  document.getElementById("ticket-status-select").value = currentTicket.status;
  document.getElementById("ticket-priority-select").value =
    currentTicket.priority;
  document.getElementById("ticket-assignee-select").value =
    currentTicket.assignee;

  // Update reply and note counts
  document.getElementById("replies-count").textContent =
    currentTicket.replies?.length || 0;
  document.getElementById("notes-count").textContent =
    currentTicket.notes?.length || 0;

  loadReplies();
  loadNotes();
}

// Customer tickets functions
function showCustomerTickets() {
  if (!currentTicket) return;

  const customerTickets = tickets.filter(
    (t) => t.customer === currentTicket.customer
  );
  const stats = {
    total: customerTickets.length,
    open: customerTickets.filter((t) => t.status === "open").length,
    inProgress: customerTickets.filter((t) => t.status === "in-progress")
      .length,
    resolved: customerTickets.filter((t) => t.status === "resolved").length,
  };

  // Update modal title
  document.getElementById(
    "customer-tickets-title"
  ).textContent = `${currentTicket.customer}'s Tickets`;

  // Update stats
  const statsContainer = document.getElementById("customer-tickets-stats");
  statsContainer.innerHTML = `
        <div class="customer-stat-card" onclick="filterCustomerTickets('open')">
            <div class="customer-stat-number text-red-600">${stats.open}</div>
            <div class="customer-stat-label">Open</div>
        </div>
        <div class="customer-stat-card" onclick="filterCustomerTickets('in-progress')">
            <div class="customer-stat-number text-yellow-600">${stats.inProgress}</div>
            <div class="customer-stat-label">In Progress</div>
        </div>
        <div class="customer-stat-card" onclick="filterCustomerTickets('resolved')">
            <div class="customer-stat-number text-green-600">${stats.resolved}</div>
            <div class="customer-stat-label">Resolved</div>
        </div>
    `;

  // Load all tickets initially
  loadCustomerTicketsList(customerTickets);

  // Show modal
  document.getElementById("customer-tickets-modal").classList.remove("hidden");
}

function hideCustomerTickets() {
  document.getElementById("customer-tickets-modal").classList.add("hidden");
}

function filterCustomerTickets(status) {
  const customerTickets = tickets.filter(
    (t) => t.customer === currentTicket.customer
  );
  const filtered = status
    ? customerTickets.filter((t) => t.status === status)
    : customerTickets;
  loadCustomerTicketsList(filtered);
}

function loadCustomerTicketsList(ticketList) {
  const listContainer = document.getElementById("customer-tickets-list");
  listContainer.innerHTML = ticketList
    .map(
      (ticket) => `
        <div class="customer-ticket-item" onclick="hideCustomerTickets(); viewTicket(${
          ticket.id
        })">
            <div class="flex justify-between items-center">
                <div>
                    <div class="font-medium text-sm">#${ticket.id} - ${
        ticket.title
      }</div>
                    <div class="text-xs text-gray-500">${formatDate(
                      ticket.created
                    )}</div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="status-badge ${getStatusClass(
                      ticket.status
                    )} text-xs">
                        ${ticket.status.replace("-", " ")}
                    </span>
                    <span class="status-badge ${getPriorityClass(
                      ticket.priority
                    )} text-xs">
                        ${ticket.priority}
                    </span>
                </div>
            </div>
        </div>
    `
    )
    .join("");
}

function showTicketTab(tabName) {
  // Update tab buttons
  document.querySelectorAll(".ticket-tab").forEach((tab) => {
    tab.classList.remove("active");
  });
  document.querySelector(`[data-tab="${tabName}"]`).classList.add("active");

  // Show/hide tab content
  document.querySelectorAll(".ticket-tab-content").forEach((content) => {
    content.classList.add("hidden");
  });
  document.getElementById(tabName + "-tab").classList.remove("hidden");

  currentTicketTab = tabName;
}

function loadReplies() {
  if (!currentTicket || !currentTicket.replies) return;

  const repliesList = document.getElementById("replies-list");
  repliesList.innerHTML = currentTicket.replies
    .map(
      (reply) => `
        <div class="p-4 rounded-lg ${
          reply.type === "customer" ? "reply-customer" : "reply-agent"
        }">
            <div class="flex justify-between items-center mb-2">
                <span class="font-medium text-sm text-gray-900">${
                  reply.author
                }</span>
                <span class="text-xs text-gray-500">${formatDate(
                  reply.timestamp
                )}</span>
            </div>
            <p class="text-sm text-gray-700">${reply.message}</p>
        </div>
    `
    )
    .join("");
}

function loadNotes() {
  if (!currentTicket || !currentTicket.notes) return;

  const notesList = document.getElementById("notes-list");
  notesList.innerHTML = currentTicket.notes
    .map(
      (note) => `
        <div class="p-4 rounded-lg note-internal">
            <div class="flex justify-between items-center mb-2">
                <span class="font-medium text-sm text-gray-900">${
                  note.author
                }</span>
                <span class="text-xs text-gray-500">${formatDate(
                  note.timestamp
                )}</span>
            </div>
            <p class="text-sm text-gray-700">${note.note}</p>
        </div>
    `
    )
    .join("");
}

function updateTicketStatus(ticketId, newStatus) {
  const ticketIndex = tickets.findIndex((t) => t.id === ticketId);
  if (ticketIndex !== -1) {
    tickets[ticketIndex].status = newStatus;
    tickets[ticketIndex].updated = new Date().toISOString();
    if (currentTicket && currentTicket.id === ticketId) {
      currentTicket.status = newStatus;
      currentTicket.updated = new Date().toISOString();
    }

    showToast(`Ticket status updated to ${newStatus.replace("-", " ")}`);
    updateDashboardStats();
  }
}

function updateTicketPriority(ticketId, newPriority) {
  const ticketIndex = tickets.findIndex((t) => t.id === ticketId);
  if (ticketIndex !== -1) {
    tickets[ticketIndex].priority = newPriority;
    tickets[ticketIndex].updated = new Date().toISOString();
    if (currentTicket && currentTicket.id === ticketId) {
      currentTicket.priority = newPriority;
      currentTicket.updated = new Date().toISOString();
    }

    showToast(`Ticket priority updated to ${newPriority}`);
  }
}

function updateTicketAssignee(ticketId, newAssignee) {
  const ticketIndex = tickets.findIndex((t) => t.id === ticketId);
  if (ticketIndex !== -1) {
    tickets[ticketIndex].assignee = newAssignee;
    tickets[ticketIndex].updated = new Date().toISOString();
    if (currentTicket && currentTicket.id === ticketId) {
      currentTicket.assignee = newAssignee;
      currentTicket.updated = new Date().toISOString();
    }

    showToast(`Ticket assigned to ${newAssignee}`);
  }
}

function addReply() {
  const replyText = document.getElementById("new-reply").value.trim();
  if (!replyText || !currentTicket) return;

  const newReply = {
    id: Date.now(),
    author: "Support Agent",
    message: replyText,
    timestamp: new Date().toISOString(),
    type: "agent",
  };

  if (!currentTicket.replies) currentTicket.replies = [];
  currentTicket.replies.push(newReply);
  currentTicket.updated = new Date().toISOString();

  // Update the ticket in the main array
  const ticketIndex = tickets.findIndex((t) => t.id === currentTicket.id);
  if (ticketIndex !== -1) {
    tickets[ticketIndex] = currentTicket;
  }

  document.getElementById("new-reply").value = "";
  document.getElementById("replies-count").textContent =
    currentTicket.replies.length;
  loadReplies();
  showToast("Reply added successfully");
}

function addNote() {
  const noteText = document.getElementById("new-note").value.trim();
  if (!noteText || !currentTicket) return;

  const newNote = {
    id: Date.now(),
    author: "Support Agent",
    note: noteText,
    timestamp: new Date().toISOString(),
  };

  if (!currentTicket.notes) currentTicket.notes = [];
  currentTicket.notes.push(newNote);
  currentTicket.updated = new Date().toISOString();

  // Update the ticket in the main array
  const ticketIndex = tickets.findIndex((t) => t.id === currentTicket.id);
  if (ticketIndex !== -1) {
    tickets[ticketIndex] = currentTicket;
  }

  document.getElementById("new-note").value = "";
  document.getElementById("notes-count").textContent =
    currentTicket.notes.length;
  loadNotes();
  showToast("Note added successfully");
}

// Settings functions
function showSettingsTab(tabName) {
  // Update tab buttons
  document.querySelectorAll(".settings-tab").forEach((tab) => {
    tab.classList.remove("active");
  });
  document.querySelector(`[data-tab="${tabName}"]`).classList.add("active");

  // Show/hide tab content
  document.querySelectorAll(".settings-tab-content").forEach((content) => {
    content.classList.add("hidden");
  });
  document.getElementById(tabName + "-tab").classList.remove("hidden");

  currentSettingsTab = tabName;
}

function saveSettings() {
  if (success === false) {
    showToast("Failed to save settings", "error");
  } else {
    showToast("Settings saved successfully", "success");
  }
}

function testConnection() {
  if (currentTab === "imap-tab") {
    showToast("Connection test successful", "success");
  } else {
    showToast("Connection test failed", "error");
  }
}

// Email template editor functions
function formatText(command, editorId) {
  const editor = document.getElementById(editorId);
  editor.focus();
  document.execCommand(command, false, null);
}

// Fetch emails function
function fetchEmails() {
  const fetchResults = document.getElementById("fetch-results");
  const fetchMessage = document.getElementById("fetch-message");

  // Simulate fetching emails
  setTimeout(() => {
    const newTickets = Math.floor(Math.random() * 5) + 1;
    const newReplies = Math.floor(Math.random() * 10) + 3;
    const totalEmails = newTickets + newReplies;

    fetchMessage.textContent = `Fetched ${totalEmails} new emails. ${newTickets} tickets and ${newReplies} replies.`;
    fetchResults.classList.remove("hidden");

    // Hide the message after 5 seconds
    setTimeout(() => {
      fetchResults.classList.add("hidden");
    }, 5000);
  }, 1000);
}
function robodesk_email_fetcher() {
  var phpadd = "<?php echo robodesk_fetch_emails();?>";
}

// Close modal when clicking outside
document.addEventListener("click", function (event) {
  const modal = document.getElementById("customer-tickets-modal");
  if (event.target === modal) {
    hideCustomerTickets();
  }
});
// custom JavaScript for Robodesk dashboard

// IMAP test connection function
// This function tests the IMAP connection using AJAX

// IMAP Save Settings AJAX handler
jQuery(document).ready(function ($) {
  $("#save-imap-settings").on("click", function (e) {
    e.preventDefault();

    var $form = $("#robodesk-imap-form");
    var $saveButton = $(this);
    $saveButton.prop("disabled", true).text("Saving...");
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "robodesk_imap_save_settings",
        nonce: $form.find('input[name="robodesk_imap_settings_nonce"]').val(),
        robodesk_imap_hostname: $form.find("#robodesk_imap_hostname").val(),
        robodesk_imap_port: $form.find("#robodesk_imap_port").val(),
        robodesk_imap_username: $form.find("#robodesk_imap_username").val(),
        robodesk_imap_password: $form.find("#robodesk_imap_password").val(),
        robodesk_imap_auth: $form.find("#robodesk_imap_auth").val(),
      },
      success: function (response) {
        if (response.success) {
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
        }
      },
      error: function () {
        showToast("An error occurred. Please try again.", "error");
      },
      complete: function () {
        $saveButton.prop("disabled", false).text("Save Settings");
      },
      // timeout: 5000,
    });
  });
});

// Handle Test Connection
jQuery(document).ready(function ($) {
  $(document).on("click", "#robodesk-imap-test", function () {
    var $form = $("#robodesk-imap-form");
    var $testButton = $(this);

    $testButton.prop("disabled", true).text("Testing...");

    $.ajax({
      url: ajaxurl, // Ensure ajaxurl is defined via wp_localize_script
      type: "POST",
      data: {
        action: "robodesk_imap_test_connection",
        nonce: $form.find('input[name="robodesk_imap_settings_nonce"]').val(),
        robodesk_imap_hostname: $form.find("#robodesk_imap_hostname").val(),
        robodesk_imap_port: $form.find("#robodesk_imap_port").val(),
        robodesk_imap_username: $form.find("#robodesk_imap_username").val(),
        robodesk_imap_password: $form.find("#robodesk_imap_password").val(),
        robodesk_imap_auth: $form.find("#robodesk_imap_auth").val(),
      },
      success: function (response) {
        console.log(response); // Debug response
        if (response.success) {
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
        }
      },
      error: function (xhr, status, error) {
        console.log("AJAX Error:", xhr, status, error);
        showToast("AJAX request failed: " + error, "error");
      },
      complete: function () {
        $testButton.prop("disabled", false).text("Test Connection Again");
      },
    });
  });
});

// email template settings AJAX handler
jQuery(document).ready(function ($) {
  $(document).on("click", "#robodesk-save-templates", function () {
    var $form = $("#robodesk_email_template_save");
    var $button = $(this);

    $button.prop("disabled", true).text("Saving Templates...");

    // Collect template data from contenteditable divs
    var templates = {
      "new-ticket": $("#new-ticket-template").html(),
      "ticket-resolved": $("#ticket-resolved-template").html(),
      "auto-reply": $("#auto-reply-template").html(),
    };

    $.ajax({
      url: ajaxurl, // Ensure ajaxurl is defined via wp_localize_script
      type: "POST",
      data: {
        action: "robodesk_email_template_save",
        nonce: $form.find('input[name="robodesk_email_template_nonce"]').val(),
        templates: templates,
      },
      success: function (response) {
        console.log(response); // Debug response
        if (response.success) {
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
        }
      },
      error: function (xhr, status, error) {
        console.log("AJAX Error:", xhr, status, error);
        showToast("AJAX request failed: " + error, "error");
      },
      complete: function () {
        $button.prop("disabled", false).text("Save Templates");
      },
    });
  });
});

//advanced settings AJAX handler
jQuery(document).ready(function ($) {
  $(document).on("click", "#save-advanced-settings", function () {
    var $form = $("#advanced-settings-form");
    var $button = $(this);

    $button.prop("disabled", true).text("Saving Advanced Settings...");

    // Collect form data
    var settings = {
      enable_auto_assign: $("#enable-auto-assign").is(":checked"),
      enable_notifications: $("#enable-notifications").is(":checked"),
      enable_customer_portal: $("#enable-customer-portal").is(":checked"),
      max_file_upload_size: $form.find('input[type="number"]').val(),
    };

    $.ajax({
      url: ajaxurl, // Ensure ajaxurl is defined via wp_localize_script
      type: "POST",
      data: {
        action: "robodesk_save_advanced_settings",
        nonce: $form
          .find('input[name="robodesk_advanced_settings_nonce"]')
          .val(),
        settings: settings,
      },
      success: function (response) {
        console.log(response); // Debug response
        if (response.success) {
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
        }
      },
      error: function (xhr, status, error) {
        console.log("AJAX Error:", xhr, status, error);
        showToast("AJAX request failed: " + error, "error");
      },
      complete: function () {
        $button.prop("disabled", false).text("Save Advanced Settings");
      },
    });
  });
});

// basic settings AJAX handler
jQuery(document).ready(function ($) {
  // Image preview handler for all file inputs
  const imageFields = [
    {
      input: "popover_icon",
      preview: "popover-icon-preview",
      hidden: "robodesk_popover_icon",
    },
    {
      input: "popover_top_icon",
      preview: "popover-top-icon-preview",
      hidden: "robodesk_popover_top_icon",
    },
    { input: "logo", preview: "logo-preview", hidden: "robodesk_logo" },
    {
      input: "wide_logo",
      preview: "wide-logo-preview",
      hidden: "robodesk_wide_logo",
    },
  ];

  imageFields.forEach(function (field) {
    // Show existing image on page load
    const hiddenInput = $(`#${field.hidden}`);
    if (hiddenInput.val()) {
      $(`#${field.preview}`)
        .removeClass("hidden")
        .attr("src", hiddenInput.val());
    }

    // Handle file input change for preview
    $(`input[name="${field.input}"]`).on("change", function (event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          $(`#${field.preview}`)
            .removeClass("hidden")
            .attr("src", e.target.result);
        };
        reader.readAsDataURL(file);
      }
    });
  });

  // Handle form submission for basic settings
  $(document).on("click", "#save-basic-settings", function () {
    const $form = $("#robodesk-basic-settings-form");
    const $button = $(this);
    const formData = new FormData();

    // Append basic settings
    formData.append("action", "robodesk_save_basic_settings");
    formData.append(
      "nonce",
      $form.find('input[name="robodesk_basic_settings_nonce"]').val()
    );
    formData.append("settings[site_name]", $form.find("#site_name").val());
    formData.append("settings[admin_email]", $form.find("#admin_email").val());
    formData.append("settings[timezone]", $form.find("#timezone").val());
    formData.append("settings[language]", $form.find("#language").val());

    // Append image URLs and files
    imageFields.forEach(function (field) {
      formData.append(field.hidden, $(`#${field.hidden}`).val());
      const fileInput = $(`input[name="${field.input}"]`)[0];
      if (fileInput.files.length > 0) {
        formData.append(field.input, fileInput.files[0]);
      }
    });

    $button.prop("disabled", true).text("Saving Settings...");

    $.ajax({
      url: ajaxurl, // Ensure ajaxurl is defined via wp_localize_script
      type: "POST",
      data: formData,
      processData: false, // Required for FormData with files
      contentType: false, // Required for FormData with files
      success: function (response) {
        console.log(response); // Debug response
        if (response.success) {
          // Update hidden inputs with new image URLs
          if (response.data.urls) {
            imageFields.forEach(function (field) {
              if (response.data.urls[field.hidden]) {
                $(`#${field.hidden}`).val(response.data.urls[field.hidden]);
                $(`#${field.preview}`)
                  .removeClass("hidden")
                  .attr("src", response.data.urls[field.hidden]);
              }
            });
          }
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
        }
      },
      error: function (xhr, status, error) {
        console.log("AJAX Error:", xhr, status, error);
        showToast("AJAX request failed: " + error, "error");
      },
      complete: function () {
        $button.prop("disabled", false).text("Save Settings");
      },
    });
  });
});

// for configuring ticket assignments (fetch interval and default agent)
jQuery(document).ready(function ($) {
  // Handle form submission for ticket assignment settings
  $(document).on("click", "#save-ticket-assignment-settings", function () {
    const $form = $("#robodesk-fetch-tickets-form");
    const $button = $(this);
    const formData = new FormData();

    // Append ticket assignment settings
    formData.append("action", "robodesk_save_ticket_assignment_settings");
    formData.append(
      "nonce",
      $form.find('input[name="robodesk_ticket_assignment_nonce"]').val()
    );
    formData.append(
      "fetch_interval",
      $form.find('input[name="fetch_interval"]').val()
    );
    formData.append(
      "default_agent",
      $form.find('select[name="default_agent"]').val()
    );

    // Basic validation
    if (!$form.find('input[name="fetch_interval"]').val()) {
      showToast("Please enter a fetch interval.", "error");
      return;
    }
    if (!$form.find('select[name="default_agent"]').val()) {
      showToast("Please select a team for auto-assignment.", "error");
      return;
    }

    $button.prop("disabled", true).text("Configuring Auto-Fetch Settings...");

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
        }
      },
      error: function (xhr, status, error) {
        console.log("AJAX Error:", xhr, status, error);
        showToast("AJAX request failed: " + error, "error");
      },
      complete: function () {
        $button.prop("disabled", false).text("Configure Auto-Fetch");
      },
    });
  });
});

//create a new ticket ajax
jQuery(document).ready(function ($) {
  // Handle form submission for ticket creation
  $("#robodesk-new-ticket-form").on("submit", function (e) {
    e.preventDefault(); // Prevent default form submission

    const $form = $(this);
    const $button = $form.find("#robodesk-create-ticket");
    const formData = new FormData();

    // Append ticket data
    formData.append("action", "robodesk_create_ticket");
    formData.append(
      "nonce",
      $form.find('input[name="robodesk_create_ticket_nonce"]').val()
    );
    formData.append("customer_name", $("#customer-name").val());
    formData.append("customer_email", $("#customer-email").val());
    formData.append("ticket_title", $("#ticket-title").val());
    formData.append("ticket_priority", $("#ticket-priority").val());
    formData.append("ticket_category", $("#ticket-category").val());
    formData.append("ticket_assignee", $("#ticket-assignee").val());
    formData.append("ticket_description", $("#ticket-description").val());
    formData.append(
      "ticket_source",
      $form.find('input[name="ticket_source"]').val()
    );

    // Basic client-side validation (HTML5 validation is already in place)
    if (!$form[0].checkValidity()) {
      $form[0].reportValidity(); // Show browser validation messages
      return;
    }

    // Additional client-side validation for priority
    const validPriorities = ["Low", "Medium", "High"];
    if (!validPriorities.includes($("#ticket-priority").val())) {
      showToast("Please select a valid priority.", "error");
      return;
    }

    $button.prop("disabled", true).text("Creating Ticket...");

    $.ajax({
      url: robodeskAjax.ajax_url, // Use localized AJAX URL
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          showToast(response.data.message, "success");
          $form[0].reset(); // Reset form on success
        } else {
          showToast(
            response.data.message || "Failed to create ticket.",
            "error"
          );
        }
      },
      error: function (xhr, status, error) {
        console.log("AJAX Error:", xhr, status, error);
        showToast("Failed to create ticket: " + error, "error");
      },
      complete: function () {
        $button.prop("disabled", false).text("Create Ticket");
      },
    });
  });
});
