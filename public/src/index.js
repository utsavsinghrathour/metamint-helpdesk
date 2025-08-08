import { createRoot } from 'react-dom/client'; // Import createRoot
import App from './App.jsx';

// Get the localized data
const { imageUrls = { logo: '' } } = window.robodeskData || { imageUrls: {} };
// console.log('arpan4444', window.robodeskData);

if (document.getElementById('robodesk-plugin')) {
  const root = createRoot(document.getElementById('robodesk-plugin')); // Create root
  root.render(<App imageUrls={imageUrls} />); // Use root.render
}