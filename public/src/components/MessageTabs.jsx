import React, { useState, useEffect, Suspense, useRef, useMemo } from 'react';
import { FaChevronRight, FaChevronLeft } from 'react-icons/fa';
import '../styles/MessageTabs.css';
import Author1 from '../images/person1.jpeg';

import { LuDot } from 'react-icons/lu';
import { RiPencilFill } from 'react-icons/ri';
import Footer from './Footer';
import Login from './Login';
import Preloader from '../images/preloader.svg';

import { useAppData } from '../utils/AppDataContext';
import defaultLogo from '../images/robodesk-logo.png';

import useExclusiveServerEvents from '../Hooks/useExclusiveServerEvents';

const SingleTicket = React.lazy(() => import('./SingleTicket'));

const TicketItem = ({ ticket, onSelect }) => {
  const showRedDot = ticket.notifications === 'no' ? true : false;

  return (
    <div
      className='rd-hover single-rd-ticket rd-flex rd-align-start rd-gap-8 rd-border-bottom-light rd-cursor-pointer single-rd-active-ticket'
      onClick={() => onSelect(ticket)}
    >
      <div>
        <img
          src={ticket.authorImage}
          alt='User Image'
          className='rd-rounded-border rd-object-cover'
          style={{ height: '30px', width: '30px' }}
        />
      </div>
      <div className='rd-flex rd-justify-between rd-w-full'>
        <div className='rd-message-list-wrap'>
          <h5
            className={`sub-text rd-title-color ${
              showRedDot ? 'rd-title-with-dot' : ''
            }`}
          >
            {ticket.title}
          </h5>
          <div className='rd-text-small rd-mt-5 rd-flex rd-gap-5'>
            <span className='rd-flex'>
              {ticket.source} <LuDot /> {ticket.timeAgo}
            </span>
          </div>
          <div className='rd-flex rd-align-center rd-gap-10 rd-mt-10'>
            <span
              className={`rd-priority rd-fw-medium ${
                ticket.priority === 'Urgent'
                  ? 'rd-urgent'
                  : ticket.priority === 'Medium'
                  ? 'rd-medium'
                  : 'rd-low'
              }`}
            >
              {ticket.priority}
            </span>
            <span className='rd-text-small rd-fw-medium'>
              (#{ticket.ticketNumber})
            </span>
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
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedTicket, setSelectedTicket] = useState(selectedNewTicket);
  const [currentTab, setCurrentTab] = useState('all');
  const [priorityFilter, setPriorityFilter] = useState('all');
  const [tickets, setTickets] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [loggedInuserId, setloggedInuserId] = useState(null);
  const {
    loadingContext,
    errorContext,
    ticketsContext,
    loggedinContext,
    loggedInUsrId,
    refreshTickets,
    updateTicket,
    updateNewCommentNotification,
  } = useAppData();
  const image_urls = window.robodeskData?.imageUrls || {};
  const messageWrapperRef = useRef(null);
  const { connectExclusive, disconnect } = useExclusiveServerEvents();
  const [refresh, setRefresh] = useState(false);
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
        lastReply: ticket.ticket_last_reply,
        totalComments: ticket.ticket_total_comments,
        notifications: ticket.ticket_notifications,
        comments: ticket.ticket_comments,
      }));

      setTickets(mappedTickets);
    } catch (error) {
    } finally {
      setLoading(false);
    }
  };
  const firstMessageId = useMemo(() => {
    if (!tickets || tickets.length === 0) return false;

    return tickets[0].id;
  }, [tickets]);

  useEffect(() => {
    // console.log('The first message id', firstMessageId);

    if (loggedinContext && loggedInUsrId && firstMessageId) {
      connectExclusive(
        'tickets',
        {
          postId: 0,
          lastId: firstMessageId,
          userId: loggedInUsrId,
        },
        {
          new_ticket: (data) => {
            const ticketsArray = Array.isArray(data) ? data : [data];

            const mappedTickets = ticketsArray.map((ticket) => ({
              id: ticket.id,
              ticketNumber: ticket.ticket_number,
              title: ticket.ticket_title,
              source: ticket.ticket_origin,
              timeAgo: ticket.ticket_time_ago,
              authorImage: ticket.ticket_author_image ?? Author1,
              status: ticket.ticket_status ?? 'New',
              priority: ticket.ticket_priority ?? 'Medium',
              author: ticket.ticket_author,
              lastReply: ticket.ticket_last_reply,
              notifications: ticket.ticket_notifications,
              totalComments: ticket.ticket_total_comments,
              comments: ticket.ticket_comments,
            }));

            updateTicket(data);

            setTickets((prevTickets) => {
              const newTickets = mappedTickets.filter(
                (mappedTicket) =>
                  !prevTickets.some(
                    (prevTicket) => prevTicket.id === mappedTicket.id
                  )
              );

              return [...newTickets, ...prevTickets];
            });
          },
          new_notification: (data) => {
            if (data) updateNewCommentNotification(data);
            console.log('data', data);
            setTickets((prevTickets) =>
              prevTickets.map((ticket) =>
                data.includes(ticket.id) && ticket.notifications === 'yes'
                  ? { ...ticket, notifications: 'no' }
                  : ticket
              )
            );

            console.log('after update', tickets);
          },
        }
      );

      return () => {};
    }
  }, [tickets]);

  // useEffect(() => {
  //     if(refresh){
  //         refreshTickets();
  //     }

  // },[refresh])

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
    // sessionStorage.setItem('activate_text', 'true');
    setActiveTab(0);
  };

  const handleScroll = (e) => {
    e.preventDefault();
    const wrapper = messageWrapperRef.current;
    if (!wrapper) return;

    const { scrollTop, scrollHeight, clientHeight } = wrapper;
    // console.log('Scroll Top:', scrollTop, 'Client Height:', clientHeight, 'Scroll Height:', scrollHeight);
    if (scrollTop + clientHeight >= scrollHeight) {
      console.log('now');
    }
  };

  const filteredTickets =
    Array.isArray(tickets) && tickets.length > 0
      ? tickets.filter((ticket) => {
          const matchesTab =
            currentTab === 'all' || ticket.status === currentTab;
          const matchesPriority =
            priorityFilter === 'all' || ticket.priority === priorityFilter;

          const searchLower = searchTerm.toLowerCase();
          const titleLower = ticket.title?.toLowerCase() || '';
          const ticketNumberLower = ticket.ticketNumber?.toLowerCase() || '';

          const matchesSearch =
            titleLower.includes(searchLower) ||
            ticketNumberLower.includes(searchLower);

          return matchesTab && matchesPriority && matchesSearch;
        })
      : [];

  return (
    <div className='rd-main-content'>
      {isLoggedIn ? (
        <>
          {selectedTicket ? (
            <Suspense
              fallback={
                <img src={Preloader} alt='Loading' className='rd-preloader' />
              }
            >
              <SingleTicket
                ticket={selectedNewTicket ? selectedNewTicket : selectedTicket}
                onBack={() => setSelectedTicket(null)}
                loggedInUserId={loggedInuserId}
                setActiveTab={setActiveTab}
                handleFocusedTextArea={handleFocusedTextArea}
              />
            </Suspense>
          ) : (
            <>
              <div className='rd-px-18 rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col rd-message-list-header'>
                <button
                  className='back-button rd-flex rd-items-center rd-gap-1 rd-border-none rd-bg-transparent rd-m-0 rd-p-0'
                  onClick={handleBackToHome}
                >
                  <FaChevronLeft />
                </button>
                {image_urls.wide_logo && image_urls.wide_logo.trim() !== '' ? (
                  <img
                    src={image_urls.wide_logo}
                    alt='Plugin Wide Logo'
                    className='rd-h-auto rd-header-logo rd-m-auto'
                    onError={(e) => {
                      e.target.style.display = 'none';
                    }}
                  />
                ) : image_urls.logo && image_urls.logo.trim() !== '' ? (
                  <img
                    src={image_urls.logo}
                    alt='Plugin Logo'
                    className='rd-h-auto rd-header-logo rd-m-auto'
                    onError={(e) => {
                      e.target.style.display = 'none';
                    }}
                  />
                ) : (
                  <div className='rd-h-auto rd-header-logo default-logo'>
                    <img
                      src={defaultLogo}
                      alt='Default Plugin Logo'
                      className='rd-h-auto rd-header-logo rd-m-auto'
                    />
                  </div>
                )}
              </div>
              <div className='rd-message-tab-content'>
                <div className='rd-mt-10 rd-mb-10'>
                  <h4 className='heading-4 rd-text-center'>Conversations</h4>
                  <div className='rd-body-text rd-mt-8 rd-text-center '>
                    <p>All your conversations are here.</p>
                  </div>
                </div>

                {/* Search and Priority Filter */}
                <div className='rd-px-18 rd-py-10 rd-flex rd-gap-10 rd-align-center rd-justify-between'>
                  <input
                    type='text'
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    placeholder='Search for conversation'
                    className='rd-border-light rd-border-radius-md rd-p-10 rd-color-gray rd-fw-medium flex-2'
                  />
                  <select
                    value={priorityFilter}
                    onChange={(e) => setPriorityFilter(e.target.value)}
                    className='rd-border-light rd-p-10 rd-body-text rd-border-radius-md rd-color-gray rd-fw-medium flex-1'
                  >
                    <option value='all'>All Priorities</option>
                    <option value='Urgent'>High</option>
                    <option value='Medium'>Medium</option>
                    <option value='Low'>Low</option>
                  </select>
                </div>

                {/* Tabs */}
                <div className='rd-flex rd-justify-between rd-py-10 rd-px-20 rd-gap-10 rd-border-bottom-light rd-pt-0'>
                  <select
                    value={currentTab}
                    onChange={(e) => setCurrentTab(e.target.value)}
                    className='rd-p-10 rd-py-5 rd-border-light rd-border-radius-md rd-body-text ticket-tab rd-color-gray rd-fw-medium'
                  >
                    <option value='all'>Active Conversations</option>
                    <option value='new'>New</option>
                    <option value='ongoing'>On Going</option>
                    <option value='resolved'>Resolved</option>
                  </select>
                </div>

                {/* Ticket List */}
                <div
                  className='rd-messge-wrapper'
                  ref={messageWrapperRef}
                  onScroll={handleScroll}
                >
                  <div className='rd-tickets'>
                    <div className='rd-active-tickets-wrap'>
                      {loadingContext ? (
                        <img
                          src={Preloader}
                          alt='Loading'
                          className='rd-preloader'
                        />
                      ) : filteredTickets.length > 0 ? (
                        filteredTickets.map((ticket) => (
                          <TicketItem
                            key={ticket.id}
                            ticket={ticket}
                            onSelect={setSelectedTicket}
                            setActiveTab={setActiveTab}
                          />
                        ))
                      ) : (
                        <p className='rd-text-center rd-text-gray rd-m-5 err_msg'>
                          No tickets found
                        </p>
                      )}
                    </div>
                  </div>
                  <div className='rd-send-message rd-text-center rd-px-18 rd-py-15 rd-flex rd-justify-center'>
                    <button
                      className='button no-bg rd-flex rd-align-center rd-gap-10 rd-cursor-pointer'
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
