import React, { useState, useEffect } from 'react';
import defaultLogo from '../images/robodesk-logo.png'; // Adjust path to your default logo
import '../styles/header.css';
import { IoAddCircle, IoCloseCircleSharp, IoLogoWhatsapp } from 'react-icons/io5';
import { sendReply } from '../utils/modules';
import { useAppData } from '../utils/AppDataContext';
import { createTicket } from "../utils/modules";

const Header = ({ logo, onClose, setActiveTab, setShowLogin, updateSelectedTicket }) => {
  const [talkToHuman, setTalkToHuman] = useState(false);
  const [messageCount, setMessageCount] = useState(0);
  const CHAT_KEY = "robodesk_chat_session";
  const [selectedStaff, setSelectedStaff] = useState(null);
  const { loggedinContext, loggedInUsrId, refreshTickets } = useAppData();
  const [isSubmitting, setIsSubmitting] = useState(false);
  // null = unknown, true = available, false = unavailable
  const [sendWhatsappMessage, setSendWhatsappMessage] = useState(null);
  const [whatsappPhone, setWhatsappPhone] = useState(null);

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
      // console.log('API Response:', response.status, data);

      if (response.ok && data?.staff_user_id) {
        // console.log('Setting staff ID:', data.staff_user_id);
        setSelectedStaff(data.staff_user_id);
      } else {
        // console.log('No default staff configured or error:', data);
        setSelectedStaff(null);
      }
    } catch (error) {
      console.error("Error fetching default staff:", error);
      setSelectedStaff(null);
    }
  };


  const handleTalkToHumanClick = async () => {
    if (isSubmitting) return; // ← guard against multiple clicks
    setIsSubmitting(true);
    
    // Dispatch event to show loader in chat window
    window.dispatchEvent(
      new CustomEvent("robodesk:creatingTicket", {
        detail: { isCreating: true },
      })
    );

    const saved = sessionStorage.getItem(CHAT_KEY);
    const messages = saved ? JSON.parse(saved) : [];
    const messagesExceptFirst = messages.slice(1);

    if (!messages || messages.length === 0) {
      setIsSubmitting(false);
      window.dispatchEvent(
        new CustomEvent("robodesk:creatingTicket", {
          detail: { isCreating: false },
        })
      );
      return;
    }

    const userMessages = messages.filter(msg => msg.role === 'user');
    if (userMessages.length === 0) {
      setIsSubmitting(false);
      window.dispatchEvent(
        new CustomEvent("robodesk:creatingTicket", {
          detail: { isCreating: false },
        })
      );
      return;
    }

    const ticketTitle = userMessages[0].text;
    const ticketDescription = userMessages.map(msg => msg.text).join('\n\n');

    if (!loggedinContext) {
      localStorage.setItem('temp_ticket_title', ticketTitle);
      setActiveTab(1);
      setShowLogin(true);
      setIsSubmitting(false);
      window.dispatchEvent(
        new CustomEvent("robodesk:creatingTicket", {
          detail: { isCreating: false },
        })
      );
      return;
    }

    try {
      const result = await createTicket(loggedInUsrId, ticketTitle, ticketDescription);

      const newTicket = {
        id: result.data.id,
        ticketNumber: result.data.ticket_number,
        title: result.data.ticket_title,
        source:
          result.data.ticket_origin && result.data.ticket_origin !== 'N/A'
            ? result.data.ticket_origin
            : 'Web',
        timeAgo: result.data.ticket_time_ago,
        authorImage: result.data.ticket_author_image,
        status: result.data.ticket_status,
        priority: result.data.ticket_priority,
        messages: [],
      };

      // Add a single transition reply the first time a human ticket is created from the chat
      if (newTicket.id) {
        await sendReply(
          newTicket.id,
          selectedStaff || loggedInUsrId,
          'Transitioned conversations from AI to human agent.',
          false,
          true
        );
      }

      if (messagesExceptFirst.length > 0) {
        for (const message of messagesExceptFirst) {
          const messageText = message.text || message.message || '';
          if (!messageText.trim()) continue;

          if (message.role === 'user') {
            await sendReply(newTicket.id, loggedInUsrId, messageText);
          } else if (message.role === 'assistant') {
            await sendReply(newTicket.id, selectedStaff || loggedInUsrId, messageText, false, true);
          }
        }
      }

      refreshTickets();
      updateSelectedTicket(newTicket);
      setActiveTab(1);

      sessionStorage.removeItem(CHAT_KEY);
    } catch (error) {
      console.error("Failed to create ticket:", error);
    } finally {
      setIsSubmitting(false); // ← always re-enable, even on error
      // Dispatch event to hide loader in chat window
      window.dispatchEvent(
        new CustomEvent("robodesk:creatingTicket", {
          detail: { isCreating: false },
        })
      );
    }
  };

  const sendWhatsappMessageClick = async() => {
    try {
      const response = await fetch("/wp-json/robodesk-bot/v1/wa-settings");
      const data = await response.json();
      console.log('WhatsApp Settings Response:', response.status, data);
      // response may be 404 if the AI Bot addon isn't loaded; handle gracefully
      if (response.ok && data?.phoneNumber) {
        setSendWhatsappMessage(true);
        setWhatsappPhone(data.phoneNumber);
      } else {
        setSendWhatsappMessage(false);
        setWhatsappPhone(null);
      }
    } catch (error) {
      console.error("Error fetching widget config:", error);
      setSendWhatsappMessage(false);
      setWhatsappPhone(null);
    }
  }

  const handleWhatsappClick = () => {
    if (!whatsappPhone) return;

    // Normalize to digits only for wa.me (international format without + or separators)
    const normalized = whatsappPhone.toString().replace(/\D/g, '');
    const message = 'Hi, I need your help.';
    const url = `https://wa.me/${normalized}?text=${encodeURIComponent(message)}`;
    window.open(url, '_blank');
  }

  useEffect(() => {
    fetchDefaultStaff();
    fetchApiKey();
    // Check WhatsApp availability on mount
    sendWhatsappMessageClick();
    const saved = sessionStorage.getItem(CHAT_KEY);
    const messages = saved ? JSON.parse(saved) : [];
    setMessageCount(messages.length);

    const handleMessageSent = (e) => {
      setMessageCount(e.detail?.count ?? 1);
    };

    window.addEventListener("robodesk:messageSent", handleMessageSent);
    return () => window.removeEventListener("robodesk:messageSent", handleMessageSent);
  }, []);

  return (
    <header className="robodesk-header rd-flex rd-justify-between">
      <div className="rd-flex rd-items-center">
        {logo && logo.trim() !== '' ? (
          <img
            src={logo}
            alt="Plugin Logo"
            className=" rd-header-logo rd-main-header-logo"
            onError={(e) => {
              e.target.style.display = 'none';
            }}
          />
        ) : (
          <div className=" rd-header-logo default">
            <img
              src={defaultLogo}
              alt="Default Plugin Logo"
              className=" rd-header-logo rd-main-header-logo"
            />
          </div>
        )}
        {sendWhatsappMessage === true &&
        <button
          className="rd-m-5 rd-border-none rd-bg-transparent rd-p-0"
          onClick={handleWhatsappClick}
        >
          <div className="icon-dark custom-svg rd-cursor-pointer">
            <IoLogoWhatsapp />
          </div>
        </button>
        }
        {talkToHuman && messageCount > 0 && (
          <button
            className="rd-m-5"
            onClick={handleTalkToHumanClick}
            disabled={isSubmitting} 
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