import React, { useState, Suspense } from 'react';
import { FaChevronRight, FaChevronLeft } from 'react-icons/fa';
import '../styles/MessageTabs.css';
import Author1 from '../images/person1.jpeg';
import Author2 from '../images/person2.jpeg';
import { LuDot } from 'react-icons/lu';
import { IoIosSend } from 'react-icons/io';
import Footer from './Footer';
import { RiPencilFill } from "react-icons/ri";

const SingleTicket = React.lazy(() => import('./SingleTicket'));

const TicketItem = ({ ticket, onSelect }) => {
  console.log('Rendering TicketItem:', ticket.title);
  return (
    <div
      className="rd-hover single-rd-ticket rd-flex rd-align-center rd-gap-8 rd-border-bottom-light rd-cursor-pointer single-rd-active-ticket"
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
        <div>
          <h5 className="sub-text rd-title-color">{ticket.title}</h5>
          <div className="rd-text-small rd-mt-5 rd-flex rd-gap-5">
            <span className='rd-flex'>{ticket.source} <LuDot /> {ticket.timeAgo}</span>
          </div>
          <div className='rd-flex rd-align-center rd-gap-10 rd-mt-10'>
            <span className={` rd-priority rd-fw-medium ${
              ticket.priority === 'Urgent' ? 'rd-urgent' : 
              ticket.priority === 'Medium' ? 'rd-medium' : 'rd-low'
            }`}>
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

const MessageTabs = ({ setActiveTab, setIsOpen, isExpanded, setIsExpanded, activeTab }) => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedTicket, setSelectedTicket] = useState(null);
  const [currentTab, setCurrentTab] = useState('all');
  const [priorityFilter, setPriorityFilter] = useState('all');

  const tickets = [
    // New Tickets
    {
      id: 1,
      ticketNumber: 'TCKT-001',
      title: 'How can we assist you today?',
      source: 'White Label WP',
      timeAgo: '4d ago',
      authorImage: Author1,
      status: 'new',
      priority: 'Medium',
      messages: [
        { id: 1, text: 'Hi, how can I help you today?', sender: 'other', image: Author1 },
        { id: 2, text: 'I need assistance with my website.', sender: 'me' },
      ],
    },
    {
      id: 2,
      ticketNumber: 'TCKT-002',
      title: 'Payment gateway not working',
      source: 'White Label WP',
      timeAgo: '2d ago',
      authorImage: Author2,
      status: 'new',
      priority: 'Urgent',
      messages: [
        { id: 1, text: 'My payment gateway is down.', sender: 'me' },
        { id: 2, text: 'We’re looking into it, please wait.', sender: 'other', image: Author2 },
      ],
    },

    // On Going Tickets
    {
      id: 3,
      ticketNumber: 'TCKT-003',
      title: 'Need help with SEO',
      source: 'White Label WP',
      timeAgo: '1d ago',
      authorImage: Author1,
      status: 'ongoing',
      priority: 'Low',
      messages: [
        { id: 1, text: 'Can you improve my site’s SEO?', sender: 'me' },
        { id: 2, text: 'Yes, let’s discuss your goals.', sender: 'other', image: Author1 },
      ],
    },
    {
      id: 4,
      ticketNumber: 'TCKT-004',
      title: 'Plugin installation issue',
      source: 'White Label WP',
      timeAgo: '3h ago',
      authorImage: Author2,
      status: 'ongoing',
      priority: 'Medium',
      messages: [
        { id: 1, text: 'The plugin won’t install.', sender: 'me' },
        { id: 2, text: 'Can you share the error message?', sender: 'other', image: Author2 },
      ],
    },

    // Resolved Tickets
    {
      id: 5,
      ticketNumber: 'TCKT-005',
      title: 'Website down issue resolved',
      source: 'White Label WP',
      timeAgo: '5d ago',
      authorImage: Author2,
      status: 'resolved',
      priority: 'Urgent',
      messages: [
        { id: 1, text: 'My website is down!', sender: 'me' },
        { id: 2, text: 'We’ve fixed the issue, it’s back up.', sender: 'other', image: Author2 },
        { id: 3, text: 'Thanks, it’s working now!', sender: 'me' },
      ],
    },
    {
      id: 6,
      ticketNumber: 'TCKT-006',
      title: 'Billing question answered',
      source: 'White Label WP',
      timeAgo: '1w ago',
      authorImage: Author1,
      status: 'resolved',
      priority: 'Low',
      messages: [
        { id: 1, text: 'Why was I charged twice?', sender: 'me' },
        { id: 2, text: 'It was a glitch, refunded now.', sender: 'other', image: Author1 },
      ],
    },
    {
      id: 7,
      ticketNumber: 'TCKT-007',
      title: 'Theme update completed',
      source: 'White Label WP',
      timeAgo: '2w ago',
      authorImage: Author2,
      status: 'resolved',
      priority: 'Medium',
      messages: [
        { id: 1, text: 'Can you update my theme?', sender: 'me' },
        { id: 2, text: 'Theme updated successfully.', sender: 'other', image: Author2 },
        { id: 3, text: 'Great, thanks!', sender: 'me' },
      ],
    },
  ];

  const handleBackToHome = () => {
    console.log('handleBackToHome called');
    if (setActiveTab) {
      setActiveTab(0);
      setIsExpanded(false);
    }
  };

  const filteredTickets = tickets.filter((ticket) => {
    const matchesTab = currentTab === 'all' || ticket.status === currentTab;
    const matchesPriority = priorityFilter === 'all' || ticket.priority === priorityFilter;
    const matchesSearch = 
      ticket.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      ticket.ticketNumber.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesTab && matchesPriority && matchesSearch;
  });

  console.log('MessageTabs Render - selectedTicket:', selectedTicket);

  return (
    <div className="rd-message-tab">
      {selectedTicket ? (
        <Suspense fallback={<div className='rd-title-text rd-p-10'>Loading ticket...</div>}>
          <SingleTicket ticket={selectedTicket} onBack={() => setSelectedTicket(null)} />
        </Suspense>
      ) : (
        <>
          <div className="rd-px-18 rd-search-header rd-bg-blue rd-flex rd-items-center rd-justify-between rd-flex-col">
            <h5 className="heading-5 rd-text-white rd-text-center">Messages</h5>
            <button
              className="back-button rd-flex rd-items-center rd-gap-1 rd-border-none rd-bg-transparent rd-m-0 rd-p-0"
              onClick={handleBackToHome}
            >
              <FaChevronLeft />
            </button>
          </div>
          <div className="rd-message-tab-content">
            {/* Search and Priority Filter */}
            <div className="rd-px-18 rd-py-15 rd-flex rd-gap-10 rd-align-center rd-justify-center">
              <input
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Search for ticket"
                className="rd-border-light rd-border-radius-md rd-p-10"
              />
              <select
                value={priorityFilter}
                onChange={(e) => setPriorityFilter(e.target.value)}
                className="rd-border-light rd-p-10 rd-body-text rd-border-radius-md"
              >
                <option value="all">All Priorities</option>
                <option value="Urgent">Urgent</option>
                <option value="Medium">Medium</option>
                <option value="Low">Low</option>
              </select>
            </div>

            {/* Tabs */}
            <div className="rd-flex rd-justify-between rd-py-15 rd-px-20 rd-gap-10 rd-border-bottom-light">
              <select
                value={currentTab}
                onChange={(e) => setCurrentTab(e.target.value)}
                className="rd-p-10 rd-py-5 rd-border-light rd-border-radius-md rd-body-text ticket-tab"
              >
                <option value="all">All Tickets</option>
                <option value="new">New</option>
                <option value="ongoing">On Going</option>
                <option value="resolved">Resolved</option>
              </select>
              <button
                className={`rd-p-0 rd-cursor-pointer rd-flex rd-gap-8 rd-align-center rd-color-gray rd-title-text ticket-tab rd-px-10 rd-py-5 rd-bg-transparent rd-border-none ${
                  currentTab === 'new' ? 'rd-text-blue rd-font-bold active-ticket-tab' : 'rd-text-gray'
                }`}
                onClick={() => setCurrentTab('new')}
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 21 20" fill="none">
                  <path
                    d="M19.0448 8.75V12.9167C19.0448 15.8333 17.3738 17.0833 14.8673 17.0833H6.51223C4.00572 17.0833 2.33472 15.8333 2.33472 12.9167V7.08333C2.33472 4.16666 4.00572 2.91666 6.51223 2.91666H12.3608"
                    stroke="#84818A"
                    stroke-width="1.5"
                    stroke-miterlimit="10"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M6.51221 7.5L9.12734 9.58333C9.9879 10.2667 11.3999 10.2667 12.2605 9.58333L13.2464 8.8"
                    stroke="#84818A"
                    stroke-width="1.5"
                    stroke-miterlimit="10"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M16.956 6.66667C18.1096 6.66667 19.0448 5.73393 19.0448 4.58333C19.0448 3.43274 18.1096 2.5 16.956 2.5C15.8024 2.5 14.8672 3.43274 14.8672 4.58333C14.8672 5.73393 15.8024 6.66667 16.956 6.66667Z"
                    stroke="#84818A"
                    stroke-width="1.5"
                    stroke-miterlimit="10"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
                New
              </button>
              <button
                className={`rd-p-0 rd-cursor-pointer ticket-tab rd-flex rd-gap-8 rd-align-center rd-color-gray rd-title-text rd-px-10 rd-py-5 rd-bg-transparent rd-border-none ${
                  currentTab === 'ongoing' ? 'rd-text-blue rd-font-bold active-ticket-tab' : 'rd-text-gray'
                }`}
                onClick={() => setCurrentTab('ongoing')}
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 21 20" fill="none">
                  <path
                    d="M1.99854 7.08333C1.99854 4.16666 3.66954 2.91666 6.17605 2.91666H14.5311C17.0376 2.91666 18.7086 4.16666 18.7086 7.08333V12.9167C18.7086 15.8333 17.0376 17.0833 14.5311 17.0833H6.17605"
                    stroke="#84818A"
                    stroke-width="1.5"
                    stroke-miterlimit="10"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M14.5311 7.5L11.9159 9.58333C11.0554 10.2667 9.64336 10.2667 8.78279 9.58333L6.17603 7.5"
                    stroke="#84818A"
                    stroke-width="1.5"
                    stroke-miterlimit="10"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M1.99854 13.75H7.01155"
                    stroke="#84818A"
                    stroke-width="1.5"
                    stroke-miterlimit="10"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                  <path
                    d="M1.99854 10.4167H4.50504"
                    stroke="#84818A"
                    stroke-width="1.5"
                    stroke-miterlimit="10"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  />
                </svg>
                On Going
              </button>
            </div>

            {/* Ticket List */}
            <div className="rd-messge-wrapper">
              <div className="rd-tickets">
                <div className="rd-active-tickets-wrap">
                  {filteredTickets.length > 0 ? (
                    filteredTickets.map((ticket) => (
                      <TicketItem key={ticket.id} ticket={ticket} onSelect={setSelectedTicket} />
                    ))
                  ) : (
                    <p className="rd-text-center rd-text-gray">No tickets found</p>
                  )}
                </div>
              </div>
            </div>

            <div className="rd-send-message rd-text-center rd-px-18 rd-py-15">
              <a href="#" className="button no-bg rd-flex rd-align-center rd-gap-10">
               Add Ticket
               <RiPencilFill />
              </a>
            </div>
            <Footer activeTab={activeTab} setActiveTab={setActiveTab} />
          </div>
        </>
      )}
    </div>
  );
};

export default MessageTabs;