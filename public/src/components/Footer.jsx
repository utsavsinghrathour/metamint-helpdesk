import { Tooltip } from 'react-tooltip';
import { FaQuestionCircle } from 'react-icons/fa';
import { RiInformation2Fill } from 'react-icons/ri';
import { RiMessage2Fill } from 'react-icons/ri';
import { GoHomeFill } from 'react-icons/go';
import '../styles/footer.css';
import { useAppData } from '../utils/AppDataContext';
import { useEffect, useState } from 'react';

const Footer = ({
  activeTab,
  setActiveTab,
  showCopyrightOnly = false,
  updateSelectedTicket,
}) => {
  const { ticketsContext } = useAppData();

  const [showRedDot, setShowRedDot] = useState(false);

  useEffect(() => {
    const hasNotification = ticketsContext?.some(
      (ticket) => ticket.notifications === 'no'
    );

    setShowRedDot(hasNotification);
  }, [ticketsContext]);

  const tabs = [
    { id: 0, icon: GoHomeFill, label: '', tooltip: 'Home' },
    {
      id: 1,
      icon: RiMessage2Fill,
      label: '',
      tooltip: 'Conversations',
      notification: showRedDot,
    },
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
    return (
      <footer className='robodesk-footer'>
        <div className='robodesk-footer-text rd-text-center rd-py-10'>
          <h6 className='rd-m-0'>
            Powered by{' '}
            <a
              href='https://robodesk.io/'
              target='_blank'
              className='rd-decoration-none'
            >
              <span className='rd-text-blue rd-decoration-none'>Robodesk</span>
            </a>
          </h6>
        </div>
      </footer>
    );
  }

  return (
    <footer className='robodesk-footer'>
      <div
        className='tab-container rd-flex rd-justify-between test-class'
        role='tablist'
      >
        {tabs.map((tab) => (
          <button
            key={tab.id}
            role='tab'
            aria-selected={activeTab === tab.id}
            aria-controls={`tabpanel-${tab.id}`}
            id={`tab-${tab.id}`}
            className={`custom-svg rd-p-0 rd-flex rd-align-center rd-gap-5 rd-border-none rd-bg-transparent icon-dark rd-cursor-pointer  ${
              activeTab === tab.id ? 'active-tab' : ''
            } ${tab.notification ? 'rd-title-with-dot' : ''}`}
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
      <div className='robodesk-footer-text rd-text-center rd-py-10'>
        <h6 className='rd-m-0'>
          Powered by{' '}
          <a
            href='http://robodesk.io/'
            target='_blank'
            className='rd-decoration-none'
          >
            <span className='rd-text-blue rd-decoration-none'>Robodesk</span>
          </a>
        </h6>
      </div>
    </footer>
  );
};

export default Footer;
