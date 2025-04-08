import React from 'react';
import Header from './Header.jsx'; // Adjust the path if needed
import Footer from './Footer.jsx'; // Adjust the path if needed

const NoticeTabs = ({ setActiveTab, setIsOpen }) => {
  return (
    <div>
      {/* Header */}
      <Header onClose={() => setIsOpen(false)} />
        <div>
          <h4>This is notice tab</h4>
        </div>
      <Footer activeTab={2} setActiveTab={setActiveTab} />
    </div>
  );
};

export default NoticeTabs;
