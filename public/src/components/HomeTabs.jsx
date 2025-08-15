import  { useState } from 'react';
import Header from './Header.jsx';
import Footer from './Footer.jsx';
import SingleQuestionBox from './SingleQuestionBox';
import '../styles/HomeTabs.css';

const HomeTabs = ({ setActiveTab, setIsOpen, isExpanded, setIsExpanded, handleClose, updateSelectedTicket, logo ,focused ,handleFocusedTextArea }) => {
  const [showLogin, setShowLogin] = useState(false);
  const [showHeaderFooter, setShowHeaderFooter] = useState(true);

  return (
    <div className="rd-flex rd-flex-col rd-h-full featured-faq">
      <div className="rd-main-content">
        {!showLogin && showHeaderFooter && <Header logo={logo} onClose={handleClose} />}
        <div className="rd-flex-1 rd-overflow-auto">
          <SingleQuestionBox
            setShowHeaderFooter={setShowHeaderFooter}
            showHeaderFooter={showHeaderFooter}
            isExpanded={isExpanded}
            setIsExpanded={setIsExpanded}
            setShowLogin={setShowLogin}
            setActiveTab={setActiveTab}
            updateSelectedTicket={updateSelectedTicket}
            handleFocusedTextArea = {handleFocusedTextArea}
            focused={focused}
            
          />
        </div>
      </div>
      {showHeaderFooter && <Footer activeTab={0} setActiveTab={setActiveTab} />}
    </div>
  );
};

export default HomeTabs;