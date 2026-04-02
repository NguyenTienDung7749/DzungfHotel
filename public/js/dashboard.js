async function loadDashboard() {
  const el = document.getElementById('page-dashboard');
  el.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
  try {
    const data = await api('GET', '/dashboard/stats');

    const occupancyRate = data.rooms.total > 0
      ? Math.round(((data.rooms.occupied + data.rooms.reserved) / data.rooms.total) * 100)
      : 0;

    el.innerHTML = `
      <!-- Stat cards -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                <i class="bi bi-door-open"></i>
              </div>
              <div>
                <div class="fw-bold fs-4">${data.rooms.total}</div>
                <div class="text-muted small">Tổng phòng</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="stat-icon bg-success bg-opacity-10 text-success">
                <i class="bi bi-check-circle"></i>
              </div>
              <div>
                <div class="fw-bold fs-4">${data.rooms.available}</div>
                <div class="text-muted small">Phòng trống</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-person-fill"></i>
              </div>
              <div>
                <div class="fw-bold fs-4">${data.rooms.occupied}</div>
                <div class="text-muted small">Có khách</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                <i class="bi bi-currency-dollar"></i>
              </div>
              <div>
                <div class="fw-bold fs-5">${fmt.currency(data.monthRevenue)}</div>
                <div class="text-muted small">Doanh thu tháng</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Second row -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="card stat-card shadow-sm border-start border-4 border-primary">
            <div class="card-body">
              <div class="text-muted small mb-1">Khách hàng</div>
              <div class="fw-bold fs-4"><i class="bi bi-people text-primary me-1"></i>${data.customers}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card stat-card shadow-sm border-start border-4 border-info">
            <div class="card-body">
              <div class="text-muted small mb-1">Đặt phòng hiện tại</div>
              <div class="fw-bold fs-4"><i class="bi bi-calendar-check text-info me-1"></i>${data.activeBookings}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card stat-card shadow-sm border-start border-4 border-success">
            <div class="card-body">
              <div class="text-muted small mb-1">Check-in hôm nay</div>
              <div class="fw-bold fs-4"><i class="bi bi-box-arrow-in-right text-success me-1"></i>${data.todayCheckins}</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card stat-card shadow-sm border-start border-4 border-warning">
            <div class="card-body">
              <div class="text-muted small mb-1">Check-out hôm nay</div>
              <div class="fw-bold fs-4"><i class="bi bi-box-arrow-right text-warning me-1"></i>${data.todayCheckouts}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Room status chart & Recent bookings -->
      <div class="row g-3">
        <div class="col-md-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold">Trạng thái phòng</div>
            <div class="card-body">
              <div class="mb-3">
                <div class="d-flex justify-content-between mb-1">
                  <span class="small">Trống</span><span class="small fw-bold text-success">${data.rooms.available}</span>
                </div>
                <div class="progress mb-2" style="height:8px">
                  <div class="progress-bar bg-success" style="width:${(data.rooms.available/data.rooms.total*100)||0}%"></div>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="small">Có khách</span><span class="small fw-bold text-danger">${data.rooms.occupied}</span>
                </div>
                <div class="progress mb-2" style="height:8px">
                  <div class="progress-bar bg-danger" style="width:${(data.rooms.occupied/data.rooms.total*100)||0}%"></div>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="small">Đã đặt</span><span class="small fw-bold text-warning">${data.rooms.reserved}</span>
                </div>
                <div class="progress mb-2" style="height:8px">
                  <div class="progress-bar bg-warning" style="width:${(data.rooms.reserved/data.rooms.total*100)||0}%"></div>
                </div>
                <div class="d-flex justify-content-between mb-1">
                  <span class="small">Bảo trì</span><span class="small fw-bold text-secondary">${data.rooms.maintenance}</span>
                </div>
                <div class="progress" style="height:8px">
                  <div class="progress-bar bg-secondary" style="width:${(data.rooms.maintenance/data.rooms.total*100)||0}%"></div>
                </div>
              </div>
              <div class="text-center mt-3">
                <span class="fs-3 fw-bold text-primary">${occupancyRate}%</span><br>
                <span class="text-muted small">Tỷ lệ lấp đầy</span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
              <span>Đặt phòng gần đây</span>
              <button class="btn btn-sm btn-outline-primary" onclick="navigateTo('bookings')">Xem tất cả</button>
            </div>
            <div class="card-body p-0">
              <div class="table-responsive">
                <table class="table table-hover mb-0">
                  <thead>
                    <tr>
                      <th class="ps-3">#</th>
                      <th>Khách hàng</th>
                      <th>Phòng</th>
                      <th>Check-in</th>
                      <th>Trạng thái</th>
                      <th>Tổng tiền</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${data.recentBookings.map(b => `
                      <tr>
                        <td class="ps-3 text-muted small">#${b.id}</td>
                        <td>${b.full_name}</td>
                        <td><span class="badge bg-light text-dark border">${b.room_number}</span></td>
                        <td class="small">${fmt.date(b.check_in_date)}</td>
                        <td>${fmt.badgeBooking(b.status)}</td>
                        <td class="fw-semibold text-primary">${fmt.currency(b.total_amount)}</td>
                      </tr>
                    `).join('') || '<tr><td colspan="6" class="text-center py-3 text-muted">Chưa có đặt phòng</td></tr>'}
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  } catch (e) {
    el.innerHTML = `<div class="alert alert-danger">Lỗi tải dữ liệu: ${e.message}</div>`;
  }
}
