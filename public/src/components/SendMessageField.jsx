import React, { useState } from 'react';
import { IoIosSend } from "react-icons/io";
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
      <div className='rd-position-relative'>
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
          className="rd-bg-transparent rd-border-none rd-text-blue"
          disabled={!message.trim()}
        >
          <IoIosSend/>
        </button>
      </div>
    </div>
  );
};

export default SendMessageField;