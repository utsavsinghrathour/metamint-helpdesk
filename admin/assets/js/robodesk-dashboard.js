function loadDashboardTickets() {
  const tableBody = document.getElementById("dashboard-tickets-table");
  let filteredTickets = [];
  const current_user_name =
    robodeskCurrentUser?.name || robodeskCurrentUser?.display_name || "";

  // Filter dashboard_tickets based on currentDashboardFilter
  switch (currentDashboardFilter) {
    case "my_conversation":
      filteredTickets = dashboard_tickets
        .filter((t) => t.assignee === current_user_name)
        .sort((a, b) => new Date(b.updated) - new Date(a.updated));
      break;
    case "unassigned_conversation":
      filteredTickets = dashboard_tickets
        .filter((t) => t.assignee === "N/A" || !t.assignee)
        .sort((a, b) => new Date(b.updated) - new Date(a.updated));
      break;
    case "open_conversation":
      filteredTickets = dashboard_tickets
        .filter((t) => t.status === "open" || t.status === "in-progress")
        .sort((a, b) => new Date(b.updated) - new Date(a.updated));
      break;
    case "chat_conversation":
      filteredTickets = dashboard_tickets
        .filter((t) => t.source === "chat")
        .sort((a, b) => new Date(b.updated) - new Date(a.updated));
      break;
    default:
      filteredTickets = dashboard_tickets
        .slice()
        .sort((a, b) => new Date(b.updated) - new Date(a.updated));
      break;
  }

  // Check if we need to show assignee column
  const showAssigneeColumn =
    currentDashboardFilter === "open_conversation" ||
    currentDashboardFilter === "chat_conversation";

  function getBlinkingClass(ticket) {
    // Ensure ticket is from chat and status is open/in-progress
    const isChat = ticket.source === "chat";
    const isActiveStatus = ["open", "in-progress"].includes(ticket.status);

    if (!isChat || !isActiveStatus) {
      return "";
    }

    const replies = Array.isArray(ticket.replies) ? ticket.replies : [];
    const lastReply = replies.length > 0 ? replies[replies.length - 1] : null;

    const now = new Date();
    const lastReplyTime = new Date(
      lastReply?.timestamp || lastReply?.date || ticket.updated
    );
    const isValidDate =
      lastReplyTime instanceof Date &&
      !isNaN(lastReplyTime) &&
      lastReplyTime <= now;

    const diffMinutes = isValidDate
      ? (now - lastReplyTime) / (1000 * 60)
      : null;

    const isCustomerReply =
      lastReply &&
      (lastReply.author === ticket.customer ||
        lastReply.author === ticket.email ||
        lastReply.type === "customer" ||
        lastReply.author_type === "customer");

    // Case: RED
    if (
      isCustomerReply &&
      diffMinutes !== null &&
      diffMinutes > 3 &&
      diffMinutes < 60
    ) {
      return "blinking-red";
    }

    // Case: YELLOW
    if (isCustomerReply && diffMinutes !== null && diffMinutes < 3) {
      return "blinking-yellow";
    }

    // Case: GREEN (only if chat + status matches)
    return "blinking-green";
  }

  // Update table header based on filter
  updateTableHeader(showAssigneeColumn);

  // Build the table HTML
  tableBody.innerHTML =
    filteredTickets.length === 0
      ? `<tr><td colspan="${
          showAssigneeColumn ? 8 : 7
        }" class="px-6 py-4 text-center text-gray-500">No tickets found for this filter.</td></tr>`
      : filteredTickets
          .map(
            (ticket) => `
            <tr class="table-row cursor-pointer" data-ticketid="${ticket.id}">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">#${
                  ticket.id
                } <span class="${getBlinkingClass(ticket)}"></span></div>
                <div class="text-sm text-gray-500 truncate max-w-xs">${escapeHTML(
                  ticket.title
                )}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                ${
                  Array.isArray(ticket.replies) && ticket.replies.length > 0
                    ? (() => {
                        const lastReply =
                          ticket.replies[ticket.replies.length - 1];
                        const lastReplyAuthor = lastReply.author || "N/A";
                        const lastReplyDate =
                          lastReply.timestamp ||
                          lastReply.date ||
                          ticket.updated;
                        const timeAgo = timeSince(new Date(lastReplyDate));
                        return `
                          <div class="text-sm font-medium text-gray-900">${escapeHTML(
                            lastReplyAuthor
                          )}</div>
                          <div class="text-sm text-gray-500">${timeAgo}</div>
                        `;
                      })()
                    : `<div class="text-sm text-gray-500">No replies yet</div>`
                }
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${escapeHTML(
                  ticket.customer
                )}</div>
                <div class="text-sm text-gray-500 truncate max-w-xs">${escapeHTML(
                  ticket.email
                )}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${escapeHTML(
                  ticket.source
                )}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${getStatusClass(
                  ticket.status
                )} editable-status"
                  onclick="event.stopPropagation(); editStatus(${
                    ticket.id
                  }, this)">
                  ${escapeHTML(ticket.status.replace("-", " "))}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${getPriorityClass(
                  ticket.priority
                )} editable-priority"
                  style="background-color: ${
                    typeof robodeskPriorityColors !== "undefined" &&
                    robodeskPriorityColors[ticket.priority]
                      ? robodeskPriorityColors[ticket.priority]
                      : ""
                  }"
                  onclick="event.stopPropagation(); editPriority(${
                    ticket.id
                  }, this)">
                  ${escapeHTML(ticket.priority)}
                </span>
              </td>
              ${
                showAssigneeColumn
                  ? `
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900 editable-assignee"
                  onclick="event.stopPropagation(); editAssignee(${
                    ticket.id
                  }, this)">
                  ${escapeHTML(ticket.assignee || "Unassigned")}
                </span>
              </td>
              `
                  : ""
              }
            </tr>
          `
          )
          .join("");

  // Add event delegation for row clicks
  tableBody.addEventListener("click", function (event) {
    const row = event.target.closest("tr.table-row");
    if (row) {
      const ticketId = parseInt(row.dataset.ticketid, 10);
      if (ticketId) {
        viewTicket(ticketId);
      } else {
        console.error("No ticket ID found for row:", row);
      }
    }
  });
}

