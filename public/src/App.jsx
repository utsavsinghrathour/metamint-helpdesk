import React, { createContext, useState, useEffect } from 'react';
import HomeTabs from './components/HomeTabs.jsx';
import MessageTabs from './components/MessageTabs.jsx';
import NoticeTabs from './components/NoticeTabs.jsx';
import HelpTabs from './components/HelpTabs.jsx';
import ActiveFeatured from './components/ActiveFeatured.jsx';
import './styles/main.css';
import defaultLogo from './images/robodesk-logo.png';

const App = ({ imageUrls = { logo: '' } }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [activeTab, setActiveTab] = useState(0);
  const [isExpanded, setIsExpanded] = useState(false);
  const [selectedNotice, setSelectedNotice] = useState(null);
  const [showActiveFeatured, setShowActiveFeatured] = useState(true);
  const [selectedTicket, setSelectedTicket] = useState();
  const [projects, setProjects] = useState([]);
  
 const handleSelectedTicketUpdate = (newValue) => {
        setSelectedTicket(newValue);
        console.log('Our new ticket val is ', newValue);
  };
  
  useEffect(() => {
    const apiUrl = `/wp-json/wp/v2/robodesk_ticket?_embed`;
    fetch(apiUrl)
      .then((res) => res.json())
      .then((data) => {
        const formattedProjects = data.map((post) => ({
          title: post.title.rendered,
          subtitle:
            post._embedded?.["wp:term"]
              ?.flat()
              .map((term) => term.name)
              .join(", ") || "",
          image: post._embedded?.["wp:featuredmedia"]?.[0]?.source_url || "",
          website: post.meta?.website_url || "",
          slug: post.slug,
        }));
        setProjects(formattedProjects);
      })
      .catch((err) => console.error("Failed to fetch API posts", err));
  }, []);

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

  const handlePopupToggle = () => {
    if (isOpen) {
      setIsOpen(false);
      setShowActiveFeatured(true);
      setSelectedNotice(null);
    } else {
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
        />
      ),
    },
  ];

  return (
    <div className="robodesk-plugin rd-flex rd-flex-col rd-align-end">
      {/* {console.log('isExpanded:', isExpanded)} */}
      {console.log('faster projects:', projects)}
      {showActiveFeatured && <ActiveFeatured onNoticeSelect={handleNoticeSelect} />}
      <div
        className={`robodesk-popup rd-flex rd-flex-col rd-border-none rd-border-radius-md ${
          isOpen ? 'open' : 'closed'
        } ${isExpanded ? 'expanded' : ''}`}
      >
        {tabs[activeTab].component}
      </div>
      <div className={`rd-popup-top-icon-container ${isOpen ? 'rd-hidden' : ''}`}>
        {console.log(
          'rd-popup-top-icon-container class:',
          `rd-popup-top-icon-container ${isExpanded ? 'rd-hidden' : ''}`
        )}
        
        {imageUrls.popover_top_icon && imageUrls.popover_top_icon.trim() !== '' ? (
          <img
            src={imageUrls.popover_top_icon}
            alt="Popup Top Icon"
            className="rd-h-auto rd-rounded-md"
            onError={(e) => {
              e.target.style.display = 'none';
            }}
          />
        ) : null}
        {
          console.log('arpan', imageUrls.popover_top_icon)
        }
      </div>
      <button
        className={`robodesk-toggle-btn rd-bg-blue rd-rounded-border rd-border-none rd-cursor-pointer ${
          isOpen ? 'rotatedssss' : ''
        }`}
        onClick={handlePopupToggle}
      >
        {isOpen ? (
          <svg xmlns="http://www.w3.org/2000/svg" classwidth="24" height="24" fill="currentColor" class="bi bi-chevron-up" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M7.646 4.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1-.708.708L8 5.707l-5.646 5.647a.5.5 0 0 1-.708-.708z"/>
</svg>
        ) : (
          imageUrls.popover_icon && imageUrls.popover_icon.trim() !== '' ? (
            <img
              src={imageUrls.popover_icon}
              alt="Pop over Icon"
              className="rd-h-auto"
              onError={(e) => {
                e.target.style.display = 'none';
              }}
            />
          ) : (
            <img
              src={defaultLogo}
              alt="Default Plugin Logo"
              className="rd-h-auto rd-header-logo"
            />
          )
        )}
      </button>
    </div>
    
  );
};

export default App;
