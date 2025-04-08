import React from 'react';
import '../styles/footer.css';

// Home Icon Component
const HomeIcon = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="24"
    height="22"
    viewBox="0 0 24 22"
    fill="none"
    {...props}
  >
    <path
      d="M23.9917 10.9792C23.9917 11.7292 23.3667 12.3167 22.6583 12.3167H21.325L21.3542 18.9917C21.3542 19.1042 21.3458 19.2167 21.3333 19.3292V20C21.3333 20.9208 20.5875 21.6667 19.6667 21.6667H19C18.9542 21.6667 18.9083 21.6667 18.8625 21.6625C18.8042 21.6667 18.7458 21.6667 18.6875 21.6667H17.3333H16.3333C15.4125 21.6667 14.6667 20.9208 14.6667 20V19V16.3333C14.6667 15.5958 14.0708 15 13.3333 15H10.6667C9.92917 15 9.33333 15.5958 9.33333 16.3333V19V20C9.33333 20.9208 8.5875 21.6667 7.66667 21.6667H6.66667H5.3375C5.275 21.6667 5.2125 21.6625 5.15 21.6583C5.1 21.6625 5.05 21.6667 5 21.6667H4.33333C3.4125 21.6667 2.66667 20.9208 2.66667 20V15.3333C2.66667 15.2958 2.66667 15.2542 2.67083 15.2167V12.3167H1.33333C0.583333 12.3167 0 11.7333 0 10.9792C0 10.6042 0.125 10.2708 0.416667 9.97916L11.1 0.666662C11.3917 0.374995 11.725 0.333328 12.0167 0.333328C12.3083 0.333328 12.6417 0.416662 12.8917 0.624995L23.5333 9.97916C23.8667 10.2708 24.0333 10.6042 23.9917 10.9792Z"
      fill="#333333"
    />
  </svg>
);

// Message Icon Component
const MessageIcon = (props) => (
  <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
<g clip-path="url(#clip0_139_116)">
<path d="M2.95834 0.5C1.51049 0.5 0.333344 1.67715 0.333344 3.125V14.9375C0.333344 16.3854 1.51049 17.5625 2.95834 17.5625H6.89584V20.8438C6.89584 21.0939 7.0353 21.3195 7.25678 21.4303C7.47827 21.541 7.74487 21.5164 7.94584 21.3687L13.0195 17.5625H18.7083C20.1562 17.5625 21.3333 16.3854 21.3333 14.9375V3.125C21.3333 1.67715 20.1562 0.5 18.7083 0.5H2.95834Z" fill="#333333"/>
</g>
<defs>
<clipPath id="clip0_139_116">
<rect width="21" height="21" fill="white" transform="translate(0.333344 0.5)"/>
</clipPath>
</defs>
</svg>
);

// Bell Icon Component
const BellIcon = (props) => (
  <svg xmlns="http://www.w3.org/2000/svg" width="25" height="24" viewBox="0 0 25 24" fill="none">
<path d="M23.1667 1.49998C23.1667 0.895297 22.801 0.34686 22.2385 0.112485C21.676 -0.12189 21.0339 0.00936 20.6026 0.435923L18.5589 2.48436C16.3089 4.73436 13.2573 5.99998 10.0745 5.99998H9.66667H8.16667H3.66667C2.01198 5.99998 0.666672 7.3453 0.666672 8.99998V13.5C0.666672 15.1547 2.01198 16.5 3.66667 16.5V22.5C3.66667 23.3297 4.33698 24 5.16667 24H8.16667C8.99636 24 9.66667 23.3297 9.66667 22.5V16.5H10.0745C13.2573 16.5 16.3089 17.7656 18.5589 20.0156L20.6026 22.0594C21.0339 22.4906 21.676 22.6172 22.2385 22.3828C22.801 22.1484 23.1667 21.6047 23.1667 20.9953V14.0812C24.0385 13.6687 24.6667 12.5578 24.6667 11.25C24.6667 9.94217 24.0385 8.83123 23.1667 8.41874V1.49998ZM20.1667 5.0953V11.25V17.4047C17.4104 14.8969 13.8151 13.5 10.0745 13.5H9.66667V8.99998H10.0745C13.8151 8.99998 17.4104 7.60311 20.1667 5.0953Z" fill="#333333"/>
</svg>
);

