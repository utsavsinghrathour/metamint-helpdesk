import React, { useState } from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Header from './Header.jsx';
import Footer from './Footer.jsx';
import SendMessageField from './SendMessageField';
import SingleQuestionBox from './SingleQuestionBox';
import Login from './Login.jsx';
import '../styles/HomeTabs.css';

const HomeTabs = ({ setActiveTab, setIsOpen, isExpanded, setIsExpanded, handleClose }) => {
  const [showLogin, setShowLogin] = useState(false);
  const [showHeaderFooter, setShowHeaderFooter] = useState(true);

  return (
    <div className="rd-flex rd-flex-col rd-h-full featured-faq">
      <div className="rd-main-content">
        {!showLogin && showHeaderFooter && <Header onClose={handleClose} />}
        <div className="rd-flex-1 rd-overflow-auto">
          {showLogin   ? (
            <Login setShowLogin={setShowLogin} setActiveTab={setActiveTab}/>
          ) : (
            <SingleQuestionBox
              setShowHeaderFooter={setShowHeaderFooter}
              showHeaderFooter={showHeaderFooter}
              isExpanded={isExpanded}
              setIsExpanded={setIsExpanded}
              setShowLogin={setShowLogin}
              setActiveTab={setActiveTab}
            />
          )}
        </div>
      </div>
      {showHeaderFooter && <Footer activeTab={0} setActiveTab={setActiveTab} />}
    </div>
  );
};

export default HomeTabs;