import './bootstrap';
import './echo';       
import './notifications';
import '@fortawesome/fontawesome-free/css/all.min.css';
import Alpine from 'alpinejs';
import './borrow-requests'; 
import './my-borrowed-items'; 
import './return-requests';
import './borrowList.js';
import './return-items';
import './admin-dashboard';
import './property-number';
import './items-add-modal';
import './items-edit-modal';
import '../css/admin-dashboard.css';
import './user-dashboard';
import './reports';
import './qty-controls';
import '../css/borrow-list.css';
import '../css/return-selected-items.css';

window.Alpine = Alpine;

Alpine.start();

// --- Real-time admin events helper ---
// Dispatches custom DOM events so individual modules can react (refresh, highlight rows, etc.)
document.addEventListener('DOMContentLoaded', () => {
  try {
    const role = document.querySelector('meta[name="user-role"]')?.getAttribute('content') || '';
    if (String(role).toLowerCase() !== 'admin') return;
    if (!window.Echo) {
      console.warn('Echo not initialized yet.');
      return;
    }

    const adminChannel = window.Echo.private('admin');

    // New borrow request submitted
    adminChannel.listen('.BorrowRequestSubmitted', (payload) => {
      // payload may come wrapped in `.data` depending on server; normalize:
      const data = (payload && payload.data) ? payload.data : payload;
      window.dispatchEvent(new CustomEvent('realtime:borrow-request-submitted', { detail: data }));
    });

    // Borrow request status updated
    adminChannel.listen('.BorrowRequestStatusUpdated', (payload) => {
      const data = (payload && payload.data) ? payload.data : payload;
      window.dispatchEvent(new CustomEvent('realtime:borrow-request-status-updated', { detail: data }));
    });

  } catch (err) {
    console.error('Admin realtime wiring failed', err);
  }
});







