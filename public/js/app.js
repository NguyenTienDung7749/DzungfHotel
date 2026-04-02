const API = '/api';

// Utility functions
const fmt = {
  currency: (n) => new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(n || 0),
  date: (s) => s ? new Date(s).toLocaleDateString('vi-VN') : '',
  statusRoom: (s) => ({ available: 'Trống', occupied: 'Có khách', reserved: 'Đã đặt', maintenance: 'Bảo trì' }[s] || s),
  statusBooking: (s) => ({ pending: 'Chờ xác nhận', confirmed: 'Đã xác nhận', checked_in: 'Đang lưu trú', checked_out: 'Đã trả phòng', cancelled: 'Đã hủy' }[s] || s),
  badgeRoom: (s) => `<span class="badge badge-${s} px-2 py-1">${fmt.statusRoom(s)}</span>`,
  badgeBooking: (s) => `<span class="badge badge-${s} px-2 py-1">${fmt.statusBooking(s)}</span>`,
};

async function api(method, endpoint, data) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json' },
  };
  if (data) opts.body = JSON.stringify(data);
  const res = await fetch(API + endpoint, opts);
  const json = await res.json();
  if (!res.ok) throw new Error(json.error || 'Lỗi không xác định');
  return json;
}

function showToast(msg, type = 'success') {
  const id = 'toast-' + Date.now();
  const html = `
    <div id="${id}" class="toast align-items-center text-bg-${type} border-0" role="alert">
      <div class="d-flex">
        <div class="toast-body">${msg}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>`;
  document.getElementById('toast-container').insertAdjacentHTML('beforeend', html);
  const el = document.getElementById(id);
  new bootstrap.Toast(el, { delay: 3500 }).show();
  el.addEventListener('hidden.bs.toast', () => el.remove());
}

function showModal(title, body, footer = '') {
  const id = 'modal-' + Date.now();
  document.getElementById('modal-container').innerHTML = `
    <div class="modal fade" id="${id}" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">${title}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">${body}</div>
          ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
        </div>
      </div>
    </div>`;
  const modal = new bootstrap.Modal(document.getElementById(id));
  modal.show();
  return id;
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) bootstrap.Modal.getInstance(el)?.hide();
}

// Navigation
const pages = ['dashboard', 'rooms', 'customers', 'bookings', 'checkin', 'services'];
const pageTitles = {
  dashboard: 'Tổng quan',
  rooms: 'Quản lý phòng',
  customers: 'Quản lý khách hàng',
  bookings: 'Đặt phòng',
  checkin: 'Check-in / Check-out',
  services: 'Dịch vụ',
};

let currentPage = 'dashboard';

function navigateTo(page) {
  if (!pages.includes(page)) return;
  pages.forEach(p => {
    document.getElementById('page-' + p)?.classList.toggle('d-none', p !== page);
  });
  document.querySelectorAll('.sidebar-link').forEach(l => {
    l.classList.toggle('active', l.dataset.page === page);
  });
  document.getElementById('page-title').textContent = pageTitles[page] || page;
  currentPage = page;

  // Load page content
  switch (page) {
    case 'dashboard': loadDashboard(); break;
    case 'rooms': loadRooms(); break;
    case 'customers': loadCustomers(); break;
    case 'bookings': loadBookings(); break;
    case 'checkin': loadCheckin(); break;
    case 'services': loadServices(); break;
  }
}

document.addEventListener('DOMContentLoaded', () => {
  // Set date
  document.getElementById('current-date').textContent =
    new Date().toLocaleDateString('vi-VN', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

  // Nav links
  document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      navigateTo(link.dataset.page);
    });
  });

  // Sidebar toggle (mobile)
  document.getElementById('toggle-sidebar')?.addEventListener('click', () => {
    document.getElementById('sidebar').classList.toggle('open');
  });

  // Load default page
  navigateTo('dashboard');
});
