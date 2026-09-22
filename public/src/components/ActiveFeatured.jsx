import React, { useState, useEffect } from 'react';
import '../styles/ActiveFeatured.css';
import { FaTimes } from 'react-icons/fa';
import Bell from '../images/alarm-gif.gif';

// Get WordPress home URL from mmhdData
// const wpHomeUrl = window.mmhdData?.home_url || '';

const ActiveFeatured = ({ onNoticeSelect }) => {
  const [notices, setNotices] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [isVisible, setIsVisible] = useState(false);

  // Fetch active and featured notices
  useEffect(() => {
    const fetchNotices = async () => {
      try {
        setIsLoading(true);
        const response = await fetch(
          `/wp-json/wp/v2/notice?featured=true&active=true&_fields=id,title.rendered,link,content.rendered`
        );
        if (!response.ok) {
          throw new Error('Failed to fetch notices');
        }
        const data = await response.json();
        setNotices(data);
        setIsLoading(false);
      } catch (err) {
        setError(err.message);
        setIsLoading(false);
      }
    };

    fetchNotices();
  }, []);

  // Check sessionStorage to determine visibility
  useEffect(() => {
    const isPopupClosed = sessionStorage.getItem('popupClosed');
    if (!isPopupClosed && !isLoading && !error && notices.length > 0) {
      const timer = setTimeout(() => {
        setIsVisible(true);
      }, 1000); // 1-second delay

      return () => clearTimeout(timer); // Cleanup on unmount
    }
  }, [isLoading, error, notices.length]);

  const handleNoticeClick = (notice) => {
    onNoticeSelect(notice); // Pass the selected notice to App.jsx
  };

  const handleCloseAllNotices = () => {
    sessionStorage.setItem('popupClosed', 'true'); // Set flag in sessionStorage
    setIsVisible(false); // Hide the popup
    setNotices([]); // Clear notices
  };

  return (
    <div
      className={`mmhd-active-featured mmhd-position-relative ${
        !isVisible || isLoading || error || notices.length === 0
          ? 'mmhd-hidden'
          : ''
      } ${isVisible ? 'mmhd-fade-in' : ''}`}
    >
      {error ? (
        <p className='mmhd-text-red mmhd-body-text'>Error: {error}</p>
      ) : (
        notices.length > 0 && (
          <>
            <button
              className='mmhd-close-all-button mmhd-bg-transparent mmhd-border-none mmhd-cursor-pointer mmhd-text-gray-600 hover:mmhd-text-gray-800 mmhd-p-0'
              onClick={handleCloseAllNotices}
              title='Close all notices'
            >
              <FaTimes />
            </button>
            <div className='mmhd-flex mmhd-flex-col'>
              {notices.map((notice) => (
                <h5
                  key={notice.id}
                  className='mmhd-notice-title sub-text mmhd-cursor-pointer mmhd-p-10 mmhd-flex mmhd-align-center mmhd-gap-10'
                  onClick={() => handleNoticeClick(notice)}
                >
                  <div className='featured-notice-icon'>
                    <img src={Bell} alt='Bell icon' className='mmhd-bell-gif' />
                  </div>
                  {notice.title.rendered}
                </h5>
              ))}
            </div>
          </>
        )
      )}
    </div>
  );
};

export default ActiveFeatured;
