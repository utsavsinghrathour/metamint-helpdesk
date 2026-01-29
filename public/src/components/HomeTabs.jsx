import { useState } from 'react';
import Header from './Header.jsx';
import Footer from './Footer.jsx';
import SingleQuestionBox from './SingleQuestionBox';
import '../styles/HomeTabs.css';
import RobodeskBotChatWidgetMain from './RobodeskBotChatWidgetMain';

const HomeTabs = ({ setActiveTab, setIsOpen, isExpanded, setIsExpanded, handleClose, updateSelectedTicket, logo, focused, handleFocusedTextArea }) => {
  const [showLogin, setShowLogin] = useState(false);
  const [showHeaderFooter, setShowHeaderFooter] = useState(true);
  const CHAT_KEY = "robodesk_chat_session";
  const [messages, setMessages] = useState(() => {
    const saved = sessionStorage.getItem(CHAT_KEY);
    return saved ? JSON.parse(saved) : [];
  });

  return (
    <div className="rd-flex rd-flex-col rd-h-full featured-faq">
      <div className="rd-main-content">
        {!showLogin && showHeaderFooter && <Header logo={logo} onClose={handleClose} setActiveTab={setActiveTab} setShowLogin={setShowLogin} updateSelectedTicket={updateSelectedTicket} />}
        <div className="rd-flex-1 rd-overflow-auto">
          <SingleQuestionBox
            setShowHeaderFooter={setShowHeaderFooter}
            showHeaderFooter={showHeaderFooter}
            isExpanded={isExpanded}
            setIsExpanded={setIsExpanded}
            setShowLogin={setShowLogin}
            setActiveTab={setActiveTab}
            updateSelectedTicket={updateSelectedTicket}
            handleFocusedTextArea={handleFocusedTextArea}
            focused={focused}

          />
        </div>
      </div>
      {showHeaderFooter && <Footer activeTab={0} setActiveTab={setActiveTab} />}
    </div>
  );
};

export default HomeTabs;