import './global/form-validation';
import './global/data-loading';
import './global/password-strength';
import './global/password-toggle';
import './global/theme';
import './reveal';

if (document.querySelector('[data-reports-page]')) {
    import('./reports');
}
