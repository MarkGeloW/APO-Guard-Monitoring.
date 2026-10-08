import Alpine from 'alpinejs';
import { initBladeConfirmations } from './lib/sweetalert';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', initBladeConfirmations);