// Helper function to update table header based on whether assignee column should be shown
function updateTableHeader(showAssigneeColumn) {
  const tableHeader = document
    .querySelector("#dashboard-tickets-table")
    .closest("table")
    .querySelector("thead tr");

  if (!tableHeader) return;

  // Define the standard headers
  const standardHeaders = [
    "Conversation",
    "Last Reply",
    "Customer",
    "Source",
    "Status",
    "Priority",
  ];

  // Add assignee header if needed
  const headers = showAssigneeColumn
    ? [...standardHeaders, "Assignee"]
    : standardHeaders;

  // Update the header HTML
  tableHeader.innerHTML = headers
    .map(
      (header) =>
        `<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">${header}</th>`
    )
    .join("");
}

// Optional: Add function to handle assignee editing if it doesn't exist
function editAssignee(ticketId, element) {
  const currentAssignee = element.textContent.trim();

  // Create a simple input for editing
  const input = document.createElement("input");
  input.type = "text";
  input.value = currentAssignee === "Unassigned" ? "" : currentAssignee;
  input.className =
    "w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500";

  // Replace the span with input
  element.replaceWith(input);
  input.focus();
  input.select();

  // Handle save on Enter or blur
  const saveAssignee = () => {
    const newAssignee = input.value.trim() || "Unassigned";

    // Create new span element
    const newSpan = document.createElement("span");
    newSpan.className = "text-sm font-medium text-gray-900 editable-assignee";
    newSpan.textContent = newAssignee;
    newSpan.onclick = (e) => {
      e.stopPropagation();
      editAssignee(ticketId, newSpan);
    };

    // Replace input with span
    input.replaceWith(newSpan);

    // Update the ticket data
    const ticket = dashboard_tickets.find((t) => t.id === ticketId);
    if (ticket) {
      ticket.assignee = newAssignee;
    }

    // Here you would typically send an AJAX request to update the assignee on the server
    showToast(`Updated ticket ${ticketId} assignee to: ${newAssignee}`);

    // You can add your server update logic here
    // updateTicketAssignee(ticketId, newAssignee);
  };

  // Save on Enter
  input.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      saveAssignee();
    } else if (e.key === "Escape") {
      // Cancel editing
      const cancelSpan = document.createElement("span");
      cancelSpan.className =
        "text-sm font-medium text-gray-900 editable-assignee";
      cancelSpan.textContent = currentAssignee;
      cancelSpan.onclick = (e) => {
        e.stopPropagation();
        editAssignee(ticketId, cancelSpan);
      };
      input.replaceWith(cancelSpan);
    }
  });

  // Save on blur
  input.addEventListener("blur", saveAssignee);
}

// Call when DOM is ready
document.addEventListener("DOMContentLoaded", loadDashboardTickets);

// Helper function to escape HTML (if not already defined)
function escapeHTML(str) {
  if (!str) return "";
  const div = document.createElement("div");
  div.textContent = str;
  return div.innerHTML;
}

// Helper function to calculate time since (if not already defined)
function timeSince(date) {
  const now = new Date();
  const secondsPast = (now.getTime() - date.getTime()) / 1000;

  if (secondsPast < 60) {
    return `${Math.floor(secondsPast)} seconds ago`;
  }
  if (secondsPast < 3600) {
    return `${Math.floor(secondsPast / 60)} minutes ago`;
  }
  if (secondsPast <= 86400) {
    return `${Math.floor(secondsPast / 3600)} hours ago`;
  }
  if (secondsPast > 86400) {
    const day = Math.floor(secondsPast / 86400);
    return `${day} day${day > 1 ? "s" : ""} ago`;
  }
}

// Helper function to format date (if not already defined)
function formatDate(dateString) {
  const date = new Date(dateString);
  const options = {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    hour12: false,
  };
  return date.toLocaleDateString("en-US", options);
}

// Function to handle filter changes
function setDashboardFilter(filter) {
  currentDashboardFilter = filter;
  loadDashboardTickets();

  // Update active filter button styling
  document.querySelectorAll(".filter-button").forEach((btn) => {
    btn.classList.remove("active");
  });
  document.querySelector(`[data-filter="${filter}"]`)?.classList.add("active");
}

