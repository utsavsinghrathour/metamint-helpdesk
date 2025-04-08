import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { IoIosSend } from "react-icons/io";
import '../styles/SendMessageField.css';

const SendMessageField = () => {
  const [message, setMessage] = useState('');
  const navigate = useNavigate();
  const isLoggedIn = false; // Replace with actual auth logic (e.g., context, token check)

  const handleInputChange = (e) => {
    setMessage(e.target.value);
  };

  const handleSend = () => {
    if (!isLoggedIn) {
      navigate('/login'); // Redirect to Login.jsx if not logged in
      return;
    }
    if (message.trim()) {
      console.log('Sending message:', message);
      setMessage('');
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
          <IoIosSend />
        </button>
      </div>
    </div>
  );
};

export default SendMessageField;