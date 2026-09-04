import { createRoot } from 'react-dom/client'; // Import createRoot
import App from './App.js';

// Get the localized data
const { imageUrls = { logo: '' } } = window.mmhdData || { imageUrls: {} };

if (document.getElementById('mmhd-plugin')) {
  const root = createRoot(document.getElementById('mmhd-plugin')); // Create root
  root.render(<App imageUrls={imageUrls} />); // Use root.render
}