// Function to refresh tickets data
function refreshDashboardTickets() {
  // If you're loading tickets via AJAX, call that function here
  // For example:
  if (typeof loadTicketsFromServer === "function") {
    loadTicketsFromServer().then(() => {
      loadDashboardTickets();
    });
  } else {
    // Otherwise just re-render with current data
    loadDashboardTickets();
  }
}

// Sample data

//for ticket varaibles show and hide
function toggleAccordion() {
  const content = document.getElementById("accordion-content");
  const btn = document.getElementById("toggle-btn");

  const isHidden = content.classList.contains("hidden");
  if (isHidden) {
    content.classList.remove("hidden");
    btn.textContent = "Hide";
  } else {
    content.classList.add("hidden");
    btn.textContent = "Show";
  }
}
//search form creating user and email

document.addEventListener("DOMContentLoaded", function () {
  const nameInput = document.getElementById("customer-name");
  const emailInput = document.getElementById("customer-email");
  const nameSuggestions = document.getElementById("customer-name-suggestions");
  const emailSuggestions = document.getElementById(
    "customer-email-suggestions"
  );

  function fetchSuggestions(type, value, callback) {
    fetch(ajaxurl, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        action: "robodesk_user_lookup",
        type: type,
        value: value,
      }),
    })
      .then((res) => res.json())
      .then(callback);
  }

  function populateSuggestions(container, items, onSelect) {
    container.innerHTML = "";
    if (!items.length) {
      container.classList.add("hidden");
      return;
    }
    items.forEach((user) => {
      const div = document.createElement("div");
      div.className = "cursor-pointer px-3 py-2 hover:bg-blue-100 text-sm";
      div.textContent = `${user.display_name} (${user.user_email})`;
      div.addEventListener("click", () => onSelect(user));
      container.appendChild(div);
    });
    container.classList.remove("hidden");
  }

  nameInput.addEventListener("keyup", () => {
    const value = nameInput.value.trim();
    if (value.length < 2) {
      nameSuggestions.classList.add("hidden");
      return;
    }
    fetchSuggestions("name", value, (users) => {
      populateSuggestions(nameSuggestions, users, (user) => {
        nameInput.value = user.display_name;
        emailInput.value = user.user_email;
        nameSuggestions.classList.add("hidden");
      });
    });
  });

  emailInput.addEventListener("keyup", () => {
    const value = emailInput.value.trim();
    if (value.length < 3) {
      emailSuggestions.classList.add("hidden");
      return;
    }
    fetchSuggestions("email", value, (users) => {
      populateSuggestions(emailSuggestions, users, (user) => {
        emailInput.value = user.user_email;
        nameInput.value = user.display_name;
        emailSuggestions.classList.add("hidden");
      });
    });
  });

  // Hide suggestion boxes on click outside
  document.addEventListener("click", (e) => {
    if (!nameSuggestions.contains(e.target))
      nameSuggestions.classList.add("hidden");
    if (!emailSuggestions.contains(e.target))
      emailSuggestions.classList.add("hidden");
  });
});

//basic setting delete for the images for basic settings
function removeImage(previewId, inputId) {
  const preview = document.getElementById(previewId);
  const input = document.getElementById(inputId);
  const button = preview.nextElementSibling;

  preview.src = "";
  preview.classList.add("hidden");
  input.value = "";
  button.classList.add("hidden");
}

let currentPage = "dashboard";
let currentTicket = null;
let currentTicketTab = "replies";
let currentSettingsTab = "basic";
let currentDashboardFilter = "my_conversation"; // Default filter for dashboard

// Initialize the application
document.addEventListener("DOMContentLoaded", function () {
  showPage("dashboard");
  // updateDashboardStats();
  loadDashboardTickets();
  // setupEventListeners();
});

// added ends

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
    case "closed":
      return "status-closed";
    default:
      return "bg-gray-100 text-gray-800";
  }
}

