async function loadBookings() {
  const el = document.getElementById('page-bookings');
  el.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Quản lý đặt phòng</h5>
      <button class="btn btn-primary btn-sm" onclick="showBookingForm()">
        <i class="bi bi-calendar-plus me-1"></i>Tạo đặt phòng
      </button>
    </div>
    <div class="filter-bar mb-3 d-flex flex-wrap gap-2">
      <select id="filter-booking-status" class="form-select form-select-sm" style="width:auto" onchange="renderBookings()">
        <option value="">Tất cả trạng thái</option>
        <option value="pending">Chờ xác nhận</option>
        <option value="confirmed">Đã xác nhận</option>
        <option value="checked_in">Đang lưu trú</option>
        <option value="checked_out">Đã trả phòng</option>
        <option value="cancelled">Đã hủy</option>
      </select>
    </div>
    <div id="bookings-table"></div>
  `;
  await renderBookings();
}

async function renderBookings() {
  const status = document.getElementById('filter-booking-status')?.value || '';
  const bookings = await api('GET', `/bookings${status ? `?status=${status}` : ''}`);
  const el = document.getElementById('bookings-table');
  if (!el) return;
  el.innerHTML = `
    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr>
            <th class="ps-3">#</th>
            <th>Khách hàng</th>
            <th>Phòng</th>
            <th>Check-in</th>
            <th>Check-out</th>
            <th>Trạng thái</th>
            <th>Tổng tiền</th>
            <th>Thao tác</th>
          </tr></thead>
          <tbody>
            ${bookings.map(b => `<tr>
              <td class="ps-3 text-muted small">#${b.id}</td>
              <td>
                <div class="fw-semibold">${b.full_name}</div>
                <div class="text-muted small">${b.phone}</div>
              </td>
              <td>
                <span class="badge bg-light text-dark border">${b.room_number}</span>
                <div class="text-muted small">${b.type_name}</div>
              </td>
              <td class="small">${fmt.date(b.check_in_date)}</td>
              <td class="small">${fmt.date(b.check_out_date)}</td>
              <td>${fmt.badgeBooking(b.status)}</td>
              <td class="fw-semibold text-primary">${fmt.currency(b.total_amount)}</td>
              <td>
                <button class="btn btn-sm btn-outline-info me-1" onclick="showBookingDetail(${b.id})" title="Chi tiết">
                  <i class="bi bi-eye"></i>
                </button>
                ${b.status === 'confirmed' || b.status === 'pending' ? `
                  <button class="btn btn-sm btn-outline-primary me-1" onclick="showBookingForm(${b.id})" title="Sửa">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" onclick="cancelBooking(${b.id})" title="Hủy">
                    <i class="bi bi-x-circle"></i>
                  </button>
                ` : ''}
              </td>
            </tr>`).join('') || '<tr><td colspan="8" class="text-center py-4 text-muted">Không có đặt phòng nào</td></tr>'}
          </tbody>
        </table>
      </div>
      <div class="card-footer text-muted small">Tổng: ${bookings.length} đặt phòng</div>
    </div>`;
}

async function showBookingForm(id = null) {
  const [customers, rooms] = await Promise.all([
    api('GET', '/customers'),
    api('GET', '/rooms?status=available'),
  ]);
  let b = { adults: 1, children: 0 };
  if (id) {
    b = await api('GET', `/bookings/${id}`);
    // Also include the current room in options even if not 'available'
    if (b.room_id && !rooms.find(r => r.id === b.room_id)) {
      const cur = await api('GET', `/rooms/${b.room_id}`);
      rooms.unshift(cur);
    }
  }

  const today = new Date().toISOString().split('T')[0];
  const tomorrow = new Date(Date.now() + 86400000).toISOString().split('T')[0];

  const body = `
    <form id="booking-form">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Khách hàng <span class="text-danger">*</span></label>
          <select name="customer_id" class="form-select" required>
            <option value="">-- Chọn khách hàng --</option>
            ${customers.map(c => `<option value="${c.id}" ${b.customer_id==c.id?'selected':''}>${c.full_name} - ${c.phone}</option>`).join('')}
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Phòng <span class="text-danger">*</span></label>
          <select name="room_id" class="form-select" required onchange="updateBookingPrice(this)">
            <option value="">-- Chọn phòng --</option>
            ${rooms.map(r => `<option value="${r.id}" data-price="${r.price_per_night}" ${b.room_id==r.id?'selected':''}>${r.room_number} - ${r.type_name} (${fmt.currency(r.price_per_night)}/đêm)</option>`).join('')}
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Ngày nhận phòng <span class="text-danger">*</span></label>
          <input name="check_in_date" type="date" class="form-control" value="${b.check_in_date || today}" required min="${today}" onchange="updateBookingPrice()" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Ngày trả phòng <span class="text-danger">*</span></label>
          <input name="check_out_date" type="date" class="form-control" value="${b.check_out_date || tomorrow}" required onchange="updateBookingPrice()" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Người lớn</label>
          <input name="adults" type="number" class="form-control" value="${b.adults || 1}" min="1" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Trẻ em</label>
          <input name="children" type="number" class="form-control" value="${b.children || 0}" min="0" />
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Yêu cầu đặc biệt</label>
          <textarea name="special_requests" class="form-control" rows="2">${b.special_requests || ''}</textarea>
        </div>
        <div class="col-12">
          <div class="alert alert-info mb-0" id="booking-price-preview">
            Chọn phòng và ngày để xem dự toán chi phí
          </div>
        </div>
      </div>
    </form>`;
  const footer = `
    <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
    <button class="btn btn-primary" onclick="submitBookingForm(${id || 'null'})">
      <i class="bi bi-save me-1"></i>${id ? 'Cập nhật' : 'Tạo đặt phòng'}
    </button>`;
  showModal(id ? 'Sửa đặt phòng' : 'Tạo đặt phòng mới', body, footer);
  if (b.room_id) updateBookingPrice();
}

function updateBookingPrice() {
  const form = document.getElementById('booking-form');
  if (!form) return;
  const roomSel = form.querySelector('[name="room_id"]');
  const selectedOpt = roomSel?.options[roomSel.selectedIndex];
  const price = parseFloat(selectedOpt?.dataset.price);
  const ci = form.querySelector('[name="check_in_date"]')?.value;
  const co = form.querySelector('[name="check_out_date"]')?.value;
  const preview = document.getElementById('booking-price-preview');
  if (!preview) return;
  if (price && ci && co) {
    const nights = Math.max(1, Math.round((new Date(co) - new Date(ci)) / 86400000));
    const total = nights * price;
    preview.innerHTML = `<i class="bi bi-calculator me-1"></i>
      <strong>${nights} đêm</strong> × ${fmt.currency(price)} = 
      <strong class="text-primary">${fmt.currency(total)}</strong>`;
  } else {
    preview.textContent = 'Chọn phòng và ngày để xem dự toán chi phí';
  }
}

async function submitBookingForm(id) {
  const form = document.getElementById('booking-form');
  if (!form.checkValidity()) { form.reportValidity(); return; }
  const data = Object.fromEntries(new FormData(form));
  data.adults = parseInt(data.adults);
  data.children = parseInt(data.children);
  try {
    if (id) {
      await api('PUT', `/bookings/${id}`, data);
      showToast('Cập nhật đặt phòng thành công');
    } else {
      await api('POST', '/bookings', data);
      showToast('Tạo đặt phòng thành công');
    }
    document.querySelector('[data-bs-dismiss="modal"]')?.click();
    renderBookings();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function cancelBooking(id) {
  if (!confirm('Hủy đặt phòng này?')) return;
  try {
    await api('POST', `/bookings/${id}/cancel`);
    showToast('Hủy đặt phòng thành công');
    renderBookings();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function showBookingDetail(id) {
  const b = await api('GET', `/bookings/${id}`);
  const nights = Math.max(1, Math.round((new Date(b.check_out_date) - new Date(b.check_in_date)) / 86400000));
  const roomCost = nights * b.price_per_night;
  const svcCost = b.services.reduce((s, x) => s + x.quantity * x.unit_price, 0);

  const body = `
    <div class="row g-3">
      <div class="col-md-6">
        <div class="card bg-light">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-person me-2 text-primary"></i>Thông tin khách</h6>
            <div class="mb-1"><strong>${b.full_name}</strong></div>
            <div class="text-muted small">CMND: ${b.id_card}</div>
            <div class="text-muted small">ĐT: ${b.phone}</div>
            ${b.email ? `<div class="text-muted small">Email: ${b.email}</div>` : ''}
            ${b.nationality ? `<div class="text-muted small">Quốc tịch: ${b.nationality}</div>` : ''}
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card bg-light">
          <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-door-open me-2 text-primary"></i>Thông tin phòng</h6>
            <div class="mb-1"><strong>Phòng ${b.room_number}</strong> - ${b.type_name}</div>
            <div class="text-muted small">Tầng ${b.floor}</div>
            <div class="text-muted small">Check-in: ${fmt.date(b.check_in_date)}</div>
            <div class="text-muted small">Check-out: ${fmt.date(b.check_out_date)}</div>
            <div class="text-muted small">Số đêm: ${nights}</div>
            <div class="text-muted small">Khách: ${b.adults} người lớn, ${b.children} trẻ em</div>
          </div>
        </div>
      </div>
      ${b.special_requests ? `
        <div class="col-12">
          <div class="alert alert-warning py-2">
            <i class="bi bi-star me-1"></i><strong>Yêu cầu đặc biệt:</strong> ${b.special_requests}
          </div>
        </div>` : ''}
      ${b.services.length > 0 ? `
        <div class="col-12">
          <h6 class="fw-bold mb-2"><i class="bi bi-stars me-2 text-primary"></i>Dịch vụ đã dùng</h6>
          <table class="table table-sm">
            <thead><tr><th>Dịch vụ</th><th>SL</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead>
            <tbody>${b.services.map(s => `<tr>
              <td>${s.service_name}</td>
              <td>${s.quantity} ${s.unit}</td>
              <td>${fmt.currency(s.unit_price)}</td>
              <td>${fmt.currency(s.quantity * s.unit_price)}</td>
            </tr>`).join('')}</tbody>
          </table>
        </div>` : ''}
      <div class="col-12">
        <div class="invoice-header">
          <h6 class="mb-3 text-white"><i class="bi bi-receipt me-2"></i>Hóa đơn tạm tính</h6>
          <div class="d-flex justify-content-between text-white-75 mb-1">
            <span>Tiền phòng (${nights} đêm × ${fmt.currency(b.price_per_night)})</span>
            <span>${fmt.currency(roomCost)}</span>
          </div>
          ${svcCost > 0 ? `<div class="d-flex justify-content-between text-white-75 mb-1">
            <span>Dịch vụ</span><span>${fmt.currency(svcCost)}</span>
          </div>` : ''}
          <hr class="border-white-50" />
          <div class="d-flex justify-content-between fw-bold text-white fs-5">
            <span>Tổng cộng</span><span>${fmt.currency(b.total_amount)}</span>
          </div>
        </div>
        <div class="invoice-body">
          <div class="d-flex justify-content-between">
            <span>Trạng thái:</span>${fmt.badgeBooking(b.status)}
          </div>
          <div class="d-flex justify-content-between mt-1">
            <span>Thanh toán:</span>
            <span class="badge ${b.payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark'}">
              ${b.payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán'}
            </span>
          </div>
        </div>
      </div>
    </div>`;

  const footer = `
    ${b.status === 'confirmed' || b.status === 'pending' ? `
      <button class="btn btn-success" onclick="doCheckin(${b.id})">
        <i class="bi bi-box-arrow-in-right me-1"></i>Check-in
      </button>` : ''}
    ${b.status === 'checked_in' ? `
      <button class="btn btn-warning" onclick="doCheckout(${b.id})">
        <i class="bi bi-box-arrow-right me-1"></i>Check-out
      </button>` : ''}`;

  showModal(`Chi tiết đặt phòng #${id}`, body, footer);
}

async function doCheckin(id) {
  try {
    await api('POST', `/bookings/${id}/checkin`);
    showToast('Check-in thành công!', 'success');
    document.querySelector('[data-bs-dismiss="modal"]')?.click();
    renderBookings();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function doCheckout(id) {
  try {
    const res = await api('POST', `/bookings/${id}/checkout`);
    showToast(`Check-out thành công! Tổng tiền: ${fmt.currency(res.total_amount)}`, 'success');
    document.querySelector('[data-bs-dismiss="modal"]')?.click();
    renderBookings();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}
