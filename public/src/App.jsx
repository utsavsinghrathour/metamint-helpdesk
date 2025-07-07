import React, { createContext, useState, useEffect } from 'react';
import HomeTabs from './components/HomeTabs.jsx';
import MessageTabs from './components/MessageTabs.jsx';
import NoticeTabs from './components/NoticeTabs.jsx';
import HelpTabs from './components/HelpTabs.jsx';
import ActiveFeatured from './components/ActiveFeatured.jsx';
import './styles/main.css';

const App = ({ imageUrls = { logo: '' } }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [activeTab, setActiveTab] = useState(0);
  const [isExpanded, setIsExpanded] = useState(false);
  const [selectedNotice, setSelectedNotice] = useState(null);
  const [showActiveFeatured, setShowActiveFeatured] = useState(true);
  const [selectedTicket, setSelectedTicket] = useState();

  const handleSelectedTicketUpdate = (newValue) => {
    setSelectedTicket(newValue);
  };

  useEffect(() => {
    if (selectedTicket) {
      console.log('selectedTicket', selectedTicket);
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
        {robodeskData.imageUrls.popover_top_icon && robodeskData.imageUrls.popover_top_icon.trim() !== '' ? (
          <img
            src={robodeskData.imageUrls.popover_top_icon}
            alt="Popup Top Icon"
            className="rd-h-auto rd-rounded-md"
            onError={(e) => {
              e.target.style.display = 'none';
            }}
          />
        ) : null}
      </div>
      <button
        className={`robodesk-toggle-btn rd-bg-blue rd-rounded-border rd-border-none rd-cursor-pointer ${
          isOpen ? 'rotatedss' : ''
        }`}
        onClick={handlePopupToggle}
      >
        {robodeskData.imageUrls.popover_icon && robodeskData.imageUrls.popover_icon.trim() !== '' ? (
          <img
            src={robodeskData.imageUrls.popover_icon}
            alt="Pop over Icon"
            className="rd-h-auto"
            onError={(e) => {
              e.target.style.display = 'none';
            }}
          />
        ) : (
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="14" fill="white">
            <path d="M233.4 406.6c12.5 12.5 32.8 12.5 45.3 0l192-192c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L256 338.7 86.6 169.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l192 192z" />
          </svg>
        )}
      </button>
    </div>
  );
};

export default App;