function getPriorityClass(priority) {
  // Use the priority colors from PHP if available
  if (
    typeof robodeskPriorityColors !== "undefined" &&
    robodeskPriorityColors[priority]
  ) {
    return `priority-${priority.toLowerCase()}`;
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
  const navLink = document.querySelector(`[data-page="${pageName}"]`);
  if (navLink) {
    navLink.classList.add("active");
  }
  if (pageName === "conversation") {
    // Load conversation view
  } else if (pageName === "ticket-detail") {
    loadTicketDetail();
  } else if (pageName === "settings") {
    showSettingsTab("basic");
  } else if (pageName === "dashboard") {
    loadDashboardTickets();
  }
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

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// Ticket detail functions
function viewTicket(ticketId) {
  // Convert ticketId to number to ensure type consistency
  ticketId = parseInt(ticketId, 10);
  currentTicket = dashboard_tickets.find((t) => t.id === ticketId);

  if (!currentTicket) {
    console.error(`Ticket with ID ${ticketId} not found in dashboard_tickets`);
    return;
  }

  // Update URL with ticket ID (without reloading)
  if (history.pushState) {
    const url = new URL(window.location);
    url.searchParams.set("ticket", ticketId);
    history.pushState({ ticketId }, "", url);
  }

  showPage("ticket-detail");
  loadTicketDetail();
  showTicketTab("replies");
}

// On page load, check for ticket param and show ticket if present
document.addEventListener("DOMContentLoaded", function () {
  const params = new URLSearchParams(window.location.search);
  const ticketId = params.get("ticket");
  if (ticketId) {
    viewTicket(Number(ticketId));
  }
});

// On page load, check for paged param and show the paged tickets
document.addEventListener("DOMContentLoaded", function () {
  const params = new URLSearchParams(window.location.search);
  const paged = params.get("paged");
  if (paged) {
    //show page tickets and load the paged tickets
    showPage("tickets");
  }
  // If no ticket is selected, show the dashboard
  showPage("dashboard");
});

// Function to create category dropdown options
function createCategoryOptions() {
  let options = `<option value="${currentTicket.category.id}" selected>${currentTicket.category.name}</option>`;

  // If we have a default category object, show it first
  if (robodeskDefaultCategoryObj) {
    options += `<option value="${robodeskDefaultCategoryObj.term_id}" selected>${robodeskDefaultCategoryObj.name}</option>`;
  }

  // Add all other categories
  robodeskTicketCategories.forEach((category) => {
    // Skip if this is already added as default
    if (
      robodeskDefaultCategoryObj &&
      currentTicket.category.id === robodeskDefaultCategoryObj.term_id
    ) {
      return;
    }
    options += `<option value="${category.term_id}">${category.name}</option>`;
    //remove the 1st option
    if (category.term_id === currentTicket.category.id) {
      options = options.replace(
        `<option value="${category.term_id}">${category.name}</option>`,
        ""
      );
    }
  });

  return options;
}

// Function to update ticket category via AJAX (CORRECTED VERSION)
function updateTicketCategory(ticketId, categoryId) {
  // WordPress AJAX call
  jQuery.ajax({
    url: robodeskAjax.ajax_url, // Use your existing AJAX URL structure
    type: "POST",
    data: {
      action: "update_ticket_category",
      ticket_id: ticketId,
      category_id: categoryId,
      nonce: robodeskAjax.nonces.update_ticket_category, // Use your existing nonce structure
    },
    success: function (response) {
      if (response.success) {
        // console.log("Category updated successfully");
        // Use your existing showToast function
        showToast("Category updated successfully", "success");
      } else {
        console.error("Failed to update category:", response.data);
        showToast("Failed to update category", "error");
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX error:", error);
      showToast("Error updating category", "error");
    },
  });
}

// Updated loadTicketDetail function
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

  // Load ticket meta information with category dropdown
  const ticketMetaInfo = document.getElementById("ticket-meta-info");

  ticketMetaInfo.innerHTML = `
    <div class="text-sm space-y-4 bg-gray-50 p-2 rounded-lg shadow-sm">
      <div class="flex items-center space-x-2">
        <strong class="text-gray-700">Author Image:</strong>
        <img id="author-image" src="${
          currentTicket.author_image
        }" alt="Author Image" class="w-10 h-10 rounded-full border border-gray-300 shadow-sm">
      </div>
      <div>
        <strong class="text-gray-700">Origin:</strong>
        <span id="ticket-source" class="inline-block px-2 py-1 bg-blue-100 text-blue-700 rounded"><strong>${currentTicket.source.toUpperCase()}</strong></span>
      </div>
      <div>
        <strong class="text-gray-700">Category:</strong>
        <select id="ticket-category-select" class="ml-2 px-2 py-1 border border-gray-300 rounded text-sm bg-white focus:ring focus:ring-blue-200">
          ${createCategoryOptions()}
        </select>
      </div>
    </div>
  `;

  // Set the current category if ticket has one
  if (currentTicket.category && currentTicket.category.term_id) {
    document.getElementById("ticket-category-select").value =
      currentTicket.category.term_id;
  }

  // Add event listener for category change
  document
    .getElementById("ticket-category-select")
    .addEventListener("change", function (e) {
      const newCategoryId = e.target.value;
      updateTicketCategory(currentTicket.id, newCategoryId);

      // Update the current ticket object
      const selectedCategory = robodeskTicketCategories.find(
        (cat) => cat.term_id == newCategoryId
      );
      if (selectedCategory) {
        currentTicket.category = selectedCategory;
      }
    });

  // Set current values in other dropdowns
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
    closed: customerTickets.filter((t) => t.status === "closed").length,
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
      
        <div class="customer-stat-card" onclick="filterCustomerTickets('closed')">
            <div class="customer-stat-number text-green-600">${stats.closed}</div>
            <div class="customer-stat-label">Closed</div>
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
// shows the customer tickets in the modal  ie view all customer tickets
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
                        ${ticket.status}
                    </span>
                    <span class="status-badge" style="background-color: ${
                      typeof robodeskPriorityColors !== "undefined" &&
                      robodeskPriorityColors[ticket.priority]
                        ? robodeskPriorityColors[ticket.priority]
                        : ""
                    }" ">
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
  // Optional: hide previous message
  document.getElementById("fetch-results").classList.add("hidden");

  fetch(robodeskAjax.ajax_url, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: new URLSearchParams({
      action: "robodesk_fetch_emails",
      nonce: robodeskAjax.nonces.fetch_emails,
    }),
  })
    .then((response) => {
      if (!response.ok) throw new Error("Network error");
      return response.json();
    })
    .then((data) => {
      if (data.success) {
        const message = data.data;
        document.getElementById("fetch-message").textContent = message;
        document.getElementById("fetch-results").classList.remove("hidden");
      } else {
        document.getElementById("fetch-message").textContent =
          data.data || "An error occurred";
        document.getElementById("fetch-results").classList.remove("hidden");
      }
    })
    .catch((error) => {
      console.error("AJAX error:", error);
      document.getElementById("fetch-message").textContent =
        "AJAX error occurred.";
      document.getElementById("fetch-results").classList.remove("hidden");
    });
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
        // console.log(response); // Debug response
        if (response.success) {
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
        }
      },
      error: function (xhr, status, error) {
        // console.log("AJAX Error:", xhr, status, error);
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
      "ticket-closed": $("#ticket-closed-template").html(),
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
        // console.log(response); // Debug response
        if (response.success) {
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
        }
      },
      error: function (xhr, status, error) {
        // console.log("AJAX Error:", xhr, status, error);
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
        // console.log(response); // Debug response
        if (response.success) {
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
        }
      },
      error: function (xhr, status, error) {
        // console.log("AJAX Error:", xhr, status, error);
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
  // Define removeImage function
  window.removeImage = function (previewId, hiddenId, inputId) {
    $("#" + previewId)
      .attr("src", "")
      .addClass("hidden");
    $("#" + hiddenId).val("");
    $("#" + inputId).val("");
    $("#" + previewId)
      .next("button")
      .addClass("hidden");
  };

  // Media preview handler for image file inputs
  const mediaFields = [
    {
      input: "popover_icon",
      preview: "popover-icon-preview",
      hidden: "robodesk_popover_icon",
      type: "image",
    },
    {
      input: "popover_top_icon",
      preview: "popover-top-icon-preview",
      hidden: "robodesk_popover_top_icon",
      type: "image",
    },
    {
      input: "logo",
      preview: "logo-preview",
      hidden: "robodesk_logo",
      type: "image",
    },
    {
      input: "wide_logo",
      preview: "wide-logo-preview",
      hidden: "robodesk_wide_logo",
      type: "image",
    },
  ];

  // Theme color preview handler
  const themeColorInput = $("#theme_color");
  const themeColorPreview = $("#theme-color-preview");
  if (themeColorInput.val()) {
    themeColorPreview
      .removeClass("hidden")
      .css("background-color", themeColorInput.val());
  }

  themeColorInput.on("change", function () {
    themeColorPreview
      .removeClass("hidden")
      .css("background-color", $(this).val());
  });

  mediaFields.forEach(function (field) {
    // Show existing media on page load
    const hiddenInput = $(`#${field.hidden}`);
    if (hiddenInput.val()) {
      if (field.type === "image") {
        $(`#${field.preview}`)
          .removeClass("hidden")
          .attr("src", hiddenInput.val());
      }
    }

    // Handle file input change for preview
    $(`input[name="${field.input}"]`).on("change", function (event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
          if (field.type === "image") {
            $(`#${field.preview}`)
              .removeClass("hidden")
              .attr("src", e.target.result);
            // Show the remove button when an image is selected
            const removeButton = $(`#${field.preview}`).next("button");
            removeButton.removeClass("hidden");
          }
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
    formData.append("settings[theme_color]", $form.find("#theme_color").val());
    formData.append(
      "settings[default_ticket_category]",
      $form.find("#robodesk_default_ticket_category").val()
    );

    // Append media URLs and files
    mediaFields.forEach(function (field) {
      formData.append(field.hidden, $(`#${field.hidden}`).val());
      const fileInput = $(`input[name="${field.input}"]`)[0];
      if (fileInput && fileInput.files.length > 0) {
        formData.append(field.input, fileInput.files[0]);
      }
    });

    // Append notification sound URL
    formData.append(
      "robodesk_notification_sound",
      $("#robodesk_notification_sound").val()
    );

    $button.prop("disabled", true).text("Saving Settings...");

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        // console.log(response);
        if (response.success) {
          // Update hidden inputs with new media URLs
          if (response.data.urls) {
            mediaFields.forEach(function (field) {
              if (response.data.urls[field.hidden]) {
                $(`#${field.hidden}`).val(response.data.urls[field.hidden]);
                if (field.type === "image") {
                  $(`#${field.preview}`)
                    .removeClass("hidden")
                    .attr("src", response.data.urls[field.hidden]);
                }
              }
            });
            // Update notification sound preview
            if (response.data.urls.robodesk_notification_sound) {
              const audioPreview = $("#notification-sound-preview");
              const audioSource = audioPreview.find("source");
              audioSource.attr(
                "src",
                response.data.urls.robodesk_notification_sound
              );
              audioPreview.removeClass("hidden").get(0).load();
              $(
                `button[onclick="removeAudio('notification-sound-preview', 'robodesk_notification_sound')"]`
              ).removeClass("hidden");
              // Highlight the audio preview to draw attention
              audioPreview.addClass("border-2 border-blue-500");
              setTimeout(() => {
                audioPreview.removeClass("border-2 border-blue-500");
              }, 2000); // Remove highlight after 2 seconds
            }
          }
          // Update theme color preview
          if (response.data.settings && response.data.settings.theme_color) {
            themeColorPreview
              .removeClass("hidden")
              .css("background-color", response.data.settings.theme_color);
          }
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
          // Log detailed errors for debugging
          if (response.data.details) {
            // console.log("Detailed errors:", response.data.details);
          }
        }
      },
      error: function (xhr, status, error) {
        // console.log("AJAX Error:", xhr, status, error);
        showToast("AJAX request failed: " + error, "error");
      },
      complete: function () {
        $button.prop("disabled", false).text("Save Settings");
      },
    });
  });
});

