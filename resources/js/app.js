// resources/js/app.js
import './bootstrap';
import Alpine from 'alpinejs';
import './../../vendor/power-components/livewire-powergrid/dist/powergrid'
import flatpickr from "flatpickr"; 

import 'flatpickr/dist/flatpickr.min.css';
import 'tom-select/dist/css/tom-select.bootstrap5.css';
// import "slim-select/dist/slimselect.css";
import '/node_modules/slim-select/dist/slimselect.css';


import SlimSelect from 'slim-select'
window.SlimSelect = SlimSelect

import TomSelect from "tom-select";
window.TomSelect = TomSelect

window.Alpine = Alpine;

Alpine.start();

import.meta.glob([
  '../assets/img/**',
  // '../assets/json/**',
  '../assets/vendor/fonts/**'
]);
