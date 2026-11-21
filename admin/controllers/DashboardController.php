<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        AuthController::requireLogin();

        $this->page('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'pageSubtitle' => 'Overview of your website activity',
            'active' => 'dashboard',
            'revenue' => Payment::revenue(),
            'appointmentCounts' => Appointment::counts(),
            'blogCounts' => BlogPost::counts(),
            'galleryCounts' => GalleryItem::counts(),
            'last30' => Analytics::last30(),
            'dailyEarnings' => Analytics::dailyEarnings(30),
            'dailyBookings' => Analytics::dailyBookings(30),
            'monthlyEarnings' => Analytics::monthlyEarnings(12),
            'byMode' => Analytics::byMode(),
            'byService' => Analytics::byService(),
            'apptByService' => Analytics::appointmentsBy('service'),
            'apptByType' => Analytics::appointmentsBy('appointment_type'),
            'bookingStats' => Analytics::bookingStats(),
            'todayCount' => Appointment::todayCount(),
            'recentAppointments' => Appointment::recent(5),
        ]);
    }
}
