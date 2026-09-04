import React, { useState, useEffect, Suspense, useRef, useMemo } from "react";
import { FaChevronRight, FaChevronLeft } from "react-icons/fa";
import "../styles/MessageTabs.css";

import { LuDot } from "react-icons/lu";
import { RiPencilFill } from "react-icons/ri";
import Footer from "./Footer";
import Login from "./Login";
import Preloader from "../images/preloader.svg";

import { useAppData } from "../utils/AppDataContext";
import defaultLogo from "../images/mmhd-logo.png";
// import ChangePassword from './ChangePassword';

const SingleTicket = React.lazy(() => import("./SingleTicket"));

const TicketItem = ({ ticket, onSelect }) => {
  const [showNotification, setShowNotification] = useState(false);

  useEffect(() => {
    setShowNotification(ticket.notifications === "no" ? true : false);
  }, [ticket]);

  // Get priority colors from mmhdAuth
  const priorityColors = window.mmhdAuth?.priorityColors || {};
  const priorityColor = priorityColors[ticket.priority] || "#000000";
  const displayTicketNumber = String(ticket.ticketNumber || "").replace(
    /^TCKT/i,
    "CONV",
  );

  return (
    <div
      className="mmhd-hover single-mmhd-ticket mmhd-flex mmhd-align-start mmhd-gap-8 mmhd-border-bottom-light mmhd-cursor-pointer single-mmhd-active-ticket"
      onClick={() => onSelect(ticket)}
    >
      <div>
        <img
          src={ticket.authorImage}
          alt="User Image"
          className="mmhd-rounded-border mmhd-object-cover"
          style={{ height: "30px", width: "30px" }}
        />
      </div>
      <div className="mmhd-flex mmhd-justify-between mmhd-w-full">
        <div className="mmhd-message-list-wrap">
          <h5
            className={`sub-text mmhd-title-color ${
              showNotification ? "mmhd-title-with-dot" : ""
            }`}
          >
            {ticket.title}
          </h5>
          <div className="mmhd-chat-info">
            <div className="mmhd-text-small mmhd-flex mmhd-gap-5">
              <span className="mmhd-flex mmhd-align-center">
                {ticket.source} <LuDot /> {ticket.timeAgo}
              </span>
            </div>
            <div className="mmhd-flex mmhd-align-center mmhd-gap-10">
              <span
                className="mmhd-priority mmhd-fw-medium"
                style={{ backgroundColor: priorityColor, color: "#ffffff" }}
              >
                {ticket.priority}
              </span>
              <span className="mmhd-text-small mmhd-fw-medium">
                (#{displayTicketNumber})
              </span>
            </div>
          </div>
        </div>
        <FaChevronRight />
      </div>
    </div>
  );
};

const MessageTabs = ({
  setActiveTab,
  setIsOpen,
  isExpanded,
  setIsExpanded,
  activeTab,
  selectedNewTicket,
  updateSelectedTicket,
  handleFocusedTextArea,
}) => {
  const [searchTerm, setSearchTerm] = useState("");
  const [selectedTicket, setSelectedTicket] = useState(selectedNewTicket);
  const [currentTab, setCurrentTab] = useState("active-conversations");
  const [priorityFilter, setPriorityFilter] = useState("all");

  const [loading, setLoading] = useState(true);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [loggedInuserId, setloggedInuserId] = useState(null);

  const {
    loadingContext,
    ticketsContext,
    loggedinContext,
    loggedInUsrId,
    updateTicket,
    setSingleTicketPage,
    isSingleTicketPage,
    refreshTickets,
    loadMoreTickets,
    ticketPagination,
    isLoadingMore,
  } = useAppData();

  const image_urls = window.mmhdData?.imageUrls || {};
  const messageWrapperRef = useRef(null);

  useEffect(() => {
    setSingleTicketPage(false);
  }, []);

  useEffect(() => {
    refreshTickets(searchTerm, priorityFilter, currentTab);
  }, [currentTab]);

  const firstMessageId = useMemo(() => {
    if (!ticketsContext || ticketsContext.length === 0) return false;

    return ticketsContext[0].id;
  }, [ticketsContext]);

  const url = useMemo(() => {
    if (!loggedinContext && !loggedInUsrId) return null;
    const nonce = window.mmhdAuth?.restNonce || window.wpApiSettings?.nonce || '';
    return `wp-json/mmhd/v1/stream/tickets/0/${firstMessageId}/${loggedInUsrId}/?ticket=1&_wpnonce=${nonce}`;
  }, [firstMessageId]);

  useEffect(() => {
    if (loggedinContext && loggedInUsrId) {
      setIsLoggedIn(true);
      setloggedInuserId(loggedInUsrId);
      if (!selectedTicket) {
        setSelectedTicket(selectedNewTicket ? selectedNewTicket : null);
      }
    } else {
      setLoading(false);
    }
  }, [isLoggedIn, loggedInUsrId, ticketsContext]);

  useEffect(() => {
    if (selectedNewTicket && selectedNewTicket.id) {
      setSelectedTicket(selectedNewTicket);
    }
  }, [selectedNewTicket]);

  const handleLogin = (loginStatus) => {
    setIsLoggedIn(loginStatus);
    if (loginStatus) {
      fetchTickets();
    }
  };

  const handleBackToHome = () => {
    if (setActiveTab) {
      setActiveTab(0);
      setIsExpanded(false);
    }
    updateSelectedTicket(null);
  };

  const handleAddTicket = async () => {
    if (!loggedInuserId) return;
    // sessionStorage.setItem('activate_text', 'true');
    setActiveTab(0);
  };

  const handleScroll = (e) => {
    const wrapper = messageWrapperRef.current;
    if (!wrapper) return;

    const { scrollTop, scrollHeight, clientHeight } = wrapper;
    if (scrollTop + clientHeight >= scrollHeight - 10) {
      loadMoreTickets();
    }
  };

  function handleSearch(nextKeyword, nextPriority, nextStatus) {
    refreshTickets(nextKeyword, nextPriority, nextStatus);
  }

  const filteredTickets =
    Array.isArray(ticketsContext) && ticketsContext.length > 0
      ? ticketsContext.filter((ticket) => {
          // --- Status filter ---
          let matchesTab = false;

          if (currentTab === "active-conversations") {
            matchesTab =
              ticket.status === "open" || ticket.status === "in-progress";
          } else {
            matchesTab = ticket.status === currentTab;
          }

          const ticketPriorityName = ticket.priority?.toLowerCase() || "";
          const matchingTerm = window.mmhdAuth?.priorityTerms?.find(
            (term) =>
              term.name?.toLowerCase() === ticketPriorityName ||
              term.slug?.toLowerCase() === ticketPriorityName,
          );
          const ticketPrioritySlug =
            matchingTerm?.slug?.toLowerCase() || ticketPriorityName;
          const filterPriority = priorityFilter?.toLowerCase() || "all";

          const matchesPriority =
            filterPriority === "all" ||
            ticketPriorityName === filterPriority ||
            ticketPrioritySlug === filterPriority;

          // --- Search filter ---
          const searchLower = searchTerm.toLowerCase();
          const titleLower = ticket.title?.toLowerCase() || "";
          const ticketNumberLower = ticket.ticketNumber?.toLowerCase() || "";

          const matchesSearch =
            titleLower.includes(searchLower) ||
            ticketNumberLower.includes(searchLower);

          // --- Final condition ---
          return matchesTab && matchesPriority && matchesSearch;
        })
      : [];

  return (
    <div className="mmhd-main-content">
      {isLoggedIn ? (
        <>
          {selectedTicket ? (
            <Suspense
              fallback={
                <img src={Preloader} alt="Loading" className="mmhd-preloader" />
              }
            >
              <SingleTicket
                ticket={selectedNewTicket ? selectedNewTicket : selectedTicket}
                onBack={() => {
                  setSelectedTicket(null);
                  if (updateSelectedTicket) updateSelectedTicket(null);
                }}
                loggedInUserId={loggedInuserId}
                setActiveTab={setActiveTab}
                handleFocusedTextArea={handleFocusedTextArea}
              />
            </Suspense>
          ) : (
            <>
              <div className="mmhd-search-header mmhd-bg-blue mmhd-flex mmhd-items-center mmhd-justify-between mmhd-flex-col mmhd-message-list-header">
                <button
                  className="back-button mmhd-flex mmhd-items-center mmhd-gap-1 mmhd-border-none mmhd-bg-transparent mmhd-m-0 mmhd-p-0"
                  onClick={handleBackToHome}
                >
                  <FaChevronLeft />
                </button>
                {image_urls.wide_logo && image_urls.wide_logo.trim() !== "" ? (
                  <img
                    src={image_urls.wide_logo}
                    alt="Plugin Wide Logo"
                    className="mmhd-header-logo mmhd-m-auto"
                    onError={(e) => {
                      e.target.style.display = "none";
                    }}
                  />
                ) : image_urls.logo && image_urls.logo.trim() !== "" ? (
                  <img
                    src={image_urls.logo}
                    alt="Plugin Logo"
                    className=" mmhd-header-logo mmhd-m-auto"
                    onError={(e) => {
                      e.target.style.display = "none";
                    }}
                  />
                ) : (
                  <div className=" mmhd-header-logo default-logo">
                    <img
                      src={defaultLogo}
                      alt="Default Plugin Logo"
                      className=" mmhd-header-logo mmhd-m-auto"
                    />
                  </div>
                )}
              </div>
              <div className="mmhd-message-tab-content">
                <div className="mmhd-p-16">
                  <h4 className="heading-4 mmhd-text-center">Conversations</h4>
                  <div className="mmhd-body-text mmhd-mt-8 mmhd-text-center ">
                    <p>All your conversations are here.</p>
                  </div>
                </div>

                {/* Search and Priority Filter */}

                <div className="mmhd-p-16 mmhd-py-10 mmhd-flex mmhd-gap-10 mmhd-align-center mmhd-justify-between">
                  <input
                    type="text"
                    id="keyword-search"
                    value={searchTerm}
                    onChange={(e) => {
                      const nextKeyword = e.target.value;
                      setSearchTerm(nextKeyword);
                      handleSearch(nextKeyword, priorityFilter, currentTab);
                    }}
                    placeholder="Search"
                    className="mmhd-border-light mmhd-border-radius-md mmhd-p-10 mmhd-color-gray mmhd-fw-medium flex-2"
                  />
                  <select
                    id="priority-filter"
                    value={priorityFilter}
                    onChange={(e) => {
                      const nextPriority = e.target.value;
                      setPriorityFilter(nextPriority);
                      handleSearch(searchTerm, nextPriority, currentTab);
                    }}
                    className="mmhd-border-light mmhd-p-10 mmhd-body-text mmhd-border-radius-md mmhd-color-gray mmhd-fw-medium flex-1"
                  >
                    <option value="all">All</option>
                    {window.mmhdAuth?.priorityTerms?.map((priority) => (
                      <option
                        key={priority.term_id}
                        value={priority.slug || priority.name?.toLowerCase()}
                      >
                        {priority.name}
                      </option>
                    ))}
                  </select>
                  <select
                    id="status-conversation"
                    value={currentTab}
                    onChange={(e) => {
                      const nextStatus = e.target.value;
                      setCurrentTab(nextStatus);
                      handleSearch(searchTerm, priorityFilter, nextStatus);
                    }}
                    className="mmhd-p-10 mmhd-py-5 mmhd-border-light mmhd-border-radius-md mmhd-body-text ticket-tab mmhd-color-gray mmhd-fw-medium"
                  >
                    {Object.entries(window.mmhdAuth?.status || {}).map(([key, label]) => (
                      <option key={key} value={key}>
                        {key === "active-conversations" ? "Active" : label}
                      </option>
                    ))}
                  </select>
                </div>

                {/* Ticket List */}
                <div
                  className="mmhd-messge-wrapper"
                  ref={messageWrapperRef}
                  onScroll={handleScroll}
                >
                  <div className="mmhd-tickets">
                    <div className="mmhd-active-tickets-wrap">
                      {loadingContext ? (
                        <img
                          src={Preloader}
                          alt="Loading"
                          className="mmhd-preloader"
                        />
                      ) : filteredTickets.length > 0 ? (
                        filteredTickets.map((ticket) => (
                          <TicketItem
                            key={ticket.id}
                            ticket={ticket}
                            onSelect={(t) => {
                              setSelectedTicket(t);
                              if (updateSelectedTicket) updateSelectedTicket(t);
                            }}
                            setActiveTab={setActiveTab}
                          />
                        ))
                      ) : (
                        <p className="mmhd-text-center mmhd-text-gray mmhd-m-5">
                          No tickets found
                        </p>
                      )}
                      {isLoadingMore && (
                        <div className="mmhd-text-center mmhd-py-10">
                          <img src={Preloader} alt="Loading" className="mmhd-preloader" />
                        </div>
                      )}
                    </div>
                  </div>
                  <div className="mmhd-send-message mmhd-text-center mmhd-px-18 mmhd-py-15 mmhd-flex mmhd-justify-center">
                    <button
                      className="button no-bg mmhd-flex mmhd-align-center mmhd-gap-10 mmhd-cursor-pointer"
                      onClick={(e) => {
                        handleAddTicket();
                        handleFocusedTextArea(true);
                      }}
                    >
                      Start a conversation
                      <RiPencilFill />
                    </button>
                  </div>
                </div>
              </div>
              {/* <Footer activeTab={activeTab} setActiveTab={setActiveTab} /> */}
              <Footer
                activeTab={activeTab}
                setActiveTab={setActiveTab}
                updateSelectedTicket={updateSelectedTicket}
              />
            </>
          )}
        </>
      ) : (
        <Login
          setShowLogin={handleLogin}
          activeTab={activeTab}
          setActiveTab={setActiveTab}
          updateSelectedTicket={updateSelectedTicket}
        />
      )}
    </div>
  );
};

export default MessageTabs;
