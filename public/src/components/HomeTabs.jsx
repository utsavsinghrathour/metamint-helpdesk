import React, { useState } from 'react';
import Header from './Header.jsx';
import Footer from './Footer.jsx';
import SendMessageField from './SendMessageField';
import SingleQuestionBox from './SingleQuestionBox';
import Login from './Login.jsx';
import '../styles/HomeTabs.css';

const HomeTabs = ({ setActiveTab, setIsOpen }) => {
  const [showLogin, setShowLogin] = useState(false);

  return (
    <div className="rd-flex rd-flex-col rd-h-full">
      {/* Header - Hidden when Login is shown */}
      {!showLogin && <Header onClose={() => setIsOpen(false)} />}

      {/* Main Content */}
      <div className="rd-flex-1 rd-overflow-auto">
        {showLogin ? (
          <Login />
        ) : (
          <>
            <div className="rd-text-center rd-px-20 rd-flex rd-flex-col rd-gap-5 rd-mb-20 home-tab-title">
              <h3 className="heading-3">Have Questions?</h3>
              <h5 className="sub-text">Have any questions or need a quote? Leave us a message.</h5>
            </div>
            <SendMessageField setShowLogin={setShowLogin} />
            <SingleQuestionBox />
          </>
        )}
      </div>

      {/* Footer - Always visible */}
      <Footer activeTab={0} setActiveTab={setActiveTab} />
    </div>
  );
};

export default HomeTabs;