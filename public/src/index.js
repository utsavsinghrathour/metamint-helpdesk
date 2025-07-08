// import ReactDOM from 'react-dom';


// if (document.getElementById('robodesk-plugin')) {
//   ReactDOM.render(<App />, document.getElementById('robodesk-plugin'));
// }

import { createRoot } from 'react-dom/client'; // Import createRoot
import App from './App.jsx';
const container = document.getElementById('robodesk-plugin');
if (container) {
  const root = createRoot(container); // Create root
  root.render(<App />); // Render your component
}
