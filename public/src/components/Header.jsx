import React, { useState, useEffect } from 'react';
import defaultLogo from '../images/mmhd-logo.png'; // Adjust path to your default logo
import '../styles/header.css';
import { IoAddCircle, IoCloseCircleSharp, IoLogoWhatsapp } from 'react-icons/io5';
import { sendReply } from '../utils/modules';
import { readChatSession, CHAT_SESSION_KEY } from '../utils/startConversation';
import { useAppData } from '../utils/AppDataContext';
import { createTicket } from "../utils/modules";

let staffFetched = false;

const Header = ({ logo, onClose, setActiveTab, setShowLogin, updateSelectedTicket }) => {
  const [talkToHuman, setTalkToHuman] = useState(false);
  const [messageCount, setMessageCount] = useState(0);
  const [selectedStaff, setSelectedStaff] = useState(null);
  const { loggedinContext, loggedInUsrId, refreshTickets } = useAppData();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const chatEnabled = window.mmhdData?.enable_chat === "1";
  const canLoadWidgetConfig = Boolean(
    chatEnabled &&
    window.mmhdData?.mmhd_addon_active &&
    window.mmhdData?.mmhd_ai_bot_active,
  );
  // null = unknown, true = available, false = unavailable
  const [sendWhatsappMessage, setSendWhatsappMessage] = useState(null);
  const [whatsappPhone, setWhatsappPhone] = useState(null);

  const handleCloseClick = () => {
    onClose();
  };

  const fetchApiKey = async () => {
    if (!canLoadWidgetConfig) {
      setTalkToHuman(false);
      return;
    }
    try {
      const response = await fetch("/wp-json/mmhd-bot/v1/widget-config");
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
    if (staffFetched) return;
    staffFetched = true;
    if (window.mmhdAuth?.defaultStaffId) {
      setSelectedStaff(window.mmhdAuth.defaultStaffId);
    } else {
      setSelectedStaff(null);
    }
  };


  const handleTalkToHumanClick = async () => {
    if (isSubmitting) return; // ← guard against multiple clicks
    setIsSubmitting(true);

    // Dispatch event to show loader in chat window
    window.dispatchEvent(
      new CustomEvent("mmhd:creatingTicket", {
        detail: { isCreating: true },
      })
    );

    let messages = readChatSession();

    // Stably sort messages chronologically by timestamp
    const withIndex = messages.map((msg, idx) => ({ msg, idx }));
    withIndex.sort((a, b) => {
      const timeA = a.msg.timestamp ? new Date(a.msg.timestamp).getTime() : null;
      const timeB = b.msg.timestamp ? new Date(b.msg.timestamp).getTime() : null;
      if (timeA !== null && timeB !== null) {
        if (timeA !== timeB) {
          return timeA - timeB;
        }
      }
      return a.idx - b.idx;
    });
    messages = withIndex.map(item => item.msg);

    if (!messages || messages.length === 0) {
      setIsSubmitting(false);
      window.dispatchEvent(
        new CustomEvent("mmhd:creatingTicket", {
          detail: { isCreating: false },
        })
      );
      return;
    }

    const userMessages = messages.filter(msg => msg.role === 'user');
    if (userMessages.length === 0) {
      setIsSubmitting(false);
      window.dispatchEvent(
        new CustomEvent("mmhd:creatingTicket", {
          detail: { isCreating: false },
        })
      );
      return;
    }

    const ticketTitle = userMessages[0].text;
    const ticketDescription = messages
      .map(m => `${m.role === 'user' ? 'User' : 'AI Bot'}: ${m.text || m.message || ''}`)
      .join('\n\n');

    if (!loggedinContext) {
      localStorage.setItem('temp_ticket_title', ticketTitle);
      setActiveTab(1);
      setShowLogin(true);
      setIsSubmitting(false);
      window.dispatchEvent(
        new CustomEvent("mmhd:creatingTicket", {
          detail: { isCreating: false },
        })
      );
      return;
    }

    try {
      const firstUserTimestamp = userMessages[0]?.timestamp || null;
      const result = await createTicket(loggedInUsrId, ticketTitle, ticketDescription, firstUserTimestamp);

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

      if (messages.length > 0) {
        const firstUserMsg = userMessages[0];
        for (const m of messages) {
          if (m === firstUserMsg) continue;

          const text = m.text || m.message || '';
          if (!text.trim()) continue;

          const isBot = m.role === 'assistant';

          for (let attempt = 0; attempt < 3; attempt++) {
            try {
              await sendReply(
                newTicket.id,
                loggedInUsrId,
                text,
                false,
                isBot,
                undefined,
                m.timestamp || new Date().toISOString()
              );
              break;
            } catch (e) {
              if (attempt === 2) {
                console.error('Failed to transfer message:', e);
              } else {
                await new Promise(r => setTimeout(r, 500));
              }
            }
          }
        }

        try {
          await sendReply(
            newTicket.id,
            loggedInUsrId,
            'Transitioned conversations from AI to human agent.',
            false,
            true,
            undefined,
            new Date().toISOString()
          );
        } catch (e) {
          console.error('Failed to send transition reply:', e);
        }
      }

      refreshTickets();
      updateSelectedTicket(newTicket);
      setActiveTab(1);

      sessionStorage.removeItem(CHAT_SESSION_KEY);
    } catch (error) {
      console.error("Failed to create ticket:", error);
    } finally {
      setIsSubmitting(false); // ← always re-enable, even on error
      // Dispatch event to hide loader in chat window
      window.dispatchEvent(
        new CustomEvent("mmhd:creatingTicket", {
          detail: { isCreating: false },
        })
      );
    }
  };

  const sendWhatsappMessageClick = async () => {
    if (!canLoadWidgetConfig) {
      setSendWhatsappMessage(false);
      setWhatsappPhone(null);
      return;
    }
    try {
      const response = await fetch("/wp-json/mmhd-bot/v1/wa-settings");
      const data = await response.json();
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
    if (canLoadWidgetConfig) {
      fetchApiKey();
    } else {
      setTalkToHuman(false);
    }
    // Check WhatsApp availability on mount
    sendWhatsappMessageClick();
    setMessageCount(readChatSession().length);

    const handleMessageSent = (e) => {
      setMessageCount(e.detail?.count ?? 1);
    };

    window.addEventListener("mmhd:messageSent", handleMessageSent);
    return () => window.removeEventListener("mmhd:messageSent", handleMessageSent);
  }, []);

  return (
    <header className="mmhd-header mmhd-flex mmhd-justify-between">
      <div className="mmhd-flex mmhd-items-center">
        {logo && logo.trim() !== '' ? (
          <img
            src={logo}
            alt="Plugin Logo"
            className=" mmhd-header-logo mmhd-main-header-logo"
            onError={(e) => {
              e.target.style.display = 'none';
            }}
          />
        ) : (
          <div className=" mmhd-header-logo default">
            <img
              src={defaultLogo}
              alt="Default Plugin Logo"
              className=" mmhd-header-logo mmhd-main-header-logo"
            />
          </div>
        )}
        {sendWhatsappMessage === true &&
          <button
            className="mmhd-m-5 mmhd-border-none mmhd-bg-transparent mmhd-p-0"
            onClick={handleWhatsappClick}
          >
            <div className="icon-dark custom-svg mmhd-cursor-pointer">
              <IoLogoWhatsapp />
            </div>
          </button>
        }
        {talkToHuman && messageCount > 0 && (
          <button
            className="mmhd-m-5"
            onClick={handleTalkToHumanClick}
            disabled={isSubmitting}
          >
            <div className="icon-dark custom-svg mmhd-cursor-pointer">
              <IoAddCircle />
            </div>
          </button>
        )}
      </div>
      <button
        className="mmhd-close-btn mmhd-m-5 mmhd-border-none mmhd-bg-transparent mmhd-p-0"
        onClick={handleCloseClick}
      >
        <div className="icon-dark custom-svg mmhd-cursor-pointer">
          <IoCloseCircleSharp />
        </div>
      </button>
    </header>
  );
};

export default Header;