// Update audio preview on dropdown change
document
  .getElementById("notification_sound")
  .addEventListener("change", function () {
    var audio = document.getElementById("notification-sound-preview");
    var src = this.value;
    if (src) {
      audio.classList.remove("hidden");
      audio.querySelector("source").src = src;
      audio.load();
    } else {
      audio.classList.add("hidden");
    }
    document.getElementById("robodesk_notification_sound").value = src;
  });

// Remove audio preview and reset hidden input
function removeAudio(previewId, hiddenId) {
  const preview = document.getElementById(previewId);
  const hiddenInput = document.getElementById(hiddenId);
  preview.classList.add("hidden");
  preview.querySelector("source").src = "";
  hiddenInput.value = "";
  document
    .querySelector(
      `button[onclick="removeAudio('${previewId}', '${hiddenId}')"]`
    )
    .classList.add("hidden");
}

// for configuring ticket assignments (fetch interval and default agent) settings> fetch tickets
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
        // console.log("AJAX Error:", xhr, status, error);
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
    formData.append("ticket_description", $("#ticket-description").html());
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
    // const validPriorities = ["Low", "Medium", "High"];
    // if (!validPriorities.includes($("#ticket-priority").val())) {
    //   showToast("Please select a valid priority.", "error");
    //   return;
    // }

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
        // console.log("AJAX Error:", xhr, status, error);
        showToast("Failed to create ticket: " + error, "error");
      },
      complete: function () {
        $button.prop("disabled", false).text("Create Ticket");
      },
    });
  });
});

