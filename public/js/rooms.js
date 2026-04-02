let allRoomTypes = [];

async function loadRooms() {
  const el = document.getElementById('page-rooms');
  el.innerHTML = `
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">Danh sách phòng</h5>
      <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="loadRoomTypes()">
          <i class="bi bi-grid me-1"></i>Loại phòng
        </button>
        <button class="btn btn-primary btn-sm" onclick="showRoomForm()">
          <i class="bi bi-plus-lg me-1"></i>Thêm phòng
        </button>
      </div>
    </div>
    <div class="filter-bar mb-3 d-flex flex-wrap gap-2">
      <select id="filter-room-status" class="form-select form-select-sm" style="width:auto" onchange="applyRoomFilters()">
        <option value="">Tất cả trạng thái</option>
        <option value="available">Trống</option>
        <option value="occupied">Có khách</option>
        <option value="reserved">Đã đặt</option>
        <option value="maintenance">Bảo trì</option>
      </select>
      <select id="filter-room-type" class="form-select form-select-sm" style="width:auto" onchange="applyRoomFilters()">
        <option value="">Tất cả loại</option>
      </select>
      <div class="ms-auto d-flex gap-2">
        <button class="btn btn-sm btn-outline-secondary" id="view-grid" onclick="setRoomView('grid')">
          <i class="bi bi-grid-3x3-gap"></i>
        </button>
        <button class="btn btn-sm btn-outline-secondary" id="view-table" onclick="setRoomView('table')">
          <i class="bi bi-table"></i>
        </button>
      </div>
    </div>
    <div id="rooms-content"></div>
  `;

  allRoomTypes = await api('GET', '/rooms/types');
  const sel = document.getElementById('filter-room-type');
  allRoomTypes.forEach(t => sel.insertAdjacentHTML('beforeend', `<option value="${t.id}">${t.name}</option>`));

  await applyRoomFilters();
}

let roomViewMode = 'grid';
function setRoomView(mode) {
  roomViewMode = mode;
  document.getElementById('view-grid')?.classList.toggle('active', mode === 'grid');
  document.getElementById('view-table')?.classList.toggle('active', mode === 'table');
  applyRoomFilters();
}

async function applyRoomFilters() {
  const status = document.getElementById('filter-room-status')?.value || '';
  const type = document.getElementById('filter-room-type')?.value || '';
  let url = '/rooms?';
  if (status) url += `status=${status}&`;
  if (type) url += `type=${type}`;
  const rooms = await api('GET', url);
  renderRooms(rooms);
}

function renderRooms(rooms) {
  const el = document.getElementById('rooms-content');
  if (!el) return;
  if (roomViewMode === 'grid') {
    el.innerHTML = `<div class="row g-3">${rooms.map(r => `
      <div class="col-6 col-md-4 col-lg-3 col-xl-2">
        <div class="card room-card status-${r.status} h-100" onclick="showRoomDetail(${r.id})">
          <div class="card-body text-center p-3">
            <div class="fs-2 mb-1"><i class="bi bi-door-${r.status === 'occupied' ? 'closed' : 'open'}"></i></div>
            <div class="fw-bold fs-5">P.${r.room_number}</div>
            <div class="small text-muted mb-2">${r.type_name} - Tầng ${r.floor}</div>
            ${fmt.badgeRoom(r.status)}
            <div class="small text-primary mt-1">${fmt.currency(r.price_per_night)}<span class="text-muted">/đêm</span></div>
          </div>
        </div>
      </div>`).join('') || '<div class="col-12 text-center text-muted py-4">Không có phòng nào</div>'}</div>`;
  } else {
    el.innerHTML = `
      <div class="card shadow-sm">
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead><tr>
              <th class="ps-3">Số phòng</th><th>Loại phòng</th><th>Tầng</th>
              <th>Giá/đêm</th><th>Sức chứa</th><th>Trạng thái</th>
              <th>Mô tả</th><th>Thao tác</th>
            </tr></thead>
            <tbody>${rooms.map(r => `<tr>
              <td class="ps-3 fw-semibold">P.${r.room_number}</td>
              <td>${r.type_name}</td>
              <td>Tầng ${r.floor}</td>
              <td class="text-primary">${fmt.currency(r.price_per_night)}</td>
              <td>${r.capacity} người</td>
              <td>${fmt.badgeRoom(r.status)}</td>
              <td class="text-muted small">${r.description || ''}</td>
              <td>
                <button class="btn btn-xs btn-outline-primary btn-sm me-1" onclick="showRoomForm(${r.id})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-xs btn-outline-danger btn-sm" onclick="deleteRoom(${r.id},'${r.room_number}')"><i class="bi bi-trash"></i></button>
              </td>
            </tr>`).join('') || '<tr><td colspan="8" class="text-center py-3 text-muted">Không có phòng nào</td></tr>'}</tbody>
          </table>
        </div>
      </div>`;
  }
}

