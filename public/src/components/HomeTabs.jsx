import { useState } from "react";
import Header from "./Header.jsx";
import Footer from "./Footer.jsx";
import SingleQuestionBox from "./SingleQuestionBox";
import { readChatSession } from "../utils/startConversation";
import "../styles/HomeTabs.css";
import MetamintHelpdeskBotChatWidgetMain from "./MetamintHelpdeskBotChatWidgetMain";

const HomeTabs = ({
  setActiveTab,
  setIsOpen,
  isExpanded,
  setIsExpanded,
  handleClose,
  updateSelectedTicket,
  logo,
  focused,
  handleFocusedTextArea,
}) => {
  const [showLogin, setShowLogin] = useState(false);
  const [showHeaderFooter, setShowHeaderFooter] = useState(true);
  const [messages, setMessages] = useState(() => readChatSession());

  return (
    <div className="mmhd-flex mmhd-flex-col mmhd-h-full featured-faq">
      <div className="mmhd-main-content">
        {!showLogin && showHeaderFooter && (
          <Header
            logo={logo}
            onClose={handleClose}
            setActiveTab={setActiveTab}
            setShowLogin={setShowLogin}
            updateSelectedTicket={updateSelectedTicket}
          />
        )}
        <div className="mmhd-flex-1 mmhd-overflow-auto mmhd-body-content-wrapper-home">
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
