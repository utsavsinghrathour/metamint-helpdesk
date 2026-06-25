function parseRobodeskDate(value) {
  if (!value) return null;
  if (value instanceof Date) return isNaN(value.getTime()) ? null : value;
  if (typeof value === "number") {
    const timestamp = value < 1000000000000 ? value * 1000 : value;
    const date = new Date(timestamp);
    return isNaN(date.getTime()) ? null : date;
  }

  const date = new Date(String(value).trim());
  return isNaN(date.getTime()) ? null : date;
}

function getRobodeskTime(value) {
  const date = parseRobodeskDate(value);
  return date ? date.getTime() : 0;
}

function formatRobodeskLocalTime(value, format = "datetime") {
  const date = parseRobodeskDate(value);
  if (!date) return value || "";

  const options =
    format === "date"
      ? { year: "numeric", month: "short", day: "numeric" }
      : {
          year: "numeric",
          month: "short",
          day: "numeric",
          hour: "2-digit",
          minute: "2-digit",
        };

  return date.toLocaleString(undefined, options);
}

function localizeRobodeskTimes(root = document) {
  root.querySelectorAll(".robodesk-local-time[data-utc]").forEach((element) => {
    const formatted = formatRobodeskLocalTime(
      element.getAttribute("data-utc"),
      element.getAttribute("data-format") || "datetime",
    );

    if (formatted) {
      element.textContent = formatted;
    }
  });
}

document.addEventListener("DOMContentLoaded", () => {
  localizeRobodeskTimes();
});

function loadDashboardTickets() {
  const tableBody = document.getElementById("dashboard-tickets-table");
  let filteredTickets = [];
  const current_user_name =
    robodeskCurrentUser?.name || robodeskCurrentUser?.display_name || "";

  updateDashboardFilterCounts(current_user_name);

  // Filter dashboard_tickets based on currentDashboardFilter
  switch (currentDashboardFilter) {
    case "my_conversation":
      filteredTickets = dashboard_tickets
        .filter(
          (t) => t.assignee === current_user_name && t.status !== "closed",
        )
        .sort(
          (a, b) => getRobodeskTime(b.updated) - getRobodeskTime(a.updated),
        );
      break;
    case "unassigned_conversation":
      filteredTickets = dashboard_tickets
        .filter((t) => t.assignee === "N/A" || !t.assignee)
        .sort(
          (a, b) => getRobodeskTime(b.updated) - getRobodeskTime(a.updated),
        );
      break;
    case "open_conversation":
      filteredTickets = dashboard_tickets
        .filter((t) => t.status === "open" || t.status === "in-progress")
        .sort(
          (a, b) => getRobodeskTime(b.updated) - getRobodeskTime(a.updated),
        );
      break;
    case "chat_conversation":
      filteredTickets = dashboard_tickets
        .filter((t) => t.source === "chat" && t.status !== "closed")
        .sort(
          (a, b) => getRobodeskTime(b.updated) - getRobodeskTime(a.updated),
        );
      break;
    default:
      filteredTickets = dashboard_tickets
        .slice()
        .sort(
          (a, b) => getRobodeskTime(b.updated) - getRobodeskTime(a.updated),
        );
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
    const lastReplyTime = parseRobodeskDate(
      lastReply?.timestamp || lastReply?.date || ticket.updated,
    );
    const isValidDate =
      lastReplyTime instanceof Date &&
      !isNaN(lastReplyTime.getTime()) &&
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
                  decodeHTML(ticket.title).slice(0, 20),
                )}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                ${
                  Array.isArray(ticket.replies) && ticket.replies.length > 0
                    ? (() => {
                        const sortedReplies = [...ticket.replies].sort(
                          (a, b) => {
                            const aDate = getRobodeskTime(
                              a?.timestamp || a?.date || 0,
                            );
                            const bDate = getRobodeskTime(
                              b?.timestamp || b?.date || 0,
                            );
                            return bDate - aDate;
                          },
                        );
                        const lastReply = sortedReplies[0] || {};
                        const lastReplyAuthor = lastReply.author || "N/A";
                        const lastReplyDate =
                          lastReply.timestamp ||
                          lastReply.date ||
                          ticket.updated;
                        const timeAgo = timeSince(
                          parseRobodeskDate(lastReplyDate),
                        );
                        return `
                          <div class="text-sm font-medium text-gray-900">${escapeHTML(
                            lastReplyAuthor,
                          )}</div>
                          <div class="text-sm text-gray-500">${timeAgo}</div>
                        `;
                      })()
                    : `<div class="text-sm text-gray-500">No replies yet</div>`
                }
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${escapeHTML(
                  ticket.customer,
                )}</div>
                <div class="text-sm text-gray-500 truncate max-w-xs">${escapeHTML(
                  ticket.email,
                )}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${escapeHTML(
                  ticket.source,
                )}</div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${getStatusClass(
                  ticket.status,
                )} editable-status inline-flex items-center gap-1 cursor-pointer hover:opacity-90"
                  title="Click to choose"
                  onclick="event.stopPropagation(); editStatus(${
                    ticket.id
                  }, this)">
                  ${escapeHTML(ticket.status.replace("-", " "))}
                  <i class="fas fa-chevron-down text-[10px] opacity-70"></i>
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="status-badge ${getPriorityClass(
                  ticket.priority,
                )} editable-priority inline-flex items-center gap-1 cursor-pointer hover:opacity-90"
                  style="background-color: ${
                    typeof robodeskPriorityColors !== "undefined" &&
                    robodeskPriorityColors[ticket.priority]
                      ? robodeskPriorityColors[ticket.priority]
                      : ""
                  }"
                  title="Click to choose"
                  onclick="event.stopPropagation(); editPriority(${
                    ticket.id
                  }, this)">
                  ${escapeHTML(ticket.priority)}
                  <i class="fas fa-chevron-down text-[10px] opacity-70"></i>
                </span>
              </td>
              ${
                showAssigneeColumn
                  ? `
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm font-medium text-gray-900 editable-assignee inline-flex items-center gap-1 cursor-pointer hover:text-blue-700"
                  title="Click to choose"
                  onclick="event.stopPropagation(); editAssignee(${
                    ticket.id
                  }, this)">
                  ${escapeHTML(ticket.assignee || "Unassigned")}
                  <i class="fas fa-chevron-down text-[10px] opacity-70"></i>
                </span>
              </td>
              `
                  : ""
              }
            </tr>
          `,
          )
          .join("");

  // Add event delegation for row clicks
  tableBody.addEventListener("click", function (event) {
    if (
      event.target.closest(
        ".editable-status, .editable-priority, .editable-assignee, .inline-ticket-editor",
      )
    ) {
      return;
    }
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

function updateDashboardFilterCounts(currentUserName) {
  const myCount = dashboard_tickets.filter(
    (t) => t.assignee === currentUserName && t.status !== "closed",
  ).length;
  const unassignedCount = dashboard_tickets.filter(
    (t) => t.assignee === "N/A" || !t.assignee,
  ).length;
  const openCount = dashboard_tickets.filter(
    (t) => t.status === "open" || t.status === "in-progress",
  ).length;
  const chatCount = dashboard_tickets.filter(
    (t) => t.source === "chat" && t.status !== "closed",
  ).length;

  const countTargets = {
    "dashboard-count-my-conversation": myCount,
    "dashboard-count-unassigned-conversation": unassignedCount,
    "dashboard-count-open-conversation": openCount,
    "dashboard-count-chat-conversation": chatCount,
  };

  Object.entries(countTargets).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) {
      const count = Number(value) || 0;
      if (count > 0) {
        el.textContent = count > 99 ? "99+" : String(count);
        el.classList.add("is-visible");
      } else {
        el.textContent = "";
        el.classList.remove("is-visible");
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
        `<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">${header}</th>`,
    )
    .join("");
}

function getDashboardTicketById(ticketId) {
  if (
    typeof dashboard_tickets !== "undefined" &&
    Array.isArray(dashboard_tickets)
  ) {
    const dashboardTicket = dashboard_tickets.find(
      (t) => Number(t.id) === Number(ticketId),
    );
    if (dashboardTicket) {
      return dashboardTicket;
    }
  }

  if (typeof tickets !== "undefined" && Array.isArray(tickets)) {
    return tickets.find((t) => Number(t.id) === Number(ticketId)) || null;
  }

  return null;
}

function isDashboardTicketsTableElement(element) {
  return Boolean(element && element.closest("#dashboard-tickets-table"));
}

function createInlineSelect(options, selectedValue, className) {
  const select = document.createElement("select");
  select.className = `${className} inline-ticket-editor`;

  // Keep row-level click handlers from opening ticket while user is editing.
  ["click", "mousedown", "mouseup", "pointerdown", "pointerup"].forEach(
    (eventName) => {
      select.addEventListener(eventName, (event) => event.stopPropagation());
    },
  );

  options.forEach((option) => {
    const opt = document.createElement("option");
    opt.value = option.value;
    opt.textContent = option.label;
    if (String(option.value) === String(selectedValue)) {
      opt.selected = true;
    }
    select.appendChild(opt);
  });

  return select;
}

function createInlineDropdown(options, selectedValue, className) {
  const container = document.createElement("div");
  container.className = "relative inline-ticket-editor";

  const trigger = document.createElement("button");
  trigger.type = "button";
  trigger.className = `${className} w-full flex items-center justify-between gap-2`;

  const label = document.createElement("span");
  label.className = "truncate";

  const icon = document.createElement("i");
  icon.className = "fas fa-chevron-down text-[10px] opacity-70 shrink-0";

  const menu = document.createElement("div");
  menu.className =
    "fixed bg-white border border-gray-300 rounded-md shadow-lg z-[9999] max-h-56 overflow-y-auto overflow-x-hidden";
  menu.style.display = "none";
  menu.style.boxSizing = "border-box";

  const getSelectedLabel = (value) => {
    const selected = options.find((opt) => String(opt.value) === String(value));
    return selected ? selected.label : String(value || "");
  };

  let onSelect = () => {};
  let onCancel = () => {};

  const close = () => {
    menu.style.display = "none";
  };

  const updateMenuPosition = () => {
    const rect = container.getBoundingClientRect();
    const viewportWidth =
      window.innerWidth || document.documentElement.clientWidth;
    const viewportHeight =
      window.innerHeight || document.documentElement.clientHeight;
    const menuWidth = Math.max(1, Math.round(rect.width));
    const estimatedHeight = Math.min(options.length * 36, 224);

    let left = rect.left;
    if (left + menuWidth > viewportWidth - 8) {
      left = Math.max(8, viewportWidth - menuWidth - 8);
    }

    const spaceBelow = viewportHeight - rect.bottom;
    const showAbove =
      spaceBelow < Math.min(estimatedHeight, 140) && rect.top > estimatedHeight;
    const top = showAbove
      ? Math.max(8, rect.top - estimatedHeight - 6)
      : rect.bottom + 6;

    menu.style.width = `${menuWidth}px`;
    menu.style.left = `${Math.round(left)}px`;
    menu.style.top = `${Math.round(top)}px`;
  };

  const open = () => {
    updateMenuPosition();
    menu.style.display = "block";
  };

  const outsideHandler = (event) => {
    if (!container.contains(event.target) && !menu.contains(event.target)) {
      onCancel();
    }
  };

  const scrollOrResizeHandler = () => {
    if (menu.style.display !== "none") {
      updateMenuPosition();
    }
  };

  const escapeHandler = (event) => {
    if (event.key === "Escape") {
      event.preventDefault();
      onCancel();
    }
  };

  const setLabel = (value) => {
    label.textContent = getSelectedLabel(value);
  };

  setLabel(selectedValue);

  options.forEach((option) => {
    const item = document.createElement("button");
    item.type = "button";
    item.className =
      "w-full text-left px-3 py-2 text-sm hover:bg-blue-50 border-b last:border-b-0";
    if (String(option.value) === String(selectedValue)) {
      item.classList.add("bg-blue-50");
    }
    item.textContent = option.label;
    item.addEventListener("click", (event) => {
      event.preventDefault();
      event.stopPropagation();
      onSelect(option.value);
    });
    menu.appendChild(item);
  });

  trigger.appendChild(label);
  trigger.appendChild(icon);
  container.appendChild(trigger);
  document.body.appendChild(menu);

  trigger.addEventListener("click", (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (menu.style.display === "none") {
      open();
    } else {
      close();
    }
  });

  container.addEventListener("click", (event) => {
    event.stopPropagation();
  });

  document.addEventListener("mousedown", outsideHandler);
  document.addEventListener("keydown", escapeHandler);
  window.addEventListener("scroll", scrollOrResizeHandler, true);
  window.addEventListener("resize", scrollOrResizeHandler);

  return {
    container,
    open,
    close,
    setLabel,
    setDisabled: (disabled) => {
      trigger.disabled = disabled;
      Array.from(menu.querySelectorAll("button")).forEach((btn) => {
        btn.disabled = disabled;
      });
    },
    onSelect: (callback) => {
      onSelect = callback;
    },
    onCancel: (callback) => {
      onCancel = callback;
    },
    destroy: () => {
      document.removeEventListener("mousedown", outsideHandler);
      document.removeEventListener("keydown", escapeHandler);
      window.removeEventListener("scroll", scrollOrResizeHandler, true);
      window.removeEventListener("resize", scrollOrResizeHandler);
      if (menu.parentNode) {
        menu.parentNode.removeChild(menu);
      }
    },
    focus: () => {
      trigger.focus();
      open();
    },
  };
}

