  @extends('admin.layouts.master')
  @section('content')
  <main id="main-container">
      <!-- Page Content -->
      <div class="content">
          <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
              <div>
                  <h1 class="h3 fw-bold mb-0">Hospital Dashboard</h1>
                  <p class="text-muted mb-0">Overview of today's hospital activity — MediCare HMS</p>
              </div>
              <div class="d-flex gap-2">
              </div>
          </div>

          <div class="row items-push">
              <div class="col-6 col-lg-3">
                  <a class="block block-rounded block-link-shadow text-center" href="hm_patients.html">
                      <div class="block-content block-content-full">
                          <div class="fs-2 fw-semibold text-primary">1,284</div>
                      </div>
                      <div class="block-content py-2 bg-body-light">
                          <p class="fw-medium fs-sm text-muted mb-0">Total Patients</p>
                      </div>
                  </a>
              </div>
              <div class="col-6 col-lg-3">
                  <a class="block block-rounded block-link-shadow text-center" href="hm_doctors.html">
                      <div class="block-content block-content-full">
                          <div class="fs-2 fw-semibold text-info">42</div>
                      </div>
                      <div class="block-content py-2 bg-body-light">
                          <p class="fw-medium fs-sm text-muted mb-0">Doctors on Staff</p>
                      </div>
                  </a>
              </div>
              <div class="col-6 col-lg-3">
                  <a class="block block-rounded block-link-shadow text-center" href="hm_appointments.html">
                      <div class="block-content block-content-full">
                          <div class="fs-2 fw-semibold text-warning">37</div>
                      </div>
                      <div class="block-content py-2 bg-body-light">
                          <p class="fw-medium fs-sm text-muted mb-0">Today&#39;s Appointments</p>
                      </div>
                  </a>
              </div>
              <div class="col-6 col-lg-3">
                  <a class="block block-rounded block-link-shadow text-center" href="hm_admissions.html">
                      <div class="block-content block-content-full">
                          <div class="fs-2 fw-semibold text-success">18 / 60</div>
                      </div>
                      <div class="block-content py-2 bg-body-light">
                          <p class="fw-medium fs-sm text-muted mb-0">Beds Available</p>
                      </div>
                  </a>
              </div>
          </div>

          <div class="row">

              <div class="col-lg-7">
                  <div class="block block-rounded">
                      <div class="block-header block-header-default">
                          <h3 class="block-title">Ward Occupancy</h3>
                      </div>
                      <div class="block-content block-content-full">
                          <div class="table-responsive">
                              <table class="table table-striped table-vcenter">
                                  <thead>
                                      <tr>
                                          <th class="fs-sm">Ward</th>
                                          <th class="d-none d-sm-table-cell text-center fs-sm">Type</th>
                                          <th class="text-center fs-sm">Total Beds</th>
                                          <th class="text-center fs-sm">Occupied</th>
                                          <th class="text-center fs-sm">Available</th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      <tr>
                                          <td>Ward A &mdash; General</td>
                                          <td class="d-none d-sm-table-cell text-center">General</td>
                                          <td class="text-center">20</td>
                                          <td class="text-center">15</td>
                                          <td class="text-center text-success fw-semibold">5</td>
                                      </tr>
                                      <tr>
                                          <td>Ward B &mdash; Cabin</td>
                                          <td class="d-none d-sm-table-cell text-center">Cabin</td>
                                          <td class="text-center">15</td>
                                          <td class="text-center">9</td>
                                          <td class="text-center text-success fw-semibold">6</td>
                                      </tr>
                                      <tr>
                                          <td>ICU</td>
                                          <td class="d-none d-sm-table-cell text-center">Critical Care</td>
                                          <td class="text-center">10</td>
                                          <td class="text-center">9</td>
                                          <td class="text-center text-danger fw-semibold">1</td>
                                      </tr>
                                      <tr>
                                          <td>Pediatric Ward</td>
                                          <td class="d-none d-sm-table-cell text-center">Pediatric</td>
                                          <td class="text-center">15</td>
                                          <td class="text-center">9</td>
                                          <td class="text-center text-success fw-semibold">6</td>
                                      </tr>
                                  </tbody>
                              </table>
                          </div>
                      </div>
                  </div>
              </div>
              <div class="col-lg-5">
                  <div class="block block-rounded">
                      <div class="block-header block-header-default">
                          <h3 class="block-title">Revenue Snapshot</h3>
                      </div>
                      <div class="block-content">
                          <div class="row text-center items-push pull-t">
                              <div class="col-6">
                                  <div class="fs-sm fw-semibold text-uppercase text-muted">This Month</div>
                                  <div class="fs-3 fw-bold text-success">&#2547;5,42,300</div>
                              </div>
                              <div class="col-6">
                                  <div class="fs-sm fw-semibold text-uppercase text-muted">Due Payments</div>
                                  <div class="fs-3 fw-bold text-danger">&#2547;86,150</div>
                              </div>
                          </div>
                          <hr>
                          <p class="fs-sm text-muted mb-1">Collected vs Target</p>
                          <div class="progress mb-3" style="height: 8px;">
                              <div class="progress-bar bg-success" style="width: 78%"></div>
                          </div>
                          <a class="btn btn-sm btn-alt-primary w-100" href="hm_invoices.html">View All Invoices</a>
                      </div>
                  </div>
              </div>
          </div>
          <div class="block block-rounded">
              <div class="block-header block-header-default">
                  <h3 class="block-title">Today&#39;s Appointments</h3>
              </div>
              <div class="block-content block-content-full">
                  <div class="table-responsive">
                      <table class="table table-striped table-vcenter">
                          <thead>
                              <tr>
                                  <th class="fs-sm">Patient</th>
                                  <th class="d-none d-sm-table-cell text-center fs-sm">Doctor</th>
                                  <th class="d-none d-sm-table-cell text-center fs-sm">Department</th>
                                  <th class="d-none d-sm-table-cell text-center fs-sm">Time</th>
                                  <th class="d-none d-sm-table-cell text-center fs-sm">Status</th>
                                  <th class="text-center fs-sm" style="width: 110px;">Actions</th>
                              </tr>
                          </thead>
                          <tbody>
                              <tr>
                                  <td><a class="fw-semibold" href="hm_patients.html">Rafiq Ahmed</a></td>
                                  <td class="d-none d-sm-table-cell text-center">Dr. Nusrat Jahan</td>
                                  <td class="d-none d-sm-table-cell text-center">Cardiology</td>
                                  <td class="d-none d-sm-table-cell text-center">10:30 AM</td>
                                  <td class="text-center"><span class="badge bg-success">Checked-in</span></td>
                                  <td class="text-center"><a class="btn btn-sm btn-alt-secondary"
                                          href="javascript:void(0)" data-bs-toggle="tooltip" title="View"><i
                                              class="fa fa-fw fa-eye"></i></a>
                                      <a class="btn btn-sm btn-alt-secondary" href="javascript:void(0)"
                                          data-bs-toggle="tooltip" title="Delete"><i
                                              class="fa fa-fw fa-times"></i></a>
                                  </td>
                              </tr>
                              <tr>
                                  <td><a class="fw-semibold" href="hm_patients.html">Farhana Akter</a></td>
                                  <td class="d-none d-sm-table-cell text-center">Dr. Imran Kabir</td>
                                  <td class="d-none d-sm-table-cell text-center">Orthopedics</td>
                                  <td class="d-none d-sm-table-cell text-center">11:00 AM</td>
                                  <td class="text-center"><span class="badge bg-warning">Waiting</span></td>
                                  <td class="text-center"><a class="btn btn-sm btn-alt-secondary"
                                          href="javascript:void(0)" data-bs-toggle="tooltip" title="View"><i
                                              class="fa fa-fw fa-eye"></i></a>
                                      <a class="btn btn-sm btn-alt-secondary" href="javascript:void(0)"
                                          data-bs-toggle="tooltip" title="Delete"><i
                                              class="fa fa-fw fa-times"></i></a>
                                  </td>
                              </tr>
                              <tr>
                                  <td><a class="fw-semibold" href="hm_patients.html">Shakil Hossain</a></td>
                                  <td class="d-none d-sm-table-cell text-center">Dr. Nusrat Jahan</td>
                                  <td class="d-none d-sm-table-cell text-center">Cardiology</td>
                                  <td class="d-none d-sm-table-cell text-center">11:45 AM</td>
                                  <td class="text-center"><span class="badge bg-info">Scheduled</span></td>
                                  <td class="text-center"><a class="btn btn-sm btn-alt-secondary"
                                          href="javascript:void(0)" data-bs-toggle="tooltip" title="View"><i
                                              class="fa fa-fw fa-eye"></i></a>
                                      <a class="btn btn-sm btn-alt-secondary" href="javascript:void(0)"
                                          data-bs-toggle="tooltip" title="Delete"><i
                                              class="fa fa-fw fa-times"></i></a>
                                  </td>
                              </tr>
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>

      </div>
      <!-- END Page Content -->
  </main>
  @endsection

