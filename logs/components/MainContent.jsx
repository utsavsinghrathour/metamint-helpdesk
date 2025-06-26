import React from 'react';

const MainContent = ({ activeContent }) => {
  return (
    <div className="main-content" role="tabpanel">
      {activeContent}
    </div>
  );
};

export default MainContent;