function getDashboardPriorityOptions() {
  const prioritySelect = document.getElementById("ticket-priority-select");
  if (prioritySelect && prioritySelect.options.length > 0) {
    return Array.from(prioritySelect.options).map((option) => ({
      value: option.value,
      label: option.textContent.trim(),
    }));
  }

  if (typeof robodeskPriorityColors !== "undefined") {
    return Object.keys(robodeskPriorityColors).map((priority) => ({
      value: priority,
      label: priority,
    }));
  }

  return [];
}

function getDashboardAssigneeOptions() {
  const assigneeSelect = document.getElementById("ticket-assignee-select");
  if (!assigneeSelect || assigneeSelect.options.length === 0) {
    return [];
  }

  return Array.from(assigneeSelect.options).map((option) => ({
    value: option.value,
    label: option.textContent.trim(),
  }));
}

function editStatus(ticketId, element) {
  const ticket = getDashboardTicketById(ticketId);
  const currentStatus = ticket?.status || element.textContent.trim();
  const statusOptions = [
    { value: "open", label: "Open" },
    { value: "in-progress", label: "In Progress" },
    { value: "closed", label: "Closed" },
  ];

  const dropdown = createInlineDropdown(
    statusOptions,
    currentStatus,
    "border border-gray-300 bg-white rounded-md text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500",
  );

  const restore = () => {
    dropdown.destroy();
    if (dropdown.container.parentNode) {
      dropdown.container.replaceWith(element);
    }
  };

  element.replaceWith(dropdown.container);
  dropdown.focus();

  let submitted = false;
  dropdown.onCancel(() => {
    if (!submitted) {
      restore();
    }
  });

  dropdown.onSelect((newStatus) => {
    if (!newStatus || newStatus === currentStatus) {
      restore();
      return;
    }

    submitted = true;
    dropdown.setDisabled(true);
    updateTicketStatus(ticketId, newStatus).then((result) => {
      if (result && result.success) {
        if (isDashboardTicketsTableElement(element)) {
          dropdown.destroy();
          loadDashboardTickets();
          return;
        }

        const updatedStatus = result.data?.status || newStatus;
        const updatedSpan = document.createElement("span");
        updatedSpan.className = `status-badge ${getStatusClass(updatedStatus)} editable-status inline-flex items-center gap-1 cursor-pointer hover:opacity-90`;
        updatedSpan.title = "Click to choose";
        updatedSpan.innerHTML = `${escapeHTML(updatedStatus.replace("-", " "))}<i class="fas fa-chevron-down text-[10px] opacity-70"></i>`;
        updatedSpan.onclick = (event) => {
          event.stopPropagation();
          editStatus(ticketId, updatedSpan);
        };

        if (dropdown.container.parentNode) {
          dropdown.destroy();
          dropdown.container.replaceWith(updatedSpan);
        }
      } else {
        restore();
      }
    });
  });
}

function editPriority(ticketId, element) {
  const ticket = getDashboardTicketById(ticketId);
  const currentPriority = ticket?.priority || element.textContent.trim();
  const priorityOptions = getDashboardPriorityOptions();

  if (priorityOptions.length === 0) {
    showToast("No priority options found", "error");
    return;
  }

  const dropdown = createInlineDropdown(
    priorityOptions,
    currentPriority,
    "border border-gray-300 bg-white rounded-md text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500",
  );

  const restore = () => {
    dropdown.destroy();
    if (dropdown.container.parentNode) {
      dropdown.container.replaceWith(element);
    }
  };

  element.replaceWith(dropdown.container);
  dropdown.focus();

  let submitted = false;
  dropdown.onCancel(() => {
    if (!submitted) {
      restore();
    }
  });

  dropdown.onSelect((newPriority) => {
    if (!newPriority || newPriority === currentPriority) {
      restore();
      return;
    }

    submitted = true;
    dropdown.setDisabled(true);
    updateTicketPriority(ticketId, newPriority).then((result) => {
      if (result && result.success) {
        if (isDashboardTicketsTableElement(element)) {
          dropdown.destroy();
          loadDashboardTickets();
          return;
        }

        const updatedPriority = result.data?.priority || newPriority;
        const updatedSpan = document.createElement("span");
        updatedSpan.className = `status-badge ${getPriorityClass(updatedPriority)} editable-priority inline-flex items-center gap-1 cursor-pointer hover:opacity-90`;
        updatedSpan.title = "Click to choose";
        if (
          typeof robodeskPriorityColors !== "undefined" &&
          robodeskPriorityColors[updatedPriority]
        ) {
          updatedSpan.style.backgroundColor =
            robodeskPriorityColors[updatedPriority];
        }
        updatedSpan.innerHTML = `${escapeHTML(updatedPriority)}<i class="fas fa-chevron-down text-[10px] opacity-70"></i>`;
        updatedSpan.onclick = (event) => {
          event.stopPropagation();
          editPriority(ticketId, updatedSpan);
        };

        if (dropdown.container.parentNode) {
          dropdown.destroy();
          dropdown.container.replaceWith(updatedSpan);
        }
      } else {
        restore();
      }
    });
  });
}

