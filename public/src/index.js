import { createRoot } from 'react-dom/client'; // Import createRoot
import App from './App.js';

// Get the localized data
const { imageUrls = { logo: '' } } = window.robodeskData || { imageUrls: {} };

if (document.getElementById('robodesk-plugin')) {
  const root = createRoot(document.getElementById('robodesk-plugin')); // Create root
  root.render(<App imageUrls={imageUrls} />); // Use root.render
}