async function showRoomDetail(id) {
  const room = await api('GET', `/rooms/${id}`);
  const body = `
    <div class="row g-3">
      <div class="col-md-6">
        <div class="p-3 bg-light rounded">
          <div class="fs-1 text-center mb-2"><i class="bi bi-door-open text-primary"></i></div>
          <h4 class="text-center">Phòng ${room.room_number}</h4>
          <div class="text-center">${fmt.badgeRoom(room.status)}</div>
        </div>
      </div>
      <div class="col-md-6">
        <table class="table table-sm table-borderless">
          <tr><td class="text-muted">Loại phòng:</td><td><strong>${room.type_name}</strong></td></tr>
          <tr><td class="text-muted">Tầng:</td><td>${room.floor}</td></tr>
          <tr><td class="text-muted">Sức chứa:</td><td>${room.capacity} người</td></tr>
          <tr><td class="text-muted">Giá/đêm:</td><td class="text-primary fw-bold">${fmt.currency(room.price_per_night)}</td></tr>
          <tr><td class="text-muted">Mô tả:</td><td>${room.description || '-'}</td></tr>
        </table>
      </div>
    </div>`;
  const footer = `
    <button class="btn btn-outline-primary" onclick="showRoomForm(${room.id})">
      <i class="bi bi-pencil me-1"></i>Chỉnh sửa
    </button>
    <button class="btn btn-outline-success" onclick="navigateTo('bookings'); closeModal(lastModal)">
      <i class="bi bi-calendar-plus me-1"></i>Đặt phòng
    </button>`;
  lastModal = showModal(`Chi tiết phòng ${room.room_number}`, body, footer);
}

let lastModal = null;

async function showRoomForm(id = null) {
  const types = allRoomTypes.length ? allRoomTypes : await api('GET', '/rooms/types');
  let room = { status: 'available' };
  if (id) room = await api('GET', `/rooms/${id}`);

  const typeOptions = types.map(t =>
    `<option value="${t.id}" ${room.room_type_id == t.id ? 'selected' : ''}>${t.name} - ${fmt.currency(t.price_per_night)}/đêm</option>`
  ).join('');

  const body = `
    <form id="room-form">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Số phòng <span class="text-danger">*</span></label>
          <input name="room_number" class="form-control" value="${room.room_number || ''}" required placeholder="VD: 101" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Loại phòng <span class="text-danger">*</span></label>
          <select name="room_type_id" class="form-select" required>${typeOptions}</select>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Tầng <span class="text-danger">*</span></label>
          <input name="floor" type="number" class="form-control" value="${room.floor || 1}" required min="1" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Trạng thái</label>
          <select name="status" class="form-select">
            <option value="available" ${room.status==='available'?'selected':''}>Trống</option>
            <option value="occupied" ${room.status==='occupied'?'selected':''}>Có khách</option>
            <option value="reserved" ${room.status==='reserved'?'selected':''}>Đã đặt</option>
            <option value="maintenance" ${room.status==='maintenance'?'selected':''}>Bảo trì</option>
          </select>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Mô tả</label>
          <textarea name="description" class="form-control" rows="2">${room.description || ''}</textarea>
        </div>
      </div>
    </form>`;
  const footer = `
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
    <button type="button" class="btn btn-primary" onclick="submitRoomForm(${id || 'null'})">
      <i class="bi bi-save me-1"></i>${id ? 'Cập nhật' : 'Thêm phòng'}
    </button>`;
  showModal(id ? 'Chỉnh sửa phòng' : 'Thêm phòng mới', body, footer);
}