function editAssignee(ticketId, element) {
  const ticket = getDashboardTicketById(ticketId);
  const assigneeOptions = getDashboardAssigneeOptions();

  if (assigneeOptions.length === 0) {
    showToast("No assignee options found", "error");
    return;
  }

  const currentAssigneeValue =
    ticket?.assignee_id ||
    assigneeOptions.find((option) => option.label === ticket?.assignee)
      ?.value ||
    assigneeOptions.find(
      (option) => option.label === element.textContent.trim(),
    )?.value;

  const dropdown = createInlineDropdown(
    assigneeOptions,
    currentAssigneeValue,
    "border border-gray-300 bg-white rounded-md text-sm px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500",
  );

  const restore = () => {
    dropdown.destroy();
    if (dropdown.container.parentNode) {
      dropdown.container.replaceWith(element);
    }
  };

  element.replaceWith(dropdown.container);
  dropdown.focus();

  let submitted = false;
  dropdown.onCancel(() => {
    if (!submitted) {
      restore();
    }
  });

  dropdown.onSelect((newAssigneeId) => {
    if (
      !newAssigneeId ||
      String(newAssigneeId) === String(currentAssigneeValue)
    ) {
      restore();
      return;
    }

    submitted = true;
    dropdown.setDisabled(true);
    updateTicketAssignee(ticketId, newAssigneeId).then((result) => {
      if (result && result.success) {
        if (isDashboardTicketsTableElement(element)) {
          dropdown.destroy();
          loadDashboardTickets();
          return;
        }

        const updatedAssignee =
          result.data?.assignee ||
          assigneeOptions.find(
            (option) => String(option.value) === String(newAssigneeId),
          )?.label ||
          element.textContent.trim();
        const updatedSpan = document.createElement("span");
        updatedSpan.className =
          "text-sm font-medium text-gray-900 editable-assignee inline-flex items-center gap-1 cursor-pointer hover:text-blue-700";
        updatedSpan.title = "Click to choose";
        updatedSpan.innerHTML = `${escapeHTML(updatedAssignee)}<i class="fas fa-chevron-down text-[10px] opacity-70"></i>`;
        updatedSpan.onclick = (event) => {
          event.stopPropagation();
          editAssignee(ticketId, updatedSpan);
        };

        if (dropdown.container.parentNode) {
          dropdown.destroy();
          dropdown.container.replaceWith(updatedSpan);
        }
      } else {
        restore();
      }
    });
  });
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

// Helper function to decode HTML entities
function decodeHTML(html) {
  if (!html) return "";
  const txt = document.createElement("textarea");
  txt.innerHTML = html;
  return txt.value;
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

// Helper function to format date (delegates to UTC-aware formatter)
function formatDate(dateString) {
  return formatRobodeskLocalTime(dateString);
}

// Function to handle filter changes
function setDashboardFilter(filter) {
  currentDashboardFilter = filter;
  loadDashboardTickets();
  conssole.log("clicked", filter);
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

// Toggle Email Templates variables list without clashing with addon scripts.
function robodeskToggleEmailVariables() {
  const content = document.getElementById("robodesk-email-variables-content");
  const btn = document.getElementById("robodesk-email-variables-toggle");

  if (!content || !btn) {
    return;
  }

  const isHidden = content.classList.contains("hidden");
  if (isHidden) {
    content.classList.remove("hidden");
    btn.textContent = "Hide";
  } else {
    content.classList.add("hidden");
    btn.textContent = "Show";
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.getElementById("robodesk-email-variables-toggle");
  const content = document.getElementById("robodesk-email-variables-content");

  if (!toggleBtn || !content) {
    return;
  }

  toggleBtn.addEventListener("click", function (event) {
    event.preventDefault();
    robodeskToggleEmailVariables();
  });
});
//search form creating user and email

document.addEventListener("DOMContentLoaded", function () {
  const nameInput = document.getElementById("customer-name");
  const emailInput = document.getElementById("customer-email");
  const nameSuggestions = document.getElementById("customer-name-suggestions");
  const emailSuggestions = document.getElementById(
    "customer-email-suggestions",
  );

  function fetchSuggestions(type, value, callback) {
    const url =
      typeof robodeskAjax !== "undefined" ? robodeskAjax.ajaxurl : ajaxurl;
    const nonce =
      typeof robodeskAjax !== "undefined" && robodeskAjax.nonces
        ? robodeskAjax.nonces.user_lookup
        : "";
    fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        action: "robodesk_user_lookup",
        type: type,
        value: value,
        nonce: nonce,
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
  // Check if there's a tab parameter in the URL
  const params = new URLSearchParams(window.location.search);
  const ticketId = params.get("ticket");

  // If there's a ticket parameter, show the ticket detail directly
  if (ticketId) {
    currentPage = "ticket-detail";
    showPage("ticket-detail");
    return; // Exit early, viewTicket will be called by another listener
  }

  const tab = params.get("tab") || "dashboard";
  showPage(tab);

  // Note: loadDashboardTickets() will be called by showPage() if tab is 'dashboard'
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

function timeSinceCompact(date) {
  const now = new Date();
  const secondsPast = Math.max(
    0,
    Math.floor((now.getTime() - date.getTime()) / 1000),
  );

  if (secondsPast < 60) {
    return `${String(secondsPast).padStart(2, "0")} sec`;
  }

  if (secondsPast < 3600) {
    return `${Math.floor(secondsPast / 60)} min`;
  }

  if (secondsPast < 86400) {
    return `${Math.floor(secondsPast / 3600)} hr`;
  }

  return `${Math.floor(secondsPast / 86400)} day`;
}

function resetEditorContent(editorId) {
  const editor = document.getElementById(editorId);
  if (!editor) return;

  editor.innerHTML = "";
  editor.classList.add("placeholder");
}

function resetNewConversationForm() {
  const form = document.getElementById("robodesk-new-ticket-form");
  if (form) {
    form.reset();
  }

  resetEditorContent("ticket-description");

  const createButton = document.getElementById("robodesk-create-ticket");
  if (createButton) {
    createButton.disabled = false;
    createButton.textContent = "Create Conversation";
  }

  const nameSuggestions = document.getElementById("customer-name-suggestions");
  const emailSuggestions = document.getElementById(
    "customer-email-suggestions",
  );
  if (nameSuggestions) {
    nameSuggestions.classList.add("hidden");
    nameSuggestions.innerHTML = "";
  }
  if (emailSuggestions) {
    emailSuggestions.classList.add("hidden");
    emailSuggestions.innerHTML = "";
  }
}

function resetTicketComposeEditors() {
  resetEditorContent("new-reply");
  resetEditorContent("new-note");

  const sendReplyButton = document.getElementById("send-reply-btn");
  if (sendReplyButton) {
    sendReplyButton.disabled = false;
    sendReplyButton.textContent = "Send Reply";
  }
}

function initConversationMultiFilter(config) {
  const wrapper = document.getElementById(config.wrapperId);
  const input = document.getElementById(config.inputId);
  const tags = document.getElementById(config.tagsId);
  const suggestions = document.getElementById(config.suggestionsId);
  const hiddenInputs = document.getElementById(config.hiddenInputsId);

  if (!wrapper || !input || !tags || !suggestions || !hiddenInputs) {
    return;
  }

  const options = Array.isArray(config.options) ? config.options : [];
  const initialValues = Array.isArray(config.selected) ? config.selected : [];
  let selectedValues = [];

  const normalizeValue = (value) => String(value || "").trim();
  const normalizeKey = (value) => normalizeValue(value).toLowerCase();

  const hasValue = (value) => {
    const key = normalizeKey(value);
    return selectedValues.some((entry) => normalizeKey(entry) === key);
  };

  const render = () => {
    const chipNodes = tags.querySelectorAll("[data-rbd-chip='1']");
    chipNodes.forEach((node) => node.remove());
    hiddenInputs.innerHTML = "";

    selectedValues.forEach((value) => {
      const chip = document.createElement("span");
      chip.setAttribute("data-rbd-chip", "1");
      chip.className =
        "inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800";
      chip.textContent = value;

      const removeButton = document.createElement("button");
      removeButton.type = "button";
      removeButton.className = "ml-2 text-blue-700 hover:text-blue-900";
      removeButton.innerHTML = "&times;";
      removeButton.setAttribute("aria-label", `Remove ${value}`);
      removeButton.addEventListener("click", () => {
        selectedValues = selectedValues.filter(
          (entry) => normalizeKey(entry) !== normalizeKey(value),
        );
        render();
      });

      chip.appendChild(removeButton);
      tags.appendChild(chip);

      const hidden = document.createElement("input");
      hidden.type = "hidden";
      hidden.name = `${config.fieldName}[]`;
      hidden.value = value;
      hiddenInputs.appendChild(hidden);

      tags.insertBefore(chip, input);
    });
  };

  const addValue = (rawValue) => {
    const value = normalizeValue(rawValue);
    if (!value || hasValue(value)) {
      return;
    }

    selectedValues.push(value);
    input.value = "";
    suggestions.classList.add("hidden");
    render();
  };

  const showSuggestions = () => {
    const query = normalizeKey(input.value);
    if (!query) {
      suggestions.classList.add("hidden");
      suggestions.innerHTML = "";
      return;
    }

    const matches = options
      .filter((option) => {
        const value = normalizeValue(option?.value);
        const email = normalizeValue(option?.email);
        const optionKey = normalizeKey(value);
        return (
          !hasValue(value) &&
          (optionKey.includes(query) || normalizeKey(email).includes(query))
        );
      })
      .slice(0, 8);

    if (matches.length === 0) {
      suggestions.classList.add("hidden");
      suggestions.innerHTML = "";
      return;
    }

    suggestions.innerHTML = "";
    matches.forEach((match) => {
      const row = document.createElement("button");
      row.type = "button";
      row.className =
        "w-full text-left px-3 py-2 text-sm hover:bg-blue-50 border-b last:border-b-0";
      row.innerHTML = `<div class="font-medium text-gray-900">${escapeHTML(match.value || "")}</div><div class="text-xs text-gray-500">${escapeHTML(match.email || "")}</div>`;
      row.addEventListener("click", () => addValue(match.value || ""));
      suggestions.appendChild(row);
    });

    suggestions.classList.remove("hidden");
  };

  input.addEventListener("input", showSuggestions);
  input.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === ",") {
      event.preventDefault();
      addValue(input.value);
      return;
    }

    if (event.key === "Backspace" && normalizeValue(input.value) === "") {
      selectedValues.pop();
      render();
    }
  });

  wrapper.addEventListener("click", () => input.focus());

  document.addEventListener("click", (event) => {
    if (!wrapper.contains(event.target)) {
      suggestions.classList.add("hidden");
    }
  });

  initialValues.forEach((value) => addValue(value));
  render();

  return {
    commitInputValue: () => addValue(input.value),
  };
}

function initConversationMultiFilters() {
  const hasCustomerUI = document.getElementById(
    "robodesk-customer-filter-wrapper",
  );
  const hasAgentUI = document.getElementById("robodesk-agent-filter-wrapper");
  if (!hasCustomerUI || !hasAgentUI) {
    return;
  }

  const customerFilter = initConversationMultiFilter({
    wrapperId: "robodesk-customer-filter-wrapper",
    inputId: "robodesk-customer-filter-input",
    tagsId: "robodesk-customer-tags",
    suggestionsId: "robodesk-customer-suggestions",
    hiddenInputsId: "robodesk-customer-hidden-inputs",
    fieldName: "customer",
    options:
      typeof robodeskConversationCustomerOptions !== "undefined"
        ? robodeskConversationCustomerOptions
        : [],
    selected:
      typeof robodeskSelectedCustomers !== "undefined"
        ? robodeskSelectedCustomers
        : [],
  });

  const agentFilter = initConversationMultiFilter({
    wrapperId: "robodesk-agent-filter-wrapper",
    inputId: "robodesk-agent-filter-input",
    tagsId: "robodesk-agent-tags",
    suggestionsId: "robodesk-agent-suggestions",
    hiddenInputsId: "robodesk-agent-hidden-inputs",
    fieldName: "agent",
    options:
      typeof robodeskConversationAgentOptions !== "undefined"
        ? robodeskConversationAgentOptions
        : [],
    selected:
      typeof robodeskSelectedAgents !== "undefined"
        ? robodeskSelectedAgents
        : [],
  });

  const form = hasCustomerUI.closest("form");
  if (form && form.dataset.robodeskConversationFiltersBound !== "1") {
    form.dataset.robodeskConversationFiltersBound = "1";
    form.addEventListener("submit", () => {
      customerFilter?.commitInputValue();
      agentFilter?.commitInputValue();
    });
  }
}

document.addEventListener("DOMContentLoaded", initConversationMultiFilters);

// Navigation functions
function showPage(pageName, fromClick = false) {
  // Update the currentPage variable
  currentPage = pageName;

  // Update URL with the current tab (except for ticket-detail which has its own logic)
  if (pageName !== "ticket-detail") {
    const url = new URL(window.location);
    url.searchParams.set("tab", pageName);
    // Prevent reload/back from reopening the previous ticket detail.
    url.searchParams.delete("ticket");
    // Remove paged parameter when changing tabs to start from page 1
    url.searchParams.delete("paged");
    window.history.pushState({}, "", url.toString());
  }

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
  } else if (pageName === "new-ticket") {
    resetNewConversationForm();
  } else if (pageName === "tickets") {
    // Conversations page is server-rendered. Reload when marked stale so new tickets appear.
    if (shouldRefreshTicketsPage) {
      reloadConversationsPageIfVisible();
      return;
    }
  } else if (pageName === "ticket-detail") {
    resetTicketComposeEditors();
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

  // Try to find in dashboard_tickets first (for dashboard tab)
  currentTicket = dashboard_tickets.find((t) => t.id === ticketId);
  window.currentTicket = currentTicket; // Make available globally for templates

  // Always navigate to the ticket detail page immediately to avoid showing
  // the conversation tab while the AJAX call is in flight.
  navigateToTicket(ticketId);

  // If local cache is stale, fetch the latest detail from server.
  if (!currentTicket) {
    fetch(robodeskAjax.ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: new URLSearchParams({
        action: "get_ticket_detail",
        ticket_id: ticketId,
        nonce: robodeskAjax.nonces.get_ticket_detail,
      }),
    })
      .then((res) => res.json())
      .then((response) => {
        if (response.success) {
          currentTicket = response.data;
          window.currentTicket = currentTicket; // Make available globally for templates
          loadTicketDetail();
        } else {
          console.error("Failed to fetch ticket details", response.data);
        }
      })
      .catch((error) => {
        console.error("Error fetching ticket:", error);
      });
  }
}

function navigateToTicket(ticketId) {
  // Update URL with ticket ID (without reloading)
  if (history.pushState) {
    const url = new URL(window.location);
    url.searchParams.set("ticket", ticketId);
    history.pushState({ ticketId }, "", url);
  }

  showPage("ticket-detail");
  showTicketTab("replies");
}

// On page load, check for ticket param and show ticket if present
document.addEventListener("DOMContentLoaded", function () {
  const params = new URLSearchParams(window.location.search);
  const ticketId = params.get("ticket");
  const tabParam = params.get("tab");

  // Priority 1: If there's a ticket parameter, show ticket detail
  if (ticketId) {
    viewTicket(Number(ticketId));
  }
  // Priority 2: If there's a tab parameter, show that tab
  else if (tabParam) {
    showPage(tabParam);
  }
  // Priority 3: Default to dashboard
  else {
    showPage("dashboard");
  }
});
// Update URL when navigating between main pages
document.querySelectorAll(".nav-link").forEach((link) => {
  link.addEventListener("click", () => {
    const url = new URL(window.location);
    url.searchParams.delete("ticket");
    url.searchParams.delete("paged");
    url.searchParams.set("view", link.dataset.page);
    history.pushState({}, "", url);
  });
});

// Function to create category dropdown options
function createCategoryOptions() {
  const currentCategoryId =
    currentTicket?.category?.id || currentTicket?.category?.term_id || "";
  const currentCategoryName = currentTicket?.category?.name || "";
  const optionSet = new Map();

  if (currentCategoryId) {
    optionSet.set(String(currentCategoryId), {
      value: String(currentCategoryId),
      label: currentCategoryName,
      selected: true,
    });
  }

  if (robodeskDefaultCategoryObj) {
    optionSet.set(String(robodeskDefaultCategoryObj.term_id), {
      value: String(robodeskDefaultCategoryObj.term_id),
      label: robodeskDefaultCategoryObj.name,
      selected:
        String(currentCategoryId) ===
        String(robodeskDefaultCategoryObj.term_id),
    });
  }

  robodeskTicketCategories.forEach((category) => {
    optionSet.set(String(category.term_id), {
      value: String(category.term_id),
      label: category.name,
      selected: String(category.term_id) === String(currentCategoryId),
    });
  });

  return Array.from(optionSet.values())
    .map(
      (option) =>
        `<option value="${option.value}"${option.selected ? " selected" : ""}>${option.label}</option>`,
    )
    .join("");
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

  const isEmptyOrNA = (value) => {
    if (value === null || value === undefined) return true;
    const s = String(value).trim();
    if (s === "") return true;
    const lower = s.toLowerCase();
    return lower === "n/a" || lower === "na";
  };

  document.getElementById("ticket-detail-title").textContent =
    `Conversation #${currentTicket.id} - ${decodeHTML(currentTicket.title)}`;

  // Load customer information
  const customerInfo = document.getElementById("customer-info");
  if (customerInfo) {
    customerInfo.innerHTML = `
      <div class="flex items-center gap-4 mb-6">
        <div class="author-image-wrapper">
          <span class="author-image w-12 h-12 rounded-full bg-gray-100 border border-gray-200 overflow-hidden flex items-center justify-center">
            <img id="author-image" src="${currentTicket.author_image || ""}" alt="Author Image" class="w-10 h-10 rounded-full border border-gray-300 shadow-sm">
          </span>
        </div>
        <div class="w-full min-w-0">
          <span class="lg:w-full w-100 flex justify-between items-start gap-3">
            <div class="min-w-0">
              <h3 class="font-bold text-gray-900 leading-tight truncate">${escapeHTML(currentTicket.customer || "")} <a href="${robodeskAjax.ajaxurl.replace('admin-ajax.php', '')}user-edit.php?user_id=${currentTicket.author_id || 0}" target="_blank" class="robodesk-popover-trigger text-gray-500 hover:text-green-700 ml-1" data-popover-text="Edit customer profile" aria-label="Edit customer profile"><i class="fas fa-user-edit" style="font-size: 12px;"></i></a></h3>
              <p class="text-sm text-gray-500 truncate">${escapeHTML(currentTicket.email || "")}</p>
            </div>
            <span class="icon-tickets shrink-0 mt-1 flex items-center gap-1">
              <a href="#" onclick="showCustomerTickets(); return false;" class="robodesk-popover-trigger text-gray-500 hover:text-blue-700" data-popover-text="View all conversations" aria-label="View all conversations">
                <i class="fas fa-ticket"></i>
              </a>
            </span>
          </span>
        </div>
      </div>
    `;
    bindPopoverTriggers(customerInfo);
  }

  // Load ticket meta information
  const ticketMetaInfo = document.getElementById("ticket-meta-info");

  if (ticketMetaInfo) {
    // Hide ticket meta info for email-originated tickets (IP, browser, location are not relevant)
    const ticketSource = String(currentTicket.source || "").toLowerCase();
    if (ticketSource === "email") {
      ticketMetaInfo.style.display = "none";
      ticketMetaInfo.innerHTML = "";
    } else {
      ticketMetaInfo.style.display = "";

      const sourceIpRow = isEmptyOrNA(currentTicket.source_ip)
        ? ""
        : `
          <div class="flex justify-between items-center">
            <span class="text-gray-500">IP Address:</span>
            <span class="text-gray-900 font-mono">${escapeHTML(currentTicket.source_ip)}</span>
          </div>
        `;

      const locationValue = currentTicket.user_location || currentTicket.source_country;
      const countryRow = isEmptyOrNA(locationValue)
        ? ""
        : `
          <div class="flex justify-between items-center">
            <span class="text-gray-500">Location:</span>
            <span class="text-gray-900">${escapeHTML(locationValue)}</span>
          </div>
        `;

      const browserRow = isEmptyOrNA(currentTicket.source_browser)
        ? ""
        : `
          <div class="flex justify-between items-center">
            <span class="text-gray-500">Browser:</span>
            <span class="text-gray-900">${escapeHTML(currentTicket.source_browser)}</span>
          </div>
        `;

      ticketMetaInfo.innerHTML = `
        <div class="space-y-4 pt-6 border-t border-gray-100 text-sm">
          <div class="flex justify-between items-center">
            <span class="text-gray-500">Origin:</span>
            <span id="ticket-source" class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-xs font-bold uppercase">${escapeHTML(
              String(currentTicket.source || "").toUpperCase(),
            )}</span>
          </div>
          ${sourceIpRow}
          ${countryRow}
          ${browserRow}
        </div>
      `;
    }
  }

  const categorySelect = document.getElementById("ticket-category-select");
  if (categorySelect) {
    categorySelect.innerHTML = createCategoryOptions();
    const selectedCategoryId =
      currentTicket.category?.id || currentTicket.category?.term_id || "";
    if (selectedCategoryId) {
      categorySelect.value = String(selectedCategoryId);
    }

    categorySelect.onchange = function (e) {
      const newCategoryId = e.target.value;
      if (!newCategoryId) return;
      updateTicketCategory(currentTicket.id, newCategoryId);

      // Update the current ticket object
      const selectedCategory = robodeskTicketCategories.find(
        (cat) => cat.term_id == newCategoryId,
      );
      if (selectedCategory) {
        currentTicket.category = selectedCategory;
      }
    };
  }

  // Set current values in other dropdowns
  const statusSelect = document.getElementById("ticket-status-select");
  if (statusSelect) {
    statusSelect.value = currentTicket.status;
  }

  const prioritySelect = document.getElementById("ticket-priority-select");
  if (prioritySelect) {
    prioritySelect.value = currentTicket.priority;
  }

  const assigneeSelect = document.getElementById("ticket-assignee-select");
  if (assigneeSelect) {
    assigneeSelect.value = currentTicket.assignee_id || currentTicket.assignee;
    if (!assigneeSelect.value && currentTicket.assignee) {
      const matchingOption = Array.from(assigneeSelect.options).find(
        (option) => option.textContent.trim() === currentTicket.assignee,
      );
      if (matchingOption) {
        assigneeSelect.value = matchingOption.value;
      }
    }
  }

  // Update reply and note counts
  document.getElementById("replies-count").textContent =
    currentTicket.replies?.length || 0;
  document.getElementById("notes-count").textContent =
    currentTicket.notes?.length || 0;

  loadReplies();
  loadNotes();
}

function bindPopoverTriggers(scope) {
  if (!scope || !scope.querySelectorAll) return;

  const triggers = scope.querySelectorAll(".robodesk-popover-trigger");
  triggers.forEach((trigger) => {
    if (trigger.dataset.popoverBound === "1") return;
    trigger.dataset.popoverBound = "1";

    let activePopover = null;

    trigger.addEventListener("mouseenter", function () {
      const text = this.getAttribute("data-popover-text");
      if (!text) return;

      if (activePopover) {
        activePopover.remove();
      }

      const popover = document.createElement("div");
      popover.className = "rb-popover";
      popover.innerHTML = `<div class="arrow"></div><div class="popover-body">${text}</div>`;
      document.body.appendChild(popover);
      activePopover = popover;

      const rect = this.getBoundingClientRect();
      const top = rect.top + window.scrollY - popover.offsetHeight - 8;
      const left =
        rect.left + window.scrollX + rect.width / 2 - popover.offsetWidth / 2;

      popover.style.top = `${top}px`;
      popover.style.left = `${left}px`;

      requestAnimationFrame(() => {
        popover.classList.add("show");
      });
    });

    trigger.addEventListener("mouseleave", function () {
      if (!activePopover) return;

      activePopover.classList.remove("show");
      const popoverToRemove = activePopover;
      activePopover = null;

      setTimeout(() => {
        if (popoverToRemove.parentNode) {
          popoverToRemove.parentNode.removeChild(popoverToRemove);
        }
      }, 150);
    });
  });
}

// Customer tickets functions
function showCustomerTickets() {
  if (!currentTicket) return;

  // Use dashboard_tickets instead of undefined 'tickets' variable
  const customerTickets = dashboard_tickets.filter(
    (t) => t.customer === currentTicket.customer,
  );
  const stats = {
    total: customerTickets.length,
    open: customerTickets.filter((t) => t.status === "open").length,
    inProgress: customerTickets.filter((t) => t.status === "in-progress")
      .length,
    closed: customerTickets.filter((t) => t.status === "closed").length,
  };

  // Update modal title
  document.getElementById("customer-tickets-title").textContent =
    `${currentTicket.customer}'s Tickets`;

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
  const customerTickets = dashboard_tickets.filter(
    (t) => t.customer === currentTicket.customer,
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
                      decodeHTML(ticket.title).slice(0, 50) +
                      (decodeHTML(ticket.title).length > 50 ? "..." : "")
                    }</div>
                    <div class="text-xs text-gray-500">${formatDate(
                      ticket.created,
                    )}</div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="status-badge ${getStatusClass(
                      ticket.status,
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
    `,
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

  // Change button text to "Fetching emails..."
  const fetchButton = document.querySelector('[onclick="fetchEmails()"]');
  if (fetchButton) {
    fetchButton.textContent = "Fetching emails...";
    fetchButton.disabled = true;
  }

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
    })
    .finally(() => {
      // Reset button text and enable it
      if (fetchButton) {
        fetchButton.textContent = "Fetch Emails Now";
        fetchButton.disabled = false;
      }
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

// SMTP Save Settings AJAX handler
jQuery(document).ready(function ($) {
  $(document).on("click", "#save-smtp-settings", function (e) {
    e.preventDefault();

    var $form = $("#robodesk-smtp-form");
    var $saveButton = $(this);
    $saveButton.prop("disabled", true).text("Saving...");

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "robodesk_smtp_save_settings",
        nonce: $form.find('input[name="robodesk_smtp_settings_nonce"]').val(),
        robodesk_smtp_enabled: $form
          .find("#robodesk_smtp_enabled")
          .is(":checked")
          ? "1"
          : "0",
        robodesk_smtp_host: $form.find("#robodesk_smtp_host").val(),
        robodesk_smtp_port: $form.find("#robodesk_smtp_port").val(),
        robodesk_smtp_username: $form.find("#robodesk_smtp_username").val(),
        robodesk_smtp_password: $form.find("#robodesk_smtp_password").val(),
        robodesk_smtp_encryption: $form.find("#robodesk_smtp_encryption").val(),
        robodesk_smtp_from_email: $form.find("#robodesk_smtp_from_email").val(),
        robodesk_smtp_from_name: $form.find("#robodesk_smtp_from_name").val(),
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
        $saveButton.prop("disabled", false).text("Save SMTP Settings");
      },
    });
  });
});

// SMTP Test Connection handler
jQuery(document).ready(function ($) {
  $(document).on("click", "#robodesk-smtp-test", function () {
    var $form = $("#robodesk-smtp-form");
    var $testButton = $(this);

    $testButton.prop("disabled", true).text("Testing...");

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "robodesk_smtp_test_connection",
        nonce: $form.find('input[name="robodesk_smtp_settings_nonce"]').val(),
        robodesk_smtp_enabled: $form
          .find("#robodesk_smtp_enabled")
          .is(":checked")
          ? "1"
          : "0",
        robodesk_smtp_host: $form.find("#robodesk_smtp_host").val(),
        robodesk_smtp_port: $form.find("#robodesk_smtp_port").val(),
        robodesk_smtp_username: $form.find("#robodesk_smtp_username").val(),
        robodesk_smtp_password: $form.find("#robodesk_smtp_password").val(),
        robodesk_smtp_encryption: $form.find("#robodesk_smtp_encryption").val(),
        robodesk_smtp_from_email: $form.find("#robodesk_smtp_from_email").val(),
        robodesk_smtp_from_name: $form.find("#robodesk_smtp_from_name").val(),
      },
      success: function (response) {
        if (response.success) {
          showToast(response.data.message, "success");
        } else {
          showToast(response.data.message, "error");
        }
      },
      error: function (xhr, status, error) {
        showToast("AJAX request failed: " + error, "error");
      },
      complete: function () {
        $testButton.prop("disabled", false).text("Test SMTP Connection");
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
  function toggleUnseenReplyOptions() {
    var enabled = $("#enable-unseen-reply-email").is(":checked");
    $("#reply-unseen-email-options").toggle(enabled);
  }

  toggleUnseenReplyOptions();

  $(document).on("change", "#enable-unseen-reply-email", function () {
    toggleUnseenReplyOptions();
  });

  $(document).on("click", "#save-advanced-settings", function () {
    var $form = $("#advanced-settings-form");
    var $button = $(this);

    $button.prop("disabled", true).text("Saving Advanced Settings...");

    // Collect form data
    var unseenEnabled = $("#enable-unseen-reply-email").is(":checked");
    var settings = {
      enable_auto_assign: $("#enable-auto-assign").is(":checked"),
      enable_notifications: $("#enable-notifications").is(":checked"),
      enable_customer_portal: $("#enable-customer-portal").is(":checked"),
      max_file_upload_size: $form.find('input[type="number"]').val(),
      reply_unseen_email_minutes: unseenEnabled
        ? $("#reply-unseen-email-minutes").val()
        : 0,
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

  const logoCropConfigs = {
    logo: {
      optionKey: "robodesk_logo",
      hidden: "#robodesk_logo",
      preview: "#logo-preview",
      input: 'input[name="logo"]',
      width: 150,
      height: 150,
      label: "Logo",
    },
    wide_logo: {
      optionKey: "robodesk_wide_logo",
      hidden: "#robodesk_wide_logo",
      preview: "#wide-logo-preview",
      input: 'input[name="wide_logo"]',
      width: 600,
      height: 200,
      label: "Wide Logo",
    },
  };

  const buildLogoCropSelectOptions = function (targetWidth, targetHeight) {
    return function (attachment, controller) {
      const realWidth = attachment.get("width");
      const realHeight = attachment.get("height");

      if (!realWidth || !realHeight) {
        return {
          handles: true,
          keys: true,
          instance: true,
          persistent: true,
          imageWidth: targetWidth,
          imageHeight: targetHeight,
          minWidth: targetWidth,
          minHeight: targetHeight,
          aspectRatio: `${targetWidth}:${targetHeight}`,
          x1: 0,
          y1: 0,
          x2: targetWidth,
          y2: targetHeight,
        };
      }

      const requiredRatio = targetWidth / targetHeight;
      const realRatio = realWidth / realHeight;
      let xInit = targetWidth;
      let yInit = targetHeight;

      if (realRatio > requiredRatio) {
        yInit = realHeight;
        xInit = yInit * requiredRatio;
      } else {
        xInit = realWidth;
        yInit = xInit / requiredRatio;
      }

      const x1 = Math.max(0, Math.round((realWidth - xInit) / 2));
      const y1 = Math.max(0, Math.round((realHeight - yInit) / 2));

      controller.set("canSkipCrop", false);

      return {
        handles: true,
        keys: true,
        instance: true,
        persistent: true,
        imageWidth: realWidth,
        imageHeight: realHeight,
        minWidth: Math.min(targetWidth, realWidth),
        minHeight: Math.min(targetHeight, realHeight),
        aspectRatio: `${targetWidth}:${targetHeight}`,
        x1: x1,
        y1: y1,
        x2: Math.min(realWidth, Math.round(xInit + x1)),
        y2: Math.min(realHeight, Math.round(yInit + y1)),
      };
    };
  };

  const getLogoCropController = function (width, height) {
    return wp.media.controller.Cropper.extend({
      doCrop: function (attachment) {
        const cropDetails = attachment.get("cropDetails");
        cropDetails.dst_width = width;
        cropDetails.dst_height = height;

        return wp.ajax.post("crop-image", {
          nonce: attachment.get("nonces").edit,
          id: attachment.get("id"),
          cropDetails: cropDetails,
        });
      },
    });
  };

  const saveCroppedLogo = function (optionKey, croppedUrl, nonce) {
    return $.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        action: "robodesk_save_cropped_logo",
        nonce: nonce,
        option_key: optionKey,
        cropped_url: croppedUrl,
      },
    });
  };

  const openLogoCropper = function (attachmentId, cropConfig, nonce) {
    return new Promise(function (resolve) {
      if (
        typeof wp === "undefined" ||
        !wp.media ||
        !wp.media.controller ||
        !window.Backbone ||
        !attachmentId
      ) {
        resolve();
        return;
      }

      const attachment = wp.media.attachment(attachmentId);
      attachment
        .fetch()
        .done(function () {
          const CropController = getLogoCropController(
            cropConfig.width,
            cropConfig.height,
          );

          const frame = wp.media({
            state: "cropper",
            button: {
              text: `Crop ${cropConfig.label}`,
              close: false,
            },
            states: [
              new CropController({
                imgSelectOptions: buildLogoCropSelectOptions(
                  cropConfig.width,
                  cropConfig.height,
                ),
              }),
            ],
          });

          frame._selection = {
            single: attachment,
          };
          frame
            .state("cropper")
            .set("selection", new Backbone.Collection([attachment]));

          frame.on("cropped", function (croppedImage) {
            const croppedUrl =
              croppedImage && croppedImage.url ? croppedImage.url : "";
            if (!croppedUrl) {
              showToast(`Cropped ${cropConfig.label} URL is missing.`, "error");
              frame.close();
              resolve();
              return;
            }

            saveCroppedLogo(cropConfig.optionKey, croppedUrl, nonce)
              .done(function (saveResponse) {
                if (saveResponse && saveResponse.success) {
                  $(cropConfig.hidden).val(croppedUrl);
                  $(cropConfig.preview)
                    .removeClass("hidden")
                    .attr("src", croppedUrl);
                  $(cropConfig.preview).next("button").removeClass("hidden");
                  $(cropConfig.input).val("");
                } else {
                  const message =
                    (saveResponse &&
                      saveResponse.data &&
                      saveResponse.data.message) ||
                    `Failed to save cropped ${cropConfig.label}.`;
                  showToast(message, "error");
                }
              })
              .fail(function () {
                showToast(
                  `Failed to save cropped ${cropConfig.label}.`,
                  "error",
                );
              })
              .always(function () {
                frame.close();
                resolve();
              });
          });

          frame.on("content:error:crop", function () {
            showToast(`Cropping failed for ${cropConfig.label}.`, "error");
            frame.close();
            resolve();
          });

          frame.on("close", function () {
            resolve();
          });

          frame.open();
        })
        .fail(function () {
          showToast(
            `Could not load ${cropConfig.label} for cropping.`,
            "error",
          );
          resolve();
        });
    });
  };

  const maybeOpenLogoCropper = function (uploadedAttachments, nonce) {
    if (!uploadedAttachments || typeof uploadedAttachments !== "object") {
      return;
    }

    const cropQueue = [];
    if (uploadedAttachments.logo) {
      cropQueue.push({
        id: uploadedAttachments.logo,
        config: logoCropConfigs.logo,
      });
    }
    if (uploadedAttachments.wide_logo) {
      cropQueue.push({
        id: uploadedAttachments.wide_logo,
        config: logoCropConfigs.wide_logo,
      });
    }

    const runQueue = function (index) {
      if (index >= cropQueue.length) {
        return;
      }

      openLogoCropper(cropQueue[index].id, cropQueue[index].config, nonce).then(
        function () {
          runQueue(index + 1);
        },
      );
    };

    runQueue(0);
  };

  // Handle form submission for basic settings
  $(document).on("click", "#save-basic-settings", function () {
    const $form = $("#robodesk-basic-settings-form");
    const $button = $(this);
    const formData = new FormData();

    // Append basic settings
    formData.append("action", "robodesk_save_basic_settings");
    formData.append(
      "nonce",
      $form.find('input[name="robodesk_basic_settings_nonce"]').val(),
    );
    formData.append("settings[site_name]", $form.find("#site_name").val());
    formData.append("settings[admin_email]", $form.find("#admin_email").val());
    // formData.append("settings[timezone]", $form.find("#timezone").val());
    // formData.append("settings[language]", $form.find("#language").val());
    formData.append("settings[theme_color]", $form.find("#theme_color").val());
    formData.append(
      "settings[default_ticket_category]",
      $form.find("#robodesk_default_ticket_category").val(),
    );
    formData.append(
      "settings[chat_heading]",
      $form.find("#chat_heading").val(),
    );
    formData.append(
      "settings[welcome_message]",
      $form.find("#welcome_message").val(),
    );
    formData.append(
      "settings[ticket_intro_message]",
      $form.find("#ticket_intro_message").val(),
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
      $("#robodesk_notification_sound").val(),
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
                response.data.urls.robodesk_notification_sound,
              );
              audioPreview.removeClass("hidden").get(0).load();
              $(
                `button[onclick="removeAudio('notification-sound-preview', 'robodesk_notification_sound')"]`,
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

          maybeOpenLogoCropper(
            response.data.uploaded_attachments,
            $form.find('input[name="robodesk_basic_settings_nonce"]').val(),
          );

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
      `button[onclick="removeAudio('${previewId}', '${hiddenId}')"]`,
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
      $form.find('input[name="robodesk_ticket_assignment_nonce"]').val(),
    );
    formData.append(
      "fetch_interval",
      $form.find('input[name="fetch_interval"]').val(),
    );
    formData.append(
      "default_agent",
      $form.find('select[name="default_agent"]').val(),
    );

    // Basic validation
    if (!$form.find('input[name="fetch_interval"]').val()) {
      showToast("Please enter a fetch interval.", "error");
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

// Helper function to update dashboard after ticket creation
function updateDashboardAfterTicketCreation(ticketId) {
  markConversationsTabStale();

  // Update the stat cards by incrementing counts
  const totalElement = document.getElementById("total-tickets");
  const openElement = document.getElementById("open-tickets");

  if (totalElement) {
    const currentTotal = parseInt(totalElement.textContent) || 0;
    totalElement.textContent = currentTotal + 1;
  }

  if (openElement) {
    const currentOpen = parseInt(openElement.textContent) || 0;
    openElement.textContent = currentOpen + 1;
  }

  const normalizedTicketId = Number(ticketId);

  const tryOpenCreatedTicket = (attempt = 0) => {
    const maxAttempts = 3;
    const hasTicket =
      Array.isArray(dashboard_tickets) &&
      dashboard_tickets.some((t) => Number(t.id) === normalizedTicketId);

    if (hasTicket || attempt >= maxAttempts) {
      if (typeof viewTicket === "function") {
        viewTicket(normalizedTicketId);
      }
      return;
    }

    if (typeof refreshDashboardTicketsData === "function") {
      refreshDashboardTicketsData().finally(() => {
        setTimeout(() => tryOpenCreatedTicket(attempt + 1), 250);
      });
    } else {
      setTimeout(() => tryOpenCreatedTicket(attempt + 1), 250);
    }
  };

  // Refresh first, then open once the created ticket is present in local data.
  if (typeof refreshDashboardTicketsData === "function") {
    refreshDashboardTicketsData().finally(() => {
      tryOpenCreatedTicket(0);
    });
  } else {
    tryOpenCreatedTicket(0);
  }
}

//create a new ticket ajax
jQuery(document).ready(function ($) {
  // Handle form submission for ticket creation
  $("#robodesk-new-ticket-form").on("submit", function (e) {
    e.preventDefault(); // Prevent default form submission

    const $form = $(this);
    const $button = $form.find("#robodesk-create-ticket");
    const formData = new FormData();
    const createTicketNonce =
      $form.find('input[name="robodesk_create_ticket_nonce"]').val() ||
      $form.find('input[name="nonce"]').val() ||
      robodeskAjax?.nonces?.create_ticket ||
      "";
    const descriptionElement = document.getElementById("ticket-description");
    const descriptionHtml = descriptionElement?.innerHTML || "";
    const descriptionText = (descriptionElement?.textContent || "").trim();

    // Append ticket data
    formData.append("action", "robodesk_create_ticket");
    formData.append("nonce", createTicketNonce);
    formData.append("customer_name", $("#customer-name").val());
    formData.append("customer_email", $("#customer-email").val());
    formData.append("ticket_title", $("#ticket-title").val());
    formData.append("ticket_priority", $("#ticket-priority").val());
    formData.append("ticket_category", $("#ticket-category").val());
    formData.append("ticket_assignee", $("#ticket-assignee").val());
    formData.append("ticket_description", descriptionText || descriptionHtml);
    formData.append(
      "ticket_source",
      $form.find('input[name="ticket_source"]').val(),
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

    $button.prop("disabled", true).text("Creating Conversation...");

    $.ajax({
      url: robodeskAjax.ajax_url, // Use localized AJAX URL
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.success) {
          showToast(response.data.message, "success");
          fetchNotifications();

          $form[0].reset(); // Reset form on success

          // Update dashboard stats and listing after creating ticket
          updateDashboardAfterTicketCreation(response.data.ticket_id);
        } else {
          showToast(
            response.data.message || "Failed to create ticket.",
            "error",
          );
        }
      },
      error: function (xhr, status, error) {
        // console.log("AJAX Error:", xhr, status, error);
        showToast("Failed to create ticket: " + error, "error");
      },
      complete: function () {
        $button.prop("disabled", false).text("Create Conversation");
      },
    });
  });
});

// Update ticket status function
function updateTicketStatus(ticketId, newStatus) {
  if (!ticketId || !newStatus) {
    return Promise.resolve({ success: false });
  }

  return fetch(robodeskAjax.ajax_url, {
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
            "error",
          );
          return { success: false, data: response.data };
        }

        // console.log(response);

        const updatedStatus = response.data.status;
        const updatedTime = response.data.updated;

        if (
          typeof dashboard_tickets !== "undefined" &&
          Array.isArray(dashboard_tickets)
        ) {
          const dashboardTicketIndex = dashboard_tickets.findIndex(
            (t) => Number(t.id) === Number(ticketId),
          );
          if (dashboardTicketIndex !== -1) {
            dashboard_tickets[dashboardTicketIndex].status = updatedStatus;
            dashboard_tickets[dashboardTicketIndex].updated = updatedTime;
          }
        }

        if (typeof tickets !== "undefined" && Array.isArray(tickets)) {
          const ticketIndex = tickets.findIndex((t) => t.id === ticketId);
          if (ticketIndex !== -1) {
            tickets[ticketIndex].status = updatedStatus;
            tickets[ticketIndex].updated = updatedTime;
          }
        }

        if (currentTicket && currentTicket.id === ticketId) {
          currentTicket.status = updatedStatus;
          currentTicket.updated = updatedTime;
        }

        showToast(`Ticket status updated to ${updatedStatus}`);
        // updateDashboardStats();
        return { success: true, data: response.data };
      } catch (error) {
        console.error("Error inside .then:", error);
        showToast("Error updating ticket status", "error");
        return { success: false, error };
      }
    })
    .catch((err) => {
      console.error("AJAX catch error:", err);
      showToast("Error updating ticket status", "error");
      return { success: false, error: err };
    });
}

// for ticket priority update
function updateTicketPriority(ticketId, newPriority) {
  if (!ticketId || !newPriority) {
    return Promise.resolve({ success: false });
  }

  return fetch(robodeskAjax.ajax_url, {
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

        if (
          typeof dashboard_tickets !== "undefined" &&
          Array.isArray(dashboard_tickets)
        ) {
          const dashboardTicketIndex = dashboard_tickets.findIndex(
            (t) => Number(t.id) === Number(ticketId),
          );
          if (dashboardTicketIndex !== -1) {
            dashboard_tickets[dashboardTicketIndex].priority = updatedPriority;
            dashboard_tickets[dashboardTicketIndex].updated = updatedTime;
          }
        }

        if (typeof tickets !== "undefined" && Array.isArray(tickets)) {
          const ticketIndex = tickets.findIndex((t) => t.id === ticketId);
          if (ticketIndex !== -1) {
            tickets[ticketIndex].priority = updatedPriority;
            tickets[ticketIndex].updated = updatedTime;
          }
        }

        if (currentTicket && currentTicket.id === ticketId) {
          currentTicket.priority = updatedPriority;
          currentTicket.updated = updatedTime;
        }

        showToast(`Ticket priority updated to ${updatedPriority}`);
        return { success: true, data: response.data };
      } else {
        showToast(
          response.data?.message || "Failed to update priority",
          "error",
        );
        return { success: false, data: response.data };
      }
    })
    .catch((error) => {
      console.error(error);
      showToast("Error updating ticket priority", "error");
      return { success: false, error };
    });
}

//// for ticket assignee update
// This function updates the assignee of a ticket via AJAX
// It expects ticketId and newAssignee to be provided
function updateTicketAssignee(ticketId, newAssignee) {
  if (!ticketId || !newAssignee) {
    return Promise.resolve({ success: false });
  }

  return fetch(robodeskAjax.ajax_url, {
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
        const updatedAssigneeId = response.data.assignee_id || 0;
        const updatedTime = response.data.updated;

        if (
          typeof dashboard_tickets !== "undefined" &&
          Array.isArray(dashboard_tickets)
        ) {
          const dashboardTicketIndex = dashboard_tickets.findIndex(
            (t) => Number(t.id) === Number(ticketId),
          );
          if (dashboardTicketIndex !== -1) {
            dashboard_tickets[dashboardTicketIndex].assignee = updatedAssignee;
            dashboard_tickets[dashboardTicketIndex].assignee_id =
              updatedAssigneeId;
            dashboard_tickets[dashboardTicketIndex].updated = updatedTime;
          }
        }

        if (typeof tickets !== "undefined" && Array.isArray(tickets)) {
          const ticketIndex = tickets.findIndex((t) => t.id === ticketId);
          if (ticketIndex !== -1) {
            tickets[ticketIndex].assignee = updatedAssignee;
            tickets[ticketIndex].assignee_id = updatedAssigneeId;
            tickets[ticketIndex].updated = updatedTime;
          }
        }

        if (currentTicket && currentTicket.id === ticketId) {
          currentTicket.assignee = updatedAssignee;
          currentTicket.assignee_id = updatedAssigneeId;
          currentTicket.updated = updatedTime;
        }

        showToast(`Ticket assigned to ${updatedAssignee}`);
        return { success: true, data: response.data };
      } else {
        showToast(
          response.data?.message || "Failed to update assignee",
          "error",
        );
        return { success: false, data: response.data };
      }
    })
    .catch((error) => {
      console.error(error);
      showToast("Error updating ticket assignee", "error");
      return { success: false, error };
    });
}
//
//
//
// This function adds a reply to the current ticket
// Ajax request is made to the server to save the reply
let isReplySending = false;

function resolveTemplateVariables(content, ticket) {
  if (!content || !ticket) return content;

  const fullName = (ticket.customer || "").trim();
  const nameParts = fullName ? fullName.split(/\s+/) : [];
  const fname = nameParts[0] || "";
  const lname = nameParts.slice(1).join(" ") || "";

  const valueMap = {
    fname,
    lname,
    email: ticket.email || "",
    ticket_id: ticket.id != null ? String(ticket.id) : "",
    ticket_status: ticket.status || "",
    ticket_title: ticket.title || "",
  };

  return content.replace(
    /\{\{\s*(fname|lname|email|ticket_id|ticket_status|ticket_title)\s*\}\}/gi,
    (match, key) => {
      const normalizedKey = String(key).toLowerCase();
      return valueMap[normalizedKey] != null ? valueMap[normalizedKey] : match;
    },
  );
}

function addReply() {
  if (isReplySending) return; // Prevent double send
  const userRole = robodeskCurrentUser?.role;
  const replyBox = document.getElementById("new-reply");
  const replyHtml = replyBox ? replyBox.innerHTML : "";
  // Use textContent to detect actual visible text, normalize NBSP and trim
  const replyTextual = replyBox
    ? (replyBox.textContent || replyBox.innerText || "")
        .replace(/\u00A0/g, " ")
        .trim()
    : "";
  const resolvedReplyText = resolveTemplateVariables(replyHtml, currentTicket);
  const sendBtn = document.getElementById("send-reply-btn");

  // Prevent sending empty or whitespace-only replies (including NBSP and zero-width spaces)
  const normalizedVisible = (replyTextual || "")
    .replace(/[\u00A0\u200B\u200C\uFEFF]/g, " ")
    .trim();
  const resolvedPlain = (resolvedReplyText || "")
    .replace(/&nbsp;|\u00A0|\u200B|\u200C|\uFEFF/g, " ")
    .replace(/<[^>]*>/g, "")
    .trim();
  if (
    (!normalizedVisible || normalizedVisible === "") &&
    (!resolvedPlain || resolvedPlain === "")
  )
    return;

  isReplySending = true;
  if (sendBtn) {
    sendBtn.disabled = true;
    sendBtn.textContent = "Sending...";
  }

  const data = {
    action: "add_ticket_reply",
    ticket_id: currentTicket.id,
    reply: resolvedReplyText,
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
          message: resolvedReplyText,
          timestamp: new Date().toISOString(),
          type: "userRole" in response.data ? response.data.userRole : userRole,
          comment_meta: response.data.comment_meta || { is_action: false },
        };

        if (!Array.isArray(currentTicket.replies)) currentTicket.replies = [];
        currentTicket.replies.push(newReply);
        currentTicket.updated = new Date().toISOString();

        const ticketIndex = tickets.findIndex((t) => t.id === currentTicket.id);
        if (ticketIndex !== -1) tickets[ticketIndex] = currentTicket;

        resetEditorContent("new-reply");
        document.getElementById("replies-count").textContent =
          currentTicket.replies.length;

        // Uncheck signature checkbox after reply if signature addon is active
        if (typeof robodeskSignature !== "undefined") {
          const sigCheckbox = document.getElementById("include-signature");
          if (sigCheckbox) sigCheckbox.checked = false;
        }

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
  } finally {
  }
}

// Render replies to the DOM and scroll to the last reply
function renderReplies(replies) {
  const repliesList = document.getElementById("replies-list");
  if (!repliesList || !Array.isArray(replies)) return;

  repliesList.innerHTML = replies
    .map((reply, index) => {
      // Add special style if reply.comment_meta?.is_action is true
      const isAction = reply.comment_meta?.is_action === true; // Use optional chaining for safety
      const actionClass = isAction ? "reply-action bg-blue-200" : "";
      const replyRole =
        reply.role ||
        (reply.is_bot_response || reply.comment_meta?.is_bot_response
          ? "AI BOT"
          : reply.type === "customer"
            ? "Customer"
            : "Agent");

      return `
        <div data-cid="${reply.id}" class="p-3 rounded-lg replys reply-${
          reply.type
        } ${actionClass}" id="reply-${index}">
          <div class="flex justify-between items-center mb-2">
            <span class="font-medium text-sm text-gray-900">${escapeHTML(
              reply.author,
            )} (${escapeHTML(replyRole)})</span>
            <span class="text-xs text-gray-500">${formatDate(
              reply.timestamp,
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

  showCustomConfirm(
    "Are you sure you want to close this Conversation?",
    function onConfirm() {
      closeTicketAjax(ticketId);
    },
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
    const replyBox = document.getElementById("new-reply");
    const replyText = replyBox.innerHTML.trim();
    const resolvedReplyText = resolveTemplateVariables(
      replyText,
      currentTicket,
    );
    jQuery.ajax({
      url: robodeskAjax.ajax_url,
      type: "POST",
      data: {
        action: "close_ticket",
        nonce: robodeskAjax.nonces.close_ticket,
        ticket_id: ticketId,
        reply: resolvedReplyText,
      },
      success: function (response) {
        if (response.success) {
          showToast(response.data.message);

          // Update the current ticket status without reload
          if (currentTicket && currentTicket.id === ticketId) {
            currentTicket.status = "closed";
          }

          // Clear the reply box
          resetEditorContent("new-reply");

          // Reload the replies to show the close message and any final reply
          loadReplies();
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

// This function add a note to the current ticket

function addNote() {
  const noteBox = document.getElementById("new-note");
  const noteText = noteBox.innerHTML.trim();
  const resolvedNoteText = resolveTemplateVariables(noteText, currentTicket);
  if (!resolvedNoteText || !currentTicket) return;
  userRole = robodeskCurrentUser?.role || "Support Staff";

  const data = {
    action: "add_ticket_note",
    ticket_id: currentTicket.id,
    note: resolvedNoteText,
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
          message: resolvedNoteText,
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

  notesList.innerHTML = notes
    .map(
      (note, index) => `
    <div class="p-4 rounded-lg note-internal reply-${note.type}" id="note-${index}">
      <div class="flex justify-between items-center mb-2">
        <span class="font-medium text-sm text-gray-900">${escapeHTML(
          note.author,
        )} (${note.role || (note.type === "customer" ? "Customer" : "Agent")})</span>
    
        <span class="text-xs text-gray-500">${formatDate(note.timestamp)}</span>
      </div>
      <p class="text-sm text-gray-700">${note.message}</p>
    </div>
  `,
    )
    .join("");

  // Scroll to the last note
  const lastNote = notesList.querySelector(`#note-${notes.length - 1}`);
  if (lastNote) {
    lastNote.scrollIntoView({ behavior: "smooth", block: "end" });
  }
}
// the message is displaying with html tags

//bulk actions on tickets
document.addEventListener("DOMContentLoaded", function () {
  const selectAllCheckbox = document.getElementById("select-all-tickets");
  const ticketCheckboxes = document.querySelectorAll(".ticket-checkbox");
  const bulkActionsBar = document.getElementById("bulk-actions-bar");
  const selectedCountSpan = document.getElementById("selected-count");
  const bulkActionSelect = document.getElementById("bulk-action-select");
  const applyBulkActionBtn = document.getElementById("apply-bulk-action");
  const clearSelectionBtn = document.getElementById("clear-selection");

  // Initialize bulk actions functionality

  initializeBulkActions();

  function initializeBulkActions() {
    // Select/Deselect All functionality
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener("change", function () {
        const isChecked = this.checked;
        ticketCheckboxes.forEach(function (checkbox) {
          checkbox.checked = isChecked;
        });
        updateBulkActionsUI();
      });
    }

    // Individual checkbox change handlers
    ticketCheckboxes.forEach(function (checkbox) {
      checkbox.addEventListener("change", function () {
        updateSelectAllState();
        updateBulkActionsUI();
      });
    });

    // Bulk action select change
    if (bulkActionSelect) {
      bulkActionSelect.addEventListener("change", function () {
        applyBulkActionBtn.disabled =
          !this.value || getSelectedTicketIds().length === 0;
      });
    }

    // Apply bulk action button
    if (applyBulkActionBtn) {
      applyBulkActionBtn.addEventListener("click", function () {
        const selectedIds = getSelectedTicketIds();
        const action = bulkActionSelect.value;

        if (selectedIds.length === 0) {
          showCustomAlert("Please select at least one ticket.");
          return;
        }

        if (!action) {
          showCustomAlert("Please select an action.");
          return;
        }

        // Confirm for all actions
        const actionTexts = {
          delete: "delete",
          close: "close",
          open: "reopen",
          assign: "assign",
          unassign: "unassign",
        };

        const actionText = actionTexts[action] || action;
        const warningText =
          action === "delete" ? " This action cannot be undone." : "";

        showBulkActionConfirm(
          `Are you sure you want to ${actionText} ${selectedIds.length} ticket(s)?${warningText}`,
          function onConfirm() {
            performBulkAction(selectedIds, action);
          },
        );
      });
    }

    // Helper functions for custom modals
    function showCustomAlert(message) {
      const modal = document.createElement("div");
      modal.className =
        "fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40";
      modal.innerHTML = `
      <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full text-center">
        <div class="mb-4 text-lg font-medium text-gray-800">${message}</div>
        <button id="custom-alert-ok" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">OK</button>
      </div>
      `;
      document.body.appendChild(modal);

      modal.querySelector("#custom-alert-ok").onclick = function () {
        document.body.removeChild(modal);
      };
    }

    function showCustomConfirm(message, onConfirm) {
      const modal = document.createElement("div");
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

      modal.querySelector("#custom-confirm-yes").onclick = function () {
        document.body.removeChild(modal);
        if (typeof onConfirm === "function") onConfirm();
      };
      modal.querySelector("#custom-confirm-no").onclick = function () {
        document.body.removeChild(modal);
      };
    }

    function showBulkActionConfirm(message, onConfirm) {
      const modal = document.createElement("div");
      modal.className =
        "fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40";
      modal.innerHTML = `
      <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full text-center">
        <div class="mb-4 text-lg font-medium text-gray-800 text-center">${message}</div>
       
        <div class="flex justify-center gap-4">
        <button id="custom-confirm-yes" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Yes</button>
        <button id="custom-confirm-no" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancel</button>
        </div>
      </div>
      `;
      document.body.appendChild(modal);

      modal.querySelector("#custom-confirm-yes").onclick = function () {
        document.body.removeChild(modal);
        if (typeof onConfirm === "function") onConfirm();
      };
      modal.querySelector("#custom-confirm-no").onclick = function () {
        document.body.removeChild(modal);
      };
    }

    // Clear selection button
    if (clearSelectionBtn) {
      clearSelectionBtn.addEventListener("click", function () {
        clearAllSelections();
      });
    }
  }

  function updateSelectAllState() {
    if (!selectAllCheckbox) return;

    const totalCheckboxes = ticketCheckboxes.length;
    const checkedCheckboxes = document.querySelectorAll(
      ".ticket-checkbox:checked",
    ).length;

    if (checkedCheckboxes === 0) {
      selectAllCheckbox.checked = false;
      selectAllCheckbox.indeterminate = false;
    } else if (checkedCheckboxes === totalCheckboxes) {
      selectAllCheckbox.checked = true;
      selectAllCheckbox.indeterminate = false;
    } else {
      selectAllCheckbox.checked = false;
      selectAllCheckbox.indeterminate = true;
    }
  }

  function updateBulkActionsUI() {
    const selectedCount = getSelectedTicketIds().length;

    if (selectedCount > 0) {
      bulkActionsBar.style.display = "block";
      selectedCountSpan.textContent = `${selectedCount} item${
        selectedCount !== 1 ? "s" : ""
      } selected`;
      applyBulkActionBtn.disabled = !bulkActionSelect.value;
    } else {
      bulkActionsBar.style.display = "none";
      bulkActionSelect.value = "";
      applyBulkActionBtn.disabled = true;
    }
  }

  function getSelectedTicketIds() {
    const selectedCheckboxes = document.querySelectorAll(
      ".ticket-checkbox:checked",
    );
    return Array.from(selectedCheckboxes).map((checkbox) =>
      parseInt(checkbox.value),
    );
  }

  function clearAllSelections() {
    if (selectAllCheckbox) selectAllCheckbox.checked = false;
    ticketCheckboxes.forEach((checkbox) => (checkbox.checked = false));
    bulkActionSelect.value = "";
    updateBulkActionsUI();
  }

  function performBulkAction(ticketIds, action) {
    // Show loading state
    const originalButtonText = applyBulkActionBtn.textContent;
    applyBulkActionBtn.textContent = "Processing...";
    applyBulkActionBtn.disabled = true;

    // Prepare data for AJAX request
    const formData = new FormData();
    formData.append("action", "robodesk_bulk_action");
    formData.append("nonce", bulkActionNonce);
    formData.append("bulk_action", action);
    formData.append("ticket_ids", JSON.stringify(ticketIds));

    // Send AJAX request
    fetch(ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Show success message
          showToast(data.data.message, "success");

          // Clear selections
          clearAllSelections();

          // Reload the page to reflect changes
          setTimeout(() => {
            window.location.reload();
          }, 1500);
        } else {
          // Show error message
          showToast(
            data.data.message ||
              "An error occurred while processing the bulk action.",
            "error",
          );
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        showToast(
          "An error occurred while processing the bulk action.",
          "error",
        );
      })
      .finally(() => {
        // Reset button state
        applyBulkActionBtn.textContent = originalButtonText;
        applyBulkActionBtn.disabled = false;
      });
  }
});

jQuery(document).ready(function ($) {
  function createSSE(ticketId, lastCid, userId = "142", parentElement) {
    let eventSource = null;

    function connect() {
      const sseUrl = `${
        window.location.origin
      }/wp-json/robodesk/v1/adminstream/${ticketId}/${lastCid || ""}/${"42"}`;
      console.log("🔌 Connecting to SSE:", sseUrl);

      eventSource = new EventSource(sseUrl);

      eventSource.addEventListener("new_admin_comment", function (event) {
        const comment = JSON.parse(event.data);
        let index = 12;
        const isAction = comment.comment_meta?.is_action === true;
        const actionClass = isAction ? "reply-action bg-blue-200" : "";
        const commentRole =
          comment.role ||
          (comment.is_bot_response || comment.comment_meta?.is_bot_response
            ? "AI BOT"
            : comment.type === "customer"
              ? "Customer"
              : userRole.toUpperCase());
        const content = `
                      <div
                        data-cid="${comment.id}"
                        class="p-4 rounded-lg replys reply-${
                          comment.type
                        } ${actionClass}"
                        id="reply-${index}"
                      >
                        <div class="flex justify-between items-center mb-2">
                          <span class="font-medium text-sm text-gray-900">
                            ${escapeHTML(comment.author)} (${escapeHTML(commentRole)})
                          </span>
                          <span class="text-xs text-gray-500">
                            ${formatDate(comment.timestamp)}
                          </span>
                        </div>
                        <p class="text-sm text-gray-700">
                          ${isAction ? comment.message : comment.message}
                        </p>
                      </div>
                    `;
        const repliesList = document.getElementById("replies-list");

        // Insert new reply
        repliesList.insertAdjacentHTML("beforeend", content);

        const audioUrl = repliesList.getAttribute("data-audio");
        const audio = new Audio(audioUrl);
        audio.play().catch((err) => {
          console.warn("Autoplay blocked by browser:", err);
        });

        // Scroll to the last reply
        const lastReply = repliesList.querySelector(".replys:last-child");
        if (lastReply) {
          lastReply.scrollIntoView({ behavior: "smooth", block: "end" });
        }
      });

      eventSource.onerror = function (err) {
        // console.error('❌ SSE error:', err);
      };
    }

    function disconnect() {
      if (eventSource) {
        console.log("🔌 Disconnecting SSE...");
        eventSource.close();
        eventSource = null;
      }
    }

    // Return control functions
    return {
      connect,
      disconnect,
    };
  }

  function getQueryParams() {
    var params = {};

    if (window.location.search) {
      try {
        var usp = new URLSearchParams(window.location.search);
        usp.forEach(function (value, key) {
          params[key] = value;
        });
        return params;
      } catch (e) {}
    }

    var qs = window.location.search.replace(/^\?/, "");
    if (!qs) return params;
    qs.split("&").forEach(function (pair) {
      var parts = pair.split("=").map(decodeURIComponent);
      params[parts[0]] = parts[1] === undefined ? "" : parts[1];
    });
    return params;
  }

  // Use params (querystring takes precedence; merge with hash if needed)
  var params = getQueryParams();

  params = Object.assign({}, params);

  if (
    params.page === "robodesk-dashboard" &&
    params.view === "tickets" &&
    params.ticket
  ) {
    setTimeout(() => {
      //run sse event
      //
      const ticketId = params.ticket;

      const repliesList = document.getElementById("replies-list");
      const replyElements = repliesList.getElementsByClassName("replys");
      const lastReply = replyElements[replyElements.length - 1];

      let lastCid = lastReply ? lastReply.getAttribute("data-cid") : null;

      let sse = createSSE(ticketId, lastCid, repliesList);
      sse.connect();

      const observer = new MutationObserver((mutationsList) => {
        for (let mutation of mutationsList) {
          if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
            const newReplies = Array.from(mutation.addedNodes).filter(
              (node) =>
                node.nodeType === 1 && node.classList.contains("replys"),
            );

            if (newReplies.length > 0) {
              const recentlyAddedItem = newReplies[newReplies.length - 1];
              lastCid = recentlyAddedItem
                ? recentlyAddedItem.getAttribute("data-cid")
                : null;

              sse.disconnect();
              sse = createSSE(ticketId, lastCid, repliesList);
              sse.connect();
            }
          }
        }
      });

      observer.observe(repliesList, {
        childList: true,
        subtree: false,
      });
    }, 3000);
  }
});

// Auto-refresh functionality for dashboard tickets
let dashboardRefreshInterval = null;
let notificationPollInterval = null;
let notificationCount = 0;
let notificationItems = [];
let shouldRefreshTicketsPage = false;
let lastTicketsPageReloadAt = 0;

function updateDashboardStatsFromTickets(ticketsData) {
  const allTickets = Array.isArray(ticketsData) ? ticketsData : [];
  const stats = {
    total: allTickets.length,
    open: allTickets.filter((t) => t.status === "open").length,
    inProgress: allTickets.filter((t) => t.status === "in-progress").length,
    closed: allTickets.filter((t) => t.status === "closed").length,
  };

  const statsTargets = {
    "total-tickets": stats.total,
    "open-tickets": stats.open,
    "in-progress-tickets": stats.inProgress,
    "closed-tickets": stats.closed,
  };

  Object.entries(statsTargets).forEach(([id, value]) => {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = String(value);
    }
  });
}

function markConversationsTabStale() {
  shouldRefreshTicketsPage = true;
  if (currentPage === "tickets") {
    reloadConversationsPageIfVisible();
  }
}

function reloadConversationsPageIfVisible() {
  if (currentPage !== "tickets") {
    return;
  }

  // Guard against rapid repeated reload calls.
  const now = Date.now();
  if (now - lastTicketsPageReloadAt < 5000) {
    return;
  }
  lastTicketsPageReloadAt = now;
  shouldRefreshTicketsPage = false;

  const url = new URL(window.location.href);
  url.searchParams.set("tab", "tickets");
  url.searchParams.delete("ticket");
  window.location.assign(url.toString());
}

// Function to create a snapshot of tickets for comparison
function createTicketsSnapshot(tickets) {
  return tickets.map((t) => ({
    id: t.id,
    title: t.title,
    source: t.source,
    status: t.status,
    assignee: t.assignee,
    updated: t.updated,
    replies_count: t.replies ? t.replies.length : 0,
  }));
}

// Function to compare tickets and detect changes
function detectTicketChanges(oldTickets, newTickets) {
  const changes = {
    new: [],
    updated: [],
    statusChanged: [],
    assigneeChanged: [],
    newReplies: [],
  };

  const oldTicketsMap = new Map(oldTickets.map((t) => [t.id, t]));
  const newTicketsMap = new Map(newTickets.map((t) => [t.id, t]));

  // Check for new tickets and changes in existing tickets
  newTickets.forEach((newTicket) => {
    const oldTicket = oldTicketsMap.get(newTicket.id);

    if (!oldTicket) {
      // New ticket detected
      changes.new.push(newTicket);
    } else {
      // Check for updates
      if (newTicket.updated !== oldTicket.updated) {
        changes.updated.push(newTicket);
      }

      // Check for status changes
      if (newTicket.status !== oldTicket.status) {
        changes.statusChanged.push({
          ticket: newTicket,
          oldStatus: oldTicket.status,
          newStatus: newTicket.status,
        });
      }

      // Check for assignee changes
      if (newTicket.assignee !== oldTicket.assignee) {
        changes.assigneeChanged.push({
          ticket: newTicket,
          oldAssignee: oldTicket.assignee,
          newAssignee: newTicket.assignee,
        });
      }

      // Check for new replies
      if (newTicket.replies_count > oldTicket.replies_count) {
        changes.newReplies.push({
          ticket: newTicket,
          newRepliesCount: newTicket.replies_count - oldTicket.replies_count,
        });
      }
    }
  });

  return changes;
}

// Function to update notification badge
function updateNotificationBadge(count) {
  const badge = document.getElementById("notification-badge");
  const bell = document.getElementById("dashboard-notification-bell");

  if (badge && bell) {
    if (count > 0) {
      badge.textContent = count > 99 ? "99+" : count;
      badge.style.display = "flex";
      bell.classList.add("animate-pulse");
    } else {
      badge.style.display = "none";
      bell.classList.remove("animate-pulse");
    }
  }
}

// Function to show notification toast for changes
function showChangeNotification(changes) {
  const messages = [];

  if (changes.new.length > 0) {
    messages.push(
      `${changes.new.length} new conversation${changes.new.length > 1 ? "s" : ""}`,
    );
  }

  if (changes.newReplies.length > 0) {
    const totalNewReplies = changes.newReplies.reduce(
      (sum, item) => sum + item.newRepliesCount,
      0,
    );
    messages.push(
      `${totalNewReplies} new ${totalNewReplies > 1 ? "replies" : "reply"}`,
    );
  }

  if (changes.statusChanged.length > 0) {
    messages.push(
      `${changes.statusChanged.length} status ${
        changes.statusChanged.length > 1 ? "changes" : "change"
      }`,
    );
  }

  if (changes.assigneeChanged.length > 0) {
    messages.push(
      `${changes.assigneeChanged.length} assignment ${
        changes.assigneeChanged.length > 1 ? "changes" : "change"
      }`,
    );
  }

  if (messages.length > 0) {
    showToast("Updates: " + messages.join(", "), "success");
  }
}

function fetchNotifications() {
  return fetch(robodeskAjax.ajax_url, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: new URLSearchParams({
      action: "robodesk_get_notifications",
      nonce: robodeskAjax.nonces.notifications,
    }),
  })
    .then((res) => res.json())
    .then((response) => {
      if (!response.success) {
        return;
      }

      const prevCount = notificationCount;
      const prevItemsMap = {};
      notificationItems.forEach(function (item) {
        prevItemsMap[item.id] = item;
      });
      notificationItems = Array.isArray(response.data.items)
        ? response.data.items
        : [];
      notificationCount = Number(response.data.count || 0);
      updateNotificationBadge(notificationCount);

      var hasNewOrUpdatedNotification = notificationCount > prevCount;
      if (!hasNewOrUpdatedNotification && prevCount > 0) {
        for (var i = 0; i < notificationItems.length; i++) {
          var newItem = notificationItems[i];
          var prevItem = prevItemsMap[newItem.id];
          if (prevItem && (prevItem.message !== newItem.message || prevItem.timestamp !== newItem.timestamp)) {
            hasNewOrUpdatedNotification = true;
            break;
          }
          if (!prevItem) {
            hasNewOrUpdatedNotification = true;
            break;
          }
        }
      }

      if (hasNewOrUpdatedNotification) {
        const audioUrl =
          typeof robodeskNotificationSound !== "undefined"
            ? robodeskNotificationSound
            : null;
        if (audioUrl) {
          const audio = new Audio(audioUrl);
          audio.play().catch((err) => {
            console.warn("Autoplay blocked by browser:", err);
          });
        }
      }

      const dropdown = document.getElementById("notification-dropdown");
      if (dropdown && !dropdown.classList.contains("hidden")) {
        renderNotificationList();
      }
    })
    .catch((error) => {
      console.error("Error fetching notifications:", error);
    });
}

// Function to toggle notification dropdown
function toggleNotificationDropdown() {
  const dropdown = document.getElementById("notification-dropdown");
  if (dropdown.classList.contains("hidden")) {
    dropdown.classList.remove("hidden");
    fetchNotifications();
    renderNotificationList();
  } else {
    dropdown.classList.add("hidden");
  }
}

// Function to render notification list
function renderNotificationList() {
  const listContainer = document.getElementById("notification-list");

  if (notificationItems.length === 0) {
    listContainer.innerHTML = `
      <div class="px-4 py-8 text-center text-gray-500 text-sm">
        <i class="fas fa-bell-slash text-3xl mb-2 text-gray-400"></i>
        <p>No new notifications</p>
      </div>
    `;
    return;
  }

  // Reverse to show newest first
  const sortedItems = [...notificationItems].reverse();

  listContainer.innerHTML = sortedItems
    .map((item, index) => {
      const timeAgo = timeSinceCompact(parseRobodeskDate(item.timestamp));
      let icon = "fa-bell";
      let iconColor = "text-blue-500";
      const ticketData = Array.isArray(dashboard_tickets)
        ? dashboard_tickets.find((t) => t.id === item.ticketId)
        : null;
      const sourceValue =
        item.source || (ticketData && ticketData.source) || "N/A";
      const assigneeValue =
        item.assignee || (ticketData && ticketData.assignee) || "Unassigned";

      switch (item.type) {
        case "ticket_created":
          icon = "fa-plus-circle";
          iconColor = "text-green-500";
          break;
        case "reply_added":
          icon = "fa-comment";
          iconColor = "text-blue-500";
          break;
        case "status_change":
          icon = "fa-exchange-alt";
          iconColor = "text-yellow-500";
          break;
        case "assignment_change":
          icon = "fa-user";
          iconColor = "text-purple-500";
          break;
      }

      return `
      <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 cursor-pointer transition-colors" onclick="handleNotificationClick(${item.ticketId}, ${item.id})">
        <div class="flex items-start space-x-3">
          <div class="flex-shrink-0 mt-1">
            <i class="fas ${icon} ${iconColor}"></i>
          </div>
          <div class="flex-1 min-w-0">
            <div class="flex items-center justify-between gap-3">
              <p class="text-sm font-medium text-gray-900 truncate">${escapeHTML(decodeHTML(item.title))}</p>
              <p class="text-xs text-gray-500 whitespace-nowrap inline-flex items-center gap-1">
                <i class="far fa-user-circle text-gray-400"></i>
                <span>${escapeHTML(assigneeValue)}</span>
              </p>
            </div>
            <div class="flex items-center justify-between gap-3 mt-1">
              <p class="text-xs text-gray-500 truncate">${escapeHTML(item.message || sourceValue)}</p>
              <p class="text-xs text-gray-400 whitespace-nowrap"><i class="far fa-clock"></i> ${timeAgo}</p>
            </div>
          </div>
        </div>
      </div>
    `;
    })
    .join("");
}

// Function to handle notification click
function handleNotificationClick(ticketId, notificationId) {
  if (notificationId) {
    fetch(robodeskAjax.ajax_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
      },
      body: new URLSearchParams({
        action: "robodesk_mark_notification_read",
        nonce: robodeskAjax.nonces.notifications,
        notification_id: notificationId,
      }),
    })
      .then((res) => res.json())
      .then((response) => {
        if (!response.success) {
          showToast(
            response.data?.message || "Failed to open notification",
            "error",
          );
          return;
        }

        notificationItems = notificationItems.filter(
          (item) => item.id !== notificationId,
        );
        notificationCount = Math.max(0, notificationCount - 1);
        updateNotificationBadge(notificationCount);
        renderNotificationList();
      })
      .catch((error) => {
        console.error("Failed to open notification:", error);
        showToast("Failed to open notification", "error");
      });

    if (ticketId) {
      document.getElementById("notification-dropdown").classList.add("hidden");
      viewTicket(ticketId);
    }

    return;
  }

  if (ticketId) {
    document.getElementById("notification-dropdown").classList.add("hidden");
    viewTicket(ticketId);
  }
}

// Function to clear all notifications
function clearAllNotifications() {
  fetch(robodeskAjax.ajax_url, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: new URLSearchParams({
      action: "robodesk_clear_notifications",
      nonce: robodeskAjax.nonces.notifications,
    }),
  })
    .then((res) => res.json())
    .then((response) => {
      if (!response.success) {
        showToast("Failed to clear notifications", "error");
        return;
      }

      notificationCount = 0;
      notificationItems = [];
      updateNotificationBadge(0);
      renderNotificationList();
      document.getElementById("notification-dropdown").classList.add("hidden");
      showToast("All notifications cleared", "success");
    })
    .catch((error) => {
      console.error("Failed to clear notifications:", error);
      showToast("Failed to clear notifications", "error");
    });
}

// Close dropdown when clicking outside
document.addEventListener("click", function (event) {
  const dropdown = document.getElementById("notification-dropdown");
  const bell = document.getElementById("dashboard-notification-bell");

  if (
    dropdown &&
    bell &&
    !dropdown.contains(event.target) &&
    !bell.contains(event.target)
  ) {
    dropdown.classList.add("hidden");
  }
});

// Test function - can be called from browser console: testDashboardNotification()
window.testDashboardNotification = function () {
  const fakeId = Date.now();
  notificationItems.push({
    id: fakeId,
    type: "ticket_created",
    ticketId: 123,
    title: "Test Notification #" + fakeId,
    message: "This is a test notification to verify the system is working",
    source: "admin",
    assignee: "Unassigned",
    timestamp: new Date().toISOString(),
  });

  notificationCount = notificationItems.length;
  updateNotificationBadge(notificationCount);
  renderNotificationList();
  showToast("Test notification added", "success");
};

// Function to refresh dashboard tickets via AJAX
function refreshDashboardTicketsData() {
  // Refresh on all dashboard views to keep notifications updated

  return fetch(robodeskAjax.ajax_url, {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
    },
    body: new URLSearchParams({
      action: "robodesk_refresh_dashboard_tickets",
      nonce: robodeskAjax.nonces.refresh_dashboard_tickets,
    }),
  })
    .then((res) => res.json())
    .then((response) => {
      if (response.success) {
        const previousTickets = Array.isArray(dashboard_tickets)
          ? [...dashboard_tickets]
          : [];
        const refreshedTickets = Array.isArray(response.data.tickets)
          ? response.data.tickets
          : [];
        const changes = detectTicketChanges(previousTickets, refreshedTickets);

        // Update the global dashboard_tickets array with fresh data
        dashboard_tickets.length = 0; // Clear the array
        Array.prototype.push.apply(dashboard_tickets, refreshedTickets);

        // Keep dashboard stat cards in sync with latest ticket data.
        updateDashboardStatsFromTickets(dashboard_tickets);

        if (changes.new.length > 0) {
          markConversationsTabStale();
        }

        // Re-render the table with updated data
        loadDashboardTickets();
        return refreshedTickets;
      } else {
        console.error("Failed to refresh dashboard tickets:", response.data);
        return [];
      }
    })
    .catch((error) => {
      console.error("Error refreshing dashboard tickets:", error);
      return [];
    });
}

// Function to start auto-refresh
function startDashboardAutoRefresh() {
  // Clear any existing interval
  if (dashboardRefreshInterval) {
    clearInterval(dashboardRefreshInterval);
  }

  // Start auto-refresh every 30 seconds (30000 milliseconds)
  dashboardRefreshInterval = setInterval(refreshDashboardTicketsData, 30000);
}

// Function to stop auto-refresh
function stopDashboardAutoRefresh() {
  if (dashboardRefreshInterval) {
    clearInterval(dashboardRefreshInterval);
    dashboardRefreshInterval = null;
  }
}

// Start auto-refresh when on dashboard page
document.addEventListener("DOMContentLoaded", function () {
  // Initialize the notification badge with stored count on page load
  updateNotificationBadge(notificationCount);

  fetchNotifications();
  if (notificationPollInterval) {
    clearInterval(notificationPollInterval);
  }
  notificationPollInterval = setInterval(fetchNotifications, 15000);

  // Start auto-refresh to keep notifications updated across all dashboard views
  startDashboardAutoRefresh();
});

// Override the filterDashboardTickets function to control auto-refresh
const originalFilterDashboardTickets = filterDashboardTickets;
filterDashboardTickets = function (filterType) {
  // Call the original function
  originalFilterDashboardTickets(filterType);

  // Keep auto-refresh running to update notifications
  startDashboardAutoRefresh();
};

// Auto-refresh functionality for ticket detail replies
let repliesRefreshInterval = null;

// Function to refresh replies for the current ticket
function refreshTicketReplies() {
  if (!currentTicket || !currentTicket.id) {
    return;
  }

  // Only refresh if we're on the replies tab
  const repliesTab = document.getElementById("replies-tab");
  if (!repliesTab || repliesTab.classList.contains("hidden")) {
    return;
  }

  const data = {
    action: "get_ticket_replies",
    ticket_id: currentTicket.id,
    nonce: robodeskAjax.nonces.get_ticket_replies,
  };

  fetch(robodeskAjax.ajax_url, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams(data).toString(),
  })
    .then((res) => res.json())
    .then((result) => {
      if (result.success) {
        const currentRepliesCount = currentTicket.replies
          ? currentTicket.replies.length
          : 0;
        const newRepliesCount = result.data.count;

        // Only update if there are new replies
        if (newRepliesCount > currentRepliesCount) {
          // Update current ticket replies
          currentTicket.replies = result.data.replies;

          // Update the ticket in the tickets array
          const ticketIndex = tickets.findIndex(
            (t) => t.id === currentTicket.id,
          );
          if (ticketIndex !== -1) {
            tickets[ticketIndex].replies = result.data.replies;
          }

          // Re-render replies
          renderReplies(result.data.replies);

          // Update count badge
          document.getElementById("replies-count").textContent =
            newRepliesCount;

          // Play notification sound if available
          const repliesList = document.getElementById("replies-list");
          const audioUrl = repliesList.getAttribute("data-audio");
          if (audioUrl) {
            const audio = new Audio(audioUrl);
            audio.play().catch((err) => {
              console.warn("Autoplay blocked by browser:", err);
            });
          }
        }
      }
    })
    .catch((error) => {
      console.error("Error refreshing replies:", error);
    });
}

