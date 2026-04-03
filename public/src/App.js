import { useState, useEffect, useRef, useMemo } from "react";
import apiFetch from "@wordpress/api-fetch";
import HomeTabs from "./components/HomeTabs.jsx";
import MessageTabs from "./components/MessageTabs.jsx";
import NoticeTabs from "./components/NoticeTabs.jsx";
import HelpTabs from "./components/HelpTabs.jsx";
import ActiveFeatured from "./components/ActiveFeatured.jsx";
import "./styles/main.css";
import defaultLogo from "./images/robodesk-logo.png";
import { AppDataProvider, useAppData } from "./utils/AppDataContext";
// import useExclusiveServerEvents from './Hooks/useExclusiveServerEvents.js';
// import useSSE from './Hooks/useSSE.js';
import { sseManager } from "./Hooks/sseManager";

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
  } = useAppData();

  const [isOpen, setIsOpen] = useState(false);
  const [activeTab, setActiveTab] = useState(0);
  const [isExpanded, setIsExpanded] = useState(false);
  const [selectedNotice, setSelectedNotice] = useState(null);
  const [showActiveFeatured, setShowActiveFeatured] = useState(true);
  const [selectedTicket, setSelectedTicket] = useState();
  const [focusedTextArea, setFocusedTextArea] = useState(false);

  const notificationAudio = useRef(
    new Audio(window.wpAuth?.notificationSound || ""),
  );

  const handleFocusedTextArea = (focus) => setFocusedTextArea(focus);

  const hasNotificationOff = ticketsContext?.some(
    (ticket) => ticket.notifications === "no",
  );

  const resetPasswordCondition = () => {
    const params = new URLSearchParams(window.location.search);
    const popupParam = params.get("rbd_popup");
    const initiatorParam = params.get("initator");
    return popupParam === "true" && initiatorParam === "robodesk";
  };

  useEffect(() => {
    const params = new URLSearchParams(window.location.search);

    const popupParam = params.get("rbd_popup");
    const initiatorParam = params.get("initator");

    if (popupParam === "true" && initiatorParam === "robodesk") {
      setShowActiveFeatured(false);
      setIsOpen(true);
      setActiveTab(1);
    }
  }, []);

  const handleSelectedTicketUpdate = (newValue) => {
    setSelectedTicket(newValue);
  };

  const url = useMemo(() => {
    if (!ticketsContext[0]?.id) return null;
    if (!loggedinContext && !loggedInUsrId) return null;
    // console.log('The url from memo change');
    // console.log(window.location.origin);
    return `${window.location.origin}/wp-json/robodesk/v1/stream/tickets/0/${ticketsContext[0]?.id}/${loggedInUsrId}`;
  }, [ticketsContext[0]?.id, loggedInUsrId]);

  useEffect(() => {
    notificationAudio.current.load();
    // useSSE(url, handlers);
  }, []);

  useEffect(() => {
    if (!url && !loggedinContext && !loggedInUsrId) return;
    // console.info('I am executed first');
    sseManager.connect(url);

    const unsubscribeNewTicket = sseManager.on("new_ticket", (data) => {
      //   console.log('Executed From new Ticket');
      updateTicket(data);
    });

    const unsubscribeNotification = sseManager.on(
      "new_notification",
      (data) => {
        const ids = Array.isArray(data)
          ? data.map((item) => (typeof item === "object" ? item.id : item))
          : [];

        console.info("Executed From new notif");
        //   console.log('We ant here')
        handleSelectedTicketUpdate(null);
        if (updateNewCommentNotification(ids)) {
          notificationAudio.current.play();
        }
      },
    );
    return () => {
      console.info("closing");
      unsubscribeNewTicket();
      unsubscribeNotification();
    };
  }, [url]);

  useEffect(() => {
    if (selectedTicket) {
      setSelectedTicket(selectedTicket);
    }
  }, [selectedTicket]);

  const handleNoticeSelect = (notice) => {
    setSelectedNotice(notice);
    setShowActiveFeatured(false);
    setActiveTab(1);
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
      console.error(err.message || __("Error generating token", "wordpress-mcp"));
    }
  };

  const handlePopupToggle = async () => {
    console.log("Toggle Clicked",wpAuth.isLoggedIn, typeof(wpAuth.isLoggedIn));
    if (isOpen) {
      setIsOpen(false);
      setShowActiveFeatured(true);
      setSelectedNotice(null);
    } else {
      if (wpAuth.isLoggedIn === 'true') {
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
        className={`robodesk-popup rd-flex rd-flex-col rd-border-none rd-border-radius-md ${
          isOpen ? "open" : "closed"
        } ${isExpanded ? "expanded" : ""}`}
      >
        {tabs[activeTab].component}
      </div>

      <div
        className={` rd-popup-top-icon-container ${
          isOpen ? "rd-hidden " : ""
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
        className={`robodesk-toggle-btn rd-bg-blue rd-rounded-border rd-border-none rd-cursor-pointer ${
          isOpen ? "rotatedssss" : ""
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