// Update ticket status function
function updateTicketStatus(ticketId, newStatus) {
  if (!ticketId || !newStatus) return;

  fetch(robodeskAjax.ajax_url, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: new URLSearchParams({
      action: "update_ticket_status",
      security: robodeskAjax.nonces.ticket_status,
      ticket_id: ticketId,
      status: newStatus,
    }),
  })
    .then((res) => res.json())
    .then((response) => {
      try {
        if (!response.success) {
          showToast(
            response.data?.message || "Failed to update status",
            "error"
          );
          return;
        }

        // console.log(response);

        const updatedStatus = response.data.status;
        const updatedTime = response.data.updated;

        if (typeof tickets !== "undefined" && Array.isArray(tickets)) {
          const ticketIndex = tickets.findIndex((t) => t.id === ticketId);
          if (ticketIndex !== -1) {
            tickets[ticketIndex].status = updatedStatus;
            tickets[ticketIndex].updated = updatedTime;
          }
        }

        if (
          typeof currentTicket !== "undefined" &&
          currentTicket.id === ticketId
        ) {
          currentTicket.status = updatedStatus;
          currentTicket.updated = updatedTime;
        }

        showToast(`Ticket status updated to ${updatedStatus}`);
        // updateDashboardStats();
      } catch (error) {
        console.error("Error inside .then:", error);
        showToast("Error updating ticket status", "error");
      }
    })
    .catch((err) => {
      console.error("AJAX catch error:", err);
      showToast("Error updating ticket status", "error");
    });
}

// for ticket priority update
function updateTicketPriority(ticketId, newPriority) {
  if (!ticketId || !newPriority) return;

  fetch(robodeskAjax.ajax_url, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: new URLSearchParams({
      action: "update_ticket_priority",
      security: robodeskAjax.nonces.ticket_priority, // nonce key
      ticket_id: ticketId,
      priority: newPriority,
    }),
  })
    .then((res) => res.json())
    .then((response) => {
      if (response.success) {
        const updatedPriority = response.data.priority;
        const updatedTime = response.data.updated;

        const ticketIndex = tickets.findIndex((t) => t.id === ticketId);
        if (ticketIndex !== -1) {
          tickets[ticketIndex].priority = updatedPriority;
          tickets[ticketIndex].updated = updatedTime;
        }

        if (currentTicket && currentTicket.id === ticketId) {
          currentTicket.priority = updatedPriority;
          currentTicket.updated = updatedTime;
        }

        showToast(`Ticket priority updated to ${updatedPriority}`);
      } else {
        showToast("Failed to update priority", "error");
      }
    })
    .catch((error) => {
      console.error(error);
      showToast("Error updating ticket priority", "error");
    });
}

