import React, { useState, useEffect, Suspense } from 'react';
import { FaChevronRight, FaChevronLeft } from 'react-icons/fa';
import '../styles/MessageTabs.css';
import Author1 from '../images/person1.jpeg';
import Author2 from '../images/person2.jpeg';
import { LuDot } from 'react-icons/lu';
import { RiPencilFill } from 'react-icons/ri';
import Footer from './Footer';
import Login from './Login';
import Preloader from '../images/preloader.svg';
import { getAllTickets } from '../utils/modules';
import { useAppData } from '../utils/AppDataContext';
import defaultLogo from '../images/robodesk-logo.png';
// import CreateTicket from './CreateTicket'

const SingleTicket = React.lazy(() => import('./SingleTicket'));

const TicketItem = ({ ticket, onSelect }) => {


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
          style={{ height: '30px', width: '30px' }}
        />
      </div>
      <div className="rd-flex rd-justify-between rd-w-full">
        <div className="rd-message-list-wrap">
          <h5 className="sub-text rd-title-color">{ticket.title} </h5>
          <div className="rd-text-small rd-mt-5 rd-flex rd-gap-5">
            <span className="rd-flex">
              {ticket.source} <LuDot /> {ticket.timeAgo}
            </span>
          </div>
          <div className="rd-flex rd-align-center rd-gap-10 rd-mt-10">
            <span
              className={`rd-priority rd-fw-medium ${
                ticket.priority === 'Urgent' ? 'rd-urgent' : ticket.priority === 'Medium' ? 'rd-medium' : 'rd-low'
              }`}
            >
              {ticket.priority}
            </span>
            <span className="rd-text-small rd-fw-medium">(#{ticket.ticketNumber})</span>
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
}) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedTicket, setSelectedTicket] = useState(selectedNewTicket);
  const [currentTab, setCurrentTab] = useState('all');
  const [priorityFilter, setPriorityFilter] = useState('all');
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isLoggedIn, setIsLoggedIn] = useState(false); // Fixed naming consistency
  const [loggedInuserId, setloggedInuserId] = useState(null);
  const { loadingContext, errorContext, ticketsContext, loggedinContext, loggedInUsrId } = useAppData();
  const image_urls = window.robodeskData?.imageUrls || {};

  const fetchTickets = async () => {
    if (!loggedInUsrId) {
      return;
    }
    try {
      setLoading(true);
      const tickets = ticketsContext;
      const mappedTickets = tickets.map((ticket) => ({
        id: ticket.id,
        ticketNumber: ticket.ticket_number,
        title: ticket.ticket_title,
        source: ticket.ticket_origin,
        timeAgo: ticket.ticket_time_ago + ' ago',
        authorImage: ticket.ticket_author_image ?? Author1,
        status: ticket.ticket_status ?? 'New',
        priority: ticket.ticket_priority ?? 'Medium',
        author: ticket.ticket_author,
        lastReply : ticket.ticket_last_reply,
        totalComments: ticket.ticket_total_comments,
        comments : ticket.ticket_comments,
        chukul : 'sukul',
      }));

      setTickets(mappedTickets);

    } catch (error) {
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (loggedinContext && loggedInUsrId) {
      setIsLoggedIn(true);
      setloggedInuserId(loggedInUsrId);

      fetchTickets();

      if (!selectedTicket) {
        setSelectedTicket(selectedNewTicket ? selectedNewTicket : null);
      }
    } else {
      setTickets([]);
      setLoading(false);
    }
  }, [isLoggedIn, loggedInUsrId, ticketsContext]);

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
  };

  const filteredTickets = tickets.filter((ticket) => {
    const matchesTab = currentTab === 'all' || ticket.status === currentTab;
    const matchesPriority = priorityFilter === 'all' || ticket.priority === priorityFilter;

    const searchLower = searchTerm.toLowerCase();
    const titleLower = ticket.title?.toLowerCase() || '';
    const ticketNumberLower = ticket.ticketNumber?.toLowerCase() || '';

    const matchesSearch = titleLower.includes(searchLower) || ticketNumberLower.includes(searchLower);

    return matchesTab && matchesPriority && matchesSearch;
  });

  return (
    <div className="rd-main-content">
      {isLoggedIn ? (
        <>
          {selectedTicket ? (
            <Suspense fallback={<img src={Preloader} alt="Loading" className="rd-preloader" />}>
              <SingleTicket
                ticket={selectedNewTicket ? selectedNewTicket : selectedTicket}
                onBack={() => setSelectedTicket(null)}
                loggedInUserId={loggedInuserId}
              />
            </Suspense>
          ) : (
            <>
              <div className="rd-px-18 rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col rd-message-list-header">
                
                <button
                  className="back-button rd-flex rd-items-center rd-gap-1 rd-border-none rd-bg-transparent rd-m-0 rd-p-0"
                  onClick={handleBackToHome}
                >
                  <FaChevronLeft />
                </button>
                {image_urls.wide_logo && image_urls.wide_logo.trim() !== '' ? (
                  <img
                    src={image_urls.wide_logo}
                    alt="Plugin Wide Logo"
                    className="rd-h-auto rd-header-logo rd-m-auto"
                    onError={(e) => {
                      e.target.style.display = 'none';
                    }}
                  />
                ) : image_urls.logo && image_urls.logo.trim() !== '' ? (
                  <img
                    src={image_urls.logo}
                    alt="Plugin Logo"
                    className="rd-h-auto rd-header-logo rd-m-auto"
                    onError={(e) => {
                      e.target.style.display = 'none';
                    }}
                  />
                ) : (
                  <div className="rd-h-auto rd-header-logo default-logo">
                    <img src={defaultLogo} alt="Default Plugin Logo" className="rd-h-auto rd-header-logo rd-m-auto" />
                  </div>
                )}
              </div>
              <div className="rd-message-tab-content">
                <div className="rd-mt-20 rd-mb-10">
                  <h4 className="heading-4 rd-text-center">Conversations</h4>
                  <div className="rd-body-text rd-mt-8 rd-text-center ">
                    <p>All your conversations are here.</p>
                  </div>
                </div>

                {/* Search and Priority Filter */}
                <div className="rd-px-18 rd-py-15 rd-flex rd-gap-10 rd-align-center rd-justify-between">
                  <input
                    type="text"
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    placeholder="Search for conversation"
                    className="rd-border-light rd-border-radius-md rd-p-10 rd-color-gray rd-fw-medium flex-2"
                  />
                  <select
                    value={priorityFilter}
                    onChange={(e) => setPriorityFilter(e.target.value)}
                    className="rd-border-light rd-p-10 rd-body-text rd-border-radius-md rd-color-gray rd-fw-medium flex-1"
                  >
                    <option value="all">All Priorities</option>
                    <option value="Urgent">Urgent</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                  </select>
                </div>

                {/* Tabs */}
                <div className="rd-flex rd-justify-between rd-py-10 rd-px-20 rd-gap-10 rd-border-bottom-light">
                  <select
                    value={currentTab}
                    onChange={(e) => setCurrentTab(e.target.value)}
                    className="rd-p-10 rd-py-5 rd-border-light rd-border-radius-md rd-body-text ticket-tab rd-color-gray rd-fw-medium"
                  >
                    <option value="all">All Conversations</option>
                    <option value="new">New</option>
                    <option value="ongoing">On Going</option>
                    <option value="resolved">Resolved</option>
                  </select>
                  <button
                    className={`rd-hover-underline rd-p-0 rd-cursor-pointer rd-flex rd-gap-8 rd-align-center rd-color-gray rd-title-text ticket-tab rd-px-10 rd-py-5 rd-bg-transparent rd-border-none ${
                      currentTab === "new"
                        ? "rd-text-blue rd-font-bold active-ticket-tab"
                        : "rd-text-gray"
                    }`}
                    onClick={() => setCurrentTab("new")}
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="18"
                      height="18"
                      viewBox="0 0 21 20"
                      fill="none"
                    >
                      <path
                        d="M19.0448 8.75V12.9167C19.0448 15.8333 17.3738 17.0833 14.8673 17.0833H6.51223C4.00572 17.0833 2.33472 15.8333 2.33472 12.9167V7.08333C2.33472 4.16666 4.00572 2.91666 6.51223 2.91666H12.3608"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                      <path
                        d="M6.51221 7.5L9.12734 9.58333C9.9879 10.2667 11.3999 10.2667 12.2605 9.58333L13.2464 8.8"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                      <path
                        d="M16.956 6.66667C18.1096 6.66667 19.0448 5.73393 19.0448 4.58333C19.0448 3.43274 18.1096 2.5 16.956 2.5C15.8024 2.5 14.8672 3.43274 14.8672 4.58333C14.8672 5.73393 15.8024 6.66667 16.956 6.66667Z"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                    </svg>
                    New
                  </button>
                  <button
                    className={`rd-hover-underline rd-p-0 rd-cursor-pointer ticket-tab rd-flex rd-gap-8 rd-align-center rd-color-gray rd-title-text rd-px-10 rd-py-5 rd-bg-transparent rd-border-none ${
                      currentTab === "ongoing"
                        ? "rd-text-blue rd-font-bold active-ticket-tab"
                        : "rd-text-gray"
                    }`}
                    onClick={() => setCurrentTab("ongoing")}
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      width="18"
                      height="18"
                      viewBox="0 0 21 20"
                      fill="none"
                    >
                      <path
                        d="M1.99854 7.08333C1.99854 4.16666 3.66954 2.91666 6.17605 2.91666H14.5311C17.0376 2.91666 18.7086 4.16666 18.7086 7.08333V12.9167C18.7086 15.8333 17.0376 17.0833 14.5311 17.0833H6.17605"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                      <path
                        d="M14.5311 7.5L11.9159 9.58333C11.0554 10.2667 9.64336 10.2667 8.78279 9.58333L6.17603 7.5"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                      <path
                        d="M1.99854 13.75H7.01155"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                      <path
                        d="M1.99854 10.4167H4.50504"
                        stroke="#84818A"
                        strokeWidth="1.5"
                        strokeMiterlimit="10"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                      />
                    </svg>
                    On Going
                  </button>
                </div>

                {/* Ticket List */}
                <div className="rd-messge-wrapper">
                  <div className="rd-tickets">
                    <div className="rd-active-tickets-wrap">
                      {loading ? (
                        <img src={Preloader} alt="Loading" className="rd-preloader" />
                      ) : filteredTickets.length > 0 ? (
                        filteredTickets.map((ticket) => (
                          <TicketItem key={ticket.id} ticket={ticket} onSelect={setSelectedTicket} />
                        ))
                      ) : (
                        <p className="rd-text-center rd-text-gray rd-m-5 err_msg">No tickets found</p>
                      )}
                    </div>
                  </div>
                </div>

                <div className="rd-send-message rd-text-center rd-px-18 rd-py-15 rd-flex rd-justify-center">
                  <button
                    className="button no-bg rd-flex rd-align-center rd-gap-10 rd-cursor-pointer"
                    onClick={handleAddTicket}
                  >
                    Start a conversation
                    <RiPencilFill />
                  </button>
                </div>
              </div>
              {/* <Footer activeTab={activeTab} setActiveTab={setActiveTab} /> */}
              <Footer activeTab={activeTab} setActiveTab={setActiveTab} updateSelectedTicket={updateSelectedTicket} />
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