async function submitRoomForm(id) {
  const form = document.getElementById('room-form');
  if (!form.checkValidity()) { form.reportValidity(); return; }
  const data = Object.fromEntries(new FormData(form));
  data.floor = parseInt(data.floor);
  try {
    if (id) {
      await api('PUT', `/rooms/${id}`, data);
      showToast('Cập nhật phòng thành công');
    } else {
      await api('POST', '/rooms', data);
      showToast('Thêm phòng thành công');
    }
    document.querySelector('[data-bs-dismiss="modal"]')?.click();
    loadRooms();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function deleteRoom(id, number) {
  if (!confirm(`Xóa phòng ${number}?`)) return;
  try {
    await api('DELETE', `/rooms/${id}`);
    showToast('Xóa phòng thành công');
    loadRooms();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}

async function loadRoomTypes() {
  const types = await api('GET', '/rooms/types');
  const body = `
    <div class="d-flex justify-content-end mb-3">
      <button class="btn btn-primary btn-sm" onclick="showRoomTypeForm()">
        <i class="bi bi-plus-lg me-1"></i>Thêm loại phòng
      </button>
    </div>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead><tr>
          <th>Tên loại</th><th>Mô tả</th><th>Giá/đêm</th><th>Sức chứa</th><th>Thao tác</th>
        </tr></thead>
        <tbody>${types.map(t => `<tr>
          <td class="fw-semibold">${t.name}</td>
          <td class="text-muted small">${t.description || ''}</td>
          <td class="text-primary fw-bold">${fmt.currency(t.price_per_night)}</td>
          <td>${t.capacity} người</td>
          <td>
            <button class="btn btn-sm btn-outline-primary" onclick="showRoomTypeForm(${t.id})">
              <i class="bi bi-pencil"></i>
            </button>
          </td>
        </tr>`).join('')}</tbody>
      </table>
    </div>`;
  showModal('Quản lý loại phòng', body);
}

async function showRoomTypeForm(id = null) {
  let type = { capacity: 2 };
  if (id) {
    const types = await api('GET', '/rooms/types');
    type = types.find(t => t.id == id) || type;
  }
  const body = `
    <form id="type-form">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Tên loại <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="${type.name || ''}" required />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Giá/đêm (VNĐ) <span class="text-danger">*</span></label>
          <input name="price_per_night" type="number" class="form-control" value="${type.price_per_night || ''}" required min="0" />
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Sức chứa (người)</label>
          <input name="capacity" type="number" class="form-control" value="${type.capacity || 2}" min="1" />
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Mô tả</label>
          <textarea name="description" class="form-control" rows="2">${type.description || ''}</textarea>
        </div>
      </div>
    </form>`;
  const footer = `
    <button class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
    <button class="btn btn-primary" onclick="submitRoomTypeForm(${id || 'null'})">
      ${id ? 'Cập nhật' : 'Thêm loại phòng'}
    </button>`;
  showModal(id ? 'Sửa loại phòng' : 'Thêm loại phòng', body, footer);
}

async function submitRoomTypeForm(id) {
  const form = document.getElementById('type-form');
  if (!form.checkValidity()) { form.reportValidity(); return; }
  const data = Object.fromEntries(new FormData(form));
  data.price_per_night = parseFloat(data.price_per_night);
  data.capacity = parseInt(data.capacity);
  try {
    if (id) {
      await api('PUT', `/rooms/types/${id}`, data);
      showToast('Cập nhật loại phòng thành công');
    } else {
      await api('POST', '/rooms/types', data);
      showToast('Thêm loại phòng thành công');
    }
    document.querySelector('[data-bs-dismiss="modal"]')?.click();
    loadRoomTypes();
  } catch (e) {
    showToast(e.message, 'danger');
  }
}