//// for ticket assignee update
// This function updates the assignee of a ticket via AJAX
// It expects ticketId and newAssignee to be provided
function updateTicketAssignee(ticketId, newAssignee) {
  if (!ticketId || !newAssignee) return;

  fetch(robodeskAjax.ajax_url, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: new URLSearchParams({
      action: "update_ticket_assignee",
      security: robodeskAjax.nonces.ticket_assignment, // nonce key
      ticket_id: ticketId,
      assignee: newAssignee,
    }),
  })
    .then((res) => res.json())
    .then((response) => {
      if (response.success) {
        const updatedAssignee = response.data.assignee;
        const updatedTime = response.data.updated;

        const ticketIndex = tickets.findIndex((t) => t.id === ticketId);
        if (ticketIndex !== -1) {
          tickets[ticketIndex].assignee = updatedAssignee;
          tickets[ticketIndex].updated = updatedTime;
        }

        if (currentTicket && currentTicket.id === ticketId) {
          currentTicket.assignee = updatedAssignee;
          currentTicket.updated = updatedTime;
        }

        showToast(`Ticket assigned to ${updatedAssignee}`);
      } else {
        showToast(
          response.data?.message || "Failed to update assignee",
          "error"
        );
      }
    })
    .catch((error) => {
      console.error(error);
      showToast("Error updating ticket assignee", "error");
    });
}
//
//
//
// This function adds a reply to the current ticket
// Ajax request is made to the server to save the reply
let isReplySending = false;

function addReply() {
  if (isReplySending) return; // Prevent double send
  const userRole = robodeskCurrentUser?.role;
  const replyBox = document.getElementById("new-reply");
  const replyText = replyBox.innerHTML.trim();
  const sendBtn = document.getElementById("send-reply-btn");
  if (!replyText || !currentTicket) return;

  isReplySending = true;
  if (sendBtn) {
    sendBtn.disabled = true;
    sendBtn.textContent = "Sending...";
  }

  const data = {
    action: "add_ticket_reply",
    ticket_id: currentTicket.id,
    reply: replyText,
    nonce: robodeskAjax.nonces.add_ticket_reply,
  };

  fetch(robodeskAjax.ajax_url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(data).toString(),
  })
    .then((res) => res.json())
    .then((response) => {
      if (response.success) {
        const newReply = {
          id: response.data.comment_id,
          author: response.data.author,
          message: replyText,
          timestamp: new Date().toISOString(),
          type: "userRole" in response.data ? response.data.userRole : userRole,
          comment_meta: response.data.comment_meta || { is_action: false },
        };

        if (!Array.isArray(currentTicket.replies)) currentTicket.replies = [];
        currentTicket.replies.push(newReply);
        currentTicket.updated = new Date().toISOString();

        const ticketIndex = tickets.findIndex((t) => t.id === currentTicket.id);
        if (ticketIndex !== -1) tickets[ticketIndex] = currentTicket;

        replyBox.innerHTML = "";
        document.getElementById("replies-count").textContent =
          currentTicket.replies.length;
        loadReplies();
        showToast("Reply added successfully");
      } else {
        showToast(response.data?.message || "Failed to add reply", "error");
      }
    })
    .catch(() => showToast("AJAX error occurred", "error"))
    .finally(() => {
      isReplySending = false;
      if (sendBtn) {
        sendBtn.disabled = false;
        sendBtn.textContent = "Send Reply";
      }
    });
}

// Function to load replies via AJAX
async function loadReplies() {
  if (!currentTicket) return;

  const repliesList = document.getElementById("replies-list");
  if (!repliesList) return;

  try {
    const data = {
      action: "get_ticket_replies",
      ticket_id: currentTicket.id,
      nonce: robodeskAjax.nonces.get_ticket_replies,
    };

    const response = await fetch(robodeskAjax.ajax_url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams(data).toString(),
    });

    if (!response.ok) throw new Error("Network response was not ok");

    const result = await response.json();

    if (result.success) {
      renderReplies(result.data.replies);
      document.getElementById("replies-count").textContent = result.data.count;
    } else {
      throw new Error(result.data?.message || "Failed to load replies");
    }
  } catch (error) {
    console.error("Failed to load replies:", error);
    repliesList.innerHTML = `<div class="text-red-500 p-4">Error: ${error.message}</div>`;
  }
}

// Render replies to the DOM and scroll to the last reply
function renderReplies(replies) {
  const repliesList = document.getElementById("replies-list");
  if (!repliesList || !Array.isArray(replies)) return;
  // user = robodeskCurrentUser?.role || "Support Staff"; // Fallback to guest if user is not defined
  userRole = robodeskCurrentUser?.role || "Support Staff"; // Ensure userRole is defined\

  repliesList.innerHTML = replies
    .map((reply, index) => {
      // Add special style if reply.comment_meta?.is_action is true
      const isAction = reply.comment_meta?.is_action === true; // Use optional chaining for safety
      const actionClass = isAction ? "reply-action bg-blue-200" : "";

      return `
        <div class="p-4 rounded-lg reply-${
          reply.type
        } ${actionClass}" id="reply-${index}">
          <div class="flex justify-between items-center mb-2">
            <span class="font-medium text-sm text-gray-900">${escapeHTML(
              reply.author
            )} (${
        reply.type === "customer" ? "Customer" : `${userRole.toUpperCase()}`
      })</span>
            <span class="text-xs text-gray-500">${formatDate(
              reply.timestamp
            )}</span>
          </div>
          <p class="text-sm text-gray-700">${
            isAction ? reply.message : reply.message
          }</p>
        </div>
      `;
    })
    .join("");

  // Scroll to the last reply
  const lastReply = repliesList.querySelector(`#reply-${replies.length - 1}`);
  if (lastReply) {
    lastReply.scrollIntoView({ behavior: "smooth", block: "end" });
  }
}

