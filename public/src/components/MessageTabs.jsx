import React, { useState, useEffect, Suspense, useRef, useMemo } from "react";
import { FaChevronRight, FaChevronLeft } from "react-icons/fa";
import "../styles/MessageTabs.css";

import { LuDot } from "react-icons/lu";
import { RiPencilFill } from "react-icons/ri";
import Footer from "./Footer";
import Login from "./Login";
import Preloader from "../images/preloader.svg";

import { useAppData } from "../utils/AppDataContext";
import defaultLogo from "../images/robodesk-logo.png";
// import ChangePassword from './ChangePassword';

const SingleTicket = React.lazy(() => import("./SingleTicket"));

const TicketItem = ({ ticket, onSelect }) => {
  const [showNotification, setShowNotification] = useState(false);

  useEffect(() => {
    setShowNotification(ticket.notifications === "no" ? true : false);
  }, [ticket]);

  // Get priority colors from robodeskAuth
  const priorityColors = window.robodeskAuth?.priorityColors || {};
  const priorityColor = priorityColors[ticket.priority] || "#000000";
  const displayTicketNumber = String(ticket.ticketNumber || "").replace(
    /^TCKT/i,
    "CONV",
  );

  return (
    <div
      className="rd-hover single-rd-ticket rd-flex rd-align-start rd-gap-8 rd-border-bottom-light rd-cursor-pointer single-rd-active-ticket"
      onClick={() => onSelect(ticket)}
    >
      <div>
        <img
          src={ticket.authorImage}
          alt="User Image"
          className="rd-rounded-border rd-object-cover"
          style={{ height: "30px", width: "30px" }}
        />
      </div>
      <div className="rd-flex rd-justify-between rd-w-full">
        <div className="rd-message-list-wrap">
          <h5
            className={`sub-text rd-title-color ${
              showNotification ? "rd-title-with-dot" : ""
            }`}
          >
            {ticket.title}
          </h5>
          <div className="rd-chat-info">
            <div className="rd-text-small rd-flex rd-gap-5">
              <span className="rd-flex rd-align-center">
                {ticket.source} <LuDot /> {ticket.timeAgo}
              </span>
            </div>
            <div className="rd-flex rd-align-center rd-gap-10">
              <span
                className="rd-priority rd-fw-medium"
                style={{ backgroundColor: priorityColor, color: "#ffffff" }}
              >
                {ticket.priority}
              </span>
              <span className="rd-text-small rd-fw-medium">
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

  const image_urls = window.robodeskData?.imageUrls || {};
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
    const nonce = window.robodeskAuth?.restNonce || window.wpApiSettings?.nonce || '';
    return `wp-json/robodesk/v1/stream/tickets/0/${firstMessageId}/${loggedInUsrId}/?ticket=1&_wpnonce=${nonce}`;
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
          const matchingTerm = window.robodeskAuth?.priorityTerms?.find(
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
    <div className="rd-main-content">
      {isLoggedIn ? (
        <>
          {selectedTicket ? (
            <Suspense
              fallback={
                <img src={Preloader} alt="Loading" className="rd-preloader" />
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
              <div className="rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col rd-message-list-header">
                <button
                  className="back-button rd-flex rd-items-center rd-gap-1 rd-border-none rd-bg-transparent rd-m-0 rd-p-0"
                  onClick={handleBackToHome}
                >
                  <FaChevronLeft />
                </button>
                {image_urls.wide_logo && image_urls.wide_logo.trim() !== "" ? (
                  <img
                    src={image_urls.wide_logo}
                    alt="Plugin Wide Logo"
                    className="rd-header-logo rd-m-auto"
                    onError={(e) => {
                      e.target.style.display = "none";
                    }}
                  />
                ) : image_urls.logo && image_urls.logo.trim() !== "" ? (
                  <img
                    src={image_urls.logo}
                    alt="Plugin Logo"
                    className=" rd-header-logo rd-m-auto"
                    onError={(e) => {
                      e.target.style.display = "none";
                    }}
                  />
                ) : (
                  <div className=" rd-header-logo default-logo">
                    <img
                      src={defaultLogo}
                      alt="Default Plugin Logo"
                      className=" rd-header-logo rd-m-auto"
                    />
                  </div>
                )}
              </div>
              <div className="rd-message-tab-content">
                <div className="rd-p-16">
                  <h4 className="heading-4 rd-text-center">Conversations</h4>
                  <div className="rd-body-text rd-mt-8 rd-text-center ">
                    <p>All your conversations are here.</p>
                  </div>
                </div>

                {/* Search and Priority Filter */}

                <div className="rd-p-16 rd-py-10 rd-flex rd-gap-10 rd-align-center rd-justify-between">
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
                    className="rd-border-light rd-border-radius-md rd-p-10 rd-color-gray rd-fw-medium flex-2"
                  />
                  <select
                    id="priority-filter"
                    value={priorityFilter}
                    onChange={(e) => {
                      const nextPriority = e.target.value;
                      setPriorityFilter(nextPriority);
                      handleSearch(searchTerm, nextPriority, currentTab);
                    }}
                    className="rd-border-light rd-p-10 rd-body-text rd-border-radius-md rd-color-gray rd-fw-medium flex-1"
                  >
                    <option value="all">All</option>
                    {robodeskAuth?.priorityTerms?.map((priority) => (
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
                    className="rd-p-10 rd-py-5 rd-border-light rd-border-radius-md rd-body-text ticket-tab rd-color-gray rd-fw-medium"
                  >
                    {Object.entries(robodeskAuth?.status).map(([key, label]) => (
                      <option key={key} value={key}>
                        {key === "active-conversations" ? "Active" : label}
                      </option>
                    ))}
                  </select>
                </div>

                {/* Ticket List */}
                <div
                  className="rd-messge-wrapper"
                  ref={messageWrapperRef}
                  onScroll={handleScroll}
                >
                  <div className="rd-tickets">
                    <div className="rd-active-tickets-wrap">
                      {loadingContext ? (
                        <img
                          src={Preloader}
                          alt="Loading"
                          className="rd-preloader"
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
                        <p className="rd-text-center rd-text-gray rd-m-5">
                          No tickets found
                        </p>
                      )}
                      {isLoadingMore && (
                        <div className="rd-text-center rd-py-10">
                          <img src={Preloader} alt="Loading" className="rd-preloader" />
                        </div>
                      )}
                    </div>
                  </div>
                  <div className="rd-send-message rd-text-center rd-px-18 rd-py-15 rd-flex rd-justify-center">
                    <button
                      className="button no-bg rd-flex rd-align-center rd-gap-10 rd-cursor-pointer"
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
