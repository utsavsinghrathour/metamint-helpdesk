import React from 'react';
import FAQ from './FAQ.jsx'; // Adjust path as needed

const HelpTabs = ({ setActiveTab, setIsOpen, isExpanded, setIsExpanded, activeTab , handleFocusedTextArea }) => {
  return (
    <div className="help-tabs">
      <FAQ
        setActiveTab={setActiveTab}
        setIsOpen={setIsOpen}
        isExpanded={isExpanded}
        setIsExpanded={setIsExpanded}
        activeTab={activeTab} 
        handleFocusedTextArea={handleFocusedTextArea}
        // Pass activeTab to FAQ
      />
    </div>
  );
};

export default HelpTabs;