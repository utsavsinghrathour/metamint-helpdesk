import React from 'react';
import ReactDOM from 'react-dom';
import App from './App.jsx';


if (document.getElementById('robodesk-plugin')) {
  ReactDOM.render(<App />, document.getElementById('robodesk-plugin'));
}