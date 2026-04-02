async function loadCheckin() {
  const el = document.getElementById('page-checkin');
  el.innerHTML = `
    <div class="row g-4">
      <!-- Check-in section -->
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-success text-white">
            <h6 class="mb-0"><i class="bi bi-box-arrow-in-right me-2"></i>Check-in hôm nay</h6>
          </div>
          <div class="card-body p-0" id="checkin-list">
            <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-success"></div></div>
          </div>
        </div>
      </div>
      <!-- Check-out section -->
      <div class="col-md-6">
        <div class="card shadow-sm h-100">
          <div class="card-header bg-warning text-dark">
            <h6 class="mb-0"><i class="bi bi-box-arrow-right me-2"></i>Check-out hôm nay</h6>
          </div>
          <div class="card-body p-0" id="checkout-list">
            <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-warning"></div></div>
          </div>
        </div>
      </div>
      <!-- Currently occupied -->
      <div class="col-12">
        <div class="card shadow-sm">
          <div class="card-header bg-danger text-white">
            <h6 class="mb-0"><i class="bi bi-person-fill me-2"></i>Phòng đang có khách</h6>
          </div>
          <div class="card-body p-0" id="occupied-list">
            <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-danger"></div></div>
          </div>
        </div>
      </div>
    </div>
  `;
  loadCheckinData();
}

async function loadCheckinData() {
  const today = new Date().toISOString().split('T')[0];

  const [arriving, departing, occupied] = await Promise.all([
    api('GET', `/bookings?status=confirmed`),
    api('GET', `/bookings?status=checked_in`),
    api('GET', `/bookings?status=checked_in`),
  ]);

  // Filter arriving today
  const arrivingToday = arriving.filter(b => b.check_in_date === today);

  // Filter departing today
  const departingToday = departing.filter(b => b.check_out_date === today);

  renderCheckinList(arrivingToday);
  renderCheckoutList(departingToday);
  renderOccupiedList(occupied);
}

function renderCheckinList(bookings) {
  const el = document.getElementById('checkin-list');
  if (!el) return;
  if (bookings.length === 0) {
    el.innerHTML = '<p class="text-center text-muted py-3">Không có khách check-in hôm nay</p>';
    return;
  }
  el.innerHTML = `
    <div class="list-group list-group-flush">
      ${bookings.map(b => `
        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold">${b.full_name}</div>
            <div class="text-muted small">Phòng ${b.room_number} (${b.type_name}) • ${b.phone}</div>
            <div class="text-muted small">Check-out: ${fmt.date(b.check_out_date)}</div>
          </div>
          <button class="btn btn-sm btn-success" onclick="performCheckin(${b.id})">
            <i class="bi bi-box-arrow-in-right me-1"></i>Check-in
          </button>
        </div>`).join('')}
    </div>`;
}

function renderCheckoutList(bookings) {
  const el = document.getElementById('checkout-list');
  if (!el) return;
  if (bookings.length === 0) {
    el.innerHTML = '<p class="text-center text-muted py-3">Không có khách check-out hôm nay</p>';
    return;
  }
  el.innerHTML = `
    <div class="list-group list-group-flush">
      ${bookings.filter(b => b.check_out_date === new Date().toISOString().split('T')[0]).map(b => `
        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold">${b.full_name}</div>
            <div class="text-muted small">Phòng ${b.room_number} • Check-in: ${fmt.date(b.check_in_date)}</div>
            <div class="fw-semibold text-primary small">${fmt.currency(b.total_amount)}</div>
          </div>
          <button class="btn btn-sm btn-warning" onclick="performCheckout(${b.id})">
            <i class="bi bi-box-arrow-right me-1"></i>Check-out
          </button>
        </div>`).join('') || '<p class="text-center text-muted py-3">Không có khách check-out hôm nay</p>'}
    </div>`;
}

function renderOccupiedList(bookings) {
  const el = document.getElementById('occupied-list');
  if (!el) return;
  if (bookings.length === 0) {
    el.innerHTML = '<p class="text-center text-muted py-3">Không có phòng nào đang có khách</p>';
    return;
  }
  el.innerHTML = `
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead><tr>
          <th class="ps-3">Phòng</th><th>Loại</th><th>Khách hàng</th>
          <th>Check-in</th><th>Check-out</th><th>Tổng tiền</th><th>Thao tác</th>
        </tr></thead>
        <tbody>
          ${bookings.map(b => `<tr>
            <td class="ps-3"><span class="badge bg-danger">${b.room_number}</span></td>
            <td class="small">${b.type_name}</td>
            <td>
              <div class="fw-semibold">${b.full_name}</div>
              <div class="text-muted small">${b.phone}</div>
            </td>
            <td class="small">${fmt.date(b.check_in_date)}</td>
            <td class="small">${fmt.date(b.check_out_date)}</td>
            <td class="text-primary fw-semibold">${fmt.currency(b.total_amount)}</td>
            <td>
              <button class="btn btn-sm btn-outline-info me-1" onclick="showBookingDetail(${b.id})">
                <i class="bi bi-eye"></i>
              </button>
              <button class="btn btn-sm btn-warning" onclick="performCheckout(${b.id})">
                <i class="bi bi-box-arrow-right"></i>
              </button>
            </td>
          </tr>`).join('')}
        </tbody>
      </table>
    </div>`;
}

async function performCheckin(id) {
  try {
    await api('POST', `/bookings/${id}/checkin`);
    showToast('Check-in thành công!', 'success');
    loadCheckinData();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function performCheckout(id) {
  try {
    const res = await api('POST', `/bookings/${id}/checkout`);
    showToast(`Check-out thành công! Tổng tiền: ${fmt.currency(res.total_amount)}`, 'success');
    loadCheckinData();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}
