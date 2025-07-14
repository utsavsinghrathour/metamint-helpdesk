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
let currentDashboardFilter = "recent";

// Initialize the application
document.addEventListener("DOMContentLoaded", function () {
  showPage("dashboard");
  updateDashboardStats();
  // loadDashboardTickets();
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
    case "closed":
      return "status-closed";
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

  currentPage = pageName;

  if (pageName === "ticket-detail") {
    var ticketId = currentTicket ? currentTicket.id : null;
    loadTicketDetail();
  }

  // Load page-specific content
  if (pageName === "tickets") {
    loadTicketsTable();
  } else if (pageName === "settings") {
    showSettingsTab("basic");
  } else if (pageName === "dashboard") {
    // loadDashboardTickets();
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
  // loadDashboardTickets();
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
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
    <div class="text-sm space-y-2">
        <div>
            <strong>Author Image:</strong>
            <img id="author-image" src="${currentTicket.author_image}" alt="Author Image" class="w-8 h-8 rounded-full inline-block ml-2">
        </div>
       
        <div>
            <strong>Origin:</strong> <span id="ticket-source">${currentTicket.source}</span>
        </div>
        <div>
            <strong>Category:</strong> <span id="ticket-category">${currentTicket.category.name}</span>

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
    formData.append("settings[theme_color]", $form.find("#theme_color").val());

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
      url: ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        console.log(response);
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
          // Update theme color preview
          if (response.data.settings && response.data.settings.theme_color) {
            themeColorPreview
              .removeClass("hidden")
              .css("background-color", response.data.settings.theme_color);
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

        console.log(response);

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

        showToast(
          `Ticket status updated to ${updatedStatus.replace("-", " ")}`
        );
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
function addReply() {
  const replyBox = document.getElementById("new-reply");
  const replyText = replyBox.innerHTML.trim();
  if (!replyText || !currentTicket) return;

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
          type: "agent",
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
    .catch(() => showToast("AJAX error occurred", "error"));
}

// This function loads replies for the current ticket
// It populates the replies list with the replies from the current ticket
// Function to load replies via AJAX
async function loadReplies() {
  if (!currentTicket) return;

  const repliesList = document.getElementById("replies-list");
  if (!repliesList) return;

  // Show loading state
  repliesList.innerHTML =
    '<div class="text-center py-4">Loading replies...</div>';

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

// Render replies to the DOM
function renderReplies(replies) {
  const repliesList = document.getElementById("replies-list");
  if (!repliesList || !Array.isArray(replies)) return;

  repliesList.innerHTML = replies
    .map(
      (reply) => `
    <div class="p-4 rounded-lg mb-3 ${
      reply.type === "customer" ? "bg-blue-50" : "bg-gray-50"
    }">
      <div class="flex justify-between items-center mb-2">
        <span class="font-medium text-sm text-gray-900">${escapeHTML(
          reply.author
        )}</span>
        <span class="text-xs text-gray-500">${formatDate(
          reply.timestamp
        )}</span>
      </div>
      <p class="text-sm text-gray-700">${escapeHTML(reply.message)}</p>
    </div>
  `
    )
    .join("");
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
  if (!noteBox || !currentTicket) return;

  const noteText = noteBox.innerHTML.trim();
  if (!noteText) return;

  const newNote = {
    id: Date.now(),
    author: "Support Agent",
    note: noteText,
    timestamp: new Date().toISOString(),
  };

  if (!Array.isArray(currentTicket.notes)) currentTicket.notes = [];
  currentTicket.notes.push(newNote);
  currentTicket.updated = new Date().toISOString();

  const ticketIndex = tickets.findIndex((t) => t.id === currentTicket.id);
  if (ticketIndex !== -1) {
    tickets[ticketIndex] = currentTicket;
  }

  noteBox.innerHTML = ""; // clear the contenteditable div
  document.getElementById("notes-count").textContent =
    currentTicket.notes.length;
  loadNotes();
  showToast("Note added successfully");
}

//loads notes for the current ticket
// It populates the notes list with the notes from the current ticket

function loadNotes() {
  if (!currentTicket || !Array.isArray(currentTicket.notes)) return;

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