// Function to start replies auto-refresh
function startRepliesAutoRefresh() {
  // Clear any existing interval
  if (repliesRefreshInterval) {
    clearInterval(repliesRefreshInterval);
  }

  // Start auto-refresh every 10 seconds
  repliesRefreshInterval = setInterval(refreshTicketReplies, 10000);
}

// Function to stop replies auto-refresh
function stopRepliesAutoRefresh() {
  if (repliesRefreshInterval) {
    clearInterval(repliesRefreshInterval);
    repliesRefreshInterval = null;
  }
}

// Override the viewTicket function to start auto-refresh
const originalViewTicket = viewTicket;
viewTicket = function (ticketId) {
  // Stop any existing refresh
  stopRepliesAutoRefresh();

  // Call the original function
  originalViewTicket(ticketId);

  // Start auto-refresh for replies after a short delay to ensure page is loaded
  setTimeout(() => {
    if (currentTicket && currentTicket.id === ticketId) {
      startRepliesAutoRefresh();
    }
  }, 1000);
};

// Override the showPage function to stop refresh when leaving ticket detail
const originalShowPage = showPage;
showPage = function (pageName, fromClick = false) {
  // Stop replies refresh when leaving ticket detail page
  if (pageName !== "ticket-detail") {
    stopRepliesAutoRefresh();
  }

  // Call the original function
  originalShowPage(pageName, fromClick);

  // Start dashboard refresh when going to dashboard
  if (pageName === "dashboard") {
    if (
      currentDashboardFilter === "my_conversation" ||
      currentDashboardFilter === "unassigned_conversation"
    ) {
      startDashboardAutoRefresh();
    }
  } else {
    stopDashboardAutoRefresh();
  }
};

// Override the showTicketTab function to control refresh based on active tab
const originalShowTicketTab = showTicketTab;
showTicketTab = function (tabName) {
  // Call the original function
  originalShowTicketTab(tabName);

  // Only auto-refresh when on replies tab
  if (tabName === "replies" && currentTicket) {
    startRepliesAutoRefresh();
  } else {
    stopRepliesAutoRefresh();
  }
};

jQuery(document).ready(function () {
  // 1. Toggle menu when hamburger is clicked
  jQuery(".rbd-nav-ham-tog").click(function (event) {
    event.stopPropagation();
    jQuery(".rbd-menu-head").toggleClass("rbd-show");
  });

  // 2. Close menu when any button inside it is clicked
  jQuery(".rbd-menu-head button").click(function () {
    jQuery(".rbd-menu-head").removeClass("rbd-show");
  });

  // 3. Close menu when clicking anywhere outside
  jQuery(document).click(function (event) {
    if (!jQuery(event.target).closest(".rbd-menu-head").length) {
      jQuery(".rbd-menu-head").removeClass("rbd-show");
    }
  });
});
