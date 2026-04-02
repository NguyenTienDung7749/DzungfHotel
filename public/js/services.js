async function loadServices() {
  const el = document.getElementById('page-services');
  el.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Quản lý dịch vụ</h5>
      <button class="btn btn-primary btn-sm" onclick="showServiceForm()">
        <i class="bi bi-plus-lg me-1"></i>Thêm dịch vụ
      </button>
    </div>
    <div id="services-content"></div>
  `;
  await renderServices();
}

async function renderServices() {
  const services = await api('GET', '/services');
  const el = document.getElementById('services-content');
  if (!el) return;

  // Group by category
  const groups = {};
  services.forEach(s => {
    if (!groups[s.category]) groups[s.category] = [];
    groups[s.category].push(s);
  });

  el.innerHTML = Object.entries(groups).map(([cat, items]) => `
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-tag me-2 text-primary"></i>${cat}</span>
        <span class="badge bg-primary rounded-pill">${items.length}</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead><tr>
            <th class="ps-3">Tên dịch vụ</th>
            <th>Đơn giá</th>
            <th>Đơn vị</th>
            <th>Thao tác</th>
          </tr></thead>
          <tbody>
            ${items.map(s => `<tr>
              <td class="ps-3 fw-semibold">${s.name}</td>
              <td class="text-primary">${fmt.currency(s.unit_price)}</td>
              <td><span class="badge bg-light text-dark border">${s.unit}</span></td>
              <td>
                <button class="btn btn-sm btn-outline-primary me-1" onclick="showServiceForm(${s.id})">
                  <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteService(${s.id}, ${JSON.stringify(s.name)})">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>`).join('')}
          </tbody>
        </table>
      </div>
    </div>`).join('') || '<div class="text-center text-muted py-4">Chưa có dịch vụ nào</div>';
}

async function showServiceForm(id = null) {
  let s = { unit: 'lần' };
  if (id) {
    const services = await api('GET', '/services');
    s = services.find(x => x.id == id) || s;
  }
  const body = `
    <form id="service-form">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Tên dịch vụ <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="${s.name || ''}" required />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Danh mục <span class="text-danger">*</span></label>
          <input name="category" class="form-control" value="${s.category || ''}" required list="categories-list" />
          <datalist id="categories-list">
            <option value="Ăn uống">
            <option value="Dịch vụ phòng">
            <option value="Vận chuyển">
            <option value="Giải trí">
            <option value="Spa & Làm đẹp">
          </datalist>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Đơn giá (VNĐ) <span class="text-danger">*</span></label>
          <input name="unit_price" type="number" class="form-control" value="${s.unit_price || ''}" required min="0" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Đơn vị</label>
          <input name="unit" class="form-control" value="${s.unit || 'lần'}" list="units-list" />
          <datalist id="units-list">
            <option value="lần">
            <option value="người">
            <option value="ngày">
            <option value="kg">
            <option value="đêm">
          </datalist>
        </div>
      </div>
    </form>`;
  const footer = `
    <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
    <button class="btn btn-primary" onclick="submitServiceForm(${id || 'null'})">
      <i class="bi bi-save me-1"></i>${id ? 'Cập nhật' : 'Thêm dịch vụ'}
    </button>`;
  showModal(id ? 'Sửa dịch vụ' : 'Thêm dịch vụ', body, footer);
}

async function submitServiceForm(id) {
  const form = document.getElementById('service-form');
  if (!form.checkValidity()) { form.reportValidity(); return; }
  const data = Object.fromEntries(new FormData(form));
  data.unit_price = parseFloat(data.unit_price);
  try {
    if (id) {
      await api('PUT', `/services/${id}`, data);
      showToast('Cập nhật dịch vụ thành công');
    } else {
      await api('POST', '/services', data);
      showToast('Thêm dịch vụ thành công');
    }
    document.querySelector('[data-bs-dismiss="modal"]')?.click();
    renderServices();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function deleteService(id, name) {
  if (!confirm(`Xóa dịch vụ "${name}"?`)) return;
  try {
    await api('DELETE', `/services/${id}`);
    showToast('Xóa dịch vụ thành công');
    renderServices();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}
