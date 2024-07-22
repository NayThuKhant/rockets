import './bootstrap';

// CSS
import '../css/app.css';

// VueJs
import {createApp} from "vue";
import Rockets from "./components/Rockets.vue";

// VueJs
const app = createApp({});
app.component("rockets", Rockets)
app.component('weather', Weather)

app.mount("#app")

// Toast
import "toastify-js/src/toastify.css"
import Weather from "./components/Weather.vue";


