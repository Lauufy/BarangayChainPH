import './global/form-validation';
import './global/data-loading';
import './global/password-strength';
import './global/password-toggle';
import './global/theme';
import './reveal';

if (document.querySelector('[data-reports-page]')) {
    import('./reports');
}

if (document.getElementById('id-scanner')) {
    window.idImageValidatorReady = () => import('./identity/id-image-validator.js');
    document.dispatchEvent(new Event('id-image-validator-ready'));
}
