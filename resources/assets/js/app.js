/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */


import Popper from "popper.js";
window.Popper = Popper;

import jQuery from "jquery";
window.$ = window.jQuery = jQuery;

import "bootstrap";
import 'admin-lte';
import Vue from 'vue';
window.Vue = Vue;

import Vuetify from 'vuetify';
Vue.use(Vuetify);

import VueSweetalert2 from "vue-sweetalert2";
import "sweetalert2/dist/sweetalert2.min.css";
import Swal from "sweetalert2/dist/sweetalert2.js";
Vue.use(VueSweetalert2);

const Toast = Swal.mixin({
  toast: true,
  position: "top",
  timer: 1500,
  showConfirmButton: true
});
window.Toast = Toast;

import money from "v-money";
Vue.use(money, { precision: 4 });

import VueTheMask from "vue-the-mask";
Vue.use(VueTheMask);

import vClickOutside from "v-click-outside";
Vue.use(vClickOutside);
import VueFlatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/flatpickr.css';
Vue.use(VueFlatPickr);

import Pagination from 'vue-pagination-2';
import ContentHeader from "./cms/components/ContentHeader.vue";
import UIForm from "./cms/components/UIForm.vue";
import UIPhone from "./cms/components/UIPhone.vue";
import UIMoney from "./cms/components/UIMoney.vue";
import UITextarea from "./cms/components/UITextarea.vue";
import UIMultiSelect from "./cms/components/UIMultiSelect.vue";
import UISelectWithParse from "./cms/components/UISelectWithParse.vue";
import UISelect from "./cms/components/UISelect.vue";
import Tabs from "./cms/components/Tabs.vue";
import FileUpload from "./cms/components/FileUpload.vue";
import DataTable from "./cms/components/DataTable.vue";
import UIPercent from "./cms/components/UIPercent.vue";
import UIMaskInput from "./cms/components/UIMaskInput.vue";
import Alert from "./cms/components/Alert.vue";
import Checkboxes from "./cms/components/Checkboxes.vue";
import Radios from "./cms/components/Radios.vue";
import DropdownList from "./cms/components/DropdownList.vue";
import DropdownEvents from "./cms/components/DropdownEvents.vue";
import CidadeBairro from "./cms/components/Cidade-bairro.vue";

Vue.component('pagination', Pagination);
Vue.component("content-header", ContentHeader);
Vue.component("data-table", DataTable);

Vue.component('file-upload', FileUpload);
Vue.component("tabs", Tabs);
Vue.component("ui-form", UIForm);
Vue.component("ui-select", UISelect);
Vue.component("ui-select-parse", UISelectWithParse);
Vue.component("multi-select", UIMultiSelect);
Vue.component("ui-textarea", UITextarea);
Vue.component("ui-money", UIMoney);
Vue.component("ui-phone", UIPhone);

Vue.component("ui-percent", UIPercent);
Vue.component("ui-mask-input", UIMaskInput);
Vue.component("alert", Alert);
Vue.component("checkboxes", Checkboxes);
Vue.component("radios", Radios);
Vue.component("dropdown-list", DropdownList);
Vue.component("dropdown-events", DropdownEvents);
Vue.component("cidade-bairro", CidadeBairro);


// Vue.component("the-gallery", require("./front/TheGallery.vue").default);
// Vue.component("the-newsletter", require("./front/TheNewsletter.vue").default);

// Vue.component("the-banner", require("./front/TheBanner.vue").default);

// Vue.component(
//   "the-city-selector",
//   require("./cms/components/UISelectCityAndState.vue").default
// );

const app = new Vue({
  el: "#app",
  vuetify: new Vuetify(),
});

