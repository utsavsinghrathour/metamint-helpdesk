import React, { useState, useEffect } from 'react';
import defaultLogo from '../images/robodesk-logo.png'; // Adjust path to your default logo
import '../styles/header.css';
import { IoAddCircle, IoCloseCircleSharp } from 'react-icons/io5';
import { sendReply } from '../utils/modules';
import { useAppData } from '../utils/AppDataContext';
import { createTicket } from "../utils/modules";

const Header = ({ logo, onClose, setActiveTab, setShowLogin, updateSelectedTicket }) => {
  const [talkToHuman, setTalkToHuman] = useState(false);
  const CHAT_KEY = "robodesk_chat_session";
  const [selectedStaff, setSelectedStaff] = useState(null);
  const { loggedinContext, loggedInUsrId, refreshTickets } = useAppData();

  const handleCloseClick = () => {
    onClose();
  };

  const fetchApiKey = async () => {
    try {
      const response = await fetch("/wp-json/robodesk-bot/v1/widget-config");
      const data = await response.json();
      if (response.ok) {
        setTalkToHuman(!!data?.talkToHuman);
      } else {
        setTalkToHuman(false);
      }
    } catch (error) {
      console.error("Error fetching widget config:", error);
      setTalkToHuman(false);
    }
  };

  const fetchDefaultStaff = async () => {
    try {
      const response = await fetch("/wp-json/robodesk/selected/staff");
      const data = await response.json();
      console.log('API Response:', response.status, data);

      if (response.ok && data?.staff_user_id) {
        console.log('Setting staff ID:', data.staff_user_id);
        setSelectedStaff(data.staff_user_id);
      } else {
        console.log('No default staff configured or error:', data);
        setSelectedStaff(null);
      }
    } catch (error) {
      console.error("Error fetching default staff:", error);
      setSelectedStaff(null);
    }
  };


  const handleTalkToHumanClick = async () => {
    // Retrieve chat messages from session storage
    const saved = sessionStorage.getItem(CHAT_KEY);
    const messages = saved ? JSON.parse(saved) : [];
    const messagesExceptFirst = messages.slice(1);

    // If no messages, return early
    if (!messages || messages.length === 0) {
      return;
    }

    // Get user messages (messages with role 'user')
    const userMessages = messages.filter(msg => msg.role === 'user');
    console.log('Filtered user messages:', userMessages);

    if (userMessages.length === 0) {
      console.log('No user messages found');
      return;
    }

    // First user message becomes the ticket title
    const ticketTitle = userMessages[0].text;
    console.log('Ticket title:', ticketTitle);

    // All user messages become the ticket description/content
    const ticketDescription = userMessages.map(msg => msg.text).join('\n\n');
    console.log('Ticket description:', ticketDescription);

    // Check if user is logged in
    if (!loggedinContext) {
      localStorage.setItem('temp_ticket_title', ticketTitle);
      setActiveTab(1);
      setShowLogin(true);
      return;
    }

    try {
      // Create ticket with title and description
      const result = await createTicket(loggedInUsrId, ticketTitle, ticketDescription);

      const newTicket = {
        id: result.data.id,
        ticketNumber: result.data.ticket_number,
        title: result.data.ticket_title,
        source: result.data.ticket_origin,
        timeAgo: result.data.ticket_time_ago,
        authorImage: result.data.ticket_author_image,
        status: result.data.ticket_status,
        priority: result.data.ticket_priority,
        messages: [],
      };

      refreshTickets();
      updateSelectedTicket(newTicket);
      setActiveTab(1);


      if (messagesExceptFirst.length > 0) {
        messagesExceptFirst.forEach((message) => {
          if (message.role === 'user') {
            sendReply(newTicket.id, loggedInUsrId, message.text);
          } else if (message.role === 'assistant') {
            sendReply(newTicket.id, selectedStaff, message.message);
          }
        });
      }


      // Clear session storage after successful ticket creation
      sessionStorage.removeItem(CHAT_KEY);

    } catch (error) {
      console.error("Failed to create ticket:", error);
    }
  };


  useEffect(() => {
    fetchDefaultStaff();
    fetchApiKey();
  }, []);

  return (
    <header className="robodesk-header rd-flex rd-justify-between">
      <div className="rd-flex rd-items-center">
        {logo && logo.trim() !== '' ? (
          <img
            src={logo}
            alt="Plugin Logo"
            className="rd-h-auto rd-header-logo rd-main-header-logo"
            onError={(e) => {
              e.target.style.display = 'none';
            }}
          />
        ) : (
          <div className="rd-h-auto rd-header-logo default">
            <img
              src={defaultLogo}
              alt="Default Plugin Logo"
              className="rd-h-auto rd-header-logo rd-main-header-logo"
            />
          </div>
        )}
        {talkToHuman && (
          <button
            className="rd-m-5"
            onClick={handleTalkToHumanClick}
          >
            <div className="icon-dark custom-svg rd-cursor-pointer">
              <IoAddCircle />
            </div>
          </button>
        )}
      </div>
      <button
        className="robodesk-close-btn rd-m-5 rd-border-none rd-bg-transparent rd-p-0"
        onClick={handleCloseClick}
      >
        <div className="icon-dark custom-svg rd-cursor-pointer">
          <IoCloseCircleSharp />
        </div>
      </button>
    </header>
  );
};

export default Header;