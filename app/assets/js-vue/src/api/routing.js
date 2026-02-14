import Routing from '../../../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router';
import Routes from '../../fos_js_routes.json';

Routing.setRoutingData(Routes);

const suffix = '';
// const suffix = '/public';

export default {
    get(name) {
        return suffix.concat(Routing.generate(name));
    }
}