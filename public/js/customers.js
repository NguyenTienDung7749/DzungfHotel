async function loadCustomers() {
  const el = document.getElementById('page-customers');
  el.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Danh sách khách hàng</h5>
      <button class="btn btn-primary btn-sm" onclick="showCustomerForm()">
        <i class="bi bi-person-plus me-1"></i>Thêm khách hàng
      </button>
    </div>
    <div class="filter-bar mb-3">
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input id="search-customer" type="text" class="form-control" placeholder="Tìm theo tên, CMND, điện thoại, email..." oninput="searchCustomers(this.value)" />
      </div>
    </div>
    <div id="customers-table"></div>
  `;
  await renderCustomers('');
}

async function renderCustomers(search) {
  const customers = await api('GET', `/customers${search ? `?search=${encodeURIComponent(search)}` : ''}`);
  const el = document.getElementById('customers-table');
  if (!el) return;
  el.innerHTML = `
    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr>
            <th class="ps-3">#</th>
            <th>Họ và tên</th>
            <th>CMND/CCCD</th>
            <th>Điện thoại</th>
            <th>Email</th>
            <th>Quốc tịch</th>
            <th>Ngày tạo</th>
            <th>Thao tác</th>
          </tr></thead>
          <tbody>
            ${customers.map(c => `<tr>
              <td class="ps-3 text-muted small">${c.id}</td>
              <td>
                <div class="fw-semibold">${c.full_name}</div>
                <div class="text-muted small">${c.address || ''}</div>
              </td>
              <td class="font-monospace small">${c.id_card}</td>
              <td>${c.phone}</td>
              <td class="small">${c.email || '-'}</td>
              <td><span class="badge bg-light text-dark border">${c.nationality}</span></td>
              <td class="small text-muted">${c.created_at ? c.created_at.split(' ')[0] : ''}</td>
              <td>
                <button class="btn btn-sm btn-outline-info me-1" onclick="showCustomerHistory(${c.id}, ${JSON.stringify(c.full_name)})">
                  <i class="bi bi-clock-history"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="showCustomerForm(${c.id})">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteCustomer(${c.id}, ${JSON.stringify(c.full_name)})">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>`).join('') || '<tr><td colspan="8" class="text-center py-4 text-muted">Không có khách hàng nào</td></tr>'}
          </tbody>
        </table>
      </div>
      <div class="card-footer text-muted small">Tổng: ${customers.length} khách hàng</div>
    </div>`;
}

let searchTimer;
function searchCustomers(val) {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => renderCustomers(val), 300);
}

async function showCustomerForm(id = null) {
  let c = {};
  if (id) c = await api('GET', `/customers/${id}`);
  const body = `
    <form id="customer-form">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
          <input name="full_name" class="form-control" value="${c.full_name || ''}" required />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">CMND/CCCD <span class="text-danger">*</span></label>
          <input name="id_card" class="form-control" value="${c.id_card || ''}" required />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Điện thoại <span class="text-danger">*</span></label>
          <input name="phone" class="form-control" value="${c.phone || ''}" required />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Email</label>
          <input name="email" type="email" class="form-control" value="${c.email || ''}" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Quốc tịch</label>
          <input name="nationality" class="form-control" value="${c.nationality || 'Việt Nam'}" />
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Địa chỉ</label>
          <textarea name="address" class="form-control" rows="2">${c.address || ''}</textarea>
        </div>
      </div>
    </form>`;
  const footer = `
    <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
    <button class="btn btn-primary" onclick="submitCustomerForm(${id || 'null'})">
      <i class="bi bi-save me-1"></i>${id ? 'Cập nhật' : 'Thêm mới'}
    </button>`;
  showModal(id ? 'Chỉnh sửa khách hàng' : 'Thêm khách hàng', body, footer);
}

async function submitCustomerForm(id) {
  const form = document.getElementById('customer-form');
  if (!form.checkValidity()) { form.reportValidity(); return; }
  const data = Object.fromEntries(new FormData(form));
  try {
    if (id) {
      await api('PUT', `/customers/${id}`, data);
      showToast('Cập nhật khách hàng thành công');
    } else {
      await api('POST', '/customers', data);
      showToast('Thêm khách hàng thành công');
    }
    document.querySelector('[data-bs-dismiss="modal"]')?.click();
    renderCustomers(document.getElementById('search-customer')?.value || '');
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function deleteCustomer(id, name) {
  if (!confirm(`Xóa khách hàng "${name}"?`)) return;
  try {
    await api('DELETE', `/customers/${id}`);
    showToast('Xóa khách hàng thành công');
    renderCustomers(document.getElementById('search-customer')?.value || '');
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function showCustomerHistory(id, name) {
  const bookings = await api('GET', `/customers/${id}/bookings`);
  const body = `
    <h6 class="text-muted mb-3">Lịch sử đặt phòng của: <strong>${name}</strong></h6>
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead><tr>
          <th>#</th><th>Phòng</th><th>Loại</th><th>Check-in</th><th>Check-out</th><th>Trạng thái</th><th>Tổng tiền</th>
        </tr></thead>
        <tbody>
          ${bookings.map(b => `<tr>
            <td>#${b.id}</td>
            <td>${b.room_number}</td>
            <td>${b.type_name}</td>
            <td>${fmt.date(b.check_in_date)}</td>
            <td>${fmt.date(b.check_out_date)}</td>
            <td>${fmt.badgeBooking(b.status)}</td>
            <td class="text-primary">${fmt.currency(b.total_amount)}</td>
          </tr>`).join('') || '<tr><td colspan="7" class="text-center py-3 text-muted">Chưa có lịch sử</td></tr>'}
        </tbody>
      </table>
    </div>`;
  showModal(`Lịch sử đặt phòng`, body);
}
