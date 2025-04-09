import React, { useState } from 'react';
import '../styles/SendMessageField.css';

const SendMessageField = ({ setShowLogin }) => {
  const [message, setMessage] = useState('');

  const handleInputChange = (e) => {
    setMessage(e.target.value);
  };

  const handleSend = () => {
    if (message.trim()) {
      console.log('Sending message:', message);
      setMessage('');
      setShowLogin(true); // Trigger Login display
    }
  };

  return (
    <div className="send-message-field rd-px-18">
      <div className="rd-position-relative">
        <textarea
          className="rd-w-full rd-border-blue rd-border-radius-md"
          placeholder="Send us a message"
          value={message}
          onChange={handleInputChange}
          rows={1}
          style={{ minHeight: '42px', maxHeight: '200px' }}
          onKeyPress={(e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
              e.preventDefault();
              handleSend();
            }
          }}
        />
        <button
          onClick={handleSend}
          className="rd-bg-transparent rd-border-none rd-text-blue rd-p-0 rd-m-0"
          disabled={!message.trim()}
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="17" height="16" viewBox="0 0 17 16" fill="none">
            <path
              d="M15.3728 0.0985297L0.886843 8.45568C0.321158 8.78071 0.39304 9.5683 0.955601 9.80582L4.27783 11.1997L13.2569 3.28637C13.4288 3.13323 13.6726 3.36763 13.5257 3.54577L5.99677 12.7186V15.2345C5.99677 15.9721 6.88749 16.2628 7.32503 15.7283L9.30962 13.3124L13.2038 14.9439C13.6476 15.1314 14.1539 14.8532 14.2351 14.3751L16.4854 0.873613C16.5917 0.242295 15.9135 -0.214004 15.3728 0.0985297Z"
              fill="#004AFF"
            />
          </svg>
        </button>
      </div>
    </div>
  );
};

export default SendMessageField;
