import { useState, useEffect, useRef, useMemo } from "react";
import apiFetch from "@wordpress/api-fetch";
import HomeTabs from "./components/HomeTabs.jsx";
import MessageTabs from "./components/MessageTabs.jsx";
import NoticeTabs from "./components/NoticeTabs.jsx";
import HelpTabs from "./components/HelpTabs.jsx";
import ActiveFeatured from "./components/ActiveFeatured.jsx";
import "./styles/main.css";
import defaultLogo from "./images/robodesk-logo.png";
import { AppDataProvider, useAppData } from "./utils/AppDataContext.jsx";
// import useExclusiveServerEvents from './Hooks/useExclusiveServerEvents.js';
// import useSSE from './Hooks/useSSE.js';
import { sseManager } from "./Hooks/sseManager.js";
import { playRobodeskBeep } from "./utils/notificationSound.js";

const AppContent = ({ imageUrls = { logo: "" } }) => {
  const {
    loadingContext,
    ticketsContext,
    loggedinContext,
    loggedInUsrId,
    updateTicket,
    updateNewCommentNotification,
    errorContext,
    isSingleTicketPage,
    openTicketId,
  } = useAppData();

  const [isOpen, setIsOpen] = useState(false);
  const [activeTab, setActiveTab] = useState(0);
  const [isExpanded, setIsExpanded] = useState(false);
  const [selectedNotice, setSelectedNotice] = useState(null);
  const [showActiveFeatured, setShowActiveFeatured] = useState(true);
  const [selectedTicket, setSelectedTicket] = useState();
  const [focusedTextArea, setFocusedTextArea] = useState(false);
  const [initialCursorId, setInitialCursorId] = useState(null);
  const hasAutoOpenedTicket = useRef(false);
  const hasAttemptedDeepLinkFetch = useRef(false);

  const isSupportPage = window.location.pathname.includes("/robodesk-support");
  const chatEnabled = window.robodeskData?.enable_chat === "1";

  const handleFocusedTextArea = (focus) => setFocusedTextArea(focus);

  const hasNotificationOff = chatEnabled && ticketsContext?.some(
    (ticket) => ticket.notifications === "no",
  );

  const isRobodeskPopupIntent = () => {
    const params = new URLSearchParams(window.location.search);
    const popupParam = params.get("rbd_popup");
    const initiatorParam = params.get("initiator") || params.get("initator");
    return popupParam === "true" && initiatorParam === "robodesk";
  };

  const requestedTicketId = useMemo(() => {
    const deepLinkTicketId = window.robodeskData?.deepLinkTicketId;
    if (deepLinkTicketId) {
      return String(deepLinkTicketId);
    }

    const params = new URLSearchParams(window.location.search);
    return (
      params.get("ticket_id") ||
      params.get("ticket") ||
      params.get("tid") ||
      ""
    ).trim();
  }, []);

  useEffect(() => {
    if (isRobodeskPopupIntent()) {
      setShowActiveFeatured(false);
      setIsOpen(true);
      if (chatEnabled) setActiveTab(1);
    }
  }, []);

  useEffect(() => {
    if (isSupportPage || !requestedTicketId || hasAutoOpenedTicket.current) {
      return;
    }

    if (!Array.isArray(ticketsContext) || ticketsContext.length === 0) {
      return;
    }

    const normalizedRequestedId = String(requestedTicketId).toLowerCase();
    const matchedTicket = ticketsContext.find((ticket) => {
      const byId =
        String(ticket?.id ?? "").toLowerCase() === normalizedRequestedId;
      const ticketNumber = String(ticket?.ticketNumber ?? "").toLowerCase();
      const byTicketNumber = ticketNumber === normalizedRequestedId;
      const byTicketNumberSuffix =
        ticketNumber.replace(/^tckt-/, "").trim() === normalizedRequestedId;

      return byId || byTicketNumber || byTicketNumberSuffix;
    });

    if (matchedTicket) {
      setSelectedTicket(matchedTicket);
      setShowActiveFeatured(false);
      setIsOpen(true);
      if (chatEnabled) setActiveTab(1);
      hasAutoOpenedTicket.current = true;
    }
  }, [isSupportPage, requestedTicketId, ticketsContext]);

  useEffect(() => {
    if (isSupportPage || !requestedTicketId || hasAutoOpenedTicket.current) {
      return;
    }

    if (hasAttemptedDeepLinkFetch.current) {
      return;
    }

    if (!loggedInUsrId && robodeskAuth.isLoggedIn !== "true") {
      return;
    }

    hasAttemptedDeepLinkFetch.current = true;

    const mapTicketPayload = (ticket) => ({
      id: ticket.id,
      ticketNumber: ticket.ticket_number,
      title: ticket.ticket_title || ticket.title?.rendered || "",
      source:
        ticket.ticket_origin && ticket.ticket_origin !== "N/A"
          ? ticket.ticket_origin
          : "Web",
      timeAgo: ticket.ticket_time_ago ? `${ticket.ticket_time_ago} ago` : "",
      authorImage: ticket.ticket_author_image || null,
      status: ticket.ticket_status ?? "open",
      priority: ticket.ticket_priority ?? "N/A",
      author: ticket.ticket_author,
      author_id: ticket.author,
      lastReply: ticket.ticket_last_reply,
      totalComments: ticket.ticket_total_comments,
      notifications: ticket.ticket_notifications,
      comments: ticket.ticket_comments,
    });

    // guard: only fetch when requestedTicketId looks like a positive integer
    const shouldFetchRequestedTicket = (() => {
      if (!requestedTicketId) return false;
      // allow numeric ids or tckt-style values; ensure not '0' or '0'-like
      if (/^\d+$/.test(String(requestedTicketId).trim())) {
        return Number(requestedTicketId) > 0;
      }
      // for ticket numbers (like TCKT-123), permit non-numeric and let server handle
      return true;
    })();

    if (!shouldFetchRequestedTicket) return;

    const fetchRequestedTicket = async () => {
      try {
        const response = await fetch(
          `/wp-json/wp/v2/robodesk_ticket/${encodeURIComponent(
            requestedTicketId,
          )}`,
        );

        if (!response.ok) {
          return;
        }

        const ticket = await response.json();
        if (!ticket?.id) {
          return;
        }

        const mappedTicket = mapTicketPayload(ticket);
        setSelectedTicket(mappedTicket);
        setShowActiveFeatured(false);
        setIsOpen(true);
        if (chatEnabled) setActiveTab(1);
        hasAutoOpenedTicket.current = true;
      } catch (error) {
        console.error("Unable to fetch requested deep-link ticket", error);
      }
    };

    fetchRequestedTicket();
  }, [isSupportPage, requestedTicketId, loggedInUsrId]);

  const handleSelectedTicketUpdate = (newValue) => {
    setSelectedTicket(newValue);
  };

  useEffect(() => {
    if (!initialCursorId && ticketsContext?.length > 0 && ticketsContext[0]?.id) {
      setInitialCursorId(ticketsContext[0]?.id);
    }
  }, [ticketsContext]);

  const url = useMemo(() => {
    if (!initialCursorId) return null;
    if (!loggedinContext && !loggedInUsrId) return null;
    const nonce = window.robodeskAuth?.restNonce || window.wpApiSettings?.nonce || '';
    return `${window.location.origin}/wp-json/robodesk/v1/stream/tickets/0/${initialCursorId}/${loggedInUsrId}?_wpnonce=${nonce}`;
  }, [initialCursorId, loggedInUsrId]);

  // Tracks the latest reply id already accounted for, per ticket, so we beep
  // only for genuinely NEW messages (not already-unread ones) and beep again
  // for every subsequent new reply even when older ones are still unread.
  const lastNotifiedCommentId = useRef({});

  // Prime the browser audio engine on the first user gesture so that
  // programmatic notification sounds (fired from SSE callbacks) are not
  // blocked by the browser autoplay policy.
  useEffect(() => {
    const unlock = () => {
      const sound = window.robodeskAuth?.notificationSound;
      if (sound) {
        const audio = new Audio(sound);
        const p = audio.play();
        if (p && typeof p.then === "function") {
          p.then(() => audio.pause()).catch(() => { });
        }
      }
      window.removeEventListener("pointerdown", unlock);
      window.removeEventListener("keydown", unlock);
      window.removeEventListener("touchstart", unlock);
    };

    window.addEventListener("pointerdown", unlock);
    window.addEventListener("keydown", unlock);
    window.addEventListener("touchstart", unlock);
    return () => {
      window.removeEventListener("pointerdown", unlock);
      window.removeEventListener("keydown", unlock);
      window.removeEventListener("touchstart", unlock);
    };
  }, []);

  useEffect(() => {
    if (!url) return undefined;

    // Keep the global "tickets" channel connected even when a single ticket is
    // open, so unread replies on ANY ticket still trigger the beep. The open
    // ticket's own replies are beeped via the comments channel.
    sseManager.connect(url, "tickets");

    const unsubscribeNewTicket = sseManager.on("new_ticket", (data) => {
      updateTicket(data);
    }, "tickets");

    const unsubscribeNotification = sseManager.on(
      "new_notification",
      (data) => {
        const items = Array.isArray(data) ? data : [];
        const ids = items.map((it) =>
          typeof it === "object" ? it.id : it,
        );

        // console.info("Executed From new notif");
        //   console.log('We ant here')
        handleSelectedTicketUpdate(null);
        updateNewCommentNotification(ids);

        // Beep only for tickets with a reply newer than what we've already
        // seen, and never for the ticket currently being viewed (it beeps via
        // the comments channel instead).
        let shouldBeep = false;
        items.forEach((it) => {
          const tid = typeof it === "object" ? it.id : it;
          const cid = typeof it === "object" ? Number(it.last_comment_id) || 0 : 0;
          const prev = lastNotifiedCommentId.current[tid];
          if (cid > 0 && prev !== undefined && cid > prev && tid !== openTicketId) {
            shouldBeep = true;
          }
          if (cid > 0) {
            lastNotifiedCommentId.current[tid] = cid;
          }
        });

        if (shouldBeep) {
          playRobodeskBeep();
        }
      },
      "tickets",
    );
    return () => {
      console.info("closing");
      unsubscribeNewTicket();
      unsubscribeNotification();
      sseManager.disconnect("tickets");
    };
  }, [url, selectedTicket, isSingleTicketPage, loggedinContext, loggedInUsrId, openTicketId]);

  useEffect(() => {
    if (selectedTicket) {
      setSelectedTicket(selectedTicket);
    }
  }, [selectedTicket]);

  const handleNoticeSelect = (notice) => {
    setSelectedNotice(notice);
    setShowActiveFeatured(false);
    if (chatEnabled) setActiveTab(1);
    setIsOpen(true);
  };

  const generateToken = async () => {
    let duration = 1 * 24 * 60 * 60; // Default to 1 day
    const STORAGE_KEY = "mcp_jwt_token";
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        const parsed = JSON.parse(stored);
        const now = Math.floor(Date.now() / 1000); // current time in seconds
        // 2. Check if token is still valid
        if (parsed.token && parsed.expiry && parsed.expiry > now) {
          console.log("Using available token");
          return parsed.token;
        } else {
          console.log("Token expired, generating new one...");
        }
      }
      const response = await apiFetch({
        path: "/jwt-auth/v1/token",
        method: "POST",
        data: {
          expires_in: duration,
        },
        includeCredentials: true,
      });
      const now = Math.floor(Date.now() / 1000);

      const tokenData = {
        token: response.token,
        expiry: now + duration,
      };

      // 4. Store token + expiry
      localStorage.setItem(STORAGE_KEY, JSON.stringify(tokenData));
      console.log("Generated new token");
      return response.token;
    } catch (err) {
      console.error(
        err.message || __("Error generating token", "wordpress-mcp"),
      );
    }
  };

  const handlePopupToggle = async () => {
    console.log("Toggle Clicked", robodeskAuth.isLoggedIn, typeof robodeskAuth.isLoggedIn);
    if (isOpen) {
      setIsOpen(false);
      setShowActiveFeatured(true);
      setSelectedNotice(null);
    } else {
      if (robodeskAuth.isLoggedIn === "true") {
        await generateToken(); // Generate token on popup toggle open
      }
      setIsOpen(true);
      setShowActiveFeatured(false);
    }
  };

  const handleClose = () => {
    setIsOpen(false);
    setActiveTab(0);
    setSelectedNotice(null);
    setShowActiveFeatured(true);
  };

  const tabs = [
    {
      id: 0,
      component: (
        <HomeTabs
          setActiveTab={setActiveTab}
          setIsOpen={setIsOpen}
          isExpanded={isExpanded}
          setIsExpanded={setIsExpanded}
          handleClose={handleClose}
          updateSelectedTicket={handleSelectedTicketUpdate}
          logo={imageUrls.logo}
          handleFocusedTextArea={handleFocusedTextArea}
          focused={focusedTextArea}
        />
      ),
    },
    {
      id: 1,
      component: (
        <MessageTabs
          setActiveTab={setActiveTab}
          setIsOpen={setIsOpen}
          isExpanded={isExpanded}
          setIsExpanded={setIsExpanded}
          activeTab={activeTab}
          selectedNewTicket={selectedTicket}
          updateSelectedTicket={handleSelectedTicketUpdate}
          handleFocusedTextArea={handleFocusedTextArea}
          logo={imageUrls.logo}
        />
      ),
    },
    {
      id: 2,
      component: (
        <NoticeTabs
          setActiveTab={setActiveTab}
          setIsOpen={setIsOpen}
          isExpanded={isExpanded}
          setIsExpanded={setIsExpanded}
          activeTab={activeTab}
          selectedNotice={selectedNotice}
          setShowActiveFeatured={setShowActiveFeatured}
          logo={imageUrls.logo}
          wideLogo={imageUrls.wide_logo}
        />
      ),
    },
    {
      id: 3,
      component: (
        <HelpTabs
          setActiveTab={setActiveTab}
          setIsOpen={setIsOpen}
          isExpanded={isExpanded}
          setIsExpanded={setIsExpanded}
          activeTab={activeTab}
          logo={imageUrls.logo}
          handleFocusedTextArea={handleFocusedTextArea}
        />
      ),
    },
  ];

  //   if (loadingContext) return <p>Loading...</p>;
  //   if (errorContext) return <p>Error: {errorContext}</p>;

  return (
    <div className="robodesk-plugin rd-flex rd-flex-col rd-align-end ">
      {showActiveFeatured && (
        <ActiveFeatured onNoticeSelect={handleNoticeSelect} />
      )}

      <div
        className={`robodesk-popup rd-flex rd-flex-col rd-border-none rd-border-radius-md ${isOpen ? "open" : "closed"
          } ${isExpanded ? "expanded" : ""}`}
      >
        {tabs[activeTab].component}
      </div>

      <div
        className={` rd-popup-top-icon-container ${isOpen ? "rd-hidden " : ""
          } ${hasNotificationOff && !isOpen ? "rd-title-with-dot" : ""}`}
      >
        {imageUrls.popover_top_icon &&
          imageUrls.popover_top_icon.trim() !== "" ? (
          <img
            src={imageUrls.popover_top_icon}
            alt="Popup Top Icon"
            className="rd-h-auto rd-rounded-md"
            onError={(e) => {
              e.target.style.display = "none";
            }}
          />
        ) : null}
      </div>

      <button
        className={`robodesk-toggle-btn rd-bg-blue rd-rounded-border rd-border-none rd-cursor-pointer ${isOpen ? "rotatedssss" : ""
          }`}
        onClick={handlePopupToggle}
      >
        {isOpen ? (
          <svg
            xmlns="http://www.w3.org/2000/svg"
            classwidth="24"
            height="24"
            fill="currentColor"
            className="bi bi-chevron-up"
            viewBox="0 0 16 16"
          >
            <path
              fillRule="evenodd"
              d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708z"
            />
          </svg>
        ) : imageUrls.popover_icon && imageUrls.popover_icon.trim() !== "" ? (
          <img
            src={imageUrls.popover_icon}
            alt="Pop over Icon"
            className="rd-h-auto"
            onError={(e) => {
              e.target.style.display = "none";
            }}
          />
        ) : (
          <img
            src={defaultLogo}
            alt="Default Plugin Logo"
            className="rd-h-auto rd-header-logo"
          />
        )}
      </button>
    </div>
  );
};

const App = (props) => (
  <AppDataProvider>
    <AppContent {...props} />
  </AppDataProvider>
);

export default App;
