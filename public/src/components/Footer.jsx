import { Tooltip } from 'react-tooltip';
import { FaQuestionCircle } from 'react-icons/fa';
import { RiInformation2Fill } from 'react-icons/ri';
import { RiMessage2Fill } from 'react-icons/ri';
import { GoHomeFill } from 'react-icons/go';
import '../styles/footer.css';
import { useAppData } from '../utils/AppDataContext';
import { useEffect, useState } from 'react';

export const mmhdShowCredits = () =>
  window.mmhdData?.show_credits === "1";

const Footer = ({
  activeTab,
  setActiveTab,
  showCopyrightOnly = false,
  updateSelectedTicket,
}) => {
  const { ticketsContext } = useAppData();

  const [showRedDot, setShowRedDot] = useState(false);

  const chatEnabled = window.mmhdData?.enable_chat !== "1" ? false : true;

  const showCredits = mmhdShowCredits();

  useEffect(() => {
    const hasNotification = ticketsContext?.some(
      (ticket) => ticket.notifications === 'no'
    );

    setShowRedDot(hasNotification);
  }, [ticketsContext]);

  const tabs = [
    { id: 0, icon: GoHomeFill, label: '', tooltip: 'Home' },
    ...(chatEnabled
      ? [{
          id: 1,
          icon: RiMessage2Fill,
          label: '',
          tooltip: 'Conversations',
          notification: showRedDot,
        }]
      : []),
    { id: 2, icon: RiInformation2Fill, label: '', tooltip: 'Notice Board' },
    { id: 3, icon: FaQuestionCircle, label: '', tooltip: 'Wiki' },
  ];
  const handlefooterClick = (tabId) => {
    setActiveTab(tabId);
    if (updateSelectedTicket) {
      updateSelectedTicket(null);
    }
  };
  if (showCopyrightOnly) {
    if (!showCredits) {
      return null;
    }
    return (
      <footer className='mmhd-footer'>
        <div className='mmhd-footer-text mmhd-text-center mmhd-py-10'>
          <h6 className='mmhd-m-0'>
            Powered by{' '}
            <a
              href='https://metamintapps.com/'
              target='_blank'
              className='mmhd-decoration-none'
            >
              <span className='mmhd-text-blue mmhd-decoration-none'>Metamint Helpdesk</span>
            </a>
          </h6>
        </div>
      </footer>
    );
  }

  return (
    <footer className='mmhd-footer'>
      <div
        className='tab-container mmhd-flex mmhd-justify-between test-class'
        role='tablist'
      >
        {tabs.map((tab) => (
          <button
            key={tab.id}
            role='tab'
            aria-selected={activeTab === tab.id}
            aria-controls={`tabpanel-${tab.id}`}
            id={`tab-${tab.id}`}
            className={`custom-svg mmhd-p-0 mmhd-flex mmhd-align-center mmhd-gap-5 mmhd-border-none mmhd-bg-transparent icon-dark mmhd-cursor-pointer  ${
              activeTab === tab.id ? 'active-tab' : ''
            } ${tab.notification ? 'mmhd-title-with-dot' : ''}`}
            onClick={() => {
              handlefooterClick(tab.id);
            }}
          >
            <tab.icon
              width={20}
              height={20}
              data-tooltip-id={`tooltip-${tab.id}`}
              data-tooltip-content={tab.tooltip}
              //   className=''
            />
            {tab.label}
            <Tooltip
              id={`tooltip-${tab.id}`}
              place='top'
              style={{
                backgroundColor: '#333',
                color: '#fff',
                borderRadius: '4px',
              }}
            />
          </button>
        ))}
      </div>
      {showCredits && (
        <div className='mmhd-footer-text mmhd-text-center mmhd-py-10'>
          <h6 className='mmhd-m-0'>
            Powered by{' '}
            <a
              href='https://metamintapps.com/'
              target='_blank'
              className='mmhd-decoration-none'
            >
              <span className='mmhd-text-blue mmhd-decoration-none'>Metamint Helpdesk</span>
            </a>
          </h6>
        </div>
      )}
    </footer>
  );
};

export default Footer;
