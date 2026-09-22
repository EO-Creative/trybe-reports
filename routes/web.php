<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DownloadGuestItinerary;

Route::livewire('/', 'orders.index')->name('home');
Route::get('/itinerary/{orderId}', DownloadGuestItinerary::class)->name('download-guest-itinerary');