// Question Circle Icon Component
const QuestionCircleIcon = (props) => (
  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
<g clip-path="url(#clip0_139_120)">
<path d="M23.625 12C23.625 18.4217 18.4199 23.625 12 23.625C5.58014 23.625 0.375 18.4217 0.375 12C0.375 5.58202 5.58014 0.375 12 0.375C18.4199 0.375 23.625 5.58202 23.625 12ZM12.312 4.21875C9.75741 4.21875 8.12813 5.29486 6.84872 7.20741C6.68297 7.45519 6.73842 7.78936 6.97598 7.9695L8.6025 9.20278C8.84648 9.3878 9.19411 9.34378 9.38367 9.10331C10.221 8.04122 10.7952 7.42533 12.0697 7.42533C13.0274 7.42533 14.2118 8.04164 14.2118 8.97023C14.2118 9.67223 13.6323 10.0328 12.6868 10.5629C11.5841 11.181 10.125 11.9504 10.125 13.875V14.0625C10.125 14.3731 10.3769 14.625 10.6875 14.625H13.3125C13.6231 14.625 13.875 14.3731 13.875 14.0625V14C13.875 12.6659 17.7743 12.6103 17.7743 9C17.7743 6.28116 14.9541 4.21875 12.312 4.21875ZM12 15.8438C10.811 15.8438 9.84375 16.811 9.84375 18C9.84375 19.1889 10.811 20.1562 12 20.1562C13.189 20.1562 14.1562 19.1889 14.1562 18C14.1562 16.811 13.189 15.8438 12 15.8438Z" fill="#333333"/>
</g>
<defs>
<clipPath id="clip0_139_120">
<rect width="24" height="24" fill="white"/>
</clipPath>
</defs>
</svg>
);

const Footer = ({ activeTab, setActiveTab, showCopyrightOnly = false }) => {
  const tabs = [
    { id: 0, icon: HomeIcon, label: '' }, // Home
    { id: 1, icon: MessageIcon, label: '' }, // Messages
    { id: 2, icon: BellIcon, label: '' }, // Notices
    { id: 3, icon: QuestionCircleIcon, label: '' }, // Help/FAQ
  ];

  if (showCopyrightOnly) {
    return (
      <footer className="robodesk-footer">
        <div className="robodesk-footer-text rd-text-center rd-py-10">
          <h6 className="rd-m-0">
            Powered by <span className="rd-text-blue">Robodesk</span>
          </h6>
        </div>
      </footer>
    );
  }

  return (
    <footer className="robodesk-footer">
      <div className="tab-container rd-flex rd-justify-between" role="tablist">
        {tabs.map((tab) => (
          <button
            key={tab.id}
            role="tab"
            aria-selected={activeTab === tab.id}
            aria-controls={`tabpanel-${tab.id}`}
            id={`tab-${tab.id}`}
            className={`rd-flex rd-align-center rd-gap-5 rd-border-none rd-bg-transparent icon-dark rd-cursor-pointer ${
              activeTab === tab.id ? 'active-tab' : ''
            }`}
            onClick={() => setActiveTab(tab.id)}
          >
            <tab.icon size={20} />
            {tab.label}
          </button>
        ))}
      </div>
      <div className="robodesk-footer-text rd-text-center rd-py-10">
        <h6 className="rd-m-0">
          Powered by <span className="rd-text-blue">Robodesk</span>
        </h6>
      </div>
    </footer>
  );
};

export default Footer;