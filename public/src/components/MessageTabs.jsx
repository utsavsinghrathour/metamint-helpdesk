// src/components/MessageTabs.jsx
import React, { useState, Suspense } from 'react';
import { FaChevronRight, FaChevronLeft, FaSearch } from 'react-icons/fa';
import '../styles/MessageTabs.css';
import Author1 from '../images/person1.jpeg';
import Author2 from '../images/person2.jpeg';
import { LuDot } from 'react-icons/lu';
import { IoIosSend } from 'react-icons/io';
import Footer from './Footer';

const SingleTicket = React.lazy(() => import('./SingleTicket'));

// Ticket Item Component
const TicketItem = ({ ticket, onSelect }) => {
  console.log('Rendering TicketItem:', ticket.title);
  return (
    <div
      className="single-rd-ticket rd-flex rd-align-center rd-gap-8 rd-border-bottom-light rd-cursor-pointer single-rd-active-ticket"
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
          <div className="rd-text-small rd-mt-5 rd-flex">
            {ticket.source} <LuDot /> {ticket.timeAgo}
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

  const tickets = [
    {
      id: 1,
      title: 'How can we assist you today?',
      source: 'White Label WP',
      timeAgo: '4d ago',
      authorImage: Author1,
      status: 'active',
    },
    {
      id: 2,
      title: 'Website down issue resolved',
      source: 'White Label WP',
      timeAgo: '5d ago',
      authorImage: Author2,
      status: 'closed',
    },
  ];

  const handleBackToHome = () => {
    console.log('handleBackToHome called');
    if (setActiveTab) {
      setActiveTab(0);
      setIsExpanded(false);
    }
  };

  console.log('MessageTabs Render - selectedTicket:', selectedTicket);

  return (
    <div className="rd-message-tab">
      {selectedTicket ? (
        <Suspense fallback={<div>Loading ticket...</div>}>
          <SingleTicket ticket={selectedTicket} onBack={() => setSelectedTicket(null)} />
          {/* <Footer activeTab={activeTab} setActiveTab={setActiveTab} showCopyrightOnly={true} /> */}
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
            <div className="rd-messge-wrapper">
              <div className="rd-active-tickets rd-tickets">
                <h5 className="sub-text text-black single-ticket-title">Active Tickets</h5>
                <div className="rd-active-tickets-wrap">
                  {tickets
                    .filter(
                      (ticket) =>
                        ticket.status === 'active' &&
                        ticket.title.toLowerCase().includes(searchTerm.toLowerCase())
                    )
                    .map((ticket) => (
                      <TicketItem key={ticket.id} ticket={ticket} onSelect={setSelectedTicket} />
                    ))}
                </div>
              </div>
              <div className="rd-closed-tickets rd-tickets">
                <h5 className="sub-text text-black single-ticket-title">Closed Tickets</h5>
                <div className="rd-active-tickets-wrap">
                  {tickets
                    .filter(
                      (ticket) =>
                        ticket.status === 'closed' &&
                        ticket.title.toLowerCase().includes(searchTerm.toLowerCase())
                    )
                    .map((ticket) => (
                      <TicketItem key={ticket.id} ticket={ticket} onSelect={setSelectedTicket} />
                    ))}
                </div>
              </div>
            </div>
            <div className="rd-send-message rd-text-center">
              <a href="#" className="button no-bg rd-flex rd-align-center rd-gap-10">
                Send Message
                <IoIosSend />
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