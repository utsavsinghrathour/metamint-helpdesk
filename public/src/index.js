import React from 'react';
import ReactDOM from 'react-dom';
import App from './App.jsx';

// Get the localized data
const { imageUrls = { logo: '' } } = window.robodeskData || { imageUrls: {} };
// console.log('arpan333', robodeskData);
console.log('arpan4444', window.robodeskData);
if (document.getElementById('robodesk-plugin')) {
  ReactDOM.render(<App imageUrls={imageUrls} />, document.getElementById('robodesk-plugin'));
}