function closeTicket() {
  if (!currentTicket || !currentTicket.id) return;
  const ticketId = currentTicket.id;
  // Show a custom modal confirmation instead of native confirm
  showCustomConfirm(
    "Are you sure you want to close this ticket?",
    function onConfirm() {
      // Proceed with closing the ticket
      closeTicketAjax(ticketId);
    }
  );
  return;

  // Helper for custom confirmation modal
  function showCustomConfirm(message, onConfirm) {
    // Create modal elements
    let modal = document.createElement("div");
    modal.className =
      "fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40";
    modal.innerHTML = `
      <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full text-center">
        <div class="mb-4 text-lg font-medium text-gray-800">${message}</div>
        <div class="flex justify-center gap-4">
          <button id="custom-confirm-yes" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Yes</button>
          <button id="custom-confirm-no" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancel</button>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    // Event listeners
    modal.querySelector("#custom-confirm-yes").onclick = function () {
      document.body.removeChild(modal);
      if (typeof onConfirm === "function") onConfirm();
    };
    modal.querySelector("#custom-confirm-no").onclick = function () {
      document.body.removeChild(modal);
    };
  }

  // Move AJAX logic to a separate function for clarity
  function closeTicketAjax(ticketId) {
    jQuery.ajax({
      url: robodeskAjax.ajax_url,
      type: "POST",
      data: {
        action: "close_ticket",
        nonce: robodeskAjax.nonces.close_ticket,
        ticket_id: ticketId,
      },
      success: function (response) {
        if (response.success) {
          showToast(response.data.message);
          location.reload();
        } else {
          showToast(response.data.message || "Error closing ticket", "error");
        }
      },
      error: function () {
        showToast("An error occurred while closing the ticket", "error");
      },
    });
  }
}

// Helper function to escape HTML
function escapeHTML(str) {
  return str
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// Helper function to format dates
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

// This function add a note to the current ticket

function addNote() {
  const noteBox = document.getElementById("new-note");
  const noteText = noteBox.innerHTML.trim();
  if (!noteText || !currentTicket) return;
  userRole = robodeskCurrentUser?.role || "Support Staff";

  const data = {
    action: "add_ticket_note",
    ticket_id: currentTicket.id,
    note: noteText,
    nonce: robodeskAjax.nonces.add_ticket_note,
  };

  fetch(robodeskAjax.ajax_url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(data).toString(),
  })
    .then((res) => res.json())
    .then((response) => {
      if (response.success) {
        const newNote = {
          id: response.data.note_id,
          author: response.data.author,
          message: noteText,
          timestamp: new Date().toISOString(),
          type: "agent",
        };

        if (!Array.isArray(currentTicket.notes)) currentTicket.notes = [];
        currentTicket.notes.push(newNote);
        currentTicket.updated = new Date().toISOString();

        const ticketIndex = tickets.findIndex((t) => t.id === currentTicket.id);
        if (ticketIndex !== -1) tickets[ticketIndex] = currentTicket;

        noteBox.innerHTML = "Type your internal note here...";
        loadNotes(); // This will call renderNotes, which handles scrolling
        showToast("Note added successfully");
      } else {
        showToast(response.data?.message || "Failed to add note", "error");
      }
    })
    .catch(() => showToast("AJAX error occurred", "error"));
}

// Function to load notes via AJAX
async function loadNotes() {
  if (!currentTicket) return;

  const notesList = document.getElementById("notes-list");
  if (!notesList) return;

  // Show loading state
  notesList.innerHTML = '<div class="text-center py-4">Loading notes...</div>';

  try {
    const data = {
      action: "get_ticket_notes",
      ticket_id: currentTicket.id,
      nonce: robodeskAjax.nonces.get_ticket_notes,
    };

    const response = await fetch(robodeskAjax.ajax_url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams(data).toString(),
    });

    if (!response.ok) throw new Error("Network response was not ok");

    const result = await response.json();

    if (result.success) {
      renderNotes(result.data.notes);
      document.getElementById("notes-count").textContent = result.data.count;
    } else {
      throw new Error(result.data?.message || "Failed to load notes");
    }
  } catch (error) {
    console.error("Failed to load notes:", error);
    notesList.innerHTML = `<div class="text-red-500 p-4">Error: ${error.message}</div>`;
  }
}

// Render notes to the DOM and scroll to the last note
function renderNotes(notes) {
  const notesList = document.getElementById("notes-list");
  if (!notesList || !Array.isArray(notes)) return;
  userRole = robodeskCurrentUser?.role || "Support Staff"; // Fallback to guest if user is not defined

  notesList.innerHTML = notes
    .map(
      (note, index) => `
    <div class="p-4 rounded-lg note-internal" id="note-${index}">
      <div class="flex justify-between items-center mb-2">
        <span class="font-medium text-sm text-gray-900">${escapeHTML(
          note.author
        )} (${
        note.type === "customer" ? "Customer" : `${userRole.toUpperCase()}`
      })</span>
    
        <span class="text-xs text-gray-500">${formatDate(note.timestamp)}</span>
      </div>
      <p class="text-sm text-gray-700">${note.message}</p>
    </div>
  `
    )
    .join("");

  // Scroll to the last note
  const lastNote = notesList.querySelector(`#note-${notes.length - 1}`);
  if (lastNote) {
    lastNote.scrollIntoView({ behavior: "smooth", block: "end" });
  }
}
// the message is displaying with html tags
