@extends('admin.layouts.master')

 @section('title', 'Appointments - Create')

 @section('content')

     <div class="block block-rounded block-transparent mt-5">

         <div class="block-content fs-sm mt-2">
             <x-admin.phead title="Book Appointment" subtitle="Schedule a new appointment from this section">

                 <a href="{{ route('appointments.index') }}" type="button" class="btn btn-sm btn-primary">
                     <i class="fa fa-plus opacity-50 me-1"></i> back to appointments
                 </a>

             </x-admin.phead>

             @if (session('error'))
                 <div class="alert alert-danger alert-dismissible fade show" role="alert">
                     {{ session('error') }}
                     <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="close"></button>
                 </div>
             @endif


             <div class="card mt-3">
                 <div class="card-body">
                     <form action="{{ route('appointments.store') }}" method="POST">
                         @csrf
                         <div class="row">
                             <div class="col-md-6 mb-4">
                                 <label class="form-label" for="ap-patient">Patient</label>
                                 <select class="form-select" name="patient_id" id="ap-patient">
                                     <option value="" selected disabled>Select Patient</option>
                                     @foreach ($patients as $patient)
                                         <option value="{{ $patient->id }}" @selected(old('patient_id') == $patient->id)>
                                             {{ $patient->name }}
                                         </option>
                                     @endforeach
                                 </select>
                                 <x-admin.error-msg name="patient_id" />
                             </div>
                             <div class="col-md-6 mb-4">
                                 <label class="form-label" for="ap-doctor">Doctor</label>
                                 <select class="form-select" name="doctor_id" id="ap-doctor">
                                     <option value="" selected disabled>Select Doctor</option>
                                     @foreach ($doctors as $doctor)
                                         <option value="{{ $doctor->id }}" @selected(old('doctor_id') == $doctor->id)>
                                             {{ $doctor->user->name ?? 'Doctor #' . $doctor->id }}{{ $doctor->department ? ' — ' . $doctor->department->name : '' }}
                                         </option>
                                     @endforeach
                                 </select>
                                 <x-admin.error-msg name="doctor_id" />
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-md-4 mb-4">
                                 <label class="form-label" for="ap-date">Date</label>
                                 <input type="date" class="form-control" name="appointment_date"
                                     value="{{ old('appointment_date') }}">
                                 <x-admin.error-msg name="appointment_date" />
                             </div>
                             <div class="col-md-4 mb-4">
                                 <label class="form-label" for="ap-time">Time</label>
                                 <input type="time" class="form-control" name="appointment_time"
                                     value="{{ old('appointment_time') }}">
                                 <x-admin.error-msg name="appointment_time" />
                             </div>
                             <div class="col-md-4 mb-4">
                                 <label class="form-label" for="ap-status">Status</label>
                                 <select class="form-select" name="status" id="ap-status">
                                     <option value="Scheduled" @selected(old('status') == 'Scheduled')>Scheduled</option>
                                     <option value="Checked-in" @selected(old('status') == 'Checked-in')>Checked-in</option>
                                     <option value="Waiting" @selected(old('status') == 'Waiting')>Waiting</option>
                                     <option value="Completed" @selected(old('status') == 'Completed')>Completed</option>
                                     <option value="Cancelled" @selected(old('status') == 'Cancelled')>Cancelled</option>
                                 </select>
                                 <x-admin.error-msg name="status" />
                             </div>
                         </div>
                         <div class="row">
                             <div class="col-md-12 mb-4">
                                 <label class="form-label" for="ap-reason">Reason for Visit</label>
                                 <textarea class="form-control" name="reason" id="ap-reason" rows="2">{{ old('reason') }}</textarea>
                                 <x-admin.error-msg name="reason" />
                             </div>
                         </div>

                         <div class="block-content block-content-full text-end bg-body">
                             <a href="{{ route('appointments.index') }}" class="btn btn-sm btn-alt-secondary me-1">
                                 Cancel
                             </a>
                             <button type="submit" class="btn btn-sm btn-primary">Save
                                 Appointment</button>
                         </div>
                     </form>
                 </div>
             </div>

         </div>

     </div>

 @endsection